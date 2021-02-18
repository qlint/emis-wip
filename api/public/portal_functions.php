<?php
$app->post('/parentLogin', function () use($app) {
  // Log parent in
  $allPostVars = $app->request->post();
  $username = $allPostVars['user_name'];
  $pwd = $allPostVars['user_pwd'];
  $devId = ( isset($allPostVars['device_user_id']) ? $allPostVars['device_user_id']: false);

  $username = pg_escape_string($username);
  $pwd = pg_escape_string($pwd);
  $devId = pg_escape_string($devId);

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();

    $userCheckQry = $db->query("SELECT status, parent_id, guardian_id, student_id, subdomain
                                FROM (
                                	SELECT (CASE
                                			WHEN EXISTS (SELECT username FROM parents WHERE username = '$username' AND active IS TRUE) THEN 'proceed' ELSE 'stop'
                                  		END) AS status,
                                  		(CASE
                                  			WHEN EXISTS (SELECT parent_id FROM parents WHERE username = '$username' AND active IS TRUE)
                                  			THEN (SELECT parent_id FROM parents WHERE username = '$username' AND active IS TRUE) ELSE 0
                                  		END) AS parent_id
                                )one
                                LEFT JOIN parent_students USING (parent_id)");
    $userStatus = $userCheckQry->fetch(PDO::FETCH_OBJ);
    $theStatus = $userStatus->status;
    // THE BELOW VALUES ARE USED ONLY WHEN status = 'proceed'
    $theParentId = $userStatus->parent_id;
    $theGuardianId = $userStatus->guardian_id;
    $theStudentId = $userStatus->student_id;
    $theSubDomain = $userStatus->subdomain;
    $gStatus = '';

    if($theStatus === "proceed"){
      $getDb = setDBConnection($theSubDomain);
      $checkIfStudentActive = $getDb->prepare("SELECT (CASE
                                                    		WHEN EXISTS (SELECT active FROM app.student_guardians WHERE guardian_id = :guardianId AND active IS TRUE)
                                                    		THEN 'proceed'
                                                    		ELSE 'stop'
                                                    	END) AS active");
      $checkIfStudentActive->execute(array(':guardianId' => $theGuardianId));
      $guardianStatus = $checkIfStudentActive->fetch(PDO::FETCH_OBJ);
      $gStatus = $guardianStatus->active;
    }else{
      $gStatus = 'stop';
    }

    if($gStatus === "proceed"){
      if(isset($allPostVars['device_user_id'])){
          $devId = str_replace('"', "", $devId); // remove all double quotes if they exist
          $devId = str_replace("'", "", $devId); // remove all single quotes if they exist
        $updateDeviceId = $db->prepare("UPDATE parents SET device_user_id = :devId WHERE parent_id = :parentId");
        $updateDeviceId->execute( array(':devId' => $devId, ':parentId' => $theParentId) );
      }

      if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
       $browser = 'Internet explorer';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
        $browser = 'Internet explorer';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
       $browser = 'Mozilla Firefox';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
       $browser = 'Google Chrome';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
       $browser = "Opera Mini";
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
       $browser = "Opera";
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
       $browser = "Safari";
    elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'insomnia') !== FALSE)
       $browser = "Insomnia";
     else
       $browser = 'Something else';
      $activityUpdate = $db->prepare("UPDATE parents SET browser = :browser, last_active = now()
                                    WHERE parent_id = :parentId");
      $activityUpdate->execute(array(':browser' => $browser, ':parentId' => $theParentId));

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
                               students.active, class_name, class_id, class_cat_id, report_card_type, TO_CHAR(admission_date :: DATE, 'dd/mm/yyyy') AS admission_date, student_category, gender, date_text_to_date(dob) AS dob, payment_method, payment_plan_name,
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
                            case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method,
                            seen_count, seen_by,
                            (
              								CASE
              									WHEN string_to_array(seen_by, ',') && '{". implode(',',$students) ."}'::text[] THEN true
		                            ELSE false
              								END
              							) AS seen
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
                          AND communications.audience_id NOT IN (3,4,7,9,10,11,13)
                          --AND students.active IS TRUE
                          AND communications.com_type_id NOT IN (6)
                          AND date_trunc('year', communications.creation_date) >=  date_trunc('year', now() - interval '1 year')
                          ORDER BY creation_date desc");

                $studentsArray = "{" . implode(',',$students) . "}";
                $sth5->execute(array(':studentIds' => $studentsArray));
                $news[$school] = $sth5->fetchAll(PDO::FETCH_OBJ);

                $sthResources = $db->prepare("SELECT student_id, resource_id, r.class_id, r.term_id, r.emp_id, resource_name,
                              resource_type, file_name, additional_text, r.active AS active_resource, vimeo_path,
                              e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name AS teacher_name,
                               s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                               term_name, class_name, TO_CHAR(r.creation_date :: DATE, 'dd/mm/yyyy') AS creation_date
                            FROM app.school_resources r
                            INNER JOIN app.classes c USING (class_id)
                            INNER JOIN app.students s ON s.current_class = c.class_id
                            INNER JOIN app.employees e USING (emp_id)
                            INNER JOIN app.terms t USING (term_id)
                            WHERE s.student_id = :studentId");
                $sthResources->execute(array(':studentId' => $student->student_id));
                $resources[$school] = $sthResources->fetchAll(PDO::FETCH_OBJ);

              }

              /* get sent messages by student's parent */
              $feedback = Array();
              foreach( $studentsBySchool as $school => $students )
              {
                if( $db !== null ) $db = null;
                $db = setDBConnection($school);

                $sth6 = $db->prepare("SELECT *, TO_CHAR(sent_date :: DATE, 'dd/mm/yyyy') AS formatted_date FROM (
                                      	SELECT 'News' AS feedback_type, cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                                      		cf.message_from as posted_by,
                                      		s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                                      		g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                                      		c.class_name, student_id, null AS emp_id, null AS teacher_name,
                                          null AS student_attachment
                                      	FROM app.communication_feedback cf
                                      	LEFT JOIN app.students s USING (student_id)
                                      	LEFT JOIN app.guardians g USING (guardian_id)
                                      	LEFT JOIN app.classes c USING (class_id)
                                      	WHERE cf.student_id = any(:studentIds)
                                      	AND s.active IS TRUE
                                      	UNION
                                      	SELECT 'Homework' AS feedback_type, hf.homework_feedback_id as post_id, hf.creation_date as sent_date, hf.title AS subject, hf.message,
                                      		hf.added_by as posted_by,
                                      		s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                                      		g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                                      		c.class_name, student_id, hf.emp_id, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as teacher_name,
                                          student_attachment
                                      	FROM app.homework_feedback hf
                                      	INNER JOIN app.students s USING (student_id)
                                      	INNER JOIN app.guardians g USING (guardian_id)
                                      	INNER JOIN app.classes c USING (class_id)
                                        LEFT JOIN app.employees e USING (emp_id)
                                      	WHERE hf.student_id = any(:studentIds)
                                      	AND s.active IS TRUE
                                      )a
                                      ORDER BY sent_date DESC");

                $studentsArray = "{" . implode(',',$students) . "}";
                $sth6->execute(array(':studentIds' => $studentsArray));
                $feedback[$school] = $sth6->fetchAll(PDO::FETCH_OBJ);

              }

              $result->students = $studentDetails;
              $result->news = $news;
              $result->feedback = $feedback;
              $result->resources = $resources;

              $app->response->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              $db = null;

              echo json_encode(array('response' => 'success', 'data' => $result, 'post' => $allPostVars ));

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

$app->post('/studentLogin', function () use($app) {
  // Log student in
  $allPostVars = $app->request->post();
  $email = $allPostVars['email'];
  $pwd = $allPostVars['password'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();

    $stdntCheckQry = $db->prepare("SELECT * FROM students_portal WHERE email = :email AND pwd = :pwd");
    $stdntCheckQry->execute(array(':email' => $email, ':pwd' => $pwd));
    $stdntStatus = $stdntCheckQry->fetch(PDO::FETCH_OBJ);
    $theStudent = (isset($stdntStatus->student_portal_id) ? $stdntStatus->student_portal_id : null);
    $theSchool = (isset($stdntStatus->school) ? $stdntStatus->school : null);
    $theStudentId = (isset($stdntStatus->student_id) ? $stdntStatus->student_id : null);

    if($theStudent){
      $getDb = setDBConnection($theSchool);
      $studentQry = $getDb->prepare("SELECT s.first_name, s.middle_name, s.last_name,
                                    		s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                                    		admission_number, gender, date_text_to_date(dob) AS dob, c.class_name, student_category,
                                    		nationality, house, club, TO_CHAR(admission_date :: DATE, 'dd/mm/yyyy') AS admission_date,
                                    		student_image
                                    FROM app.students s
                                    INNER JOIN app.classes c ON s.current_class = c.class_id
                                    WHERE s.student_id = :theStudentId");
      $studentQry->execute(array(':theStudentId' => $theStudentId));
      $student = $studentQry->fetch(PDO::FETCH_OBJ);

      $schoolQry = $getDb->prepare("SELECT (SELECT value FROM app.settings WHERE name = 'subdomain') AS subdomain,
                                      		(SELECT value FROM app.settings WHERE name = 'Address 1') AS address_1,
                                      		(SELECT value FROM app.settings WHERE name = 'Address 2') AS address_2,
                                      		(SELECT value FROM app.settings WHERE name = 'Email Address') AS email_address,
                                      		(SELECT value FROM app.settings WHERE name = 'Phone Number') AS phone_number,
                                      		(SELECT value FROM app.settings WHERE name = 'School Name') AS school_name,
                                      		(SELECT value FROM app.settings WHERE name = 'School Level') AS school_level,
                                      		(SELECT value FROM app.settings WHERE name = 'logo') AS logo,
                                      		(SELECT value FROM app.settings WHERE name = 'Letterhead') AS letter_head");
      $schoolQry->execute(array());
      $school = $schoolQry->fetch(PDO::FETCH_OBJ);

      $termsQry = $getDb->prepare("SELECT term_id, term_name, start_date, end_date
                                    FROM app.terms ORDER BY start_date DESC");
      $termsQry->execute(array());
      $terms = $termsQry->fetchAll(PDO::FETCH_OBJ);

      $homeworkQry = $getDb->prepare("SELECT homework_id, assigned_date, TO_CHAR(assigned_date :: DATE, 'dd/mm/yyyy') assigned_date_formated,
                          	emp_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                          	title, body, homework.post_status_id, post_status, class_name, class_subjects.class_id, due_date, TO_CHAR(due_date :: DATE, 'dd/mm/yyyy') AS due_date_formated,
                            subject_name, class_subjects.subject_id,
                          	attachment, homework.modified_date,
                            (
                              CASE
                                WHEN string_to_array(seen_by,',')::int[] @> ARRAY[:theStudentId::int] THEN true
                                ELSE false
                              END
                            ) AS seen
                          FROM app.homework
                          INNER JOIN app.blog_post_statuses USING (post_status_id)
                          INNER JOIN app.class_subjects USING (class_subject_id)
                          INNER JOIN app.classes USING (class_id)
                          INNER JOIN app.students ON classes.class_id = students.current_class
                          			AND class_subjects.class_id = classes.class_id
                          INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
                          			AND homework.class_subject_id = class_subjects.class_subject_id
                          LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
                          WHERE student_id = :theStudentId
                          AND homework.post_status_id = 1
                          --AND date_trunc('year', homework.creation_date) =  date_trunc('year', now())
                          AND homework.creation_date >  CURRENT_DATE - INTERVAL '3 months'
                          --AND (assigned_date between date_trunc('week', now())::date and (date_trunc('week', now())+ '6 days'::interval)::date OR due_date > now() )
                          AND students.active IS TRUE
                          AND (homework.students ILIKE '%' || :theStudentId || '%' OR homework.students IS NULL)
                          ORDER BY homework.assigned_date DESC, subjects.sort_order");
      $homeworkQry->execute(array(':theStudentId' => $theStudentId));
      $homework = $homeworkQry->fetchAll(PDO::FETCH_OBJ);

      $timetableQry = $getDb->prepare("SELECT student_name, class_id, class_name, term_id, term_name, year,
                                        	array_to_json(array_agg(day_lessons)) AS timetable
                                        FROM (
                                        	SELECT student_name, class_id, class_name, term_id, term_name, year,
                                        		'{\"' || day || '\":' || day_details ||'}' AS day_lessons
                                        	FROM (
                                        		SELECT student_name, class_id, class_name, term_id, term_name, year, day,
                                        			array_to_json(array_agg(subject_details)) AS day_details
                                        		FROM (
                                        			SELECT s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                                        				ct.class_id, c.class_name, ct.term_id, t.term_name, ct.year, ct.day, row_to_json(a) AS subject_details
                                        			FROM (
                                        				SELECT class_timetable_id, ctt.day, ctt.subject_name, ctt.start_hour, ctt.start_minutes, ctt.end_hour, ctt.end_minutes,
                                                  e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name AS teacher_name
                                        				FROM app.class_timetables ctt
                                                INNER JOIN app.subjects s USING (subject_id)
                                                LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
                                        				WHERE ctt.class_id = (SELECT current_class FROM app.students WHERE student_id = :theStudentId)
                                        			)a
                                        			INNER JOIN app.class_timetables ct USING (class_timetable_id)
                                        			INNER JOIN app.classes c USING (class_id)
                                        			INNER JOIN app.terms t USING (term_id)
                                        			INNER JOIN app.students s ON c.class_id = s.current_class
                                        			WHERE s.student_id = :theStudentId
                                        		)b
                                        		GROUP BY student_name, class_id, class_name, term_id, term_name, year, day
                                        	)c
                                        )d
                                        GROUP BY student_name, class_id, class_name, term_id, term_name, year");
      $timetableQry->execute(array(':theStudentId' => $theStudentId));
      $timetable = $timetableQry->fetch(PDO::FETCH_OBJ);
      if($timetable){
        $timetable->timetable = json_decode($timetable->timetable);
        for ($i=0; $i < count($timetable->timetable); $i++) {
          $timetable->timetable[$i] = json_decode($timetable->timetable[$i]);
        }
      }

      $results = new stdClass();
  		$results->details = $student;
  		$results->school = $school;
  		$results->terms = $terms;
  		$results->homework = $homework;
  		$results->timetable = $timetable;
  		// $results->overallLastTerm = $overallLastTerm;
  		// $results->overallLastTermByAverage = $overallLastTermByAverage;
  		// $results->graphPoints = $graphPoints;

      if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
       $browser = 'Internet explorer';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
        $browser = 'Internet explorer';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
       $browser = 'Mozilla Firefox';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
       $browser = 'Google Chrome';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
       $browser = "Opera Mini";
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
       $browser = "Opera";
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
       $browser = "Safari";
    elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'insomnia') !== FALSE)
       $browser = "Insomnia";
     else
       $browser = 'Something else';

      $stdntUpdate = $db->prepare("UPDATE students_portal SET browser = :browser, last_active = now()
                                    WHERE email = :email AND pwd = :pwd");
      $stdntUpdate->execute(array(':browser' => $browser, ':email' => $email, ':pwd' => $pwd));

      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
      $getDb = null;
    }else{
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'message' => 'The credentials you have entered to not match. Confirm and/or check your spellings and try again.' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->post('/studentSubscriber', function () use($app) {
  // Log student in
  $allPostVars = $app->request->post();
  $email = $allPostVars['email'];
  $pwd = $allPostVars['password'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();

    $stdntCheckQry = $db->prepare("SELECT * FROM students_portal WHERE email = :email AND pwd = :pwd");
    $stdntCheckQry->execute(array(':email' => $email, ':pwd' => $pwd));
    $stdntStatus = $stdntCheckQry->fetch(PDO::FETCH_OBJ);
    $theStudent = (isset($stdntStatus->student_portal_id) ? $stdntStatus->student_portal_id : null);
    $theSchool = (isset($stdntStatus->school) ? $stdntStatus->school : null);
    $theStudentId = (isset($stdntStatus->student_id) ? $stdntStatus->student_id : null);

    if($theStudent){
      $getDb = setDBConnection($theSchool);
      $studentQry = $getDb->prepare("SELECT s.first_name, s.middle_name, s.last_name,
                                    		s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                                    		admission_number, gender, date_text_to_date(dob) AS dob, c.class_name, student_category,
                                    		TO_CHAR(admission_date :: DATE, 'dd/mm/yyyy') AS admission_date,
                                    		CASE WHEN student_image IS NULL THEN NULL ELSE 'https://cdn.eduweb.co.ke/students/' || student_image END AS image,
                                        :email AS email
                                    FROM app.students s
                                    INNER JOIN app.classes c ON s.current_class = c.class_id
                                    WHERE s.student_id = :theStudentId");
      $studentQry->execute(array(':email' => $email, ':theStudentId' => $theStudentId));
      $student = $studentQry->fetch(PDO::FETCH_OBJ);

      if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
       $browser = 'Internet explorer';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
        $browser = 'Internet explorer';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
       $browser = 'Mozilla Firefox';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
       $browser = 'Google Chrome';
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
       $browser = "Opera Mini";
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
       $browser = "Opera";
     elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
       $browser = "Safari";
    elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'insomnia') !== FALSE)
       $browser = "Insomnia";
     else
       $browser = 'Something else';

      $stdntUpdate = $db->prepare("UPDATE students_portal SET browser = :browser, last_active = now()
                                    WHERE email = :email AND pwd = :pwd");
      $stdntUpdate->execute(array(':browser' => $browser, ':email' => $email, ':pwd' => $pwd));

      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $student ));
      $db = null;
      $getDb = null;
    }else{
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'message' => 'The credentials you have entered to not match. Confirm and/or check your spellings and try again.' ));
      $db = null;
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
  $phoneNumber = pg_escape_string($phoneNumber);
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

        $db = pg_connect("host=localhost port=5433 dbname=eduweb_mis user=postgres password=pg_edu@8947");

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
          	$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5433" : "5434");
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
              // THESE USERS ARE IN MULTIPLE SCHOOLS, GIVE THEM AN EEROR MSG OR SOMETHING

              // $app->response()->setStatus(200);
              // $app->response()->headers->set('Content-Type', 'application/json');
              // echo  json_encode(array('response' => 'Error', 'message' => "There seems to be an issue either with this phone number or your details. Please consult with your school.", "status" => "Cannot proceed with registration." ));
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
            $registrationMessageObj->subscriber_name = "api";// $school;

            $msgRecipientsObj = new stdClass();
            $msgRecipientsObj->recipient_name = "$registrationData->first_name $registrationData->last_name";
            $msgRecipientsObj->phone_number = "+254" . $phoneNumber;
            array_push($registrationMessageObj->message_recipients, clone $msgRecipientsObj);

            sendSms($registrationMessageObj->message_by, $registrationMessageObj->message_text, json_encode($registrationMessageObj->message_recipients), "dev2");
            /*
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
              echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found but there seems to be a slight problem sending the SMS. Please try again.", "status" => "SMS not sent", "error" => curl_error($ch), "phone" => $phoneNumber ));
            }
            else
            {
              $app->response()->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found. A confirmation SMS will be sent to you for validation to allow you to proceed to register your password.", "status" => "SMS sent successfully", "phone" => $phoneNumber ));
            }

            curl_close($ch);
            */
            $app->response()->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo  json_encode(array('response' => 'Success', 'message' => "Your request is being processed. You will receive an SMS shortly." ));

        }else{
          $app->response()->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo  json_encode(array('response' => 'error', 'message' => "The submitted phone number has not been found in our system. Please use a phone number that you use with the school.", "status" => "Phone number not found." ));
        }
    }else{
      // resend the code then delete it
      $db1 = getLoginDB();
      $qry = $db1->query("SELECT * FROM registration_codes WHERE telephone = '$phoneNumber' LIMIT 1;");
      $usr = $qry->fetch(PDO::FETCH_OBJ);

      if(isset($usr->first_name)){
        // first we need to change the phone format to +[code]phone
        $firstChar = substr($phoneNumber, 0, 1);
        if($firstChar === "0"){$phoneNumber = preg_replace('/^0/', '', $phoneNumber);}
        // we now create & send the actual sms
        $registrationMessageObj = new stdClass();
        $registrationMessageObj->message_recipients = Array();
        $registrationMessageObj->message_by = "Eduweb Mobile App Registration";
        $registrationMessageObj->message_date = date('Y-m-d H:i:s');
        $registrationMessageObj->message_text = "Hello $usr->first_name, Your code for the Eduweb Mobile App registration is $usr->code ";
        $registrationMessageObj->subscriber_name = "api";// $school;

        $msgRecipientsObj = new stdClass();
        $msgRecipientsObj->recipient_name = "$usr->first_name $usr->last_name";
        $msgRecipientsObj->phone_number = "+254" . $phoneNumber;
        array_push($registrationMessageObj->message_recipients, clone $msgRecipientsObj);

        sendSms($registrationMessageObj->message_by, $registrationMessageObj->message_text, json_encode($registrationMessageObj->message_recipients), "dev2");
        /*
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
          echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found but there seems to be a slight problem sending the SMS. Please try again.", "status" => "SMS not sent", "error" => curl_error($ch), "phone" => $phoneNumber, "code" => $usr->code ));
        }
        else
        {
          $app->response()->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found. A confirmation SMS will be sent to you for validation to allow you to proceed to register your password.", "status" => "SMS sent successfully", "phone" => $phoneNumber, "code" => $usr->code ));
        }

        curl_close($ch);
        */
        $db1 = null;
      }else{
        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'message' => "The submitted phone number is already in use, either by you or another user.", "status" => "Phone number already in use" ));
      }
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
      $phone = pg_escape_string($phone);
      $code = pg_escape_string($code);

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

  $pwd = pg_escape_string($pwd);
  $phone = pg_escape_string($phone);

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

        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
         $browser = 'Internet explorer';
       elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
          $browser = 'Internet explorer';
       elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
         $browser = 'Mozilla Firefox';
       elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
         $browser = 'Google Chrome';
       elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
         $browser = "Opera Mini";
       elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
         $browser = "Opera";
       elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
         $browser = "Safari";
      elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'insomnia') !== FALSE)
         $browser = "Insomnia";
       else
         $browser = 'Something else';

      $sth2 = $db->prepare("UPDATE parents SET password = :newPwd, browser = :browser, last_active = now() WHERE parent_id = :userId");
      $sth2->execute( array(':userId' => $userId, ':newPwd' => $newPwd, ':browser' => $browser) );
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
  // $http_origin = $_SERVER['HTTP_ORIGIN'];
  // header("Access-Control-Allow-Origin: $http_origin");

  // if ($http_origin == "http://www.domain1.com" || $http_origin == "http://www.domain2.com")
  // {
  //     header("Access-Control-Allow-Origin: $http_origin");
  // }
  // header('Access-Control-Allow-Origin: *');
  // header("Access-Control-Allow-Credentials: true");
  // header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS, PATCH');
  // header('Access-Control-Max-Age: 1000');
  // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, token');
  // header("Referrer-Policy: no-referrer");
  // Forgot password

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
  $pwdString = implode("-",$twoRndWords);
  $temporaryPwd = preg_replace('~[\r\n]+~', '', $pwdString);
  // echo $temporaryPwd;

  $app = \Slim\Slim::getInstance();

  try
  {
    //first check if this number is in use in active use ie taken
    $db0 = getLoginDB();

    $previousIncomplete = $db0->query("SELECT (CASE WHEN EXISTS (SELECT usr_name AS phone FROM forgot_password WHERE usr_name = '$phoneNumber') THEN 'incomplete-reset' ELSE 'continue' END) AS state");
    $checkOne = $previousIncomplete->fetch(PDO::FETCH_OBJ);
    $incompleteStatus = $checkOne->state;
    if($incompleteStatus === "continue"){

      $checkOne = $db0->query("SELECT (CASE
                                      		WHEN EXISTS (SELECT * FROM (
                                                        SELECT username AS phone FROM parents WHERE username = '$phoneNumber'
                                                        )a
                                                        LIMIT 1) THEN 'found'
                                      		ELSE 'not-found'
                                        END) AS check_one, (SELECT parent_id FROM parents WHERE username = '$phoneNumber') AS parent_id,
                                        (SELECT first_name FROM parents WHERE username = '$phoneNumber') AS parent_name");
      $lineCheck = $checkOne->fetch(PDO::FETCH_OBJ);
      $phoneCheck = $lineCheck->check_one;
      $parentId = $lineCheck->parent_id;
      $parentName = $lineCheck->parent_name;

      if($phoneCheck === "found"){
          $sth2 = $db0->prepare("INSERT INTO forgot_password(usr_name, temp_pwd, parent_id)
                                VALUES ('$phoneNumber','$temporaryPwd',$parentId);");
          $sth2->execute( array() );

          // first we need to change the phone format to +[code]phone
          $firstChar = substr($phoneNumber, 0, 1);
          if($firstChar === "0"){$phoneNumber = preg_replace('/^0/', '', $phoneNumber);}
          // we now create & send the actual sms
          $forgotPwdObj = new stdClass();
          $forgotPwdObj->message_recipients = Array();
          $forgotPwdObj->message_by = "Eduweb Mobile App Forgot Password";
          $forgotPwdObj->message_date = date('Y-m-d H:i:s');
          $forgotPwdObj->message_text = "Hello $parentName, use $temporaryPwd as your temporary password for the Eduweb Mobile App.";
          $forgotPwdObj->subscriber_name = "api";// to be replaced;


          $msgRecipientsObj = new stdClass();
          $msgRecipientsObj->recipient_name = "$parentName";
          $msgRecipientsObj->phone_number = "+254" . $phoneNumber;
          array_push($forgotPwdObj->message_recipients, clone $msgRecipientsObj);

          sendSms($forgotPwdObj->message_by, $forgotPwdObj->message_text, json_encode($forgotPwdObj->message_recipients), "dev2");
          /*
          // send the message
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, "https://sms_api.eduweb.co.ke/api/sendBulkSms"); // the endpoint url
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8')); // the content type headers
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
          curl_setopt($ch, CURLOPT_HEADER, FALSE);
          curl_setopt($ch, CURLOPT_POST, TRUE); // the request type
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($forgotPwdObj)); // the data to post
          curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // this works like jquery ajax (asychronous)
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // disable SSL certificate checks and it's complications

          $resp = curl_exec($ch);

          if($resp === false)
          {
            $app->response()->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo  json_encode(array('response' => 'Success', 'message' => "An error was encountered while attempting to SMS you your temporary password for confirmation and reset. Please try again.", "status" => "SMS not sent", "error" => curl_error($ch) ));
          }
          else
          {
            $app->response()->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo  json_encode(array('response' => 'Success', 'message' => "A temporary password has been sent to you via SMS for confirmation and reset.", "status" => "SMS sent successfully", "temporary-code" => $temporaryPwd, "phone" => $phoneNumber ));
          }
          curl_close($ch);
          */
          $app->response()->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo  json_encode(array('response' => 'Success', 'message' => "The request is being processed", "status" => "You will receive an sms shortly" ));
      }else{
        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'message' => "The submitted details were not found in our records.", "status" => "Phone number not found, no sms will be sent." ));
      }
    }else{
      $user = $db0->query("SELECT p.first_name, p.last_name, fp.usr_name, fp.temp_pwd
                                          FROM forgot_password fp
                                          INNER JOIN parents p USING (parent_id)
                                          WHERE usr_name = '$phoneNumber'
                                          LIMIT 1");
      $userDetails = $user->fetch(PDO::FETCH_OBJ);
      $fName = $userDetails->first_name;
      $lName = $userDetails->last_name;
      $code = $userDetails->temp_pwd;
      $phone = $userDetails->usr_name;

      // first we need to change the phone format to +[code]phone
      $firstChar = substr($phoneNumber, 0, 1);
      if($firstChar === "0"){$phoneNumber = preg_replace('/^0/', '', $phoneNumber);}
      // we now create & send the actual sms
      $forgotPwdObj = new stdClass();
      $forgotPwdObj->message_recipients = Array();
      $forgotPwdObj->message_by = "Eduweb Mobile App Forgot Password";
      $forgotPwdObj->message_date = date('Y-m-d H:i:s');
      $forgotPwdObj->message_text = "Hello $fName, use $code as your temporary password for the Eduweb Mobile App.";
      $forgotPwdObj->subscriber_name = "api";// to be replaced;


      $msgRecipientsObj = new stdClass();
      $msgRecipientsObj->recipient_name = "$fName $lName";
      $msgRecipientsObj->phone_number = "+254" . $phoneNumber;
      array_push($forgotPwdObj->message_recipients, clone $msgRecipientsObj);

      sendSms($forgotPwdObj->message_by, $forgotPwdObj->message_text, json_encode($forgotPwdObj->message_recipients), "dev2");
      /*
      // send the message
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://sms_api.eduweb.co.ke/api/sendBulkSms"); // the endpoint url
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8')); // the content type headers
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_POST, TRUE); // the request type
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($forgotPwdObj)); // the data to post
      curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // this works like jquery ajax (asychronous)
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // disable SSL certificate checks and it's complications

      $resp = curl_exec($ch);

      if($resp === false)
      {
        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'Success', 'message' => "An error was encountered while attempting to SMS you your temporary password for confirmation and reset. Please try again.", "status" => "SMS not sent", "temporary-code" => $temporaryPwd, "phone" => $phoneNumber, "error" => curl_error($ch) ));
      }
      else
      {
        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'Success', 'message' => "You seem to have previously tried to reset your password but did not complete the process. An SMS has been resent with your temporary password.", "status" => "SMS resent.", "temporary-code" => $temporaryPwd, "phone" => $phoneNumber ));
      }

      curl_close($ch);
      */
      $app->response()->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'Success', 'message' => "You should receive an SMS shortly with your temporary password.", "status" => "SMS resent.", "temporary-code" => $temporaryPwd, "phone" => $phoneNumber ));
    }
    $db0 = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/confirmTemporaryPassword/:phone/:tempPwd', function ($phoneNumber,$tempPwd){

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getLoginDB();

    $checkPassword = $db->query("SELECT (CASE
                                    		WHEN EXISTS (SELECT * FROM (
                                                      SELECT usr_name AS phone FROM forgot_password WHERE usr_name = '$phoneNumber' AND temp_pwd = '$tempPwd'
                                                      )a
                                                      LIMIT 1) THEN 'found'
                                    		ELSE 'not-found'
                                      END) AS pwd_status,
                                      (SELECT temp_pwd FROM forgot_password WHERE usr_name = '$phoneNumber' AND temp_pwd = '$tempPwd') AS pwd,
                                      (SELECT parent_id FROM forgot_password WHERE usr_name = '$phoneNumber' AND temp_pwd = '$tempPwd') AS parent_id");
    $userCheck = $checkPassword->fetch(PDO::FETCH_OBJ);
    $pwdCheck = $userCheck->pwd_status;
    $pwd = $userCheck->pwd;
    $parentId = $userCheck->parent_id;

    if($pwdCheck === "found"){
        $sth2 = $db->prepare("UPDATE parents SET password = :pwd WHERE parent_id = :parentId;");
        $sth2->execute( array(':parentId'=>$parentId, ':pwd'=>$pwd) );

        // we now remove this record
        $sth3 = $db->prepare("DELETE FROM forgot_password WHERE parent_id = :parentId AND temp_pwd = :pwd;");
        $sth3->execute( array(':parentId'=>$parentId, ':pwd'=>$pwd) );

        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'Success', 'message' => "Your password has been successfully reset. You can now log in and change it.", "status" => "Password reset successfully" ));

    }else{
      $app->response()->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'message' => "The submitted details were not found in our records. Confirm the details and try again.", "status" => "Data not found." ));
    }
    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/resendOtp/:phone', function ($phoneNumber){
  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing

  $app = \Slim\Slim::getInstance();

  try
  {
    //first check if this number has a code sent to it
    $db0 = getLoginDB();

    $checkOne = $db0->query("SELECT (CASE
                                    		WHEN EXISTS (SELECT * FROM (
                                                      SELECT telephone AS phone FROM registration_codes WHERE telephone = '$phoneNumber'
                                                      )a
                                                      LIMIT 1) THEN 'proceed'
                                    		ELSE 'stop'
                                    	END) AS check_one");
    $status = $checkOne->fetch(PDO::FETCH_OBJ);
    $status = $status->check_one;
    $db0 = null;
    if($status === "proceed"){

      $db = getLoginDB();

      $userQry = $db->query("SELECT telephone, code, first_name, last_name, first_name || ' ' || last_name AS full_name
                              FROM registration_codes WHERE telephone = '$phoneNumber'");
      $userData = $userQry->fetch(PDO::FETCH_OBJ);

      // first we need to change the phone format to +[code]phone
      $firstChar = substr($phoneNumber, 0, 1);
      if($firstChar === "0"){$phoneNumber = preg_replace('/^0/', '', $phoneNumber);}
      // we now create & send the actual sms
      $messageObj = new stdClass();
      $messageObj->message_recipients = Array();
      $messageObj->message_by = "Eduweb Forgot Password";
      $messageObj->message_date = date('Y-m-d H:i:s');
      $messageObj->message_text = "Hello $userData->first_name, Your code for the Eduweb Mobile App is $userData->code ";
      $messageObj->subscriber_name = "api";// $school;

      $msgRecipientsObj = new stdClass();
      $msgRecipientsObj->recipient_name = "$userData->full_name";
      $msgRecipientsObj->phone_number = "+254" . $phoneNumber;
      array_push($messageObj->message_recipients, clone $msgRecipientsObj);

      sendSms($messageObj->message_by, $messageObj->message_text, json_encode($messageObj->message_recipients), "dev2");
      /*
      // send the message
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://sms_api.eduweb.co.ke/api/sendBulkSms"); // the endpoint url
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8')); // the content type headers
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_POST, TRUE); // the request type
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageObj)); // the data to post
      curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // this works like jquery ajax (asychronous)
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // disable SSL certificate checks and it's complications

      $resp = curl_exec($ch);

      if($resp === false)
      {
        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'Success', 'message' => "Success, code your code was found.", "status" => "SMS not sent", "error" => curl_error($ch) ));
      }
      else
      {
        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found. A confirmation SMS will be sent to you for validation to allow you to proceed to register your password.", "status" => "SMS sent successfully" ));
      }

      curl_close($ch);
      */

      $app->response()->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'Success', 'message' => "Phone record has been found. A confirmation SMS will be sent to you for validation to allow you to proceed to register your password.", "status" => "SMS will be sent shortly." ));

    }else{
      $app->response()->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'message' => "To reset your password, click on forgot password.", "status" => "No code" ));
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

    if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
     $browser = 'Internet explorer';
   elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
      $browser = 'Internet explorer';
   elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
     $browser = 'Mozilla Firefox';
   elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
     $browser = 'Google Chrome';
   elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
     $browser = "Opera Mini";
   elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
     $browser = "Opera";
   elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
     $browser = "Safari";
  elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'insomnia') !== FALSE)
     $browser = "Insomnia";
   else
     $browser = 'Something else';
    $activityUpdate = $db->prepare("UPDATE parents SET browser = :browser, last_active = now()
                                  WHERE parent_id = :parentId");
    $activityUpdate->execute(array(':browser' => $browser, ':parentId' => $parentId));

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
                     students.active, class_name, class_id, class_cat_id, report_card_type, TO_CHAR(admission_date :: DATE, 'dd/mm/yyyy') AS admission_date, student_category, gender, date_text_to_date(dob) AS dob, payment_method, payment_plan_name,
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
                    case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method,
                    seen_count, seen_by,
                    (
              				CASE
              					WHEN string_to_array(seen_by, ',') && '{". implode(',',$students) ."}'::text[] THEN true
		                    ELSE false
              				END
              			) AS seen
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
                  AND communications.audience_id NOT IN (3,4,7,9,10,11,13)
                  --AND students.active IS TRUE
                  AND communications.com_type_id NOT IN (6)
                  AND date_trunc('year', communications.creation_date) >=  date_trunc('year', now() - interval '1 year')
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

        $sth6 = $db->prepare("SELECT *, TO_CHAR(sent_date :: DATE, 'dd/mm/yyyy') AS formatted_date FROM (
                                SELECT 'News' AS feedback_type, cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                                  cf.message_from as posted_by,
                                  s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                                  g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                                  c.class_name, student_id, null AS emp_id, null AS teacher_name, null AS student_attachment
                                FROM app.communication_feedback cf
                                LEFT JOIN app.students s USING (student_id)
                                LEFT JOIN app.guardians g USING (guardian_id)
                                LEFT JOIN app.classes c USING (class_id)
                                WHERE cf.student_id = any(:studentIds)
                                AND s.active IS TRUE
                                UNION
                                SELECT 'Homework' AS feedback_type, hf.homework_feedback_id as post_id, hf.creation_date as sent_date, hf.title AS subject, hf.message,
                                  hf.added_by as posted_by,
                                  s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                                  g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                                  c.class_name, student_id, hf.emp_id, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as teacher_name,
                                  student_attachment
                                FROM app.homework_feedback hf
                                INNER JOIN app.students s USING (student_id)
                                INNER JOIN app.guardians g USING (guardian_id)
                                INNER JOIN app.classes c USING (class_id)
                                LEFT JOIN app.employees e USING (emp_id)
                                WHERE hf.student_id = any(:studentIds)
                                AND s.active IS TRUE
                              )a
                              ORDER BY sent_date DESC");

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

$app->get('/getSchDetails/:school', function ($school) {
    //Show school details

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'School Name') THEN (SELECT value FROM app.settings WHERE name = 'School Name') ELSE null END) AS school_name,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'School Level') THEN (SELECT value FROM app.settings WHERE name = 'School Level') ELSE null END) AS school_level,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'School Type') THEN (SELECT value FROM app.settings WHERE name = 'School Type') ELSE null END) AS school_type,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'Address 1') THEN (SELECT value FROM app.settings WHERE name = 'Address 1') ELSE null END) AS address_1,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'Address 2') THEN (SELECT value FROM app.settings WHERE name = 'Address 2') ELSE null END) AS address_2,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'Country') THEN (SELECT value FROM app.settings WHERE name = 'Country') ELSE null END) AS country,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'Email Address') THEN (SELECT value FROM app.settings WHERE name = 'Email Address') ELSE null END) AS email_address,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'Phone Number') THEN (SELECT value FROM app.settings WHERE name = 'Phone Number') ELSE null END) AS phone_number,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'logo') THEN (SELECT value FROM app.settings WHERE name = 'logo') ELSE null END) AS logo,
                          	(CASE WHEN EXISTS (SELECT value FROM app.settings WHERE name = 'Letterhead') THEN (SELECT value FROM app.settings WHERE name = 'Letterhead') ELSE null END) AS letterhead");
        $sth->execute();
        $details = $sth->fetch(PDO::FETCH_OBJ);

        $sth2 = $db->prepare("SELECT sb.* FROM app.school_bnks sb WHERE active IS TRUE
                              UNION
                              SELECT 0 AS bnk_id, 'MPESA' AS name, null AS branch, null AS acc_name, (SELECT value FROM app.settings WHERE name = 'Mpesa Details') AS acc_number, true AS active, now() AS creation_date, null AS modified_date
                              ORDER BY bnk_id DESC");
        $sth2->execute();
        $bankingDetails = $sth2->fetchAll(PDO::FETCH_OBJ);

        if($details) {
            $details->banking_details = $bankingDetails;
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $details ));
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

