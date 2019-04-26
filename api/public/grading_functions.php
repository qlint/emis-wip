<?php

$app->get('/getGrading', function () {
    //Show grading

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT *
            FROM app.grading
			ORDER BY max_mark desc");
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

$app->get('/getGrading2', function () {
    //Show lower school grading

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT *
            FROM app.grading2
			ORDER BY max_mark desc");
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

$app->post('/addGrading', function () use($app) {
    // Add grading

	$allPostVars = json_decode($app->request()->getBody(),true);

	$grade =	( isset($allPostVars['grade']) ? $allPostVars['grade']: null);
	$markMin =	( isset($allPostVars['min_mark']) ? $allPostVars['min_mark']: null);
	$markMax =	( isset($allPostVars['max_mark']) ? $allPostVars['max_mark']: null);
	$comment =	( isset($allPostVars['comment']) ? $allPostVars['comment']: null);
	$kiswahili_comment =	( isset($allPostVars['kiswahili_comment']) ? $allPostVars['kiswahili_comment']: null);
	$principal_comment =	( isset($allPostVars['principal_comment']) ? $allPostVars['principal_comment']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.grading(grade, min_mark, max_mark, comment, kiswahili_comment, principal_comment)
            VALUES(:grade, :markMin, :markMax, :comment, :kiswahili_comment, :principal_comment)");

        $sth->execute( array(':grade' => $grade, ':markMin' => $markMin, ':markMax' => $markMax, ':comment' => $comment, ':kiswahili_comment' => $kiswahili_comment, ':principal_comment' => $principal_comment ) );

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

$app->post('/addGrading2', function () use($app) {
    // Add lower school grading

	$allPostVars = json_decode($app->request()->getBody(),true);

	$grade2 =	( isset($allPostVars['grade2']) ? $allPostVars['grade2']: null);
	$markMin =	( isset($allPostVars['min_mark']) ? $allPostVars['min_mark']: null);
	$markMax =	( isset($allPostVars['max_mark']) ? $allPostVars['max_mark']: null);
	$comment =	( isset($allPostVars['comment']) ? $allPostVars['comment']: null);
	$kiswahili_comment =	( isset($allPostVars['kiswahili_comment']) ? $allPostVars['kiswahili_comment']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.grading2(grade2, min_mark, max_mark, comment, kiswahili_comment)
            VALUES(:grade2, :markMin, :markMax, :comment, :kiswahili_comment)");

        $sth->execute( array(':grade2' => $grade2, ':markMin' => $markMin, ':markMax' => $markMax, ':comment' => $comment, ':kiswahili_comment' => $kiswahili_comment ) );

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

$app->put('/updateGrading', function () use($app) {
    // Update grading

	$allPostVars = json_decode($app->request()->getBody(),true);
	$gradeId =	( isset($allPostVars['grade_id']) ? $allPostVars['grade_id']: null);
	$grade =	( isset($allPostVars['grade']) ? $allPostVars['grade']: null);
	$markMin =	( isset($allPostVars['min_mark']) ? $allPostVars['min_mark']: null);
	$markMax =	( isset($allPostVars['max_mark']) ? $allPostVars['max_mark']: null);
	$comment =	( isset($allPostVars['comment']) ? $allPostVars['comment']: null);
	$kiswahili_comment =	( isset($allPostVars['kiswahili_comment']) ? $allPostVars['kiswahili_comment']: null);
	$principal_comment =	( isset($allPostVars['principal_comment']) ? $allPostVars['principal_comment']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.grading
			SET grade = :grade,
				min_mark = :markMin,
				max_mark = :markMax,
				comment = :comment,
				kiswahili_comment = :kiswahili_comment,
				principal_comment = :principal_comment
            WHERE grade_id = :gradeId");

        $sth->execute( array(':grade' => $grade, ':markMin' => $markMin, ':markMax' => $markMax, ':comment' => $comment, ':kiswahili_comment' => $kiswahili_comment, ':principal_comment' => $principal_comment, ':gradeId' => $gradeId ) );

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

$app->put('/updateGrading2', function () use($app) {
    // Update lower school grading

	$allPostVars = json_decode($app->request()->getBody(),true);
	$gradeId2 =	( isset($allPostVars['grade2_id']) ? $allPostVars['grade2_id']: null);
	$grade2 =	( isset($allPostVars['grade2']) ? $allPostVars['grade2']: null);
	$markMin =	( isset($allPostVars['min_mark']) ? $allPostVars['min_mark']: null);
	$markMax =	( isset($allPostVars['max_mark']) ? $allPostVars['max_mark']: null);
	$comment =	( isset($allPostVars['comment']) ? $allPostVars['comment']: null);
	$kiswahili_comment =	( isset($allPostVars['kiswahili_comment']) ? $allPostVars['kiswahili_comment']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.grading2
			SET grade2 = :grade2,
				min_mark = :markMin,
				max_mark = :markMax,
				comment = :comment,
				kiswahili_comment = :kiswahili_comment
            WHERE grade2_id = :gradeId2");

        $sth->execute( array(':grade2' => $grade2, ':markMin' => $markMin, ':markMax' => $markMax, ':comment' => $comment, ':kiswahili_comment' => $kiswahili_comment, ':gradeId2' => $gradeId2 ) );

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
