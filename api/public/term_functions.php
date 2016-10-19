<?php

$app->get('/getTerms(/:year)', function ($year = null) {
    //Show all terms for given year (or this year if null)
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
		if( $year == null )
		{
			$query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name, start_date, end_date,
										case when term_id = (select term_id from app.current_term) then true else false end as current_term, date_part('year',start_date) as year,
										(select count(*) from app.exam_marks where term_id = terms.term_id) as has_exams
										FROM app.terms
										--WHERE date_part('year',start_date) <= date_part('year',now())
										ORDER BY date_part('year',start_date), term_name");
			$query->execute();
		}
		else
		{
			$query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name,start_date, end_date,
										  case when term_id = (select term_id from app.current_term) then true else false end as current_term,
										  date_part('year',start_date) as year,
										  (select count(*) from app.exam_marks where term_id = terms.term_id) as has_exams
										FROM app.terms
										WHERE date_part('year',start_date) = :year
										ORDER BY date_part('year',start_date), term_name");
			$query->execute(array(':year' => $year));
		}
 
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
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getCurrentTerm', function () {
    //Get current term
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
	
		$query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.current_term");
		$query->execute();			
        $results = $query->fetch(PDO::FETCH_ASSOC);
 
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

$app->get('/getNextTerm(/:date)', function ($date = null) {
    //Get next term
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
		if( $date == null )
		{
			$query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.next_term");
			$query->execute();	
		}
		else
		{
			$query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year 
									FROM app.terms
									WHERE start_date >= :date
									ORDER BY start_date asc
									LIMIT 1");
			$query->execute( array(':date' => $date) );	
		}
				
        $results = $query->fetch(PDO::FETCH_ASSOC);
 
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

$app->post('/addTerm', function () use($app) {
    // Add term
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$termName =		( isset($allPostVars['term_name']) ? $allPostVars['term_name']: null);
	$startDate =	( isset($allPostVars['start_date']) ? $allPostVars['start_date']: null);
	$endDate =		( isset($allPostVars['end_date']) ? $allPostVars['end_date']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.terms(term_name, start_date, end_date, created_by) 
            VALUES(:termName, :startDate, :endDate, :userId)");
 
        $sth->execute( array(':termName' => $termName, ':startDate' => $startDate, ':endDate' => $endDate, ':userId' => $userId ) );
 
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

$app->put('/updateTerm', function () use($app) {
    // Update term
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$termId =		( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$termName =		( isset($allPostVars['term_name']) ? $allPostVars['term_name']: null);
	$startDate =	( isset($allPostVars['start_date']) ? $allPostVars['start_date']: null);
	$endDate =		( isset($allPostVars['end_date']) ? $allPostVars['end_date']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.terms
			SET term_name = :termName,
				start_date = :startDate,
				end_date = :endDate
            WHERE term_id = :termId");
 
        $sth->execute( array(':termName' => $termName, ':startDate' => $startDate, ':endDate' => $endDate, ':termId' => $termId ) );
 
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

$app->delete('/deleteTerm/:term_id', function ($termId) {
    // delete term
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.terms WHERE term_id = :termId");		
										
		$sth->execute( array(':termId' => $termId) );
 
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
