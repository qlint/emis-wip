<?php
require '../vendor/autoload.php';
require '../lib/CorsSlim.php';
//require '../lib/password.php';
require '../lib/db.php';
require '../bootstrap.php';



/*  DEFINITIONS
URL                  						HTTP Method   	Operation
/login               						POST          	Returns users data
/getUsers(/:status)  						GET				Returns all users in client database, optional status, defaults to true, for active users
/addUser			 						POST			Creates a new user in the client database
/updateUser									PUT				Updates client user
/setUserStatus								PUT				Sets user status to active or inactive
/getSettings								GET				Gets clients settings
/updateSetting								PUT				Update settings
/getAllClasses(/:status)					GET				Get all school classes, optional status, defaults to true for active classes
/getClasses/(:classCatid/:status)			GET				Gets classes specific for class category if passed in, optional status, defaults to true for active classes
/getTeacherClasses/:teacher_id				GET				Gets classes that belong to a specific teacher
/getClassExams/:class_id(/:exam_type_id)	GET				Gets exams associated with a specific class, can be limited by exam type
/addClass									POST			Creates a new class
/updateClass								PUT				Updates a class
/setClassStatus								PUT				Sets active status of class to true/false
/getClassCats(/:teacher_id)					GET				Gets class categories, option to restrict based on specific teacher
/addClassCat								POST			Creates new class category
/updateClassCat								PUT				Updates class category
/setClassCatStatus							PUT				Sets active status of class category to true/false
/getClassCatsSummary						GET				Returns summary of the number of students associated in each class category
/getCountries								GET				Returns list of countries
/getDepartments(/:status)					GET				Returns list of departments, optional status, defaults to true for active departments
/addDepartment								POST			Creates department
/updateDepartment							PUT				Update department
/setDeptStatus								PUT				Sets active status of department to true/false
/getDeptSummary								GET				Returns summary of the number of employees associated with each department
/getGrading									GET				Returns list of grading
/addGrading									POST			Creates grading
/updateGrading								PUT				Updates grading
/getAllEmployees(/:status)					GET
/getEmployee/:id							GET
/addEmployee								POST
/updateEmployee								PUT	
/getAllTeachers(/:status)					GET
/getEmployeeDetails/:empId					GET
/getEmployeeCats							GET
/addEmployeeCat								POST
/updateEmployeeCat							PUT
/setEmployeeCatStatus						PUT
/getTerms(/:year)							GET
/getCurrentTerm								GET
/getNextTerm								GET
/addTerm									POST
/updateTerm									PUT
/getSubjects/(:classCatId)					GET
/addSubject									POST
/updateSubject								PUT
/setSubjectStatus							PUT	
/getExamTypes(/:class_cat_id)				GET


*/



