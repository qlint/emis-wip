<?php
$app->post('/parentLogin', function () use($app) {
  // Log parent in
  $allPostVars = $app->request->post();
  $username = $allPostVars['user_name'];
  $pwd = $allPostVars['user_pwd'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();

    $userCheckQry = $db->query("SELECT (CASE
                                    		WHEN EXISTS (SELECT username FROM parents WHERE username = '$username') THEN 'proceed'
                                    		ELSE 'stop'
                                    	END) AS status");
    $userStatus = $userCheckQry->fetch(PDO::FETCH_OBJ);
    $userStatus = $userStatus->status;

    if($userStatus === "proceed"){

            $sth = $db->prepare("SELECT parents.parent_id, username, active, first_name, middle_name, last_name, email,
                          first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name, device_user_id, guardian_id AS school_guardian_id
                        FROM parents
                        INNER JOIN parent_students ON parents.parent_id = parent_students.parent_id
                        WHERE username= :username
                        AND password = :password
                        AND active is true");
            $sth->execute( array(':username' => $username, ':password' => $pwd) );
            $result = $sth->fetch(PDO::FETCH_OBJ);

            if($result) {

                // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app login')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

              // get the parents' students and add to result
              $sth1 = $db->prepare("SELECT student_id, guardian_id AS school_guardian_id, subdomain, dbusername, dbpassword
                                    FROM parent_students
                                    WHERE parent_id = :parentId
                                    ORDER BY subdomain");
              $sth1->execute(array(':parentId' => $result->parent_id));
              $students = $sth1->fetchAll(PDO::FETCH_OBJ);
              $db = null;

              $studentDetails = Array();
              $curSubDomain = '';
              $studentsBySchool = Array();
              foreach( $students as $student )
              {
                // get individual student details
                // only get new db connection if different subdomain
                if( $curSubDomain != $student->subdomain )
                {
                  if( $db !== null ) $db = null;
                  $db = setDBConnection($student->subdomain);
                }
                $sth3 = $db->prepare("SELECT student_id, first_name, middle_name, last_name, student_image, admission_number,
                               first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                               students.active, class_name, class_id, class_cat_id, report_card_type, admission_date, student_category, gender, dob, payment_method, payment_plan_name,
                               (SELECT value FROM app.settings WHERE name = 'School Name') as school_name,
                               (SELECT value FROM app.settings WHERE name = 'subdomain') as school_subdomain,
                               (SELECT value FROM app.settings WHERE name = 'Use Feedback') as use_feedback
                            FROM app.students
                            INNER JOIN app.classes ON students.current_class = classes.class_id
                            LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
                            WHERE student_id = :studentId  AND students.active IS TRUE");
                $sth3->execute(array(':studentId' => $student->student_id));
                $details = $sth3->fetch(PDO::FETCH_OBJ);

                if( $details ) {
                  $details->school = $student->subdomain;

                  if( $details->student_id !== null )
                  {
                    $studentDetails[] = $details;
                    $curSubDomain = $student->subdomain;

                    /* build an array for grabbing news */
                    $studentsBySchool[$curSubDomain][] = $student->student_id;
                  }
                }
              }

              /* get news for students, only want new once per school, and student specific news */
              $news = Array();
              foreach( $studentsBySchool as $school => $students )
              {
                if( $db !== null ) $db = null;
                $db = setDBConnection($school);

                $sth5 = $db->prepare("SELECT
                            com_id, com_date, communications.creation_date, com_type, subject, message, send_as_email, send_as_sms,
                            employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                            audience, attachment, reply_to,
                            students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
                            guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
                            communications.guardian_id, communications.student_id, classes.class_name, post_status,
                            sent, sent_date, message_from,
                            case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method
                          FROM app.communications
                          LEFT JOIN app.students ON communications.student_id = students.student_id
                          LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
                          LEFT JOIN app.classes ON communications.class_id = classes.class_id
                          INNER JOIN app.employees ON communications.message_from = employees.emp_id
                          INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
                          INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
                          INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
                          WHERE communications.student_id = any(:studentIds) OR communications.student_id is null
                          AND communications.sent IS TRUE
                          AND communications.post_status_id = 1
                          AND (communications.class_id = any(select current_class from app.students where student_id = any(:studentIds))
                          OR communications.class_id is null)
                          AND communications.audience_id NOT IN (3,4,7,9)
                          --AND students.active IS TRUE
                          AND communications.com_type_id NOT IN (6)
                          AND date_trunc('year', communications.creation_date) =  date_trunc('year', now())
                          ORDER BY creation_date desc");

                $studentsArray = "{" . implode(',',$students) . "}";
                $sth5->execute(array(':studentIds' => $studentsArray));
                $news[$school] = $sth5->fetchAll(PDO::FETCH_OBJ);

              }

              /* get sent messages by student's parent */
              $feedback = Array();
              foreach( $studentsBySchool as $school => $students )
              {
                if( $db !== null ) $db = null;
                $db = setDBConnection($school);

                $sth6 = $db->prepare("SELECT cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                        cf.message_from as posted_by,
                        s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                        g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                        c.class_name
                    FROM app.communication_feedback cf
                    LEFT JOIN app.students s USING (student_id)
                    LEFT JOIN app.guardians g USING (guardian_id)
                    LEFT JOIN app.classes c USING (class_id)
                    WHERE cf.student_id = any(:studentIds)
                    AND s.active IS TRUE
                    GROUP BY cf.com_feedback_id, subject, message, student_name, parent_full_name, class_name, opened
                    ORDER BY post_id DESC");

                $studentsArray = "{" . implode(',',$students) . "}";
                $sth6->execute(array(':studentIds' => $studentsArray));
                $feedback[$school] = $sth6->fetchAll(PDO::FETCH_OBJ);

              }

              $result->students = $studentDetails;
              $result->news = $news;
              $result->feedback = $feedback;

              $app->response->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              $db = null;

              echo json_encode(array('response' => 'success', 'data' => $result ));

            } else {
                $app->response->setStatus(200);
                $app->response()->headers->set('Content-Type', 'application/json');
                echo json_encode(array("response" => "error", "code" => 3, "data" => 'The password you have entered is incorrect. Please check the spelling and / or capitalization.'));
                /*
                $responseJSON = json_encode($theResponse);
                // throw new PDOException('The username or password you have entered is incorrect.');
                throw new PDOException($responseJSON);
                // throw new PDOException('The username you entered does not exist. Please confirm and try again.');
                */
            }

    } else {
        if($userStatus === "stop"){

            // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app failed login')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array("response" => "error", "code" => 2, "data" => 'The username you entered does not exist. Please confirm and try again.'));
            /*
            $theResponse->data = 'The username you entered does not exist. Please confirm and try again.';
            $theResponse->status = array(2=>"The username you entered does not exist. Please confirm and try again.");

            $responseJSON = json_encode($theResponse);

            // throw new PDOException('The username you entered does not exist. Please confirm and try again.');
            throw new PDOException($responseJSON);
            */
        } else {

            // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app failed login')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array("response" => "error", "code" => 3, "data" => 'The password you have entered is incorrect. Please check the spelling and / or capitalization.'));
            /*
            $theResponse->data = 'The password you have entered is incorrect. Please check the spelling and / or capitalization.';
            $theResponse->status = array(3=>"The password you have entered is incorrect. Please check the spelling and / or capitalization.");

            $responseJSON = json_encode($theResponse);
            // throw new PDOException('The username or password you have entered is incorrect.');
            throw new PDOException($responseJSON);
            */
        }
    }
  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/registrationStatus/:phone', function ($phoneNumber){
  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing
  // Get the registration status
  $app = \Slim\Slim::getInstance();

  try
  {
    //first check if this number is in use in active use ie taken
    $db0 = getLoginDB();

    $checkOne = $db0->query("SELECT (CASE
                                    		WHEN EXISTS (SELECT * FROM (
                                                      SELECT telephone AS phone FROM registration_codes WHERE telephone = '$phoneNumber'
                                                      UNION ALL
                                                      SELECT username AS phone FROM parents WHERE username = '$phoneNumber'
                                                      )a
                                                      LIMIT 1) THEN 'in-use'
                                    		ELSE 'continue'
                                    	END) AS check_one");
    $lineInUse = $checkOne->fetch(PDO::FETCH_OBJ);
    $lineInUse = $lineInUse->check_one;
    $db0 = null;
    if($lineInUse === "continue"){

        $db = pg_connect("host=localhost port=5432 dbname=eduweb_mis user=postgres password=pg_edu@8947");

        // Query 1 - Get all databases
        $fetchDbs = pg_query($db,"SELECT subdomain FROM clients WHERE active IS TRUE");

        $dbArray = array(); // we'll put our db's here
        $statusResults = array(); // this will contain boolean results from all databases
        $registrationData = new stdClass();

        while ($dbResults = pg_fetch_assoc($fetchDbs))
        {
            $dbCreate = 'eduweb_' . $dbResults['subdomain']; // full name of the db's
            array_push($dbArray,$dbCreate); // push into dbArray the value of dbCreate
        }
        // now $dbArray has all databases we need to look up data (phone numbers)
        $firstTimeFound = TRUE;
        foreach ($dbArray as $key => $value) {
          // db connect for each school

          	$dbhost="localhost";
          	$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5434");
          	$dbuser = "postgres";
          	$dbpass = "pg_edu@8947";
          	$dbname = $value;
          	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
          	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          $schoolDb = $dbConnection; // the db connect
          $executeOnSchoolDb = $schoolDb->query("SELECT (CASE
                                                            WHEN EXISTS (SELECT telephone FROM app.guardians WHERE telephone = '$phoneNumber' AND active IS TRUE) THEN 'Proceed Registration'
                                                            ELSE 'Stop'
                                                          END) AS registration_status;");
          $regStatus = $executeOnSchoolDb->fetch(PDO::FETCH_OBJ);
          $regStatus = $regStatus->registration_status;

          if($regStatus === 'Proceed Registration'){
            array_push($statusResults,"TRUE");
            // var_dump($dbArray[$key]); // use this to check which school(s) the record appears
            if($firstTimeFound === TRUE){
                if($value === $dbArray[$key]){
                  $dataFrmSchoolDb = $schoolDb->query("SELECT guardian_id, first_name, middle_name, last_name, email, username, id_number, array_to_string(array_agg(student_id),',') AS student_ids, subdomain
                                                      FROM (
                                                      	SELECT guardian_id, first_name, middle_name, last_name, email, telephone AS username, id_number, student_id,
      								                        (SELECT CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'subdomain') THEN (SELECT value FROM app.settings WHERE name = 'subdomain') ELSE 'dev' END) AS subdomain
                                                      	FROM app.guardians
                                                      	INNER JOIN app.student_guardians USING (guardian_id)
                                                      	WHERE telephone = '$phoneNumber' AND guardians.active IS TRUE
                                                      )one
                                                      GROUP BY guardian_id, first_name, middle_name, last_name, email, username, id_number, subdomain;");
                  $parentData = $dataFrmSchoolDb->fetch(PDO::FETCH_OBJ);

                  // var_dump($parentData);
                  $registrationData->guardian_id = $parentData->guardian_id;
                  $registrationData->first_name = $parentData->first_name;
                  $registrationData->middle_name = $parentData->middle_name;
                  $registrationData->last_name = $parentData->last_name;
                  $registrationData->email = $parentData->email;
                  $registrationData->username = $parentData->username;
                  $registrationData->id_number = $parentData->id_number;
                  $registrationData->student_ids = $parentData->student_ids;
                  $registrationData->subdomain = $parentData->subdomain;
                }
                $firstTimeFound = FALSE;
            }else{
              // we return an error for users in multiple schools
              $app->response()->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              echo  json_encode(array('response' => 'Error', 'message' => "There seems to be an issue either with this phone number or your details. Please consult with your school.", "status" => "Cannot proceed with registration." ));
            }
          }else{ array_push($statusResults,"FALSE"); }
        }

        // we now search through our array of status results for a boolean true
        $valueToSearch = "TRUE"; // this will mean the phone number exists in one of the db's
        if (($i = array_search($valueToSearch, $statusResults)) !== FALSE)
        {
            // loop will terminate

            // this will generate our unique code
            function getRandomString($length = 5) {
                // $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $characters = '0123456789';
                $string = '';

                for ($i = 0; $i < $length; $i++) {
                    $string .= $characters[mt_rand(0, strlen($characters) - 1)];
                }

                return $string;
            }
            $uniqueCode = getRandomString();
            $insertRegCode = pg_query($db,"INSERT INTO registration_codes(telephone, code, guardian_id, first_name, middle_name, last_name, email, id_number, username, student_ids, subdomain)
                                            VALUES ('$phoneNumber',
                                                    '$uniqueCode',
                                                    $registrationData->guardian_id,
                                                    '$registrationData->first_name',
                                                    '$registrationData->middle_name',
                                                    '$registrationData->last_name',
                                                    '$registrationData->email',
                                                    '$registrationData->id_number',
                                                    '$registrationData->username',
                                                    '$registrationData->student_ids',
                                                    '$registrationData->subdomain');");

            // first we need to change the phone format to +[code]phone
            $firstChar = substr($phoneNumber, 0, 1);
            if($firstChar === "0"){$phoneNumber = preg_replace('/^0/', '', $phoneNumber);}
            // we now create & send the actual sms
            $registrationMessageObj = new stdClass();
            $registrationMessageObj->message_recipients = Array();
            $registrationMessageObj->message_by = "Eduweb Mobile App Registration";
            $registrationMessageObj->message_date = date('Y-m-d H:i:s');
            $registrationMessageObj->message_text = "Hello $registrationData->first_name, Your code for the Eduweb Mobile App registration is $uniqueCode ";
            $registrationMessageObj->subscriber_name = "kingsinternational";// $school;


            $msgRecipientsObj = new stdClass();
            $msgRecipientsObj->recipient_name = "$registrationData->first_name $registrationData->last_name";
            $msgRecipientsObj->phone_number = "+254" . $phoneNumber;
            array_push($registrationMessageObj->message_recipients, clone $msgRecipientsObj);

            // send the message
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://sms_api.eduweb.co.ke/api/sendBulkSms"); // the endpoint url
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8')); // the content type headers
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE); // the request type
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registrationMessageObj)); // the data to post
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // this works like jquery ajax (asychronous)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // disable SSL certificate checks and it's complications

            $resp = curl_exec($ch);

            if($resp === false)
            {
              $app->response()->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found but there seems to be a slight problem sending the SMS. Please try again.", "status" => "SMS not sent", "error" => curl_error($ch) ));
            }
            else
            {
              $app->response()->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found. A confirmation SMS will be sent to you for validation to allow you to proceed to register your password.", "status" => "SMS sent successfully" ));
            }

            curl_close($ch);

            // $app->response()->setStatus(200);
            // $app->response()->headers->set('Content-Type', 'application/json');
            // echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found. A confirmation SMS will be sent to you for validation to allow you to proceed to register your password." ));

        }else{
          $app->response()->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo  json_encode(array('response' => 'error', 'message' => "The submitted phone number has not been found in our system. Please use a phone number that you use with the school.", "status" => "Phone number not found." ));
        }
    }else{
      $app->response()->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'message' => "The submitted phone number is already in use, either by you or another user.", "status" => "Phone number already in use" ));
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/checkRegCode/:phone/:code', function ($phone,$code) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
      $db = getLoginDB();

      $checkCode = $db->query("SELECT (CASE
                                          WHEN EXISTS (SELECT telephone,code FROM registration_codes WHERE telephone = '$phone' AND code='$code') THEN 'proceed'
                                          ELSE 'stop'
                                        END) AS status");
      $userStatus = $checkCode->fetch(PDO::FETCH_OBJ);
      $userStatus = $userStatus->status;

        if($userStatus === "proceed") {
            $updateToValidated = $db->query("UPDATE registration_codes SET status= TRUE
                                            WHERE telephone='$phone' AND code='$code';");
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'validated' => true, 'data' => 'Your phone number has been validated. Proceed to set up your password.', 'status' => 'Validation passed.' ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'error', 'validated' => false, 'data' => 'Either your phone number could not be found or the entered code is wrong, hence not validated yet.', 'status' => 'Validation failed.' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/registerPwd', function () use($app) {
  // update users device id
  $allPostVars = $app->request->post();
  $pwd = $allPostVars['pwd'];
  $phone = $allPostVars['phone'];

  try
  {
    $db = getLoginDB();
    $sth = $db->query("SELECT * FROM registration_codes WHERE telephone = '$phone';");
    $results = $sth->fetch(PDO::FETCH_OBJ);
    $validationStatus = $results->status;
    if($validationStatus === true){

      $sth2 = $db->prepare("INSERT INTO parents(first_name, middle_name, last_name, email, id_number, username, password, active)
                            VALUES ('$results->first_name','$results->middle_name','$results->last_name','$results->email','$results->id_number','$results->username','$pwd',true) returning parent_id;");
      $sth2->execute( array() );
      $thisParentsId = $sth2->fetch(PDO::FETCH_OBJ);
      $thisParentsId = $thisParentsId->parent_id;

      // insert parent student relation into db
      $studentsArr = explode (",", $results->student_ids);
      foreach ($studentsArr as &$studentId) {
            $sth22 = $db->prepare("INSERT INTO parent_students(parent_id, guardian_id, student_id, subdomain, dbusername, dbpassword)
                                    VALUES ($thisParentsId,
                                            $results->guardian_id,
                                            $studentId,
                                            '$results->subdomain',
                                            'postgres',
                                            'pg_edu@8947');");
            $sth22->execute( array() );
      }

      $sth3 = $db->prepare("DELETE FROM registration_codes WHERE telephone = '$phone'");
      $sth3->execute( array() );

      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success", "message" => "You have been successfully registered. You can now log in."));
      $db = null;
    }else{
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "error", "message" => "Your phone number has not been validated yet. Please validate the code sent to you first by entering it in the mobile app."));
      $db = null;
    }

    $db = null;

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app user registration')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

    // $app->response->setStatus(200);
    // $app->response()->headers->set('Content-Type', 'application/json');
    // echo json_encode(array("response" => "success", "code" => 1));
    // $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->put('/updatePassword', function () use($app) {
  // update password
  $allPostVars = json_decode($app->request()->getBody(),true);
  $userId = $allPostVars['parent_id'];
  $oldPwd = $allPostVars['user_pwd'];
  $newPwd = $allPostVars['new_password'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();

    $sth1 = $db->prepare("SELECT * FROM parents WHERE parent_id = :userId and password = :oldPwd");
    $sth1->execute( array(':userId' => $userId, ':oldPwd' => $oldPwd) );
    $result = $sth1->fetch(PDO::FETCH_OBJ);
    if( $result )
    {
        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app updated login')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

      $sth2 = $db->prepare("UPDATE parents SET password = :newPwd WHERE parent_id = :userId");
      $sth2->execute( array(':userId' => $userId, ':newPwd' => $newPwd) );
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success", "code" => 1));
    }
    else
    {

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app failed login update')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "error", "data" => "Current password is incorrect." ));
    }


    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/forgotPassword/:phone', function ($phoneNumber){
  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing
  // Get the registration status

  $file = "wordlist.txt";
  $twoRndWords = Array();
  // we need to merge two words eg "dog-town" so we create a loop of only 2
  for ($x = 0; $x <= 1; $x++) {
    // Convert the text fle into array and get text of each line in each array index
    $file_arr = file($file);
    // Total number of lines in file
    $num_lines = count($file_arr);
    // Getting the last array index number
    $last_arr_index = $num_lines - 1;
    // Random index number
    $rand_index = rand(0, $last_arr_index);
    // random text from a line. The line will be a random number within the indexes of the array
    $rand_text = $file_arr[$rand_index];
    array_push($twoRndWords,$rand_text);
  }
  $temporaryPwd = join("-",$twoRndWords);
  echo $temporaryPwd;

  $app = \Slim\Slim::getInstance();

  try
  {
    //first check if this number is in use in active use ie taken
    $db0 = getLoginDB();

    $checkOne = $db0->query("SELECT (CASE
                                    		WHEN EXISTS (SELECT * FROM (
                                                      SELECT username AS phone FROM parents WHERE username = '$phoneNumber'
                                                      )a
                                                      LIMIT 1) THEN 'found'
                                    		ELSE 'not-found'
                                    	END) AS check_one, (SELECT parent_id FROM parents WHERE username = '$phoneNumber') AS parent_id");
    $lineCheck = $checkOne->fetch(PDO::FETCH_OBJ);
    $phoneCheck = $lineCheck->check_one;
    $parentId = $lineCheck->parent_id;
    $db0 = null;
    if($phoneCheck === "found"){
        $sth2 = $db->prepare("INSERT INTO forgot_password(usr_name, temp_pwd, parent_id)
                              VALUES ('$results->first_name','$results->middle_name','$results->last_name','$results->email','$results->id_number','$results->username','$pwd',true) returning parent_id;");
        $sth2->execute( array() );

        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'message' => "Phone number has been found. A temporary password will be sent to your phone numer.", "status" => "Record found, sms sent." ));
    }else{
      $app->response()->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'message' => "The submitted details were not found in our records.", "status" => "Phone number not found, no sms will be sent." ));
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->post('/updateDeviceUserId', function () use($app) {
  // update users device id
  $allPostVars = $app->request->post();
  $parentId = $allPostVars['parent_id'];
  $deviceUserId = $allPostVars['device_user_id'];

  try
  {
    $db = getLoginDB();

    $sth = $db->prepare("UPDATE parents SET device_user_id = :deviceUserId WHERE parent_id = :parentId");
    $sth->execute( array(':parentId' => $parentId, ':deviceUserId' => $deviceUserId) );

    $db = null;

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app device user id updated')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getParentStudents/:parent_id', function ($parentId){
  // get parents students and news
  $app = \Slim\Slim::getInstance();
  try
  {
    $db = getLoginDB();

    // get the parents' students and add to result
    $sth1 = $db->prepare("SELECT student_id, guardian_id AS school_guardian_id, subdomain, dbusername, dbpassword FROM parent_students WHERE parent_id = :parentId ORDER BY subdomain");
    $sth1->execute(array(':parentId' => $parentId));
    $students = $sth1->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','parent-app getting parents children and associated data')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

    $studentDetails = Array();
    $curSubDomain = '';
    $studentsBySchool = Array();
    foreach( $students as $student )
    {
      // get individual student details
      // only get new db connection if different subdomain
      if( $curSubDomain != $student->subdomain )
      {
        if( $db !== null ) $db = null;
        $db = setDBConnection($student->subdomain);
      }
      $sth3 = $db->prepare("SELECT student_id, first_name, middle_name, last_name, student_image, admission_number,
                     first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                     students.active, class_name, class_id, class_cat_id, report_card_type, admission_date, student_category, gender, dob, payment_method, payment_plan_name,
                     (SELECT value FROM app.settings WHERE name = 'School Name') as school_name,
                     (SELECT value FROM app.settings WHERE name = 'Use Feedback') as use_feedback
                  FROM app.students
                  INNER JOIN app.classes ON students.current_class = classes.class_id
                  LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
                  WHERE student_id = :studentId AND students.active IS TRUE");
      $sth3->execute(array(':studentId' => $student->student_id));
      $details = $sth3->fetch(PDO::FETCH_OBJ);
      if($details){
        $details->school = $student->subdomain;

        if( $details->student_id !== null )
        {
          $studentDetails[] = $details;
          $curSubDomain = $student->subdomain;

          /* build an array for grabbing news */
          $studentsBySchool[$curSubDomain][] = $student->student_id;
        }
      }
    }

    /* get news for students, only want new once per school, and student specific news */
    $news = Array();
    foreach( $studentsBySchool as $school => $students )
    {
      if( $db !== null ) $db = null;
      $db = setDBConnection($school);

      $sth5 = $db->prepare("SELECT
                    com_id, com_date, communications.creation_date, com_type, subject, message, send_as_email, send_as_sms,
                    employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                    audience, attachment, reply_to,
                    students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
                    guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
                    communications.guardian_id, communications.student_id, classes.class_name, post_status,
                    sent, sent_date, message_from,
                    case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method
                  FROM app.communications
                  LEFT JOIN app.students ON communications.student_id = students.student_id
                  LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
                  LEFT JOIN app.classes ON communications.class_id = classes.class_id
                  INNER JOIN app.employees ON communications.message_from = employees.emp_id
                  INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
                  INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
                  INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
                  WHERE communications.student_id = any(:studentIds) OR communications.student_id is null
                  AND communications.sent IS TRUE
                  AND communications.post_status_id = 1
                  AND (communications.class_id = any(select current_class from app.students where student_id = any(:studentIds))
                  OR communications.class_id is null)
                  AND communications.audience_id NOT IN (3,4,7,9)
                  --AND students.active IS TRUE
                  AND communications.com_type_id NOT IN (6)
                  AND date_trunc('year', communications.creation_date) =  date_trunc('year', now())
                  ORDER BY creation_date desc");

      $studentsArray = "{" . implode(',',$students) . "}";
      $sth5->execute(array(':studentIds' => $studentsArray));
      $news[$school] = $sth5->fetchAll(PDO::FETCH_OBJ);

    }

    /* get sent messages by student's parent */
      $feedback = Array();
      foreach( $studentsBySchool as $school => $students )
      {
        if( $db !== null ) $db = null;
        $db = setDBConnection($school);

        $sth6 = $db->prepare("SELECT cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                cf.message_from as posted_by,
                s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                c.class_name
            FROM app.communication_feedback cf
            LEFT JOIN app.students s USING (student_id)
            LEFT JOIN app.guardians g USING (guardian_id)
            LEFT JOIN app.classes c USING (class_id)
            WHERE cf.student_id = any(:studentIds)
            AND s.active IS TRUE
            GROUP BY cf.com_feedback_id, subject, message, student_name, parent_full_name, class_name, opened
            ORDER BY post_id DESC");

        $studentsArray = "{" . implode(',',$students) . "}";
        $sth6->execute(array(':studentIds' => $studentsArray));
        $feedback[$school] = $sth6->fetchAll(PDO::FETCH_OBJ);

      }

    $result = new stdClass();
    $result->students = $studentDetails;
    $result->news = $news;
    $result->feedback = $feedback;
    $result->notices = Array();

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    $db = null;

    echo json_encode(array('response' => 'success', 'data' => $result ));



  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getSchoolCurrency/:school', function ($school) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT value FROM app.settings WHERE name = 'Currency'");
    $sth->execute();
    $settings = $sth->fetch(PDO::FETCH_OBJ);

        if($settings) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $settings ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getGrading2/:school', function ($school) {
    //Show lower school grading

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT *
            FROM app.grading2
			ORDER BY max_mark desc");
        $sth->execute();

        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
         $app->response()->setStatus(200);
		 $app->response()->headers->set('Content-Type', 'application/json');
         echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getSchoolContactInfo/:school', function ($school) {
    //Show contact info

  $app = \Slim\Slim::getInstance();

    try
    {
      $db = setDBConnection($school);
      $sth = $db->prepare("SELECT name, value FROM app.settings");
      $sth->execute();
      $settings = $sth->fetchAll(PDO::FETCH_OBJ);

        if($settings) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $settings ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getBlog/:school/:student_id(/:pageNumber)', function ($school, $studentId, $pageNumber=null) {
    // Get published blog posts associated with student for this current school year

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    if( $pageNumber === null )
    {
      $sth = $db->prepare("SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
                  employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                  feature_image, blog_posts.modified_date,
                  class_name, blogs.class_id, blogs.blog_name
               FROM app.blogs
                INNER JOIN app.blog_posts
                  INNER JOIN app.employees
                  ON blog_posts.created_by = employees.emp_id
                  LEFT JOIN app.blog_post_types
                  ON blog_posts.post_type_id = blog_post_types.post_type_id
                  INNER JOIN app.blog_post_statuses
                  ON blog_posts.post_status_id = blog_post_statuses.post_status_id
                ON blogs.blog_id = blog_posts.blog_id
                INNER JOIN app.classes
                ON blogs.class_id = classes.class_id
                INNER JOIN app.students ON blogs.class_id = students.current_class
                WHERE student_id = :studentId
                AND blog_posts.post_status_id = 1
               -- AND blog_posts.post_type_id = 1
                AND date_trunc('year', blog_posts.creation_date) =  date_trunc('year', now())
                AND students.active IS TRUE
                ORDER BY blog_posts.creation_date desc
                ");
      $sth->execute( array(':studentId' => $studentId) );
      $results = $sth->fetchAll(PDO::FETCH_OBJ);
    }
    else
    {
      // need pagination
      $limit = 3;
      $offset = ($pageNumber * $limit) - $limit;
      $sth1 = $db->prepare("SELECT count(post_id) as num_posts
               FROM app.blogs
               INNER JOIN app.blog_posts ON blogs.blog_id = blog_posts.blog_id
               INNER JOIN app.classes ON blogs.class_id = classes.class_id
               INNER JOIN app.students ON blogs.class_id = students.current_class
               WHERE student_id = :studentId
               AND blog_posts.post_status_id = 1
              -- AND blog_posts.post_type_id = 1
               AND date_trunc('year', blog_posts.creation_date) =  date_trunc('year', now())
               AND students.active IS TRUE
                ");

      $sth2 = $db->prepare("SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
                  employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                  feature_image, blog_posts.modified_date,
                  class_name, blogs.class_id, blogs.blog_name
               FROM app.blogs
                INNER JOIN app.blog_posts
                  INNER JOIN app.employees
                  ON blog_posts.created_by = employees.emp_id
                  LEFT JOIN app.blog_post_types
                  ON blog_posts.post_type_id = blog_post_types.post_type_id
                  INNER JOIN app.blog_post_statuses
                  ON blog_posts.post_status_id = blog_post_statuses.post_status_id
                ON blogs.blog_id = blog_posts.blog_id
                INNER JOIN app.classes
                ON blogs.class_id = classes.class_id
                INNER JOIN app.students ON blogs.class_id = students.current_class
                WHERE student_id = :studentId
                AND blog_posts.post_status_id = 1
                --AND blog_posts.post_type_id = 1
                AND date_trunc('year', blog_posts.creation_date) =  date_trunc('year', now())
                ORDER BY blog_posts.creation_date desc
                OFFSET :offset LIMIT :limit
                ");

      $db->beginTransaction();
      $sth1->execute( array(':studentId' => $studentId) );
      $sth2->execute( array(':studentId' => $studentId, ':offset' => $offset, ':limit' => $limit) );
      $db->commit();

      $count = $sth1->fetch(PDO::FETCH_OBJ);
      $posts = $sth2->fetchAll(PDO::FETCH_OBJ);

      $pagination = new stdClass();
      $pagination->page = $pageNumber;
      $pagination->perPage = $limit;
      $pagination->pageCount = floor($count->num_posts / $limit) + 1;
      $pagination->totalCount = (int) $count->num_posts;

      $results = new stdClass();
      $results->count = $count->num_posts;
      $results->pagination = $pagination;
      $results->posts = $posts;
    }

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting blog posts')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end


        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getGalleryCommunications/:school/:student_id', function ($school, $studentId) {
    // Get homework associated with student for current week

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    $sth = $db->prepare("SELECT
                com_id, com_date, communications.creation_date, com_type, subject, message, send_as_email, send_as_sms,
                employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                audience, attachment, reply_to,
                students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
                guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
                communications.guardian_id, communications.student_id, classes.class_name, post_status,
                sent, sent_date, message_from,
                case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method
              FROM app.communications
              LEFT JOIN app.students ON communications.student_id = students.student_id
              LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
              LEFT JOIN app.classes ON communications.class_id = classes.class_id
              INNER JOIN app.employees ON communications.message_from = employees.emp_id
              INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
              INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
              INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
              WHERE communications.com_type_id = 6
              AND communications.student_id IN (:studentId) OR communications.student_id is null
              AND communications.sent IS TRUE
              AND communications.post_status_id = 1
              AND (communications.class_id = any(select current_class from app.students where student_id IN (:studentId))
              OR communications.class_id is null)
              AND communications.audience_id NOT IN (3,4,7,9)
              AND communications.send_as_email IS TRUE
              --AND students.active IS TRUE
              AND date_trunc('year', communications.creation_date) =  date_trunc('year', now())
              ORDER BY creation_date desc");

    $sth->execute( array(':studentId' => $studentId) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app viewing gallery')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getHomework/:school/:student_id', function ($school, $studentId) {
    // Get homework associated with student for current week

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    $sth = $db->prepare("SELECT homework_id, assigned_date,
                        	employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                        	title, body, homework.post_status_id, post_status, class_name, class_subjects.class_id, due_date, subject_name,
                        	attachment, homework.modified_date
                        FROM app.homework
                        INNER JOIN app.blog_post_statuses USING (post_status_id)
                        INNER JOIN app.class_subjects USING (class_subject_id)
                        INNER JOIN app.classes USING (class_id)
                        INNER JOIN app.students ON classes.class_id = students.current_class
                        			AND class_subjects.class_id = classes.class_id
                        INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
                        			AND homework.class_subject_id = class_subjects.class_subject_id
                        LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
                        WHERE student_id = :studentId
                        AND homework.post_status_id = 1
                        --AND date_trunc('year', homework.creation_date) =  date_trunc('year', now())
                        AND homework.creation_date >  CURRENT_DATE - INTERVAL '3 months'
                        --AND (assigned_date between date_trunc('week', now())::date and (date_trunc('week', now())+ '6 days'::interval)::date OR due_date > now() )
                        AND students.active IS TRUE
                        ORDER BY homework.assigned_date DESC, subjects.sort_order
                ");
    $sth->execute( array(':studentId' => $studentId) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);
    /*
        $sth = $db->prepare("SELECT homework_date, description
                FROM app.homework
                INNER JOIN app.students ON homework.class_id = students.current_class
                WHERE student_id = :studentId
                AND homework_date between date_trunc('week', now())::date and (date_trunc('week', now())+ '6 days'::interval)::date
                ORDER BY homework_date asc
                ");
    $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
    */

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting homework')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudent/:school/:studentId', function ($school, $studentId) {
    //Show all students

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT students.student_id, first_name, middle_name, last_name, admission_number, admission_date,
                  student_category, gender, dob, student_image, classes.class_name,
                payment_method, payment_plan_name,
                emergency_name, emergency_relationship, emergency_telephone, pick_up_drop_off_individual,
                other_medical_conditions, other_medical_conditions_description,
                medical_conditions, hospitalized, current_medical_treatment, hospitalized_description,
                current_medical_treatment_description, comments
               FROM app.students
               INNER JOIN app.classes ON students.current_class = classes.class_id
               LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
               WHERE student_id = :studentID AND students.active IS TRUE
               ORDER BY first_name, middle_name, last_name");
        $sth->execute( array(':studentID' => $studentId));
        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {

      // get parents
      $sth2 = $db->prepare("SELECT *
               FROM app.student_guardians
               INNER JOIN app.guardians ON student_guardians.guardian_id = guardians.guardian_id
               WHERE student_guardians.student_id = :studentID
               AND student_guardians.active = true
               ORDER BY relationship, last_name, first_name, middle_name");
      $sth2->execute( array(':studentID' => $studentId));
      $results2 = $sth2->fetchAll(PDO::FETCH_OBJ);

      $results->guardians = $results2;

      // get medical history
      $sth3 = $db->prepare("SELECT medical_id, illness_condition, age, comments, creation_date as date_medical_added
               FROM app.student_medical_history
               WHERE student_id = :studentID
               ORDER BY creation_date");
      $sth3->execute( array(':studentID' => $studentId));
      $results3 = $sth3->fetchAll(PDO::FETCH_OBJ);

      $results->medical_history = $results3;

      // get fee items
      $sth4 = $db->prepare("SELECT
                  fee_item
                FROM app.student_fee_items
                INNER JOIN app.fee_items on student_fee_items.fee_item_id = fee_items.fee_item_id
                WHERE student_id = :studentID
                AND optional is true
                ORDER BY student_fee_items.creation_date");
      $sth4->execute( array(':studentID' => $studentId));
      $results4 = $sth4->fetchAll(PDO::FETCH_OBJ);

      $results->fee_items = $results4;

      // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student information')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentBalancePortal/:school/:studentId', function ($school, $studentId) {
  // Return students fee summary

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    // get total amount of student fee items
    // calculate the amount due and due date
    // calculate the balance owing
    $sth = $db->prepare("SELECT fee_item, q.payment_method,
                          sum(invoice_total) AS total_due,
                          sum(total_paid) AS total_paid,
                          sum(total_paid) - sum(invoice_total) AS balance,
                          (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                        FROM
                          ( SELECT invoice_line_items.amount as invoice_total,
                             fee_item,
                             student_fee_items.payment_method,
                             inv_item_id,
                             (SELECT COALESCE(sum(payment_inv_items.amount), 0)
                              FROM app.payment_inv_items
                              INNER JOIN app.payments
                              ON payment_inv_items.payment_id = payments.payment_id AND reversed is false
                              WHERE inv_item_id = invoice_line_items.inv_item_id) as total_paid
                            FROM app.invoices
                            INNER JOIN app.invoice_line_items
                              INNER JOIN app.student_fee_items
                                INNER JOIN app.fee_items
                                ON student_fee_items.fee_item_id = fee_items.fee_item_id
                              ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id AND student_fee_items.active = true
                            ON invoices.inv_id = invoice_line_items.inv_id
                            WHERE invoices.student_id = :studentID
                            AND invoices.canceled = false
                          ) q
                        GROUP BY fee_item, q.payment_method
              ");
        $sth->execute( array(':studentID' => $studentId));
        $fees = $sth->fetchAll(PDO::FETCH_OBJ);


    if( $fees )
    {
      $sth2 = $db->prepare("SELECT
                  (SELECT due_date FROM app.invoice_balances2 WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_due_date,
                  (SELECT balance from app.invoice_balances2 WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_amount,
                  COALESCE((SELECT sum(amount) from app.credits WHERE student_id = :studentID ),0) AS total_credit,
                  (SELECT sum(balance) from app.invoice_balances2 WHERE student_id = :studentID AND due_date <= now()::date AND canceled = false) AS arrears,
                  (SELECT value FROM app.settings WHERE name = 'Currency') as currency");
      $sth2->execute( array(':studentID' => $studentId));
      $details = $sth2->fetch(PDO::FETCH_OBJ);


      if( $details )
      {
        //  set the next due summary
        $feeSummary = new Stdclass();
        $feeSummary->next_due_date = $details->next_due_date;
        $feeSummary->next_amount = $details->next_amount;
        //$feeSummary->unapplied_payments = $details->unapplied_payments;
        $feeSummary->total_credit = $details->total_credit;
        $feeSummary->arrears = $details->arrears;

        // is the next due date within 30 days?
        $diff = dateDiff("now", $details->next_due_date);
        $feeSummary->within30days = ( $diff < 30 ? true : false );
      }

      $balanceQry = $db->prepare("SELECT total_due, total_paid, total_paid - total_due as balance,
                                  case when (select count(*) from app.invoice_balances2 where student_id = :studentID and past_due is true) > 0 then true else false end as past_due
                                FROM (
                                  SELECT
                                    coalesce(sum(total_amount),0) as total_due,
                                    coalesce((select sum(payment_inv_items.amount) from app.payments inner join app.payment_inv_items on payments.payment_id = payment_inv_items.payment_id where student_id = :studentID),0) as total_paid
                                  FROM app.invoices
                                  WHERE student_id = :studentID
                                  --AND date_part('year', due_date) = date_part('year',now())
                                  AND canceled = false
                                )q");
      $balanceQry->execute( array(':studentID' => $studentId));
      $balance = $balanceQry->fetch(PDO::FETCH_OBJ);
      //var_dump($balance);

      $feeSummary->total_due = ($balance ? $balance->total_due : 0);
      $feeSummary->total_paid = ($balance ? $balance->total_paid : 0);
      $feeSummary->balance = ($balance ? $balance->balance : 0);
      $feeSummary->past_due = ($balance ? $balance->past_due : false);

      $results = new stdClass();
      $results->fee_summary = $feeSummary;
      $results->fees = $fees;

    }

        if($fees) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student fee balance')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentInvoicesPortal/:school/:studentId', function ($school, $studentId) {
  // Return students invoices

  $app = \Slim\Slim::getInstance();

  try
  {
     $db = setDBConnection($school);

    // get invoices
    // TO DO: I only want invoices for this school year?
    $sth = $db->prepare("SELECT invoice_balances2.*, ARRAY(select fee_item || ' (' || invoice_line_items.amount || ')'
                                    from app.invoice_line_items
                                    inner join app.student_fee_items
                                    inner join app.fee_items
                                    on student_fee_items.fee_item_id = fee_items.fee_item_id
                                    on invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                                    where inv_id = invoice_balances2.inv_id) as invoice_items,
                                    term_name,
                                    date_part('year', terms.start_date) as year,
                                    (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                          FROM app.invoice_balances2
                          INNER JOIN app.terms
                          ON invoice_balances2.term_id = terms.term_id
                          WHERE student_id = :studentId
                          ORDER BY inv_date");
    $sth->execute( array(':studentId' => $studentId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);


  if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
  } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
  }

  // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student invoices')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentPaymentsPortal/:school/:studentId', function ($school, $studentId) {
  // Return students payments

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    // get payments
    // TO DO: I only want payments for this school year?
    $sth = $db->prepare("SELECT payments.payment_id,
                payment_date,
                payment_method,
                amount,
                reversed,
                reversed_date,
                replacement_payment,
                slip_cheque_no,
                COALESCE(
                CASE WHEN replacement_payment = true THEN
                 (SELECT string_agg(fee_item || ' Replacement', ',')
                     FROM app.payment_replacement_items
                     INNER JOIN app.student_fee_items using (student_fee_item_id)
                     INNER JOIN app.fee_items using (fee_item_id)
                     WHERE payment_id = payments.payment_id
                     )
                 ELSE
                  (SELECT
                    string_agg(item, '<br>')
                  FROM (
                    select 'Inv #' || payment_inv_items.inv_id || ' (' || string_agg(fee_item, ', ' order by fee_item) || ')' as item
                     FROM app.payment_inv_items
                     INNER JOIN app.invoices on payment_inv_items.inv_id  = invoices.inv_id and canceled = false
                     INNER JOIN app.invoice_line_items using (inv_item_id)
                     INNER JOIN app.student_fee_items using (student_fee_item_id)
                     INNER JOIN app.fee_items using (fee_item_id)
                     WHERE payment_id = payments.payment_id
                     group by payment_inv_items.inv_id
                  ) q
                     )
                END, 'Credit') as applied_to,
               COALESCE((
                  amount - coalesce((select coalesce(sum(amount),0)  as sum
                            from app.payment_inv_items
                            inner join app.invoices using (inv_id)
                            where payment_id = payments.payment_id
                            and canceled = false ),0)
                ),0) AS unapplied_amount,
                (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                FROM app.payments
                WHERE student_id = :studentID
                GROUP BY payments.payment_id");
    $sth->execute( array(':studentID' => $studentId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);


    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student fee payments')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentCreditsPortal/:school/:studentId', function ($school, $studentId) {
  // Return students credits

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    // get credits
    $sth = $db->prepare("SELECT credit_id, credits.amount, payment_date, credits.payment_id, payment_method, slip_cheque_no,
                        (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                FROM app.credits
                INNER JOIN app.payments ON credits.payment_id = payments.payment_id
                WHERE credits.student_id = :studentID
                AND reversed is false
                ORDER BY payment_date");
    $sth->execute( array(':studentID' => $studentId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student fee credits')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentArrearsPortal/:school/:studentId/:date', function ($school, $studentId, $date) {
  // Return students arrears

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    // get credits
    $sth = $db->prepare("select sum(total_paid - total_amount) as balance, (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                          from (
                            select invoices.inv_id, invoices.total_amount, coalesce(sum(amount),0) as total_paid
                            from app.invoices
                            left join app.payment_inv_items
                            on invoices.inv_id = payment_inv_items.inv_id
                            WHERE student_id = :studentID
                            AND canceled = false
                            AND due_date <= :date
                            group by invoices.inv_id
                          ) q");
    $sth->execute( array(':studentID' => $studentId, ':date' => $date));
    $results = $sth->fetch(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student fee arrears')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentFeeItemsPortal/:school/:studentId', function ($school, $studentId) {
    // Get all students replaceable fee items

  $app = \Slim\Slim::getInstance();

    try
    {
       $db = setDBConnection($school);
    // TO DO: I only want fee items for this school year?
       $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount, frequency,
                            (SELECT value FROM app.settings WHERE name = 'Currency') as currency
              FROM app.student_fee_items
              INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true
              WHERE student_id = :studentId
              AND student_fee_items.active = true
              ORDER BY fee_item");
    $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student fee items')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentExamMarksPortal/:school/:student_id/:class/:term', function ($school,$studentId,$classId,$termId) {
    //Get student exam marks

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);

    // get exam marks by exam type
    $sth = $db->prepare("SELECT subject_name, (select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name
                ,exam_type
                ,mark
                ,grade_weight
                ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade
            FROM app.exam_marks
            INNER JOIN app.class_subject_exams
            INNER JOIN app.exam_types
            ON class_subject_exams.exam_type_id = exam_types.exam_type_id
            INNER JOIN app.class_subjects
              INNER JOIN app.subjects
              ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
            ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
            ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
            WHERE class_subjects.class_id = :classId
            AND term_id = :termId
            AND student_id = :studentId
            ORDER BY subjects.sort_order, exam_types.exam_type_id
            ");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );

        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student exam marks')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentClasses/:school/:studentId', function ($school, $studentId) {
    // Get all students classes, present and past

  $app = \Slim\Slim::getInstance();

    try
    {
       $db = setDBConnection($school);
       $sth = $db->prepare("SELECT 1 as ord, student_id, class_id, class_name,
                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 1') then true else false end as term_1,
                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 2') then true else false end as term_2,
                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 3') then true else false end as term_3
              FROM app.students
              INNER JOIN app.classes ON students.current_class = classes.class_id
              WHERE student_id = :studentId AND students.active IS TRUE
              UNION
              SELECT class_history_id as ord, student_id, student_class_history.class_id, class_name, true, true, true
              FROM app.student_class_history
              INNER JOIN app.classes ON student_class_history.class_id = classes.class_id
              WHERE student_id = :studentId
              AND student_class_history.class_id != (select current_class from app.students where student_id = :studentId AND students.active IS TRUE)
              ORDER BY ord");
    $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getCurrentTerm/:school', function ($school) {
    //Get current term

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    $query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.current_term");
    $query->execute();
        $results = $query->fetch(PDO::FETCH_ASSOC);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getTerms/:school(/:year)', function ($school, $year = null) {
    //Show all terms for given year (or this year if null)

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    if( $year == null )
    {
      $query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name, start_date, end_date,
                      case when term_id = (select term_id from app.current_term) then true else false end as current_term, date_part('year',start_date) as year
                    FROM app.terms
                    ORDER BY date_part('year',start_date), term_name");
      $query->execute();
    }
    else
    {
      $query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name,start_date, end_date,
                      case when term_id = (select term_id from app.current_term) then true else false end as current_term,
                      date_part('year',start_date) as year
                    FROM app.terms
                    WHERE date_part('year',start_date) = :year
                    ORDER BY date_part('year',start_date), term_name");
      $query->execute(array(':year' => $year));
    }

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentReportCards/:school/:student_id', function ($school, $studentId) {
  //Get student report cards

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT report_card_id, report_cards.student_id, report_cards.class_id, class_name, term_name, report_cards.term_id,
                  date_part('year', start_date) as year, report_data, report_cards.report_card_type, class_cat_id,
                  report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
                  report_cards.creation_date::date as date
          FROM app.report_cards
          INNER JOIN app.students ON report_cards.student_id = students.student_id
          INNER JOIN app.classes ON report_cards.class_id = classes.class_id
          INNER JOIN app.terms ON report_cards.term_id = terms.term_id
          LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
          WHERE report_cards.student_id = :studentId
          AND published is true AND students.active IS TRUE
          ORDER BY report_card_id");
    $sth->execute( array(':studentId' => $studentId) );

    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student report card')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentReportCard/:school/:student_id/:class_id/:term_id', function ($school, $studentId, $classId, $termId) {
    //Get student report card

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);

    $sth = $db->prepare("SELECT report_card_id, report_cards.student_id, class_name, term_name, report_cards.term_id,
                  date_part('year', start_date) as year, report_data, report_cards.report_card_type,
                  report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
                  report_cards.creation_date::date as date
          FROM app.report_cards
          INNER JOIN app.students ON report_cards.student_id = students.student_id
          INNER JOIN app.classes ON report_cards.class_id = classes.class_id
          INNER JOIN app.terms ON report_cards.term_id = terms.term_id
          LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
          WHERE report_cards.student_id = :studentId
          AND report_cards.class_id = :classId
          AND report_cards.term_id = :termId AND students.active IS TRUE");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
        $results = $sth->fetch(PDO::FETCH_OBJ);


        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app getting student report card')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getFeeItemsPortal/:school', function ($school) {
    //Show fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount, frequency, active, class_cats_restriction, optional, new_student_only, replaceable
              FROM app.fee_items
              WHERE active = true
              ORDER BY fee_item_id");
        $sth->execute( );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
           $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getPaymentDetails/:school/:payment_id', function ($school, $paymentId) {
    // Get all payment details

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);

    // get payment data
       $sth = $db->prepare("SELECT payments.payment_id, payment_date, payments.amount, payments.payment_method, slip_cheque_no,
                  payments.student_id, replacement_payment, reversed, reversed_date, credit_id --,payments.inv_id
              FROM app.payments
              LEFT JOIN app.credits ON payments.payment_id = credits.payment_id
              WHERE payments.payment_id = :paymentId
     ");
    $sth->execute( array(':paymentId' => $paymentId) );
        $results1 = $sth->fetch(PDO::FETCH_OBJ);

    // get what the payment was applied to
    $sth2 = $db->prepare("SELECT payment_inv_item_id, payment_inv_items.inv_item_id,
                  fee_item,
                  payment_inv_items.amount as line_item_amount, invoice_line_items.inv_id
              FROM app.payment_inv_items
              INNER JOIN app.invoice_line_items
                INNER JOIN app.student_fee_items
                  INNER JOIN app.fee_items
                  ON student_fee_items.fee_item_id = fee_items.fee_item_id
                ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
              ON payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
              WHERE payment_id = :paymentId
              UNION
              SELECT payment_replace_item_id, payment_replacement_items.student_fee_item_id,
                  fee_item,
                  payment_replacement_items.amount as line_item_amount, null
              FROM app.payment_replacement_items
              INNER JOIN app.student_fee_items
                INNER JOIN app.fee_items
                ON student_fee_items.fee_item_id = fee_items.fee_item_id
              ON payment_replacement_items.student_fee_item_id = student_fee_items.student_fee_item_id
              WHERE payment_id = :paymentId
              ORDER BY inv_id
              ");
    $sth2->execute( array(':paymentId' => $paymentId) );
        $results2 = $sth2->fetchAll(PDO::FETCH_OBJ);

    // Loop through and get unique inv_ids for next query
    $invIds = array();
    foreach($results2 as $result2){
      if( !in_array( $result2->inv_id, $invIds ) ) $invIds[] = $result2->inv_id;
    }
    $invIdStr = '{' . implode(',', $invIds) . '}';

    // get the invoice details that payment was applied to

    $sth3 = $db->prepare("SELECT invoices.inv_id,
                inv_date,
                (select coalesce(sum(amount),0) - invoices.total_amount from app.payment_inv_items where inv_id = invoices.inv_id) as overall_balance,
                invoice_line_items.amount,
                coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) as total_paid,
                coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) - invoice_line_items.amount as balance,
                due_date,
                invoice_line_items.inv_item_id,
                fee_item,
                invoice_line_items.amount as line_item_amount,
                term_name,
                date_part('year',terms.start_date) as term_year,
                invoices.canceled
              FROM app.invoices
              INNER JOIN app.invoice_line_items
                INNER JOIN app.student_fee_items
                  INNER JOIN app.fee_items
                  ON student_fee_items.fee_item_id = fee_items.fee_item_id
                ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
              ON invoices.inv_id = invoice_line_items.inv_id
              INNER JOIN app.terms
              ON invoices.term_id = terms.term_id
              WHERE invoices.inv_id = any(:invIds)
              ORDER BY inv_id, due_date, fee_item");

    $sth3->execute( array(':invIds' => $invIdStr) );
    $results3 = $sth3->fetchAll(PDO::FETCH_OBJ);


    $results = new Stdclass();
    $results->payment = $results1;
    $results->paymentItems = $results2;
    $results->invoice = $results3;

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app showing payment')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getInvoiceDetails/:school/:inv_id', function ($school,$invId) {
  // Get all invoice details

  $app = \Slim\Slim::getInstance();

  try
  {
     $db = setDBConnection($school);
     $sth = $db->prepare("SELECT *, amount - total_paid as balance
              FROM (
              SELECT
                invoices.inv_id,
                inv_date,
                invoice_line_items.amount,
                coalesce((select sum(payment_inv_items.amount)
                    from app.payment_inv_items
                    inner join app.payments on payment_inv_items.payment_id = payments.payment_id
                    where payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
                    AND reversed = false),0) as total_paid,
                due_date,
                inv_item_id,
                fee_item
              FROM app.invoices
              INNER JOIN app.invoice_line_items
                INNER JOIN app.student_fee_items
                  INNER JOIN app.fee_items
                  ON student_fee_items.fee_item_id = fee_items.fee_item_id
                ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
              ON invoices.inv_id = invoice_line_items.inv_id
              INNER JOIN app.students
                INNER JOIN app.classes
                ON students.current_class = classes.class_id
              ON invoices.student_id = students.student_id
              WHERE invoices.inv_id = :invId AND students.active IS TRUE
              ORDER BY fee_item
              ) q");
    $sth->execute( array(':invId' => $invId) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app showing invoice')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getExamTypes/:school/:class_cat_id', function ($school, $classCatId) {

    // Get all exam types

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
              FROM app.exam_types
              LEFT JOIN app.class_cats
              ON exam_types.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
              WHERE exam_types.class_cat_id = :classCatId
              ORDER BY sort_order");
    $sth->execute(array(':classCatId' => $classCatId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addFeedback/:school', function ($school) {

  $app = \Slim\Slim::getInstance();

  $allPostVars = json_decode($app->request()->getBody(),true);
  $subject = $allPostVars['subject'];
  $message = $allPostVars['message'];
  $messageFrom = $allPostVars['message_from'];
  $studentId = $allPostVars['student_id'];
  $guardianId = $allPostVars['guardian_id'];

  try
  {

    $db = setDBConnection($school);
    $sth = $db->prepare("INSERT INTO app.communication_feedback (subject, message, message_from, student_id, guardian_id) VALUES (:subject, :message, :messageFrom, :studentId, :guardianId)");
    $sth->execute( array(':subject' => $subject, ':message' => $message, ':messageFrom' => $messageFrom, ':studentId' => $studentId, ':guardianId' => $guardianId) );

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", 'data' => 'Message Posted'));

    $db = null;

    // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app sending feedback')");
				$trafficMonitor->execute( array() );
				$mistrafficdb = null;
				// traffic analysis end

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});


?>
