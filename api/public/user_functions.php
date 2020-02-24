<?php
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

$app->put('/postUserRequest', function () use($app) {
    // Update password

	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$password =			( isset($allPostVars['new_user_pwd']) ? $allPostVars['new_user_pwd']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.users SET password = :password WHERE user_id = :userId");

				$db->beginTransaction();
        $sth->execute( array(':password' => $password, ':userId' => $userId	) );
				$db->commit();
		    $db = null;

				$app->response->setStatus(200);
		    $app->response()->headers->set('Content-Type', 'application/json');
		    echo json_encode(array("response" => "success", "code" => 1));

    } catch(PDOException $e) {
	    $app->response()->setStatus(404);
	    $app->response()->headers->set('Content-Type', 'application/json');
	    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	  }

});

$app->get('/getSysAdmns', function () {
    //Show all sys admns

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'SYS_ADMIN'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

$app->get('/getAdmns', function () {
    //Show all admns

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'ADMIN'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

$app->get('/getTchrs', function () {
    //Show all teachers

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'TEACHER'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

$app->get('/getPrincipals', function () {
    //Show principals

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'PRINCIPAL'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

$app->get('/getAdmnFinance', function () {
    //Show all admin-finance

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'ADMIN-FINANCE'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

$app->get('/getAdmnTransp', function () {
    //Show all admin-transport

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'ADMIN-TRANSPORT'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

$app->get('/getFnance', function () {
    //Show all finance

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'FINANCE'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

$app->get('/getFnanceCtrld', function () {
    //Show all finance-controlled

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT user_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS user_name, user_type
							 FROM app.users
							 WHERE active IS true AND user_type = 'FINANCE-CONTROLLED'
							 ORDER BY user_name ASC");
        $sth->execute( array());
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

?>
