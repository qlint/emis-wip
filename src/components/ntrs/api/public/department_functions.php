<?php

$app->get('/getDepartments(/:status)', function ($status=true) {
    //Show all departments
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT dept_id, dept_name, active, category
            FROM app.departments
            WHERE active = :status
			ORDER BY dept_id");
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
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/checkDepartment/:dept_id', function ($deptId) {
    // Check is department can be deleted
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT count(emp_id) as num_employees
								FROM app.employees
								WHERE dept_id = :deptId");
        $sth->execute( array(':deptId' => $deptId ) );
 
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

$app->post('/addDepartment', function () use($app) {
    // Add department
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$deptName =	( isset($allPostVars['dept_name']) ? $allPostVars['dept_name']: null);
	$category =	( isset($allPostVars['category']) ? $allPostVars['category']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.departments(dept_name, category, created_by) 
            VALUES(:deptName, :category, :userId)");
 
        $sth->execute( array(':deptName' => $deptName, ':category' => $category, ':userId' => $userId ) );
 
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

$app->put('/updateDepartment', function () use($app) {
    // Update department
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$deptId =	( isset($allPostVars['dept_id']) ? $allPostVars['dept_id']: null);
	$deptName =	( isset($allPostVars['dept_name']) ? $allPostVars['dept_name']: null);
	$category =	( isset($allPostVars['category']) ? $allPostVars['category']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.departments
			SET dept_name = :deptName,
				category = :category,
				active = true
            WHERE dept_id = :deptId");
 
        $sth->execute( array(':deptName' => $deptName, ':deptId' => $deptId, ':category' => $category ) );
 
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

$app->put('/setDeptStatus', function () use($app) {
    // Update department status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$deptId = ( isset($allPostVars['dept_id']) ? $allPostVars['dept_id']: null);
	$status = ( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.departments
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE dept_id = :deptId
							"); 
        $sth->execute( array(':deptId' => $deptId, 
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

$app->get('/getDeptSummary', function () {
    //Show all class categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT dept_id, dept_name, active, category,
									(select count(*) 
								from app.employees 
								where dept_id = departments.dept_id) as num_staff
								FROM app.departments
								WHERE active is true
								ORDER BY dept_id");
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

$app->delete('/deleteDept/:dept_id', function ($deptId) {
    // delete department
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.departments WHERE dept_id = :deptId");		
										
		$sth->execute( array(':deptId' => $deptId) );
 
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
