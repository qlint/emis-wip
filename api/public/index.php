<?php
require '../vendor/autoload.php';
require '../lib/CorsSlim.php';
//require '../lib/password.php';
require 'db.php';

// Prepare app
$app = new \Slim\Slim();
$corsOptions = array(
    "origin" => "*",
    "exposeHeaders" => array("Content-Type", "X-Requested-With", "X-authentication", "X-client"),
    "allowMethods" => array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS')
);
$cors = new \CorsSlim\CorsSlim($corsOptions);

$app->add($cors);

// Create monolog logger and store logger in container as singleton 
// (Singleton resources retrieve the same log resource definition each time)
$app->container->singleton('log', function () {
    $log = new \Monolog\Logger('slim-skeleton');
    $log->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log', \Monolog\Logger::DEBUG));
    return $log;
});
/*
// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
*/


// Define routes
// ************** Login  ****************** //
$app->post('/login/', function () use($app){
    // Log user in
	$allPostVars = $app->request->post();
	$username = $allPostVars['user_name'];
	$pwd = $allPostVars['user_pwd'];
	
	//$hash = password_hash($pwd, PASSWORD_BCRYPT);
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, username, active, first_name, middle_name, last_name, email, user_type
								FROM hog.users WHERE username= :username AND password = :password AND active is true");
        $sth->execute( array(':username' => $username, ':password' => $pwd) );
 
        $result = $sth->fetch(PDO::FETCH_OBJ);

        if($result) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
			
			// grad the users settings and add to result
			$sth2 = $db->prepare("SELECT name, value FROM hog.settings");
			$sth2->execute();
			$settings = $sth2->fetchAll(PDO::FETCH_OBJ);
			$result->settings = $settings;			
			
            echo json_encode(array('response' => 'success', 'data' => $result ));
            $db = null;
        } else {
            throw new PDOException('No user found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
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
        $sth = $db->prepare("SELECT class_id, class_name, class_cat_id, teacher_id, active
            FROM hog.classes
            WHERE active = :status
			ORDER BY class_cat_id, class_id"); 
       $sth->execute( array(':status' => $status ) );
 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getClasses/:classCatid(/:status)', function ($id, $status=true) {
    //Show classes for specific class category
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT class_id, class_name, class_cat_id, teacher_id, active
            FROM hog.classes
            WHERE class_cat_id = :id
			AND active = :status
			ORDER BY class_id");
        $sth->execute( array(':id' => $id, ':status' => $status) );
 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addClass/', function () use($app) {
    // Add class
	
	$allPostVars = $app->request->post();
	$className = $allPostVars['class_name'];
	$classCatId = $allPostVars['class_cat_id'];
	$teacherId = $allPostVars['teacher_id'];
	$userId = $allPostVars['user_id'];
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO hog.classes(class_name, class_cat_id, teacher_id, created_by) 
            VALUES(:className, :classCatId, :teacherId, :userId)");
 
        $sth->execute( array(':className' => $className, ':classCatId' => $classCatId, ':teacherId' => $teacherId, ':userId' => $userId ) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateClass/', function () use($app) {
    // Update class
	
	$allPostVars = $app->request->post();
	$classId = $allPostVars['class_id'];
	$className = $allPostVars['class_name'];
	$classCatId = $allPostVars['class_cat_id'];
	$teacherId = $allPostVars['teacher_id'];
	$active = $allPostVars['active'];
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE hog.classes
			SET class_name = :className,
				class_cat_id = :classCatId,
				teacher_id = :teacherId,
				active = :active
            WHERE class_id = :classId");
 
        $sth->execute( array(':className' => $className, ':classCatId' => $classCatId, ':teacherId' => $teacherId, ':active' => $active ) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});


// ************** Class Categories  ****************** //
$app->get('/getClassCats/', function () {
    //Show all class categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT class_cat_id, class_cat_name FROM hog.class_cats ORDER BY class_cat_id");
        $sth->execute();
 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addClassCat/', function () use($app) {
    // Add class category
	
	$allPostVars = $app->request->post();
	$classCatName = $allPostVars['class_cat_name'];

    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO hog.class_cats(class_cat_name) 
            VALUES(:classCatName)");
        $sth->execute( array(':classCatName' => $classCatName) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateClassCat/', function () use($app) {
    // Update class category
	
	$allPostVars = $app->request->post();
	$classCatName = $allPostVars['class_cat_name'];
	$classCatId = $allPostVars['class_cat_id'];
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE hog.class_cats
			SET class_cat_name = :classCatName
            WHERE class_cat_id = :classCatId"); 
        $sth->execute( array(':classCatName' => $classCatName, ':classCatId' => $classCatId) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});


// ************** Countries  ****************** //
$app->get('/getCountries/', function ($status=true) {
    // Get countries
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT countries_name FROM hog.countries ORDER BY countries_name");
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
        $sth = $db->prepare("SELECT dept_id, dept_name, active
            FROM hog.departments
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
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addDepartment/', function () use($app) {
    // Add department
	
	$allPostVars = $app->request->post();
	$deptName = $allPostVars['dept_name'];
	$userId = $allPostVars['user_id'];
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO hog.departments(dept_name, created_by) 
            VALUES(:deptName, :userId)");
 
        $sth->execute( array(':deptName' => $deptName, ':userId' => $userId ) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateDepartment/', function () use($app) {
    // Update department
	
	$allPostVars = $app->request->post();
	$deptId = $allPostVars['dept_id'];
	$deptName = $allPostVars['dept_name'];
	$active = $allPostVars['active'];
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE hog.departments
			SET dept_name = :deptName,
				active = :active
            WHERE dept_id = :deptId");
 
        $sth->execute( array(':deptName' => $deptName, ':deptId' => $deptId, ':active' => $active ) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
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
        $sth = $db->prepare("SELECT emp_id, emp_cat_id, dept_id, emp_number, id_number, gender, first_name,
									middle_name, last_name, initials, dob, country, active, telephone, email, joined_date,
									job_title, qualifications, experience, additional_info, emp_image
							 FROM hog.employees 
							 WHERE active = :status 
							 ORDER BY first_name, middle_name, last_name");
        $sth->execute( array(':status' => $status)); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
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
							 FROM hog.employee 
							 WHERE emp_id = :id");
        $sth->execute( array(':id' => $id)); 
        $results = $sth->fetch(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addEmployee/', function () use($app) {
    // Add employee
	
	$allPostVars = $app->request->post();
	$empCatId = $allPostVars['emp_cat_id'];
	$deptId = $allPostVars['dept_id'];
	$empNumber = $allPostVars['emp_number'];
	$idNumber = $allPostVars['id_number'];
	$gender = $allPostVars['gender'];
	$firstName = $allPostVars['first_name'];
	$middleName = $allPostVars['middle_name'];
	$lastName = $allPostVars['last_name'];
	$initials = $allPostVars['initials'];
	$dob = $allPostVars['dob'];
	$country = $allPostVars['country'];
	$active = $allPostVars['active'];
	$telephone = $allPostVars['telephone'];
	$email = $allPostVars['email'];
	$joinedDate = $allPostVars['joined_date'];
	$jobTitle = $allPostVars['job_title'];
	$qualifications = $allPostVars['qualifications'];
	$experience = $allPostVars['experience'];
	$additionalInfo = $allPostVars['additional_info'];
	$createdBy = $allPostVars['user_id'];
	$empImage = $allPostVars['emp_image'];

    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO hog.employees(emp_cat_id, dept_id, emp_number, id_number, gender, first_name, middle_name, last_name, initials, dob, country, active, telephone, email, joined_date, job_title, qualifications, experience, additional_info, created_by, emp_image) 
            VALUES(:empCatId,:deptId,:empNumber,:idNumber,:gender,:firstName,:middleName,:lastName,:initials,:dob,:country,:active,:telephone,:email,:joinedDate,:jobTitle,:qualifications,:experience,:additionalInfo,:createdBy,:empImage)"); 
        $sth->execute( array(':empCatId' => $empCatId,
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
							':empImage' => $empImage
		) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateEmployee/', function () use($app) {
    // Update employee
	
	$allPostVars = $app->request->post();
	$empId = $allPostVars['emp_id'];
	$empCatId = $allPostVars['emp_cat_id'];
	$deptId = $allPostVars['dept_id'];
	$empNumber = $allPostVars['emp_number'];
	$idNumber = $allPostVars['id_number'];
	$gender = $allPostVars['gender'];
	$firstName = $allPostVars['first_name'];
	$middleName = $allPostVars['middle_name'];
	$lastName = $allPostVars['last_name'];
	$initials = $allPostVars['initials'];
	$dob = $allPostVars['dob'];
	$country = $allPostVars['country'];
	$active = $allPostVars['active'];
	$telephone = $allPostVars['telephone'];
	$email = $allPostVars['email'];
	$joinedDate = $allPostVars['joined_date'];
	$jobTitle = $allPostVars['job_title'];
	$qualifications = $allPostVars['qualifications'];
	$experience = $allPostVars['experience'];
	$additionalInfo = $allPostVars['additional_info'];
	$empImage = $allPostVars['emp_image'];
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE hog.employees
			SET emp_cat_id = :empCatId,
				dept_id = :deptId,
				emp_number = :empNumber,
				id_number = :idNumber,
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
				joined_date = :joinedDate,
				qualifications = :qualifications,
				experience = :experience,
				additional_info = :additionalInfo,
				emp_image = :empImage
            WHERE emp_id = :empId");
 
        $sth->execute( array(':empId' => $empId,
							':empCatId' => $empCatId,
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
							':empImage' => $empImage
		) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});


// ************** Employee Categories  ****************** //
$app->get('/getEmployeeCats/', function () {
    //Show all employee categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT emp_cat_id, emp_cat_name FROM hog.employee_cats ORDER BY emp_cat_id");
        $sth->execute();
 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addEmployeeCat/', function () use($app) {
    // Add employee category
	
	$allPostVars = $app->request->post();
	$empCatName = $allPostVars['emp_cat_name'];

    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO hog.employee_cats(emp_cat_name) 
            VALUES(:empCatName)"); 
        $sth->execute( array(':empCatName' => $empCatName) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateEmployeeCat/', function () use($app) {
    // Update employee category
	
	$allPostVars = $app->request->post();
	$empCatName = $allPostVars['emp_cat_name'];
	$empCatId = $allPostVars['emp_cat_id'];
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE hog.employee_cats
			SET emp_cat_name = :empCatName
            WHERE emp_cat_id = :empCatId"); 
        $sth->execute( array(':empCatName' => $empCatName, ':empCatId' => $empCatId) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("status" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});


// ************** Exam Marks  ****************** //
$app->get('/getExamMarks/:class/:year/:term/:type', function ($class,$year,$term,$type) {
    //Show exam marks
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
								subject_name, mark
							FROM hog.exam_marks 
							INNER JOIN hog.students USING (student_id)
							INNER JOIN hog.terms USING (term_id)
							INNER JOIN hog.subjects USING (subject_id)
							WHERE class_id = :class
							AND term_id = :term
							AND date_part('year', terms.start_date) = :year
							AND exam_type = :type
							ORDER BY student_name");
        $sth->execute( array(':class' => $class, ':year' => $year, ':term' => $term, ':type' => $type)); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
		
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});


// ************** Fee Items  ****************** //
$app->get('/getFeeItems/', function () {
    //Show fee items
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount, frequency, active, class_cats_restriction, optional, new_student_only
							FROM hog.fee_items 
							ORDER BY fee_item_id");
        $sth->execute(); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
		
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            throw new PDOException('No records found.');
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
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
        $sth = $db->prepare("SELECT *
							 FROM hog.students 
							 WHERE active = :status 
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
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentDetails(/:studentId)', function ($studentId) {
    //Show all students
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT *
							 FROM hog.students 
							 WHERE student_id = :studentID 
							 ORDER BY first_name, middle_name, last_name");
        $sth->execute( array(':studentID' => $studentId)); 
        $results = $sth->fetch(PDO::FETCH_OBJ);
 
        if($results) {
			
			// get parents
			$sth2 = $db->prepare("SELECT *
							 FROM hog.student_guardians 
							 WHERE student_id = :studentID
							 AND active = true
							 ORDER BY relationship, last_name, first_name, middle_name");
			$sth2->execute( array(':studentID' => $studentId));
			$results2 = $sth2->fetchAll(PDO::FETCH_OBJ);

			$results->guardians = $results2;
						
			// get medical history
			$sth3 = $db->prepare("SELECT medical_id, illness_condition, comments, creation_date as date_medical_added
							 FROM hog.student_medical_history 
							 WHERE student_id = :studentID
							 ORDER BY creation_date");
			$sth3->execute( array(':studentID' => $studentId));
			$results3 = $sth3->fetchAll(PDO::FETCH_OBJ);

			$results->medical_history = $results3;
			
			// get fee items
			$sth4 = $db->prepare("SELECT student_fee_item_id, student_fee_items.fee_item_id, fee_item, amount, payment_method
							 FROM hog.student_fee_items 
							 INNER JOIN hog.fee_items on student_fee_items.fee_item_id = fee_items.fee_item_id
							 WHERE student_id = :studentID
							 AND student_fee_items.active = true
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

$app->get('/getStudentFeeSummary(/:studentId)', function ($studentId) {
    // Return students fee summary
	// list all fee items, their opening balances, what has been paid and what's left
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get fee items
		$sth = $db->prepare("SELECT student_fee_item_id, student_fee_items.fee_item_id, fee_item, amount, payment_method
						 FROM hog.student_fee_items 
						 INNER JOIN hog.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
						 WHERE student_id = :studentID
						 AND student_fee_items.active = true
						 ORDER BY fee_items.fee_item_id");
		$sth->execute( array(':studentID' => $studentId));
		$results = $sth->fetchAll(PDO::FETCH_OBJ);
			
		// get payments
        $sth2 =  $db->prepare("SELECT payments.payment_id, payment_date, payments.amount as payment_amount, payment_method, slip_cheque_no, replacement_payment, 
									  fee_item, payment_fee_items.amount as fee_item_amount_paid
							 FROM hog.payments 
							 LEFT JOIN hog.payment_fee_items 
								LEFT JOIN hog.fee_items
								ON payment_fee_items.fee_item_id = fee_items.fee_item_id
							 ON payments.payment_id = payment_fee_items.payment_id
							 WHERE student_id = :studentID
							 AND reversed = false
							 ORDER BY fee_items.fee_item_id;");
        $sth2->execute( array(':studentID' => $studentId)); 
        $results2 = $sth2->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {

			// need to loop through the fee items and apply payments
			
			for( $i=0; $i < $results.length; $i++ )
			{
				
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
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

/*
-- get next term
select term_id, term_name, start_date, end_date
from hog.terms
where
CASE WHEN date_part('month',now()::date) in (1,5,9) THEN
		('now()'::date + interval '4 months')::date BETWEEN (date_trunc('month',start_date))::date and (date_trunc('month', end_date)+'1month'::interval-'1day'::interval)::date
         WHEN date_part('month','now()'::date) in (2,6,10) THEN         
		('now()'::date + interval '3 months')::date BETWEEN (date_trunc('month',start_date))::date and (date_trunc('month', end_date)+'1month'::interval-'1day'::interval)::date
         WHEN date_part('month','now()'::date) in (3,7,11) THEN
		('now()'::date + interval '2 months')::date BETWEEN (date_trunc('month',start_date))::date and (date_trunc('month', end_date)+'1month'::interval-'1day'::interval)::date
         ELSE
		('now()'::date + interval '1 months')::date BETWEEN (date_trunc('month',start_date))::date and (date_trunc('month', end_date)+'1month'::interval-'1day'::interval)::date
    END
*/

$app->post('/addStudent/', function () use($app) {
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
	
	// guardian fields
	$fatherTelephone = 			( isset($allPostVars['guardian']['father']['telephone']) ? $allPostVars['guardian']['father']['telephone']: null);
	$fatherEmail = 				( isset($allPostVars['guardian']['father']['email']) ? $allPostVars['guardian']['father']['email']: null);
	$fatherFirstName = 			( isset($allPostVars['guardian']['father']['first_name']) ? $allPostVars['guardian']['father']['first_name']: null);
	$fatherMiddleName = 		( isset($allPostVars['guardian']['father']['middle_name']) ? $allPostVars['guardian']['father']['middle_name']: null);
	$fatherLastName = 			( isset($allPostVars['guardian']['father']['last_name']) ? $allPostVars['guardian']['father']['last_name']: null);
	$fatherIdNumber = 			( isset($allPostVars['guardian']['father']['id_number']) ? $allPostVars['guardian']['father']['id_number']: null);
	$fatherRelationship = 		( isset($allPostVars['guardian']['father']['relationship']) ? $allPostVars['guardian']['father']['relationship']: null);
	$fatherTitle = 				( isset($allPostVars['guardian']['father']['title']) ? $allPostVars['guardian']['father']['title']: null);
	$fatherOccupation = 		( isset($allPostVars['guardian']['father']['occupation']) ? $allPostVars['guardian']['father']['occupation']: null);
	$fatherAddress = 			( isset($allPostVars['guardian']['father']['address']) ? $allPostVars['guardian']['father']['address']: null);
	$fatherMaritalStatus = 		( isset($allPostVars['guardian']['father']['marital_status']) ? $allPostVars['guardian']['father']['marital_status']: null);
	$fatherEmployer = 			( isset($allPostVars['guardian']['father']['employer']) ? $allPostVars['guardian']['father']['employer']: null);
	$fatherEmployerAddress = 	( isset($allPostVars['guardian']['father']['employer_address']) ? $allPostVars['guardian']['father']['employer_address']: null);
	$fatherWorkEmail = 			( isset($allPostVars['guardian']['father']['work_email']) ? $allPostVars['guardian']['father']['work_email']: null);
	$fatherWorkPhone =			( isset( $allPostVars['guardian']['father']['work_phone']) ? $allPostVars['guardian']['father']['work_phone']: null);
	
	// mother fields
	$motherTelephone = 			( isset($allPostVars['guardian']['mother']['telephone']) ? $allPostVars['guardian']['mother']['telephone']: null);
	$motherEmail = 				( isset($allPostVars['guardian']['mother']['email']) ? $allPostVars['guardian']['mother']['email']: null);
	$motherFirstName = 			( isset($allPostVars['guardian']['mother']['first_name']) ? $allPostVars['guardian']['mother']['first_name']: null);
	$motherMiddleName = 		( isset($allPostVars['guardian']['mother']['middle_name']) ? $allPostVars['guardian']['mother']['middle_name']: null);
	$motherLastName = 			( isset($allPostVars['guardian']['mother']['last_name']) ? $allPostVars['guardian']['mother']['last_name']: null);
	$motherIdNumber = 			( isset($allPostVars['guardian']['mother']['id_number']) ? $allPostVars['guardian']['mother']['id_number']: null);
	$motherRelationship = 		( isset($allPostVars['guardian']['mother']['relationship']) ? $allPostVars['guardian']['mother']['relationship']: null);
	$motherTitle = 				( isset($allPostVars['guardian']['mother']['title']) ? $allPostVars['guardian']['mother']['title']: null);
	$motherOccupation = 		( isset($allPostVars['guardian']['mother']['occupation']) ? $allPostVars['guardian']['mother']['occupation']: null);
	$motherAddress = 			( isset($allPostVars['guardian']['mother']['address']) ? $allPostVars['guardian']['mother']['address']: null);
	$motherMaritalStatus = 		( isset($allPostVars['guardian']['mother']['marital_status']) ? $allPostVars['guardian']['mother']['marital_status']: null);
	$motherEmployer = 			( isset($allPostVars['guardian']['mother']['employer']) ? $allPostVars['guardian']['mother']['employer']: null);
	$motherEmployerAddress = 	( isset($allPostVars['guardian']['mother']['employer_address']) ? $allPostVars['guardian']['mother']['employer_address']: null);
	$motherWorkEmail = 			( isset($allPostVars['guardian']['mother']['work_email']) ? $allPostVars['guardian']['mother']['work_email']: null);
	$motherWorkPhone =			( isset( $allPostVars['guardian']['mother']['work_phone']) ? $allPostVars['guardian']['mother']['work_phone']: null);
	
	// medical condition fields
	$medicalConditions = ( isset($allPostVars['medicalConditions']) ? $allPostVars['medicalConditions']: null);
	
	// fee item fields
	$feeItems = ( isset($allPostVars['feeItems']) ? $allPostVars['feeItems']: null);

	$createdBy = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);


    try 
    {
        $db = getDB();
        $studentInsert = $db->prepare("INSERT INTO hog.students(admission_number, gender, first_name, middle_name, last_name, dob, student_category, nationality,
																student_image, current_class, payment_method, active, created_by, admission_date, marial_status_parents, 
																adopted, adopted_age, marital_separation_age, adoption_aware, comments, medical_conditions, hospitalized,
																hospitalized_description, current_medical_treatment, current_medical_treatment_description,
																other_medical_conditions, other_medical_conditions_description, 
																emergency_name, emergency_relationship, emergency_telephone, pick_up_drop_off_individual) 
            VALUES(:admissionNumber,:gender,:firstName,:middleName,:lastName,:dob,:studentCat,:nationality,:studentImg, :currentClass, :paymentMethod, :active, :createdBy, 
					:admissionDate, :marialStatusParents, :adopted, :adoptedAge, :maritalSeparationAge, :adoptionAware, :comments, :hasMedicalConditions, :hospitalized,
					:hospitalizedDesc, :currentMedicalTreatment, :currentMedicalTreatmentDesc, :otherMedicalConditions, :otherMedicalConditionsDesc, 
					:emergencyContact, :emergencyRelation, :emergencyPhone, :pickUpIndividual);"); 
					
		$studentClassInsert = $db->prepare("INSERT INTO hog.student_class_history(student_id,class_id,created_by)
											VALUES(currval('hog.students_student_id_seq'),:currentClass,:createdBy);");
        
		if( $fatherFirstName !== null )
		{
			// add primary contact
			$fatherInsert = $db->prepare("INSERT INTO hog.student_guardians(student_id, first_name, middle_name, last_name, title, id_number, address, telephone, email, 
																			relationship, marital_status, occupation, employer, employer_address, work_email, work_phone, created_by)
				VALUES(currval('hog.students_student_id_seq'), :fatherFirstName, :fatherMiddleName, :fatherLastName, :fatherTitle, :fatherIdNumber, :fatherAddress, 
						:fatherTelephone, :fatherEmail, :fatherRelationship, :fatherMaritalStatus, :fatherOccupation, :fatherEmployer, :fatherEmployerAddress, :fatherWorkEmail, 
						:fatherWorkPhone, :createdBy);");
		}
		if( $motherFirstName !== null )
		{
			// add secondary contact
			$motherInsert = $db->prepare("INSERT INTO hog.student_guardians(student_id, first_name, middle_name, last_name, title, id_number, address, telephone, email, 
																			relationship, marital_status, occupation, employer, employer_address, work_email, work_phone, created_by)
				VALUES(currval('hog.students_student_id_seq'), :motherFirstName, :motherMiddleName, :motherLastName, :motherTitle, :motherIdNumber, :motherAddress, 
					:motherTelephone, :motherEmail, :motherRelationship, :motherMaritalStatus, :motherOccupation, :motherEmployer, :motherEmployerAddress, :motherWorkEmail, 
					:motherWorkPhone, :createdBy);");
		}
		if( count($medicalConditions) > 0 )
		{
			$conditionInsert = $db->prepare("INSERT INTO hog.student_medical_history(student_id, illness_condition, age, comments, created_by) 
            VALUES(currval('hog.students_student_id_seq'),?,?,?,?);"); 
        
		}
		if( count($feeItems) > 0 )
		{
			$feesInsert = $db->prepare("INSERT INTO hog.student_fee_items(student_id, fee_item_id, amount, payment_method, created_by) 
            VALUES(currval('hog.students_student_id_seq'),?,?,?,?);"); 
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
							':pickUpIndividual' => $pickUpIndividual
		) );
		
		$studentClassInsert->execute(array(':currentClass' => $currentClass,':createdBy' => $createdBy));
		
		if( $fatherFirstName !== null )
		{
			$fatherInsert->execute( array(':fatherFirstName' => $fatherFirstName,
							':fatherMiddleName' => $fatherMiddleName,
							':fatherLastName' => $fatherLastName,
							':fatherTitle' => $fatherTitle,
							':fatherIdNumber' => $fatherIdNumber,
							':fatherAddress' => $fatherAddress,
							':fatherTelephone' => $fatherTelephone,
							':fatherEmail' => $fatherEmail,
							':fatherRelationship' => $fatherRelationship,
							':fatherMaritalStatus' => $fatherMaritalStatus, 
							':fatherOccupation' => $fatherOccupation,
							':fatherEmployer' => $fatherEmployer,
							':fatherEmployerAddress' => $fatherEmployerAddress,
							':fatherWorkEmail' => $fatherWorkEmail, 
							':fatherWorkPhone' => $fatherWorkPhone,
							':createdBy' => $createdBy
			) );
		}
		
		if( $motherFirstName !== null )
		{
			$motherInsert->execute( array(':motherFirstName' => $motherFirstName,
							':motherMiddleName' => $motherMiddleName,
							':motherLastName' => $motherLastName,
							':motherTitle' => $motherTitle,
							':motherIdNumber' => $motherIdNumber,
							':motherAddress' => $motherAddress,
							':motherTelephone' => $motherTelephone,
							':motherEmail' => $motherEmail,
							':motherRelationship' => $motherRelationship,
							':motherMaritalStatus' => $motherMaritalStatus, 
							':motherOccupation' => $motherOccupation,
							':motherEmployer' => $motherEmployer,
							':motherEmployerAddress' => $motherEmployerAddress,
							':motherWorkEmail' => $motherWorkEmail, 
							':motherWorkPhone' => $motherWorkPhone,
							':createdBy' => $createdBy
			) );
		}
		
		if( count($medicalConditions) > 0 )
		{
        
			for( $i=0; $i < count($medicalConditions); $i++ )
			{
				$conditionInsert->execute( array($medicalConditions[$i]['medical_condition'],
							$medicalConditions[$i]['age'],
							$medicalConditions[$i]['comments'],
							$createdBy
				) );
			}
		}
		
		if( count($feeItems) > 0 )
		{
        
			for( $i=0; $i < count($feeItems); $i++ )
			{
				$feesInsert->execute( array($feeItems[$i]['fee_item_id'],
							$feeItems[$i]['amount'],
							$feeItems[$i]['payment_method'],
							$createdBy
				) );
			}
		}
		
		$db->commit();
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		echo $e->getMessage();
		$db->rollBack();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addGuardian/', function () use($app) {
    // Add guardian	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
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
	
	$createdBy = 			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);


    try 
    {
        $db = getDB();

		$insert = $db->prepare("INSERT INTO hog.student_guardians(student_id, first_name, middle_name, last_name, title, id_number, address, telephone, email, 
										    relationship, marital_status, occupation, employer, employer_address, work_email, work_phone, created_by)
								VALUES(:studentID, :FirstName, :MiddleName, :LastName, :Title, :IdNumber, :Address, 
										:Telephone, :Email, :Relationship, :MaritalStatus, :Occupation, :Employer, :EmployerAddress, :WorkEmail, 
										:WorkPhone, :createdBy);");		
										
		$query = $db->prepare("SELECT currval('hog.student_guardians_guardian_id_seq')");

		$db->beginTransaction();
		$insert->execute( array(':studentID' => $studentId,
						':FirstName' => $FirstName,
						':MiddleName' => $MiddleName,
						':LastName' => $LastName,
						':Title' => $Title,
						':IdNumber' => $IdNumber,
						':Address' => $Address,
						':Telephone' => $Telephone,
						':Email' => $Email,
						':Relationship' => $Relationship,
						':MaritalStatus' => $MaritalStatus, 
						':Occupation' => $Occupation,
						':Employer' => $Employer,
						':EmployerAddress' => $EmployerAddress,
						':WorkEmail' => $WorkEmail, 
						':WorkPhone' => $WorkPhone,
						':createdBy' => $createdBy
		) );
		$query->execute();
		$db->commit();
		
		$result = $query->fetch(PDO::FETCH_OBJ);
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "data" => $result));
        $db = null;
 
 
    } catch(PDOException $e) {
		echo $e->getMessage();
		$db->rollBack();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateGuardian/', function () use($app) {
    // update guardian	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$guardianId =			( isset($allPostVars['guardian']['guardian_id']) ? $allPostVars['guardian']['guardian_id']: null);
	
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


    try 
    {
        $db = getDB();

		$sth = $db->prepare("UPDATE hog.student_guardians
								SET first_name = :FirstName, 
									middle_name = :MiddleName, 
									last_name =:LastName, 
									title = :Title, 
									id_number = :IdNumber,
									address = :Address,
									telephone = :Telephone, 
									email = :Email, 
									relationship = :Relationship, 
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
										
		$sth->execute( array(':guardianId' => $guardianId,
						':FirstName' => $FirstName,
						':MiddleName' => $MiddleName,
						':LastName' => $LastName,
						':Title' => $Title,
						':IdNumber' => $IdNumber,
						':Address' => $Address,
						':Telephone' => $Telephone,
						':Email' => $Email,
						':Relationship' => $Relationship,
						':MaritalStatus' => $MaritalStatus, 
						':Occupation' => $Occupation,
						':Employer' => $Employer,
						':EmployerAddress' => $EmployerAddress,
						':WorkEmail' => $WorkEmail, 
						':WorkPhone' => $WorkPhone,
						':userId' => $userId
		) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		echo $e->getMessage();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/deleteGuardian/', function () use($app) {
    // update guardian	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$guardianId =			( isset($allPostVars['guardian_id']) ? $allPostVar['guardian_id']: null);
	$userId = 				( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);


    try 
    {
        $db = getDB();

		$sth = $db->prepare("UPDATE hog.student_guardians
								SET active = false
								WHERE guardian_id = :guardianId"
								);		
										
		$sth->execute( array(':guardianId' => $guardianId
						':userId' => $userId
		) );
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		echo $e->getMessage();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});



// Run app
$app->run();
