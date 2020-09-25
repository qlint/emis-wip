<?php
$app->get('/getDpClient/:subdomain(/:status)', function ($subdomain, $status = true) {
    // Get client using digital payments

  $app = \Slim\Slim::getInstance();

    try
    {
      $dbhost="localhost";
      $dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5434");
      $dbuser = "postgres";
      $dbpass = "pg_edu@8947";
      $dbname = "digital_payments";
      $dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
      $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $db = $dbConnection; // the db connect
      $sth = $db->prepare("SELECT * FROM clients WHERE domain = :subdomain AND active = :status;");
      $sth->execute( array(':subdomain' => $subdomain, ':status' => $status) );
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

$app->get('/getMpesaPayments', function () {
	//Get current term

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();

		$query = $db->prepare("SELECT * FROM app.mpesa_received_payments ORDER BY rcvd_id DESC");
		$query->execute();
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
		echo	json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	}

});

?>