$app->get('/schoolTerms/:school/:studentId', function ($school,$studentId) {
    //Show school terms

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT a.* FROM (
                              SELECT DISTINCT em.term_id, t.term_name, t.term_number, em.student_id, cs.class_id, c.class_name
                              FROM app.exam_marks em
                              INNER JOIN app.terms t USING (term_id)
                              INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                              INNER JOIN app.class_subjects cs USING (class_subject_id)
                              INNER JOIN app.classes c USING (class_id)
                              WHERE em.student_id = :studentId
                              ORDER BY term_id DESC
                            )a
                            INNER JOIN app.report_cards rc ON a.student_id = rc.student_id AND a.term_id = rc.term_id AND a.class_id = rc.class_id AND rc.published IS TRUE");
    		$sth->execute(array(':studentId' => $studentId));
    		$terms = $sth->fetchAll(PDO::FETCH_OBJ);

        if($terms) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $terms ));
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
              AND communications.audience_id NOT IN (3,4,7,9,10,11,13)
              AND communications.send_as_email IS TRUE
              --AND students.active IS TRUE
              AND date_trunc('year', communications.creation_date) >=  date_trunc('year', now() - interval '1 year')
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
    $sth = $db->prepare("SELECT homework_id, assigned_date, TO_CHAR(assigned_date :: DATE, 'dd/mm/yyyy') assigned_date_formated,
                        	emp_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                        	title, body, homework.post_status_id, post_status, class_name, class_subjects.class_id, due_date, TO_CHAR(due_date :: DATE, 'dd/mm/yyyy') AS due_date_formated,
                          subject_name, class_subjects.subject_id,
                        	attachment, homework.modified_date,
                          (
                            CASE
                              WHEN string_to_array(seen_by,',')::int[] @> ARRAY[:studentId::int] THEN true
                              ELSE false
                            END
                          ) AS seen
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
                        AND (homework.students ILIKE '%' || :studentId || '%' OR homework.students IS NULL)
                        ORDER BY homework.assigned_date DESC, subjects.sort_order
                ");
    $sth->execute( array(':studentId' => $studentId) );
    $homework = $sth->fetchAll(PDO::FETCH_OBJ);

    /*
    $sth2 = $db->prepare("SELECT s.sort_order, c.class_id, s.subject_id, s.subject_name
                          FROM app.subjects s
                          INNER JOIN app.classes c USING (class_cat_id)
                          INNER JOIN app.students ON c.class_id = students.current_class
                          WHERE student_id = :studentId
                          ORDER BY s.sort_order ASC");
    $sth2->execute( array(':studentId' => $studentId) );
    $subjects = $sth2->fetchAll(PDO::FETCH_OBJ);
    */
    $sth2 = $db->prepare("SELECT s.subject_name
                          FROM app.subjects s
                          INNER JOIN app.classes c USING (class_cat_id)
                          INNER JOIN app.students ON c.class_id = students.current_class
                          WHERE student_id = :studentId AND s.active IS TRUE
                          ORDER BY s.sort_order");
    $sth2->execute( array(':studentId' => $studentId) );
    $subjects = $sth2->fetchAll(PDO::FETCH_OBJ);
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

        if($homework) {
            $results = new stdClass();
            $results->homework = $homework;
            $results->subjects = $subjects;

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

$app->get('/getParticularHomework/:school/:student_id/:homeworkId', function ($school, $studentId, $homeworkId) {
    // Get paticular homewok for student

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    $sth = $db->prepare("SELECT homework_id, assigned_date,
                        	employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                        	title, body, homework.post_status_id, post_status, class_name, class_subjects.class_id, due_date, subject_name,
                        	attachment, homework.modified_date, seen_count, seen_by
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
                        AND homework.homework_id = :homeworkId
                ");
    $sth->execute( array(':studentId' => $studentId, ':homeworkId' => $homeworkId) );
    $results = $sth->fetch(PDO::FETCH_OBJ);

    $currentHomeworkCount = $results->seen_count;
    $seenByList = $results->seen_by;
    if($currentHomeworkCount === null){
      $currentHomeworkCount = 0;
    }
    $newHomeworkCount = $currentHomeworkCount + 1;

    if($seenByList === null){
      $seenByList = $studentId;
    }else{
      $seenByList .= "," . $studentId;
    }

    $sth2 = $db->prepare("UPDATE app.homework SET seen_count = :newHomeworkCount, seen_by = :seenByList WHERE homework_id = :homeworkId");
    $sth2->execute( array(':newHomeworkCount' => $newHomeworkCount, ':homeworkId' => $homeworkId, ':seenByList' => $seenByList) );

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results, 'seen_count' => $newHomeworkCount, 'seen_by' => $seenByList ));
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

$app->get('/getParticularCommunication/:school/:student_id/:commId', function ($school, $studentId, $commId) {
    // Get paticular homewok for student

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
                case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method,
                seen_count, seen_by,
                (
                  CASE
                    WHEN string_to_array(seen_by,',')::int[] @> ARRAY[:studentId::int] THEN true
                    ELSE false
                  END
                ) AS seen
              FROM app.communications
              LEFT JOIN app.students ON communications.student_id = students.student_id
              LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
              LEFT JOIN app.classes ON communications.class_id = classes.class_id
              INNER JOIN app.employees ON communications.message_from = employees.emp_id
              INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
              INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
              INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
              WHERE communications.student_id = :studentId OR communications.student_id is null
              AND communications.com_id = :commId
              AND communications.sent IS TRUE
              AND communications.post_status_id = 1
              AND (communications.class_id = any(select current_class from app.students where student_id = :studentId)
              OR communications.class_id is null)
              AND communications.audience_id NOT IN (3,4,7,9,10,11,13)
              --AND students.active IS TRUE
              AND communications.com_type_id NOT IN (6)
              AND date_trunc('year', communications.creation_date) >=  date_trunc('year', now() - interval '1 year')
              ORDER BY creation_date desc");
    $sth->execute( array(':studentId' => $studentId, ':commId' => $commId) );
    $results = $sth->fetch(PDO::FETCH_OBJ);

    $currentCommSeenCount = $results->seen_count;
    $seenByList = $results->seen_by;
    if($currentCommSeenCount === null){
      $currentCommSeenCount = 0;
    }
    $newCommSeenCount = $currentCommSeenCount + 1;

    if($seenByList === null){
      $seenByList = $studentId;
    }else{
      $seenByList .= "," . $studentId;
    }

    $sth2 = $db->prepare("UPDATE app.communications SET seen_count = :newCommSeenCount, seen_by = :seenByList WHERE com_id = :commId");
    $sth2->execute( array(':newCommSeenCount' => $newCommSeenCount, ':commId' => $commId, ':seenByList' => $seenByList) );

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results, 'seen_count' => $newCommSeenCount, 'seen_by' => $seenByList ));
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
        $sth = $db->prepare("SELECT students.student_id, first_name, middle_name, last_name, admission_number, TO_CHAR(admission_date :: DATE, 'dd/mm/yyyy') AS admission_date,
                  student_category, gender, date_text_to_date(dob) AS dob, student_image, classes.class_name,
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

$app->get('/getStudentReportCardData/:school/:studentId/:termId/:classId', function ($school, $studentId, $termId, $classId) {
    // Get the student's report card

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    $sth = $db->prepare("SELECT d.*, (SELECT value FROM app.settings WHERE name = 'Exam Calculation') AS calculation_mode,
                                (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId)) AS entity_id,
                              	(
                              		SELECT array_to_json(ARRAY_AGG(c)) FROM
                              		(
                              			SELECT exam_type_id, exam_type, array_to_json(ARRAY_AGG(row_to_json(b))) AS exam_marks
                              			FROM (
                                      SELECT student_id, exam_type_id, exam_type, exam_sort, subject_name, subject_id, mark, out_of,
                                          (SELECT grade FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS grade,
                                          (SELECT comment FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS comment,
                                          subject_sort, parent_subject_id, percentage
                                      FROM (
                                  				SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                                  						s2.subject_name, s2.subject_id, e2.mark,
                                              round((mark/cse2.grade_weight::float)*100) AS percentage,
                                  						cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                                  				FROM app.exam_marks e2
                                  				INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                                  				INNER JOIN app.exam_types et2 USING (exam_type_id)
                                  				INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                                  				INNER JOIN app.subjects s2 USING (subject_id)
                                  				WHERE e2.student_id = :studentId
                                          AND cs2.class_id = :classId
                                  				AND e2.term_id = :termId AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                                  				ORDER BY et2.sort_order ASC, s2.sort_order ASC
                                      )a
                              			) b
                              			GROUP BY exam_type_id, exam_type
                              		) AS c
                              	) AS exam_marks,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS subject_overalls
										FROM (
											SELECT *, (SELECT grade FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS grade,
                      (SELECT comment FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS subject_overalls_column,
								(
									SELECT array_to_json(ARRAY_AGG(j)) AS total_marks FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS total_marks
										FROM (
												SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of,
														(SELECT grade FROM app.grading WHERE round(sum(mark)::float/sum(out_of))*100 >= min_mark AND round(sum(mark)::float/sum(out_of))*100 <= max_mark ) AS grade
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND use_for_grading IS TRUE AND s2.active IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY student_id, exam_type_id, exam_type
										) h
									) AS j
								) AS totals,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
											(
												SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
												FROM (
													SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
														(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                            (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
													FROM (
														SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
														FROM(
															SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																	s2.subject_name, s2.subject_id, e2.mark,
																	cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																	(
																		SELECT COUNT(DISTINCT exam_type_id)
																		FROM app.class_subject_exams
																		INNER JOIN app.exam_marks USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects USING (class_subject_id)
																		WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_id = :classId
																	) AS class_exam_count
															FROM app.exam_marks e2
															INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
															INNER JOIN app.exam_types et2 USING (exam_type_id)
															INNER JOIN app.class_subjects cs2 USING (class_subject_id)
															INNER JOIN app.subjects s2 USING (subject_id)
															WHERE e2.student_id = :studentId AND s2.active IS TRUE AND s2.use_for_grading IS TRUE
															AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
															ORDER BY et2.sort_order ASC, s2.sort_order ASC
														)f
														GROUP BY subject_id, subject_name, class_exam_count
													)g
												) h
											) AS last_term_marks_and_grade
										FROM (
                      SELECT sum(average) || '/' || sum(out_of)::float AS overall_mark, round((sum(average)::float/sum(out_of)::float)*100) AS percentage,
												(SELECT grade FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS overall_grade,
                        (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS principal_comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average, out_of
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId AND s2.active IS TRUE
                          AND cs2.class_id = :classId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count, out_of
											)g
										) h
									) AS j
								) AS overall_marks_and_grade,
                (
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
											(
												SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
												FROM (
													SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
														(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                            (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
													FROM (
														SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
														FROM(
															SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																	s2.subject_name, s2.subject_id, e2.mark,
																	cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																	(
																		SELECT COUNT(DISTINCT exam_type_id)
																		FROM app.class_subject_exams
																		INNER JOIN app.exam_marks USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects USING (class_subject_id)
																		WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_id = :classId
                                    AND mark IS NOT NULL
																		AND class_subject_exams.exam_type_id = (
																												  SELECT cc.exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																															AND cs.class_id = :classId
                                                              AND mark IS NOT NULL
																															ORDER BY em.creation_date DESC
																														)aa
																													)bb ORDER BY creation_date DESC LIMIT 1
																												  )cc
																												)
																	) AS class_exam_count
															FROM app.exam_marks e2
															INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
															INNER JOIN app.exam_types et2 USING (exam_type_id)
															INNER JOIN app.class_subjects cs2 USING (class_subject_id)
															INNER JOIN app.subjects s2 USING (subject_id)
															WHERE e2.student_id = :studentId AND s2.active IS TRUE and s2.use_for_grading IS TRUE
															AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
															AND cse2.exam_type_id = (
																					  SELECT cc.exam_type_id FROM (
																						SELECT * FROM (
																							SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																								SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																								FROM app.exam_marks em
																								INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																								INNER JOIN app.class_subjects cs USING (class_subject_id)
																								INNER JOIN app.exam_types et USING (exam_type_id)
																								WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																								AND cs.class_id = :classId
                                                AND mark IS NOT NULL
																								ORDER BY em.creation_date DESC
																							)aa
																						)bb ORDER BY creation_date DESC LIMIT 1
																					  )cc
																					)
															ORDER BY et2.sort_order ASC, s2.sort_order ASC
														)f
														GROUP BY subject_id, subject_name, class_exam_count
													)g
												) h
											) AS last_term_marks_and_grade
										FROM (
											SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
												(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                        (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
                                AND mark IS NOT NULL
																AND class_subject_exams.exam_type_id = (
																										  SELECT cc.exam_type_id FROM (
																											SELECT * FROM (
																												SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																													SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																													FROM app.exam_marks em
																													INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																													INNER JOIN app.class_subjects cs USING (class_subject_id)
																													INNER JOIN app.exam_types et USING (exam_type_id)
																													WHERE em.term_id = :termId
																													AND cs.class_id = :classId
                                                          AND mark IS NOT NULL
																													ORDER BY em.creation_date DESC
																												)aa
																											)bb ORDER BY creation_date DESC LIMIT 1
																										  )cc
																										)
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId AND s2.active IS TRUE
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													AND cse2.exam_type_id = (
																			  SELECT cc.exam_type_id FROM (
																				SELECT * FROM (
																					SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																						SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																						FROM app.exam_marks em
																						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																						INNER JOIN app.class_subjects cs USING (class_subject_id)
																						INNER JOIN app.exam_types et USING (exam_type_id)
																						WHERE em.term_id = :termId
																						AND cs.class_id = :classId
                                            AND mark IS NOT NULL
																						ORDER BY em.creation_date DESC
																					)aa
																				)bb ORDER BY creation_date DESC LIMIT 1
																			  )cc
																			)
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS overall_marks_and_grade_by_last_exam,
								(
									SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
									(
											SELECT row_to_json(i) AS this_term_position,
												(
													SELECT row_to_json(j) AS last_term_position FROM
													(
														SELECT * FROM (
															SELECT
																student_id, total_mark, total_grade_weight,
																round((total_mark::float/total_grade_weight::float)*100) as percentage,
																rank() over w as position, position_out_of
															FROM (
																SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
																	round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
																	position_out_of
																FROM(
																	SELECT exam_marks.student_id,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					mark
																				end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					grade_weight
																				end),0) as total_grade_weight,
																		(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																				INNER JOIN app.class_subjects cs USING (class_subject_id)
																				INNER JOIN app.students s USING (student_id)
																				WHERE cs.class_id = :classId
																				AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																				AND s.active IS TRUE
																		) as position_out_of,
																		(
																									SELECT COUNT(DISTINCT exam_type_id)
																									FROM app.class_subject_exams
																									INNER JOIN app.exam_marks USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects USING (class_subject_id)
																									WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND class_id = :classId
																								) AS class_exam_count

																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types USING (exam_type_id)
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects
																	ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students USING (student_id)
																	WHERE class_subjects.class_id = :classId
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND students.active is true
																	AND mark IS NOT NULL

																	GROUP BY exam_marks.student_id
																)c
															) a
															WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
														)q WHERE student_id = :studentId
													)j
												) AS last_term_position
											FROM
											(
												SELECT * FROM (
													SELECT
														student_id, total_mark, total_grade_weight,
														round((total_mark::float/total_grade_weight::float)*100) as percentage,
														rank() over w as position, position_out_of
													FROM (
														SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
															round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
															position_out_of
														FROM(
															SELECT exam_marks.student_id,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			mark
																		end),0) as total_mark,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			grade_weight
																		end),0) as total_grade_weight,
																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.students s USING (student_id)
																		WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE
																) as position_out_of,
																(
																							SELECT COUNT(DISTINCT exam_type_id)
																							FROM app.class_subject_exams
																							INNER JOIN app.exam_marks USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects USING (class_subject_id)
																							WHERE term_id = :termId
																							AND class_id = :classId
																						) AS class_exam_count

															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types USING (exam_type_id)
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects
															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students USING (student_id)
															WHERE class_subjects.class_id = :classId
															AND term_id = :termId
															AND subjects.parent_subject_id is null
															AND subjects.use_for_grading is true
															AND students.active is true
															AND mark IS NOT NULL

															GROUP BY exam_marks.student_id
														)c
													) a
													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
												)p WHERE student_id = :studentId
											)i

									)l
								) AS positions,
                (
									SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
									(
											SELECT row_to_json(i) AS this_term_position,
												(
													SELECT row_to_json(j) AS last_term_position FROM
													(
														SELECT * FROM (
															SELECT
																student_id, total_mark, total_grade_weight,
																round((total_mark::float/total_grade_weight::float)*100) as percentage,
																rank() over w as position, position_out_of
															FROM (
																SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
																	round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
																	position_out_of
																FROM(
																	SELECT exam_marks.student_id,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					mark
																				end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					grade_weight
																				end),0) as total_grade_weight,
																		(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																				INNER JOIN app.class_subjects cs USING (class_subject_id)
																				INNER JOIN app.students s USING (student_id)
																				WHERE cs.class_id = :classId
																				AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                        AND cse.exam_type_id = (
                                          SELECT exam_type_id FROM (
                                          	SELECT * FROM (
                                          		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                          			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                          			FROM app.exam_marks em
                                          			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                          			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                          			INNER JOIN app.exam_types et USING (exam_type_id)
                                          			WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                          			AND cs.class_id = :classId
                                                AND mark IS NOT NULL
                                          			ORDER BY em.creation_date DESC
                                          		)a
                                          	)b ORDER BY creation_date DESC LIMIT 1
                                          )c
                                        )
																				AND s.active IS TRUE
																		) as position_out_of,
																		(
																									SELECT COUNT(DISTINCT exam_type_id)
																									FROM app.class_subject_exams
																									INNER JOIN app.exam_marks USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects USING (class_subject_id)
																									WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND class_id = :classId
                                                  AND mark IS NOT NULL
                                                  AND class_subject_exams.exam_type_id = (
                                                    SELECT exam_type_id FROM (
                                                    	SELECT * FROM (
                                                    		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                    			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                    			FROM app.exam_marks em
                                                    			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                    			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                    			INNER JOIN app.exam_types et USING (exam_type_id)
                                                    			WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                                    			AND cs.class_id = :classId
                                                          AND mark IS NOT NULL
                                                    			ORDER BY em.creation_date DESC
                                                    		)a
                                                    	)b ORDER BY creation_date DESC LIMIT 1
                                                    )c
                                                  )
																								) AS class_exam_count

																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types USING (exam_type_id)
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects
																	ON class_subjects.subject_id = subjects.subject_id
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students USING (student_id)
																	WHERE class_subjects.class_id = :classId AND subjects.active IS TRUE
                                  AND mark IS NOT NULL
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND students.active is true
                                  AND class_subject_exams.exam_type_id = (
                                    SELECT exam_type_id FROM (
                                    	SELECT * FROM (
                                    		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                    			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                    			FROM app.exam_marks em
                                    			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                    			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                    			INNER JOIN app.exam_types et USING (exam_type_id)
                                    			WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                    			AND cs.class_id = :classId
                                          AND mark IS NOT NULL
                                    			ORDER BY em.creation_date DESC
                                    		)a
                                    	)b ORDER BY creation_date DESC LIMIT 1
                                    )c
                                  )
																	AND mark IS NOT NULL

																	GROUP BY exam_marks.student_id
																)c
															) a
															WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
														)q WHERE student_id = :studentId
													)j
												) AS last_term_position
											FROM
											(
												SELECT * FROM (
													SELECT
														student_id, total_mark, total_grade_weight,
														round((total_mark::float/total_grade_weight::float)*100) as percentage,
														rank() over w as position, position_out_of
													FROM (
														SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
															round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
															position_out_of
														FROM(
															SELECT exam_marks.student_id,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			mark
																		end),0) as total_mark,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			grade_weight
																		end),0) as total_grade_weight,
																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.students s USING (student_id)
																		WHERE cs.class_id = :classId
                                    AND mark IS NOT NULL
                                    AND em.term_id = :termId
                                    AND s.active IS TRUE
                                    AND cse.exam_type_id = (
                                      SELECT exam_type_id FROM (
                                      	SELECT * FROM (
                                      		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                      			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                      			FROM app.exam_marks em
                                      			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                      			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                      			INNER JOIN app.exam_types et USING (exam_type_id)
                                      			WHERE em.term_id = :termId
                                      			AND cs.class_id = :classId
                                            AND mark IS NOT NULL
                                      			ORDER BY em.creation_date DESC
                                      		)a
                                      	)b ORDER BY creation_date DESC LIMIT 1
                                      )c
                                    )
																) as position_out_of,
																(
																							SELECT COUNT(DISTINCT exam_type_id)
																							FROM app.class_subject_exams
																							INNER JOIN app.exam_marks USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects USING (class_subject_id)
																							WHERE term_id = :termId
																							AND class_id = :classId
                                              AND mark IS NOT NULL
                                              AND class_subject_exams.exam_type_id = (
                                                SELECT exam_type_id FROM (
                                                	SELECT * FROM (
                                                		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                			FROM app.exam_marks em
                                                			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                			INNER JOIN app.exam_types et USING (exam_type_id)
                                                			WHERE em.term_id = :termId
                                                			AND cs.class_id = :classId
                                                      AND mark IS NOT NULL
                                                			ORDER BY em.creation_date DESC
                                                		)a
                                                	)b ORDER BY creation_date DESC LIMIT 1
                                                )c
                                              )
																						) AS class_exam_count

															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types USING (exam_type_id)
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects
															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students USING (student_id)
															WHERE class_subjects.class_id = :classId
															AND term_id = :termId
                              AND mark IS NOT NULL
															AND subjects.parent_subject_id is null
															AND subjects.use_for_grading is true
															AND students.active is true
                              AND class_subject_exams.exam_type_id = (
                                SELECT exam_type_id FROM (
                                	SELECT * FROM (
                                		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                			FROM app.exam_marks em
                                			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                			INNER JOIN app.exam_types et USING (exam_type_id)
                                			WHERE em.term_id = :termId
                                			AND cs.class_id = :classId
                                      AND mark IS NOT NULL
                                			ORDER BY em.creation_date DESC
                                		)a
                                	)b ORDER BY creation_date DESC LIMIT 1
                                )c
                              )
															AND mark IS NOT NULL

															GROUP BY exam_marks.student_id
														)c
													) a
													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
												)p WHERE student_id = :studentId
											)i

									)l
								) AS positions_by_last_exam,
								(
								  SELECT array_to_json(ARRAY_AGG(row_to_json(p))) AS subjects FROM
								  (
									SELECT DISTINCT ON (subject_id) subject_id, subject_name, teacher_id, sort_order, parent_subject_id,
										e.initials
									FROM app.subjects s
									INNER JOIN app.class_subjects cs USING (subject_id)
									INNER JOIN app.class_subject_exams cse USING (class_subject_id)
									INNER JOIN app.exam_marks em USING (class_sub_exam_id)
									LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
									WHERE s.active IS TRUE
									AND cs.class_id = :classId
                  AND em.term_id = :termId
									AND s.use_for_grading IS TRUE
								  )p
								) AS subjects_column,
                (
									SELECT report_data::json FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								) AS report_card_comments,
                (
									SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								) AS report_card_type,
                (
                  SELECT CASE
                        WHEN report_card_type = 'Playgroup'
                        THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
                      END AS report_card_data
                  FROM (
                    SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
                  )a
								) AS playgroup_report_card,
                (
                  SELECT CASE
                        WHEN report_card_type = 'Kindergarten'
                        THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
                      END AS report_card_data
                  FROM (
                    SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
                  )a
								) AS kindergarten_report_card
                              FROM (
                              	SELECT DISTINCT student_id, student_name, term_id, term_name, closing_date, year, next_term_begins, class_name,
                              		admission_number
                              	FROM(
                              		SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                              				em.term_id, t.term_name, t.end_date AS closing_date, date_part('year', t.start_date) AS year,
                              				(SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = :termId) LIMIT 1) AS next_term_begins,
                              				(SELECT class_name FROM app.classes WHERE class_id = :classId) AS class_name, s.admission_number
                              		FROM app.students s
                              		LEFT JOIN app.report_cards em USING (student_id) /* this is not a mistake */
                              		INNER JOIN app.terms t USING (term_id)
                              		WHERE em.term_id = :termId
                                  AND em.class_id = :classId
                              		AND s.student_id = :studentId
                              	)a
                              )d");

    $sth->execute( array(':studentId' => $studentId, ':termId' => $termId, ':classId' => $classId) );
    $results = $sth->fetch(PDO::FETCH_OBJ);

    $results->exam_marks = json_decode($results->exam_marks);
    $results->subject_overalls_column = json_decode($results->subject_overalls_column);
    $results->totals = json_decode($results->totals);
    $results->overall_marks_and_grade = json_decode($results->overall_marks_and_grade);
	$results->overall_marks_and_grade_by_last_exam = json_decode($results->overall_marks_and_grade_by_last_exam);
    $results->positions = json_decode($results->positions);
    $results->positions_by_last_exam = json_decode($results->positions_by_last_exam);
    $results->subjects_column = json_decode($results->subjects_column);
    $results->report_card_comments = json_decode($results->report_card_comments);
    $results->report_card_comments = ($results->report_card_comments->comments == null ? null : $results->report_card_comments->comments);
    $results->playgroup_report_card = json_decode($results->playgroup_report_card);
    $results->playgroup_report_card = ($results->playgroup_report_card == null ? null : $results->playgroup_report_card->subjects);
    $results->kindergarten_report_card = json_decode($results->kindergarten_report_card);
    $results->kindergarten_report_card = ($results->kindergarten_report_card == null ? null : $results->kindergarten_report_card->subjects);

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app viewing report card')");
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

