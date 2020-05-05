<?php
$app->post('/createResource', function () use($app) {
  // Add student
  $allPostVars = json_decode($app->request()->getBody(),true);

  $teacherId = ( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
  $classId = ( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
  $resourceName = ( isset($allPostVars['resource_name']) ? $allPostVars['resource_name']: null);
  $resourceType = ( isset($allPostVars['resource_type']) ? $allPostVars['resource_type']: null);
  $additionalText = ( isset($allPostVars['additional_notes']) ? $allPostVars['additional_notes']: null);
  $fileName = ( isset($allPostVars['file_name']) ? $allPostVars['file_name']: null);
  $termId = ( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);

  try
  {
    $db = getDB();

    $insertResource = $db->prepare("INSERT INTO app.school_resources(class_id, term_id, emp_id, resource_name, resource_type, file_name, additional_text)
                                    VALUES(:classId, :termId, :teacherId, :resourceName, :resourceType, :fileName, :additionalText);");

    $db->beginTransaction();

    $insertResource->execute( array(':teacherId' => $teacherId,
                                    ':classId' => $classId,
                                    ':termId' => $termId,
                                    ':resourceName' => $resourceName,
                                    ':resourceType' => $resourceType,
                                    ':additionalText' => $additionalText,
                                    ':fileName' => $fileName
                                  ) );

    $db->commit();

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getTeacherResources/:empId', function ($empId) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT * FROM app.school_resources WHERE emp_id = :empId");
    $sth->execute( array(':empId' => $empId) );

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

$app->get('/getResource/:resourceId', function ($resourceId) {
  // Return students arrears for before a given date

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT sr.*, e.first_name, e.middle_name, e.last_name
                          FROM app.school_resources sr
                          LEFT JOIN app.employees e USING (emp_id)
                          WHERE sr.resource_id = :resourceId");
    $sth->execute( array(':resourceId' => $resourceId));
    $results = $sth->fetch(PDO::FETCH_OBJ);

    if($results && $results->balance !== null && $results->balance < 0 ) {
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

$app->put('/updateResource', function () use($app) {
    // Update department

	$allPostVars = json_decode($app->request()->getBody(),true);
	$resourceId =	( isset($allPostVars['resource_id']) ? $allPostVars['resource_id']: null);
	$title =	( isset($allPostVars['title']) ? $allPostVars['title']: null);
	$additionalText =	( isset($allPostVars['additional_text']) ? $allPostVars['additional_text']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.school_resources
			                       SET resource_name = :title,
				                         additional_text = :additionalText
                            WHERE resource_id = :resourceId");

        $sth->execute( array(':title' => $title, ':additionalText' => $additionalText, ':resourceId' => $resourceId ) );

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

$app->get('/getAllResources', function () {
    //Show all resources

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

        $sth = $db->prepare("SELECT resource_id, sr.emp_id, e.first_name, e.middle_name, e.last_name,
                              		e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as teacher_name,
                              		sr.class_id, c.class_name, sr.term_id, t.term_name, resource_name, resource_type,
                              		file_name, additional_text, sr.active, TO_CHAR(sr.creation_date :: DATE, 'dd/mm/yyyy') AS creation_date
                              FROM app.school_resources sr
                              INNER JOIN app.employees e USING (emp_id)
                              INNER JOIN app.classes c USING (class_id)
                              INNER JOIN app.terms t USING (term_id)");
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
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateVimeoUri', function () use($app) {
    // Update uri

	$allPostVars = json_decode($app->request()->getBody(),true);
	$uri =	( isset($allPostVars['uri']) ? $allPostVars['uri']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.school_resources SET vimeo_path = :uri
                            WHERE resource_id = (SELECT resource_id FROM app.school_resources ORDER BY resource_id DESC LIMIT 1)");
        $sth->execute( array(':uri' => $uri ) );

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
