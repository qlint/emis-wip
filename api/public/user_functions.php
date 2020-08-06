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

$app->get('/usrRights/:sch/:group', function ($sch,$group) {
    //Return user rights
		error_reporting(E_ALL);  // uncomment this only when testing
	  ini_set('display_errors', 1); // uncomment this only when testing

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getLoginDB();
        $sth = $db->prepare("SELECT row_to_json(e) AS rights FROM (
															SELECT user_type, jsonb_agg(rights) AS rights FROM (
																SELECT user_type, '{\"'||school_module||'\":'||rights||'}'::text AS rights FROM (
																	SELECT user_type, school_module, jsonb_agg(rights) AS rights FROM (
																		SELECT user_type, school_module, '{\"id\":'||user_auth_id||', \"'||sub_module||'\":'||rights||'}'::text AS rights FROM (
																			SELECT user_auth_id, ut.user_type, sm.school_module,
																				CASE WHEN ua.sub_module_id IS NULL THEN '-' ELSE ssm.sub_module END AS sub_module,
																				'{\"add\":'||add||', \"edit\":'||edit||', \"view\":'||view||', \"delete\":'||delete||', \"export\":'||export||'}'::text AS rights
																			FROM user_auth ua
																			INNER JOIN user_types ut USING (user_type_id)
																			INNER JOIN school_modules sm USING (module_id)
																			LEFT JOIN school_sub_modules ssm USING (sub_module_id)
																			WHERE subdomain = :sch
																			AND user_type = :group
																		)a
																	)b
																	GROUP BY school_module, user_type
																)c
															)d
															GROUP BY user_type
														)e");
       $sth->execute( array(':sch' => $sch, ':group' => $group ) );

        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {
						$results->rights = json_decode($results->rights);

						for ($x = 0; $x <= count($results->rights->rights); $x++) {
							if(isset($results->rights->rights[$x])){
									$results->rights->rights[$x] = json_decode($results->rights->rights[$x]);

									foreach ($results->rights->rights[$x] as $name => $value) {
								      for ($y = 0; $y <= count($results->rights->rights[$x]->$name); $y++) {
												if(isset($results->rights->rights[$x]->$name[$y])){
													$results->rights->rights[$x]->$name[$y] = json_decode($results->rights->rights[$x]->$name[$y]);
												}
											}
								  }
							}
						}

            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
					echo json_encode(array('response' => 'success', 'data' => $results/*, 'test' => $results->rights->rights*/ ));
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

$app->post('/updateUsrRights', function () use($app) {
	error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing

  // update access rights
	$allPostVars = json_decode($app->request()->getBody(),true);
	$sch = ( isset($allPostVars['sch']) ? $allPostVars['sch']: null);
	$userType = ( isset($allPostVars['user_type']) ? $allPostVars['user_type']: null);
	$perms = ( isset($allPostVars['perms']) ? $allPostVars['perms']: null);

	$output_object = new stdClass;
	$output_object->a_received_payload = $allPostVars;
	$moduleResults = null; $childResults = null;

	try
	{
		$output_object->b_school = $sch;
		$output_object->c_user_type = $userType;
		$output_object->d_perms = $perms;
		$output_object->e_perms_array = array();

		foreach( $perms as $module )
		{
			$perModule = new stdClass;
			$parentModule = ( isset($module['name']) ? $module['name']: null);
			$values = ( isset($module['values']) ? $module['values']: null);
			$children = ( isset($module['children']) ? $module['children']: null);

			$perModule->a_module = $parentModule;
			$perModule->b_values = $values;
			$perModule->c_children = $children;
			$perModule->d_parent_modules = array();
			$perModule->e_child_modules = array();
				foreach( $values as $moduleValue )
				{
					$perChildModule = new stdClass;
					$modulePermName = $moduleValue['name'];
					$modulePermState = ($moduleValue['isSelected'] == "" ? 'false' : 'true');

					$perChildModule->a_parent_module = $modulePermName;
					$perChildModule->b_parent_module_state = $modulePermState;

					if($modulePermName == "create"){$modulePermName = "add";}
					if($modulePermName != "full"){
						function updateParentModuleState($col, $sch, $userType, $parentModule, $modulePermState, $moduleValue){
							$db = getLoginDB();
							// logging
							$qryTest = "UPDATE user_auth SET $col = $modulePermState
																					WHERE user_type_id = (SELECT user_type_id FROM user_types WHERE user_type = '$userType')
																					AND module_id = (SELECT module_id FROM school_modules WHERE school_module = '$parentModule')
																					AND subdomain = '$sch'";
						  $dte = date("Y-m-d");
						  $tme = date("h:i:sa");
						  $datetime = $dte . " " . $tme;
						  file_put_contents('del.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
							file_put_contents('del.txt', print_r($modulePermState . PHP_EOL, true), FILE_APPEND);
						  file_put_contents('del.txt', print_r($qryTest . PHP_EOL, true), FILE_APPEND);

							$moduleQry = $db->prepare("UPDATE user_auth SET $col = :modulePermState
																					WHERE user_type_id = (SELECT user_type_id FROM user_types WHERE user_type = :userType)
																					AND module_id = (SELECT module_id FROM school_modules WHERE school_module = :parentModule)
																					AND subdomain = :sch");
							$db->beginTransaction();
							$moduleQry->execute( array(':sch' => $sch, // eg 'dev'
																					':userType' => $userType, // eg 'ADMIN'
																					':parentModule' => $parentModule, // eg 'Exams'
																					// ':modulePermName' => $modulePermName, // eg 'edit'
																					':modulePermState' => $modulePermState) ); // eg true
						}
						updateParentModuleState($modulePermName, $sch, $userType, $parentModule, $modulePermState, $moduleValue);
					}

					/*
					$moduleQry = $db->prepare("UPDATE user_auth SET add = :add, view = :view, edit = :edit, delete = :delete, export = :export
																			WHERE user_type_id = (SELECT user_type_id FROM user_types WHERE user_type = :userType)
																			AND module_id = (SELECT module_id FROM school_modules WHERE school_module = :parentModule)
																			AND subdomain = :sch");
					$moduleQry->execute( array(':sch' => $sch, // eg 'dev'
																			':userType' => $userType, // eg 'ADMIN'
																			':parentModule' => $parentModule, // eg 'Exams'
																			':modulePermName' => $modulePermName, // eg 'edit'
																			':modulePermState' => $modulePermState) ); // eg true
					$moduleResults = $moduleQry->fetchAll(PDO::FETCH_OBJ);
					*/
					array_push($perModule->d_parent_modules,$perChildModule);
					$perChildModule = null;
				}

				foreach( $children as $child )
				{
					$perChildModule2 = new stdClass;
					$childName = ( isset($child['name']) ? $child['name']: null);
					$childValues = ( isset($child['values']) ? $child['values']: null);

					$perChildModule2->a_child_name = $childName;
					$perChildModule2->b_child_values = $childValues;
					$perChildModule2->c_each_child_value = array();
					foreach( $childValues as $childVal )
					{
						$childValObj = new stdClass;
						$childPermName = ( isset($childVal['name']) ? $childVal['name']: null);
						$childPermState = ($childVal['isSelected'] == "" ? 'false' : 'true');

						$childValObj->a_val_name = $childPermName;
						$childValObj->b_vale_state = $childPermState;

						if($childPermName == "create"){$childPermName = "add";}
						if($childPermName != "full"){
							function updateChildModuleState($col, $sch, $userType, $parentModule, $childName, $childPermState){
								$db = getLoginDB();
								// logging
								$qryTest = "UPDATE user_auth SET $col = $childPermState
																						WHERE user_type_id = (SELECT user_type_id FROM user_types WHERE user_type = '$userType')
																						AND module_id = (SELECT module_id FROM school_modules WHERE school_module = '$parentModule')
																						AND sub_module_id = (SELECT sub_module_id FROM school_sub_modules WHERE sub_module = '$childName')
																						AND subdomain = '$sch'";
							  $dte = date("Y-m-d");
							  $tme = date("h:i:sa");
							  $datetime = $dte . " " . $tme;
							  file_put_contents('del.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
							  file_put_contents('del.txt', print_r($qryTest . PHP_EOL, true), FILE_APPEND);

								$moduleQry = $db->prepare("UPDATE user_auth SET $col = :childPermState
																						WHERE user_type_id = (SELECT user_type_id FROM user_types WHERE user_type = :userType)
																						AND module_id = (SELECT module_id FROM school_modules WHERE school_module = :parentModule)
																						AND sub_module_id = (SELECT sub_module_id FROM school_sub_modules WHERE sub_module = :childName)
																						AND subdomain = :sch");
								$db->beginTransaction();
								$moduleQry->execute( array(':sch' => $sch, // eg 'dev'
																						':userType' => $userType, // eg 'ADMIN'
																						':parentModule' => $parentModule, // eg 'Exams'
																						':childName' => $childName, // eg 'Report Cards'
																						// ':modulePermName' => $modulePermName, // eg 'edit'
																						':childPermState' => $childPermState) ); // eg true
							}
							updateChildModuleState($childPermName, $sch, $userType, $parentModule, $childName, $childPermState);
						}

						/*
						$childQry = $db->prepare("INSERT INTO app.invoices(student_id, inv_date, total_amount, due_date, created_by, term_id, custom_invoice_no)
																				VALUES(:studentId, :invDate, :totalAmt, :dueDate, :userId, :termId, :custom_invoice_no)");
						$childQry->execute( array(':sch' => $sch, // eg 'dev'
																				':userType' => $userType, // eg 'TEACHER'
																				':parentModule' => $parentModule, // eg 'Communications'
																				':childName' => $childName, // eg 'Homework'
																				':childPermName' => $childPermName, // eg 'create'
																				':childPermState' => $childPermState) ); // eg true
						$childResults = $childQry->fetchAll(PDO::FETCH_OBJ);
						*/
						array_push($perChildModule2->c_each_child_value,$childValObj);
						$childValObj = null;
					}
					array_push($perModule->e_child_modules,$perChildModule2);
					$perChildModule2 = null;
				}
				array_push($output_object->e_perms_array,$perModule);
				$perModule = null;
		}
		// end
		$db->commit();
		$output = json_encode($output_object);

		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "output" => $output));
		$db = null;
	} catch(PDOException $e) {
		$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	}

});

$app->post('/updateAccessRights', function () use($app) {
	error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing

  // update access rights
	try
	{
		$allPostVars = json_decode($app->request()->getBody(),true);
		$output = new stdClass;
		$output->data = array();

		foreach( $allPostVars as $rights )
		{
			$perRight = new stdClass;
			$id = $rights['id'];
			$col = ($rights['col'] == "create" ? "add" : $rights['col']);
			$val = $rights['val'];

			$perRight->id = $id;
			$perRight->col = $col;
			$perRight->val = $val;
			array_push($output->data,$perRight);

			if($col != "full"){

				$qryTest = "UPDATE user_auth SET $col = $val WHERE user_auth_id = $id";
				$dte = date("Y-m-d");
				$tme = date("h:i:sa");
				$datetime = $dte . " " . $tme;
				file_put_contents('del.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
				file_put_contents('del.txt', print_r($qryTest . PHP_EOL, true), FILE_APPEND);

				$db = pg_connect("host=localhost port=5433 dbname=eduweb_mis user=postgres password=pg_edu@8947");
        $updtQry = pg_query($db,"UPDATE user_auth SET $col = $val WHERE user_auth_id = $id");
				$db = null;
			}
		}

		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "message" => "The access rights have been updated succcessfully.", "output" => json_encode($output), "received" => json_encode($allPostVars)));

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

$app->get('/getUserGroups', function () {
    //Show all user groups

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getLoginDB();
        $sth = $db->prepare("SELECT * FROM user_types ORDER BY user_type_id ASC");
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
