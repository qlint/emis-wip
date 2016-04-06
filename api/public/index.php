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
            throw new PDOException('The username or password you have entered is incorrect.');
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
$app->get('/getFeeItems(/:status)', function ($status = true) {
    //Show fee items
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount, frequency, active, class_cats_restriction, optional, new_student_only
							FROM hog.fee_items 
							WHERE active = :status
							AND optional is false
							ORDER BY fee_item_id");
        $sth->execute( array(':status' => $status) ); 
        $requiredItems = $sth->fetchAll(PDO::FETCH_OBJ);
		
		$results = new stdClass();
		$results->required_items = $requiredItems;
		
		$sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount, frequency, active, class_cats_restriction, optional, new_student_only
							FROM hog.fee_items 
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
			$sth3 = $db->prepare("SELECT medical_id, illness_condition, age, comments, creation_date as date_medical_added
							 FROM hog.student_medical_history 
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
									(select sum(amount) from hog.payment_fee_items where student_fee_item_id = student_fee_items.student_fee_item_id) as payment_made
								FROM hog.student_fee_items 
								INNER JOIN hog.fee_items on student_fee_items.fee_item_id = fee_items.fee_item_id
								WHERE student_id = :studentID
								AND student_fee_items.active = true
								AND (term_id = (select term_id from hog.current_term) OR term_id is null)
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

$app->get('/getStudentBalance(/:studentId)', function ($studentId) {
    // Return students next payment
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get total amount of student fee items
		// calculate the amount due and due date
		// calculate the balance owing
		$sth = $db->prepare("-- find all fee items for student up to current term
							SELECT 
								term_name, fee_item, frequency, payment_method, 
								-- yearly grand total of all fees to be paid
								CASE WHEN fee_items.frequency in ('once','yearly') THEN SF.amount 
									 WHEN fee_items.frequency = 'per term'         THEN SF.amount*3 
								END AS annual_amount,

								CASE WHEN SF.payment_method = 'Per Month' THEN
									-- need to have paid the amount_per_payperiod * number of past months this year
									CASE WHEN fee_items.frequency = 'per term' THEN SF.amount/4 ELSE SF.amount/12 END * date_part('month', now())
									WHEN SF.payment_method = 'Per Term' THEN
									-- need to have paid the amount_per_payperiod * number of past terms this year
									CASE WHEN fee_items.frequency = 'per term' THEN	SF.amount ELSE SF.amount/3 END * (SELECT COUNT(*) FROM hog.terms WHERE date_trunc('year', start_date) = date_trunc('year',now()) AND start_date < now())
									ELSE
									SF.amount
								END as amount_due,
								
								coalesce((select sum(amount) from hog.payment_fee_items where student_fee_item_id = SF.student_fee_item_id),0) as amount_paid,
								
								CASE WHEN SF.payment_method = 'Per Month' THEN
									-- need to have paid the amount_per_payperiod * number of past months this year
									CASE WHEN fee_items.frequency = 'per term' THEN SF.amount/4 ELSE SF.amount/12 END * date_part('month', now())
									WHEN SF.payment_method = 'Per Term' THEN
									-- need to have paid the amount_per_payperiod * number of past terms this year
									CASE WHEN fee_items.frequency = 'per term' THEN	SF.amount ELSE SF.amount/3 END * (SELECT COUNT(*) FROM hog.terms WHERE date_trunc('year', start_date) = date_trunc('year',now()) AND start_date < now())
									ELSE
									SF.amount
								END - coalesce((select sum(amount) from hog.payment_fee_items where student_fee_item_id = SF.student_fee_item_id),0) as amount_outstanding,

								CASE WHEN SF.amount - coalesce((select sum(amount) from hog.payment_fee_items where student_fee_item_id = SF.student_fee_item_id),0) > 0  THEN
									CASE WHEN SF.payment_method = 'Once' THEN 
										DATE_PART('day', now() - (SELECT start_date FROM hog.terms WHERE date_trunc('year', start_date) = date_trunc('year',now()) AND term_name = 'Term 1')::timestamp)
										 WHEN  SF.payment_method in ('Per Term','Annually') THEN
										DATE_PART('day', now() - (SELECT start_date FROM hog.terms WHERE term_id = SF.term_id )::timestamp)
										 WHEN SF.payment_method = 'Per Month' THEN
										DATE_PART('day', now() - (SELECT date_trunc('month', now()))::timestamp)
									END
								END AS age,				
								
								CASE WHEN SF.amount = (select sum(amount) from hog.payment_fee_items where student_fee_item_id = SF.student_fee_item_id) then
									true
								ELSE
									false
								END as paid_in_full,
								CASE WHEN SF.payment_method = 'Per Term' then 'Term' 
									 WHEN SF.payment_method = 'Per Month' then'Month'
									 ELSE 'Year'
								END as pay_period
							FROM hog.student_fee_items SF
							INNER JOIN hog.fee_items using (fee_item_id)
							LEFT JOIN hog.payment_fee_items using (student_fee_item_id)
							LEFT JOIN hog.terms using (term_id)
							WHERE SF.student_id = :studentID
							AND SF.active is true
							AND (terms.start_date < now() OR terms.start_date is null)
							GROUP BY term_name, fee_item, frequency, payment_method, annual_amount, amount_due, amount_paid, amount_outstanding, age, paid_in_full, pay_period
							ORDER BY term_name, fee_item
							");
        $sth->execute( array(':studentID' => $studentId)); 
        $fees = $sth->fetchAll(PDO::FETCH_OBJ);
		
		
		if( $fees )
		{
		
			$sth2 = $db->prepare("SELECT term_name, payment_method,
										sum(CASE WHEN payment_method = 'Per Month' THEN
											-- need to have paid the amount_per_payperiod * number of past months this year
											CASE WHEN fee_items.frequency = 'per term' THEN amount/4 ELSE amount/12 END
											WHEN payment_method = 'Per Term' THEN
											-- need to have paid the amount_per_payperiod * number of past terms this year
											CASE WHEN frequency = 'per term' THEN	amount ELSE amount/3 END
											ELSE amount
											END) as amount_due,
										-- what is the due date of next pay period
										CASE WHEN payment_method in ('Once','Annually') THEN
												-- due date is start of first term
												(SELECT start_date FROM hog.terms WHERE date_trunc('year', start_date) = date_trunc('year',now()) AND term_name = 'Term 1')
											 WHEN payment_method = 'Per Term' THEN
												-- due date is next term
												(SELECT start_date FROM hog.next_term)
											 WHEN payment_method = 'Per Month' THEN
												-- due date is next month
												(SELECT date_trunc('month', now() + interval '1 month')::date)
										END AS next_due_date,
										(
											SELECT sum(diff) FROM (
												SELECT (payments.amount - (select sum(amount) from hog.payment_fee_items where payment_id = payments.payment_id )) as diff
												FROM hog.payments
												LEFT JOIN hog.payment_fee_items ON payments.payment_id = payment_fee_items.payment_id
												WHERE student_id = :studentID
												AND reversed is false
												AND replacement_payment is false
												AND (student_fee_item_id is null OR  payments.amount - (select sum(amount) from hog.payment_fee_items where payment_id = payments.payment_id )  > 0)
												GROUP BY payment_fee_items.payment_id, payments.amount, payments.payment_id
											) AS q
										) AS unapplied_payments	
									FROM hog.student_fee_items SF
									INNER JOIN hog.fee_items using (fee_item_id)
									LEFT JOIN hog.terms using (term_id)
									WHERE SF.student_id = :studentID
									AND CASE WHEN SF.payment_method = 'Per Term' THEN
											-- get fee items for next term
											SF.term_id = (select term_id from hog.next_term)
											 WHEN SF.payment_method = 'Per Month' then
											-- get fee items for the next month
											(now() + '1 mon'::interval) >= date_trunc('month'::text, terms.start_date::timestamp with time zone) AND (now() + '1 mon'::interval) <= (terms.end_date + '1 mon'::interval - '1 day'::interval)     
										END
									GROUP BY term_name, next_due_date, payment_method
									ORDER BY CASE payment_method
										WHEN 'Per Month' THEN 1
										WHEN 'Per Term' THEN 2
										WHEN 'Annually' THEN 3
										WHEN 'Once' THEN 4
									END");
			$sth2->execute( array(':studentID' => $studentId)); 
			$details = $sth2->fetch(PDO::FETCH_OBJ);
			
			if( $details )
			{
					
				$totalDue = 0;
				$totalPaid = 0;
				$totalBalance = 0;
				for( $i=0; $i < count($fees); $i++)
				{
					$totalDue += $fees[$i]->amount_due;
					$totalPaid += $fees[$i]->amount_paid;			
					$totalBalance += $fees[$i]->amount_outstanding;
				}
			
				//  set the next due summary
				$feeSummary = new Stdclass();
				$feeSummary->next_due_date = ( $totalBalance > 0 ? 'Immediately' : $details->next_due_date);
				$feeSummary->unapplied_payments = $details->unapplied_payments;
				$feeSummary->next_term = $details->term_name;	
				$feeSummary->next_payperiod_due = $details->amount_due;
				
				// is the next due date within 30 days?
				$diff = dateDiff("now", $details->next_due_date);
				$feeSummary->within30days = ( $diff < 30 ? true : false ); 
				
				$feeSummary->total_amount_per_payperiod = $totalDue;
				$feeSummary->total_amount_paid = $totalPaid;
				$feeSummary->total_balance = $totalBalance;
			}
			
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

$app->get('/getStudentPayments(/:studentId)', function ($studentId) {
    // Return students payments
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get fee items
		$sth = $db->prepare("SELECT payment_date, payments.amount, payment_method, 
									(SELECT array_agg(fee_item) 
									 FROM hog.payment_fee_items 
									 INNER JOIN hog.student_fee_items using (student_fee_item_id)
									 INNER JOIN hog.fee_items using (fee_item_id) 
									 WHERE payment_id = payments.payment_id
									 ) as applied_to,
									 (
										SELECT sum(diff) FROM (
											SELECT (p.amount - (select sum(amount) from hog.payment_fee_items where payment_id = p.payment_id )) as diff
											FROM hog.payments as p
											LEFT JOIN hog.payment_fee_items ON p.payment_id = payment_fee_items.payment_id
											WHERE p.payment_id = payments.payment_id
											AND reversed is false
											AND replacement_payment is false
											AND (student_fee_item_id is null OR  p.amount - (select sum(amount) from hog.payment_fee_items where payment_id = p.payment_id )  > 0)
											GROUP BY payment_fee_items.payment_id, p.amount, p.payment_id
										) AS q
									) AS unapplied_amount,
									reversed, reversed_date, replacement_payment
								FROM hog.payments						
								WHERE student_id = :studentID");
		$sth->execute( array(':studentID' => $studentId));
		$results = $sth->fetchAll(PDO::FETCH_OBJ);
		
 
        if($results) {			
			// convert pgarray to php array
			
			for( $i=0; $i < count($results); $i++ )
			{
				$results[$i]->applied_to = pg_array_parse($results[$i]->applied_to);
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
		
		/* get terms for this year for future use */
		$termQuery = $db->prepare("SELECT term_id
									FROM hog.terms
									WHERE date_part('year',start_date) = date_part('year', now())");
		$termQuery->execute();
		$terms = $termQuery->fetchAll(PDO::FETCH_ASSOC);
		
		
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
			$feesInsert = $db->prepare("INSERT INTO hog.student_fee_items(student_id, fee_item_id, amount, payment_method, created_by, term_id) 
										VALUES(currval('hog.students_student_id_seq'),?,?,?,?,?);"); 
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
				// if the fee item has a frequency of per term, need to enter once per term
				// if frequency is yearly, enter first term
				//if once, leave term null
				if( $feeItems[$i]['frequency'] == 'per term' )
				{
					for( $j = 0; $j < count($terms); $j++) 
					{
						$feesInsert->execute( array($feeItems[$i]['fee_item_id'],
								$feeItems[$i]['amount'],
								$feeItems[$i]['payment_method'],
								$createdBy,
								$terms[$j]['term_id']
						) );
					}
				}
				else if( $feeItems[$i]['frequency'] == 'yearly' )
				{
					$feesInsert->execute( array($feeItems[$i]['fee_item_id'],
								$feeItems[$i]['amount'],
								$feeItems[$i]['payment_method'],
								$createdBy,
								$terms[0]['term_id']
						) );
				}
				else
				{
					$feesInsert->execute( array($feeItems[$i]['fee_item_id'],
								$feeItems[$i]['amount'],
								$feeItems[$i]['payment_method'],
								$createdBy,
								null
					) );
				}
				
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

$app->put('/updateStudent/', function () use($app) {
    // Update student	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$updateDetails = false;
	$updateFamily = false;
	$updateMedical = false;
	$updateFees = false;
	
//	$admissionNumber =				( isset($allPostVars['admission_number']) ? $allPostVars['admission_number']: null);
//	$admissionDate = 				( isset($allPostVars['admission_date']) ? $allPostVars['admission_date']: null);

	$studentId = ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

	if( isset($allPostVars['details']) )
	{
		$updateDetails = true;
		$gender = 			( isset($allPostVars['details']['gender']) ? $allPostVars['details']['gender']: null);
		$firstName = 		( isset($allPostVars['details']['first_name']) ? $allPostVars['details']['first_name']: null);
		$middleName = 		( isset($allPostVars['details']['middle_name']) ? $allPostVars['details']['middle_name']: null);
		$lastName =			( isset($allPostVars['details']['last_name']) ? $allPostVars['details']['last_name']: null);
		$dob = 				( isset($allPostVars['details']['dob']) ? $allPostVars['details']['dob']: null);
		$studentCat = 		( isset($allPostVars['details']['student_category']) ? $allPostVars['details']['student_category']: null);
		$nationality = 		( isset($allPostVars['details']['nationality']) ? $allPostVars['details']['nationality']: null);
		$currentClass = 	( isset($allPostVars['details']['current_class']) ? $allPostVars['details']['current_class']: null);
		$previousClass = 	( isset($allPostVars['details']['previous_class']) ? $allPostVars['details']['previous_class']: null);
		$updateClass = 		( isset($allPostVars['details']['update_class']) ? $allPostVars['details']['update_class']: false);
		$studentImg = 		( isset($allPostVars['details']['student_image']) ? $allPostVars['details']['student_image']: null);
		$active = 			( isset($allPostVars['details']['active']) ? $allPostVars['details']['active']: true);
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
		$feeItems =			( isset($allPostVars['fees']['feeItems']) ? $allPostVars['fees']['feeItems']: array());
		$optFeeItems =		( isset($allPostVars['fees']['optFeeItems']) ? $allPostVars['fees']['optFeeItems']: array());
	}
	

    try 
    {
        $db = getDB();
		
		if( $updateDetails )
		{
			$studentUpdate = $db->prepare(
				"UPDATE hog.students
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
						modified_date = now(),
						modified_by = :userId
					WHERE student_id = :studentId"
			);
			
			// if they changed the class, make entry into class history table
			if( $updateClass )
			{		
				$classInsert1 = $db->prepare("UPDATE hog.student_class_history SET end_date = now() WHERE student_id = :studentId AND class_id = :previousClass;");

				$classInsert2 = $db->prepare("
					INSERT INTO hog.student_class_history(student_id,class_id,created_by)
					VALUES(:studentId,:currentClass,:createdBy);"
				);
			}
				
		}
		
		else if( $updateFamily )
		{
			$studentFamilyUpdate = $db->prepare(
				"UPDATE hog.students
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
				"UPDATE hog.students
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
				"UPDATE hog.students
					SET payment_method = :paymentMethod,
						active = true,
						modified_date = now(),
						modified_by = :userId
					WHERE student_id = :studentId"
			);
			
			// prepare the possible statements
			$feesUpdate = $db->prepare("UPDATE hog.student_fee_items
										SET amount = :amount,
											payment_method = :paymentMethod,
											active = true,
											modified_date = now(),
											modified_by = :userID
										WHERE student_id = :studentID
										AND fee_item_id = :feeItemId
										AND (term_id is null OR term_id = ANY(SELECT term_id 
																			  FROM hog.terms 
																			  WHERE start_date > now()
																			  AND date_part('year', start_date) = date_part('year',now())
																			  UNION
																			  SELECT term_id from hog.current_term)
											)"
			);
			
			$feesInsert = $db->prepare("INSERT INTO hog.student_fee_items(student_id, fee_item_id, amount, payment_method, created_by, term_id) 
										VALUES(:studentID,:feeItemID,:amount,:paymentMethod,:userId,:termId);"); 
			

			$inactivate = $db->prepare("UPDATE hog.student_fee_items
										SET active = false,
											modified_date = now(),
											modified_by = :userId
										WHERE student_id = :studentId
										AND fee_item_id = :feeItemId"
			);
			
			$reactivate = $db->prepare("UPDATE hog.student_fee_items
										SET active = true,
											modified_date = now(),
											modified_by = :userId
										WHERE student_id = :studentId
										AND fee_item_id = :feeItemId"
			);
		
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
							':userId' => $userId							
			) );
			if( $updateClass )
			{
				$classInsert1->execute(array('studentId' => $studentId, ':previousClass' => $previousClass));
				$classInsert2->execute(array('studentId' => $studentId, ':currentClass' => $currentClass,':createdBy' => $userId));
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
						':userId' => $userId,
						':studentId' => $studentId)
			);
			
			// figure out what is new
			// figure out what is update
			// figure out what to inactive
			
			// get terms for this year 
			$termQuery = $db->prepare("SELECT term_id
										FROM hog.terms
										WHERE date_part('year',start_date) = date_part('year', now())");
			$termQuery->execute();
			$terms = $termQuery->fetchAll(PDO::FETCH_ASSOC);
			
			
			// get what is already set of this student
			$query = $db->prepare("SELECT fee_item_id FROM hog.student_fee_items WHERE student_id = :studentID");
			$query->execute( array('studentID' => $studentId) );
			$currentFeeItems = $query->fetchAll(PDO::FETCH_OBJ);
			
			if( count($feeItems) > 0 )
			{
				// loop through and add or update
				for( $i=0; $i<count($feeItems); $i++ )
				{
					$amount = 			( isset($feeItems[$i]['amount']) ? $feeItems[$i]['amount']: null);
					$paymentMethod = 	( isset($feeItems[$i]['payment_method']) ? $feeItems[$i]['payment_method']: null);
					$studentFeeItemID = ( isset($feeItems[$i]['student_fee_item_id']) ? $feeItems[$i]['student_fee_item_id']: null);
					$feeItemId = 		( isset($feeItems[$i]['fee_item_id']) ? $feeItems[$i]['fee_item_id']: null);
					
					// this fee item exists, update it, else insert
					if( $studentFeeItemID !== null && !empty($studentFeeItemID) )
					{
						// need to update all terms, current and future of this year
						// leaving previous terms as they were
						$feesUpdate->execute(array(':amount' => $amount, ':paymentMethod' => $paymentMethod, ':userID' => $userId, ':studentID' => $studentId, ':feeItemId' => $feeItemId ));

					}
					else
					{
						// check if was previously added, if so, reactivate
						// else fee items is new, add it
						// needs to be added once term term if per term fee item
						$found = false;
							
						// if found, reactivate
						for( $j=0; $j<count($currentFeeItems); $j++ )
						{
							if( $feeItems[$i]['fee_item_id'] == $currentFeeItems[$j]->fee_item_id )
							{
								$reactivate->execute(array(':studentId' => $studentId, ':feeItemId' => $currentFeeItems[$j]->fee_item_id, ':userId' => $userId));
								$found = true;
								break;
							}
						}
												
						if( !$found )
						{						
							if( $feeItems[$i]['frequency'] == 'per term' )
							{
								for( $j = 0; $j < count($terms); $j++) 
								{
									$feesInsert->execute( array(
											':studentID' => $studentId,
											':feeItemID' => $feeItems[$i]['fee_item_id'],
											':amount' => $feeItems[$i]['amount'],
											':paymentMethod' => $feeItems[$i]['payment_method'],
											':userId' => $userId,
											':termId' => $terms[$j]['term_id']
									) );
								}
							}
							else if( $feeItems[$i]['frequency'] == 'yearly' )
							{
								$feesInsert->execute( array(
										':studentID' => $studentId,
										':feeItemID' => $feeItems[$i]['fee_item_id'],
										':amount' => $feeItems[$i]['amount'],
										':paymentMethod' => $feeItems[$i]['payment_method'],
										':userId' => $userId,
										':termId' => $terms[0]['term_id']
								) );
							}
							else
							{
								$feesInsert->execute( array(
											':studentID' => $studentId,
											':feeItemID' => $feeItems[$i]['fee_item_id'],
											':amount' => $feeItems[$i]['amount'],
											':paymentMethod' => $feeItems[$i]['payment_method'],
											':userId' => $userId,
											':termId' => null
									) );
							}
						}
					}
				}
				
					
			}
			
			if( count($optFeeItems) > 0 )
			{
							
				// loop through and add or update
				for( $i=0; $i<count($optFeeItems); $i++ )
				{
					$amount = 			( isset($optFeeItems[$i]['amount']) ? $optFeeItems[$i]['amount']: null);
					$paymentMethod = 	( isset($optFeeItems[$i]['payment_method']) ? $optFeeItems[$i]['payment_method']: null);
					$studentFeeItemID = ( isset($optFeeItems[$i]['student_fee_item_id']) ? $optFeeItems[$i]['student_fee_item_id']: null);
					$feeItemId = 		( isset($optFeeItems[$i]['fee_item_id']) ? $optFeeItems[$i]['fee_item_id']: null);
					
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
						
						$found = false;
							
						// if found, reactivate
						for( $j=0; $j<count($currentFeeItems); $j++ )
						{
							if( $optFeeItems[$i]['fee_item_id'] == $currentFeeItems[$j]->fee_item_id )
							{
								$reactivate->execute(array(':studentId' => $studentId, ':feeItemId' => $currentFeeItems[$j]->fee_item_id, ':userId' => $userId));
								$found = true;
								break;
							}
						}
												
						if( !$found )
						{													
							if( $optFeeItems[$i]['frequency'] == 'per term' )
							{
								for( $j = 0; $j < count($terms); $j++) 
								{
									$feesInsert->execute( array(
											':studentID' => $studentId,
											':feeItemID' => $optFeeItems[$i]['fee_item_id'],
											':amount' => $optFeeItems[$i]['amount'],
											':paymentMethod' => $optFeeItems[$i]['payment_method'],
											':userId' => $userId,
											':termId' => $terms[$j]['term_id']
									) );
								}
							}
							else if( $optFeeItems[$i]['frequency'] == 'yearly' )
							{
								$feesInsert->execute( array(
										':studentID' => $studentId,
										':feeItemID' => $optFeeItems[$i]['fee_item_id'],
										':amount' => $optFeeItems[$i]['amount'],
										':paymentMethod' => $optFeeItems[$i]['payment_method'],
										':userId' => $userId,
										':termId' => $terms[0]['term_id']
								) );
							}
							else
							{
								$feesInsert->execute( array(
											':studentID' => $studentId,
											':feeItemID' => $optFeeItems[$i]['fee_item_id'],
											':amount' => $optFeeItems[$i]['amount'],
											':paymentMethod' => $optFeeItems[$i]['payment_method'],
											':userId' => $userId,
											':termId' => null
									) );
							}
						}
					}
				}
							
					
			}

			// look for fee items to remove
			// compare to what was passed in			
			for( $i=0; $i<count($currentFeeItems); $i++ )
			{	
				$deleteMe = true;
				// if found, do not delete
				for( $j=0; $j<count($feeItems); $j++ )
				{
					if( $feeItems[$j]['fee_item_id'] == $currentFeeItems[$i]->fee_item_id )
					{
						$deleteMe = false;
					}
				}
				for( $j=0; $j<count($optFeeItems); $j++ )
				{
					if( $optFeeItems[$j]['fee_item_id'] == $currentFeeItems[$i]->fee_item_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$inactivate->execute(array(':studentId' => $studentId, ':feeItemId' => $currentFeeItems[$i]->fee_item_id, ':userId' => $userId));
				}
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

$app->delete('/deleteGuardian/:guardian_id', function ($guardianId) {
    // delete guardian
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM hog.student_guardians WHERE guardian_id = :guardianId");		
										
		$sth->execute( array(':guardianId' => $guardianId) );
 
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

$app->post('/addMedicalConditions/', function () use($app) {
    // Add medical conditions	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$studentId =	( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$userId = 		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
	// medical fields
	$medicalConditions = ( isset($allPostVars['medicalConditions']) ? $allPostVars['medicalConditions']: null);
	
    try 
    {
        $db = getDB();

		$studentUpdate = $db->prepare("UPDATE hog.students SET medical_conditions = true WHERE student_id = :studentId");
		$conditionInsert = $db->prepare("INSERT INTO hog.student_medical_history(student_id, illness_condition, age, comments, created_by) 
		VALUES(?,?,?,?,?);"); 
		$query = $db->prepare("SELECT currval('hog.student_medical_history_medical_id_seq') as medical_id, now() as date_medical_added");
		
		
        $results = array();
		// loop through the medical conditions and insert
		// place the resulting id in array for return
		for( $i=0; $i < count($medicalConditions); $i++ )
		{
			$db->beginTransaction();
			$studentUpdate->execute(array(':studentId' => $studentId));
			$conditionInsert->execute( array(
						$studentId,
						$medicalConditions[$i]['illness_condition'],
						$medicalConditions[$i]['age'],
						$medicalConditions[$i]['comments'],
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
		echo $e->getMessage();
		$db->rollBack();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateMedicalConditions/', function () use($app) {
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

		$sth = $db->prepare("UPDATE hog.student_medical_history
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
		echo $e->getMessage();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->delete('/deleteMedicalCondition/:medical_id', function ($medicalId) {
    // delete guardian
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM hog.student_medical_history WHERE medical_id = :medicalId");		
										
		$sth->execute( array(':medicalId' => $medicalId) );
 
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