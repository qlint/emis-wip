<?php
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
		/*
		$inactivateRoute = $db->prepare("UPDATE app.transport_routes
							SET active = false,
								modified_date = now(),
								modified_by = :userId 
							WHERE transport_id = :transportId
							"); 
		*/
		$deleteRoute = $db->prepare('DELETE FROM app.transport_routes WHERE transport_id = :transportId');		
							
							
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
				$deleteRoute->execute(array(':transportId' => $currentRoute->transport_id));
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

$app->get('/checkFeeItem/:fee_item_id', function ($feeItemId) {
    // Check is fee item can be deleted
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT count(student_id) as num_students
								FROM app.student_fee_items
								WHERE fee_item_id = :feeItemId");
        $sth->execute( array(':feeItemId' => $feeItemId ) );
 
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

$app->delete('/deleteFeeItem/:fee_item_id', function ($feeItemId) {
    // delete fee item
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.fee_items WHERE fee_item_id = :feeItemId");		
										
		$sth->execute( array(':feeItemId' => $feeItemId) );
 
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
