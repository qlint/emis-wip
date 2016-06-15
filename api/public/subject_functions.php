<?php
$app->get('/getAllSubjects/:classCatId(/:status)', function ($classCatId, $status = true) {
    //Show all subjects, including parent subjects
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
		$sth = $db->prepare("SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
									teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active,
									parent_subject_id, sort_order,
									(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
									case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is not null then true else false end as has_children
								FROM app.subjects
								LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
								INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
								WHERE subjects.class_cat_id = :classCatId
								AND subjects.active = :status
								ORDER BY class_cat_name, sort_order, subject_name;
							");
							
		$sth->execute(array(':classCatId' => $classCatId, ':status' => $status));			
 
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);
 
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

$app->get('/getSubjects/:classCatId', function ($classCatId) {
    //Show only subjects that receive exam marks (children subjects)
	
	$app = \Slim\Slim::getInstance();

    try 
    {
		$db = getDB();
		$sth = $db->prepare("SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
									teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active,
									parent_subject_id, sort_order,
									(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name			
								FROM app.subjects
								LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
								INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true 
								WHERE subjects.class_cat_id = :classCatId
								AND subjects.active is true
								AND (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is null 
								ORDER BY class_cat_name, sort_order, subject_name;
							");
							
		$sth->execute(array(':classCatId' => $classCatId));			
 
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);
 
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

$app->post('/addSubject', function () use($app) {
    // Add subject
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$subjectName =		( isset($allPostVars['subject_name']) ? $allPostVars['subject_name']: null);
	$classCatId =		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$parentSubjectId =	( isset($allPostVars['parent_subject_id']) ? $allPostVars['parent_subject_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.subjects(subject_name, class_cat_id, teacher_id, created_by, parent_subject_id) 
            VALUES(:subjectName, :classCatId, :teacherId, :userId, :parentSubjectId)");
 
        $sth->execute( array(':subjectName' => $subjectName, ':classCatId' => $classCatId, 
							 ':teacherId' => $teacherId, ':userId' => $userId, ':parentSubjectId' => $parentSubjectId,
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

$app->put('/updateSubject', function () use($app) {
    // Update subject
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$subjectId =		( isset($allPostVars['subject_id']) ? $allPostVars['subject_id']: null);
	$subjectName =		( isset($allPostVars['subject_name']) ? $allPostVars['subject_name']: null);
	$classCatId =		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$parentSubjectId =	( isset($allPostVars['parent_subject_id']) ? $allPostVars['parent_subject_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.subjects
			SET subject_name = :subjectName,
				class_cat_id = :classCatId,
				teacher_id = :teacherId,
				parent_subject_id = :parentSubjectId,
				modified_date = now(),
				modified_by = :userId
            WHERE subject_id = :subjectId");
 
        $sth->execute( array(':subjectName' => $subjectName, ':classCatId' => $classCatId, ':teacherId' => $teacherId, 
							 ':subjectId' => $subjectId, ':userId' => $userId, ':parentSubjectId' => $parentSubjectId,
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

$app->put('/setSubjectStatus', function () use($app) {
    // Update subject status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$subjectId =( isset($allPostVars['subject_id']) ? $allPostVars['subject_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.subjects
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE subject_id = :subjectId
							"); 
        $sth->execute( array(':subjectId' => $subjectId, 
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

$app->put('/setSubjectSortOrder', function () use($app) {
    // Update subject sort order
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.subjects
							SET sort_order = :sortOrder,
								modified_date = now(),
								modified_by = :userId 
							WHERE subject_id = :subjectId
							"); 
							
		$db->beginTransaction();
		foreach( $sortData as $item )
		{
			$subjectId =	( isset($item['subject_id']) ? $item['subject_id']: null);
			$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
			$sth->execute( array(':subjectId' => $subjectId, 
							 ':sortOrder' => $sortOrder, 
							 ':userId' => $userId
					) );
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

$app->get('/checkSubject/:subject_id', function ($subjectId) {
    // Check is subject can be deleted
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT count(class_subject_id) as num_classes
								FROM app.class_subjects
								WHERE subject_id = :subjectId");
        $sth->execute( array(':subjectId' => $subjectId ) );
 
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

$app->delete('/deleteSubject/:subject_id', function ($subjectId) {
    // delete subject
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$d1 = $db->prepare("DELETE FROM app.class_subject_exams 
								WHERE class_sub_exam_id in (select class_sub_exam_id 
															from app.class_subjects
															inner join app.class_subject_exams
															on class_subjects.class_subject_id = class_subject_exams.class_subject_id
															where subject_id = :subjectId)
							");
		$d2 = $db->prepare("DELETE FROM app.class_subjects WHERE subject_id = :subjectId");		
		
		$d3 = $db->prepare("DELETE FROM app.subjects WHERE subject_id = :subjectId");	
										
		$db->beginTransaction();
		$d1->execute( array(':subjectId' => $subjectId) );
		$d2->execute( array(':subjectId' => $subjectId) );
		$d3->execute( array(':subjectId' => $subjectId) );
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

$app->get('/getTeacherSubjects/:teacher_id(/:status)', function ($teacherId, $status = true) {
    //Show subjects for specific teacher
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$sth = $db->prepare("SELECT subjects.subject_id, subject_name, subjects.teacher_id, subjects.active, subjects.class_cat_id, class_cat_name,
					first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name,
					(select count(*) 
								from app.class_subjects 
								INNER JOIN app.classes 
									INNER JOIN app.students ON students.current_class = classes.class_id AND classes.active is true 
								ON class_subjects.class_id = classes.class_id								
								WHERE subject_id = subjects.subject_id) as num_students
					FROM app.subjects 
					INNER JOIN app.class_cats 
					ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true	
					INNER JOIN app.employees ON subjects.teacher_id = employees.emp_id
					WHERE subjects.teacher_id = :teacherId
					AND subjects.active = :status 
					ORDER BY subjects.sort_order"); 
		
        $sth->execute( array(':teacherId' => $teacherId, ':status' => $status));
 
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
?>
