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

?>
