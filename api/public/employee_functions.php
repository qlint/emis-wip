<?php
$app->get('/getAllEmployees(/:status)', function ($status) {
    //Show all employees

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT emp_id, employees.emp_cat_id, employees.dept_id, emp_number, id_number, gender, first_name,
									middle_name, last_name, initials, dob, country, employees.active, telephone, email, joined_date,
									job_title, qualifications, experience, additional_info, emp_image,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as employee_name,
									emp_cat_name, dept_name, super_teacher
							 FROM app.employees
							 INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id AND employee_cats.active is true
							 INNER JOIN app.departments ON employees.dept_id = departments.dept_id AND departments.active is TRUE
							 WHERE employees.active = :status
							 ORDER BY first_name, middle_name, last_name, emp_cat_name, dept_name");
        $sth->execute( array(':status' => $status));
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

$app->get('/allStaffSubscriber', function () {

  $app = \Slim\Slim::getInstance();

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT emp_id, u.username, e.active, e.first_name, e.middle_name, e.last_name, e.email,
													e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name AS staff_full_name, telephone, u.user_id, u.user_type,
													e.emp_cat_id, dept_id, e.emp_number, e.gender,
													CASE WHEN e.emp_image IS NULL THEN NULL ELSE 'https://cdn.eduweb.co.ke/employees/' || emp_image END AS emp_image
												FROM app.employees e
												INNER JOIN app.users u ON e.login_id = u.user_id
												AND e.active IS TRUE AND u.active IS TRUE AND u.user_type = 'TEACHER'
												ORDER BY emp_id ASC");
    $sth->execute( array() );
		$result = $sth->fetchAll(PDO::FETCH_OBJ);

		if($result) {
			$app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      $db = null;
      echo json_encode(array('response' => 'success', 'data' => $result ));
		} else {
			$app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "error", "code" => 3, "data" => 'The password you have entered is incorrect. Please check the spelling and / or capitalization.'));
		}

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/exportAllStaffDetails', function () {
  //Get all staff details

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as employee_name, initials, CASE WHEN gender = 'F' THEN 'Female' ELSE 'Male' END AS gender,
                        	id_number, telephone, email, dob, country, emp_number, emp_cat_name AS category, dept_name AS department, job_title, qualifications, experience,
                        	additional_info, next_of_kin_name, next_of_kin_telephone, house, committee, super_teacher
                        FROM app.employees e
                        INNER JOIN app.employee_cats USING (emp_cat_id)
                        INNER JOIN app.departments USING (dept_id)
                        ORDER BY employee_name ASC");
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

$app->get('/getEmployee/:id', function () {
    //Show specific employee

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT  emp_id, emp_cat_id, dept_id, emp_number, id_number, gender, first_name,
									middle_name, last_name, initials, dob, country, active, telephone, email, joined_date,
									job_title, qualifications, experience, additional_info, emp_image, committee, super_teacher
							 FROM app.employee
							 WHERE emp_id = :id");
        $sth->execute( array(':id' => $id));
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

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addEmployee', function () use($app) {
    // Add employee

	$allPostVars = json_decode($app->request()->getBody(),true);

	$empCatId =			( isset($allPostVars['emp_cat_id']) ? $allPostVars['emp_cat_id']: null);
	$deptId =			( isset($allPostVars['dept_id']) ? $allPostVars['dept_id']: null);
	$empNumber =		( isset($allPostVars['emp_number']) ? $allPostVars['emp_number']: null);
	$idNumber =			( isset($allPostVars['id_number']) ? $allPostVars['id_number']: null);
	$gender =			( isset($allPostVars['gender']) ? $allPostVars['gender']: null);
	$firstName =		( isset($allPostVars['first_name']) ? $allPostVars['first_name']: null);
	$middleName =		( isset($allPostVars['middle_name']) ? $allPostVars['middle_name']: null);
	$lastName =			( isset($allPostVars['last_name']) ? $allPostVars['last_name']: null);
	$initials =			( isset($allPostVars['initials']) ? $allPostVars['initials']: null);
	$dob =				( isset($allPostVars['dob']) ? $allPostVars['dob']: null);
	$country =			( isset($allPostVars['country']) ? $allPostVars['country']: null);
	$active =			( isset($allPostVars['active']) ? $allPostVars['active']: 'f');
	$telephone =		( isset($allPostVars['telephone']) ? $allPostVars['telephone']: null);
	$telephone2 =		( isset($allPostVars['telephone2']) ? $allPostVars['telephone2']: null);
	$email =			( isset($allPostVars['email']) ? $allPostVars['email']: null);
	$joinedDate =		( isset($allPostVars['joined_date']) ? $allPostVars['joined_date']: null);
	$jobTitle =			( isset($allPostVars['job_title']) ? $allPostVars['job_title']: null);
	$qualifications =	( isset($allPostVars['qualifications']) ? $allPostVars['qualifications']: null);
	$experience =		( isset($allPostVars['experience']) ? $allPostVars['experience']: null);
	$additionalInfo =	( isset($allPostVars['additional_info']) ? $allPostVars['additional_info']: null);
	$empImage =			( isset($allPostVars['emp_image']) ? $allPostVars['emp_image']: null);
	$createdBy =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$nokName =			( isset($allPostVars['next_of_kin_name']) ? $allPostVars['next_of_kin_name']: null);
	$nokTelephone =		( isset($allPostVars['next_of_kin_telephone']) ? $allPostVars['next_of_kin_telephone']: null);
	$nokEmail =			( isset($allPostVars['next_of_kin_email']) ? $allPostVars['next_of_kin_email']: null);

	$username =			( isset($allPostVars['username']) ? $allPostVars['username']: null);
	$password =			( isset($allPostVars['password']) ? $allPostVars['password']: null);
	$userType =			( isset($allPostVars['user_type']) ? $allPostVars['user_type']: null);


    try
    {
        $db = getDB();
        $insertEmp = $db->prepare("INSERT INTO app.employees(emp_cat_id, dept_id, emp_number, id_number, gender, first_name, middle_name, last_name, initials, dob,
										country, active, telephone, email, joined_date, job_title, qualifications, experience, additional_info, created_by, emp_image,
										next_of_kin_name, next_of_kin_telephone, next_of_kin_email, telephone2)
							VALUES(:empCatId,:deptId,:empNumber,:idNumber,:gender,:firstName,:middleName,:lastName,:initials,:dob,
										:country,:active,:telephone,:email,:joinedDate,:jobTitle,:qualifications,:experience,:additionalInfo,:createdBy,:empImage,
										:nokName, :nokTelephone, :nokEmail, :telephone2)");

		if( $username !== null )
		{
			$insertUser = $db->prepare("INSERT INTO app.users(first_name, middle_name, last_name, username, password, email, user_type, created_by)
								VALUES(:firstName, :middleName, :lastName, :username, :password, :email, :userType, :createdBy)");
			$updateEmp = $db->prepare("UPDATE app.employees
										SET login_id = currval('app.user_user_id_seq')
										WHERE emp_id = currval('app.employees_emp_id_seq');");

		}

		$db->beginTransaction();
        $insertEmp->execute( array(':empCatId' => $empCatId,
							':deptId' => $deptId,
							':empNumber' => $empNumber,
							':idNumber' => $idNumber,
							':gender' => $gender,
							':firstName' => $firstName,
							':middleName' => $middleName,
							':lastName' => $lastName,
							':initials' => $initials,
							':dob' => $dob,
							':country' => $country,
							':active' => $active,
							':telephone' => $telephone,
							':telephone2' => $telephone2,
							':email' => $email,
							':joinedDate' => $joinedDate,
							':jobTitle' => $jobTitle,
							':qualifications' => $qualifications,
							':experience' => $experience,
							':additionalInfo' => $additionalInfo,
							':createdBy' => $createdBy,
							':empImage' => $empImage,
							':nokName' => $nokName,
							':nokTelephone' => $nokTelephone,
							':nokEmail' => $nokEmail
		) );

		if( $username !== null )
		{
			$insertUser->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName,
							 ':username' => $username, ':password' => $password, ':email' => $email, ':userType' => $userType,
							 ':createdBy' => $createdBy	) );
			$updateEmp->execute();
		}

		$db->commit();

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateEmployee', function () use($app) {
    // Update employee

	$allPostVars = json_decode($app->request()->getBody(),true);

	$empId =			( isset($allPostVars['emp_id']) ? $allPostVars['emp_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

	$employeeDataOnly = ( isset($allPostVars['employee']) ? $allPostVars['employee']: null);

	$updatePersonal = false;
	$updateEmployee = false;

	if( isset($allPostVars['personal']) )
	{
		$idNumber =			( isset($allPostVars['personal']['id_number']) ? $allPostVars['personal']['id_number']: null);
		$gender =			( isset($allPostVars['personal']['gender']) ? $allPostVars['personal']['gender']: null);
		$firstName =		( isset($allPostVars['personal']['first_name']) ? $allPostVars['personal']['first_name']: null);
		$middleName =		( isset($allPostVars['personal']['middle_name']) ? $allPostVars['personal']['middle_name']: null);
		$lastName =			( isset($allPostVars['personal']['last_name']) ? $allPostVars['personal']['last_name']: null);
		$initials =			( isset($allPostVars['personal']['initials']) ? $allPostVars['personal']['initials']: null);
		$dob =				( isset($allPostVars['personal']['dob']) ? $allPostVars['personal']['dob']: null);
		$country =			( isset($allPostVars['personal']['country']) ? $allPostVars['personal']['country']: null);
		$active =			( isset($allPostVars['personal']['active']) ? $allPostVars['personal']['active']: 'f');
		$telephone =		( isset($allPostVars['personal']['telephone']) ? $allPostVars['personal']['telephone']: null);
		$telephone2 =		( isset($allPostVars['personal']['telephone2']) ? $allPostVars['personal']['telephone2']: null);
		$email =			( isset($allPostVars['personal']['email']) ? $allPostVars['personal']['email']: null);
		$empImage =			( isset($allPostVars['personal']['emp_image']) ? $allPostVars['personal']['emp_image']: null);
		$nokName =			( isset($allPostVars['personal']['next_of_kin_name']) ? $allPostVars['personal']['next_of_kin_name']: null);
		$nokTelephone =		( isset($allPostVars['personal']['next_of_kin_telephone']) ? $allPostVars['personal']['next_of_kin_telephone']: null);
		$nokEmail =			( isset($allPostVars['personal']['next_of_kin_email']) ? $allPostVars['personal']['next_of_kin_email']: null);
		$house =			( isset($allPostVars['personal']['house']) ? $allPostVars['personal']['house']: null);
		$updatePersonal = true;
	}
	if( isset($allPostVars['employee']) )
	{
	    $active =           ( isset($allPostVars['employee']['active']) ? $allPostVars['employee']['active']: null);
		$empNumber =		( isset($allPostVars['employee']['emp_number']) ? $allPostVars['employee']['emp_number']: null);
		$empCatId =			( isset($allPostVars['employee']['emp_cat_id']) ? $allPostVars['employee']['emp_cat_id']: null);
		// $empId =			( isset($allPostVars['employee']['emp_id']) ? $allPostVars['employee']['emp_id']: null);
		$idNumber =			( isset($allPostVars['employee']['id_number']) ? $allPostVars['employee']['id_number']: null);
		$deptId =			( isset($allPostVars['employee']['dept_id']) ? $allPostVars['employee']['dept_id']: null);
		$joinedDate =		( isset($allPostVars['employee']['joined_date']) ? $allPostVars['employee']['joined_date']: null);
		$jobTitle =			( isset($allPostVars['employee']['job_title']) ? $allPostVars['employee']['job_title']: null);
		$qualifications =	( isset($allPostVars['employee']['qualifications']) ? $allPostVars['employee']['qualifications']: null);
		$experience =		( isset($allPostVars['employee']['experience']) ? $allPostVars['employee']['experience']: null);
		$additionalInfo =	( isset($allPostVars['employee']['additional_info']) ? $allPostVars['employee']['additional_info']: null);
		$firstName =		( isset($allPostVars['employee']['first_name']) ? $allPostVars['employee']['first_name']: null);
		$middleName =		( isset($allPostVars['employee']['middle_name']) ? $allPostVars['employee']['middle_name']: null);
		$lastName =			( isset($allPostVars['employee']['last_name']) ? $allPostVars['employee']['last_name']: null);
		$email =			( isset($allPostVars['employee']['email']) ? $allPostVars['employee']['email']: null);
		$username =			( isset($allPostVars['employee']['username']) ? $allPostVars['employee']['username']: null);
		$password =			( isset($allPostVars['employee']['password']) ? $allPostVars['employee']['password']: null);
		$userType =			( isset($allPostVars['employee']['user_type']) ? $allPostVars['employee']['user_type']: null);
		$superTeacher =		( isset($allPostVars['employee']['super_teacher']) ? $allPostVars['employee']['super_teacher']: 'f');
		$loginActive =		( isset($allPostVars['employee']['login_active']) ? $allPostVars['employee']['login_active']: 'f');
		$loginId =			( isset($allPostVars['employee']['login_id']) ? $allPostVars['employee']['login_id']: null);
		$telephone =		( isset($allPostVars['employee']['telephone']) ? $allPostVars['employee']['telephone']: null);
		$subdomain =		( isset($allPostVars['employee']['subdomain']) ? $allPostVars['employee']['subdomain']: null);
		$committee =			( isset($allPostVars['employee']['committee']) ? $allPostVars['employee']['committee']: null);
		$updateEmployee = true;
	}

	$updateLogin = false;
	$addLogin = false;
	$updatePwd = false;

    try
    {
        $db = getDB();

		if( $updatePersonal )
		{
			$sth = $db->prepare("UPDATE app.employees
				SET id_number = :idNumber,
					gender = :gender,
					first_name = :firstName,
					middle_name = :middleName,
					last_name = :lastName,
					initials = :initials,
					dob = :dob,
					country = :country,
					active = :active,
					telephone = :telephone,
					telephone2 = :telephone2,
					email = :email,
					emp_image = :empImage,
					next_of_kin_name = :nokName,
					next_of_kin_telephone = :nokTelephone,
					next_of_kin_email = :nokEmail,
					modified_date = now(),
					modified_by = :userId,
					house = :house
				WHERE emp_id = :empId");

				$sth->execute( array(':empId' => $empId,
								':idNumber' => $idNumber,
								':gender' => $gender,
								':firstName' => $firstName,
								':middleName' => $middleName,
								':lastName' => $lastName,
								':initials' => $initials,
								':dob' => $dob,
								':country' => $country,
								':active' => $active,
								':telephone' => $telephone,
								':telephone2' => $telephone2,
								':email' => $email,
								':empImage' => $empImage,
								':nokName' => $nokName,
								':nokTelephone' => $nokTelephone,
								':nokEmail' => $nokEmail,
								':userId' => $userId,
								':house' => $house
			) );
		}
		if( $updateEmployee )
		{
			$sth = $db->prepare("UPDATE app.employees
				SET emp_cat_id = :empCatId,
					dept_id = :deptId,
					emp_number = :empNumber,
					joined_date = :joinedDate,
					job_title = :jobTitle,
					qualifications = :qualifications,
					experience = :experience,
					additional_info = :additionalInfo,
					modified_date = now(),
					modified_by = :userId,
					committee = :committee,
					super_teacher = :superTeacher
				WHERE emp_id = :empId");

				if( $loginId !== null && $username !== null )
				{
					$updateLogin = true;
					if( $password === null )
					{
						$updateUser = $db->prepare("UPDATE app.users
													SET	active = :loginActive,
														user_type = :userType,
														modified_date = now(),
														modified_by = :userId
													WHERE user_id = :loginId
										");
					}
					else
					{
						$updatePwd = true;
						$updateUser = $db->prepare("UPDATE app.users
													SET password = :password,
														active = :loginActive,
														user_type = :userType,
														modified_date = now(),
														modified_by = :userId
													WHERE user_id = :loginId
										");
					}


				}
				else if($loginId === null && $username !== null)
				{
					$addLogin = true;
					$insertUser = $db->prepare("INSERT INTO app.users(first_name, middle_name, last_name, username, password, email, user_type, created_by)
												VALUES(:firstName, :middleName, :lastName, :username, :password, :email, :userType, :userId)");
					$updateEmp = $db->prepare("UPDATE app.employees
												SET login_id = currval('app.user_user_id_seq')
												WHERE emp_id = :empId;");
				}

				$db->beginTransaction();

				$sth->execute( array(':empId' => $empId,
								':empCatId' => $empCatId,
								':deptId' => $deptId,
								':empNumber' => $empNumber,
								':joinedDate' => $joinedDate,
								':jobTitle' => $jobTitle,
								':qualifications' => $qualifications,
								':experience' => $experience,
								':additionalInfo' => $additionalInfo,
								':userId' => $userId,
								':committee' => $committee,
								':superTeacher' => $superTeacher
						) );

				if( $updateLogin )
				{
					if( $updatePwd ) $updateUser->execute( array(':loginActive' => $loginActive, ':password' => $password,  ':userType' => $userType, ':userId' => $userId, ':loginId' => $loginId ) );
					else 			 $updateUser->execute( array(':loginActive' => $loginActive,':userType' => $userType, ':userId' => $userId, ':loginId' => $loginId ) );
				}
				else if( $addLogin )
				{
					$insertUser->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName,
												 ':username' => $username, ':password' => $password, ':email' => $email, ':userType' => $userType,
												 ':userId' => $userId	) );
					$updateEmp->execute( array(':empId' => $empId) );
				}

				$db->commit();

				// we use the same data to create or modify the data for app login
				createStaffLogin($employeeDataOnly);

		}



		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getAllTeachers(/:status)', function ($status=true) {
    //Show all teachers

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT emp_id as teacher_id, employees.emp_cat_id, dept_id, emp_number, id_number, gender, first_name,
         middle_name, last_name, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name,
         initials, dob, country, employees.active, telephone, email, joined_date,
         job_title, qualifications, experience, additional_info, emp_image, super_teacher
        FROM app.employees
        INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id
        WHERE LOWER(employee_cats.emp_cat_name) = LOWER('TEACHING')
        AND employees.active = :status
        ORDER BY first_name, middle_name, last_name");
        $sth->execute( array(':status' => $status));
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

$app->get('/getEmployeeDetails/:empId', function ($empId) {
    // Get employee details

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT employees.*,
									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as employee_name,
									emp_cat_name, dept_name, super_teacher,
									(select array_agg(class_name) from app.classes where teacher_id = employees.emp_id and classes.active is true) as classes,
									(select array_agg(subject_name || ' (' || class_cat_name || ')') from app.subjects inner join app.class_cats using (class_cat_id) where teacher_id = employees.emp_id and subjects.active is true) as subjects,
									username, user_type, users.active as login_active
							 FROM app.employees
							 INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id AND employee_cats.active is true
							 INNER JOIN app.departments ON employees.dept_id = departments.dept_id AND departments.active is TRUE
							 LEFT JOIN app.users ON employees.login_id = users.user_id
							 WHERE emp_id = :empId");
        $sth->execute( array(':empId' => $empId));
        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {
			$results->classes = pg_array_parse($results->classes);
			$results->subjects = pg_array_parse($results->subjects);

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

// ************** Employee Categories  ****************** //
$app->get('/getEmployeeCats', function () {
    //Show all employee categories

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT emp_cat_id, emp_cat_name FROM app.employee_cats WHERE active is true ORDER BY emp_cat_id");
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
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addEmployeeCat', function () use($app) {
    // Add employee category

	$allPostVars = json_decode($app->request()->getBody(),true);
	$empCatName = ( isset($allPostVars['emp_cat_name']) ? $allPostVars['emp_cat_name']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try
    {
       $db = getDB();
        $sth1 = $db->prepare("INSERT INTO app.employee_cats(emp_cat_name,created_by)
            VALUES(:empCatName,:userId)");

		$sth2 = $db->prepare("SELECT currval('app.employee_cats_emp_cat_id_seq') as emp_cat_id");

		$db->beginTransaction();
		$sth1->execute( array(':empCatName' => $empCatName, ':userId' => $userId) );
		$sth2->execute();
		$empCatId = $sth2->fetch(PDO::FETCH_OBJ);
		$db->commit();


		$result = new stdClass();
		$result->emp_cat_id = $empCatId->emp_cat_id;
		$result->emp_cat_name = $empCatName;

		if( $empCatId )
		{
			$app->response->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo json_encode(array("response" => "success", "data" => $result));
			$db = null;
		}
		else
		{
			$app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $e->getMessage() ));
            $db = null;
		}


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateEmployeeCat', function () use($app) {
    // Update employee category

	$allPostVars = json_decode($app->request()->getBody(),true);
	$empCatId = ( isset($allPostVars['emp_cat_id']) ? $allPostVars['emp_cat_id']: null);
	$empCatName = ( isset($allPostVars['emp_cat_name']) ? $allPostVars['emp_cat_name']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try
    {
       $db = getDB();
       $sth = $db->prepare("UPDATE app.employee_cats
								SET emp_cat_name = :empCatName,
									modified_date = now(),
									modified_by = :userId
								WHERE emp_cat_id = :empCatId");

		$sth->execute( array(':empCatId' => $empCatId, ':empCatName' => $empCatName, ':userId' => $userId) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/setEmployeeCatStatus', function () use($app) {
    // Update employee cat status

	$allPostVars = json_decode($app->request()->getBody(),true);
	$empCatId = ( isset($allPostVars['emp_cat_id']) ? $allPostVars['emp_cat_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.employee_cats
							SET active = :status,
								modified_date = now(),
								modified_by = :userId
							WHERE emp_cat_id = :empCatId
							");
        $sth->execute( array(':empCatId' => $empCatId,
							 ':status' => $status,
							 ':userId' => $userId
					) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/checkEmpCat/:emp_cat_id', function ($empCatId) {
    // Check is employe cat can be deleted

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT count(emp_id) as num_employees
								FROM app.employees
								WHERE emp_cat_id = :empCatId");
        $sth->execute( array(':empCatId' => $empCatId ) );

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

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->delete('/deleteEmpCat/:emp_cat_id', function ($empCatId) {
    // delete employee cat

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.employee_cats WHERE emp_cat_id = :empCatId");

		$sth->execute( array(':empCatId' => $empCatId) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;


    } catch(PDOException $e) {

        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});


?>
