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

				// traffic analysis start
				$misdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $misdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','staff login')");
				$trafficMonitor->execute( array() );
				$misdb = null;
				// traffic analysis end

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
require('user_functions.php');

// ************** Settings  ****************** //
require('settings_functions.php');

// ************** Classes & Class Categories  ****************** //
require('classes_functions.php');


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
require('department_functions.php');

// ************** Grading  ****************** //
require('grading_functions.php');

// ************** Employees & Employee Cats  ****************** //
require('employee_functions.php');

// ************** Terms  ****************** //
require('term_functions.php');

// ************** Subjects  ****************** //
require('subject_functions.php');


// ************** Exam Marks  ****************** //
require('exam_functions.php');

// ************** Timetables  ****************** //
require('timetable_functions.php');

// ************** Report Cards  ****************** //
require('report_card_functions.php');

// ************** Payments  ****************** //
require('payment_functions.php');

// ************** Invoices  ****************** //
require('invoice_functions.php');

// ************** Fee Items  ****************** //
require('fee_item_functions.php');

// ************** Students  ****************** //
require('student_functions.php');

// ************** Manage Blog  ****************** //
require('blog_functions.php');

// ************** Parent Portal  ****************** //
require('portal_functions.php');

// ************** Staff App  ****************** //
require('staff_app_functions.php');

// ************** Notifications  ****************** //
require('notifications_functions.php');

// ************** Transport  ****************** //
require('transport_functions.php');

// ************** Reports  ****************** //
require('reports_functions.php');

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

			// traffic analysis start
				$misdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $misdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','staff created parent login')");
				$trafficMonitor->execute( array() );
				$misdb = null;
				// traffic analysis end
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

			// traffic analysis start
				$misdb = getMISDB();
				$subdom = getSubDomain();
				$trafficMonitor = $misdb->prepare("INSERT INTO traffic(school, module)
												VALUES('$subdom','staff updated parent login')");
				$trafficMonitor->execute( array() );
				$misdb = null;
				// traffic analysis end
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

function createStaffLogin($data)
{
    $username = 		( isset($data['username']) ? $data['username']: null);
	$password = 		( isset($data['password']) ? $data['password']: null);
	$loginActive = 		( isset($data['login_active']) ? ( $data['login_active'] == 't' ? 't' : 'f') : 'f');
    $empId = 		    ( isset($data['emp_id']) ? $data['emp_id']: null);
    $userId = 		    ( isset($data['login_id']) ? $data['login_id']: null);
	$active = 			( isset($data['active']) ? ($data['active'] === true ? 't' : 'f'): 'f');
	$userType = 		( isset($data['user_type']) ? $data['user_type']: null);
	$email = 			( isset($data['email']) ? $data['email']: null);
	$firstName = 		( isset($data['first_name']) ? $data['first_name']: null);
	$middleName = 		( isset($data['middle_name']) ? $data['middle_name']: null);
	$lastName = 		( isset($data['last_name']) ? $data['last_name']: null);
	$idNumber = 		( isset($data['id_number']) ? $data['id_number']: null);
	$telephone = 		( isset($data['telephone']) ? $data['telephone']: null);
	$subdmn = 		    ( isset($data['subdmn']) ? $data['subdmn']: null);

	$dbData = getClientDBData();
	$subdomain = $dbData->subdomain;
	$dbUser = $dbData->dbuser;
	$dbPass = $dbData->dbpass;

	$updateLogin = false;
	$addParentStudent = false;
	$addLogin = false;

	$db = getMISDB();

	if($userId !== null){
		$statusCheckQry = $db->query("SELECT (CASE WHEN EXISTS (SELECT staff_id FROM staff WHERE user_id = $userId AND subdomain = '$subdomain') THEN 'proceed' ELSE 'stop' END) AS status");
	    $userStatus = $statusCheckQry->fetch(PDO::FETCH_OBJ);
	    $userStatus = $userStatus->status;

	    if( $userStatus === "proceed" )
		{
			$updateLogin = true;
			// existing login, update status

			$staffUpdate = $db->prepare("UPDATE staff
											SET first_name = '$firstName',
												middle_name = '$middleName',
												last_name = '$lastName',
												telephone = '$telephone',
												email = '$email',
												user_type = '$userType',
												password = '$password',
												active = '$active',
												modified_date = now(),
												last_active = now()
											WHERE id_number = '$idNumber'");

		}
		else if( $userStatus === "stop" )
		{
			$addLogin = true;
			// new login, create

			$insertStaffLogin = $db->prepare("INSERT INTO staff(staff_id, first_name, middle_name, last_name, telephone, email, emp_id, user_id, user_type, subdomain, usernm, password, active, last_active, id_number)
										VALUES((SELECT max(staff_id)+1 FROM staff), '$firstName', '$middleName', '$lastName', '$telephone', '$email', $empId, $userId, '$userType', '$subdmn', '$username', '$password', '$active', now(), '$idNumber')");

		}
		else
		{
		    echo "We have run into a problem with this record. Please ensure all credentials are fine";
		}

		try{

			$db->beginTransaction();

			if( $addLogin )
			{
			    $insertStaffLogin->execute();
			    /*
			    $insertStaffLogin->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName, ':telephone' => $telephone, ':email' => $email,
				                                ':empId' => $empId, ':userId' => $userId, ':userType' => $userType, ':subdmn' => $subdmn, ':username' => $username,
				                                ':password' => $password, ':active' => $active) );
				*/

				// traffic analysis start
					$misdb = getMISDB();
					$subdom = getSubDomain();
					$trafficMonitor = $misdb->prepare("INSERT INTO traffic(school, module)
													VALUES('$subdom','staff created staff login')");
					$trafficMonitor->execute( array() );
					$misdb = null;
					// traffic analysis end

			}
			else if( $updateLogin )
			{
			    $staffUpdate->execute();
			    /*
				$staffUpdate->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName, ':telephone' => $telephone, ':email' => $email,
												':userType' => $userType, ':password' => $password, ':active' => $active, ':idNumber' => $idNumber) );
			    */

			    // traffic analysis start
					$misdb = getMISDB();
					$subdom = getSubDomain();
					$trafficMonitor = $misdb->prepare("INSERT INTO traffic(school, module)
													VALUES('$subdom','staff updated staff login')");
					$trafficMonitor->execute( array() );
					$misdb = null;
					// traffic analysis end

			}

			$db->commit();
			$db = null;

		} catch(PDOException $e) {
			// echo $e->getMessage();
			$app->response()->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
	        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	    }
		} // close if
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
