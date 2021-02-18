<?php
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

$app->get('/checkMultiLinks/:subdomain', function ($subdomain) {

	// Get multi links if they exist

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getLoginDB();

		$sth = $db->prepare("SELECT CASE WHEN multi_link IS NULL THEN 'no-link' ELSE 'multi-link' END AS link_status,
                            	multi_link, (SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS school_name FROM clients WHERE client_id = qry.other_client_id) AS school_name
                            FROM (
                            	SELECT other_client_id, multi_link FROM clients
                            	WHERE active IS TRUE AND subdomain = :subdomain
                            )qry");
		$sth->execute(array(':subdomain' => $subdomain));
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

$app->get('/getAllCountries', function () {
    //Show settings

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT * FROM app.countries ORDER BY countries_name ASC");
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

$app->get('/getAllBnkDetails', function () {
    //Show settings

	$app = \Slim\Slim::getInstance();

    try
    {
    $db = getDB();
    $sth = $db->prepare("SELECT * FROM app.school_bnks");
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

$app->post('/addBnk', function () use($app) {
		// Add term

	$allPostVars = json_decode($app->request()->getBody(),true);

	$bank =		( isset($allPostVars['bank']) ? $allPostVars['bank']: null);
	$branch =	( isset($allPostVars['branch']) ? $allPostVars['branch']: null);
	$accName =		( isset($allPostVars['acc_name']) ? $allPostVars['acc_name']: null);
	$accNum =		( isset($allPostVars['acc_number']) ? $allPostVars['acc_number']: null);

		try
		{
				$db = getDB();
				$sth = $db->prepare("INSERT INTO app.school_bnks(name, branch, acc_name, acc_number)
														VALUES (:bank, :branch, :accName, :accNum);");

				$sth->execute( array(':bank' => $bank, ':branch' => $branch, ':accName' => $accName, ':accNum' => $accNum ) );

		$app->response->setStatus(200);
				$app->response()->headers->set('Content-Type', 'application/json');
				echo json_encode(array("response" => "success", "code" => 1));
				$db = null;


		} catch(PDOException $e) {
				$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
				echo	json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		}

});

$app->post('/addPaymentTerms', function () use($app) {
		// Add payment term

	$allPostVars = json_decode($app->request()->getBody(),true);

	$terms =		( isset($allPostVars['terms']) ? $allPostVars['terms']: null);

		try
		{
				$db = getDB();
				$sth = $db->prepare("SELECT value FROM app.settings WHERE name = 'Payment Terms'");
				$sth->execute(array());
				$results = $sth->fetch(PDO::FETCH_OBJ);

				if($results) {
					// update
					$sth2 = $db->prepare("UPDATE app.settings SET value = :terms
																WHERE name = 'Payment Terms';");
					$sth2->execute( array(':terms' => $terms) );
				}else{
					// insert
					$sth2 = $db->prepare("INSERT INTO app.settings(name, value)
															VALUES ('Payment Terms', :terms);");
					$sth2->execute( array(':terms' => $terms) );
				}

				$app->response->setStatus(200);
				$app->response()->headers->set('Content-Type', 'application/json');
				echo json_encode(array("response" => "success", "code" => 1));
				$db = null;


		} catch(PDOException $e) {
				$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
				echo	json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		}

});

$app->post('/updateSchoolMenu', function () use($app) {
		// Add or edit school menu

	$allPostVars = json_decode($app->request()->getBody(),true);

	$menu =		( isset($allPostVars['menu']) ? $allPostVars['menu']: null);

		try
		{
				$db = getDB();
				foreach($menu as $mn)
				{
					$sth = $db->prepare("SELECT menu_id FROM app.school_menu WHERE day_name = :day AND break_name = :break");
					$sth->execute(array(':day' => $mn["day"], ':break' => $mn["time"]));
					$results = $sth->fetch(PDO::FETCH_OBJ);

					if($results) {
						// update
						$sth2 = $db->prepare("UPDATE app.school_menu SET meal = :meal, modified_date = now() WHERE menu_id = :menuId;");
						$sth2->execute( array(':menuId' => $results->menu_id) );
					}else{
						// insert
						$sth2 = $db->prepare("INSERT INTO app.school_menu(day_name, break_name, meal)
																	VALUES (:day, :break, :meal);");
						$sth2->execute( array(':day' => $mn["day"], ':break' => $mn["time"], ':meal' => $mn["meal"]) );
					}
				}

				$app->response->setStatus(200);
				$app->response()->headers->set('Content-Type', 'application/json');
				echo json_encode(array("response" => "success", "code" => 1));
				$db = null;

		} catch(PDOException $e) {
				$app->response()->setStatus(404);
				$app->response()->headers->set('Content-Type', 'application/json');
				echo	json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		}

});

$app->get('/getSchMenu', function () {
    //Show menu

	$app = \Slim\Slim::getInstance();

    try
    {
    $db = getDB();
    $sth = $db->prepare("SELECT * FROM app.school_menu");
		$sth->execute();
		$menu = $sth->fetchAll(PDO::FETCH_OBJ);

        if($menu) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $menu ));
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

?>