$app->get('/getStudentReportCardDataHigh/:school/:studentId/:termId/:classId', function ($school, $studentId, $termId, $classId) {
    // Get the student's report card

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    $sth = $db->prepare("SELECT d.*, (SELECT value FROM app.settings WHERE name = 'Exam Calculation') AS calculation_mode,
                                (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId)) AS entity_id,
                              	(
                              		SELECT array_to_json(ARRAY_AGG(c)) FROM
                              		(
                              			SELECT exam_type_id, exam_type, array_to_json(ARRAY_AGG(row_to_json(b))) AS exam_marks
                              			FROM (
                                      SELECT student_id, exam_type_id, exam_type, exam_sort, subject_name, subject_id, mark, out_of,
                                          (SELECT grade FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS grade,
                                          (SELECT comment FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS comment,
                                          subject_sort, parent_subject_id, percentage
                                      FROM (
                                  				SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                                  						s2.subject_name, s2.subject_id, e2.mark,
                                              round((mark/cse2.grade_weight::float)*100) AS percentage,
                                  						cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                                  				FROM app.exam_marks e2
                                  				INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                                  				INNER JOIN app.exam_types et2 USING (exam_type_id)
                                  				INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                                  				INNER JOIN app.subjects s2 USING (subject_id)
                                  				WHERE e2.student_id = :studentId
                                          AND cs2.class_id = :classId
                                  				AND e2.term_id = :termId AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                                  				ORDER BY et2.sort_order ASC, s2.sort_order ASC
                                      )a
                              			) b
                              			GROUP BY exam_type_id, exam_type
                              		) AS c
                              	) AS exam_marks,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS subject_overalls
										FROM (
											SELECT *, (SELECT grade FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS grade,
                      (SELECT comment FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS subject_overalls_column,
								(
									SELECT array_to_json(ARRAY_AGG(j)) AS total_marks FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS total_marks
										FROM (
												SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of,
														(SELECT grade FROM app.grading WHERE round(sum(mark)::float/sum(out_of))*100 >= min_mark AND round(sum(mark)::float/sum(out_of))*100 <= max_mark ) AS grade
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND use_for_grading IS TRUE AND s2.active IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY student_id, exam_type_id, exam_type
										) h
									) AS j
								) AS totals,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
											(
												SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
												FROM (
													SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
														(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                            (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
													FROM (
														SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
														FROM(
															SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																	s2.subject_name, s2.subject_id, e2.mark,
																	cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																	(
																		SELECT COUNT(DISTINCT exam_type_id)
																		FROM app.class_subject_exams
																		INNER JOIN app.exam_marks USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects USING (class_subject_id)
																		WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_id = :classId
																	) AS class_exam_count
															FROM app.exam_marks e2
															INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
															INNER JOIN app.exam_types et2 USING (exam_type_id)
															INNER JOIN app.class_subjects cs2 USING (class_subject_id)
															INNER JOIN app.subjects s2 USING (subject_id)
															WHERE e2.student_id = :studentId AND s2.active IS TRUE AND s2.use_for_grading IS TRUE
															AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
															ORDER BY et2.sort_order ASC, s2.sort_order ASC
														)f
														GROUP BY subject_id, subject_name, class_exam_count
													)g
												) h
											) AS last_term_marks_and_grade
										FROM (
                      SELECT sum(average) || '/' || (sum(out_of)::float) AS overall_mark, round((sum(average)::float/(sum(out_of)::float))*100) AS percentage,
												(SELECT grade FROM app.grading WHERE round((sum(average)::float/(sum(out_of)::float))*100) >= min_mark AND round((sum(average)::float/(sum(out_of)::float))*100) <= max_mark) AS overall_grade,
                        (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/(sum(out_of)::float))*100) >= min_mark AND round((sum(average)::float/(sum(out_of)::float))*100) <= max_mark) AS principal_comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average, out_of
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId AND s2.active IS TRUE
                          AND cs2.class_id = :classId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count, out_of
											)g

											) h
									) AS j
								) AS overall_marks_and_grade,
                (
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
											(
												SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
												FROM (
													SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
														(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                            (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
													FROM (
														SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
														FROM(
															SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																	s2.subject_name, s2.subject_id, e2.mark,
																	cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																	(
																		SELECT COUNT(DISTINCT exam_type_id)
																		FROM app.class_subject_exams
																		INNER JOIN app.exam_marks USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects USING (class_subject_id)
																		WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_id = :classId
                                    AND mark IS NOT NULL
																		AND class_subject_exams.exam_type_id = (
																												  SELECT cc.exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																															AND cs.class_id = :classId
                                                              AND mark IS NOT NULL
																															ORDER BY em.creation_date DESC
																														)aa
																													)bb ORDER BY creation_date DESC LIMIT 1
																												  )cc
																												)
																	) AS class_exam_count
															FROM app.exam_marks e2
															INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
															INNER JOIN app.exam_types et2 USING (exam_type_id)
															INNER JOIN app.class_subjects cs2 USING (class_subject_id)
															INNER JOIN app.subjects s2 USING (subject_id)
															WHERE e2.student_id = :studentId AND s2.active IS TRUE and s2.use_for_grading IS TRUE
															AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
															AND cse2.exam_type_id = (
																					  SELECT cc.exam_type_id FROM (
																						SELECT * FROM (
																							SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																								SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																								FROM app.exam_marks em
																								INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																								INNER JOIN app.class_subjects cs USING (class_subject_id)
																								INNER JOIN app.exam_types et USING (exam_type_id)
																								WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																								AND cs.class_id = :classId
                                                AND mark IS NOT NULL
																								ORDER BY em.creation_date DESC
																							)aa
																						)bb ORDER BY creation_date DESC LIMIT 1
																					  )cc
																					)
															ORDER BY et2.sort_order ASC, s2.sort_order ASC
														)f
														GROUP BY subject_id, subject_name, class_exam_count
													)g
												) h
											) AS last_term_marks_and_grade
										FROM (
											SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
												(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                        (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
                                AND mark IS NOT NULL
																AND class_subject_exams.exam_type_id = (
																										  SELECT cc.exam_type_id FROM (
																											SELECT * FROM (
																												SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																													SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																													FROM app.exam_marks em
																													INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																													INNER JOIN app.class_subjects cs USING (class_subject_id)
																													INNER JOIN app.exam_types et USING (exam_type_id)
																													WHERE em.term_id = :termId
																													AND cs.class_id = :classId
                                                          AND mark IS NOT NULL
																													ORDER BY em.creation_date DESC
																												)aa
																											)bb ORDER BY creation_date DESC LIMIT 1
																										  )cc
																										)
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId AND s2.active IS TRUE
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													AND cse2.exam_type_id = (
																			  SELECT cc.exam_type_id FROM (
																				SELECT * FROM (
																					SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																						SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																						FROM app.exam_marks em
																						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																						INNER JOIN app.class_subjects cs USING (class_subject_id)
																						INNER JOIN app.exam_types et USING (exam_type_id)
																						WHERE em.term_id = :termId
																						AND cs.class_id = :classId
                                            AND mark IS NOT NULL
																						ORDER BY em.creation_date DESC
																					)aa
																				)bb ORDER BY creation_date DESC LIMIT 1
																			  )cc
																			)
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS overall_marks_and_grade_by_last_exam,
								(
									SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
									(
											SELECT row_to_json(i) AS this_term_position,
												(
													SELECT row_to_json(j) AS last_term_position FROM
													(
														SELECT * FROM (
															SELECT
																student_id, total_mark, total_grade_weight,
																round((total_mark::float/total_grade_weight::float)*100) as percentage,
																rank() over w as position, position_out_of
															FROM (
																SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
																	round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
																	position_out_of
																FROM(
																	SELECT exam_marks.student_id,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					mark
																				end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					grade_weight
																				end),0) as total_grade_weight,
																		(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																				INNER JOIN app.class_subjects cs USING (class_subject_id)
																				INNER JOIN app.students s USING (student_id)
																				WHERE cs.class_id = :classId
																				AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																				AND s.active IS TRUE
																		) as position_out_of,
																		(
																									SELECT COUNT(DISTINCT exam_type_id)
																									FROM app.class_subject_exams
																									INNER JOIN app.exam_marks USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects USING (class_subject_id)
																									WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND class_id = :classId
																								) AS class_exam_count

																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types USING (exam_type_id)
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects
																	ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students USING (student_id)
																	WHERE class_subjects.class_id = :classId
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND students.active is true
																	AND mark IS NOT NULL

																	GROUP BY exam_marks.student_id
																)c
															) a
															WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
														)q WHERE student_id = :studentId
													)j
												) AS last_term_position
											FROM
											(
												SELECT * FROM (
													SELECT
														student_id, total_mark, total_grade_weight,
														round((total_mark::float/total_grade_weight::float)*100) as percentage,
														rank() over w as position, position_out_of
													FROM (
														SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
															round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
															position_out_of
														FROM(
															SELECT exam_marks.student_id,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			mark
																		end),0) as total_mark,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			grade_weight
																		end),0) as total_grade_weight,
																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.students s USING (student_id)
																		WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE
																) as position_out_of,
																(
																							SELECT COUNT(DISTINCT exam_type_id)
																							FROM app.class_subject_exams
																							INNER JOIN app.exam_marks USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects USING (class_subject_id)
																							WHERE term_id = :termId
																							AND class_id = :classId
																						) AS class_exam_count

															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types USING (exam_type_id)
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects
															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students USING (student_id)
															WHERE class_subjects.class_id = :classId
															AND term_id = :termId
															AND subjects.parent_subject_id is null
															AND subjects.use_for_grading is true
															AND students.active is true
															AND mark IS NOT NULL

															GROUP BY exam_marks.student_id
														)c
													) a
													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
												)p WHERE student_id = :studentId
											)i

									)l
								) AS positions,
                (
									SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
									(
											SELECT row_to_json(i) AS this_term_position,
												(
													SELECT row_to_json(j) AS last_term_position FROM
													(
														SELECT * FROM (
															SELECT
																student_id, total_mark, total_grade_weight,
																round((total_mark::float/total_grade_weight::float)*100) as percentage,
																rank() over w as position, position_out_of
															FROM (
																SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
																	round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
																	position_out_of
																FROM(
																	SELECT exam_marks.student_id,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					mark
																				end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					grade_weight
																				end),0) as total_grade_weight,
																		(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																				INNER JOIN app.class_subjects cs USING (class_subject_id)
																				INNER JOIN app.students s USING (student_id)
																				WHERE cs.class_id = :classId
																				AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                        AND cse.exam_type_id = (
                                          SELECT exam_type_id FROM (
                                          	SELECT * FROM (
                                          		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                          			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                          			FROM app.exam_marks em
                                          			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                          			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                          			INNER JOIN app.exam_types et USING (exam_type_id)
                                          			WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                          			AND cs.class_id = :classId
                                                AND mark IS NOT NULL
                                          			ORDER BY em.creation_date DESC
                                          		)a
                                          	)b ORDER BY creation_date DESC LIMIT 1
                                          )c
                                        )
																				AND s.active IS TRUE
																		) as position_out_of,
																		(
																									SELECT COUNT(DISTINCT exam_type_id)
																									FROM app.class_subject_exams
																									INNER JOIN app.exam_marks USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects USING (class_subject_id)
																									WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND class_id = :classId
                                                  AND mark IS NOT NULL
                                                  AND class_subject_exams.exam_type_id = (
                                                    SELECT exam_type_id FROM (
                                                    	SELECT * FROM (
                                                    		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                    			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                    			FROM app.exam_marks em
                                                    			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                    			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                    			INNER JOIN app.exam_types et USING (exam_type_id)
                                                    			WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                                    			AND cs.class_id = :classId
                                                          AND mark IS NOT NULL
                                                    			ORDER BY em.creation_date DESC
                                                    		)a
                                                    	)b ORDER BY creation_date DESC LIMIT 1
                                                    )c
                                                  )
																								) AS class_exam_count

																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types USING (exam_type_id)
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects
																	ON class_subjects.subject_id = subjects.subject_id
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students USING (student_id)
																	WHERE class_subjects.class_id = :classId AND subjects.active IS TRUE
                                  AND mark IS NOT NULL
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND students.active is true
                                  AND class_subject_exams.exam_type_id = (
                                    SELECT exam_type_id FROM (
                                    	SELECT * FROM (
                                    		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                    			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                    			FROM app.exam_marks em
                                    			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                    			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                    			INNER JOIN app.exam_types et USING (exam_type_id)
                                    			WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                    			AND cs.class_id = :classId
                                          AND mark IS NOT NULL
                                    			ORDER BY em.creation_date DESC
                                    		)a
                                    	)b ORDER BY creation_date DESC LIMIT 1
                                    )c
                                  )
																	AND mark IS NOT NULL

																	GROUP BY exam_marks.student_id
																)c
															) a
															WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
														)q WHERE student_id = :studentId
													)j
												) AS last_term_position
											FROM
											(
												SELECT * FROM (
													SELECT
														student_id, total_mark, total_grade_weight,
														round((total_mark::float/total_grade_weight::float)*100) as percentage,
														rank() over w as position, position_out_of
													FROM (
														SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
															round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
															position_out_of
														FROM(
															SELECT exam_marks.student_id,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			mark
																		end),0) as total_mark,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			grade_weight
																		end),0) as total_grade_weight,
																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.students s USING (student_id)
																		WHERE cs.class_id = :classId
                                    AND mark IS NOT NULL
                                    AND em.term_id = :termId
                                    AND s.active IS TRUE
                                    AND cse.exam_type_id = (
                                      SELECT exam_type_id FROM (
                                      	SELECT * FROM (
                                      		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                      			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                      			FROM app.exam_marks em
                                      			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                      			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                      			INNER JOIN app.exam_types et USING (exam_type_id)
                                      			WHERE em.term_id = :termId
                                      			AND cs.class_id = :classId
                                            AND mark IS NOT NULL
                                      			ORDER BY em.creation_date DESC
                                      		)a
                                      	)b ORDER BY creation_date DESC LIMIT 1
                                      )c
                                    )
																) as position_out_of,
																(
																							SELECT COUNT(DISTINCT exam_type_id)
																							FROM app.class_subject_exams
																							INNER JOIN app.exam_marks USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects USING (class_subject_id)
																							WHERE term_id = :termId
																							AND class_id = :classId
                                              AND mark IS NOT NULL
                                              AND class_subject_exams.exam_type_id = (
                                                SELECT exam_type_id FROM (
                                                	SELECT * FROM (
                                                		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                			FROM app.exam_marks em
                                                			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                			INNER JOIN app.exam_types et USING (exam_type_id)
                                                			WHERE em.term_id = :termId
                                                			AND cs.class_id = :classId
                                                      AND mark IS NOT NULL
                                                			ORDER BY em.creation_date DESC
                                                		)a
                                                	)b ORDER BY creation_date DESC LIMIT 1
                                                )c
                                              )
																						) AS class_exam_count

															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types USING (exam_type_id)
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects
															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students USING (student_id)
															WHERE class_subjects.class_id = :classId
															AND term_id = :termId
                              AND mark IS NOT NULL
															AND subjects.parent_subject_id is null
															AND subjects.use_for_grading is true
															AND students.active is true
                              AND class_subject_exams.exam_type_id = (
                                SELECT exam_type_id FROM (
                                	SELECT * FROM (
                                		SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                			SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                			FROM app.exam_marks em
                                			INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                			INNER JOIN app.class_subjects cs USING (class_subject_id)
                                			INNER JOIN app.exam_types et USING (exam_type_id)
                                			WHERE em.term_id = :termId
                                			AND cs.class_id = :classId
                                      AND mark IS NOT NULL
                                			ORDER BY em.creation_date DESC
                                		)a
                                	)b ORDER BY creation_date DESC LIMIT 1
                                )c
                              )
															AND mark IS NOT NULL

															GROUP BY exam_marks.student_id
														)c
													) a
													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
												)p WHERE student_id = :studentId
											)i

									)l
								) AS positions_by_last_exam,
								(
								  SELECT array_to_json(ARRAY_AGG(row_to_json(p))) AS subjects FROM
								  (
									SELECT DISTINCT ON (subject_id) subject_id, subject_name, teacher_id, sort_order, parent_subject_id,
										e.initials
									FROM app.subjects s
									INNER JOIN app.class_subjects cs USING (subject_id)
									INNER JOIN app.class_subject_exams cse USING (class_subject_id)
									INNER JOIN app.exam_marks em USING (class_sub_exam_id)
									LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
									WHERE s.active IS TRUE
									AND cs.class_id = :classId
                  AND em.term_id = :termId
									AND s.use_for_grading IS TRUE
								  )p
								) AS subjects_column,
                (
									SELECT report_data::json FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								) AS report_card_comments,
                (
									SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								) AS report_card_type,
                (
                  SELECT CASE
                        WHEN report_card_type = 'Playgroup'
                        THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
                      END AS report_card_data
                  FROM (
                    SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
                  )a
								) AS playgroup_report_card,
                (
                  SELECT CASE
                        WHEN report_card_type = 'Kindergarten'
                        THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
                      END AS report_card_data
                  FROM (
                    SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
                  )a
								) AS kindergarten_report_card
                              FROM (
                              	SELECT DISTINCT student_id, student_name, term_id, term_name, closing_date, year, next_term_begins, class_name,
                              		admission_number
                              	FROM(
                              		SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                              				em.term_id, t.term_name, t.end_date AS closing_date, date_part('year', t.start_date) AS year,
                              				(SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = :termId) LIMIT 1) AS next_term_begins,
                              				(SELECT class_name FROM app.classes WHERE class_id = :classId) AS class_name, s.admission_number
                              		FROM app.students s
                              		LEFT JOIN app.report_cards em USING (student_id) /* this is not a mistake */
                              		INNER JOIN app.terms t USING (term_id)
                              		WHERE em.term_id = :termId
                                  AND em.class_id = :classId
                              		AND s.student_id = :studentId
                              	)a
                              )d");

    $sth->execute( array(':studentId' => $studentId, ':termId' => $termId, ':classId' => $classId) );
    $results = $sth->fetch(PDO::FETCH_OBJ);

    $results->exam_marks = json_decode($results->exam_marks);
    $results->subject_overalls_column = json_decode($results->subject_overalls_column);
    $results->totals = json_decode($results->totals);
    $results->overall_marks_and_grade = json_decode($results->overall_marks_and_grade);
	$results->overall_marks_and_grade_by_last_exam = json_decode($results->overall_marks_and_grade_by_last_exam);
    $results->positions = json_decode($results->positions);
    $results->positions_by_last_exam = json_decode($results->positions_by_last_exam);
    $results->subjects_column = json_decode($results->subjects_column);
    $results->report_card_comments = json_decode($results->report_card_comments);
    $results->report_card_comments = ($results->report_card_comments->comments == null ? null : $results->report_card_comments->comments);
    $results->playgroup_report_card = json_decode($results->playgroup_report_card);
    $results->playgroup_report_card = ($results->playgroup_report_card == null ? null : $results->playgroup_report_card->subjects);
    $results->kindergarten_report_card = json_decode($results->kindergarten_report_card);
    $results->kindergarten_report_card = ($results->kindergarten_report_card == null ? null : $results->kindergarten_report_card->subjects);

        // traffic analysis start
				$mistrafficdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$school','parent-app viewing report card')");
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