// Define routes
// ************** Login  ****************** //
$app->post('/login', function () use($app) {
	// Log user in
		$allPostVars = $app->request->post();
		$username = $allPostVars['user_name'];
		$pwd = $allPostVars['user_pwd'];
		
		//$hash = password_hash($pwd, PASSWORD_BCRYPT);
	 
		try 
		{
			$db = getDB();
			$sth = $db->prepare("SELECT user_id, username, users.active, users.first_name, users.middle_name, users.last_name, users.email, 
										user_type, emp_id, emp_cat_id, dept_id
									FROM app.users 
									LEFT JOIN app.employees ON users.user_id = employees.login_id
									WHERE username= :username 
									AND password = :password 
									AND users.active is true");
			$sth->execute( array(':username' => $username, ':password' => $pwd) );
	 
			$result = $sth->fetch(PDO::FETCH_OBJ);

			if($result) {
				$app->response->setStatus(200);
				$app->response()->headers->set('Content-Type', 'application/json');
				
				// get the users settings and add to result
				$sth2 = $db->prepare("SELECT name, value FROM app.settings");
				$sth2->execute();
				$settings = $sth2->fetchAll(PDO::FETCH_OBJ);
				$result->settings = $settings;			
				
				echo json_encode(array('response' => 'success', 'data' => $result ));
				$db = null;
			} else {
				throw new PDOException('The username or password you have entered is incorrect.');
			}
	 
		} catch(PDOException $e) {
			$app->response()->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		}
});

// ************** Users  ****************** //
$app->get('/getUsers(/:status)', function ($status = true) {
    //Show users
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, 
									first_name, middle_name, last_name, email, username, user_type, user_id, active
							FROM app.users
							WHERE active = :status
							ORDER BY user_name"); 
       $sth->execute( array(':status' => $status ) );
 
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

$app->post('/addUser', function () use($app) {
    // Add user
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$firstName =		( isset($allPostVars['first_name']) ? $allPostVars['first_name']: null);
	$middleName =		( isset($allPostVars['middle_name']) ? $allPostVars['middle_name']: null);
	$lastName =			( isset($allPostVars['last_name']) ? $allPostVars['last_name']: null);
	$username =			( isset($allPostVars['username']) ? $allPostVars['username']: null);
	$password =			( isset($allPostVars['password']) ? $allPostVars['password']: null);
	$email =			( isset($allPostVars['email']) ? $allPostVars['email']: null);
	$userType =			( isset($allPostVars['user_type']) ? $allPostVars['user_type']: null);
	$currentUserId =	( isset($allPostVars['currnet_user_id']) ? $allPostVars['currnet_user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.users(first_name, middle_name, last_name, username, password, email, user_type, created_by) 
								VALUES(:firstName, :middleName, :lastName, :username, :password, :email, :userType, :currentUserId)");
 
        $sth->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName,
							 ':username' => $username, ':password' => $password, ':email' => $email, ':userType' => $userType, 
							 ':currentUserId' => $currentUserId	) );
 
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

$app->put('/updateUser', function () use($app) {
    // Update user
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$firstName =		( isset($allPostVars['first_name']) ? $allPostVars['first_name']: null);
	$middleName =		( isset($allPostVars['middle_name']) ? $allPostVars['middle_name']: null);
	$lastName =			( isset($allPostVars['last_name']) ? $allPostVars['last_name']: null);
	$username =			( isset($allPostVars['username']) ? $allPostVars['username']: null);
	$password =			( isset($allPostVars['password']) ? $allPostVars['password']: null);
	$email =			( isset($allPostVars['email']) ? $allPostVars['email']: null);
	$userType =			( isset($allPostVars['user_type']) ? $allPostVars['user_type']: null);
	$currentUserId =	( isset($allPostVars['currnet_user_id']) ? $allPostVars['currnet_user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.users
			SET first_name = :firstName,
				middle_name = :middleName,
				last_name = :lastName,
				email = :email,
				password = :password,
				user_type = :userType,
				active = true,
				modified_date = now(),
				modified_by = :currentUserId
            WHERE user_id = :userId");
 
        $sth->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName,
							 ':password' => $password, ':email' => $email, ':userType' => $userType, 
							 ':currentUserId' => $currentUserId, ':userId' => $userId	) );
 
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

$app->put('/setUserStatus', function () use($app) {
    // Update user status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$status =			( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$currentUserId =	( isset($allPostVars['currnet_user_id']) ? $allPostVars['currnet_user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.users
							SET active = :status,
								modified_date = now(),
								modified_by = :currentUserId 
							WHERE user_id = :userId
							"); 
        $sth->execute( array(':userId' => $userId, 
							 ':status' => $status, 
							 ':currentUserId' => $currentUserId
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


// ************** Settings  ****************** //
$app->get('/getSettings', function () {
    //Show settings
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
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

$app->put('/updateSetting', function () use($app) {
	$allPostVars = json_decode($app->request()->getBody(),true);
	$name = ( isset($allPostVars['name']) ? $allPostVars['name']: null);
	$value = ( isset($allPostVars['value']) ? $allPostVars['value']: null);
	$append = ( isset($allPostVars['append']) ? $allPostVars['append']: null);
	try 
    {
        $db = getDB();
		// check if setting exists
		
		$query = $db->prepare("SELECT * FROM app.settings WHERE name = :name");
		$query->execute( array(':name' => $name ) );
		$results = $query->fetch(PDO::FETCH_OBJ);
		
		if( $results )
		{
			// update, append new value to end if append is true
			if( $append) $value = $results->value . ',' . $value;
			$sth = $db->prepare("UPDATE app.settings
				SET value = :value
				WHERE name = :name");
	 
			$sth->execute( array(':name' => $name, ':value' => $value ) );	
		}
		else
		{
			// add
			$sth = $db->prepare("INSERT INTO app.settings(name, value)
								VALUES(:name,:value)");
	 
			$sth->execute( array(':name' => $name, ':value' => $value ) );	
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

$app->put('/updateSettings', function () use($app) {
	$allPostVars = json_decode($app->request()->getBody(),true);
	$settings = ( isset($allPostVars['settings']) ? $allPostVars['settings']: null);

	
	try 
    {
        $db = getDB();
		// check if setting exists
		
		$query = $db->prepare("SELECT * FROM app.settings WHERE name = :name");
		
		$insert = $db->prepare("INSERT INTO app.settings(name, value)
									VALUES(:name,:value)");
									
		$update = $db->prepare("UPDATE app.settings
					SET value = :value
					WHERE name = :name");
		
		foreach($settings as $setting)
		{
			$name = ( isset($setting['name']) ? $setting['name']: null);
			$value = ( isset($setting['value']) ? $setting['value']: null);
			$append = ( isset($setting['append']) ? $setting['append']: null);
			
			if( $value !== null )
			{
				$query->execute( array(':name' => $name ) );
			
				$results = $query->fetch(PDO::FETCH_OBJ);
				
				if( $results )
				{
					// update, append new value to end if append is true
					if( $append ) $value = $results->value . ',' . $value;		 
					$update->execute( array(':name' => $name, ':value' => $value ) );	
				}
				else
				{
					// add
					$insert->execute( array(':name' => $name, ':value' => $value ) );	
				}
			}
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


// ************** Classes  ****************** //
$app->get('/getAllClasses(/:status)', function ($status = true) {
    //Show all classes
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT class_id, class_name, class_cat_id, teacher_id, active, report_card_type
            FROM app.classes
            WHERE active = :status
			ORDER BY sort_order"); 
       $sth->execute( array(':status' => $status ) );
 
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

$app->get('/getClasses/(:classCatid/:status)', function ($classCatid = null, $status=true) {
    //Show classes for specific class category
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$params = array(':status' => $status);
		$query = "SELECT class_id, class_name, classes.class_cat_id, teacher_id, classes.active, class_cat_name,
					classes.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, report_card_type,
					(select array_agg(subject_name order by sort_order) 
						from (
							select subject_name, sort_order
							from app.class_subjects 
							inner join app.subjects on class_subjects.subject_id = subjects.subject_id
							where class_subjects.class_id = classes.class_id 
							group by subject_name, sort_order
						)a ) as subjects
            FROM app.classes
			INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
			LEFT JOIN app.employees ON classes.teacher_id = employees.emp_id
            WHERE classes.active = :status
			";
			
		if( $classCatid !== null )
		{
			$query .= "AND classes.class_cat_id = :classCatid";
			$params[':classCatid'] = $classCatid;
		}
		
		$query .= " ORDER BY sort_order";
		
        $sth = $db->prepare($query);
        $sth->execute( $params );
 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
		
			foreach( $results as $result)
			{
				$result->subjects = pg_array_parse($result->subjects);
			}
			
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

$app->get('/getTeacherClasses/:teacher_id', function ($teacherId) {
    //Show classes for specific teacher
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$sth = $db->prepare("SELECT class_id, class_name, classes.class_cat_id, teacher_id, classes.active, class_cat_name,
					classes.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name,
					(select array_agg(distinct subject_name) from app.class_subjects inner join app.subjects using (subject_id) where class_subjects.class_id = classes.class_id) as subjects
					FROM app.classes
					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
					LEFT JOIN app.employees ON classes.teacher_id = employees.emp_id
					WHERE teacher_id = :teacherId
					ORDER BY sort_order"); 
		
        $sth->execute( array(':teacherId' => $teacherId));
 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {		
			foreach( $results as $result)
			{
				$result->subjects = pg_array_parse($result->subjects);
			}
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

$app->get('/getAllClassExams/:class_id(/:exam_type_id)', function ($classId, $examTypeId = null) {
    //Return all associated class subject exams, including the parent subjects
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$query = "SELECT class_sub_exam_id, class_subjects.class_subject_id, class_subjects.subject_id, 
								subject_name, class_subject_exams.exam_type_id, exam_type, grade_weight, parent_subject_id,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1) as parent_subject_name,
								case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id limit 1) is null then false else true end as is_parent
							FROM app.class_subjects 
							INNER JOIN app.class_subject_exams
								INNER JOIN app.exam_types
								ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							ON class_subjects.class_subject_id = class_subject_exams.class_subject_id
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							WHERE class_id = :classId
							";
		$params = array(':classId' => $classId);
		if( $examTypeId !== null )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId";
			$params[':examTypeId'] = $examTypeId; 
		}
		$query .= " ORDER BY exam_types.sort_order, subjects.sort_order";
		
        $sth = $db->prepare($query);
        $sth->execute( $params  );
 
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

$app->get('/getClassExams/:class_id(/:exam_type_id)', function ($classId, $examTypeId = null) {
    //Returns only the class subject exams that the user will enter exam marks for
	// parent subjects are not returned, these exam mark totals are calculated based on their children subjects
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$query = "SELECT class_sub_exam_id, class_subjects.class_subject_id, class_subjects.subject_id, 
								subject_name, class_subject_exams.exam_type_id, exam_type, grade_weight, parent_subject_id,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1) as parent_subject_name
							FROM app.class_subjects 
							INNER JOIN app.class_subject_exams
								INNER JOIN app.exam_types
								ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							ON class_subjects.class_subject_id = class_subject_exams.class_subject_id
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							WHERE class_id = :classId
							AND (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id limit 1) is null 
							";
		$params = array(':classId' => $classId);
		if( $examTypeId !== null )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId";
			$params[':examTypeId'] = $examTypeId; 
		}
		$query .= " ORDER BY exam_types.sort_order, subjects.sort_order";
		
        $sth = $db->prepare($query);
        $sth->execute( $params  );
 
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

$app->post('/addClass', function () use($app) {
    // Add class
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$className = 		( isset($allPostVars['class_name']) ? $allPostVars['class_name']: null);
	$classCatId = 		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId = 		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$reportCardType = 	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$subjects =  		( isset($allPostVars['subjects']) ? $allPostVars['subjects']: null);	
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.classes(class_name, class_cat_id, teacher_id, created_by, report_card_type) 
            VALUES(:className, :classCatId, :teacherId, :userId, :reportCardType)");
 		
		if( count($subjects) > 0 )
		{
			$sth2 = $db->prepare("INSERT INTO app.class_subjects(class_id, subject_id, created_by)
									VALUES(currval('app.classes_class_id_seq'), :subjectId, :userId)");
			
			$sth3 = $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
									VALUES(currval('app.class_subjects_class_subject_id_seq'), :examTypeId, :gradeWeight, :userId)");
		}
		
		$db->beginTransaction();
		
		$sth->execute( array(':className' => $className, ':classCatId' => $classCatId, ':teacherId' => $teacherId, ':userId' => $userId, ':reportCardType' => $reportCardType ) );
		
		if( count($subjects) > 0 )
		{
			foreach( $subjects as $subject )
			{
				$subjectId = ( isset($subject['subject_id']) ? $subject['subject_id']: null);
				$sth2->execute( array(':subjectId' => $subjectId, ':userId' => $userId ) );
				
				if( count($subject['exams']) > 0 )
				{
					foreach( $subject['exams'] as $exam )
					{
						$examTypeId = ( isset($exam['exam_type_id']) ? $exam['exam_type_id']: null);
						$gradeWeight = ( isset($exam['grade_weight']) ? $exam['grade_weight']: null);
						
						$sth3->execute( array(':examTypeId' => $examTypeId, ':gradeWeight' => $gradeWeight, ':userId' => $userId ) );

					}
				}
				
			}
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

$app->put('/updateClass', function () use($app) {
    // Update class
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classId = 			( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$className = 		( isset($allPostVars['class_name']) ? $allPostVars['class_name']: null);
	$classCatId = 		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId = 		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$reportCardType = 	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$subjects =  		( isset($allPostVars['subjects']) ? $allPostVars['subjects']: null);	
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $updateClass = $db->prepare("UPDATE app.classes
			SET class_name = :className,
				class_cat_id = :classCatId,
				teacher_id = :teacherId,
				report_card_type = :reportCardType,
				active = true,
				modified_date = now(),
				modified_by = :userId
            WHERE class_id = :classId");
			
		
		$inactivateSubject =  $db->prepare("UPDATE app.class_subjects 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
		
		$inactivateAllSubjectExams = $db->prepare( "UPDATE app.class_subject_exams 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
								
		if( count($subjects) > 0 )
		{
			$insertSubject =  $db->prepare("INSERT INTO app.class_subjects(class_id,subject_id,created_by)
							VALUES(:classId,:subjectId,:userId)");
		
			$updateExam =  $db->prepare("UPDATE app.class_subject_exams 
							SET grade_weight = :gradeWeight,
								modified_date =  now(),
								modified_by = :userId
							WHERE class_sub_exam_id = :classSubExamId");
			
			$insertNewSubjectExam =  $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
							VALUES(currval('app.class_subjects_class_subject_id_seq'), :examTypeId, :gradeWeight, :userId)");
							
			$insertExam =  $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
							VALUES(:classSubjectId, :examTypeId, :gradeWeight, :userId)");
			
			$inactivateExam = $db->prepare( "UPDATE app.class_subject_exams 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_sub_exam_id = :classSubExamId");
			
			
			
								
		}
		
		// pull out existing class subjects	    
	    $query = $db->prepare("SELECT class_subject_id FROM app.class_subjects WHERE class_id = :classId");
		$query->execute( array(':classId' => $classId ) );
		$currentSubjects = $query->fetchAll(PDO::FETCH_OBJ);
		
		// pull out existing class subject exams  
	    $query = $db->prepare("SELECT class_sub_exam_id FROM app.class_subject_exams INNER JOIN app.class_subjects USING (class_subject_id) WHERE class_id = :classId");
		$query->execute( array(':classId' => $classId ) );
		$currentSubjectExams = $query->fetchAll(PDO::FETCH_OBJ);
					
		$db->beginTransaction();
		
        $updateClass->execute( array(':className' => $className, 
									 ':classCatId' => $classCatId, 
									 ':teacherId' => $teacherId, 
									 ':reportCardType' => $reportCardType,
									 ':userId' => $userId,
									 ':classId' => $classId) );
		
		if( count($subjects) > 0 )
		{
			foreach($subjects as $subject)
			{
				$classSubjectId = ( isset($subject['class_subject_id']) ? $subject['class_subject_id']: null);
				$subjectId = ( isset($subject['subject_id']) ? $subject['subject_id']: null);
				$exams =	( isset($subject['exams']) ? $subject['exams']: null);

				if( $classSubjectId === null )
				{
					$insertSubject->execute( array(':classId' => $classId,
												':subjectId' => $subjectId, 					 
												':userId' => $userId
					) );
				}
				
				// check exams?
				if( count($exams) > 0 )
				{
					foreach($exams as $exam)
					{
						$examTypeId =		( isset($exam['exam_type_id']) ? $exam['exam_type_id']: null);
						$gradeWeight =		( isset($exam['grade_weight']) ? $exam['grade_weight']: null);
						$classSubExamId =	( isset($exam['class_sub_exam_id']) ? $exam['class_sub_exam_id']: null);
						$classSubjectId =	( isset($exam['class_subject_id']) ? $exam['class_subject_id']: null);
						
						if( $classSubExamId !== null )			
						{
							$updateExam->execute(array(':gradeWeight' => $gradeWeight, ':classSubExamId' => $classSubExamId, ':userId' => $userId));
						}
						else
						{
							// if no class subject id, then subject was new, use new seq value
							if( $classSubjectId === null )			
							{
								$insertNewSubjectExam->execute( array(':examTypeId' => $examTypeId,		
															':gradeWeight' => $gradeWeight,
															':userId' => $userId
								) );
							}
							else
							{
								$insertExam->execute( array(':classSubjectId' => $classSubjectId, 	
															':examTypeId' => $examTypeId,		
															':gradeWeight' => $gradeWeight,
															':userId' => $userId
								) );
							}
						}
					}
					
					// set active to false for any not passed in
					
					foreach( $currentSubjectExams as $currentSubjectExam )
					{	
						$deleteMe = true;
						// if found, do not delete
						foreach( $exams as $exam )
						{
							if( isset($exam['class_sub_exam_id']) && $exam['class_sub_exam_id'] == $currentSubjectExam->class_sub_exam_id )
							{
								$deleteMe = false;
							}
						}
						
						if( $deleteMe )
						{
							$inactivateExam->execute(array(':classSubExamId' => $currentSubjectExam->class_sub_exam_id, ':userId' => $userId));								
						}
					}
					
				}
				
			}
		   
			// set active to false for any not passed in
			
			foreach( $currentSubjects as $currentSubject )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $subjects as $subject )
				{
					if( isset($subject['class_subject_id']) && $subject['class_subject_id'] == $currentSubject->class_subject_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$inactivateSubject->execute(array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId));
					
					// if subject was marked inactive, mark exams inactive as well
					$inactivateAllSubjectExams->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) );
					
				}
			}
			
			
		}
		else
		{
			// no subjects, remove any associated with class
			// if subject was marked inactive, mark exams inactive as well
			foreach( $currentSubjects as $currentSubject )
			{	
				$inactivateSubject->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) );
				$inactivateAllSubjectExams->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) ); 
			}

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

$app->put('/setClassStatus', function () use($app) {
    // Update class status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classId =	( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.classes
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE class_id = :classId
							"); 
        $sth->execute( array(':classId' => $classId, 
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

$app->put('/setClassSortOrder', function () use($app) {
    // Update class sort order
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.classes
							SET sort_order = :sortOrder,
								modified_date = now(),
								modified_by = :userId 
							WHERE class_id = :classId
							"); 
							
		$db->beginTransaction();
		foreach( $sortData as $item )
		{
			$classId =		( isset($item['class_id']) ? $item['class_id']: null);
			$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
			$sth->execute( array(':classId' => $classId, 
							 ':sortOrder' => $sortOrder, 
							 ':userId' => $userId
					) );
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

// ************** Class Categories  ****************** //
$app->get('/getClassCats(/:teacher_id)', function ($teacherId=null) {
    //Show all class categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		if( $teacherId !== null )
		{
			$sth = $db->prepare("SELECT class_cats.class_cat_id, class_cat_name 
								FROM app.class_cats 
								INNER JOIN app.classes ON class_cats.class_cat_id = classes.class_cat_id AND teacher_id = :teacherId
								WHERE class_cats.active is true 
								ORDER BY class_cats.class_cat_id");
			$sth->execute(array(':teacherId' => $teacherId));
		}
		else
		{
			$sth = $db->prepare("SELECT class_cat_id, class_cat_name 
								FROM app.class_cats 
								WHERE active is true 
								ORDER BY class_cat_id");
			$sth->execute();
		}
		
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

$app->post('/addClassCat', function () use($app) {
    // Add class category
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classCatName = ( isset($allPostVars['class_cat_name']) ? $allPostVars['class_cat_name']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth1 = $db->prepare("INSERT INTO app.class_cats(class_cat_name,created_by) 
            VALUES(:classCatName,:userId)"); 
			
		$sth2 = $db->prepare("SELECT currval('app.class_cats_class_cat_id_seq') as class_cat_id");	
		
		$db->beginTransaction();
		$sth1->execute( array(':classCatName' => $classCatName, ':userId' => $userId) );
		$sth2->execute();
		$classCatId = $sth2->fetch(PDO::FETCH_OBJ);
		$db->commit();		
        
 
		$result = new stdClass();
		$result->class_cat_id = $classCatId->class_cat_id;
		$result->class_cat_name = $classCatName;
	
		if( $classCatId )
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

$app->put('/updateClassCat', function () use($app) {
    // Update class cat
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classCatId = ( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$classCatName = ( isset($allPostVars['class_cat_name']) ? $allPostVars['class_cat_name']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.class_cats
							SET class_cat_name = :classCatName,
								modified_date = now(),
								modified_by = :userId 
							WHERE class_cat_id = :classCatId
							"); 
        $sth->execute( array(':classCatId' => $classCatId, 
							 ':classCatName' => $classCatName, 
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

$app->put('/setClassCatStatus', function () use($app) {
    // Update class cat status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classCatId = ( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.class_cats
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE class_cat_id = :classCatId
							"); 
        $sth->execute( array(':classCatId' => $classCatId, 
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

$app->get('/getClassCatsSummary', function () {
    //Show all class categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT class_cat_id, class_cat_name,
							(select count(*) 
								from app.students 
								inner join app.classes 
								on students.current_class = classes.class_id 
								where class_cat_id = class_cats.class_cat_id) as num_students
							FROM app.class_cats 
							WHERE active is true
							ORDER BY class_cat_id");
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


// ************** Countries  ****************** //
$app->get('/getCountries', function () {
    // Get countries
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT countries_name FROM app.countries ORDER BY countries_name");
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


// ************** Departments  ****************** //
$app->get('/getDepartments(/:status)', function ($status=true) {
    //Show all departments
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT dept_id, dept_name, active, category
            FROM app.departments
            WHERE active = :status
			ORDER BY dept_id");
        $sth->execute( array(':status' => $status ) );
 
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

$app->post('/addDepartment', function () use($app) {
    // Add department
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$deptName =	( isset($allPostVars['dept_name']) ? $allPostVars['dept_name']: null);
	$category =	( isset($allPostVars['category']) ? $allPostVars['category']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.departments(dept_name, category, created_by) 
            VALUES(:deptName, :category, :userId)");
 
        $sth->execute( array(':deptName' => $deptName, ':category' => $category, ':userId' => $userId ) );
 
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

$app->put('/updateDepartment', function () use($app) {
    // Update department
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$deptId =	( isset($allPostVars['dept_id']) ? $allPostVars['dept_id']: null);
	$deptName =	( isset($allPostVars['dept_name']) ? $allPostVars['dept_name']: null);
	$category =	( isset($allPostVars['category']) ? $allPostVars['category']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.departments
			SET dept_name = :deptName,
				category = :category,
				active = true
            WHERE dept_id = :deptId");
 
        $sth->execute( array(':deptName' => $deptName, ':deptId' => $deptId, ':category' => $category ) );
 
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

$app->put('/setDeptStatus', function () use($app) {
    // Update department status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$deptId =	( isset($allPostVars['dept_id']) ? $allPostVars['dept_id']: null);
	$status =		( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.departments
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE dept_id = :deptId
							"); 
        $sth->execute( array(':deptId' => $deptId, 
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

$app->get('/getDeptSummary', function () {
    //Show all class categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT dept_id, dept_name, active, category,
									(select count(*) 
								from app.employees 
								where dept_id = departments.dept_id) as num_staff
								FROM app.departments
								WHERE active is true
								ORDER BY dept_id");
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


// ************** Grading  ****************** //
$app->get('/getGrading', function () {
    //Show grading
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT *
            FROM app.grading
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

$app->post('/addGrading', function () use($app) {
    // Add grading
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$grade =	( isset($allPostVars['grade']) ? $allPostVars['grade']: null);
	$markMin =	( isset($allPostVars['min_mark']) ? $allPostVars['min_mark']: null);
	$markMax =	( isset($allPostVars['max_mark']) ? $allPostVars['max_mark']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.grading(grade, min_mark, max_mark) 
            VALUES(:grade, :markMin, :markMax)");
 
        $sth->execute( array(':grade' => $grade, ':markMin' => $markMin, ':markMax' => $markMax ) );
 
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

$app->put('/updateGrading', function () use($app) {
    // Update grading
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$gradeId =	( isset($allPostVars['grade_id']) ? $allPostVars['grade_id']: null);
	$grade =	( isset($allPostVars['grade']) ? $allPostVars['grade']: null);
	$markMin =	( isset($allPostVars['min_mark']) ? $allPostVars['min_mark']: null);
	$markMax =	( isset($allPostVars['max_mark']) ? $allPostVars['max_mark']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.grading
			SET grade = :grade,
				min_mark = :markMin,
				max_mark = :markMax
            WHERE grade_id = :gradeId");
 
        $sth->execute( array(':grade' => $grade, ':markMin' => $markMin, ':markMax' => $markMax, ':gradeId' => $gradeId ) );
 
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


// ************** Employees  ****************** //
$app->get('/getAllEmployees(/:status)', function ($status=true) {
    //Show all employees
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT emp_id, employees.emp_cat_id, employees.dept_id, emp_number, id_number, gender, first_name,
									middle_name, last_name, initials, dob, country, employees.active, telephone, email, joined_date,
									job_title, qualifications, experience, additional_info, emp_image,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as employee_name,
									emp_cat_name, dept_name
							 FROM app.employees
							 INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id
							 INNER JOIN app.departments ON employees.dept_id = departments.dept_id
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

$app->get('/getEmployee/:id', function () {
    //Show specific employee
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT  emp_id, emp_cat_id, dept_id, emp_number, id_number, gender, first_name,
									middle_name, last_name, initials, dob, country, active, telephone, email, joined_date,
									job_title, qualifications, experience, additional_info, emp_image
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
										next_of_kin_name, next_of_kin_telephone, next_of_kin_email) 
							VALUES(:empCatId,:deptId,:empNumber,:idNumber,:gender,:firstName,:middleName,:lastName,:initials,:dob,
										:country,:active,:telephone,:email,:joinedDate,:jobTitle,:qualifications,:experience,:additionalInfo,:createdBy,:empImage,
										:nokName, :nokTelephone, :nokEmail)"); 
		
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
		$email =			( isset($allPostVars['personal']['email']) ? $allPostVars['personal']['email']: null);
		$empImage =			( isset($allPostVars['personal']['emp_image']) ? $allPostVars['personal']['emp_image']: null);
		$nokName =			( isset($allPostVars['personal']['next_of_kin_name']) ? $allPostVars['personal']['next_of_kin_name']: null);
		$nokTelephone =		( isset($allPostVars['personal']['next_of_kin_telephone']) ? $allPostVars['personal']['next_of_kin_telephone']: null);
		$nokEmail =			( isset($allPostVars['personal']['next_of_kin_email']) ? $allPostVars['personal']['next_of_kin_email']: null);
		$updatePersonal = true;
	}
	if( isset($allPostVars['employee']) )
	{
		$empNumber =		( isset($allPostVars['employee']['emp_number']) ? $allPostVars['employee']['emp_number']: null);
		$empCatId =			( isset($allPostVars['employee']['emp_cat_id']) ? $allPostVars['employee']['emp_cat_id']: null);
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
		$loginActive =		( isset($allPostVars['employee']['login_active']) ? $allPostVars['employee']['login_active']: null);
		$loginId =			( isset($allPostVars['employee']['login_id']) ? $allPostVars['employee']['login_id']: null);
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
					email = :email,
					emp_image = :empImage,
					next_of_kin_name = :nokName,
					next_of_kin_telephone = :nokTelephone,
					next_of_kin_email = :nokEmail,
					modified_date = now(),
					modified_by = :userId
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
								':email' => $email,
								':empImage' => $empImage,
								':nokName' => $nokName,
								':nokTelephone' => $nokTelephone,
								':nokEmail' => $nokEmail,
								':userId' => $userId
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
					modified_by = :userId
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
								':userId' => $userId
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
        $sth = $db->prepare("SELECT emp_id as teacher_id, emp_cat_id, dept_id, emp_number, id_number, gender, first_name,
									middle_name, last_name, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name,
									initials, dob, country, active, telephone, email, joined_date,
									job_title, qualifications, experience, additional_info, emp_image
							 FROM app.employees 
							 WHERE emp_cat_id = 1
							 AND active = :status 							 
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
									emp_cat_name, dept_name,
									(select array_agg(class_name) from app.classes where teacher_id = employees.emp_id) as classes,
									(select array_agg(subject_name) from app.subjects where teacher_id = employees.emp_id) as subjects,
									username, user_type, users.active as login_active
							 FROM app.employees 
							 INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id
							 INNER JOIN app.departments ON employees.dept_id = departments.dept_id
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


// ************** Terms  ****************** //
$app->get('/getTerms(/:year)', function ($year = null) {
    //Show all terms for given year (or this year if null)
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
		if( $year == null )
		{
			$query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name, start_date, end_date,
										  case when term_id = (select term_id from app.current_term) then true else false end as current_term, date_part('year',start_date) as year
										FROM app.terms
										--WHERE date_part('year',start_date) <= date_part('year',now())
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

$app->get('/getCurrentTerm', function () {
    //Get current term
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
	
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

$app->get('/getNextTerm', function () {
    //Get next term
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
	
		$query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.next_term");
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

$app->post('/addTerm', function () use($app) {
    // Add term
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$termName =		( isset($allPostVars['term_name']) ? $allPostVars['term_name']: null);
	$startDate =	( isset($allPostVars['start_date']) ? $allPostVars['start_date']: null);
	$endDate =		( isset($allPostVars['end_date']) ? $allPostVars['end_date']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.terms(term_name, start_date, end_date, created_by) 
            VALUES(:termName, :startDate, :endDate, :userId)");
 
        $sth->execute( array(':termName' => $termName, ':startDate' => $startDate, ':endDate' => $endDate, ':userId' => $userId ) );
 
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

$app->put('/updateTerm', function () use($app) {
    // Update term
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$termId =		( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$termName =		( isset($allPostVars['term_name']) ? $allPostVars['term_name']: null);
	$startDate =	( isset($allPostVars['start_date']) ? $allPostVars['start_date']: null);
	$endDate =		( isset($allPostVars['end_date']) ? $allPostVars['end_date']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.terms
			SET term_name = :termName,
				start_date = :startDate,
				end_date = :endDate
            WHERE term_id = :termId");
 
        $sth->execute( array(':termName' => $termName, ':startDate' => $startDate, ':endDate' => $endDate, ':termId' => $termId ) );
 
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


// ************** Subjects  ****************** //
$app->get('/getAllSubjects/:classCatId', function ($classCatId) {
    //Show all subjects, including parent subjects
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
		$sth = $db->prepare("SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
									teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active,
									parent_subject_id, sort_order,
									(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1) as parent_subject_name			
								FROM app.subjects
								LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
								INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id
								WHERE subjects.class_cat_id = :classCatId
								ORDER BY class_cat_name, sort_order, subject_name;
							");
							
		$sth->execute(array(':classCatId' => $classCatId));			
 
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);
 
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

$app->get('/getSubjects/:classCatId', function ($classCatId) {
    //Show only subjects that receive exam marks (children subjects)
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
		$sth = $db->prepare("SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
									teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active,
									parent_subject_id, sort_order,
									(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1) as parent_subject_name			
								FROM app.subjects
								LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
								INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id
								WHERE subjects.class_cat_id = :classCatId
								AND (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id limit 1) is null 
								ORDER BY class_cat_name, sort_order, subject_name;
							");
							
		$sth->execute(array(':classCatId' => $classCatId));			
 
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);
 
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

$app->post('/addSubject', function () use($app) {
    // Add subject
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$subjectName =		( isset($allPostVars['subject_name']) ? $allPostVars['subject_name']: null);
	$classCatId =		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$parentSubjectId =	( isset($allPostVars['parent_subject_id']) ? $allPostVars['parent_subject_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.subjects(subject_name, class_cat_id, teacher_id, created_by, parent_subject_id) 
            VALUES(:subjectName, :classCatId, :teacherId, :userId, :parentSubjectId)");
 
        $sth->execute( array(':subjectName' => $subjectName, ':classCatId' => $classCatId, 
							 ':teacherId' => $teacherId, ':userId' => $userId, ':parentSubjectId' => $parentSubjectId,
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

$app->put('/updateSubject', function () use($app) {
    // Update subject
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$subjectId =		( isset($allPostVars['subject_id']) ? $allPostVars['subject_id']: null);
	$subjectName =		( isset($allPostVars['subject_name']) ? $allPostVars['subject_name']: null);
	$classCatId =		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$parentSubjectId =	( isset($allPostVars['parent_subject_id']) ? $allPostVars['parent_subject_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.subjects
			SET subject_name = :subjectName,
				class_cat_id = :classCatId,
				teacher_id = :teacherId,
				parent_subject_id = :parentSubjectId,
				modified_date = now(),
				modified_by = :userId
            WHERE subject_id = :subjectId");
 
        $sth->execute( array(':subjectName' => $subjectName, ':classCatId' => $classCatId, ':teacherId' => $teacherId, 
							 ':subjectId' => $subjectId, ':userId' => $userId, ':parentSubjectId' => $parentSubjectId,
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

$app->put('/setSubjectStatus', function () use($app) {
    // Update subject status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$subjectId =( isset($allPostVars['subject_id']) ? $allPostVars['subject_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.subjects
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE subject_id = :subjectId
							"); 
        $sth->execute( array(':subjectId' => $subjectId, 
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

$app->put('/setSubjectSortOrder', function () use($app) {
    // Update subject sort order
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.subjects
							SET sort_order = :sortOrder,
								modified_date = now(),
								modified_by = :userId 
							WHERE subject_id = :subjectId
							"); 
							
		$db->beginTransaction();
		foreach( $sortData as $item )
		{
			$subjectId =	( isset($item['subject_id']) ? $item['subject_id']: null);
			$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
			$sth->execute( array(':subjectId' => $subjectId, 
							 ':sortOrder' => $sortOrder, 
							 ':userId' => $userId
					) );
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


// ************** Exams  ****************** //
$app->get('/getExamTypes/:class_cat_id', function ($classCatId) {

    // Get all exam types
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		$sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
							FROM app.exam_types 
							LEFT JOIN app.class_cats
							ON exam_types.class_cat_id = class_cats.class_cat_id
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

$app->post('/addExamType', function () use($app) {
    // Add exam type
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$examType =		( isset($allPostVars['exam_type']) ? $allPostVars['exam_type']: null);
	$classCatId =	( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
		

		
		/* get the next number for sort order */		
		$sth0 = $db->prepare("SELECT sort_order FROM app.exam_types WHERE class_cat_id = :classCatId ORDER BY sort_order desc LIMIT 1");
        $sth1 = $db->prepare("INSERT INTO app.exam_types(exam_type, class_cat_id, sort_order, created_by) 
								VALUES(:examType, :classCatId, :sortOrder, :userId)"); 
		$sth2 = $db->prepare("SELECT * FROM app.exam_types WHERE exam_type_id = currval('app.exam_types_exam_type_id_seq')");
		
							
		$db->beginTransaction();
		$sth0->execute( array(':classCatId' => $classCatId) );
		$sort = $sth0->fetch(PDO::FETCH_OBJ);
		$sortOrder = ($sort ? $sort->sort_order + 1 : 1);
		
        $sth1->execute( array(':examType' => $examType, ':classCatId' => $classCatId, ':sortOrder' => $sortOrder, ':userId' => $userId ) );
		$sth2->execute();
		$results = $sth2->fetch(PDO::FETCH_OBJ);
		
		$db->commit();
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "data" => $results));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->delete('/deleteExamType/:exam_type_id', function ($examTypeId) {
    // delete exam type
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.exam_types WHERE exam_type_id = :examTypeId");		
										
		$sth->execute( array(':examTypeId' => $examTypeId) );
 
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

$app->put('/setExamTypeSortOrder', function () use($app) {
    // Update exam type sort order
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.exam_types
							SET sort_order = :sortOrder,
								modified_date = now(),
								modified_by = :userId 
							WHERE exam_type_id = :examTypeId
							"); 
							
		$db->beginTransaction();
		foreach( $sortData as $item )
		{
			$examTypeId =	( isset($item['exam_type_id']) ? $item['exam_type_id']: null);
			$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
			$sth->execute( array(':examTypeId' => $examTypeId, 
							 ':sortOrder' => $sortOrder, 
							 ':userId' => $userId
					) );
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

$app->get('/getStudentExamMarks/:student_id/:class/:term(/:type)', function ($studentId,$classId,$termId,$examTypeId=null) {
    //Get student exam marks
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get exam marks by exam type
		$queryArray = array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId);
		$query = "SELECT subject_name, mark, grade_weight, exam_type, rank, grade
					FROM (
						SELECT class_id
							  ,subject_name      
							  ,exam_type
							  ,student_id
							  ,mark          
							  ,grade_weight
							  ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade
							  ,dense_rank() over w as rank,
							  subjects.sort_order,
							  exam_types.exam_type_id
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams 
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects 
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId
						
						AND term_id = :termId
						";
						
		if( $examTypeId !== null )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId ";
								
			$queryArray[':examTypeId'] = $examTypeId; 
		}
		
		$query .= "WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY  exam_types.exam_type_id, subjects.sort_order)
				 ) q
				 where student_id = :studentId
				 ORDER BY exam_type_id, sort_order";
		
		$sth = $db->prepare($query);
		$sth->execute( $queryArray ); 
        
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

$app->get('/getClassExamMarks/:class_id/:term_id/:exam_type_id', function ($classId,$termId,$examTypeId) {
    //Show exam marks for all students in class
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT q.student_id, student_name, subject_name, q.class_sub_exam_id, mark, exam_marks.term_id, grade_weight, sort_order, parent_subject_id, subject_id, is_parent
							FROM (
								SELECT  students.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, 
									subject_name, class_subject_exams.class_sub_exam_id, grade_weight, subjects.sort_order, parent_subject_id, subjects.subject_id,
									case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id limit 1) is null then false else true end as is_parent
								FROM app.students 
								INNER JOIN app.classes 
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id
										INNER JOIN app.class_subject_exams																	
										ON class_subjects.class_subject_id = class_subject_exams.class_subject_id		
									ON classes.class_id = class_subjects.class_id									
								ON students.current_class = classes.class_id							
								WHERE current_class = :classId
								AND exam_type_id = :examTypeId							
							) q
							LEFT JOIN app.exam_marks 
								INNER JOIN app.terms 
								ON exam_marks.term_id = terms.term_id															
							ON q.class_sub_exam_id = exam_marks.class_sub_exam_id AND exam_marks.term_id = :termId AND exam_marks.student_id = q.student_id
							ORDER BY student_name, sort_order, subject_name");
        $sth->execute( array(':classId' => $classId, ':termId' => $termId, ':examTypeId' => $examTypeId)); 
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

$app->get('/getAllStudentExamMarks/:class/:term/:type', function ($classId,$termId,$examTypeId) {
    //Get all student exam marks
	$app = \Slim\Slim::getInstance();
	
    try 
    {
		// need to make sure class, term and type are integers
		if( is_numeric($classId) && is_numeric($termId)  && is_numeric($examTypeId) )
		{
			$db = getDB();
			
			$query = "select app.colpivot('_exam_marks', 'SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
							 ,class_id
							  ,subject_name     
							  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1),'''') as parent_subject_name
							  ,exam_type
							  ,exam_marks.student_id
							  ,mark          
							  ,grade_weight
							  ,subjects.sort_order
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams 
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects 
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						WHERE class_subjects.class_id = $classId
						AND term_id = $termId						
						AND class_subject_exams.exam_type_id = $examTypeId
						WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
						',
						array['student_id','student_name','exam_type'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";
			
			$query2 = "select *,
									(
										SELECT rank FROM (
											SELECT
												student_id,
												total_mark,
												dense_rank() over w as rank
											FROM (
												SELECT student_id, 
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams 
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects 
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId
												AND class_subject_exams.exam_type_id = $examTypeId
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)	
										) q
										WHERE student_id = _exam_marks.student_id
									) as rank
									from _exam_marks order by exam_type, rank;";

			$sth1 = $db->prepare($query);
			$sth2 = $db->prepare($query2);
			
			$db->beginTransaction();
			$sth1->execute(); 
			$sth2->execute();
			$results = $sth2->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
		}			
		
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
        //echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		 echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
    }

});

$app->post('/addExamMarks', function () use($app) {
    // Add exam type
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$examMarks =	( isset($allPostVars['exam_marks']) ? $allPostVars['exam_marks']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
		
		$getExam = $db->prepare("SELECT exam_id FROM app.exam_marks WHERE student_id = :studentId AND class_sub_exam_id = :classSubExamId AND term_id = :termId");
		
		$addMark = $db->prepare("INSERT INTO app.exam_marks(student_id, class_sub_exam_id, term_id, mark, created_by) 
								VALUES(:studentId, :classSubExamId, :termId, :mark, :userId)"); 
		
		$updateMark = $db->prepare("UPDATE app.exam_marks
									SET mark = :mark,
										modified_date = now(),
										modified_by = :userId
									WHERE exam_id = :examId"); 
									
		$deleteMark = $db->prepare("DELETE FROM app.exam_marks WHERE exam_id = :examId");
		
		$db->beginTransaction();
		
		if( count($examMarks) > 0 )
		{
			foreach($examMarks as $mark)
			{
				$studentId = ( isset($mark['student_id']) ? $mark['student_id']: null);				
				$classSubExamId = ( isset($mark['class_sub_exam_id']) ? $mark['class_sub_exam_id']: null);
				$termId = ( isset($mark['term_id']) ? $mark['term_id']: null);
				$mark = ( isset($mark['mark']) && !empty($mark['mark']) ? $mark['mark']: null);
				
				$currentExamMarks = $getExam->execute(array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId ));
				$examId = $getExam->fetch(PDO::FETCH_OBJ);
				
				if( $examId )
				{
					$updateMark->execute( array(':mark' => $mark, ':examId' => $examId->exam_id, ':userId' => $userId  ) );
				}
				else
				{
					$addMark->execute( array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId, ':mark' => $mark, ':userId' => $userId ) );
				}
			}
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

$app->get('/getTopStudents(/:class_id)', function ($classId=null) {
    //Get all top 3 students for each class, or requested class
	
	$app = \Slim\Slim::getInstance();
	
    try 
    {
		
		$db = getDB();
		$queryParams = array();
		$query = "SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
							total_mark, total_grade_weight, rank, percentage, 
								(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
								position_out_of
							FROM (
							SELECT
								 student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_id
									  ,class_name
									  ,total_mark    
									  ,total_grade_weight
									  ,round((total_mark/total_grade_weight)*100) as percentage
									  ,dense_rank() over w as rank
									  ,position_out_of
							FROM (
								SELECT    
									 exam_marks.student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_subjects.class_id
									  ,class_name
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										mark
										end),0) as total_mark
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										grade_weight
									   end),0) as total_grade_weight									  
									  ,(select count(*) from app.students where active is true and current_class = students.current_class) as position_out_of
								FROM app.exam_marks
								INNER JOIN app.students
								ON exam_marks.student_id = students.student_id
								INNER JOIN app.class_subject_exams 
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE term_id = (select term_id from app.current_term)
					";
			
		if( $classId !== null  )
		{
			$query .= " AND class_subjects.class_id = :classId ";
			$queryParams = array(':classId' => $classId );
		}
		
		$query .= " 	GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name
					) a
								WINDOW w AS (PARTITION BY class_id ORDER BY class_id desc, total_mark desc)								
							 ) q
							 WHERE rank < 4";
		
		//echo $query;

		$sth = $db->prepare($query);
		$sth->execute($queryParams); 
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
        //echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		$app->response()->headers->set('Content-Type', 'application/json');
		 echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
    }

});


// ************** Report Cards  ****************** //
$app->get('/getAllStudentReportCards/:class_id', function ($classId) {
    //Get report cards for class
	
	$app = \Slim\Slim::getInstance();
	
    try 
    {

		$db = getDB();
		
		$sth = $db->prepare("SELECT report_cards.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, admission_number,
									report_cards.class_id, class_cat_id,
									class_name, report_cards.term_id, term_name, date_part('year', start_date) as year, report_data, report_cards.report_card_type			
							FROM app.report_cards
							INNER JOIN app.students ON report_cards.student_id = students.student_id
							INNER JOIN app.classes ON report_cards.class_id = classes.class_id
							INNER JOIN app.terms ON report_cards.term_id = terms.term_id
							WHERE report_cards.class_id = :classId
							ORDER BY student_id");
		
		$sth->execute( array($classId) ); 
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
        //echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		$app->response()->headers->set('Content-Type', 'application/json');
		 echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
    }

});

$app->get('/getStudentReportCards/:student_id', function ($studentId) {
    //Get student report cards
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		$sth = $db->prepare("SELECT report_cards.student_id, report_cards.class_id, class_name, term_name, report_cards.term_id,
									date_part('year', start_date) as year, report_data, report_cards.report_card_type, class_cat_id
					FROM app.report_cards
					INNER JOIN app.students ON report_cards.student_id = students.student_id
					INNER JOIN app.classes ON report_cards.class_id = classes.class_id
					INNER JOIN app.terms ON report_cards.term_id = terms.term_id
					WHERE report_cards.student_id = :studentId
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
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentReportCard/:student_id/:class_id/:term_id', function ($studentId, $classId, $termId) {
    //Get student report card
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();

		$sth = $db->prepare("SELECT report_cards.student_id, class_name, term_name, report_cards.term_id, date_part('year', start_date) as year, report_data, report_cards.report_card_type
					FROM app.report_cards
					INNER JOIN app.students ON report_cards.student_id = students.student_id
					INNER JOIN app.classes ON report_cards.class_id = classes.class_id
					INNER JOIN app.terms ON report_cards.term_id = terms.term_id
					WHERE report_cards.student_id = :studentId
					AND report_cards.class_id = :classId
					AND report_cards.term_id = :termId");
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
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getExamMarksforReportCard/:student_id/:class/:term', function ($studentId,$classId,$termId) {
    //Get student exam marks
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get exam marks by exam type
		$sth = $db->prepare("SELECT subject_name, mark, grade_weight, exam_type, rank, grade, parent_subject_name
					FROM (
						SELECT class_id
							  ,subject_name      
							  ,exam_type
							  ,student_id
							  ,mark          
							  ,grade_weight
							  ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade
							  ,dense_rank() over w as rank,
							  subjects.sort_order,
							  exam_types.exam_type_id,
							  (select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1) as parent_subject_name
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams 
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects 
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId
						AND term_id = :termId
						WINDOW w AS (PARTITION BY class_subjects.subject_id, class_subject_exams.exam_type_id ORDER BY subjects.sort_order, exam_types.exam_type_id)
				 ) q
				 where student_id = :studentId
				 ORDER BY sort_order, exam_type_id");
		$sth->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) ); 
		$details = $sth->fetchAll(PDO::FETCH_OBJ);
		

		// get overall marks per subjects, only use parent subjects
		$sth2 = $db->prepare("SELECT subject_name, total_mark, total_grade_weight, rank, percentage, 
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade
							FROM (
							SELECT
								class_id, subject_id
										  ,subject_name      
										  ,student_id
										  ,total_mark    
										  ,total_grade_weight
										  ,round(total_mark::float/total_grade_weight::float*100) as percentage
										  ,dense_rank() over w as rank
										  ,sort_order
							FROM (
									SELECT class_id
									      ,class_subjects.subject_id
										  ,subject_name      
										  ,student_id
										  ,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
										  ,coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight										  
										  ,subjects.sort_order
									FROM app.exam_marks
									INNER JOIN app.class_subject_exams 
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
									WHERE class_subjects.class_id = :classId
									AND term_id = :termId
									AND subjects.parent_subject_id is null
									GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order
								) a
								WINDOW w AS (PARTITION BY subject_id ORDER BY sort_order, total_mark desc)								
							 ) q
							 where student_id = :studentId
							 ORDER BY sort_order");
		$sth2->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) ); 
		$subjectOverall = $sth2->fetchAll(PDO::FETCH_OBJ);
		
		// get overall position
		$sth3 = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage, 
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight, 
										round(total_mark::float/total_grade_weight::float*100) as percentage,
										dense_rank() over w as rank, position_out_of
									FROM (
										SELECT    
											  student_id
											  ,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
											  , coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight											  
											  ,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
										FROM app.exam_marks
										INNER JOIN app.class_subject_exams 
										INNER JOIN app.exam_types
										ON class_subject_exams.exam_type_id = exam_types.exam_type_id
										INNER JOIN app.class_subjects 
											INNER JOIN app.subjects
											ON class_subjects.subject_id = subjects.subject_id
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
										WHERE class_subjects.class_id = :classId
										AND term_id = :termId
										AND subjects.parent_subject_id is null
										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)									
								 ) q
								 where student_id = :studentId");
		$sth3->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) ); 
		$overall = $sth3->fetch(PDO::FETCH_OBJ);
		
		// get overall position last term
		$sth4 = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage, 
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight, 
										round(total_mark::float/total_grade_weight::float*100) as percentage,
										dense_rank() over w as rank, position_out_of
									FROM (
										SELECT    
											  student_id
											  ,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
											  ,coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight											  
											  ,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
										FROM app.exam_marks
										INNER JOIN app.class_subject_exams 
										INNER JOIN app.exam_types
										ON class_subject_exams.exam_type_id = exam_types.exam_type_id
										INNER JOIN app.class_subjects 
											INNER JOIN app.subjects
											ON class_subjects.subject_id = subjects.subject_id
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
										WHERE class_subjects.class_id = :classId
										AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
										AND subjects.parent_subject_id is null
										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)									
								 ) q
								 where student_id = :studentId");
		$sth4->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) ); 
		$overallLastTerm = $sth4->fetch(PDO::FETCH_OBJ);
		
		$results =  new stdClass();
		$results->details = $details;
		$results->subjectOverall = $subjectOverall;
		$results->overall = $overall;
		$results->overallLastTerm = $overallLastTerm;
        
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

$app->post('/addReportCard', function () use($app) {
    // Add report card
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$studentId =		( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$termId =			( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$classId =			( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$reportCardType =	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$reportData =		( isset($allPostVars['report_data']) ? $allPostVars['report_data']: null);
	
    try 
    {
        $db = getDB();
		
		$getReport = $db->prepare("SELECT report_card_id FROM app.report_cards WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId");
		
		$addReport = $db->prepare("INSERT INTO app.report_cards(student_id, class_id, term_id, report_data, created_by, report_card_type) 
								VALUES(:studentId, :classId, :termId, :reportData, :userId, :reportCardType)"); 
		
		$updateReport = $db->prepare("UPDATE app.report_cards
									SET report_data = :reportData,
										modified_date = now(),
										modified_by = :userId
									WHERE report_card_id = :reportCardId"); 
									
		
		$db->beginTransaction();
		
		$getReport->execute( array(':studentId' => $studentId, ':classId' => $classId,':termId' => $termId ) );
		$reportCardId = $getReport->fetch(PDO::FETCH_OBJ);
		
		if( $reportCardId )
		{
			$updateReport->execute( array(':reportData' => $reportData, ':userId' => $userId, ':reportCardId' => $reportCardId->report_card_id ) );
		}
		else
		{
			$addReport->execute( array(':studentId' => $studentId, 
											':classId' => $classId,
											':reportCardType' => $reportCardType,
											':termId' => $termId, 
											':reportData' => $reportData, 
											':userId' => $userId
											) );
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


// ************** Payments  ****************** //
$app->get('/getPaymentsReceived/:startDate/:endDate/:paymentStatus(/:studentStatus)', function ($startDate,$endDate, $paymentStatus = false, $studentStatus = null) {
    // Get all payment received for given date range
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$query = "SELECT payments.student_id, 
						 payment_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
						 amount, payment_date, payments.payment_method,
						 CASE WHEN replacement_payment = true THEN
						 (SELECT array_agg(fee_item || ' Replacement') 
								 FROM app.payment_replacement_items 
								 INNER JOIN app.student_fee_items using (student_fee_item_id)
								 INNER JOIN app.fee_items using (fee_item_id) 
								 WHERE payment_id = payments.payment_id
								 )
						 ELSE
							(SELECT array_agg(fee_item) 
								 FROM app.payment_inv_items 
								 INNER JOIN app.invoices on payment_inv_items.inv_id  = invoices.inv_id and canceled = false
								 INNER JOIN app.invoice_line_items using (inv_item_id)
								 INNER JOIN app.student_fee_items using (student_fee_item_id)
								 INNER JOIN app.fee_items using (fee_item_id) 
								 WHERE payment_id = payments.payment_id
								 )
						 END as applied_to,
						 class_name, class_id, class_cat_id, students.active as status, replacement_payment, reversed,
						 (
									SELECT sum(diff) FROM (
										SELECT p.payment_id, p.amount, (p.amount - coalesce((select sum(amount) from app.payment_inv_items inner join app.invoices using (inv_id) where payment_id = p.payment_id and canceled = false ),0)) as diff
										FROM app.payments as p
										WHERE p.payment_id = payments.payment_id
										AND reversed is false
										AND replacement_payment is false
									) AS q
								) AS unapplied_amount
					FROM app.payments
					INNER JOIN app.students ON payments.student_id = students.student_id
					INNER JOIN app.classes ON students.current_class = classes.class_id
					WHERE payment_date between :startDate and :endDate
					AND reversed = :reversed
							";
		$queryParams = array(':startDate' => $startDate, ':endDate' => $endDate, ':reversed' => $paymentStatus);
		
		if( $studentStatus != null )
		{
			// interested to pull payments for a specific student status
			$query .= "AND students.active = :status ";
			$queryParams[':status'] = $studentStatus;
		}
		
		
        $sth = $db->prepare($query);
		$sth->execute( $queryParams ); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
			foreach( $results as $result)
			{
				$result->applied_to = pg_array_parse($result->applied_to);
			}
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

$app->get('/getPaymentsDue(/:startDate/:endDate)', function ($startDate=null,$endDate=null) {
    // Get all payment due for given date range
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT student_id,  
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
									total_due as amount, total_paid, balance,  due_date
							FROM app.invoice_balances
							INNER JOIN app.students using (student_id)
							WHERE date_trunc('month',due_date) = date_trunc('month', now())
							AND balance < 0");
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

$app->get('/getPaymentsPastDue', function () {
    // Get all payment due for given date range
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT student_id,  
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
									balance,  due_date
							FROM app.invoice_balances
							INNER JOIN app.students using (student_id)
							WHERE due_date < now() - interval '1 mon' 
							AND balance < 0 ");
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

$app->get('/getTotalsForTerm', function () {
    // Get all invoice totals for current term
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		// replacement payments do not have invoices, need to select these in addition to invoice payments to add to total paid amount
       $sth = $db->prepare("SELECT 
							sum(total_due) as total_due, 
							sum(total_paid) as total_paid,
							sum(balance) as total_balance
						FROM (
							SELECT total_due, total_paid,balance
							FROM app.invoice_balances
							WHERE due_date between (select start_date from app.current_term) and coalesce((select start_date - interval '1 day' from app.next_term), (select end_date from app.current_term)) 
							AND canceled = false

							UNION
							SELECT 0 as total_due, amount as total_paid,0 as balance
							FROM app.payments
							WHERE payment_date between (select start_date from app.current_term) and coalesce((select start_date - interval '1 day' from app.next_term), (select end_date from app.current_term)) 
							AND replacement_payment is true

						) q");
		$sth->execute(); 
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

$app->get('/getStudentBalances/:year(/:status)', function ($year, $status = true) {
    // Get all students balances
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT 
								students.student_id,
								first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
								class_name, class_id, class_cat_id,
								sum(total_due) as total_due, sum(total_paid) as total_paid, sum(balance) as balance,
								(SELECT to_char(amount, '999,999,999.99') || ' - ' || to_char(payment_date,'Mon DD, YYYY')  FROM app.payments WHERE student_id = students.student_id ORDER BY payment_date desc LIMIT 1 ) as last_payment,
								(SELECT to_char(total_due, '999,999,999.99') || ' - ' || to_char(due_date,'Mon DD, YYYY')   FROM app.invoice_balances WHERE student_id = students.student_id AND due_date > now() ORDER BY due_date asc LIMIT 1 ) as next_payment
							FROM app.invoice_balances
							INNER JOIN app.students
								INNER JOIN app.classes
								ON students.current_class = classes.class_id
							ON invoice_balances.student_id = students.student_id
							WHERE invoice_balances.due_date < now()
							AND date_part('year', due_date) = :year
							AND students.active = :status
							AND canceled = false
							GROUP BY students.student_id, class_name, class_id, class_cat_id, first_name, middle_name, last_name");
		$sth->execute( array(':year' => $year, ':status' => $status) ); 
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

$app->get('/getInstallmentOptions', function () {
    // Get countries
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT installment_id, payment_plan_name FROM app.installment_options ORDER BY payment_plan_name");
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

$app->post('/addPayment', function () use($app) {
    // create payment	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$userId = 				( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$studentId = 			( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$paymentDate = 			( isset($allPostVars['payment_date']) ? $allPostVars['payment_date']: null);
	$amount = 				( isset($allPostVars['amount']) ? $allPostVars['amount']: null);
	$paymentMethod = 		( isset($allPostVars['payment_method']) ? $allPostVars['payment_method']: null);
	$slipChequeNo = 		( isset($allPostVars['slip_cheque_no']) ? $allPostVars['slip_cheque_no']: null);
	$replacementPayment = 	( isset($allPostVars['replacement_payment']) ? $allPostVars['replacement_payment']: null);
	$invId = 				( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$lineItems =			( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
	$replacementItems =		( isset($allPostVars['replacement_items']) ? $allPostVars['replacement_items']: null);
	
    try 
    {
        $db = getDB();
        $payment = $db->prepare("INSERT INTO app.payments(student_id, payment_date, amount, payment_method, slip_cheque_no, replacement_payment, inv_id, created_by) 
									VALUES(:studentId, :paymentDate, :amount, :paymentMethod, :slipChequeNo, :replacementPayment, :invId, :userId)");
		if( count($lineItems) > 0 )
		{	
			$paymentItems = $db->prepare("INSERT INTO app.payment_inv_items(payment_id, inv_id, inv_item_id, amount, created_by)
									VALUES(currval('app.payments_payment_id_seq'), :invId, :invItemId, :amount, :userId)");
		}
		if( count($replacementItems) > 0 )
		{
			$replaceItems = $db->prepare("INSERT INTO app.payment_replacement_items(payment_id, student_fee_item_id, amount, created_by)
									VALUES(currval('app.payments_payment_id_seq'), :studenFeeItemId, :amount, :userId)");							
		}
 
		$db->beginTransaction();	
		
			
		$payment->execute( array(':studentId' => $studentId, 
								 ':paymentDate' => $paymentDate, 
								 ':amount' => $amount, 
								 ':paymentMethod' => $paymentMethod, 
								 ':slipChequeNo' => $slipChequeNo, 
								 ':replacementPayment' => $replacementPayment, 
								 ':invId' => $invId, 
								 ':userId' => $userId ) );
		
		if( count($lineItems) > 0 )
		{
			foreach( $lineItems as $lineItem )
			{
				$invItemId = ( isset($lineItem['inv_item_id']) ? $lineItem['inv_item_id']: null);
				$amount = ( isset($lineItem['amount']) ? $lineItem['amount']: null);
				
				$paymentItems->execute( array(':invId' => $invId, 
										   ':invItemId' => $invItemId, 
										   ':amount' => $amount,
										   ':userId' => $userId ) );
			}
		}
		
		if( count($replacementItems) > 0 )
		{
			foreach( $replacementItems as $replacementItem )
			{
				$studenFeeItemId = ( isset($replacementItem['student_fee_item_id']) ? $replacementItem['student_fee_item_id']: null);
				$amount = ( isset($replacementItem['amount']) ? $replacementItem['amount']: null);
				
				$replaceItems->execute( array(':studenFeeItemId' => $studenFeeItemId, 
										   ':amount' => $amount,
										   ':userId' => $userId ) );
			}
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

$app->get('/getPaymentDetails/:payment_id', function ($paymentId) {
    // Get all payment details
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
       $db = getDB();
	
		// get payment data
       $sth = $db->prepare("SELECT payment_id, payment_date, payments.amount, payments.payment_method, slip_cheque_no, 
									payments.student_id, replacement_payment, reversed, reversed_date,
									payments.inv_id
							FROM app.payments							
							WHERE payment_id = :paymentId
	   ");
		$sth->execute( array(':paymentId' => $paymentId) ); 
        $results1 = $sth->fetch(PDO::FETCH_OBJ);
		
		// get what the payment was applied to
		$sth2 = $db->prepare("SELECT payment_inv_item_id, payment_inv_items.inv_item_id,
									fee_item,
									payment_inv_items.amount as line_item_amount
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
									payment_replacement_items.amount as line_item_amount
							FROM app.payment_replacement_items							
							INNER JOIN app.student_fee_items
								INNER JOIN app.fee_items
								ON student_fee_items.fee_item_id = fee_items.fee_item_id
							ON payment_replacement_items.student_fee_item_id = student_fee_items.student_fee_item_id						
							WHERE payment_id = :paymentId							
							");
		$sth2->execute( array(':paymentId' => $paymentId) ); 
        $results2 = $sth2->fetchAll(PDO::FETCH_OBJ);
		
		// get the invoice details that payment was applied to
		$sth3 = $db->prepare("SELECT invoice_balances.inv_id,								
								inv_date,	
								total_due,								
								balance,
								due_date,
								inv_item_id,
								fee_item,
								invoice_line_items.amount as line_item_amount,
								(select term_name from app.terms where due_date between start_date and end_date) as term_name
							FROM app.invoice_balances
							INNER JOIN app.invoice_line_items
								INNER JOIN app.student_fee_items
									INNER JOIN app.fee_items
									ON student_fee_items.fee_item_id = fee_items.fee_item_id
								ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
							ON invoice_balances.inv_id = invoice_line_items.inv_id
							INNER JOIN app.payments ON invoice_balances.inv_id = payments.inv_id
							WHERE payment_id = :paymentId
							ORDER BY due_date, fee_item");
		$sth3->execute( array(':paymentId' => $paymentId) ); 
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
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updatePayment', function() use($app){
	 // Update payment	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$paymentId = 			( isset($allPostVars['payment_id']) ? $allPostVars['payment_id']: null);
	$userId = 				( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$studentId = 			( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$paymentDate = 			( isset($allPostVars['payment_date']) ? $allPostVars['payment_date']: null);
	$amount = 				( isset($allPostVars['amount']) ? $allPostVars['amount']: null);
	$paymentMethod = 		( isset($allPostVars['payment_method']) ? $allPostVars['payment_method']: null);
	$slipChequeNo = 		( isset($allPostVars['slip_cheque_no']) ? $allPostVars['slip_cheque_no']: null);
	$replacementPayment = 	( isset($allPostVars['replacement_payment']) ? $allPostVars['replacement_payment']: null);
	$invId = 				( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$lineItems =			( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
	$replacementItems =		( isset($allPostVars['replacement_items']) ? $allPostVars['replacement_items']: null);
	
	try 
    {
        $db = getDB();
		
		$updatePayment = $db->prepare("UPDATE app.payments
										SET payment_date = :paymentDate,
											amount = :amount,
											payment_method = :paymentMethod,
											slip_cheque_no = :slipChequeNo,
											replacement_payment = :replacementPayment,
											inv_id = :invId,
											modified_date = now(),
											modified_by = :userId
										WHERE payment_id = :paymentId");
		
		
		// prepare the possible statements
		if( count($lineItems) > 0 ) 
		{
			$itemUpdate = $db->prepare("UPDATE app.payment_inv_items
										SET amount = :amount,
											modified_date = now(),
											modified_by = :userID
										WHERE payment_inv_item_id = :paymentInvItemId");
			
			$itemInsert = $db->prepare("INSERT INTO app.payment_inv_items(payment_id, inv_id, inv_item_id, amount, created_by)
										VALUES(:paymentId, :invId, :invItemId, :amount, :userId)");
			

			$deleteLine = $db->prepare("DELETE FROM app.payment_inv_items WHERE payment_inv_item_id = :paymentInvItemId");
		}
		else
		{
			$deleteAllLines = $db->prepare("DELETE FROM app.payment_inv_items WHERE payment_id = :paymentId");
		}
		
		if( count($replacementItems) > 0 ) 
		{
			$replaceItemUpdate = $db->prepare("UPDATE app.payment_replacement_items
										SET amount = :amount,
											modified_date = now(),
											modified_by = :userID
										WHERE payment_replace_item_id = :paymentReplaceItemId");
			
			$replaceItemInsert = $db->prepare("INSERT INTO app.payment_replacement_items(payment_id, student_fee_item_id, amount, created_by)
										VALUES(:paymentId, :studentFeeItemId, :amount, :userId)");	
			

			$replaceDeleteLine = $db->prepare("DELETE FROM app.payment_replacement_items WHERE payment_replace_item_id = :paymentReplaceItemId");
		}
		else
		{
			$deleteReplaceLines = $db->prepare("DELETE FROM app.payment_replacement_items WHERE payment_id = :paymentId");
		}
		
		// get what is already set of this payment
		$query = $db->prepare("SELECT payment_replace_item_id FROM app.payment_replacement_items WHERE payment_id = :paymentId");
		$query->execute( array('paymentId' => $paymentId) );
		$currentReplaceItems = $query->fetchAll(PDO::FETCH_OBJ);
		
		// get what is already set of this payment
		$query = $db->prepare("SELECT payment_inv_item_id FROM app.payment_inv_items WHERE payment_id = :paymentId");
		$query->execute( array('paymentId' => $paymentId) );
		$currentLineItems = $query->fetchAll(PDO::FETCH_OBJ);	
		
		$db->beginTransaction();
	
		$updatePayment->execute( array(':paymentId' => $paymentId,
						':paymentDate' => $paymentDate,
						':amount' => $amount,
						':paymentMethod' => $paymentMethod,
						':slipChequeNo' => $slipChequeNo,
						':replacementPayment' => $replacementPayment,
						':invId' => $invId,
						':userId' => $userId
		) );	
		
		if( count($lineItems) > 0 ) 
		{		
	
			// loop through and add or update
			foreach( $lineItems as $lineItem )
			{
				$amount = 			( isset($lineItem['amount']) ? $lineItem['amount']: null);
				$paymentInvItemId = ( isset($lineItem['payment_inv_item_id']) ? $lineItem['payment_inv_item_id']: null);				
				$invItemId = 		( isset($lineItem['inv_item_id']) ? $lineItem['inv_item_id']: null);
				
				// this item exists, update it, else insert
				if( $paymentInvItemId !== null && !empty($paymentInvItemId) )
				{
					// need to update all terms, current and future of this year
					// leaving previous terms as they were
					$itemUpdate->execute(array(':amount' => $amount, 
												':paymentInvItemId' => $paymentInvItemId,
												':userID' => $userId ));
				}
				else
				{
					// check if was previously added, if so, reactivate
					// else fee items is new, add it
					// needs to be added once term term if per term fee item

					$itemInsert->execute( array(
						':paymentId' => $paymentId,
						':invId' => $invId,
						':invItemId' => $invItemId,
						':amount' => $amount,
						':userId' => $userId)
					);
					
				}
			}	
			
		
			// look for items to remove
			// compare to what was passed in			
			foreach( $currentLineItems as $currentLineItem )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $lineItems as $lineItem )
				{
					if( isset($lineItem['payment_inv_item_id']) && $lineItem['payment_inv_item_id'] == $currentLineItem->payment_inv_item_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$deleteLine->execute(array(':paymentInvItemId' => $currentLineItem->payment_inv_item_id));
				}
			}

		}
		else
		{
			$deleteAllLines->execute(array('paymentId' => $paymentId));
		}
		
		if( count($replacementItems) > 0 ) 
		{					
			
			// loop through and add or update
			foreach( $replacementItems as $replacementItem )
			{
				$amount = 				( isset($replacementItem['amount']) ? $replacementItem['amount']: null);
				$paymentReplaceItemId = ( isset($replacementItem['payment_replace_item_id']) ? $replacementItem['payment_replace_item_id']: null);				
				$studentFeeItemId = 	( isset($replacementItem['student_fee_item_id']) ? $replacementItem['student_fee_item_id']: null);
				
				// this item exists, update it, else insert
				if( $paymentReplaceItemId !== null && !empty($paymentReplaceItemId) )
				{
					// need to update all terms, current and future of this year
					// leaving previous terms as they were
					$replaceItemUpdate->execute(array(':amount' => $amount, 
												':paymentReplaceItemId' => $paymentReplaceItemId,
												':userID' => $userId ));
				}
				else
				{
					// check if was previously added, if so, reactivate
					// else fee items is new, add it
					// needs to be added once term term if per term fee item

					$replaceItemInsert->execute( array(
						':paymentId' => $paymentId,
						':studentFeeItemId' => $studentFeeItemId,
						':amount' => $amount,
						':userId' => $userId)
					);
					
				}
			}	
			
		
			// look for items to remove
			// compare to what was passed in			
			foreach( $currentReplaceItems as $currentReplaceItem )
			{	
				$deleteMe = true;

				// if found, do not delete
				foreach( $replacementItems as $replacementItem )
				{
					if( isset($replacementItem['payment_replace_item_id']) && $replacementItem['payment_replace_item_id'] == $currentReplaceItem->payment_replace_item_id )
					{
						$deleteMe = false;
					}
				}

				if( $deleteMe )
				{
					$replaceDeleteLine->execute(array(':paymentReplaceItemId' => $currentReplaceItem->payment_replace_item_id));
				}
			}

		}
		else
		{
			$deleteReplaceLines->execute(array('paymentId' => $paymentId));
		}
		
		$db->commit();
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/reversePayment', function () use($app) {
	// update payment to reversed
	$allPostVars = json_decode($app->request()->getBody(),true);
	$paymentId = ( isset($allPostVars['payment_id']) ? $allPostVars['payment_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	try 
    {
        $db = getDB();
		
		$updatePayment = $db->prepare("UPDATE app.payments	
										SET reversed = true,
											reversed_date = now(),
											reversed_by = :userId,
											modified_date = now(),
											modified_by = :userId
										WHERE payment_id = :paymentId");
		
	
		$updatePayment->execute( array(':paymentId' => $paymentId,
						':userId' => $userId
		) );	
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }
});

$app->put('/reactivatePayment', function() use($app) {
	// update payment to not reversed
	$allPostVars = json_decode($app->request()->getBody(),true);
	$paymentId = ( isset($allPostVars['payment_id']) ? $allPostVars['payment_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	try 
    {
        $db = getDB();
		
		$updatePayment = $db->prepare("UPDATE app.payments	
										SET reversed = false,
											reversed_date = null,
											reversed_by = null,
											modified_date = now(),
											modified_by= :userId
										WHERE payment_id = :paymentId");		
	
		$updatePayment->execute( array(':paymentId' => $paymentId,
						':userId' => $userId
		) );	
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }
});



// ************** Invoices  ****************** //
$app->get('/getInvoices/:startDate/:endDate(/:canceled/:status)', function ($startDate, $endDate, $canceled = false, $status = true) {
    // Get all students balances
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT 
								students.student_id,
								invoice_balances.inv_id,
								first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
								class_name, class_id, class_cat_id,
								inv_date,
								total_due,
								total_paid,
								balance,
								due_date,
								case when now()::date > due_date and balance < 0 then now()::date - due_date end as days_overdue
							FROM app.invoice_balances
							INNER JOIN app.students
								INNER JOIN app.classes
								ON students.current_class = classes.class_id
							ON invoice_balances.student_id = students.student_id
							WHERE due_date between :startDate and :endDate
							AND students.active = :status
							AND invoice_balances.canceled = :canceled");
		$sth->execute( array(':startDate' => $startDate, ':endDate' => $endDate, ':status' => $status, ':canceled' => $canceled) ); 
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

$app->get('/generateInvoices/:term(/:studentId)', function ($term, $studentId = null) {
    // Generate invoice(s) for given term
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$params = array();
		$termStatement = (  $term == 'current'  ? 'app.current_term' : 'app.next_term' );
		$nextTermStatement = (  $term == 'current'  ? 'app.next_term' : 'app.term_after_next' );
		$query = "SELECT * FROM (
						SELECT
							student_id,  student_fee_item_id, student_name, fee_item, 
							coalesce((CASE 
								WHEN frequency = 'per term' and payment_method = 'Installments' THEN
									case when payment_plan_name = 'Per Month' then
										round(yearly_amount/9,2)
									else
										round(yearly_amount/num_payments,2)
									end
								ELSE
									round(yearly_amount,2)				
							END),0) AS invoice_amount,
							
							CASE
								 WHEN payment_method = 'Installments' THEN
								case when num_payments_this_term > 1 THEN
									generate_series(term_start_date, term_start_date + ((payment_interval*(num_payments_this_term-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date
								else
									term_start_date
								end 
								 ELSE
								year_start_date								
							END as due_date,
							
							coalesce(round((select sum(amount)  
								from app.invoices 
								inner join app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id 
								where invoices.canceled = false 
								and student_fee_item_id = q2.student_fee_item_id 
								and due_date between term_start_date AND start_next_term
							)/num_payments_this_term,2) ,0) as total_amount_invoiced,
							num_payments_this_term
							
						FROM (
							SELECT
								student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,
								fee_item, student_fee_item_id, payment_method, frequency,yearly_amount,num_payments,term_start_date,payment_plan_name,
								payment_interval,year_start_date,term_end_date,start_next_term,payment_interval2,
								CASE 
									 WHEN frequency = 'per term' and payment_method = 'Installments' THEN
										CASE WHEN payment_plan_name = '50/50 Installment' THEN
											-- if 50/50 and not paid in first term, invoice
											CASE WHEN (
												SELECT count(*) 
												FROM app.invoice_line_items
												INNER JOIN app.invoices ON invoice_line_items.inv_id = invoices.inv_id
												WHERE canceled = false
												AND student_fee_item_id = q.student_fee_item_id
											) = 0 THEN 
												(SELECT count(*) FROM (
													SELECT
													generate_series(term_start_date, term_start_date + ((payment_interval*(num_payments-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date  as due_date
													FROM (
														SELECT payment_interval,payment_interval2,num_payments
														FROM app.students
														INNER JOIN app.installment_options 
														ON installment_option_id = installment_options.installment_id
														WHERE student_id = q.student_id
													) q
												   )q2
												   WHERE due_date BETWEEN term_start_date and date_trunc('month',term_end_date) 
												)

											 ELSE 0 END
										ELSE
											-- are there any installments due this term
											(SELECT count(*) FROM (
												SELECT
												generate_series(year_start_date, year_start_date + ((payment_interval*(num_payments-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date  as due_date
												FROM (
														SELECT payment_interval,payment_interval2,num_payments
														FROM app.students
														INNER JOIN app.installment_options 
														ON installment_option_id = installment_options.installment_id
														WHERE student_id = q.student_id
													) q
												)q2
												WHERE due_date BETWEEN term_start_date and date_trunc('month',term_end_date) 
											)
										END
									 ELSE
										-- otherwise we are paying annually, this is due in the first invoice
										CASE WHEN (
											SELECT count(*) 
											FROM app.invoice_line_items
											INNER JOIN app.invoices ON invoice_line_items.inv_id = invoices.inv_id
											WHERE canceled = false
											AND student_fee_item_id = q.student_fee_item_id
										) = 0 THEN 1 ELSE 0 END
								END::integer AS num_payments_this_term
							FROM (
								SELECT 
									students.student_id, first_name, middle_name, last_name, 
									fee_item, student_fee_items.student_fee_item_id, payment_interval,payment_interval2,
									student_fee_items.payment_method, frequency, coalesce(num_payments,1) as num_payments,payment_plan_name,
									round( CASE WHEN frequency = 'per term' THEN student_fee_items.amount*3 ELSE student_fee_items.amount END, 2) as yearly_amount,
									(select start_date from $termStatement) as term_start_date,
									(select end_date from $termStatement) as term_end_date,
									coalesce((select start_date from $nextTermStatement), (select end_date from $termStatement)) as start_next_term,
									( select min(start_date) from app.terms where date_part('year',start_date) = date_part('year', (select start_date from $termStatement)) ) as year_start_date
								FROM app.students									
								INNER JOIN app.student_fee_items
									INNER JOIN app.fee_items
									ON student_fee_items.fee_item_id = fee_items.fee_item_id										
								ON students.student_id = student_fee_items.student_id AND student_Fee_items.active = true
								LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
								WHERE students.active = true
								ORDER BY students.student_id
							) q
							
						) q2
						WHERE q2.num_payments_this_term > 0
					) q3
					WHERE total_amount_invoiced < invoice_amount
				";
		if( $studentId !== null )
		{
			$query .= "AND student_id = :studentId ";
			$params = array('studentId' => $studentId);
		}

		$query .= " ORDER BY student_id, due_date, fee_item";
		
        $sth = $db->prepare($query);
		$sth->execute( $params ); 
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

$app->post('/createInvoice', function () use($app) {
    // create invoice	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$invoices = ( isset($allPostVars['invoices']) ? $allPostVars['invoices']: null);
	
    try 
    {
        $db = getDB();
        $invoiceQry = $db->prepare("INSERT INTO app.invoices(student_id, inv_date, total_amount, due_date, created_by) 
									VALUES(:studentId, :invDate, :totalAmt, :dueDate, :userId)");
			
		$lineItems = $db->prepare("INSERT INTO app.invoice_line_items(inv_id, student_fee_item_id, amount, created_by)
									VALUES(currval('app.invoices_inv_id_seq'), :studentFeeItemId, :amount, :userId)");
 
 
		$db->beginTransaction();	
		foreach( $invoices as $invoice )
		{
			$studentId = ( isset($invoice['student_id']) ? $invoice['student_id']: null);
			$invDate = ( isset($invoice['inv_date']) ? $invoice['inv_date']: null);
			$totalAmt = ( isset($invoice['total_amount']) ? $invoice['total_amount']: null);
			$dueDate = ( isset($invoice['due_date']) ? $invoice['due_date']: null);
			
			$invoiceQry->execute( array(':studentId' => $studentId, ':invDate' => $invDate, ':totalAmt' => $totalAmt, ':dueDate' => $dueDate, ':userId' => $userId ) );
			
			foreach( $invoice['line_items'] as $lineItem )
			{
				$studentFeeItemId = ( isset($lineItem['student_fee_item_id']) ? $lineItem['student_fee_item_id']: null);
				$amount = ( isset($lineItem['amount']) ? $lineItem['amount']: null);
				
				$lineItems->execute( array(':studentFeeItemId' => $studentFeeItemId, ':amount' => $amount, ':userId' => $userId ) );
			}
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

$app->get('/getInvoiceDetails/:inv_id', function ($invId) {
    // Get all invoice details
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
       $db = getDB();
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
							WHERE invoices.inv_id = :invId
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
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateInvoice', function() use($app){
	 // Update invoice	
	$allPostVars = json_decode($app->request()->getBody(),true);
	var_dump($allPostVars);
	$invId = 	 ( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$invDate = 	 ( isset($allPostVars['inv_date']) ? $allPostVars['inv_date']: null);
	$totalAmt =  ( isset($allPostVars['total_amount']) ? $allPostVars['total_amount']: null);
	$dueDate = 	 ( isset($allPostVars['due_date']) ? $allPostVars['due_date']: null);
	$userId = 	 ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$lineItems = ( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
	
	try 
    {
        $db = getDB();
		
		$updateInvoice = $db->prepare("UPDATE app.invoices	
										SET inv_date = :invDate,
											total_amount = :totalAmt,
											due_date = :dueDate,
											modified_date = now(),
											modified_by= :userId
										WHERE inv_id = :invId");
		
		// prepare the possible statements
		if( count($lineItems) > 0 )
		{
			$itemUpdate = $db->prepare("UPDATE app.invoice_line_items
										SET amount = :amount,
											modified_date = now(),
											modified_by = :userID
										WHERE inv_item_id = :invItemId");
			
			$itemInsert = $db->prepare("INSERT INTO app.invoice_line_items(inv_id, student_fee_item_id, amount, created_by) 
										VALUES(:invId,:studentFeeItemID,:amount,:userId);"); 
			

			$deleteLine = $db->prepare("DELETE FROM app.invoice_line_items WHERE inv_item_id = :invItemId");
		}
		else
		{
			$deleteAllLine = $db->prepare("DELETE FROM app.invoice_line_items WHERE inv_id = :invId");
		}
		
		// get what is already set of this invoice
		$query = $db->prepare("SELECT inv_item_id FROM app.invoice_line_items WHERE inv_id = :invId");
		$query->execute( array('invId' => $invId) );
		$currentLineItems = $query->fetchAll(PDO::FETCH_OBJ);
			
		
		$db->beginTransaction();
	
		$updateInvoice->execute( array(':invId' => $invId,
						':invDate' => $invDate,
						':totalAmt' => $totalAmt,
						':dueDate' => $dueDate,
						':userId' => $userId
		) );	
		
		if( count($lineItems) > 0 ) 
		{		
	
			// loop through and add or update
			foreach( $lineItems as $lineItem )
			{
				$amount = 			( isset($lineItem['amount']) ? $lineItem['amount']: null);
				$invItemId = 		( isset($lineItem['inv_item_id']) ? $lineItem['inv_item_id']: null);				
				$studentFeeItemID = ( isset($lineItem['student_fee_item_id']) ? $lineItem['student_fee_item_id']: null);
				
				// this item exists, update it, else insert
				if( $invItemId !== null && !empty($invItemId) )
				{
					// need to update all terms, current and future of this year
					// leaving previous terms as they were
					$itemUpdate->execute(array(':amount' => $amount, 
												':invItemId' => $invItemId,
												':userID' => $userId ));
				}
				else
				{
					// check if was previously added, if so, reactivate
					// else fee items is new, add it
					// needs to be added once term term if per term fee item

					$itemInsert->execute( array(
						':invId' => $invId,
						':studentFeeItemID' => $studentFeeItemID,
						':amount' => $amount,
						':userId' => $userId)
					);
					
				}
			}	
			
		
			// look for items to remove
			// compare to what was passed in			
			foreach( $currentLineItems as $currentLineItem )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $lineItems as $lineItem )
				{
					if(  isset($lineItem['inv_item_id']) && $lineItem['inv_item_id'] == $currentLineItem->inv_item_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$deleteLine->execute(array(':invItemId' => $currentLineItem->inv_item_id));
				}
			}

		}
		else
		{
			$deleteAllLine->execute(array('invId' => $invId));
		}
		
		$db->commit();
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/cancelInvoice', function () use($app) {
	// update invoice to cancelled
	$allPostVars = json_decode($app->request()->getBody(),true);
	$invId = ( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	try 
    {
        $db = getDB();
		
		$updateInvoice = $db->prepare("UPDATE app.invoices	
										SET canceled = true,
											modified_date = now(),
											modified_by= :userId
										WHERE inv_id = :invId");
		
	
		$updateInvoice->execute( array(':invId' => $invId,
						':userId' => $userId
		) );	
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }
});

$app->put('/reactivateInvoice', function() use($app) {
	// update invoice to cancelled
	$allPostVars = json_decode($app->request()->getBody(),true);
	$invId = ( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	try 
    {
        $db = getDB();
		
		$updateInvoice = $db->prepare("UPDATE app.invoices	
										SET canceled = false,
											modified_date = now(),
											modified_by= :userId
										WHERE inv_id = :invId");		
	
		$updateInvoice->execute( array(':invId' => $invId,
						':userId' => $userId
		) );	
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }
});



// ************** Fee Items  ****************** //
$app->get('/getFeeItems(/:status)', function ($status = true) {
    //Show fee items
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount, frequency, active, class_cats_restriction, optional, new_student_only, replaceable
							FROM app.fee_items 
							WHERE active = :status
							AND optional is false
							ORDER BY fee_item_id");
        $sth->execute( array(':status' => $status) ); 
        $requiredItems = $sth->fetchAll(PDO::FETCH_OBJ);
		
		$results = new stdClass();
		$results->required_items = $requiredItems;
		
		$sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount,
								CASE WHEN fee_item = 'Transport' THEN
									(select min(to_char(amount, '999,999,999.99')) || '-' || max(to_char(amount, '999,999,999.99')) from app.transport_routes where active is true)
								END as range, 
								frequency, active, class_cats_restriction, optional, new_student_only, replaceable
							FROM app.fee_items 
							WHERE active = :status
							AND optional is true
							ORDER BY fee_item_id");
        $sth->execute( array(':status' => $status) ); 
        $optionalItems = $sth->fetchAll(PDO::FETCH_OBJ);
		
		$results->optional_items = $optionalItems;
		
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

$app->get('/getTansportRoutes(/:status)', function ($status = true) {
    //Show transport routes
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT transport_id, route, amount
							FROM app.transport_routes 
							WHERE active = :status
							ORDER BY route");
        $sth->execute( array(':status' => $status) ); 
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

$app->post('/addFeeItem', function () use($app) {
    // Add fee item
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$feeItem =		( isset($allPostVars['fee_item']) ? $allPostVars['fee_item']: null);
	$defaultAmount =( isset($allPostVars['default_amount']) ? $allPostVars['default_amount']: null);
	$frequency =	( isset($allPostVars['frequency']) ? $allPostVars['frequency']: null);
	$classCats =	( isset($allPostVars['class_cats_restriction']) ? $allPostVars['class_cats_restriction']: null);
	$optional =		( isset($allPostVars['optional']) ? $allPostVars['optional']: 'f');
	$newStudent =	( isset($allPostVars['new_student_only']) ? $allPostVars['new_student_only']: 'f');
	$replaceable =	( isset($allPostVars['replaceable']) ? $allPostVars['replaceable']: 'f');
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
	// convert $classCats to postgresql array
	if( is_array($classCats) ) 	$classCatsStr = '{' . implode(',',$classCats) . '}';
	

    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.fee_items(fee_item, default_amount, frequency, class_cats_restriction, optional, new_student_only, replaceable, created_by) 
							 VALUES(:feeItem, :defaultAmount, :frequency, :classCats, :optional, :newStudent, :replaceable, :userId)"); 
        $sth->execute( array(':feeItem' => $feeItem, 
							 ':defaultAmount' => $defaultAmount,
							 ':frequency' => $frequency,
							 ':classCats' => $classCatsStr,
							 ':optional' => $optional,
							 ':newStudent' => $newStudent,
							 ':replaceable' => $replaceable,
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

$app->put('/updateFeeItem', function () use($app) {
    // Update fee item
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$feeItemId =	( isset($allPostVars['fee_item_id']) ? $allPostVars['fee_item_id']: null);
	$feeItem =		( isset($allPostVars['fee_item']) ? $allPostVars['fee_item']: null);
	$defaultAmount =( isset($allPostVars['default_amount']) ? $allPostVars['default_amount']: null);
	$frequency =	( isset($allPostVars['frequency']) ? $allPostVars['frequency']: null);
	$classCats =	( isset($allPostVars['class_cats_restriction']) ? $allPostVars['class_cats_restriction']: null);
	$optional =		( isset($allPostVars['optional']) ? $allPostVars['optional']: 'f');
	$newStudent =	( isset($allPostVars['new_student_only']) ? $allPostVars['new_student_only']: 'f');
	$replaceable =	( isset($allPostVars['replaceable']) ? $allPostVars['replaceable']: 'f');
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

	// convert $classCats to postgresql array
	if( is_array($classCats) ) 	$classCatsStr = '{' . implode(',',$classCats) . '}';
	
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.fee_items
							SET fee_item = :feeItem,
								default_amount = :defaultAmount,
								frequency = :frequency, 
								class_cats_restriction = :classCatsStr, 
								optional = :optional, 
								new_student_only = :newStudent, 
								replaceable = :replaceable, 
								modified_date = now(),
								modified_by = :userId 
							WHERE fee_item_id = :feeItemId
							"); 
        $sth->execute( array(':feeItemId' => $feeItemId, 
							 ':feeItem' => $feeItem, 
							 ':defaultAmount' => $defaultAmount,
							 ':frequency' => $frequency,
							 ':classCatsStr' => $classCatsStr,
							 ':optional' => $optional,
							 ':newStudent' => $newStudent,
							 ':replaceable' => $replaceable,
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

$app->put('/setFeeItemStatus', function () use($app) {
    // Update fee item status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$feeItemId =	( isset($allPostVars['fee_item_id']) ? $allPostVars['fee_item_id']: null);
	$status =		( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.fee_items
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE fee_item_id = :feeItemId
							"); 
        $sth->execute( array(':feeItemId' => $feeItemId, 
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

$app->put('/updateRoutes', function () use($app) {
    // Update routes
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$routes =	( isset($allPostVars['routes']) ? $allPostVars['routes']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $updateRoute = $db->prepare("UPDATE app.transport_routes
							SET amount = :amount,
								active = true,
								modified_date = now(),
								modified_by = :userId 
							WHERE transport_id = :transportId
							"); 
		
		$insertRoute = $db->prepare("INSERT INTO app.transport_routes(route,amount,created_by)
										VALUES(:route, :amount, :userId)");
		
		$inactivateRoute = $db->prepare("UPDATE app.transport_routes
							SET active = false,
								modified_date = now(),
								modified_by = :userId 
							WHERE transport_id = :transportId
							"); 
							
		// pull out existing routes	    
	    $query = $db->prepare("SELECT transport_id FROM app.transport_routes WHERE active is true");
		$query->execute();
		$currentRoutes = $query->fetchAll(PDO::FETCH_OBJ);
						
		$db->beginTransaction();
		foreach($routes as $route)
		{
			$transportId =	( isset($route['transport_id']) ? $route['transport_id']: null);
			$routeName =	( isset($route['route']) ? $route['route']: null);
			$amount =		( isset($route['amount']) ? $route['amount']: null);
			
			if( $transportId !== null )			
			{
				$updateRoute->execute( array(':transportId' => $transportId, 
							 ':amount' => $amount,
							 ':userId' => $userId
					) );
			}
			else
			{
				$insertRoute->execute( array(':route' => $routeName, 
							 ':amount' => $amount,
							 ':userId' => $userId
					) );
			}
		}
       
	    // set active to false for any not passed in
		foreach( $currentRoutes as $currentRoute )
		{	
			$deleteMe = true;
			// if found, do not delete
			foreach( $routes as $route )
			{
				if( isset($route['transport_id']) && $route['transport_id'] == $currentRoute->transport_id )
				{
					$deleteMe = false;
				}
			}
			
			if( $deleteMe )
			{
				$inactivateRoute->execute(array(':transportId' => $currentRoute->transport_id, ':userId' => $userId));
			}
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



// ************** Students  ****************** //
$app->get('/getAllStudents(/:status)', function ($status=true) {
    //Show all students
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		$sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
							 FROM app.students 
							 INNER JOIN app.classes ON students.current_class = classes.class_id
							 WHERE students.active = :status 
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

$app->get('/getTeacherStudents/:teacher_id(/:status)', function ($teacherId, $status=true) {
    //Show teacher students
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		$sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
							 FROM app.students 
							 INNER JOIN app.classes ON students.current_class = classes.class_id AND classes.teacher_id = :teacherId
							 WHERE students.active = :status 
							 ORDER BY first_name, middle_name, last_name");
		$sth->execute( array(':status' => $status, ':teacherId' => $teacherId)); 
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

$app->get('/getStudentDetails/:studentId', function ($studentId) {
    //Show all students
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
								payment_plan_name || ' (' || num_payments || ' payments ' || payment_interval || ' days apart)' as payment_plan_name
							 FROM app.students 
							 INNER JOIN app.classes ON students.current_class = classes.class_id
							 LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
							 WHERE student_id = :studentID 
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
									student_fee_item_id, 
									student_fee_items.fee_item_id, 
									fee_item, amount, 
									payment_method,
									(select sum(payment_inv_items.amount) 
										from app.payment_inv_items 
										inner join app.invoice_line_items 
										on payment_inv_items.inv_item_id = invoice_line_items.inv_item_id 
										where invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id) as payment_made,
									student_fee_items.active
								FROM app.student_fee_items 
								INNER JOIN app.fee_items on student_fee_items.fee_item_id = fee_items.fee_item_id
								WHERE student_id = :studentID
								ORDER BY student_fee_items.creation_date");
			$sth4->execute( array(':studentID' => $studentId));
			$results4 = $sth4->fetchAll(PDO::FETCH_OBJ);

			$results->fee_items = $results4;

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

$app->get('/getStudentBalance/:studentId', function ($studentId) {
    // Return students next payment
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get total amount of student fee items
		// calculate the amount due and due date
		// calculate the balance owing
		$sth = $db->prepare("SELECT fee_item, payment_method,
									sum(invoice_line_items.amount) AS total_due, 
									COALESCE(sum(payment_inv_items.amount), 0) AS total_paid, 
									COALESCE(sum(payment_inv_items.amount), 0) - sum(invoice_line_items.amount) AS balance        
							FROM app.invoices
							INNER JOIN app.invoice_line_items 
								INNER JOIN app.student_fee_items
									INNER JOIN app.fee_items
									ON student_fee_items.fee_item_id = fee_items.fee_item_id
								ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id AND student_fee_items.active = true
								LEFT JOIN app.payment_inv_items
								ON invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
							ON invoices.inv_id = invoice_line_items.inv_id
							WHERE invoices.student_id = :studentID
							AND invoices.canceled = false
							AND invoices.due_date < now()
							GROUP BY fee_item, payment_method
							");
        $sth->execute( array(':studentID' => $studentId)); 
        $fees = $sth->fetchAll(PDO::FETCH_OBJ);
		
		
		if( $fees )
		{
		
			$sth2 = $db->prepare("SELECT 
									(SELECT due_date FROM app.invoice_balances WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_due_date,
									(SELECT balance from app.invoice_balances WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_amount,
									(
										SELECT sum(diff) FROM (
											SELECT p.payment_id, p.amount, (p.amount - coalesce((select sum(amount) from app.payment_inv_items inner join app.invoices using (inv_id) where payment_id = p.payment_id and canceled = false ),0)) as diff
											FROM app.payments as p	
											WHERE student_id = :studentID
											AND reversed is false
											AND replacement_payment is false
										) AS q
									) AS unapplied_payments");
			$sth2->execute( array(':studentID' => $studentId)); 
			$details = $sth2->fetch(PDO::FETCH_OBJ);
			
			
			if( $details )
			{			
				//  set the next due summary
				$feeSummary = new Stdclass();
				$feeSummary->next_due_date = $details->next_due_date;
				$feeSummary->next_amount = $details->next_amount;
				$feeSummary->unapplied_payments = $details->unapplied_payments;				
				
				// is the next due date within 30 days?
				$diff = dateDiff("now", $details->next_due_date);
				$feeSummary->within30days = ( $diff < 30 ? true : false ); 	
			}
			
			$balanceQry = $db->prepare("SELECT sum(total_due) as total_due, sum(total_paid) as total_paid, sum(balance) as balance
										FROM app.invoice_balances
									    WHERE student_id = :studentID
										AND due_date < now()::date
										AND canceled = false");
			$balanceQry->execute( array(':studentID' => $studentId)); 
			$balance = $balanceQry->fetch(PDO::FETCH_OBJ);
			//var_dump($balance);
			
			$feeSummary->total_due = ($balance ? $balance->total_due : 0);
			$feeSummary->total_paid = ($balance ? $balance->total_paid : 0);
			$feeSummary->balance = ($balance ? $balance->balance : 0);
			
			
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
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentInvoices/:studentId', function ($studentId) {
    // Return students invoices
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get fee items
		$sth = $db->prepare("SELECT * FROM app.invoice_balances WHERE student_id = :studentId ORDER BY inv_date");
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

$app->get('/getOpenInvoices/:studentId', function ($studentId) {
    // Get all students open invoices
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT 
								students.student_id,
								invoice_balances.inv_id,
								first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
								class_name, class_id, class_cat_id,
								inv_date,
								total_due,
								total_paid,
								balance,
								due_date,
								inv_item_id,
								fee_item,
								invoice_line_items.amount as line_item_amount
							FROM app.invoice_balances
							INNER JOIN app.invoice_line_items
								INNER JOIN app.student_fee_items
									INNER JOIN app.fee_items
									ON student_fee_items.fee_item_id = fee_items.fee_item_id
								ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
							ON invoice_balances.inv_id = invoice_line_items.inv_id
							INNER JOIN app.students
								INNER JOIN app.classes
								ON students.current_class = classes.class_id
							ON invoice_balances.student_id = students.student_id
							WHERE students.student_id = :studentId
							AND balance < 0
							AND canceled = false
							ORDER BY due_date, fee_item");
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

$app->get('/getStudentFeeItems/:studentId', function ($studentId) {
    // Get all students replaceable fee items
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount, 
									CASE WHEN frequency = 'per term' THEN 3 
									     ELSE 1
									END as frequency
							FROM app.student_fee_items
							INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
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
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getReplaceableFeeItems/:studentId', function ($studentId) {
    // Get all students replaceable fee items
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount
							FROM app.student_fee_items
							INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
							WHERE student_id = :studentId
							AND student_fee_items.active = true
							AND replaceable = true
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
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentPayments/:studentId', function ($studentId) {
    // Return students payments
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get fee items
		$sth = $db->prepare("SELECT payment_id,
								payment_date,
								payment_method,
								amount,
								CASE WHEN replacement_payment = true THEN
								 (SELECT array_agg(fee_item || ' Replacement') 
										 FROM app.payment_replacement_items 
										 INNER JOIN app.student_fee_items using (student_fee_item_id)
										 INNER JOIN app.fee_items using (fee_item_id) 
										 WHERE payment_id = payments.payment_id
										 )
								 ELSE
									(SELECT array_agg(fee_item) 
										 FROM app.payment_inv_items 
										 INNER JOIN app.invoices on payment_inv_items.inv_id  = invoices.inv_id and canceled = false
										 INNER JOIN app.invoice_line_items using (inv_item_id)
										 INNER JOIN app.student_fee_items using (student_fee_item_id)
										 INNER JOIN app.fee_items using (fee_item_id) 
										 WHERE payment_id = payments.payment_id
										 )
								 END as applied_to,
								  (
									SELECT sum(diff) FROM (
										SELECT p.payment_id, p.amount, (p.amount - coalesce((select sum(amount) from app.payment_inv_items inner join app.invoices using (inv_id) where payment_id = p.payment_id and canceled = false ),0)) as diff
										FROM app.payments as p
										WHERE p.payment_id = payments.payment_id
										AND reversed is false
										AND replacement_payment is false
									) AS q
								) AS unapplied_amount,
								 reversed, reversed_date, replacement_payment, slip_cheque_no
								FROM app.payments						
								WHERE student_id = :studentID");
		$sth->execute( array(':studentID' => $studentId));
		$results = $sth->fetchAll(PDO::FETCH_OBJ);
		
 
        if($results) {			
			// convert pgarray to php array
			
			foreach( $results as $result)
			{
				$result->applied_to = pg_array_parse($result->applied_to);
			}
			
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

$app->get('/getStudentClassess/:studentId', function ($studentId) {
    // Get all students classes, present and past
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT 1 as ord, student_id, class_id, class_name,
								case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 1') then true else false end as term_1,
								case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 2') then true else false end as term_2,
								case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 3') then true else false end as term_3
							FROM app.students
							INNER JOIN app.classes ON students.current_class = classes.class_id
							WHERE student_id = :studentId
							UNION
							SELECT class_history_id as ord, student_id, student_class_history.class_id, class_name, true, true, true
							FROM app.student_class_history
							INNER JOIN app.classes ON student_class_history.class_id = classes.class_id
							WHERE student_id = :studentId
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

$app->post('/addStudent', function () use($app) {
    // Add student	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$admissionNumber =				( isset($allPostVars['admission_number']) ? $allPostVars['admission_number']: null);
	$gender = 						( isset($allPostVars['gender']) ? $allPostVars['gender']: null);
	$firstName = 					( isset($allPostVars['first_name']) ? $allPostVars['first_name']: null);
	$middleName = 					( isset($allPostVars['middle_name']) ? $allPostVars['middle_name']: null);
	$lastName =						( isset($allPostVars['last_name']) ? $allPostVars['last_name']: null);
	$dob = 							( isset($allPostVars['dob']) ? $allPostVars['dob']: null);
	$studentCat = 					( isset($allPostVars['student_category']) ? $allPostVars['student_category']: null);
	$nationality = 					( isset($allPostVars['nationality']) ? $allPostVars['nationality']: null);
	$currentClass = 				( isset($allPostVars['current_class']) ? $allPostVars['current_class']: null);
	$studentImg = 					( isset($allPostVars['student_image']) ? $allPostVars['student_image']: null);
	$paymentMethod = 				( isset($allPostVars['payment_method']) ? $allPostVars['payment_method']: null);
	$active = 						( isset($allPostVars['active']) ? $allPostVars['active']: true);
	$newStudent = 		( isset($allPostVars['details']['new_student']) ? $allPostVars['details']['new_student']: true);
	$admissionDate = 				( isset($allPostVars['admission_date']) ? $allPostVars['admission_date']: null);
	$marialStatusParents = 			( isset($allPostVars['marial_status_parents']) ? $allPostVars['marial_status_parents']: null);
	$adopted = 						( isset($allPostVars['adopted']) ? $allPostVars['adopted']: 'f');
	$adoptedAge = 					( isset($allPostVars['adopted_age']) ? $allPostVars['adopted_age']: null);	
	$maritalSeparationAge = 		( isset($allPostVars['marital_separation_age']) ? $allPostVars['marital_separation_age']: null);
	$adoptionAware = 				( isset($allPostVars['adoption_aware']) ? $allPostVars['adoption_aware']: 'f');
	$comments = 					( isset($allPostVars['comments']) ? $allPostVars['comments']: null);
	$hasMedicalConditions = 		( isset($allPostVars['has_medical_conditions']) ? $allPostVars['has_medical_conditions']: 'f');
	$hospitalized = 				( isset($allPostVars['hospitalized']) ? $allPostVars['hospitalized']: 'f');
	$hospitalizedDesc = 			( isset($allPostVars['hospitalized_description']) ? $allPostVars['hospitalized_description']: null);
	$currentMedicalTreatment = 		( isset($allPostVars['current_medical_treatment']) ? $allPostVars['current_medical_treatment']: 'f');	
	$currentMedicalTreatmentDesc = 	( isset($allPostVars['current_medical_treatment_description']) ? $allPostVars['current_medical_treatment_description']: null);
	$otherMedicalConditions = 		( isset($allPostVars['other_medical_conditions']) ? $allPostVars['other_medical_conditions']: 'f');
	$otherMedicalConditionsDesc = 	( isset($allPostVars['other_medical_conditions_description']) ? $allPostVars['other_medical_conditions_description']: null);
	$emergencyContact = 			( isset($allPostVars['emergency_name']) ? $allPostVars['emergency_name']: null);
	$emergencyRelation = 			( isset($allPostVars['emergency_relationship']) ? $allPostVars['emergency_relationship']: null);
	$emergencyPhone = 				( isset($allPostVars['emergency_telephone']) ? $allPostVars['emergency_telephone']: null);
	$pickUpIndividual =				( isset($allPostVars['pick_up_drop_off_individual']) ? $allPostVars['pick_up_drop_off_individual']: null);
	$installmentOption =			( isset($allPostVars['installment_option']) ? $allPostVars['installment_option']: null);
	
	// guardian fields
	$guardianData = 				( isset($allPostVars['guardians']) ? $allPostVars['guardians']: null);
	
	
	// medical condition fields
	$medicalConditions = ( isset($allPostVars['medicalConditions']) ? $allPostVars['medicalConditions']: null);
	
	// fee item fields
	$feeItems = ( isset($allPostVars['feeItems']) ? $allPostVars['feeItems']: null);
	$optFeeItems = ( isset($allPostVars['optFeeItems']) ? $allPostVars['optFeeItems']: null);

	$createdBy = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);


    try 
    {
        $db = getDB();
				
        $studentInsert = $db->prepare("INSERT INTO app.students(admission_number, gender, first_name, middle_name, last_name, dob, student_category, nationality,
																student_image, current_class, payment_method, active, created_by, admission_date, marial_status_parents, 
																adopted, adopted_age, marital_separation_age, adoption_aware, comments, medical_conditions, hospitalized,
																hospitalized_description, current_medical_treatment, current_medical_treatment_description,
																other_medical_conditions, other_medical_conditions_description, 
																emergency_name, emergency_relationship, emergency_telephone, pick_up_drop_off_individual, installment_option_id, new_student) 
            VALUES(:admissionNumber,:gender,:firstName,:middleName,:lastName,:dob,:studentCat,:nationality,:studentImg, :currentClass, :paymentMethod, :active, :createdBy, 
					:admissionDate, :marialStatusParents, :adopted, :adoptedAge, :maritalSeparationAge, :adoptionAware, :comments, :hasMedicalConditions, :hospitalized,
					:hospitalizedDesc, :currentMedicalTreatment, :currentMedicalTreatmentDesc, :otherMedicalConditions, :otherMedicalConditionsDesc, 
					:emergencyContact, :emergencyRelation, :emergencyPhone, :pickUpIndividual, :installmentOption, :newStudent);"); 
					
		$studentClassInsert = $db->prepare("INSERT INTO app.student_class_history(student_id,class_id,created_by)
											VALUES(currval('app.students_student_id_seq'),:currentClass,:createdBy);");
											
		$query = $db->prepare("SELECT currval('app.students_student_id_seq') as student_id");
        $query2 = $db->prepare("SELECT currval('app.students_student_id_seq') as student_id, currval('app.guardians_guardian_id_seq') as guardian_id");
		
		if( $guardianData !== null )
		{
			// add contact
			$guardianInsert = $db->prepare("INSERT INTO app.guardians(first_name, middle_name, last_name, title, id_number, address, telephone, email, 
											marital_status, occupation, employer, employer_address, work_email, work_phone, created_by)
											VALUES(:guardianFirstName, :guardianMiddleName, :guardianLastName, :guardianTitle, :guardianIdNumber, :guardianAddress, 
													:guardianTelephone, :guardianEmail, :guardianMaritalStatus, :guardianOccupation, :guardianEmployer, 
													:guardianEmployerAddress, :guardianWorkEmail, :guardianWorkPhone, :createdBy);");
						
			$guardianInsert2 = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
											VALUES(currval('app.students_student_id_seq'), currval('app.guardians_guardian_id_seq'), :guardianRelationship,  :createdBy)");
											
			// if its an update
			$guardianUpdate = $db->prepare("UPDATE app.guardians
									SET first_name = :guardianFirstName,
										middle_name = :guardianMiddleName,
										last_name = :guardianLastName,
										title = :guardianTitle,
										id_number = :guardianIdNumber,
										address = :guardianAddress,
										telephone = :guardianTelephone,
										email = :guardianEmail,
										marital_status = :guardianMaritalStatus,
										occupation = :guardianOccupation,
										employer =:guardianEmployer,
										employer_address = :guardianEmployerAddress,
										work_email = :guardianWorkEmail,
										work_phone = :guardianWorkPhone,
										modified_date = now(),
										modified_by = :createdBy
									WHERE guardian_id = :guardianId");
						
			$guardianUpdate2 = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
											VALUES(currval('app.students_student_id_seq'), :guardianId, :guardianRelationship,  :createdBy)");
											
		}

		if( count($medicalConditions) > 0 )
		{
			$conditionInsert = $db->prepare("INSERT INTO app.student_medical_history(student_id, illness_condition, age, comments, created_by) 
            VALUES(currval('app.students_student_id_seq'),?,?,?,?);"); 
        
		}
		if( count($feeItems) > 0 || count($optFeeItems) > 0 )
		{
			$feesInsert = $db->prepare("INSERT INTO app.student_fee_items(student_id, fee_item_id, amount, payment_method, created_by) 
										VALUES(currval('app.students_student_id_seq'),:feeItemID,:amount,:paymentMethod,:userId);"); 
		}		
		
		$db->beginTransaction();	
		
		$studentInsert->execute( array(':admissionNumber' => $admissionNumber,
							':gender' => $gender,
							':firstName' => $firstName,
							':middleName' => $middleName,
							':lastName' => $lastName,
							':dob' => $dob,
							':studentCat' => $studentCat,
							':nationality' => $nationality,
							':studentImg' => $studentImg,
							':currentClass' => $currentClass,
							':paymentMethod' => $paymentMethod,
							':active' => $active,
							':createdBy' => $createdBy,
							':admissionDate' => $admissionDate,
							':marialStatusParents' => $marialStatusParents, 
							':adopted' => $adopted, 
							':adoptedAge' => $adoptedAge, 
							':maritalSeparationAge' => $maritalSeparationAge, 
							':adoptionAware' => $adoptionAware, 
							':comments' => $comments,
							':hasMedicalConditions' => $hasMedicalConditions,
							':hospitalized' => $hospitalized,
							':hospitalizedDesc' => $hospitalizedDesc, 
							':currentMedicalTreatment' => $currentMedicalTreatment, 
							':currentMedicalTreatmentDesc' => $currentMedicalTreatmentDesc, 
							':otherMedicalConditions' => $otherMedicalConditions, 
							':otherMedicalConditionsDesc' => $otherMedicalConditionsDesc, 
							':emergencyContact' => $emergencyContact, 
							':emergencyRelation' => $emergencyRelation, 
							':emergencyPhone' => $emergencyPhone,
							':pickUpIndividual' => $pickUpIndividual,
							':installmentOption' => $installmentOption,
							':newStudent' => $newStudent
		) );
		
		$studentClassInsert->execute(array(':currentClass' => $currentClass,':createdBy' => $createdBy));
		
		if( $guardianData !== null )
		{
			foreach( $guardianData as $guardian )
			{
				$guardianTelephone = 		( isset($guardian['telephone']) ? $guardian['telephone']: null);
				$guardianEmail = 			( isset($guardian['email']) ? $guardian['email']: null);
				$guardianFirstName = 		( isset($guardian['first_name']) ? $guardian['first_name']: null);
				$guardianMiddleName = 		( isset($guardian['middle_name']) ? $guardian['middle_name']: null);
				$guardianLastName = 		( isset($guardian['last_name']) ? $guardian['last_name']: null);
				$guardianIdNumber = 		( isset($guardian['id_number']) ? $guardian['id_number']: null);
				$guardianRelationship = 	( isset($guardian['relationship']) ? $guardian['relationship']: null);
				$guardianTitle = 			( isset($guardian['title']) ? $guardian['title']: null);
				$guardianOccupation = 		( isset($guardian['occupation']) ? $guardian['occupation']: null);
				$guardianAddress = 			( isset($guardian['address']) ? $guardian['address']: null);
				$guardianMaritalStatus = 	( isset($guardian['marital_status']) ? $guardian['marital_status']: null);
				$guardianEmployer = 		( isset($guardian['employer']) ? $guardian['employer']: null);
				$guardianEmployerAddress = 	( isset($guardian['employer_address']) ? $guardian['employer_address']: null);
				$guardianWorkEmail = 		( isset($guardian['work_email']) ? $guardian['work_email']: null);
				$guardianWorkPhone =		( isset($guardian['work_phone']) ? $guardian['work_phone']: null);
				
				if( isset($guardian['guardian_id']) ) 
				{
					$guardianUpdate->execute(array( ':guardianId' => $guardian['guardian_id'],
								':guardianFirstName' => $guardianFirstName,
								':guardianMiddleName' => $guardianMiddleName,
								':guardianLastName' => $guardianLastName,
								':guardianTitle' => $guardianTitle,
								':guardianIdNumber' => $guardianIdNumber,
								':guardianAddress' => $guardianAddress,
								':guardianTelephone' => $guardianTelephone,
								':guardianEmail' => $guardianEmail,								
								':guardianMaritalStatus' => $guardianMaritalStatus, 
								':guardianOccupation' => $guardianOccupation,
								':guardianEmployer' => $guardianEmployer,
								':guardianEmployerAddress' => $guardianEmployerAddress,
								':guardianWorkEmail' => $guardianWorkEmail, 
								':guardianWorkPhone' => $guardianWorkPhone,
								':createdBy' => $createdBy) );
					$guardianUpdate2->execute(array(':guardianId' =>  $guardian['guardian_id'], 
													':guardianRelationship' => $guardianRelationship, 
													':createdBy' => $createdBy));
				}
				else
				{
					$guardianInsert->execute( array(':guardianFirstName' => $guardianFirstName,
								':guardianMiddleName' => $guardianMiddleName,
								':guardianLastName' => $guardianLastName,
								':guardianTitle' => $guardianTitle,
								':guardianIdNumber' => $guardianIdNumber,
								':guardianAddress' => $guardianAddress,
								':guardianTelephone' => $guardianTelephone,
								':guardianEmail' => $guardianEmail,								
								':guardianMaritalStatus' => $guardianMaritalStatus, 
								':guardianOccupation' => $guardianOccupation,
								':guardianEmployer' => $guardianEmployer,
								':guardianEmployerAddress' => $guardianEmployerAddress,
								':guardianWorkEmail' => $guardianWorkEmail, 
								':guardianWorkPhone' => $guardianWorkPhone,
								':createdBy' => $createdBy
					) );
					$guardianInsert2->execute(array(':guardianRelationship' => $guardianRelationship, ':createdBy' => $createdBy));
				}

			}
			
		}
			
		if( count($medicalConditions) > 0 )
		{
        
			foreach($medicalConditions as $medicalCondition )
			{
				$conditionInsert->execute( array($medicalCondition['medical_condition'],
							$medicalCondition['age'],
							$medicalCondition['comments'],
							$createdBy
				) );
			}
		}
		
		if( count($feeItems) > 0 )
		{
			foreach( $feeItems as $feeItem )
			{
				$feesInsert->execute( array(
					':feeItemID' => $feeItem['fee_item_id'],
					':amount' => $feeItem['amount'],
					':paymentMethod' => $feeItem['payment_method'],
					':userId' => $createdBy)
				);
			}
		}

		if( count($optFeeItems) > 0 )
		{
        
			foreach( $optFeeItems as $optFeeItem )
			{
				$feesInsert->execute( array(
					':feeItemID' => $optFeeItem['fee_item_id'],
					':amount' => $optFeeItem['amount'],
					':paymentMethod' => $optFeeItem['payment_method'],
					':userId' => $createdBy)
				);
			}
		}
		
		// if guardian id was sent, just grab student id, else grab both ids
		if( isset($guardian['guardian_id']) ) $query->execute();
		else $query2->execute();
		
		$newStudent = $query->fetch(PDO::FETCH_OBJ);	
		
		$db->commit();
		

		// if login data was passed
		if( $guardianData !== null )
		{
			
			foreach( $guardianData as $guardian )
			{
				if( $guardian['login'] !== null )
				{
					$guardian['student_id'] = $newStudent->student_id;
					if( !isset($guardian['guardian_id']) ) $guardian['guardian_id'] = $newStudent->guardian_id;
					createParentLogin($guardian);
				}
			}
		}
		
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateStudent', function () use($app) {
    // Update student	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$updateDetails = false;
	$updateFamily = false;
	$updateMedical = false;
	$updateFees = false;
	
	$studentId = ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

	if( isset($allPostVars['details']) )
	{
		$updateDetails = true;
		$gender = 				( isset($allPostVars['details']['gender']) ? $allPostVars['details']['gender']: null);
		$firstName = 			( isset($allPostVars['details']['first_name']) ? $allPostVars['details']['first_name']: null);
		$middleName = 			( isset($allPostVars['details']['middle_name']) ? $allPostVars['details']['middle_name']: null);
		$lastName =				( isset($allPostVars['details']['last_name']) ? $allPostVars['details']['last_name']: null);
		$dob = 					( isset($allPostVars['details']['dob']) ? $allPostVars['details']['dob']: null);
		$studentCat = 			( isset($allPostVars['details']['student_category']) ? $allPostVars['details']['student_category']: null);
		$nationality = 			( isset($allPostVars['details']['nationality']) ? $allPostVars['details']['nationality']: null);
		$currentClass = 		( isset($allPostVars['details']['current_class']) ? $allPostVars['details']['current_class']: null);
		$previousClass = 		( isset($allPostVars['details']['previous_class']) ? $allPostVars['details']['previous_class']: null);
		$currentClassCatId = 	( isset($allPostVars['details']['current_class_cat']) ? $allPostVars['details']['current_class_cat']: null);
		$previousClassCatId = 	( isset($allPostVars['details']['previous_class_cat']) ? $allPostVars['details']['previous_class_cat']: null);
		$updateClass = 			( isset($allPostVars['details']['update_class']) ? $allPostVars['details']['update_class']: false);
		$studentImg = 			( isset($allPostVars['details']['student_image']) ? $allPostVars['details']['student_image']: null);
		$active = 				( isset($allPostVars['details']['active']) ? $allPostVars['details']['active']: true);
		$newStudent = 			( isset($allPostVars['details']['new_student']) ? $allPostVars['details']['new_student']: true);
		$admissionNumber =		( isset($allPostVars['details']['admission_number']) ? $allPostVars['details']['admission_number']: null);
		$admissionDate = 		( isset($allPostVars['details']['admission_date']) ? $allPostVars['details']['admission_date']: null);
	}
	
	if( isset($allPostVars['family']) )
	{
		$updateFamily = true;
		$marialStatusParents = 	( isset($allPostVars['family']['marial_status_parents']) ? $allPostVars['family']['marial_status_parents']: null);
		$adopted = 				( isset($allPostVars['family']['adopted']) ? $allPostVars['family']['adopted']: 'f');
		$adoptedAge = 			( isset($allPostVars['family']['adopted_age']) ? $allPostVars['family']['adopted_age']: null);	
		$maritalSeparationAge = ( isset($allPostVars['family']['marital_separation_age']) ? $allPostVars['family']['marital_separation_age']: null);
		$adoptionAware = 		( isset($allPostVars['family']['adoption_aware']) ? $allPostVars['family']['adoption_aware']: 'f');
		$emergencyContact = 	( isset($allPostVars['family']['emergency_name']) ? $allPostVars['family']['emergency_name']: null);
		$emergencyRelation = 	( isset($allPostVars['family']['emergency_relationship']) ? $allPostVars['family']['emergency_relationship']: null);
		$emergencyPhone = 		( isset($allPostVars['family']['emergency_telephone']) ? $allPostVars['family']['emergency_telephone']: null);
		$pickUpIndividual =		( isset($allPostVars['family']['pick_up_drop_off_individual']) ? $allPostVars['family']['pick_up_drop_off_individual']: null);
	}
	
	if( isset($allPostVars['medical']) )
	{
		$updateMedical = true;
		$comments = 					( isset($allPostVars['medical']['comments']) ? $allPostVars['medical']['comments']: null);
		$hasMedicalConditions = 		( isset($allPostVars['medical']['has_medical_conditions']) ? $allPostVars['medical']['has_medical_conditions']: 'f');
		$hospitalized = 				( isset($allPostVars['medical']['hospitalized']) ? $allPostVars['medical']['hospitalized']: 'f');
		$hospitalizedDesc = 			( isset($allPostVars['medical']['hospitalized_description']) ? $allPostVars['medical']['hospitalized_description']: null);
		$currentMedicalTreatment = 		( isset($allPostVars['medical']['current_medical_treatment']) ? $allPostVars['medical']['current_medical_treatment']: 'f');	
		$currentMedicalTreatmentDesc = 	( isset($allPostVars['medical']['current_medical_treatment_description']) ? $allPostVars['medical']['current_medical_treatment_description']: null);
		$otherMedicalConditions = 		( isset($allPostVars['medical']['other_medical_conditions']) ? $allPostVars['medical']['other_medical_conditions']: 'f');
		$otherMedicalConditionsDesc = 	( isset($allPostVars['medical']['other_medical_conditions_description']) ? $allPostVars['medical']['other_medical_conditions_description']: null);
	}
	
	if( isset($allPostVars['fees']) )
	{
		$updateFees = true;
		$paymentMethod = 	( isset($allPostVars['fees']['payment_method']) ? $allPostVars['fees']['payment_method']: null);
		$installmentOption = 	( isset($allPostVars['fees']['installment_option']) ? $allPostVars['fees']['installment_option']: null);
		$feeItems =			( isset($allPostVars['fees']['feeItems']) ? $allPostVars['fees']['feeItems']: array());
		$optFeeItems =		( isset($allPostVars['fees']['optFeeItems']) ? $allPostVars['fees']['optFeeItems']: array());
	}
	

    try 
    {
        $db = getDB();
		
		if( $updateDetails )
		{
			$studentUpdate = $db->prepare(
				"UPDATE app.students
					SET gender = :gender,
						first_name = :firstName, 
						middle_name = :middleName, 
						last_name = :lastName, 
						dob = :dob, 
						student_category = :studentCat, 
						nationality = :nationality,
						student_image = :studentImg, 
						current_class = :currentClass,
						active = :active,
						new_student = :newStudent,
						admission_date= :admissionDate,
						admission_number= :admissionNumber,
						modified_date = now(),
						modified_by = :userId
					WHERE student_id = :studentId"
			);
			
			// if they changed the class, make entry into class history table
			if( $updateClass )
			{		
				$classInsert1 = $db->prepare("UPDATE app.student_class_history SET end_date = now() WHERE student_id = :studentId AND class_id = :previousClass;");

				$classInsert2 = $db->prepare("
					INSERT INTO app.student_class_history(student_id,class_id,created_by)
					VALUES(:studentId,:currentClass,:createdBy);"
				);
				
				if( $currentClassCatId != $previousClassCatId )
				{
					// need to remove any students fee items associated with old class cat
					$feeItemUpdate = $db->prepare("UPDATE app.student_fee_items 
													SET active = false,
														modified_date = now(),
														modified_by = :userId												
													WHERE fee_item_id = any(SELECT fee_item_id FROM app.fee_items WHERE :previousClassCatId = any(class_cats_restriction))");
				}
			}
				
		}
		
		else if( $updateFamily )
		{
			$studentFamilyUpdate = $db->prepare(
				"UPDATE app.students
					SET marial_status_parents = :marialStatusParents,
						adopted = :adopted, 
						adopted_age = :adoptedAge, 
						marital_separation_age = :maritalSeparationAge, 
						adoption_aware = :adoptionAware, 
						emergency_name = :emergencyName, 
						emergency_relationship = :emergencyRelationship,
						emergency_telephone = :emergencyTelephone, 
						pick_up_drop_off_individual = :pickUpIndividual,
						modified_date = now(),
						modified_by = :userId
					WHERE student_id = :studentId"
			);
		}
		
		else if( $updateMedical )
		{
			$studentMedicalUpdate = $db->prepare(
				"UPDATE app.students
					SET medical_conditions = :hasMedicalConditions,
						hospitalized = :hospitalized, 
						hospitalized_description = :hospitalizedDesc, 
						current_medical_treatment = :currentMedicalTreatment, 
						current_medical_treatment_description = :currentMedicalTreatmentDesc, 
						other_medical_conditions = :otherMedicalConditions, 
						other_medical_conditions_description = :otherMedicalConditionsDesc,
						modified_date = now(),
						modified_by = :userId
					WHERE student_id = :studentId"
			);
		}
		
		else if( $updateFees )
		{
			
			$studentUpdate = $db->prepare(
				"UPDATE app.students
					SET payment_method = :paymentMethod,
						installment_option_id = :installmentOption,
						active = true,
						modified_date = now(),
						modified_by = :userId
					WHERE student_id = :studentId"
			);
			
			// prepare the possible statements
			$feesUpdate = $db->prepare("UPDATE app.student_fee_items
										SET amount = :amount,
											payment_method = :paymentMethod,
											active = true,
											modified_date = now(),
											modified_by = :userID
										WHERE student_id = :studentID
										AND fee_item_id = :feeItemId");
			
			$feesInsert = $db->prepare("INSERT INTO app.student_fee_items(student_id, fee_item_id, amount, payment_method, created_by) 
										VALUES(:studentID,:feeItemID,:amount,:paymentMethod,:userId);"); 
			

			$inactivate = $db->prepare("UPDATE app.student_fee_items
										SET active = false,
											modified_date = now(),
											modified_by = :userId
										WHERE student_id = :studentId
										AND fee_item_id = :feeItemId"
			);
			
			$reactivate = $db->prepare("UPDATE app.student_fee_items
										SET active = true,
											modified_date = now(),
											modified_by = :userId
										WHERE student_id = :studentId
										AND fee_item_id = :feeItemId"
			);
			
			
			// get what is already set of this student
			$query = $db->prepare("SELECT fee_item_id FROM app.student_fee_items WHERE student_id = :studentID");
			$query->execute( array('studentID' => $studentId) );
			$currentFeeItems = $query->fetchAll(PDO::FETCH_OBJ);
						
		
		}
		
			
		$db->beginTransaction();
	
		if( $updateDetails )
		{
			$studentUpdate->execute( array(':studentId' => $studentId,
							':gender' => $gender,
							':firstName' => $firstName,
							':middleName' => $middleName,
							':lastName' => $lastName,
							':dob' => $dob,
							':studentCat' => $studentCat,
							':nationality' => $nationality,
							':studentImg' => $studentImg,
							':currentClass' => $currentClass,
							':active' => $active,
							':newStudent' => $newStudent,
							':admissionDate' => $admissionDate,
							':admissionNumber' => $admissionNumber,
							':userId' => $userId							
			) );
			if( $updateClass )
			{
				$classInsert1->execute(array('studentId' => $studentId, ':previousClass' => $previousClass));
				$classInsert2->execute(array('studentId' => $studentId, ':currentClass' => $currentClass,':createdBy' => $userId));
				
				if( $currentClassCatId != $previousClassCatId )
				{				
					$feeItemUpdate->execute( array(':previousClassCatId' => $previousClassCatId, ':userId' => $userId) );
				}
			}
		}
		
		else if( $updateFamily )
		{
			
			$studentFamilyUpdate->execute( array(':studentId' => $studentId,
							':marialStatusParents' => $marialStatusParents,
							':adopted' => $adopted,
							':adoptedAge' => $adoptedAge,
							':maritalSeparationAge' => $maritalSeparationAge,
							':adoptionAware' => $adoptionAware,
							':emergencyName' => $emergencyContact,
							':emergencyRelationship' => $emergencyRelation,
							':emergencyTelephone' => $emergencyPhone,
							':pickUpIndividual' => $pickUpIndividual,
							':userId' => $userId							
			) );
		}
		
		else if( $updateMedical )
		{
			$studentMedicalUpdate->execute( array(':studentId' => $studentId,
							':hasMedicalConditions' => $hasMedicalConditions,
							':hospitalized' => $hospitalized,
							':hospitalizedDesc' => $hospitalizedDesc,
							':currentMedicalTreatment' => $currentMedicalTreatment,
							':currentMedicalTreatmentDesc' => $currentMedicalTreatmentDesc,
							':otherMedicalConditions' => $otherMedicalConditions,
							':otherMedicalConditionsDesc' => $otherMedicalConditionsDesc,
							':userId' => $userId							
			) );
		}
		
		else if( $updateFees )
		{
			
			$studentUpdate->execute(array( ':paymentMethod' => $paymentMethod,
						':installmentOption' => $installmentOption,
						':userId' => $userId,
						':studentId' => $studentId)
			);
					
			
			if( count($feeItems) > 0 )
			{
				// loop through and add or update
				foreach( $feeItems as $feeItem )
				{
					$amount = 			( isset($feeItem['amount']) ? $feeItem['amount']: null);
					$paymentMethod = 	( isset($feeItem['payment_method']) ? $feeItem['payment_method']: null);
					$studentFeeItemID = ( isset($feeItem['student_fee_item_id']) ? $feeItem['student_fee_item_id']: null);
					$feeItemId = 		( isset($feeItem['fee_item_id']) ? $feeItem['fee_item_id']: null);
					
					// this fee item exists, update it, else insert
					if( $studentFeeItemID !== null && !empty($studentFeeItemID) )
					{
						// need to update all terms, current and future of this year
						// leaving previous terms as they were
						$feesUpdate->execute(array(':amount' => $amount, 
													':paymentMethod' => $paymentMethod, 
													':userID' => $userId, 
													':studentID' => $studentId, 
													':feeItemId' => $feeItemId ));

					}
					else
					{
						// check if was previously added, if so, reactivate
						// else fee items is new, add it
						// needs to be added once term term if per term fee item
						/*
						$feeData = new stdClass();
						$feeData->studentId = $studentId;
						$feeData->userId = $userId;
						$feeData->feeItem = $feeItems[$i];
						insertFeeItem($feeData, $feesInsert);
						*/
						$feesInsert->execute( array(
							':studentID' => $studentId,
							':feeItemID' => $feeItem['fee_item_id'],
							':amount' => $feeItem['amount'],
							':paymentMethod' => $feeItem['payment_method'],
							':userId' => $userId)
						);
						
					}
				}	
			}
			
			if( count($optFeeItems) > 0 )
			{
							
				// loop through and add or update
				foreach( $optFeeItems as $optFeeItem )
				{
					$amount = 			( isset($optFeeItem['amount']) ? $optFeeItem['amount']: null);
					$paymentMethod = 	( isset($optFeeItem['payment_method']) ? $optFeeItem['payment_method']: null);
					$studentFeeItemID = ( isset($optFeeItem['student_fee_item_id']) ? $optFeeItem['student_fee_item_id']: null);
					$feeItemId = 		( isset($optFeeItem['fee_item_id']) ? $optFeeItem['fee_item_id']: null);
					
					// this fee item exists, update it, else insert
					if( $studentFeeItemID !== null && !empty($studentFeeItemID) )
					{
						// need to update all terms, current and future of this year
						// leaving previous terms as they were
						$feesUpdate->execute(array(':amount' => $amount, ':paymentMethod' => $paymentMethod, ':userID' => $userId, ':studentID' => $studentId, ':feeItemId' => $feeItemId ));

					}
					else
					{
						// fee items is new, add it
						// needs to be added once term term if per term fee item
						/*
						$feeData = new stdClass();
						$feeData->studentId = $studentId;
						$feeData->userId = $userId;
						$feeData->feeItem = $optFeeItems[$i];
						insertFeeItem($feeData, $feesInsert);
						*/
						$feesInsert->execute( array(
							':studentID' => $studentId,
							':feeItemID' => $optFeeItem['fee_item_id'],
							':amount' => $optFeeItem['amount'],
							':paymentMethod' => $optFeeItem['payment_method'],
							':userId' => $userId)
						);
					}
				}
							
					
			}

			// look for fee items to remove
			// compare to what was passed in			
			foreach( $currentFeeItems as $currentFeeItem )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $feeItems as $feeItem )
				{
					if( isset($feeItem['fee_item_id']) && $feeItem['fee_item_id'] == $currentFeeItem->fee_item_id )
					{
						$deleteMe = false;
					}
				}
				foreach( $optFeeItems as $optFeeItem )
				{
					if( isset($optFeeItem['fee_item_id']) &&  $optFeeItem['fee_item_id'] == $currentFeeItem->fee_item_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$inactivate->execute(array(':studentId' => $studentId, ':feeItemId' => $currentFeeItem->fee_item_id, ':userId' => $userId));
				}
			}
			
			
		}
		
		$db->commit();
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getAllGuardians(/:status)', function ($status=true) {
    // Get all guardians
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT *, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name
							FROM app.guardians 
							WHERE active = :status 
							ORDER BY first_name, middle_name, last_name");
		$sth->execute( array(':status' => $status) ); 
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

$app->post('/addGuardian', function () use($app) {
    // Add guardian	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$studentId =			( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$guardianId =			( isset($allPostVars['guardian_id']) ? $allPostVars['guardian_id']: null);
	
	// guardian fields
	$Telephone = 			( isset($allPostVars['guardian']['telephone']) ? $allPostVars['guardian']['telephone']: null);
	$Email = 				( isset($allPostVars['guardian']['email']) ? $allPostVars['guardian']['email']: null);
	$FirstName = 			( isset($allPostVars['guardian']['first_name']) ? $allPostVars['guardian']['first_name']: null);
	$MiddleName = 			( isset($allPostVars['guardian']['middle_name']) ? $allPostVars['guardian']['middle_name']: null);
	$LastName = 			( isset($allPostVars['guardian']['last_name']) ? $allPostVars['guardian']['last_name']: null);
	$IdNumber = 			( isset($allPostVars['guardian']['id_number']) ? $allPostVars['guardian']['id_number']: null);
	$Relationship = 		( isset($allPostVars['guardian']['relationship']) ? $allPostVars['guardian']['relationship']: null);
	$Title = 				( isset($allPostVars['guardian']['title']) ? $allPostVars['guardian']['title']: null);
	$Occupation = 			( isset($allPostVars['guardian']['occupation']) ? $allPostVars['guardian']['occupation']: null);
	$Address = 				( isset($allPostVars['guardian']['address']) ? $allPostVars['guardian']['address']: null);
	$MaritalStatus = 		( isset($allPostVars['guardian']['marital_status']) ? $allPostVars['guardian']['marital_status']: null);
	$Employer = 			( isset($allPostVars['guardian']['employer']) ? $allPostVars['guardian']['employer']: null);
	$EmployerAddress = 		( isset($allPostVars['guardian']['employer_address']) ? $allPostVars['guardian']['employer_address']: null);
	$WorkEmail = 			( isset($allPostVars['guardian']['work_email']) ? $allPostVars['guardian']['work_email']: null);
	$WorkPhone =			( isset( $allPostVars['guardian']['work_phone']) ? $allPostVars['guardian']['work_phone']: null);
	
	$createdBy = 			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$login = 				( isset($allPostVars['guardian']['login']) ? $allPostVars['guardian']['login']: null);
	
	
    try 
    {
        $db = getDB();
		
		
		if( $guardianId !== null )
		{
			// using existing guardian for student
			$update = $db->prepare("UPDATE app.guardians
									SET first_name = :FirstName,
										middle_name = :MiddleName,
										last_name = :LastName,
										title = :Title,
										id_number = :IdNumber,
										address = :Address,
										telephone = :Telephone,
										email = :Email,
										marital_status = :MaritalStatus,
										occupation = :Occupation,
										employer =:Employer,
										employer_address = :EmployerAddress,
										work_email = :WorkEmail,
										work_phone = :WorkPhone,
										modified_date = now(),
										modified_by = :createdBy
									WHERE guardian_id = :guardianId");
									
			$insert = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
											VALUES(:studentId, :guardianId, :Relationship,  :createdBy)");
						
			$db->beginTransaction();
			$update->execute( array( ':guardianId' => $guardianId,
							':FirstName' => $FirstName,
							':MiddleName' => $MiddleName,
							':LastName' => $LastName,
							':Title' => $Title,
							':IdNumber' => $IdNumber,
							':Address' => $Address,
							':Telephone' => $Telephone,
							':Email' => $Email,							
							':MaritalStatus' => $MaritalStatus, 
							':Occupation' => $Occupation,
							':Employer' => $Employer,
							':EmployerAddress' => $EmployerAddress,
							':WorkEmail' => $WorkEmail, 
							':WorkPhone' => $WorkPhone,
							':createdBy' => $createdBy
			) );
			$insert->execute(array(':Relationship' => $Relationship, ':guardianId' => $guardianId, ':studentId' => $studentId, ':createdBy' => $createdBy));
			$db->commit();	
			$guardian_id = $guardianId;
		}
		else
		{
			// add new guardian and add to student
			$insert = $db->prepare("INSERT INTO app.guardians( first_name, middle_name, last_name, title, id_number, address, telephone, email, 
												marital_status, occupation, employer, employer_address, work_email, work_phone, created_by)
									VALUES(:FirstName, :MiddleName, :LastName, :Title, :IdNumber, :Address, 
											:Telephone, :Email, :MaritalStatus, :Occupation, :Employer, :EmployerAddress, :WorkEmail, 
											:WorkPhone, :createdBy);");		
			
			$insert2 = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
											VALUES(:studentId, currval('app.guardians_guardian_id_seq'), :Relationship, :createdBy)");
											
			$query = $db->prepare("SELECT currval('app.guardians_guardian_id_seq') as guardian_id");

			$db->beginTransaction();
			$insert->execute( array(
							':FirstName' => $FirstName,
							':MiddleName' => $MiddleName,
							':LastName' => $LastName,
							':Title' => $Title,
							':IdNumber' => $IdNumber,
							':Address' => $Address,
							':Telephone' => $Telephone,
							':Email' => $Email,							
							':MaritalStatus' => $MaritalStatus, 
							':Occupation' => $Occupation,
							':Employer' => $Employer,
							':EmployerAddress' => $EmployerAddress,
							':WorkEmail' => $WorkEmail, 
							':WorkPhone' => $WorkPhone,
							':createdBy' => $createdBy
			) );
			$insert2->execute(array(':Relationship' => $Relationship, ':studentId' => $studentId, ':createdBy' => $createdBy));
			$query->execute();
			$db->commit();	
			$result = $query->fetch(PDO::FETCH_OBJ);
			$guardian_id = $result->guardian_id;
		}
		
		 $db = null;

		// if login data was passed
		if( $login !== null )
		{
			$allPostVars['guardian']['student_id'] = $studentId;
			$allPostVars['guardian']['guardian_id'] = $guardian_id;
			createParentLogin($allPostVars['guardian']);
		}
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "data" => $guardian_id));
       
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateGuardian', function () use($app) {
    // update guardian	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$guardianId =			( isset($allPostVars['guardian']['guardian_id']) ? $allPostVars['guardian']['guardian_id']: null);
	$studentId =			( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	
	// guardian fields
	$Telephone = 			( isset($allPostVars['guardian']['telephone']) ? $allPostVars['guardian']['telephone']: null);
	$Email = 				( isset($allPostVars['guardian']['email']) ? $allPostVars['guardian']['email']: null);
	$FirstName = 			( isset($allPostVars['guardian']['first_name']) ? $allPostVars['guardian']['first_name']: null);
	$MiddleName = 			( isset($allPostVars['guardian']['middle_name']) ? $allPostVars['guardian']['middle_name']: null);
	$LastName = 			( isset($allPostVars['guardian']['last_name']) ? $allPostVars['guardian']['last_name']: null);
	$IdNumber = 			( isset($allPostVars['guardian']['id_number']) ? $allPostVars['guardian']['id_number']: null);
	$Relationship = 		( isset($allPostVars['guardian']['relationship']) ? $allPostVars['guardian']['relationship']: null);
	$Title = 				( isset($allPostVars['guardian']['title']) ? $allPostVars['guardian']['title']: null);
	$Occupation = 			( isset($allPostVars['guardian']['occupation']) ? $allPostVars['guardian']['occupation']: null);
	$Address = 				( isset($allPostVars['guardian']['address']) ? $allPostVars['guardian']['address']: null);
	$MaritalStatus = 		( isset($allPostVars['guardian']['marital_status']) ? $allPostVars['guardian']['marital_status']: null);
	$Employer = 			( isset($allPostVars['guardian']['employer']) ? $allPostVars['guardian']['employer']: null);
	$EmployerAddress = 		( isset($allPostVars['guardian']['employer_address']) ? $allPostVars['guardian']['employer_address']: null);
	$WorkEmail = 			( isset($allPostVars['guardian']['work_email']) ? $allPostVars['guardian']['work_email']: null);
	$WorkPhone =			( isset( $allPostVars['guardian']['work_phone']) ? $allPostVars['guardian']['work_phone']: null);
	
	$userId = 				( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$login = 				( isset($allPostVars['guardian']['login']) ? $allPostVars['guardian']['login']: null);

    try 
    {
        $db = getDB();

		$sth1 = $db->prepare("UPDATE app.guardians
								SET first_name = :FirstName, 
									middle_name = :MiddleName, 
									last_name =:LastName, 
									title = :Title, 
									id_number = :IdNumber,
									address = :Address,
									telephone = :Telephone, 
									email = :Email, 
									marital_status = :MaritalStatus, 
									occupation = :Occupation, 
									employer = :Employer, 
									employer_address = :EmployerAddress, 
									work_email = :WorkEmail, 
									work_phone = :WorkPhone,
									modified_date = now(),
									modified_by = :userId
								WHERE guardian_id = :guardianId"
								);		
		$sth2 = $db->prepare("UPDATE app.student_guardians
								SET relationship= :Relationship,
									modified_date = now(),
									modified_by = :userId
							   WHERE student_id = :studentId	
							   AND guardian_id = :guardianId");
										
		$db->beginTransaction();
		$sth1->execute( array(':guardianId' => $guardianId,
						':FirstName' => $FirstName,
						':MiddleName' => $MiddleName,
						':LastName' => $LastName,
						':Title' => $Title,
						':IdNumber' => $IdNumber,
						':Address' => $Address,
						':Telephone' => $Telephone,
						':Email' => $Email,
						':MaritalStatus' => $MaritalStatus, 
						':Occupation' => $Occupation,
						':Employer' => $Employer,
						':EmployerAddress' => $EmployerAddress,
						':WorkEmail' => $WorkEmail, 
						':WorkPhone' => $WorkPhone,
						':userId' => $userId
		) );
		$sth2->execute(array(':guardianId' => $guardianId, ':studentId' => $studentId, ':Relationship' => $Relationship,':userId' => $userId));
		$db->commit();
		$db = null;
		
		// if login data was passed
		if( $login !== null )
		{
			$allPostVars['guardian']['student_id'] = $studentId;
			createParentLogin($allPostVars['guardian']);
		}
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
       
 
 
    } catch(PDOException $e) {
		
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->delete('/deleteGuardian/:student_id/:guardian_id', function ($studentId,$guardianId) {
    // delete guardian
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		// remove from client database
        $db = getDB();
		$sth = $db->prepare("DELETE FROM app.student_guardians WHERE guardian_id = :guardianId AND student_id = :studentId");					
		$sth->execute( array(':guardianId' => $guardianId, ':studentId' => $studentId) );
		$db = null;
		
		// if they have a parent login, remove association with student
		$subdomain = getSubDomain();
		$db2 = getLoginDB();
		$sth2 = $db2->prepare("DELETE FROM parent_students WHERE guardian_id = :guardianId AND student_id = :studentId AND subdomain = :subdomain");									
		$sth2->execute( array(':guardianId' => $guardianId, ':studentId' => $studentId, ':subdomain' => $subdomain) );
		$db2 = null;
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        
 
 
    } catch(PDOException $e) {
		
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addMedicalConditions', function () use($app) {
    // Add medical conditions	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$studentId =	( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$userId = 		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
	// medical fields
	$medicalConditions = ( isset($allPostVars['medicalConditions']) ? $allPostVars['medicalConditions']: null);
	
    try 
    {
        $db = getDB();

		$studentUpdate = $db->prepare("UPDATE app.students SET medical_conditions = true WHERE student_id = :studentId");
		$conditionInsert = $db->prepare("INSERT INTO app.student_medical_history(student_id, illness_condition, age, comments, created_by) 
		VALUES(?,?,?,?,?);"); 
		$query = $db->prepare("SELECT currval('app.student_medical_history_medical_id_seq') as medical_id, now() as date_medical_added");
		
		
        $results = array();
		// loop through the medical conditions and insert
		// place the resulting id in array for return
		foreach( $medicalConditions as $medicalCondition )
		{
			$db->beginTransaction();
			$studentUpdate->execute(array(':studentId' => $studentId));
			$conditionInsert->execute( array(
						$studentId,
						$medicalCondition['illness_condition'],
						$medicalCondition['age'],
						$medicalCondition['comments'],
						$userId
			) );			
			$query->execute();
			$db->commit();
			
			$results[] = $query->fetch(PDO::FETCH_OBJ);
			
		}
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "data" => $results));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateMedicalConditions', function () use($app) {
    // update medical condition	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$medicalId =		( isset($allPostVars['medicalCondition']['medical_id']) ? $allPostVars['medicalCondition']['medical_id']: null);
	$illnessCondition = ( isset($allPostVars['medicalCondition']['illness_condition']) ? $allPostVars['medicalCondition']['illness_condition']: null);
	$age = 				( isset($allPostVars['medicalCondition']['age']) ? $allPostVars['medicalCondition']['age']: null);
	$comments = 		( isset($allPostVars['medicalCondition']['comments']) ? $allPostVars['medicalCondition']['comments']: null);
	$userId = 			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();

		$sth = $db->prepare("UPDATE app.student_medical_history
								SET illness_condition = :illnessCondition, 
									age = :age, 
									comments =:comments, 
									modified_date = now(),
									modified_by = :userId
								WHERE medical_id = :medicalId"
								);		
										
		$sth->execute( array(':medicalId' => $medicalId,
						':illnessCondition' => $illnessCondition,
						':age' => $age,
						':comments' => $comments,
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

$app->delete('/deleteMedicalCondition/:medical_id', function ($medicalId) {
    // delete guardian
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.student_medical_history WHERE medical_id = :medicalId");		
										
		$sth->execute( array(':medicalId' => $medicalId) );
 
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

$app->get('/getGuardiansChildren/:guardian_id', function ($guardianId) {
    // Get all children associated with a guardian
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT students.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name
							FROM app.student_guardians 
							INNER JOIN app.students ON student_guardians.student_id = students.student_id
							WHERE guardian_id = :guardianId 
							ORDER BY students.first_name, students.middle_name, students.last_name");
		$sth->execute( array(':guardianId' => $guardianId) ); 
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

$app->get('/getMISLogin/:id_number', function ($idNumber) {
    // Get mis login for id number
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getLoginDB();
        $sth = $db->prepare("SELECT parent_id, first_name, middle_name, last_name, email, id_number, active as login_active, username,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name,
									(SELECT array_agg(student_id) FROM parent_students WHERE parent_id = parents.parent_id) as student_ids
							FROM parents
							WHERE id_number = :idNumber");
		$sth->execute( array(':idNumber' => $idNumber) ); 
        $results = $sth->fetch(PDO::FETCH_OBJ);
 
        if($results) {
		
			// convert pgarray to php array			
			$results->student_ids = pg_array_parse($results->student_ids);
			
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

$app->get('/checkUsername/:username', function ($username) {
    // Check that username is unique
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getLoginDB();
        $sth = $db->prepare("SELECT parent_id
							FROM parents
							WHERE username = :username");
		$sth->execute( array(':username' => $username) ); 
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

$app->get('/checkIdNumber/:id_number', function ($idNumber) {
    // Check that id number is unique
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT guardian_id
							FROM app.guardians
							WHERE id_number = :idNumber");
		$sth->execute( array(':idNumber' => $idNumber) ); 
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


// ************** Parent Portal  ****************** //
$app->post('/parentLogin', function () use($app) {
	// Log parent in
		$allPostVars = $app->request->post();
		$username = $allPostVars['user_name'];
		$pwd = $allPostVars['user_pwd'];
		
		//$hash = password_hash($pwd, PASSWORD_BCRYPT);
	 
		try 
		{
			$db = getLoginDB();
			$sth = $db->prepare("SELECT parents.parent_id, username, active, first_name, middle_name, last_name, email, 
										first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name
									FROM parents
									INNER JOIN parent_students ON parents.parent_id = parent_students.parent_id
									WHERE username= :username 
									AND password = :password 
									AND active is true");
			$sth->execute( array(':username' => $username, ':password' => $pwd) );
	 
			$result = $sth->fetch(PDO::FETCH_OBJ);

			if($result) {
				// get the parents students and add to result
				$sth2 = $db->prepare("SELECT student_id, subdomain, dbusername, dbpassword FROM parent_students WHERE parent_id = :parentId");
				$sth2->execute(array(':parentId' => $result->parent_id));
				$students = $sth2->fetchAll(PDO::FETCH_OBJ);
				$db = null;
				
				$studentDetails = Array();
				$curSubDomain = '';
				foreach( $students as $student )
				{
					// get individual student details
					// only get new db connection if different subdomain
					if( $curSubDomain != $student->subdomain ) 
					{
						if( $db !== null ) $db = null;
						$db = setDBConnection($student->subdomain);
					}
					$sth3 = $db->prepare("SELECT student_id, first_name, middle_name, last_name, student_image,
												 first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
												 students.active, class_name, class_id,
												 (SELECT value FROM app.settings WHERE name = 'School Name') as school_name
											FROM app.students
											INNER JOIN app.classes ON students.current_class = classes.class_id
											WHERE student_id = :studentId");
					$sth3->execute(array(':studentId' => $student->student_id));
					$details = $sth3->fetch(PDO::FETCH_OBJ);
					$details->school = $student->subdomain;
					$studentDetails[] = $details;
					$curSubDomain = $student->subdomain;
										
				}
				
				$result->students = $studentDetails;
				
				$app->response->setStatus(200);
				$app->response()->headers->set('Content-Type', 'application/json');
				$db = null;
				
				echo json_encode(array('response' => 'success', 'data' => $result ));
				
			} else {
				throw new PDOException('The username or password you have entered is incorrect.');
			}
	 
		} catch(PDOException $e) {
			$app->response()->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		}
});

$app->get('/getBlog/:school/:student_id', function ($school, $studentId) {
    // Get blog associated with student
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
		$db = setDBConnection($school);
        $sth = $db->prepare("SELECT blog_name, title, body, blog_posts.creation_date, 
								(employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name) as created_by,
								post_type
							  FROM app.blogs
							  LEFT JOIN app.blog_posts ON blogs.blog_id = blog_posts.blog_id
							  LEFT JOIN app.employees ON blog_posts.created_by = employees.emp_id
							  LEFT JOIN app.blog_post_types ON blog_posts.post_type_id = blog_post_types.post_type_id
							  INNER JOIN app.students ON blogs.class_id = students.current_class
							  WHERE student_id = :studentId
							  ORDER BY blog_posts.creation_date desc
							  ");
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

$app->get('/getHomework/:school/:student_id', function ($school, $studentId) {
    // Get homework associated with student for current week
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
		$db = setDBConnection($school);
        $sth = $db->prepare("SELECT homework_date, description
							  FROM app.homework
							  INNER JOIN app.students ON homework.class_id = students.current_class
							  WHERE student_id = :studentId
							  AND homework_date between date_trunc('week', now())::date	and (date_trunc('week', now())+ '6 days'::interval)::date
							  ORDER BY homework_date asc
							  ");
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


// Run app
$app->run();

/*
function insertFeeItem($feeData, $feesInsert)
{
	if( $feeData->feeItem['frequency'] == 'per term' )
	{
		$terms = getCurrentTerms();		
		
		for( $j = 0; $j < count($terms); $j++) 
		{
			$feesInsert->execute( array(
					':studentID' => $feeData->studentId,
					':feeItemID' => $feeData->feeItem['fee_item_id'],
					':amount' => $feeData->feeItem['amount'],
					':paymentMethod' => $feeData->feeItem['payment_method'],
					':userId' => $feeData->userId,
					':termId' => $terms[$j]['term_id']
			) );
		}
	}
	else if( $feeData->feeItem['frequency'] == 'yearly' )
	{
		$feesInsert->execute( array(
				':studentID' => $feeData->studentId,
				':feeItemID' => $feeData->feeItem['fee_item_id'],
				':amount' => $feeData->feeItem['amount'],
				':paymentMethod' => $feeData->feeItem['payment_method'],
				':userId' => $feeData->userId,
				':termId' => $terms[0]['term_id']
		) );
	}
	else
	{
		$feesInsert->execute( array(
				':studentID' => $feeData->studentId,
				':feeItemID' => $feeData->feeItem['fee_item_id'],
				':amount' => $feeData->feeItem['amount'],
				':paymentMethod' => $feeData->feeItem['payment_method'],
				':userId' => $feeData->userId,
				':termId' => null
		) );
	}
	
}
*/

function createParentLogin($data)
{	
	$username = 		( isset($data['login']['username']) ? $data['login']['username']: null);
	$password = 		( isset($data['login']['password']) ? $data['login']['password']: null);
	$loginActive = 		( isset($data['login']['login_active']) ? ( $data['login']['login_active'] == 'true' ? 't' : 'f') : 'f');
	$parentId = 		( isset($data['login']['parent_id']) ? $data['login']['parent_id']: null);
	$exists = 			( isset($data['login']['exists']) ? $data['login']['exists']: false);
	$createdBy = 		( isset($data['user_id']) ? $data['user_id']: null);
	$studentId = 		( isset($data['student_id']) ? $data['student_id']: null);
	$guardianId = 		( isset($data['guardian_id']) ? $data['guardian_id']: null);
	$email = 			( isset($data['email']) ? $data['email']: null);
	$firstName = 		( isset($data['first_name']) ? $data['first_name']: null);
	$middleName = 		( isset($data['middle_name']) ? $data['middle_name']: null);
	$lastName = 		( isset($data['last_name']) ? $data['last_name']: null);
	$idNumber = 		( isset($data['id_number']) ? $data['id_number']: null);
	
	$dbData = getClientDBData();
	$subdomain = $dbData->subdomain;
	$dbUser = $dbData->dbuser;
	$dbPass = $dbData->dbpass;
	
	$updateLogin = false;
	$addParentStudent = false;
	$addLogin = false;	
	
	$db = getMISDB();
	
	if( $parentId !== null )
	{
		$updateLogin = true;
		// existing login, update status
		$parentUpdate = $db->prepare("UPDATE parents 
										SET first_name = :firstName,
											middle_name = :middleName,
											last_name = :lastName,
											email = :email,
											active = :loginActive, 
											modified_date = now() 
										WHERE parent_id = :parentId");
		
		// add student if not already added
		if( !$exists )
		{
			$addParentStudent = true;
			$insertLoginStudent = $db->prepare("INSERT INTO parent_students(parent_id, guardian_id, student_id, subdomain, dbusername, dbpassword, created_by)
												VALUES(:parentId, :guardianId, :studentId, :subdomain, :dbUser, :dbPass, :createdBy)");
		}
	}
	else
	{
		$addLogin = true;
		// new login, create
		$insertLogin = $db->prepare("INSERT INTO parents(first_name, middle_name, last_name, email, id_number, username, password, active)
									VALUES(:firstName, :middleName, :lastName, :email, :idNumber, :username, :password, :active)");
		$insertLoginStudent = $db->prepare("INSERT INTO parent_students(parent_id, guardian_id, student_id, subdomain, dbusername, dbpassword, created_by)
											VALUES(currval('parents_parent_id_seq'), :guardianId, :studentId, :subdomain, :dbUser, :dbPass, :createdBy)");
	}
	
	try{
		
		$db->beginTransaction();
		
		if( $addLogin ) 
		{
			$insertLogin->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName, 
										 ':email' => $email, ':idNumber' => $idNumber, ':username' => $username, ':password' => $password, 
										 ':active' => $loginActive) );
			$insertLoginStudent->execute( array(':guardianId' => $guardianId, ':studentId' => $studentId, ':subdomain' => $subdomain, ':dbUser' => $dbUser, ':dbPass' => $dbPass, 
										':createdBy' => $createdBy) );
		}
		else if( $updateLogin )
		{
			$parentUpdate->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName, 
											':email' => $email, ':loginActive' => $loginActive, ':parentId' => $parentId) );
			if( $addParentStudent )
			{
				$insertLoginStudent->execute( array(':guardianId' => $guardianId, ':parentId' => $parentId, ':studentId' => $studentId, ':subdomain' => $subdomain, 
										':dbUser' => $dbUser, ':dbPass' => $dbPass, ':createdBy' => $createdBy) );
			}
		}
		
		$db->commit();		
		$db = null;
	} catch(PDOException $e) {
		echo $e->getMessage();
       // $app->response()->setStatus(404);
		//$app->response()->headers->set('Content-Type', 'application/json');
       // echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }
	
}

function getCurrentTerms()
{
	$db = getDB();
	$termQuery = $db->prepare("SELECT term_id
								FROM app.terms
								WHERE date_part('year',start_date) = date_part('year', now())");
	$termQuery->execute();
	$terms = $termQuery->fetchAll(PDO::FETCH_ASSOC);
	return $terms;
}

function pg_array_parse($literal)
{
    if ($literal == '') return;
    preg_match_all('/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|[0-9]+|x[0-9a-f]+))*)"\s*)(,|(?<!^\{)(?=\}$))/i', $literal, $matches, PREG_SET_ORDER);
    $values = array();
    foreach ($matches as $match) {
        $values[] = $match[3] != '' ? stripcslashes($match[3]) : (strtolower($match[2]) == 'null' ? null : $match[2]);
    }
    return $values;
}

/*
* https://gist.github.com/jorgeguberte/1270672
* DateTime::diff on PHP has a bug on Windows systems where it always outputs 6015. Here's a workaround]
* that i found on http://acme-tech.net/blog/2010/10/12/php-datetimediff-returns-6015/
*/
function dateDiff($dt1, $dt2) {
    $dt1 = new DateTime($dt1);
    $dt2 = new DateTime($dt2);
    $ts1 = $dt1->format('Y-m-d');
    $ts2 = $dt2->format('Y-m-d');
    $diff = abs(strtotime($ts1)-strtotime($ts2));
    $diff/= 3600*24;
    return $diff;
}