<?php
$app->get('/getUsers(/:status)', function ($status = true) {
    //Show users

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT u.first_name || ' ' || coalesce(u.middle_name,'') || ' ' || u.last_name AS user_name,
									u.first_name, u.middle_name, u.last_name, u.email, username, user_type, user_id, u.active,
									e.id_number, e.gender, e.telephone AS phone1, e.telephone2 AS phone2, e.dob, e.emp_cat_id, e.dept_id, emp_id
							FROM app.users u
							INNER JOIN app.employees e ON u.user_id = e.login_id
							WHERE u.active = :status
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

	$empCatId =		( isset($allPostVars['emp_cat_id']) ? $allPostVars['emp_cat_id']: null);
	$deptId =		( isset($allPostVars['dept_id']) ? $allPostVars['dept_id']: null);
	$firstName =		( isset($allPostVars['first_name']) ? $allPostVars['first_name']: null);
	$middleName =		( isset($allPostVars['middle_name']) ? $allPostVars['middle_name']: null);
	$lastName =			( isset($allPostVars['last_name']) ? $allPostVars['last_name']: null);
	$initials =			( isset($allPostVars['initials']) ? $allPostVars['initials']: null);
	$username =			( isset($allPostVars['username']) ? $allPostVars['username']: null);
	$password =			( isset($allPostVars['password']) ? $allPostVars['password']: null);
	$email =			( isset($allPostVars['email']) ? $allPostVars['email']: null);
	$userType =			( isset($allPostVars['user_type']) ? $allPostVars['user_type']: null);
	$phone1 =			( isset($allPostVars['phone']) ? $allPostVars['phone']: null);
	$phone2 =			( isset($allPostVars['phone2']) ? $allPostVars['phone2']: null);
	$idNumber =			( isset($allPostVars['id_number']) ? $allPostVars['id_number']: null);
	$gender =			( isset($allPostVars['gender']) ? $allPostVars['gender']: null);
	$dob =			( isset($allPostVars['dob']) ? $allPostVars['dob']: null);
	$currentUserId =	( isset($allPostVars['currnet_user_id']) ? $allPostVars['currnet_user_id']: null);

    try
    {
        $db = getDB();
				$insertEmp = $db->prepare("INSERT INTO app.employees(emp_cat_id, dept_id, id_number, gender, first_name, middle_name, last_name, initials, dob, telephone, email, created_by, telephone2)
																	VALUES(:empCatId,:deptId,:idNumber,:gender,:firstName,:middleName,:lastName,:initials,:dob,:phone1,:email,:createdBy,:phone2)");

        $sth = $db->prepare("INSERT INTO app.users(first_name, middle_name, last_name, username, password, email, user_type, created_by)
														VALUES(:firstName, :middleName, :lastName, :username, :password, :email, :userType, :currentUserId)");
			  $updateEmp = $db->prepare("UPDATE app.employees SET login_id = currval('app.user_user_id_seq') WHERE emp_id = currval('app.employees_emp_id_seq');");
				$getEmp = $db->prepare("SELECT u.*, e.* FROM app.users u
																INNER JOIN app.employees e ON u.user_id = e.login_id
																WHERE user_id = currval('app.user_user_id_seq')");

				$db->beginTransaction();
				$insertEmp->execute( array(':empCatId' => $empCatId, ':deptId' => $deptId, ':idNumber' => $idNumber, ':gender' => $gender,
																	':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName, ':initials' => $initials, ':dob' => $dob,
																	':phone1' => $phone1, ':phone2' => $phone2, ':email' => $email, ':createdBy' => $currentUserId ) );
        $sth->execute( array(':firstName' => $firstName, ':middleName' => $middleName, ':lastName' => $lastName,
							 ':username' => $username, ':password' => $password, ':email' => $email, ':userType' => $userType,
							 ':currentUserId' => $currentUserId	) );
				$updateEmp->execute();
				$getEmp->execute( array() );

         $theEmp = $getEmp->fetch(PDO::FETCH_OBJ);
				 $db->commit();

				$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1, "the_emp" => $theEmp));
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
									SELECT user_type, '{\"mod_name\":\"'||school_module||'\", \"icon\":\"'||icon||'\", \"sort\":'||sm_sort||', \"'||school_module||'\":'||rights||'}'::text AS rights FROM (
										SELECT user_type, school_module, sm_sort, icon, jsonb_agg(rights) AS rights FROM (
											SELECT user_type, school_module, icon, sm_sort, '{\"id\":'||user_auth_id||', \"sub_mod_name\":\"'||sub_module||'\", \"'||sub_module||'\":'||rights||'}'::text AS rights FROM (
												SELECT user_auth_id, ut.user_type, sm.school_module, sm.sort_order AS sm_sort, sm.icon, ssm.sort_order AS ssm_sort,
													CASE WHEN ua.sub_module_id IS NULL THEN '-' ELSE ssm.sub_module END AS sub_module,
													'{\"add\":'||add||', \"edit\":'||edit||', \"view\":'||view||', \"delete\":'||delete||', \"export\":'||export||'}'::text AS rights,
													sm_icon
												FROM user_auth ua
												INNER JOIN user_types ut USING (user_type_id)
												INNER JOIN school_modules sm USING (module_id)
												LEFT JOIN school_sub_modules ssm USING (sub_module_id)
												WHERE subdomain = :sch
												AND user_type = :group
												AND view = true --user only interacts with modules they can view
												ORDER BY sm.sort_order ASC, ssm.sort_order ASC
											)a
											ORDER BY sm_sort ASC
										)b
										GROUP BY school_module, sm_sort, icon, user_type
										ORDER BY sm_sort ASC
									)c
								)d
								GROUP BY user_type
							)e");
       $sth->execute( array(':sch' => $sch, ':group' => $group ) );

        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {
						$results->rights = json_decode($results->rights);
						// var_dump($results->rights->rights);
						for ($i=0; $i < count($results->rights->rights); $i++) {
							if(isset($results->rights->rights[$i])){
								$results->rights->rights[$i] = json_decode($results->rights->rights[$i]);
								$modName = $results->rights->rights[$i]->mod_name;
								// var_dump($results->rights->rights[$i]->{$modName});
								for ($k=0; $k < count($results->rights->rights[$i]->{$modName}); $k++) {
									$results->rights->rights[$i]->{$modName}[$k] = json_decode($results->rights->rights[$i]->{$modName}[$k]);
									// var_dump($results->rights->rights[$i]->{$modName}[$k]);
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

$app->get('/allUsrRights/:sch/:group', function ($sch,$group) {
    //Return user rights
		error_reporting(E_ALL);  // uncomment this only when testing
	  ini_set('display_errors', 1); // uncomment this only when testing

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getLoginDB();
        $sth = $db->prepare("SELECT row_to_json(e) AS rights FROM (
								SELECT user_type, jsonb_agg(rights) AS rights FROM (
									SELECT user_type, '{\"mod_name\":\"'||school_module||'\", \"icon\":\"'||icon||'\", \"sort\":'||sm_sort||', \"'||school_module||'\":'||rights||'}'::text AS rights FROM (
										SELECT user_type, school_module, sm_sort, icon, jsonb_agg(rights) AS rights FROM (
											SELECT user_type, school_module, icon, sm_sort, '{\"id\":'||user_auth_id||', \"sub_mod_name\":\"'||sub_module||'\", \"'||sub_module||'\":'||rights||'}'::text AS rights FROM (
												SELECT user_auth_id, ut.user_type, sm.school_module, sm.sort_order AS sm_sort, sm.icon, ssm.sort_order AS ssm_sort,
													CASE WHEN ua.sub_module_id IS NULL THEN '-' ELSE ssm.sub_module END AS sub_module,
													'{\"add\":'||add||', \"edit\":'||edit||', \"view\":'||view||', \"delete\":'||delete||', \"export\":'||export||'}'::text AS rights,
													sm_icon
												FROM user_auth ua
												INNER JOIN user_types ut USING (user_type_id)
												INNER JOIN school_modules sm USING (module_id)
												LEFT JOIN school_sub_modules ssm USING (sub_module_id)
												WHERE subdomain = :sch
												AND user_type = :group
												ORDER BY sm.sort_order ASC, ssm.sort_order ASC
											)a
											ORDER BY sm_sort ASC
										)b
										GROUP BY school_module, sm_sort, icon, user_type
										ORDER BY sm_sort ASC
									)c
								)d
								GROUP BY user_type
							)e");
       $sth->execute( array(':sch' => $sch, ':group' => $group ) );

        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {
						$results->rights = json_decode($results->rights);
						// var_dump($results->rights->rights);
						for ($i=0; $i < count($results->rights->rights); $i++) {
							if(isset($results->rights->rights[$i])){
								$results->rights->rights[$i] = json_decode($results->rights->rights[$i]);
								$modName = $results->rights->rights[$i]->mod_name;
								// var_dump($results->rights->rights[$i]->{$modName});
								for ($k=0; $k < count($results->rights->rights[$i]->{$modName}); $k++) {
									$results->rights->rights[$i]->{$modName}[$k] = json_decode($results->rights->rights[$i]->{$modName}[$k]);
									// var_dump($results->rights->rights[$i]->{$modName}[$k]);
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

$app->get('/fgtPwd/:phone', function ($phoneNumber){

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
    $db0 = getDB();

    $previousIncomplete = $db0->query("SELECT (CASE WHEN EXISTS (SELECT usr_phone AS phone FROM app.forgot_pwd WHERE usr_phone = '$phoneNumber') THEN 'incomplete-reset' ELSE 'continue' END) AS state");
    $checkOne = $previousIncomplete->fetch(PDO::FETCH_OBJ);
    $incompleteStatus = $checkOne->state;
    if($incompleteStatus === "continue"){

      $checkOne = $db0->query("SELECT (CASE
                                      		WHEN EXISTS (SELECT * FROM (
                                                        SELECT username AS phone FROM app.users WHERE username = '$phoneNumber'
                                                        )a
                                                        LIMIT 1) THEN 'found'
                                      		ELSE 'not-found'
                                        END) AS check_one, (SELECT user_id FROM app.users WHERE username = '$phoneNumber') AS user_id,
                                        (SELECT first_name FROM app.users WHERE username = '$phoneNumber') AS staff_name");
      $lineCheck = $checkOne->fetch(PDO::FETCH_OBJ);
      $phoneCheck = $lineCheck->check_one;
      $userId = $lineCheck->user_id;
      $staffName = $lineCheck->staff_name;

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

$app->get('/staffFgtPwd/:phone', function ($phoneNumber){

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
    $db0 = getDB();

    $previousIncomplete = $db0->query("SELECT (CASE WHEN EXISTS (SELECT usr_phone AS phone FROM app.forgot_pwd WHERE usr_phone = '$phoneNumber') THEN 'incomplete-reset' ELSE 'continue' END) AS state");
    $checkOne = $previousIncomplete->fetch(PDO::FETCH_OBJ);
    $incompleteStatus = $checkOne->state;
    if($incompleteStatus === "continue"){

      $checkOne = $db0->query("SELECT (CASE
                                      		WHEN EXISTS (SELECT * FROM (
                                                        SELECT username AS phone FROM app.users WHERE username = '$phoneNumber'
                                                        )a
                                                        LIMIT 1) THEN 'found'
                                      		ELSE 'not-found'
                                        END) AS check_one, (SELECT user_id FROM app.users WHERE username = '$phoneNumber') AS user_id,
                                        (SELECT first_name FROM app.users WHERE username = '$phoneNumber') AS staff_name");
      $lineCheck = $checkOne->fetch(PDO::FETCH_OBJ);
      $phoneCheck = $lineCheck->check_one;
      $userId = $lineCheck->user_id;
      $staffName = $lineCheck->staff_name;

      if($phoneCheck === "found"){
          $sth2 = $db0->prepare("INSERT INTO app.forgot_pwd(usr_phone, temp_pwd, user_id)
                                VALUES ('$phoneNumber','$temporaryPwd',$userId);");
          $sth2->execute( array() );

          // first we need to change the phone format to +[code]phone
          $firstChar = substr($phoneNumber, 0, 1);
          if($firstChar === "0"){$phoneNumber = preg_replace('/^0/', '', $phoneNumber);}
          // we now create & send the actual sms
          $forgotPwdObj = new stdClass();
          $forgotPwdObj->message_recipients = Array();
          $forgotPwdObj->message_by = "Eduweb Mobile App Forgot Password";
          $forgotPwdObj->message_date = date('Y-m-d H:i:s');
          $forgotPwdObj->message_text = "Hello $staffName, use $temporaryPwd as your temporary password for the Eduweb Mobile App.";
          $forgotPwdObj->subscriber_name = "api";// to be replaced;


          $msgRecipientsObj = new stdClass();
          $msgRecipientsObj->recipient_name = "$staffName";
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
      $user = $db0->query("SELECT u.first_name, u.last_name, fp.usr_phone, fp.temp_pwd
                                          FROM app.forgot_pwd fp
                                          INNER JOIN app.users u USING (user_id)
                                          WHERE usr_phonr = '$phoneNumber'
                                          LIMIT 1");
      $userDetails = $user->fetch(PDO::FETCH_OBJ);
      $fName = $userDetails->first_name;
      $lName = $userDetails->last_name;
      $code = $userDetails->temp_pwd;
      $phone = $userDetails->usr_phone;

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

$app->get('/confirmStaffTemporaryPassword/:phone/:tempPwd', function ($phoneNumber,$tempPwd){

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $checkPassword = $db->query("SELECT (CASE
                                    		WHEN EXISTS (SELECT * FROM (
                                                      SELECT usr_phone AS phone FROM app.forgot_pwd WHERE usr_phone = '$phoneNumber' AND temp_pwd = '$tempPwd'
                                                      )a
                                                      LIMIT 1) THEN 'found'
                                    		ELSE 'not-found'
                                      END) AS pwd_status,
                                      (SELECT temp_pwd FROM app.forgot_pwd WHERE usr_phone = '$phoneNumber' AND temp_pwd = '$tempPwd') AS pwd,
                                      (SELECT user_id FROM app.forgot_pwd WHERE usr_phone = '$phoneNumber' AND temp_pwd = '$tempPwd') AS user_id");
    $userCheck = $checkPassword->fetch(PDO::FETCH_OBJ);
    $pwdCheck = $userCheck->pwd_status;
    $pwd = $userCheck->pwd;
    $userId = $userCheck->user_id;

    if($pwdCheck === "found"){
        $sth2 = $db->prepare("UPDATE app.users SET password = :pwd WHERE user_id = :userId;");
        $sth2->execute( array(':userId'=>$userId, ':pwd'=>$pwd) );

        // we now remove this record
        $sth3 = $db->prepare("DELETE FROM app.forgot_pwd WHERE user_id = :userId AND temp_pwd = :pwd;");
        $sth3->execute( array(':userId'=>$userId, ':pwd'=>$pwd) );

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

?>