$app->get('/getAllStudentReportCardData/:school/:studentId', function ($school, $studentId) {
  // Get the student's report card

    $app = \Slim\Slim::getInstance();

      try
      {
      $db = setDBConnection($school);
      $sth = $db->prepare("SELECT d.*, (SELECT value FROM app.settings WHERE name = 'Exam Calculation') AS calculation_mode,
    	(
    		SELECT array_to_json(ARRAY_AGG(c)) FROM
    		(
    			SELECT exam_type_id, exam_type, array_to_json(ARRAY_AGG(row_to_json(b))) AS exam_marks
    			FROM (
            SELECT student_id, exam_type_id, exam_type, exam_sort, subject_name, subject_id, mark,
              (SELECT grade FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS grade,
              (SELECT comment FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS comment,
              out_of, subject_sort, parent_subject_id, percentage
            FROM (
        				SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
        					s2.subject_name, s2.subject_id, e2.mark,
        					round((mark/cse2.grade_weight::float)*100) AS percentage,
        					cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
        				FROM app.exam_marks e2
        				INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
        				INNER JOIN app.exam_types et2 USING (exam_type_id)
        				INNER JOIN app.class_subjects cs2 USING (class_subject_id)
        				INNER JOIN app.subjects s2 USING (subject_id)
        				WHERE e2.student_id = d.student_id
                AND cs2.class_id = d.class_id
        				AND e2.term_id = d.term_id AND s2.use_for_grading IS TRUE
        				ORDER BY et2.sort_order ASC, s2.sort_order ASC
            )a
    			) b
    			GROUP BY exam_type_id, exam_type
    		) AS c
    	) AS exam_marks,
    	(
    		SELECT array_to_json(ARRAY_AGG(j)) FROM
    		(
    			SELECT ARRAY_AGG(row_to_json(h)) AS subject_overalls
    			FROM (
    				SELECT *, (SELECT grade FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS grade
    				FROM (
    					SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
    					FROM(
    						SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    							s2.subject_name, s2.subject_id, e2.mark,
    							cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    							(
    								SELECT COUNT(DISTINCT exam_type_id)
    								FROM app.class_subject_exams
    								INNER JOIN app.exam_marks USING (class_sub_exam_id)
    								INNER JOIN app.class_subjects USING (class_subject_id)
    								WHERE term_id = d.term_id
    								AND class_id = d.class_id
    							) AS class_exam_count
    						FROM app.exam_marks e2
    						INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    						INNER JOIN app.exam_types et2 USING (exam_type_id)
    						INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    						INNER JOIN app.subjects s2 USING (subject_id)
    						WHERE e2.student_id = d.student_id
                AND cs2.class_id = d.class_id
    						AND e2.term_id = d.term_id AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
    						ORDER BY et2.sort_order ASC, s2.sort_order ASC
    					)f
    					GROUP BY subject_id, subject_name, class_exam_count
    				)g
    			) h
    		) AS j
    	) AS subject_overalls_column,
    	(
    		SELECT array_to_json(ARRAY_AGG(j)) AS total_marks FROM
    		(
    			SELECT ARRAY_AGG(row_to_json(h)) AS total_marks
    			FROM (
    				SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of,
    					(SELECT grade FROM app.grading WHERE round(sum(mark)::float/sum(out_of))*100 >= min_mark AND round(sum(mark)::float/sum(out_of))*100 <= max_mark ) AS grade
    				FROM(
    					SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    						s2.subject_name, s2.subject_id, e2.mark,
    						cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    						(
    							SELECT COUNT(DISTINCT exam_type_id)
    							FROM app.class_subject_exams
    							INNER JOIN app.exam_marks USING (class_sub_exam_id)
    							INNER JOIN app.class_subjects USING (class_subject_id)
    							WHERE term_id = d.term_id
    							AND class_id = d.class_id
    						) AS class_exam_count
    					FROM app.exam_marks e2
    					INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    					INNER JOIN app.exam_types et2 USING (exam_type_id)
    					INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    					INNER JOIN app.subjects s2 USING (subject_id)
    					WHERE e2.student_id = d.student_id
              AND cs2.class_id = d.class_id
    					AND e2.term_id = d.term_id AND parent_subject_id IS null AND use_for_grading IS TRUE
    					ORDER BY et2.sort_order ASC, s2.sort_order ASC
    				)f
    				GROUP BY student_id, exam_type_id, exam_type
    			) h
    		) AS j
    	) AS totals,
    	(
    		SELECT array_to_json(ARRAY_AGG(j)) FROM
    		(
    			SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
    				(
    					SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
    					FROM (
    						SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
    							(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                                (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
    						FROM (
    							SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
    							FROM(
    								SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    									s2.subject_name, s2.subject_id, e2.mark,
    									cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    									(
    										SELECT COUNT(DISTINCT exam_type_id)
    										FROM app.class_subject_exams
    										INNER JOIN app.exam_marks USING (class_sub_exam_id)
    										INNER JOIN app.class_subjects USING (class_subject_id)
    										WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    										AND class_id = d.class_id
    									) AS class_exam_count
    								FROM app.exam_marks e2
    								INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    								INNER JOIN app.exam_types et2 USING (exam_type_id)
    								INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    								INNER JOIN app.subjects s2 USING (subject_id)
    								WHERE e2.student_id = d.student_id and s2.use_for_grading IS TRUE
                    -- AND cs2.class_id = d.class_id
    								AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
    								ORDER BY et2.sort_order ASC, s2.sort_order ASC
    							)f
    							GROUP BY subject_id, subject_name, class_exam_count
    						)g
    					) h
    				) AS last_term_marks_and_grade
    			FROM (
    					SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
    						(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                            (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
    					FROM (
    						SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
    						FROM(
    							SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    								s2.subject_name, s2.subject_id, e2.mark,
    								cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    								(
    									SELECT COUNT(DISTINCT exam_type_id)
    									FROM app.class_subject_exams
    									INNER JOIN app.exam_marks USING (class_sub_exam_id)
    									INNER JOIN app.class_subjects USING (class_subject_id)
    									WHERE term_id = d.term_id
    									AND class_id = d.class_id
    								) AS class_exam_count
    							FROM app.exam_marks e2
    							INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    							INNER JOIN app.exam_types et2 USING (exam_type_id)
    							INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    							INNER JOIN app.subjects s2 USING (subject_id)
    							WHERE e2.student_id = d.student_id
                  AND cs2.class_id = d.class_id
    							AND e2.term_id = d.term_id AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
    							ORDER BY et2.sort_order ASC, s2.sort_order ASC
    						)f
    						GROUP BY subject_id, subject_name, class_exam_count
    					)g
    			) h
    		) AS j
    	) AS overall_marks_and_grade,
      (
        SELECT array_to_json(ARRAY_AGG(j)) FROM
        (
          SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
            (
              SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
              FROM (
                SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
                  (SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
                  (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
                FROM (
                  SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
                  FROM(
                    SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
                        s2.subject_name, s2.subject_id, e2.mark,
                        cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
                        (
                          SELECT COUNT(DISTINCT exam_type_id)
                          FROM app.class_subject_exams
                          INNER JOIN app.exam_marks USING (class_sub_exam_id)
                          INNER JOIN app.class_subjects USING (class_subject_id)
                          WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                          AND class_id = d.class_id
                          AND class_subject_exams.exam_type_id = (
                                                SELECT cc.exam_type_id FROM (
                                                SELECT * FROM (
                                                  SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                    SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                    FROM app.exam_marks em
                                                    INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                    INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                    INNER JOIN app.exam_types et USING (exam_type_id)
                                                    WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                                    AND cs.class_id = d.class_id
                                                    ORDER BY em.creation_date DESC
                                                  )aa
                                                )bb ORDER BY creation_date DESC LIMIT 1
                                                )cc
                                              )
                        ) AS class_exam_count
                    FROM app.exam_marks e2
                    INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                    INNER JOIN app.exam_types et2 USING (exam_type_id)
                    INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                    INNER JOIN app.subjects s2 USING (subject_id)
                    WHERE e2.student_id = d.student_id AND s2.active IS TRUE and s2.use_for_grading IS TRUE
                    AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
                    AND cse2.exam_type_id = (
                                  SELECT cc.exam_type_id FROM (
                                  SELECT * FROM (
                                    SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                      SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                      FROM app.exam_marks em
                                      INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                      INNER JOIN app.class_subjects cs USING (class_subject_id)
                                      INNER JOIN app.exam_types et USING (exam_type_id)
                                      WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                      AND cs.class_id = d.class_id
                                      ORDER BY em.creation_date DESC
                                    )aa
                                  )bb ORDER BY creation_date DESC LIMIT 1
                                  )cc
                                )
                    ORDER BY et2.sort_order ASC, s2.sort_order ASC
                  )f
                  GROUP BY subject_id, subject_name, class_exam_count
                )g
              ) h
            ) AS last_term_marks_and_grade
          FROM (
            SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
              (SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
              (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
            FROM (
              SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
              FROM(
                SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
                    s2.subject_name, s2.subject_id, e2.mark,
                    cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
                    (
                      SELECT COUNT(DISTINCT exam_type_id)
                      FROM app.class_subject_exams
                      INNER JOIN app.exam_marks USING (class_sub_exam_id)
                      INNER JOIN app.class_subjects USING (class_subject_id)
                      WHERE term_id = d.term_id
                      AND class_id = d.class_id
                      AND class_subject_exams.exam_type_id = (
                                            SELECT cc.exam_type_id FROM (
                                            SELECT * FROM (
                                              SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                FROM app.exam_marks em
                                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                INNER JOIN app.exam_types et USING (exam_type_id)
                                                WHERE em.term_id = d.term_id
                                                AND cs.class_id = d.class_id
                                                ORDER BY em.creation_date DESC
                                              )aa
                                            )bb ORDER BY creation_date DESC LIMIT 1
                                            )cc
                                          )
                    ) AS class_exam_count
                FROM app.exam_marks e2
                INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                INNER JOIN app.exam_types et2 USING (exam_type_id)
                INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                INNER JOIN app.subjects s2 USING (subject_id)
                WHERE e2.student_id = d.student_id AND s2.active IS TRUE
                AND e2.term_id = d.term_id AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
                AND cse2.exam_type_id = (
                              SELECT cc.exam_type_id FROM (
                              SELECT * FROM (
                                SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                  SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                  FROM app.exam_marks em
                                  INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                  INNER JOIN app.class_subjects cs USING (class_subject_id)
                                  INNER JOIN app.exam_types et USING (exam_type_id)
                                  WHERE em.term_id = d.term_id
                                  AND cs.class_id = d.class_id
                                  ORDER BY em.creation_date DESC
                                )aa
                              )bb ORDER BY creation_date DESC LIMIT 1
                              )cc
                            )
                ORDER BY et2.sort_order ASC, s2.sort_order ASC
              )f
              GROUP BY subject_id, subject_name, class_exam_count
            )g
          ) h
        ) AS j
      ) AS overall_marks_and_grade_by_last_exam,
    	(
    		SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
    		(
    			SELECT row_to_json(i) AS this_term_position,
    			(
    				SELECT row_to_json(j) AS last_term_position FROM
    				(
    					SELECT * FROM (
    						SELECT student_id, total_mark, total_grade_weight,
    							round((total_mark::float/total_grade_weight::float)*100) as percentage,
    							rank() over w as position, position_out_of
    						FROM (
    							SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
    								round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
    								position_out_of
    							FROM(
    								SELECT exam_marks.student_id,
    									coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
    									coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
    									(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
    									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
    									INNER JOIN app.class_subjects cs USING (class_subject_id)
    									INNER JOIN app.students s USING (student_id)
    									WHERE cs.class_id = d.class_id
    									AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    									AND s.active IS TRUE
    									) as position_out_of,
    									(
    										SELECT COUNT(DISTINCT exam_type_id)
    										FROM app.class_subject_exams
    										INNER JOIN app.exam_marks USING (class_sub_exam_id)
    										INNER JOIN app.class_subjects USING (class_subject_id)
    										WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    										AND class_id = d.class_id
    									) AS class_exam_count

    								FROM app.exam_marks
    								INNER JOIN app.class_subject_exams
    								INNER JOIN app.exam_types USING (exam_type_id)
    								INNER JOIN app.class_subjects
    								INNER JOIN app.subjects
    								ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
    								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
    								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
    								INNER JOIN app.students USING (student_id)
    								WHERE class_subjects.class_id = d.class_id
    								AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    								AND subjects.parent_subject_id is null
    								AND subjects.use_for_grading is true
    								AND students.active is true
    								AND mark IS NOT NULL

    								GROUP BY exam_marks.student_id
    							)c
    						) a
    						WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
    					)q WHERE student_id = d.student_id
    				)j
    			) AS last_term_position
    			FROM
    			(
    												SELECT * FROM (
    													SELECT
    														student_id, total_mark, total_grade_weight,
    														round((total_mark::float/total_grade_weight::float)*100) as percentage,
    														rank() over w as position, position_out_of
    													FROM (
    														SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
    															round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
    															position_out_of
    														FROM(
    															SELECT exam_marks.student_id,
    																coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
    																coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
    																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
    																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
    																		INNER JOIN app.class_subjects cs USING (class_subject_id)
    																		INNER JOIN app.students s USING (student_id)
    																		WHERE cs.class_id = d.class_id AND em.term_id = d.term_id AND s.active IS TRUE
    																) as position_out_of,
    																(
    																	SELECT COUNT(DISTINCT exam_type_id)
    																	FROM app.class_subject_exams
    																	INNER JOIN app.exam_marks USING (class_sub_exam_id)
    																	INNER JOIN app.class_subjects USING (class_subject_id)
    																	WHERE term_id = d.term_id
    																	AND class_id = d.class_id
    																) AS class_exam_count

    															FROM app.exam_marks
    															INNER JOIN app.class_subject_exams
    															INNER JOIN app.exam_types USING (exam_type_id)
    															INNER JOIN app.class_subjects
    															INNER JOIN app.subjects
    															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
    															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
    															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
    															INNER JOIN app.students USING (student_id)
    															WHERE class_subjects.class_id = d.class_id
    															AND term_id = d.term_id
    															AND subjects.parent_subject_id is null
    															AND subjects.use_for_grading is true
    															AND students.active is true
    															AND mark IS NOT NULL

    															GROUP BY exam_marks.student_id
    														)c
    													) a
    													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
    												)p WHERE student_id = d.student_id
    			)i

    		)l
    	) AS positions,
      (
        SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
        (
            SELECT row_to_json(i) AS this_term_position,
              (
                SELECT row_to_json(j) AS last_term_position FROM
                (
                  SELECT * FROM (
                    SELECT
                      student_id, total_mark, total_grade_weight,
                      round((total_mark::float/total_grade_weight::float)*100) as percentage,
                      rank() over w as position, position_out_of
                    FROM (
                      SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
                        round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
                        position_out_of
                      FROM(
                        SELECT exam_marks.student_id,
                          coalesce(sum(case when subjects.parent_subject_id is null then
                                mark
                              end),0) as total_mark,
                          coalesce(sum(case when subjects.parent_subject_id is null then
                                grade_weight
                              end),0) as total_grade_weight,
                          (SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
                              INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                              INNER JOIN app.class_subjects cs USING (class_subject_id)
                              INNER JOIN app.students s USING (student_id)
                              WHERE cs.class_id = d.class_id
                              AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                              AND cse.exam_type_id = (
                                SELECT exam_type_id FROM (
                                  SELECT * FROM (
                                    SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                      SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                      FROM app.exam_marks em
                                      INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                      INNER JOIN app.class_subjects cs USING (class_subject_id)
                                      INNER JOIN app.exam_types et USING (exam_type_id)
                                      WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                      AND cs.class_id = d.class_id
                                      ORDER BY em.creation_date DESC
                                    )a
                                  )b ORDER BY creation_date DESC LIMIT 1
                                )c
                              )
                              AND s.active IS TRUE
                          ) as position_out_of,
                          (
                                        SELECT COUNT(DISTINCT exam_type_id)
                                        FROM app.class_subject_exams
                                        INNER JOIN app.exam_marks USING (class_sub_exam_id)
                                        INNER JOIN app.class_subjects USING (class_subject_id)
                                        WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                        AND class_id = d.class_id
                                        AND class_subject_exams.exam_type_id = (
                                          SELECT exam_type_id FROM (
                                            SELECT * FROM (
                                              SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                FROM app.exam_marks em
                                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                INNER JOIN app.exam_types et USING (exam_type_id)
                                                WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                                AND cs.class_id = d.class_id
                                                ORDER BY em.creation_date DESC
                                              )a
                                            )b ORDER BY creation_date DESC LIMIT 1
                                          )c
                                        )
                                      ) AS class_exam_count

                        FROM app.exam_marks
                        INNER JOIN app.class_subject_exams
                        INNER JOIN app.exam_types USING (exam_type_id)
                        INNER JOIN app.class_subjects
                        INNER JOIN app.subjects
                        ON class_subjects.subject_id = subjects.subject_id
                        ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
                        ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                        INNER JOIN app.students USING (student_id)
                        WHERE class_subjects.class_id = d.class_id AND subjects.active IS TRUE
                        AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                        AND subjects.parent_subject_id is null
                        AND subjects.use_for_grading is true
                        AND students.active is true
                        AND class_subject_exams.exam_type_id = (
                          SELECT exam_type_id FROM (
                            SELECT * FROM (
                              SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                FROM app.exam_marks em
                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                INNER JOIN app.exam_types et USING (exam_type_id)
                                WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                AND cs.class_id = d.class_id
                                ORDER BY em.creation_date DESC
                              )a
                            )b ORDER BY creation_date DESC LIMIT 1
                          )c
                        )
                        AND mark IS NOT NULL

                        GROUP BY exam_marks.student_id
                      )c
                    ) a
                    WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
                  )q WHERE student_id = d.student_id
                )j
              ) AS last_term_position
            FROM
            (
              SELECT * FROM (
                SELECT
                  student_id, total_mark, total_grade_weight,
                  round((total_mark::float/total_grade_weight::float)*100) as percentage,
                  rank() over w as position, position_out_of
                FROM (
                  SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
                    round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
                    position_out_of
                  FROM(
                    SELECT exam_marks.student_id,
                      coalesce(sum(case when subjects.parent_subject_id is null then
                            mark
                          end),0) as total_mark,
                      coalesce(sum(case when subjects.parent_subject_id is null then
                            grade_weight
                          end),0) as total_grade_weight,
                      (SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
                          INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                          INNER JOIN app.class_subjects cs USING (class_subject_id)
                          INNER JOIN app.students s USING (student_id)
                          WHERE cs.class_id = d.class_id
                          AND em.term_id = d.term_id
                          AND s.active IS TRUE
                          AND cse.exam_type_id = (
                            SELECT exam_type_id FROM (
                              SELECT * FROM (
                                SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                  SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                  FROM app.exam_marks em
                                  INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                  INNER JOIN app.class_subjects cs USING (class_subject_id)
                                  INNER JOIN app.exam_types et USING (exam_type_id)
                                  WHERE em.term_id = d.term_id
                                  AND cs.class_id = d.class_id
                                  ORDER BY em.creation_date DESC
                                )a
                              )b ORDER BY creation_date DESC LIMIT 1
                            )c
                          )
                      ) as position_out_of,
                      (
                                    SELECT COUNT(DISTINCT exam_type_id)
                                    FROM app.class_subject_exams
                                    INNER JOIN app.exam_marks USING (class_sub_exam_id)
                                    INNER JOIN app.class_subjects USING (class_subject_id)
                                    WHERE term_id = d.term_id
                                    AND class_id = d.class_id
                                    AND class_subject_exams.exam_type_id = (
                                      SELECT exam_type_id FROM (
                                        SELECT * FROM (
                                          SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                            SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                            FROM app.exam_marks em
                                            INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                            INNER JOIN app.class_subjects cs USING (class_subject_id)
                                            INNER JOIN app.exam_types et USING (exam_type_id)
                                            WHERE em.term_id = d.term_id
                                            AND cs.class_id = d.class_id
                                            ORDER BY em.creation_date DESC
                                          )a
                                        )b ORDER BY creation_date DESC LIMIT 1
                                      )c
                                    )
                                  ) AS class_exam_count

                    FROM app.exam_marks
                    INNER JOIN app.class_subject_exams
                    INNER JOIN app.exam_types USING (exam_type_id)
                    INNER JOIN app.class_subjects
                    INNER JOIN app.subjects
                    ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                    ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
                    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                    INNER JOIN app.students USING (student_id)
                    WHERE class_subjects.class_id = d.class_id
                    AND term_id = d.term_id
                    AND subjects.parent_subject_id is null
                    AND subjects.use_for_grading is true
                    AND students.active is true
                    AND class_subject_exams.exam_type_id = (
                      SELECT exam_type_id FROM (
                        SELECT * FROM (
                          SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                            SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                            FROM app.exam_marks em
                            INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                            INNER JOIN app.class_subjects cs USING (class_subject_id)
                            INNER JOIN app.exam_types et USING (exam_type_id)
                            WHERE em.term_id = d.term_id
                            AND cs.class_id = d.class_id
                            ORDER BY em.creation_date DESC
                          )a
                        )b ORDER BY creation_date DESC LIMIT 1
                      )c
                    )
                    AND mark IS NOT NULL

                    GROUP BY exam_marks.student_id
                  )c
                ) a
                WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
              )p WHERE student_id = d.student_id
            )i

        )l
      ) AS positions_by_last_exam,
    	(
    		SELECT array_to_json(ARRAY_AGG(row_to_json(p))) AS subjects FROM
    		(
    			SELECT DISTINCT ON (subject_id) subject_id, subject_name, teacher_id, sort_order, parent_subject_id,
    				e.initials
    			FROM app.subjects s
    			INNER JOIN app.class_subjects cs USING (subject_id)
    			INNER JOIN app.class_subject_exams cse USING (class_subject_id)
    			INNER JOIN app.exam_marks em USING (class_sub_exam_id)
    			LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
    			WHERE s.active IS TRUE
    			AND cs.class_id = d.class_id
    			AND s.use_for_grading IS TRUE
    		)p
    	) AS subjects_column,
      rc.report_data::json AS report_card_comments
    FROM (
      SELECT DISTINCT r.term_id, t.term_name, r.class_id, c.class_name, r.student_id, s.admission_number,
            s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
            t.end_date AS closing_date, date_part('year', t.start_date) AS year,
            (SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = r.term_id) LIMIT 1) AS next_term_begins
      FROM app.report_cards r
      INNER JOIN app.terms t USING (term_id)
      INNER JOIN app.classes c USING (class_id)
      INNER JOIN app.students s USING (student_id)
      WHERE r.student_id = :studentId AND published IS TRUE
      ORDER BY term_id DESC
    )d
    INNER JOIN
    app.report_cards rc ON d.class_id = rc.class_id AND d.term_id = rc.term_id AND d.student_id = rc.student_id");

      $sth->execute( array(':studentId' => $studentId) );
      $results = $sth->fetchAll(PDO::FETCH_OBJ);
      foreach($results as $result){
        $result->exam_marks = json_decode($result->exam_marks);
        $result->subject_overalls_column = json_decode($result->subject_overalls_column);
        $result->totals = json_decode($result->totals);
        $result->overall_marks_and_grade = json_decode($result->overall_marks_and_grade);
        $result->overall_marks_and_grade_by_last_exam = json_decode($result->overall_marks_and_grade_by_last_exam);
        $result->positions = json_decode($result->positions);
        $result->positions_by_last_exam = json_decode($result->positions_by_last_exam);
        $result->subjects_column = json_decode($result->subjects_column);
        $result->report_card_comments = json_decode($result->report_card_comments);
        $result->report_card_comments = ($result->report_card_comments == null ? null : $result->report_card_comments->comments);
      }

          // traffic analysis start
          $mistrafficdb = getMISDB();
          $subdom = getSubDomain();
          $trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
                          VALUES('$school','parent-app viewing report card')");
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

$app->get('/getAllStudentReportCardDataHigh/:school/:studentId', function ($school, $studentId) {
  // Get the student's report card

    $app = \Slim\Slim::getInstance();

      try
      {
      $db = setDBConnection($school);
      $sth = $db->prepare("SELECT d.*, (SELECT value FROM app.settings WHERE name = 'Exam Calculation') AS calculation_mode,
    	(
    		SELECT array_to_json(ARRAY_AGG(c)) FROM
    		(
    			SELECT exam_type_id, exam_type, array_to_json(ARRAY_AGG(row_to_json(b))) AS exam_marks
    			FROM (
            SELECT student_id, exam_type_id, exam_type, exam_sort, subject_name, subject_id, mark,
              (SELECT grade FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS grade,
              (SELECT comment FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS comment,
              out_of, subject_sort, parent_subject_id, percentage
            FROM (
        				SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
        					s2.subject_name, s2.subject_id, e2.mark,
        					round((mark/cse2.grade_weight::float)*100) AS percentage,
        					cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
        				FROM app.exam_marks e2
        				INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
        				INNER JOIN app.exam_types et2 USING (exam_type_id)
        				INNER JOIN app.class_subjects cs2 USING (class_subject_id)
        				INNER JOIN app.subjects s2 USING (subject_id)
        				WHERE e2.student_id = d.student_id
                AND cs2.class_id = d.class_id
        				AND e2.term_id = d.term_id AND s2.use_for_grading IS TRUE
        				ORDER BY et2.sort_order ASC, s2.sort_order ASC
            )a
    			) b
    			GROUP BY exam_type_id, exam_type
    		) AS c
    	) AS exam_marks,
    	(
    		SELECT array_to_json(ARRAY_AGG(j)) FROM
    		(
    			SELECT ARRAY_AGG(row_to_json(h)) AS subject_overalls
    			FROM (
    				SELECT *, (SELECT grade FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS grade
    				FROM (
    					SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
    					FROM(
    						SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    							s2.subject_name, s2.subject_id, e2.mark,
    							cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    							(
    								SELECT COUNT(DISTINCT exam_type_id)
    								FROM app.class_subject_exams
    								INNER JOIN app.exam_marks USING (class_sub_exam_id)
    								INNER JOIN app.class_subjects USING (class_subject_id)
    								WHERE term_id = d.term_id
    								AND class_id = d.class_id
    							) AS class_exam_count
    						FROM app.exam_marks e2
    						INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    						INNER JOIN app.exam_types et2 USING (exam_type_id)
    						INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    						INNER JOIN app.subjects s2 USING (subject_id)
    						WHERE e2.student_id = d.student_id
                AND cs2.class_id = d.class_id
    						AND e2.term_id = d.term_id AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
    						ORDER BY et2.sort_order ASC, s2.sort_order ASC
    					)f
    					GROUP BY subject_id, subject_name, class_exam_count
    				)g
    			) h
    		) AS j
    	) AS subject_overalls_column,
    	(
    		SELECT array_to_json(ARRAY_AGG(j)) AS total_marks FROM
    		(
    			SELECT ARRAY_AGG(row_to_json(h)) AS total_marks
    			FROM (
    				SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of,
    					(SELECT grade FROM app.grading WHERE round(sum(mark)::float/sum(out_of))*100 >= min_mark AND round(sum(mark)::float/sum(out_of))*100 <= max_mark ) AS grade
    				FROM(
    					SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    						s2.subject_name, s2.subject_id, e2.mark,
    						cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    						(
    							SELECT COUNT(DISTINCT exam_type_id)
    							FROM app.class_subject_exams
    							INNER JOIN app.exam_marks USING (class_sub_exam_id)
    							INNER JOIN app.class_subjects USING (class_subject_id)
    							WHERE term_id = d.term_id
    							AND class_id = d.class_id
    						) AS class_exam_count
    					FROM app.exam_marks e2
    					INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    					INNER JOIN app.exam_types et2 USING (exam_type_id)
    					INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    					INNER JOIN app.subjects s2 USING (subject_id)
    					WHERE e2.student_id = d.student_id
              AND cs2.class_id = d.class_id
    					AND e2.term_id = d.term_id AND parent_subject_id IS null AND use_for_grading IS TRUE
    					ORDER BY et2.sort_order ASC, s2.sort_order ASC
    				)f
    				GROUP BY student_id, exam_type_id, exam_type
    			) h
    		) AS j
    	) AS totals,
    	(
    		SELECT array_to_json(ARRAY_AGG(j)) FROM
    		(
    			SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
    				(
    					SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
    					FROM (
    						SELECT sum(average) || '/' || sum(out_of)::float AS overall_mark, round((sum(average)::float/sum(out_of)::float)*100) AS percentage,
    							(SELECT grade FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS overall_grade,
                                (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS principal_comment
    						FROM (
    							SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average, out_of
    							FROM(
    								SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    									s2.subject_name, s2.subject_id, e2.mark,
    									cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    									(
    										SELECT COUNT(DISTINCT exam_type_id)
    										FROM app.class_subject_exams
    										INNER JOIN app.exam_marks USING (class_sub_exam_id)
    										INNER JOIN app.class_subjects USING (class_subject_id)
    										WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    										AND class_id = d.class_id
    									) AS class_exam_count
    								FROM app.exam_marks e2
    								INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    								INNER JOIN app.exam_types et2 USING (exam_type_id)
    								INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    								INNER JOIN app.subjects s2 USING (subject_id)
    								WHERE e2.student_id = d.student_id and s2.use_for_grading IS TRUE
                    -- AND cs2.class_id = d.class_id
    								AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
    								ORDER BY et2.sort_order ASC, s2.sort_order ASC
    							)f
    							GROUP BY subject_id, subject_name, class_exam_count, out_of
    						)g
    					) h
    				) AS last_term_marks_and_grade
    			FROM (
    					SELECT sum(average) || '/' || sum(out_of)::float AS overall_mark, round((sum(average)::float/sum(out_of)::float)*100) AS percentage,
    						(SELECT grade FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS overall_grade,
                            (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS principal_comment
    					FROM (
    						SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average, out_of
    						FROM(
    							SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
    								s2.subject_name, s2.subject_id, e2.mark,
    								cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
    								(
    									SELECT COUNT(DISTINCT exam_type_id)
    									FROM app.class_subject_exams
    									INNER JOIN app.exam_marks USING (class_sub_exam_id)
    									INNER JOIN app.class_subjects USING (class_subject_id)
    									WHERE term_id = d.term_id
    									AND class_id = d.class_id
    								) AS class_exam_count
    							FROM app.exam_marks e2
    							INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
    							INNER JOIN app.exam_types et2 USING (exam_type_id)
    							INNER JOIN app.class_subjects cs2 USING (class_subject_id)
    							INNER JOIN app.subjects s2 USING (subject_id)
    							WHERE e2.student_id = d.student_id
                  AND cs2.class_id = d.class_id
    							AND e2.term_id = d.term_id AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
    							ORDER BY et2.sort_order ASC, s2.sort_order ASC
    						)f
    						GROUP BY subject_id, subject_name, class_exam_count, out_of
    					)g
    			) h
    		) AS j
    	) AS overall_marks_and_grade,
      (
        SELECT array_to_json(ARRAY_AGG(j)) FROM
        (
          SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
            (
              SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
              FROM (
                SELECT sum(average) || '/' || sum(out_of)::float AS overall_mark, round((sum(average)::float/sum(out_of)::float)*100) AS percentage,
                  (SELECT grade FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS overall_grade,
                  (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS principal_comment
                FROM (
                  SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average, out_of
                  FROM(
                    SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
                        s2.subject_name, s2.subject_id, e2.mark,
                        cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
                        (
                          SELECT COUNT(DISTINCT exam_type_id)
                          FROM app.class_subject_exams
                          INNER JOIN app.exam_marks USING (class_sub_exam_id)
                          INNER JOIN app.class_subjects USING (class_subject_id)
                          WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                          AND class_id = d.class_id
                          AND class_subject_exams.exam_type_id = (
                                                SELECT cc.exam_type_id FROM (
                                                SELECT * FROM (
                                                  SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                    SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                    FROM app.exam_marks em
                                                    INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                    INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                    INNER JOIN app.exam_types et USING (exam_type_id)
                                                    WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                                    AND cs.class_id = d.class_id
                                                    ORDER BY em.creation_date DESC
                                                  )aa
                                                )bb ORDER BY creation_date DESC LIMIT 1
                                                )cc
                                              )
                        ) AS class_exam_count
                    FROM app.exam_marks e2
                    INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                    INNER JOIN app.exam_types et2 USING (exam_type_id)
                    INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                    INNER JOIN app.subjects s2 USING (subject_id)
                    WHERE e2.student_id = d.student_id AND s2.active IS TRUE and s2.use_for_grading IS TRUE
                    AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
                    AND cse2.exam_type_id = (
                                  SELECT cc.exam_type_id FROM (
                                  SELECT * FROM (
                                    SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                      SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                      FROM app.exam_marks em
                                      INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                      INNER JOIN app.class_subjects cs USING (class_subject_id)
                                      INNER JOIN app.exam_types et USING (exam_type_id)
                                      WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                      AND cs.class_id = d.class_id
                                      ORDER BY em.creation_date DESC
                                    )aa
                                  )bb ORDER BY creation_date DESC LIMIT 1
                                  )cc
                                )
                    ORDER BY et2.sort_order ASC, s2.sort_order ASC
                  )f
                  GROUP BY subject_id, subject_name, class_exam_count, out_of
                )g
              ) h
            ) AS last_term_marks_and_grade
          FROM (
            SELECT sum(average) || '/' || sum(out_of)::float AS overall_mark, round((sum(average)::float/sum(out_of)::float)*100) AS percentage,
              (SELECT grade FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS overall_grade,
              (SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/sum(out_of)::float)*100) >= min_mark AND round((sum(average)::float/sum(out_of)::float)*100) <= max_mark) AS principal_comment
            FROM (
              SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average, out_of
              FROM(
                SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
                    s2.subject_name, s2.subject_id, e2.mark,
                    cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
                    (
                      SELECT COUNT(DISTINCT exam_type_id)
                      FROM app.class_subject_exams
                      INNER JOIN app.exam_marks USING (class_sub_exam_id)
                      INNER JOIN app.class_subjects USING (class_subject_id)
                      WHERE term_id = d.term_id
                      AND class_id = d.class_id
                      AND class_subject_exams.exam_type_id = (
                                            SELECT cc.exam_type_id FROM (
                                            SELECT * FROM (
                                              SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                FROM app.exam_marks em
                                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                INNER JOIN app.exam_types et USING (exam_type_id)
                                                WHERE em.term_id = d.term_id
                                                AND cs.class_id = d.class_id
                                                ORDER BY em.creation_date DESC
                                              )aa
                                            )bb ORDER BY creation_date DESC LIMIT 1
                                            )cc
                                          )
                    ) AS class_exam_count
                FROM app.exam_marks e2
                INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                INNER JOIN app.exam_types et2 USING (exam_type_id)
                INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                INNER JOIN app.subjects s2 USING (subject_id)
                WHERE e2.student_id = d.student_id AND s2.active IS TRUE
                AND e2.term_id = d.term_id AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
                AND cse2.exam_type_id = (
                              SELECT cc.exam_type_id FROM (
                              SELECT * FROM (
                                SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                  SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                  FROM app.exam_marks em
                                  INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                  INNER JOIN app.class_subjects cs USING (class_subject_id)
                                  INNER JOIN app.exam_types et USING (exam_type_id)
                                  WHERE em.term_id = d.term_id
                                  AND cs.class_id = d.class_id
                                  ORDER BY em.creation_date DESC
                                )aa
                              )bb ORDER BY creation_date DESC LIMIT 1
                              )cc
                            )
                ORDER BY et2.sort_order ASC, s2.sort_order ASC
              )f
              GROUP BY subject_id, subject_name, class_exam_count, out_of
            )g
          ) h
        ) AS j
      ) AS overall_marks_and_grade_by_last_exam,
    	(
    		SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
    		(
    			SELECT row_to_json(i) AS this_term_position,
    			(
    				SELECT row_to_json(j) AS last_term_position FROM
    				(
    					SELECT * FROM (
    						SELECT student_id, total_mark, total_grade_weight,
    							round((total_mark::float/total_grade_weight::float)*100) as percentage,
    							rank() over w as position, position_out_of
    						FROM (
    							SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
    								round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
    								position_out_of
    							FROM(
    								SELECT exam_marks.student_id,
    									coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
    									coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
    									(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
    									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
    									INNER JOIN app.class_subjects cs USING (class_subject_id)
    									INNER JOIN app.students s USING (student_id)
    									WHERE cs.class_id = d.class_id
    									AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    									AND s.active IS TRUE
    									) as position_out_of,
    									(
    										SELECT COUNT(DISTINCT exam_type_id)
    										FROM app.class_subject_exams
    										INNER JOIN app.exam_marks USING (class_sub_exam_id)
    										INNER JOIN app.class_subjects USING (class_subject_id)
    										WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    										AND class_id = d.class_id
    									) AS class_exam_count

    								FROM app.exam_marks
    								INNER JOIN app.class_subject_exams
    								INNER JOIN app.exam_types USING (exam_type_id)
    								INNER JOIN app.class_subjects
    								INNER JOIN app.subjects
    								ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
    								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
    								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
    								INNER JOIN app.students USING (student_id)
    								WHERE class_subjects.class_id = d.class_id
    								AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
    								AND subjects.parent_subject_id is null
    								AND subjects.use_for_grading is true
    								AND students.active is true
    								AND mark IS NOT NULL

    								GROUP BY exam_marks.student_id
    							)c
    						) a
    						WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
    					)q WHERE student_id = d.student_id
    				)j
    			) AS last_term_position
    			FROM
    			(
    												SELECT * FROM (
    													SELECT
    														student_id, total_mark, total_grade_weight,
    														round((total_mark::float/total_grade_weight::float)*100) as percentage,
    														rank() over w as position, position_out_of
    													FROM (
    														SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
    															round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
    															position_out_of
    														FROM(
    															SELECT exam_marks.student_id,
    																coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
    																coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
    																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
    																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
    																		INNER JOIN app.class_subjects cs USING (class_subject_id)
    																		INNER JOIN app.students s USING (student_id)
    																		WHERE cs.class_id = d.class_id AND em.term_id = d.term_id AND s.active IS TRUE
    																) as position_out_of,
    																(
    																	SELECT COUNT(DISTINCT exam_type_id)
    																	FROM app.class_subject_exams
    																	INNER JOIN app.exam_marks USING (class_sub_exam_id)
    																	INNER JOIN app.class_subjects USING (class_subject_id)
    																	WHERE term_id = d.term_id
    																	AND class_id = d.class_id
    																) AS class_exam_count

    															FROM app.exam_marks
    															INNER JOIN app.class_subject_exams
    															INNER JOIN app.exam_types USING (exam_type_id)
    															INNER JOIN app.class_subjects
    															INNER JOIN app.subjects
    															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
    															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
    															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
    															INNER JOIN app.students USING (student_id)
    															WHERE class_subjects.class_id = d.class_id
    															AND term_id = d.term_id
    															AND subjects.parent_subject_id is null
    															AND subjects.use_for_grading is true
    															AND students.active is true
    															AND mark IS NOT NULL

    															GROUP BY exam_marks.student_id
    														)c
    													) a
    													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
    												)p WHERE student_id = d.student_id
    			)i

    		)l
    	) AS positions,
      (
        SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
        (
            SELECT row_to_json(i) AS this_term_position,
              (
                SELECT row_to_json(j) AS last_term_position FROM
                (
                  SELECT * FROM (
                    SELECT
                      student_id, total_mark, total_grade_weight,
                      round((total_mark::float/total_grade_weight::float)*100) as percentage,
                      rank() over w as position, position_out_of
                    FROM (
                      SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
                        round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
                        position_out_of
                      FROM(
                        SELECT exam_marks.student_id,
                          coalesce(sum(case when subjects.parent_subject_id is null then
                                mark
                              end),0) as total_mark,
                          coalesce(sum(case when subjects.parent_subject_id is null then
                                grade_weight
                              end),0) as total_grade_weight,
                          (SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
                              INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                              INNER JOIN app.class_subjects cs USING (class_subject_id)
                              INNER JOIN app.students s USING (student_id)
                              WHERE cs.class_id = d.class_id
                              AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                              AND cse.exam_type_id = (
                                SELECT exam_type_id FROM (
                                  SELECT * FROM (
                                    SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                      SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                      FROM app.exam_marks em
                                      INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                      INNER JOIN app.class_subjects cs USING (class_subject_id)
                                      INNER JOIN app.exam_types et USING (exam_type_id)
                                      WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                      AND cs.class_id = d.class_id
                                      ORDER BY em.creation_date DESC
                                    )a
                                  )b ORDER BY creation_date DESC LIMIT 1
                                )c
                              )
                              AND s.active IS TRUE
                          ) as position_out_of,
                          (
                                        SELECT COUNT(DISTINCT exam_type_id)
                                        FROM app.class_subject_exams
                                        INNER JOIN app.exam_marks USING (class_sub_exam_id)
                                        INNER JOIN app.class_subjects USING (class_subject_id)
                                        WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                        AND class_id = d.class_id
                                        AND class_subject_exams.exam_type_id = (
                                          SELECT exam_type_id FROM (
                                            SELECT * FROM (
                                              SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                                SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                                FROM app.exam_marks em
                                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                INNER JOIN app.exam_types et USING (exam_type_id)
                                                WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                                AND cs.class_id = d.class_id
                                                ORDER BY em.creation_date DESC
                                              )a
                                            )b ORDER BY creation_date DESC LIMIT 1
                                          )c
                                        )
                                      ) AS class_exam_count

                        FROM app.exam_marks
                        INNER JOIN app.class_subject_exams
                        INNER JOIN app.exam_types USING (exam_type_id)
                        INNER JOIN app.class_subjects
                        INNER JOIN app.subjects
                        ON class_subjects.subject_id = subjects.subject_id
                        ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
                        ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                        INNER JOIN app.students USING (student_id)
                        WHERE class_subjects.class_id = d.class_id AND subjects.active IS TRUE
                        AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                        AND subjects.parent_subject_id is null
                        AND subjects.use_for_grading is true
                        AND students.active is true
                        AND class_subject_exams.exam_type_id = (
                          SELECT exam_type_id FROM (
                            SELECT * FROM (
                              SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                FROM app.exam_marks em
                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                INNER JOIN app.exam_types et USING (exam_type_id)
                                WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = d.term_id) order by start_date desc limit 1 )
                                AND cs.class_id = d.class_id
                                ORDER BY em.creation_date DESC
                              )a
                            )b ORDER BY creation_date DESC LIMIT 1
                          )c
                        )
                        AND mark IS NOT NULL

                        GROUP BY exam_marks.student_id
                      )c
                    ) a
                    WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
                  )q WHERE student_id = d.student_id
                )j
              ) AS last_term_position
            FROM
            (
              SELECT * FROM (
                SELECT
                  student_id, total_mark, total_grade_weight,
                  round((total_mark::float/total_grade_weight::float)*100) as percentage,
                  rank() over w as position, position_out_of
                FROM (
                  SELECT student_id, round(total_mark::float/NULLIF(class_exam_count,0)) AS total_mark,
                    round(total_grade_weight::float/NULLIF(class_exam_count,0)) AS total_grade_weight,
                    position_out_of
                  FROM(
                    SELECT exam_marks.student_id,
                      coalesce(sum(case when subjects.parent_subject_id is null then
                            mark
                          end),0) as total_mark,
                      coalesce(sum(case when subjects.parent_subject_id is null then
                            grade_weight
                          end),0) as total_grade_weight,
                      (SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
                          INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                          INNER JOIN app.class_subjects cs USING (class_subject_id)
                          INNER JOIN app.students s USING (student_id)
                          WHERE cs.class_id = d.class_id
                          AND em.term_id = d.term_id
                          AND s.active IS TRUE
                          AND cse.exam_type_id = (
                            SELECT exam_type_id FROM (
                              SELECT * FROM (
                                SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                  SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                  FROM app.exam_marks em
                                  INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                  INNER JOIN app.class_subjects cs USING (class_subject_id)
                                  INNER JOIN app.exam_types et USING (exam_type_id)
                                  WHERE em.term_id = d.term_id
                                  AND cs.class_id = d.class_id
                                  ORDER BY em.creation_date DESC
                                )a
                              )b ORDER BY creation_date DESC LIMIT 1
                            )c
                          )
                      ) as position_out_of,
                      (
                                    SELECT COUNT(DISTINCT exam_type_id)
                                    FROM app.class_subject_exams
                                    INNER JOIN app.exam_marks USING (class_sub_exam_id)
                                    INNER JOIN app.class_subjects USING (class_subject_id)
                                    WHERE term_id = d.term_id
                                    AND class_id = d.class_id
                                    AND class_subject_exams.exam_type_id = (
                                      SELECT exam_type_id FROM (
                                        SELECT * FROM (
                                          SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                                            SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                                            FROM app.exam_marks em
                                            INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                            INNER JOIN app.class_subjects cs USING (class_subject_id)
                                            INNER JOIN app.exam_types et USING (exam_type_id)
                                            WHERE em.term_id = d.term_id
                                            AND cs.class_id = d.class_id
                                            ORDER BY em.creation_date DESC
                                          )a
                                        )b ORDER BY creation_date DESC LIMIT 1
                                      )c
                                    )
                                  ) AS class_exam_count

                    FROM app.exam_marks
                    INNER JOIN app.class_subject_exams
                    INNER JOIN app.exam_types USING (exam_type_id)
                    INNER JOIN app.class_subjects
                    INNER JOIN app.subjects
                    ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                    ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
                    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                    INNER JOIN app.students USING (student_id)
                    WHERE class_subjects.class_id = d.class_id
                    AND term_id = d.term_id
                    AND subjects.parent_subject_id is null
                    AND subjects.use_for_grading is true
                    AND students.active is true
                    AND class_subject_exams.exam_type_id = (
                      SELECT exam_type_id FROM (
                        SELECT * FROM (
                          SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
                            SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
                            FROM app.exam_marks em
                            INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                            INNER JOIN app.class_subjects cs USING (class_subject_id)
                            INNER JOIN app.exam_types et USING (exam_type_id)
                            WHERE em.term_id = d.term_id
                            AND cs.class_id = d.class_id
                            ORDER BY em.creation_date DESC
                          )a
                        )b ORDER BY creation_date DESC LIMIT 1
                      )c
                    )
                    AND mark IS NOT NULL

                    GROUP BY exam_marks.student_id
                  )c
                ) a
                WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
              )p WHERE student_id = d.student_id
            )i

        )l
      ) AS positions_by_last_exam,
    	(
    		SELECT array_to_json(ARRAY_AGG(row_to_json(p))) AS subjects FROM
    		(
    			SELECT DISTINCT ON (subject_id) subject_id, subject_name, teacher_id, sort_order, parent_subject_id,
    				e.initials
    			FROM app.subjects s
    			INNER JOIN app.class_subjects cs USING (subject_id)
    			INNER JOIN app.class_subject_exams cse USING (class_subject_id)
    			INNER JOIN app.exam_marks em USING (class_sub_exam_id)
    			LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
    			WHERE s.active IS TRUE
    			AND cs.class_id = d.class_id
    			AND s.use_for_grading IS TRUE
    		)p
    	) AS subjects_column,
      rc.report_data::json AS report_card_comments
    FROM (
      SELECT DISTINCT r.term_id, t.term_name, r.class_id, c.class_name, r.student_id, s.admission_number,
            s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
            t.end_date AS closing_date, date_part('year', t.start_date) AS year,
            (SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = r.term_id) LIMIT 1) AS next_term_begins
      FROM app.report_cards r
      INNER JOIN app.terms t USING (term_id)
      INNER JOIN app.classes c USING (class_id)
      INNER JOIN app.students s USING (student_id)
      WHERE r.student_id = :studentId AND published IS TRUE
      ORDER BY term_id DESC
    )d
    INNER JOIN
    app.report_cards rc ON d.class_id = rc.class_id AND d.term_id = rc.term_id AND d.student_id = rc.student_id");

      $sth->execute( array(':studentId' => $studentId) );
      $results = $sth->fetchAll(PDO::FETCH_OBJ);
      foreach($results as $result){
        $result->exam_marks = json_decode($result->exam_marks);
        $result->subject_overalls_column = json_decode($result->subject_overalls_column);
        $result->totals = json_decode($result->totals);
        $result->overall_marks_and_grade = json_decode($result->overall_marks_and_grade);
        $result->overall_marks_and_grade_by_last_exam = json_decode($result->overall_marks_and_grade_by_last_exam);
        $result->positions = json_decode($result->positions);
        $result->positions_by_last_exam = json_decode($result->positions_by_last_exam);
        $result->subjects_column = json_decode($result->subjects_column);
        $result->report_card_comments = json_decode($result->report_card_comments);
        $result->report_card_comments = ($result->report_card_comments == null ? null : $result->report_card_comments->comments);
      }

          // traffic analysis start
          $mistrafficdb = getMISDB();
          $subdom = getSubDomain();
          $trafficMonitor = $mistrafficdb->prepare("INSERT INTO traffic(school, module)
                          VALUES('$school','parent-app viewing report card')");
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
              ORDER BY class_id DESC");
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

$app->get('/getGrading2/:school', function ($school) {
    //Show lower school grading

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT * FROM app.grading2 ORDER BY max_mark desc");
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
                    WHERE date_part('year',start_date) >= :year -1
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

$app->get('/getAllTerms/:school', function ($school) {
    //Show all terms

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    $query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name, start_date, end_date,
                      case when term_id = (select term_id from app.current_term) then true else false end as current_term, date_part('year',start_date) as year
                    FROM app.terms
                    ORDER BY date_part('year',start_date), term_name");
    $query->execute();

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

    $sth = $db->prepare("SELECT a.*, 'TERM ' || (SELECT term_number FROM app.terms WHERE term_id = a.term_id) AS alt_term_name
                        FROM (
                          SELECT report_card_id, report_cards.student_id, report_cards.class_id, class_name, term_name, report_cards.term_id,
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
                              ORDER BY report_card_id
                        )a");
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

$app->get('/getDocReport/:school/:studentId', function ($school,$studentId) {
  //Show document report cards for the student

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT term_name, sch.class_id, class_name, lr.* FROM app.lowersch_reportcards lr
                        INNER JOIN app.terms t USING (term_id)
                        INNER JOIN app.student_class_history sch ON t.start_date >= sch.start_date AND t.end_date <= sch.end_date AND lr.student_id = sch.student_id
                        INNER JOIN app.classes c USING (class_id)
                        WHERE lr.student_id = :studentId");
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
    foreach($studentId as $student)
    {
      $sth = $db->prepare("INSERT INTO app.communication_feedback (subject, message, message_from, student_id, guardian_id) VALUES (:subject, :message, :messageFrom, :student, :guardianId)");
      $sth->execute( array(':subject' => $subject, ':message' => $message, ':messageFrom' => $messageFrom, ':student' => $student, ':guardianId' => $guardianId) );
    }

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

$app->post('/reportAbsenteeism', function () {

  $app = \Slim\Slim::getInstance();

  $allPostVars = json_decode($app->request()->getBody(),true);
  $students = $allPostVars['students'];
  $message = $allPostVars['message'];
  $reason = $allPostVars['reason'];
  $startDate = $allPostVars['start_date'];
  $endDate = $allPostVars['end_date'];
  $starting = $allPostVars['starting'];
  $ending = $allPostVars['ending'];
  $school = $allPostVars['school'];

  try
  {

    $db = setDBConnection($school);
    foreach($students as $studentId)
    {
      $sth = $db->prepare("INSERT INTO app.absenteeism(student_id, reason, message, start_date, end_date, starting, ending)
  	                       VALUES (:studentId, :reason, :message, :startDate, :endDate, :starting, :ending);");
      $sth->execute( array(':studentId' => $studentId, ':message' => $message, ':reason' => $reason,
                            ':startDate' => $startDate, ':endDate' => $endDate, ':starting' => $starting,
                            ':ending' => $ending) );
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", 'data' => 'The absenteeism has been successfully recorded.'));

    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getStudentAbsenteeism/:school/:studentId', function ($school,$studentId) {
    //Show student's absenteeism

  $app = \Slim\Slim::getInstance();
    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT *,
                              TO_CHAR(creation_date :: DATE, 'dd/mm/yyyy') AS formatted_creation,
                              TO_CHAR(starting :: DATE, 'dd/mm/yyyy') AS formatted_start_date,
                              TO_CHAR(ending :: DATE, 'dd/mm/yyyy') AS formatted_end_date,
                              ending - starting AS days_absent
                            FROM app.absenteeism
                            WHERE student_id = :studentId ORDER BY creation_date DESC");
        $sth->execute(array(':studentId' => $studentId));
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
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addHomeworkFeedback', function () {

  $app = \Slim\Slim::getInstance();

  $allPostVars = json_decode($app->request()->getBody(),true);

  $homeworkId = ( isset($allPostVars['homework_id']) ? $allPostVars['homework_id']: null);
  $title = ( isset($allPostVars['title']) ? $allPostVars['title']: null);
  $body = ( isset($allPostVars['body']) ? $allPostVars['body']: null);
  $message = ( isset($allPostVars['message']) ? $allPostVars['message']: null);
  $homeworkAttachment = ( isset($allPostVars['homework_attachment']) ? $allPostVars['homework_attachment']: null);
  $studentAttachment = ( isset($allPostVars['student_attachment']) ? $allPostVars['student_attachment']: null);
  $assignedDate = ( isset($allPostVars['assigned_date']) ? $allPostVars['assigned_date']: null);
  $dueDate = ( isset($allPostVars['due_date']) ? $allPostVars['due_date']: null);
  $addedBy = ( isset($allPostVars['added_by']) ? $allPostVars['added_by']: null);
  $empId = ( isset($allPostVars['emp_id']) ? $allPostVars['emp_id']: null);
  $studentId = ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $guardianId = ( isset($allPostVars['guardian_id']) ? $allPostVars['guardian_id']: null);
  $subjectId = ( isset($allPostVars['subject_id']) ? $allPostVars['subject_id']: null);
  $subjectName = ( isset($allPostVars['subject_name']) ? $allPostVars['subject_name']: null);
  $classId = ( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
  $className = ( isset($allPostVars['class_name']) ? $allPostVars['class_name']: null);
  $school = ( isset($allPostVars['school']) ? $allPostVars['school']: null);

  try
  {

    $db = setDBConnection($school);
    $sth = $db->prepare("INSERT INTO app.homework_feedback(
                    	homework_id, title, body, message, homework_attachment, student_attachment, assigned_date, due_date, added_by, emp_id, subject_name, subject_id, class_id, class_name, student_id, guardian_id)
                    	VALUES (:homeworkId,
                    	        :title,
                    	        :body,
                    	        :message,
                    	        :homeworkAttachment,
                    	        :studentAttachment,
                    	        :assignedDate,
                    	        :dueDate,
                    	        :addedBy,
                    	        :empId,
                    	        :subjectName,
                    	        :subjectId,
                    	        :classId,
                    	        :className,
                    	        :studentId,
                    	        :guardianId
                    	);");
    $sth->execute( array(':homeworkId' => $homeworkId, ':title' => $title, ':body' => $body, ':message' => $message, ':homeworkAttachment' => $homeworkAttachment,
                        ':studentAttachment' => $studentAttachment, ':assignedDate' => $assignedDate, ':dueDate' => $dueDate, ':addedBy' => $addedBy, ':empId' => $empId,
                        ':subjectName' => $subjectName, ':subjectId' => $subjectId, ':classId' => $classId, ':className' => $className, ':studentId' => $studentId, ':guardianId' => $guardianId) );

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", 'data' => 'Homework message has been successfully sent!'));

    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->post('/pushQuickbooksData', function () use($app) {
  // receive data from quickbooks
  $allPostVars = json_decode($app->request()->getBody(),true);
  $dataType = ( isset($allPostVars['data_type']) ? $allPostVars['data_type']: null); // either invoice or payment
  $dataArr = ( isset($allPostVars['qb_data']) ? $allPostVars['qb_data']: null); // data from quickbooks

  try
  {
    $db = getQuickbooksDB();

    if($dataType === 'invoices'){
      // initialize invoice data
      foreach($dataArr as $invoice)
			{
				$qbInvoiceId = ( isset($invoice['qb_invoice_id']) ? $invoice['qb_invoice_id']: null);
				$clientId = ( isset($invoice['client_id']) ? $invoice['client_id']: null);
				$admissionNumber = ( isset($invoice['admission_number']) ? $invoice['admission_number']: null);
        $feeItem = ( isset($invoice['fee_item']) ? $invoice['fee_item']: null);
				$amount = ( isset($invoice['amount']) ? $invoice['amount']: null);
        $dueDate = ( isset($invoice['due_date']) ? $invoice['due_date']: null);

        $sth = $db->prepare("INSERT INTO public.to_eduweb_invoices(qb_invoice_id, client_id, admission_number, fee_item, amount, due_date)
                            VALUES (:qbInvoiceId, :clientId, :admissionNumber, :feeItem, :amount, :dueDate);");
        $sth->execute( array(':qbInvoiceId' => $qbInvoiceId,
                            ':clientId' => $clientId,
                            ':admissionNumber' => $admissionNumber,
                            ':feeItem' => $feeItem,
                            ':amount' => $amount,
                            ':dueDate' => $dueDate) );
      }

      $message = "Quickbooks INVOICES received and posted successfully.";
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success", "code" => 1, "message" => $message));
      $db = null;

    }elseif($dataType === "payments"){
      // initialize invoice data
      foreach($dataArr as $payment)
			{
				$qbPaymentId = ( isset($payment['qb_payment_id']) ? $payment['qb_payment_id']: null);
				$clientId = ( isset($payment['client_id']) ? $payment['client_id']: null);
				$admissionNumber = ( isset($payment['admission_number']) ? $payment['admission_number']: null);
        $amount = ( isset($payment['amount']) ? $payment['amount']: null);
        $paymentDate = ( isset($payment['payment_date']) ? $payment['payment_date']: null);
        $qbInvoiceId = ( isset($payment['qb_invoice_id']) ? $payment['qb_invoice_id']: null);

        $sth = $db->prepare("INSERT INTO public.to_eduweb_payments(qb_payment_id, client_id, admission_number, amount, payment_date, qb_invoice_id)
                              VALUES (:qbPaymentId, :clientId, :admissionNumber, :amount, :paymentDate, :qbInvoiceId);");
        $sth->execute( array(':qbPaymentId' => $qbPaymentId,
                            ':clientId' => $clientId,
                            ':admissionNumber' => $admissionNumber,
                            ':amount' => $amount,
                            ':paymentDate' => $paymentDate,
                            ':qbInvoiceId' => $qbInvoiceId) );
      }

      $message = "Quickbooks PAYMENTS received and posted successfully.";
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success", "code" => 1, "message" => $message));
      $db = null;

    }else{
      $message = "The operation encountered an issue with the 'data_type'. Ensure it is either 'invoices' or 'payments'";
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "error", "code" => 1, "message" => $message, "data_sent" => $allPostVars));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getToQuickbooksData/:username/:password', function ($username,$password) {
    //Get all data required for quickbooks sync

  $app = \Slim\Slim::getInstance();
  $results = new stdClass;

    try
    {
      $db = getQuickbooksDB();

      // get this client's details
      $clientQry = $db->prepare("SELECT * FROM quickbooks_clients WHERE username = :username AND password = :password");
      $clientQry->execute(array(':username' => $username, ':password' => $password));
      $clientDetails = $clientQry->fetch(PDO::FETCH_OBJ);
      $results->client = $clientDetails;

      $results->IncomeAccount = array();
      $incomeAccountData = new stdClass();
      $incomeAccountData->account_id = $results->client->quickbooks_client_id;
      $incomeAccountData->account_name = "FEES";
      $incomeAccountData->account_type = "ENAccountType.atIncome";
      array_push($results->IncomeAccount,$incomeAccountData);

      $results->ReceivableAccount = array();
      $accountData = new stdClass();
      $accountData->account_id = $results->client->quickbooks_client_id;
      $accountData->account_name = "Accounts Receivable";
      $accountData->account_type = "ENAccountType.atAccountsReceivable";
      array_push($results->ReceivableAccount,$accountData);

      $feeItemsQry = $db->prepare("SELECT fi.*, 'FEES' AS account_name FROM fee_items fi
                                  INNER JOIN quickbooks_clients qc USING (subdomain)
                                  WHERE fi.client_identifier = (SELECT subdomain FROM quickbooks_clients WHERE username = :username AND password = :password)
                                  AND fi.exists_in_quickbooks IS FALSE");
      $feeItemsQry->execute(array(':username' => $username, ':password' => $password));
      $itemsDetails = $feeItemsQry->fetchAll(PDO::FETCH_OBJ);
      $results->fee_items = $itemsDetails;

      $studentsQry = $db->prepare("SELECT * FROM client_students
                                  WHERE client_id = (SELECT subdomain FROM quickbooks_clients WHERE username = :username AND password = :password) AND exists_in_quickbooks IS FALSE
                                  ");
      $studentsQry->execute(array(':username' => $username, ':password' => $password));
      $studentsList = $studentsQry->fetchAll(PDO::FETCH_OBJ);
      $results->students = $studentsList;
      /*
      $paymentsQry = $db->prepare("SELECT *, 'Accounts Receivable' AS ARAccountRef FROM to_quickbooks_payments
                                    WHERE client_id = (SELECT subdomain FROM quickbooks_clients WHERE username = :username AND password = :password)
                                    ");
      */
      $paymentsQry = $db->prepare("SELECT * FROM (
                                    	SELECT to_quickbooks_payment_id, eduweb_payment_id, client_id, admission_number, amount, payment_date, eduweb_invoice_id, receipt_number, 'Accounts Receivable' AS ARAccountRef FROM to_quickbooks_payments
                                    	WHERE client_id = (SELECT subdomain FROM quickbooks_clients WHERE username = :username AND password = :password)
                                    	UNION
                                    	SELECT to_quickbooks_credit_id, eduweb_payment_id, client_id, admission_number, amount, payment_date, eduweb_invoice_id, receipt_number, 'Accounts Receivable' AS ARAccountRef FROM to_quickbooks_credits
                                    	WHERE client_id = (SELECT subdomain FROM quickbooks_clients WHERE username = :username AND password = :password)
                                    )a
                                    ORDER BY admission_number ASC
                                    ");
      $paymentsQry->execute(array(':username' => $username, ':password' => $password));
      $payments = $paymentsQry->fetchAll(PDO::FETCH_OBJ);
      $results->payments = $payments;

      $invoicesQry = $db->prepare("SELECT invoice_id, client_id, admission_number, due_date, invoice_date, line_items FROM (
                                      	SELECT q2.eduweb_invoice_id AS invoice_id, q1.client_id, q1.admission_number, q1.due_date, q1.invoice_date,
                                      			q2.line_items
                                      	FROM
                                      	(
                                      		SELECT DISTINCT eduweb_invoice_id, client_id, admission_number, due_date, invoice_date  FROM to_quickbooks_invoices
                                      		WHERE client_id = (SELECT subdomain FROM quickbooks_clients WHERE username = :username AND password = :password)
                                      	)q1
                                      	INNER JOIN
                                      	(
                                      		SELECT eduweb_invoice_id, array_to_json(array_agg(row_to_json(d))) AS line_items
                                      		  FROM (
                                      			SELECT eduweb_invoice_id, fee_item, amount
                                      			FROM to_quickbooks_invoices
                                      			WHERE client_id = (SELECT subdomain FROM quickbooks_clients WHERE username = :username AND password = :password)
                                      			ORDER BY eduweb_invoice_id ASC
                                      			) d
                                      			GROUP BY eduweb_invoice_id
                                      	)q2
                                      	ON q1.eduweb_invoice_id = q2.eduweb_invoice_id
                                      )q
                                      ORDER BY invoice_id ASC");
      $invoicesQry->execute(array(':username' => $username, ':password' => $password));
      $invoices = $invoicesQry->fetchAll(PDO::FETCH_OBJ);
      $results->invoices = $invoices;

      foreach( $results->invoices as $inv )
      {
        $inv->line_items = json_decode($inv->line_items);
      }

      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success',  'data' => $results ));
      $db = null;

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addedToQuickbooks', function () use($app) {
  // receive data from quickbooks
  $allPostVars = json_decode($app->request()->getBody(),true);

  // logging
  $dte = date("Y-m-d");
  $tme = date("h:i:sa");
  $datetime = $dte . " " . $tme;
  file_put_contents('log-ids.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
  file_put_contents('log-ids.txt', print_r($app->request()->getBody() . PHP_EOL, true), FILE_APPEND);

  $clientUsrnm = ( isset($allPostVars['client']) ? $allPostVars['client']: null);
  $accountArr = ( isset($allPostVars['Account']) ? $allPostVars['Account']: null);
  $incomeAccountsArr = ( isset($allPostVars['incomeAccountIds']) ? $allPostVars['incomeAccountIds']: null);
  $feeItemsArr = ( isset($allPostVars['fee_ItemIds']) ? $allPostVars['fee_ItemIds']: null);
  $studentsArr = ( isset($allPostVars['studentIds']) ? $allPostVars['studentIds']: null);
  $paymentsArr = ( isset($allPostVars['paymentIds']) ? $allPostVars['paymentIds']: null);
  $invoicesArr = ( isset($allPostVars['invoiceIds']) ? $allPostVars['invoiceIds']: null);

  $paymentId = ( isset($allPostVars['paymentIds']) ? implode(',', $paymentsArr) : null );
  $invoiceId = ( isset($allPostVars['invoiceIds']) ? implode(',', $invoicesArr) : null );
  $feeItemId = ( isset($allPostVars['fee_ItemIds']) ? implode(',', $feeItemsArr) : null );
  $studentId = ( isset($allPostVars['studentIds']) ? implode(',', $studentsArr) : null );

  try
  {
    $db = getQuickbooksDB();

    if(!empty($incomeAccountsArr)){

      foreach($incomeAccountsArr as $incomeAccount)
			{
        $userDomain = $db->query("SELECT subdomain FROM public.quickbooks_clients WHERE username = '$clientUsrnm'");
        $res = $userDomain->fetch(PDO::FETCH_OBJ);
        $theSubDomain = $res->subdomain;

        $getDb = setDBConnection($theSubDomain);
        /* QUERIES FOR QUICKBOOKS DB */
        if(isset($allPostVars['paymentIds'])){
          // QUICKBOOKS
          $paySth = $db->prepare("DELETE FROM public.to_quickbooks_payments
                                  WHERE client_id = (SELECT client_identifier FROM public.quickbooks_clients WHERE username = :clientUsrnm)
                                  AND eduweb_payment_id IN ($paymentId);");
          $paySth->execute( array(':clientUsrnm' => $clientUsrnm) );

          $credSth = $db->prepare("DELETE FROM public.to_quickbooks_credits
                                  WHERE client_id = (SELECT client_identifier FROM public.quickbooks_clients WHERE username = :clientUsrnm)
                                  AND eduweb_payment_id IN ($paymentId);");
          $credSth->execute( array(':clientUsrnm' => $clientUsrnm) );

          // SCHOOL
          $paySth2 = $getDb->prepare("UPDATE app.payments SET in_quickbooks = true
    	                                 WHERE payment_id IN ($paymentId);");
          $paySth2->execute( array() );

          $credSth2 = $getDb->prepare("UPDATE app.credits SET in_quickbooks = true
    	                                 WHERE payment_id IN ($paymentId);");
          $credSth2->execute( array() );
        }

        if(isset($allPostVars['invoiceIds'])){
          // QUICKBOOKS
          $invSth = $db->prepare("DELETE FROM public.to_quickbooks_invoices
                                  WHERE client_id = (SELECT client_identifier FROM public.quickbooks_clients WHERE username = :clientUsrnm)
                                  AND eduweb_invoice_id IN ($invoiceId);");
          $invSth->execute( array(':clientUsrnm' => $clientUsrnm) );

          // SCHOOL
          $invSth2 = $getDb->prepare("UPDATE app.invoices SET in_quickbooks = true
                                      WHERE inv_id IN ($invoiceId);");
          $invSth2->execute( array() );
        }

        if(isset($allPostVars['fee_ItemIds'])){
          // QUICKBOOKS
          $feeSth = $db->prepare("DELETE FROM public.fee_items
                                  WHERE client_identifier = (SELECT client_identifier FROM public.quickbooks_clients WHERE username = :clientUsrnm)
                                  AND eduweb_fee_item_id IN ($feeItemId);");
          $feeSth->execute( array(':clientUsrnm' => $clientUsrnm) );

          // SCHOOL
          $feeSth2 = $getDb->prepare("UPDATE app.fee_items SET in_quickbooks = true
                                      WHERE fee_item_id IN ($feeItemId);");
          $feeSth2->execute( array() );
        }

        if(isset($allPostVars['studentIds'])){
          // QUICKBOOKS
          $stdntSth = $db->prepare("DELETE FROM public.client_students
                                    WHERE client_id = (SELECT client_identifier FROM public.quickbooks_clients WHERE username = :clientUsrnm)
                                    AND admission_number = ANY(string_to_array('$studentId',','))
                                    --AND admission_number IN ('$studentId');
                                    ");
          $stdntSth->execute( array(':clientUsrnm' => $clientUsrnm) );

          // SCHOOL
          $stdntSth2 = $getDb->prepare("UPDATE app.students SET in_quickbooks = true
                                        WHERE admission_number = ANY(string_to_array('$studentId',','))
                                        --WHERE admission_number IN ('$studentId');
                                        ");
          $stdntSth2->execute( array() );
        }


      }

      $message = "Process completed successfully. Parsed data has been removed.";
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success", "code" => 1, "message" => $message, "client" => $clientUsrnm, "account_id" => $incomeAccount, "payments" => $paymentId, "invoices" => $invoiceId, "students" => $studentId, "fee_items" => $feeItemId));
      $db = null;

    }else{
      $message = "Processing of data could not proceed. Please ensure all required parameters are set.";
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "error", "code" => 1, "error_message" => $message, "received_data" => $allPostVars));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getStudentResources/:school/:studentId', function ($school, $studentId) {
  // Return students resources

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    $sth = $db->prepare("SELECT sr.*, c.class_name,
                        		e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as teacher_name,
                        		t.term_name, TO_CHAR(sr.creation_date :: DATE, 'dd/mm/yyyy') AS formatted_date
                        FROM app.school_resources sr
                        INNER JOIN app.classes c USING (class_id)
                        INNER JOIN app.employees e USING (emp_id)
                        INNER JOIN app.terms t USING (term_id)
                        --INNER JOIN app.students s ON sr.class_id = s.current_class
                        WHERE c.class_id = (SELECT current_class FROM app.students WHERE student_id = :studentID)
                        ORDER BY resource_id DESC");
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

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentTermTotalsAnalysis/:school/:studentId/:classId/:termId', function ($school, $studentId, $classId, $termId) {
  // Return students resources

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    $sth = $db->prepare("SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of
                          FROM(
                          	SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
                          		s2.subject_name, s2.subject_id, e2.mark,
                          		cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
                          		(
                          			SELECT COUNT(DISTINCT exam_type_id)
                          			FROM app.class_subject_exams
                          			INNER JOIN app.exam_marks USING (class_sub_exam_id)
                          			INNER JOIN app.class_subjects USING (class_subject_id)
                          			WHERE term_id = :termId
                          			AND class_id = :classId
                          		) AS class_exam_count
                          	FROM app.exam_marks e2
                          	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                          	INNER JOIN app.exam_types et2 USING (exam_type_id)
                          	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                          	INNER JOIN app.subjects s2 USING (subject_id)
                          	WHERE e2.student_id = :studentId
                          	AND e2.term_id = :termId
                          	AND cs2.class_id = :classId
                          	AND parent_subject_id IS null AND use_for_grading IS TRUE AND s2.active IS TRUE
                          	ORDER BY et2.sort_order ASC, s2.sort_order ASC
                          )f
                          GROUP BY student_id, exam_type_id, exam_type");
    $sth->execute( array(':studentId' => $studentId, ':termId' => $termId, ':classId' => $classId));
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

$app->get('/getStudentYearTotalsAnalysis/:school/:studentId/:classId', function ($school, $studentId, $classId) {
  // Return students resources

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    $sth = $db->prepare("SELECT student_id, term_name, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of
                          FROM(
                          	SELECT e2.student_id, term_name, cse2.exam_type_id, et2.exam_type,
                          		s2.subject_name, s2.subject_id, e2.mark,
                          		cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
                          		t.start_date, et2.sort_order AS exam_order, s2.sort_order AS subject_order
                          	FROM app.exam_marks e2
                          	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                          	INNER JOIN app.exam_types et2 USING (exam_type_id)
                          	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                          	INNER JOIN app.subjects s2 USING (subject_id)
                          	INNER JOIN app.terms t USING (term_id)
                          	WHERE e2.student_id = :studentId
                          	AND cs2.class_id = :classId
                          	AND parent_subject_id IS null AND use_for_grading IS TRUE AND s2.active IS TRUE
                          	ORDER BY t.start_date ASC, et2.sort_order ASC, s2.sort_order ASC
                          )f
                          GROUP BY student_id, term_name, exam_type_id, exam_type, start_date, exam_order
                          ORDER BY start_date ASC, exam_order ASC");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId));
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

$app->get('/getStudentSubjPerformanceByTermAnalysis/:school/:studentId/:classId/:termId', function ($school, $studentId, $classId, $termId) {
  // Return students resources

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    $sth = $db->prepare("SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                        			s2.subject_name, s2.subject_id, coalesce(e2.mark,0) AS mark,
                        			round((mark/cse2.grade_weight::float)*100) AS percentage,
                        			cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                        	FROM app.exam_marks e2
                        	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                        	INNER JOIN app.exam_types et2 USING (exam_type_id)
                        	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                        	INNER JOIN app.subjects s2 USING (subject_id)
                        	WHERE e2.student_id = :studentId
                        	AND cs2.class_id = :classId
                        	AND e2.term_id = :termId AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                        	AND s2.parent_subject_id IS NULL
                        	ORDER BY et2.sort_order ASC, s2.sort_order ASC");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId));
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

$app->get('/getStudentSubjPerformanceByYearAnalysis/:school/:studentId/:classId', function ($school, $studentId, $classId) {
  // Return students resources

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    $sth = $db->prepare("SELECT e2.term_id, t2.term_name, e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                        			s2.subject_name, s2.subject_id, coalesce(e2.mark,0) AS mark,
                        			round((mark/cse2.grade_weight::float)*100) AS percentage,
                        			cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                        	FROM app.exam_marks e2
                        	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                        	INNER JOIN app.exam_types et2 USING (exam_type_id)
                        	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                        	INNER JOIN app.subjects s2 USING (subject_id)
                          INNER JOIN app.terms t2 USING (term_id)
                        	WHERE e2.student_id = :studentId
                        	AND cs2.class_id = :classId
                          AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                        	AND s2.parent_subject_id IS NULL
                        	ORDER BY e2.term_id ASC, et2.sort_order ASC, s2.sort_order ASC");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId));
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

$app->get('/getSubjGradePerfByTermAnalysis/:school/:studentId/:classId/:termId', function ($school, $studentId, $classId, $termId) {
  // Return students resources

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    $sth = $db->prepare("SELECT DISTINCT ON (exam_type_id) exam_type_id, subject_name, exam_type, exam_sort,
                        		mark as max_mark
                        FROM (
                        	SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                        			s2.subject_name, s2.subject_id, coalesce(e2.mark,0) AS mark,
                        			round((mark/cse2.grade_weight::float)*100) AS percentage,
                        			cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                        	FROM app.exam_marks e2
                        	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                        	INNER JOIN app.exam_types et2 USING (exam_type_id)
                        	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                        	INNER JOIN app.subjects s2 USING (subject_id)
                        	WHERE e2.student_id = :studentId
                        	AND cs2.class_id = :classId
                        	AND e2.term_id = :termId AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                        	AND s2.parent_subject_id IS NULL
                        	ORDER BY et2.sort_order DESC, s2.sort_order ASC
                        )a
                        ORDER BY exam_type_id, mark DESC NULLS LAST;");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId));
    $highest = $sth->fetchAll(PDO::FETCH_OBJ);

    $sth2 = $db->prepare("SELECT DISTINCT ON (exam_type_id) exam_type_id, subject_name, exam_type, exam_sort,
                          		mark as low_mark
                          FROM (
                          	SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                          			s2.subject_name, s2.subject_id, coalesce(e2.mark,0) AS mark,
                          			round((mark/cse2.grade_weight::float)*100) AS percentage,
                          			cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                          	FROM app.exam_marks e2
                          	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                          	INNER JOIN app.exam_types et2 USING (exam_type_id)
                          	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                          	INNER JOIN app.subjects s2 USING (subject_id)
                          	WHERE e2.student_id = :studentId
                          	AND cs2.class_id = :classId
                          	AND e2.term_id = :termId AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                          	AND s2.parent_subject_id IS NULL
                          	ORDER BY et2.sort_order ASC, s2.sort_order ASC
                          )a
                          ORDER BY exam_type_id, mark ASC NULLS LAST;");
    $sth2->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId));
    $lowest = $sth2->fetchAll(PDO::FETCH_OBJ);

    $results = new stdClass();
    $results->highest = $highest;
    $results->lowest = $lowest;

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

$app->get('/getSubjGradePerfByYearAnalysis/:school/:studentId/:classId', function ($school, $studentId, $classId) {
  // Return students resources

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    $sth = $db->prepare("SELECT DISTINCT ON (term_id, exam_type_id) term_id, exam_type_id, term_name, subject_name, exam_type, exam_sort,
                          		max_mark
                          FROM (
                          	SELECT e2.term_id, t2.term_name, e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                          			s2.subject_name, s2.subject_id, coalesce(e2.mark,0) AS max_mark,
                          			round((mark/cse2.grade_weight::float)*100) AS percentage,
                          			cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                          	FROM app.exam_marks e2
                          	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                          	INNER JOIN app.exam_types et2 USING (exam_type_id)
                          	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                          	INNER JOIN app.subjects s2 USING (subject_id)
                          	INNER JOIN app.terms t2 USING (term_id)
                          	WHERE e2.student_id = :studentId
                          	AND cs2.class_id = :classId
                          	AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                          	AND s2.parent_subject_id IS NULL
                          	ORDER BY term_id ASC, et2.sort_order ASC, s2.sort_order ASC
                          )a
                          ORDER BY term_id, exam_type_id, max_mark DESC NULLS LAST;");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId));
    $highest = $sth->fetchAll(PDO::FETCH_OBJ);

    $sth2 = $db->prepare("SELECT DISTINCT ON (term_id, exam_type_id) term_id, exam_type_id, term_name, subject_name, exam_type, exam_sort,
                          		low_mark
                          FROM (
                          	SELECT e2.term_id, t2.term_name, e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
                          			s2.subject_name, s2.subject_id, coalesce(e2.mark,0) AS low_mark,
                          			round((mark/cse2.grade_weight::float)*100) AS percentage,
                          			cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
                          	FROM app.exam_marks e2
                          	INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
                          	INNER JOIN app.exam_types et2 USING (exam_type_id)
                          	INNER JOIN app.class_subjects cs2 USING (class_subject_id)
                          	INNER JOIN app.subjects s2 USING (subject_id)
                          	INNER JOIN app.terms t2 USING (term_id)
                          	WHERE e2.student_id = :studentId
                          	AND cs2.class_id = :classId
                          	AND s2.use_for_grading IS TRUE AND s2.active IS TRUE
                          	AND s2.parent_subject_id IS NULL
                          	ORDER BY term_id ASC, et2.sort_order ASC, s2.sort_order ASC
                          )a
                          ORDER BY term_id, exam_type_id, low_mark ASC NULLS LAST;");
    $sth2->execute( array(':studentId' => $studentId, ':classId' => $classId));
    $lowest = $sth2->fetchAll(PDO::FETCH_OBJ);

    $results = new stdClass();
    $results->highest = $highest;
    $results->lowest = $lowest;

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

?>
