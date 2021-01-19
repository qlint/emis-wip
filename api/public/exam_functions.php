<?php
$app->get('/getExamTypes/:class_cat_id', function ($classCatId) {

	// Get all exam types

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();

		$sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
							FROM app.exam_types
							LEFT JOIN app.class_cats
							ON exam_types.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							WHERE exam_types.class_cat_id = :classCatId
							ORDER BY sort_order");
		$sth->execute(array(':classCatId' => $classCatId));
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

$app->get('/getExamTypesByEntity/:entity_id', function ($entityId) {

	// Get all exam types

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();

		$sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
							FROM app.exam_types
							LEFT JOIN app.class_cats
							ON exam_types.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							WHERE exam_types.class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = :entityId LIMIT 1)
							ORDER BY sort_order");
		$sth->execute(array(':entityId' => $entityId));
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

$app->get('/getSpecialExamTypes/:class_cat_id', function ($classCatId) {

	// Get all exam types

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();

		$sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
							FROM app.exam_types
							LEFT JOIN app.class_cats
							ON exam_types.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							WHERE exam_types.class_cat_id = :classCatId
							AND exam_types.is_special_exam IS TRUE
							ORDER BY sort_order");
		$sth->execute(array(':classCatId' => $classCatId));
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

$app->get('/getNonSpecialExamTypes/:class_cat_id', function ($classCatId) {

	// Get all exam types

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();

		$sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
							FROM app.exam_types
							LEFT JOIN app.class_cats
							ON exam_types.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							WHERE exam_types.class_cat_id = :classCatId
							AND exam_types.is_special_exam IS FALSE
							ORDER BY sort_order");
		$sth->execute(array(':classCatId' => $classCatId));
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

$app->post('/addExamType', function () use($app) {
	// Add exam type

	$allPostVars = json_decode($app->request()->getBody(),true);

	$examType =		( isset($allPostVars['exam_type']) ? $allPostVars['exam_type']: null);
	$classCatId =	( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$specialExam =		( isset($allPostVars['special_exam']) ? $allPostVars['special_exam']: false);
	if($specialExam === false){ $specialExam = 0; }elseif ($specialExam === true) {$specialExam = 1; }

	try
	{
		$db = getDB();

		$sth0 = $db->prepare("SELECT max(sort_order) as sort_order FROM app.exam_types WHERE class_cat_id = :classCatId");
		/* get the next number for sort order */

		$sth1 = $db->prepare("INSERT INTO app.exam_types(exam_type, class_cat_id, sort_order, created_by, is_special_exam)
								VALUES(:examType, :classCatId, :sortOrder, :userId, :specialExam)");
		$sth2 = $db->prepare("SELECT * FROM app.exam_types WHERE exam_type_id = currval('app.exam_types_exam_type_id_seq')");

		$db->beginTransaction();
		$sth0->execute( array(':classCatId' => $classCatId) );
		$sort = $sth0->fetch(PDO::FETCH_OBJ);
		$sortOrder = ($sort && $sort->sort_order !== NULL ? $sort->sort_order + 1 : 1);

		$sth1->execute( array(':examType' => $examType, ':classCatId' => $classCatId, ':sortOrder' => $sortOrder, ':userId' => $userId, ':specialExam' => $specialExam ) );
		$sth2->execute();
		$results = $sth2->fetch(PDO::FETCH_OBJ);

		$db->commit();
		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "data" => $results));
		$db = null;
	} catch(PDOException $e) {
		$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	}

});

$app->delete('/deleteExamType/:exam_type_id', function ($examTypeId) {
	// delete exam type

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();

		$sth = $db->prepare("DELETE FROM app.exam_types WHERE exam_type_id = :examTypeId");
		$sth->execute( array(':examTypeId' => $examTypeId) );

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

$app->put('/setExamTypeSortOrder', function () use($app) {
	// Update exam type sort order

	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

	try
	{
		$db = getDB();
		$sth = $db->prepare("UPDATE app.exam_types
							SET sort_order = :sortOrder,
								modified_date = now(),
								modified_by = :userId
							WHERE exam_type_id = :examTypeId
							");

		$db->beginTransaction();
		foreach( $sortData as $item )
		{
			$examTypeId =	( isset($item['exam_type_id']) ? $item['exam_type_id']: null);
			$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
			$sth->execute( array(':examTypeId' => $examTypeId,
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

$app->get('/getStudentExamMarks/:student_id/:class/:term(/:type)', function ($studentId,$classId,$termId,$examTypeId=null) {
	//Get student exam marks

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();

		// get exam marks by exam type
		$queryArray = array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId);
		$query = "SELECT subject_name, (select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name
							  ,exam_id
								,exam_type
							  ,mark
							  ,grade_weight
							  ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId
						AND term_id = :termId
						AND student_id = :studentId
						";

		if( $examTypeId !== null )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId ";
			$queryArray[':examTypeId'] = $examTypeId;
		}

		$query .= "ORDER BY subjects.sort_order, exam_types.exam_type_id ";

		$sth = $db->prepare($query);
		$sth->execute( $queryArray );

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

$app->get('/getClassExamMarks/:class_id/:term_id/:exam_type_id(/:teacher_id)', function ($classId, $termId, $examTypeId, $teacherId=null) {
	//Show exam marks for all students in class

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();
		$params = array(':classId' => $classId, ':termId' => $termId, ':examTypeId' => $examTypeId);
		$query = "SELECT q.student_id, student_name, subject_name, q.class_sub_exam_id, mark, exam_marks.term_id, grade_weight, sort_order, parent_subject_id, subject_id, is_parent, use_for_grading
							FROM (
								SELECT  students.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
									subject_name, class_subject_exams.class_sub_exam_id, grade_weight, subjects.sort_order, parent_subject_id, subjects.subject_id,
									case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is null then false else true end as is_parent,
									use_for_grading
								FROM app.students
								INNER JOIN app.classes
								INNER JOIN app.class_subjects
								INNER JOIN app.subjects
									ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
								INNER JOIN app.class_subject_exams
									ON class_subjects.class_subject_id = class_subject_exams.class_subject_id
									ON classes.class_id = class_subjects.class_id
								    ON students.current_class = classes.class_id
								WHERE current_class = :classId
								AND exam_type_id = :examTypeId
								AND students.active IS TRUE
							";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId) ";
			$params[':teacherId'] = $teacherId;
		}

		$query .= ") q
							LEFT JOIN app.exam_marks
								INNER JOIN app.terms
								ON exam_marks.term_id = terms.term_id
							ON q.class_sub_exam_id = exam_marks.class_sub_exam_id AND exam_marks.term_id = :termId AND exam_marks.student_id = q.student_id
							ORDER BY student_name, sort_order, subject_name";

		$sth = $db->prepare($query);
		$sth->execute($params);
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

$app->get('/getAllStudentExamMarks/:class/:term/:type(/:teacherId)', function ($classId,$termId,$examTypeId,$teacherId=null) {
	//Get all student exam marks
	$app = \Slim\Slim::getInstance();

	try
	{
		// need to make sure class, term and type are integers
		if( is_numeric($classId) && is_numeric($termId)  && is_numeric($examTypeId) )
		{
			$db = getDB();

			$query = "select app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
							 ,classes.class_id
							  ,subject_name
							  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
							  ,exam_type
							  ,exam_marks.student_id
							  ,mark
							  ,grade_weight
							  ,subjects.sort_order
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes
							ON class_subjects.class_id = classes.class_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						WHERE class_subjects.class_id = $classId
						AND term_id = $termId
						AND class_subject_exams.exam_type_id = $examTypeId
						AND subjects.use_for_grading is true
						AND students.active is true
						";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = $teacherId OR classes.teacher_id = $teacherId) ";
		}

		$query .= "	WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
						',
						array['gender','student_id','student_name','exam_type'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";

			$query2 = "select *,
									(
										SELECT rank FROM (
											SELECT
												student_id,
												total_mark,
												rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students
												ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId
												AND class_subject_exams.exam_type_id = $examTypeId
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as rank
									from _exam_marks order by exam_type, rank;";

			$sth1 = $db->prepare($query);
			$sth2 = $db->prepare($query2);

			$db->beginTransaction();
			$sth1->execute();
			$sth2->execute();
			$results = $sth2->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
		}

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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
	}

});

$app->get('/getStudentSubjectsForExams/:class/:term/:examTypeId(/:teacherId)', function ($classId,$termId,$examTypeId,$teacherId=null) {
	//Get all student exam marks
	$app = \Slim\Slim::getInstance();

	try
	{
		// need to make sure class, term and type are integers
		if( is_numeric($classId) && is_numeric($termId) && is_numeric($examTypeId) )
		{
			$db = getDB();

			$query = "SELECT student_name, array_to_string(ARRAY_AGG(subject),',') AS subjects
								FROM (
									SELECT s.student_id, '(' || s.student_id || ') ' || s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
										c.class_name, '(' || cs.subject_id || ') ' || s2.subject_name AS subject, cse.grade_weight, s2.sort_order
									FROM app.students s
									INNER JOIN app.student_class_history sch USING (student_id)
									INNER JOIN app.classes c USING (class_id)
									INNER JOIN app.class_subjects cs ON c.class_id = cs.class_id
									INNER JOIN app.subjects s2 USING (subject_id)
									INNER JOIN app.class_subject_exams cse USING (class_subject_id)
									INNER JOIN app.terms t ON sch.start_date >= t.start_date AND sch.end_date <= t.end_date
									WHERE sch.class_id = $classId
									AND t.term_id = $termId
									AND cse.exam_type_id = $examTypeId AND s2.active IS TRUE AND s.active IS TRUE ";
			if( $teacherId !== null )
			{
			$query .= " AND (s2.teacher_id = $teacherId) ";
			}
			$query .= " ORDER BY student_name ASC, s2.sort_order ASC
								)a
								GROUP BY student_name
								ORDER BY student_name ASC";

			$sth1 = $db->prepare($query);

			$db->beginTransaction();
			$sth1->execute();
			$results = $sth1->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
		}

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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
	}

});

$app->get('/getResultSlips/:class/:term/:type(/:teacherId)', function ($classId,$termId,$examTypeId,$teacherId=null) {
	//Get all student exam marks
	$app = \Slim\Slim::getInstance();

	try
	{
		// need to make sure class, term and type are integers
		if( is_numeric($classId) && is_numeric($termId)  && is_numeric($examTypeId) )
		{
			$db = getDB();

			$query = "select app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
							 ,classes.class_id
							  ,subject_name
							  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
							  ,exam_type
							  ,exam_marks.student_id
							  ,mark
							  ,grade_weight
							  ,subjects.sort_order, admission_number, class_name
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes
							ON class_subjects.class_id = classes.class_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						WHERE class_subjects.class_id = $classId
						AND term_id = $termId
						AND class_subject_exams.exam_type_id = $examTypeId
						AND subjects.use_for_grading is true
						AND students.active is true
						";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = $teacherId OR classes.teacher_id = $teacherId) ";
		}

		$query .= "	WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order ASC, mark desc)
						',
						array['gender','student_id','student_name','admission_number','class_name','exam_type'], array['sort_order','subject_name','grade_weight'], '#.mark', null);";

			$query2 = "select *,
									(
										SELECT rank FROM (
											SELECT
												student_id,
												total_mark,
												rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students
												ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId
												AND class_subject_exams.exam_type_id = $examTypeId
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as rank,
									(
										SELECT rank FROM (
											SELECT
												student_id,
												total_mark,
												rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students
												ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))))
												AND term_id = $termId
												AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId) AND class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))))
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as stream_rank,
									(SELECT COUNT(*) FROM app.students WHERE active IS TRUE AND current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))))) as stream_out_of,
									(
										SELECT total_mark FROM (
											SELECT
												student_id,
												total_mark,
												rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students
												ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId
												AND class_subject_exams.exam_type_id = $examTypeId
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as total_mark,
									(
										SELECT round(total_mark::float/tot_students::float) || ' / 500' AS overall_mean FROM (
											SELECT
												sum(total_mark) as total_mark, count(total_mark) as tot_students
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students
												ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))))
												AND term_id = $termId
												AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))) AND sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId))
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
										) q
									) as overall_mean
									from _exam_marks order by exam_type, rank;";

			$sth1 = $db->prepare($query);
			$sth2 = $db->prepare($query2);

			$db->beginTransaction();
			$sth1->execute();
			$sth2->execute();
			$results = $sth2->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
		}

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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
	}

});

$app->get('/getAllStudentStreamMarks/:entity/:term/:examTypeId', function ($entityId,$termId,$examTypeId) {
	//Get all student exam marks
	$app = \Slim\Slim::getInstance();

	try
	{
		// need to make sure class, term and type are integers
		if( is_numeric($termId)  && is_numeric($entityId) && is_numeric($examTypeId) )
		{
			$db = getDB();

			$query = "SELECT app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,
                                                                      classes.class_id,subject_name,
                                                                      coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name,
                                                                      exam_type, exam_marks.student_id, mark, grade_weight, subjects.sort_order
                                                                    FROM app.exam_marks
                                                                    INNER JOIN app.class_subject_exams
                                                                    INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                                                    INNER JOIN app.class_subjects
                                                                    INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
                                                                    INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                                                          ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                                                          ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                                                    INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                                                    WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $entityId))
                                                                    AND term_id = $termId
                                                                    AND class_subject_exams.exam_type_id IN (
																		SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $entityId) AND sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId)
																	)
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order asc, mark desc)',
                                                                array['gender','student_name'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";

			$query2 = "SELECT *, ( SELECT total_mark FROM (
                                                                  SELECT student_id, student_name, total_mark, rank() over w as rank
                                                                  FROM (
                                                                    SELECT exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                                                      coalesce(sum(CASE WHEN subjects.parent_subject_id IS NULL THEN mark END),0) AS total_mark
                                                                    FROM app.exam_marks
                                                                    INNER JOIN app.class_subject_exams
                                                                    INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                                                    INNER JOIN app.class_subjects
                                                                    INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active IS TRUE AND subjects.use_for_grading IS TRUE
                                                                          ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
                                                                          ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                                                    INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                                                    WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $entityId))
                                                                    AND term_id = $termId
                                                                    AND class_subject_exams.exam_type_id IN (
																		SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $entityId) AND sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId)
																	)
                                                                    AND students.active IS TRUE
                                                                    GROUP BY exam_marks.student_id, students.first_name, students.middle_name, students.last_name
                                                                  ) a
                                                                  WINDOW w AS (ORDER BY total_mark DESC)
                                                                ) q
                                                                WHERE student_name = _exam_marks.student_name
                                                              ) AS total,
                                                            ( SELECT rank FROM (
                                                                  SELECT student_id, student_name, total_mark, rank() over w as rank
                                                                  FROM (
                                                                    SELECT exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                                                      coalesce(sum(CASE WHEN subjects.parent_subject_id IS NULL THEN mark END),0) AS total_mark
                                                                    FROM app.exam_marks
                                                                    INNER JOIN app.class_subject_exams
                                                                    INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                                                    INNER JOIN app.class_subjects
                                                                    INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active IS TRUE AND subjects.use_for_grading IS TRUE
                                                                          ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
                                                                          ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                                                    INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                                                    WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $entityId))
                                                                    AND term_id = $termId
                                                                    AND class_subject_exams.exam_type_id IN (
																		SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $entityId) AND sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId)
																	)
                                                                    AND students.active IS TRUE
                                                                    GROUP BY exam_marks.student_id, students.first_name, students.middle_name, students.last_name
                                                                  ) a
                                                                  WINDOW w AS (ORDER BY total_mark DESC)
                                                                ) q
                                                                WHERE student_name = _exam_marks.student_name
                                                              ) AS rank
                                                            FROM _exam_marks ORDER BY rank;";

			$sth1 = $db->prepare($query);
			$sth2 = $db->prepare($query2);

			$db->beginTransaction();
			$sth1->execute();
			$sth2->execute();
			$results = $sth2->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
		}

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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
	}

});

$app->get('/getDoneExamSubjectCount/:class/:term/:exam_type_id', function ($classId,$termId,$examTypeId) {
	//Get all student exam marks
	$app = \Slim\Slim::getInstance();

	try
	{
		// need to make sure class, term and type are integers
		if( is_numeric($classId) && is_numeric($termId)  && is_numeric($examTypeId) )
		{
			$db = getDB();

			$query = "SELECT distinct subject_name, sort_order, count(mark) FROM (
									SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,subject_name,
										coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name,exam_type,
										exam_marks.student_id,mark,grade_weight,subjects.sort_order
									FROM app.exam_marks
									INNER JOIN app.class_subject_exams
									INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects
									INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
									INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
												ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
									INNER JOIN app.students ON exam_marks.student_id = students.student_id
									WHERE class_subjects.class_id =$classId
									AND term_id = $termId
									AND class_subject_exams.exam_type_id = $examTypeId
									AND subjects.use_for_grading is true
									AND students.active is true
									WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
								)a WHERE mark is not null GROUP BY a.subject_name, a.sort_order ORDER BY sort_order";

			$sth1 = $db->prepare($query);

			$db->beginTransaction();
			$sth1->execute();
			$results = $sth1->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
		}

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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		echo json_encode(array('response' => 'success', 'nodata' => 'No count data found' ));
	}

});

$app->get('/getStreamDoneExamSubjectCount/:entity/:term', function ($entityId,$termId) {
	//Get all student exam marks
	$app = \Slim\Slim::getInstance();

	try
	{
		// need to make sure class, term and type are integers
		if( is_numeric($entityId) && is_numeric($termId) )
		{
			$db = getDB();

			$query = "SELECT distinct subject_name, sort_order, count(mark) FROM (
									SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,subject_name,
										coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name,exam_type,
										exam_marks.student_id,mark,grade_weight,subjects.sort_order
									FROM app.exam_marks
									INNER JOIN app.class_subject_exams
									INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects
									INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
									INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
												ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
									INNER JOIN app.students ON exam_marks.student_id = students.student_id
									WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $entityId))
									AND term_id = $termId
									AND class_subject_exams.exam_type_id IN (
														SELECT exam FROM (
															SELECT max(exam_type_id) as exam, class_id FROM (
																SELECT DISTINCT ON(cse.exam_type_id, c.class_id) cse.exam_type_id, c.class_id FROM app.class_subject_exams cse
																INNER JOIN app.exam_types et USING (exam_type_id)
																INNER JOIN app.class_cats cc USING (class_cat_id)
																INNER JOIN app.classes c USING (class_cat_id)
																INNER JOIN app.exam_marks em USING (class_sub_exam_id)
																WHERE cc.entity_id = $entityId
																AND em.term_id = $termId
															)q1
															GROUP BY q1.class_id
														)q2
													)
									AND subjects.use_for_grading is true
									AND students.active is true
									WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
								)a WHERE mark is not null GROUP BY a.subject_name, a.sort_order ORDER BY sort_order";

			$sth1 = $db->prepare($query);

			$db->beginTransaction();
			$sth1->execute();
			$results = $sth1->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
		}

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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		echo json_encode(array('response' => 'success', 'nodata' => 'No count data found' ));
	}

});

$app->post('/addExamMarks', function () use($app) {
	// Add exam mark

	$allPostVars = json_decode($app->request()->getBody(),true);

	$examMarks =	( isset($allPostVars['exam_marks']) ? $allPostVars['exam_marks']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

	try
	{
		$db = getDB();

		$getExam = $db->prepare("SELECT exam_id FROM app.exam_marks WHERE student_id = :studentId AND class_sub_exam_id = :classSubExamId AND term_id = :termId");

		$addMark = $db->prepare("INSERT INTO app.exam_marks(student_id, class_sub_exam_id, term_id, mark, created_by)
								VALUES(:studentId, :classSubExamId, :termId, :mark, :userId)");

		$updateMark = $db->prepare("UPDATE app.exam_marks
									SET mark = :mark,
										modified_date = now(),
										modified_by = :userId
									WHERE exam_id = :examId");

		$deleteMark = $db->prepare("DELETE FROM app.exam_marks WHERE exam_id = :examId");

		$db->beginTransaction();

		if( count($examMarks) > 0 )
		{
			foreach($examMarks as $mark)
			{
				$studentId = ( isset($mark['student_id']) ? $mark['student_id']: null);
				$classSubExamId = ( isset($mark['class_sub_exam_id']) ? $mark['class_sub_exam_id']: null);
				$termId = ( isset($mark['term_id']) ? $mark['term_id']: null);
				$mark = ( isset($mark['mark']) && !empty($mark['mark']) ? $mark['mark']: null);

				$currentExamMarks = $getExam->execute(array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId ));
				$examId = $getExam->fetch(PDO::FETCH_OBJ);

				if( $examId )
				{
					$updateMark->execute( array(':mark' => $mark, ':examId' => $examId->exam_id, ':userId' => $userId  ) );
				}
				else
				{
					$addMark->execute( array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId, ':mark' => $mark, ':userId' => $userId ) );
				}
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

$app->get('/getTopStudents(/:class_id)', function ($classId=null) {
	//Get all top 3 students for each class, or requested class

	$app = \Slim\Slim::getInstance();

	try
	{

		$db = getDB();
		$queryParams = array();
		$query = "SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
							case when denominator > 1 then
								round(total_mark/denominator)
							else
								total_mark
							end as total_mark,
							case when denominator > 1 then
								round(total_grade_weight/denominator)
							else
								'500'
							end as total_grade_weight,
							rank, percentage,
							(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
							position_out_of
							FROM (
							SELECT
								 student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_id
									  ,class_name
									  ,total_mark
									  ,total_grade_weight
									  ,round((total_grade_weight/500)) as denominator
									  ,round((total_mark/total_grade_weight)*100) as percentage
									  ,rank() over w as rank
									  ,position_out_of
							FROM (
								SELECT
									 exam_marks.student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_subjects.class_id
									  ,class_name
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										mark
										end),0) as total_mark
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										grade_weight
									   end),0) as total_grade_weight
									  ,(select count(*) from app.students where active is true and current_class = students.current_class) as position_out_of
								FROM app.exam_marks
								INNER JOIN app.students
								ON exam_marks.student_id = students.student_id
								INNER JOIN app.class_subject_exams
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id AND classes.active is true
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE term_id = (select term_id from app.current_term)
								AND students.active is true

					";

		if( $classId !== null  )
		{
			$query .= " AND class_subjects.class_id = :classId ";
			$queryParams = array(':classId' => $classId );
		}

		$query .= " 	GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name
					) a
								WINDOW w AS (PARTITION BY class_id ORDER BY class_id desc, total_mark desc)
							 ) q
							 WHERE rank < 4";

		//echo $query;

		$sth = $db->prepare($query);
		$sth->execute($queryParams);
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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
	}

});

$app->get('/getTeacherTopStudents/:teacher_id(/:class_id)', function ($teacherId, $classId=null) {
	//Get all top 3 students for each class, or requested class

	$app = \Slim\Slim::getInstance();

	try
	{

		$db = getDB();
		$queryParams = array(':teacherId' => $teacherId);
		$query = "SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
							round(total_mark/denominator) as total_mark,
							round(total_grade_weight/denominator) as total_grade_weight,
							rank, percentage,
							(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
							position_out_of
							FROM (
							SELECT
								 student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_id
									  ,class_name
									  ,total_mark
									  ,total_grade_weight
									  ,round((total_grade_weight/500)) as denominator
									  ,round((total_mark/total_grade_weight)*100) as percentage
									  ,rank() over w as rank
									  ,position_out_of
							FROM (
								SELECT
									 exam_marks.student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_subjects.class_id
									  ,class_name
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										mark
										end),0) as total_mark
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										grade_weight
									   end),0) as total_grade_weight
									  ,(select count(*) from app.students where active is true and current_class = students.current_class) as position_out_of
								FROM app.exam_marks
								INNER JOIN app.students
								ON exam_marks.student_id = students.student_id
								INNER JOIN app.class_subject_exams
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id AND classes.active is true
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE term_id = (select term_id from app.current_term)
								AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId)
								AND students.active is true
					";

		if( $classId !== null  )
		{
			$query .= " AND class_subjects.class_id = :classId ";
			$queryParams[':classId'] = $classId;
		}

		$query .= " 	GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name
					) a
								WINDOW w AS (PARTITION BY class_id ORDER BY class_id desc, total_mark desc)
							 ) q
							 WHERE rank < 4";

		//echo $query;

		$sth = $db->prepare($query);
		$sth->execute($queryParams);
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
		//echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
	}

});

$app->put('/updateExamClass', function () use($app) {
	$allPostVars = json_decode($app->request()->getBody(),true);

	$examIds =	( isset($allPostVars['exam_ids']) ? $allPostVars['exam_ids']: null);
	$classId =	( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);

	try
	{
		$db = getDB();

		$getIds = $db->prepare("SELECT exam_type_id, subject_id
															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
																INNER JOIN app.class_subjects
																ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															WHERE exam_id = :examId");

		$getNewId = $db->prepare("SELECT class_sub_exam_id
																FROM app.class_subject_exams
																INNER JOIN app.class_subjects
																ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																WHERE subject_id = :subjectId
																AND class_id = :classId
																AND exam_type_id = :examTypeId");

		$updateExam = $db->prepare("UPDATE app.exam_marks
													SET class_sub_exam_id = :newId
													WHERE exam_id = :examId");

		$db->beginTransaction();

		foreach( $examIds as $examId )
		{
			// determine the subject and exam type ids
			$getIds->execute(array(':examId' => $examId));
			$ids = $getIds->fetch(PDO::FETCH_OBJ);

			// use to get the new class subject exam id using new class id
			$getNewId->execute(array(':subjectId' => $ids->subject_id, ':examTypeId' => $ids->exam_type_id, ':classId' => $classId ));
			$newId = $getNewId->fetch(PDO::FETCH_OBJ);

			// update exam to new class subject exam id
			$updateExam->execute(array('newId' => $newId->class_sub_exam_id, ':examId' => $examId));

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

$app->post('/advncedExamEdit', function () use($app) {
	// Add exam type

	$allPostVars = json_decode($app->request()->getBody(),true);
	$results = new stdClass();

	$operation =	( isset($allPostVars['operation']) ? $allPostVars['operation']: null);
	$termId =		( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$marks = ( isset($allPostVars['marks']) ? $allPostVars['marks']: null);
	$results->operation = $operation;
	$results->term_id = $termId;
	$results->marks = $marks;
	if($operation == 'move'){
		$fromClassId =	( isset($allPostVars['from_class_id']) ? $allPostVars['from_class_id']: null);
		$fromExamTypeId =		( isset($allPostVars['from_et_id']) ? $allPostVars['from_et_id']: null);
		$fromTermId =		( isset($allPostVars['from_term_id']) ? $allPostVars['from_term_id']: null);
		$toClassId =		( isset($allPostVars['to_class_id']) ? $allPostVars['to_class_id']: null);
		$toExamTypeId =		( isset($allPostVars['to_et_id']) ? $allPostVars['to_et_id']: null);
		$toTermId =		( isset($allPostVars['to_term_id']) ? $allPostVars['to_term_id']: null);
		$results->from_class_id = $fromClassId;
		$results->from_exam_type_id = $fromExamTypeId;
		$results->from_term_id = $fromTermId;
		$results->to_class_id = $toClassId;
		$results->to_exam_type_id = $toExamTypeId;
		$results->to_term_id = $toTermId;
	}elseif($operation == 'delete'){
		$deleteClassId =	( isset($allPostVars['from_class_id']) ? $allPostVars['from_class_id']: null);
		$deleteExamTypeId =		( isset($allPostVars['from_et_id']) ? $allPostVars['from_et_id']: null);
		$results->delete_class_id = $deleteClassId;
		$results->delete_exam_type_id = $deleteExamTypeId;
	}

	try
	{
		$db = getDB();

		if($operation == 'move'){
			$db->beginTransaction();
			foreach( $marks as $subjMark )
			{
				$classSubExamId =	( isset($subjMark['class_sub_exam_id']) ? $subjMark['class_sub_exam_id']: null);
				$studentId =	( isset($subjMark['student_id']) ? $subjMark['student_id']: null);
				$subjectId =	( isset($subjMark['subject_id']) ? $subjMark['subject_id']: null);

				if($toExamTypeId == $fromExamTypeId){
					/*
					$sth0 = $db->prepare("UPDATE app.class_subject_exams
																SET exam_type_id = :toExamTypeId, modified_date = now()
																FROM (
																		SELECT class_sub_exam_id FROM app.exam_marks
																		WHERE class_sub_exam_id = :classSubExamId
																		AND student_id = :studentId
																		AND term_id = :fromTermId
																	 ) AS subquery
																WHERE class_subject_exams.class_sub_exam_id = subquery.class_sub_exam_id
																AND class_subject_exams.exam_type_id = :fromExamTypeId;");

					$sth0->execute( array(':fromExamTypeId' => $fromExamTypeId, ':toExamTypeId' => $toExamTypeId, ':classSubExamId' => $classSubExamId, ':studentId' => $studentId, ':fromTermId' => $fromTermId) );
					*/

					$sth1 = $db->prepare("UPDATE app.exam_marks
																SET term_id = :toTermId, modified_date = now()
																WHERE class_sub_exam_id = :classSubExamId
																AND student_id = :studentId
																AND term_id = :fromTermId;");

					$sth1->execute( array(':toTermId' => $toTermId,
																':classSubExamId' => $classSubExamId,
																':studentId' => $studentId,
																':fromTermId' => $fromTermId
															) );
				}else{
					$sth0 = $db->prepare("SELECT class_sub_exam_id FROM app.class_subject_exams
																WHERE exam_type_id = :toExamTypeId
																AND class_subject_id = (
																	SELECT class_subject_id FROM app.class_subjects
																	WHERE subject_id = :subjectId AND class_id = :fromClassId
																);");

					$sth0->execute( array(':toExamTypeId' => $toExamTypeId,
																':fromClassId' => $fromClassId,
																':subjectId' => $subjectId) );
					$classSubExamIdResult = $sth0->fetch(PDO::FETCH_OBJ);
					$toClassSubExamId = $classSubExamIdResult->class_sub_exam_id;

					$sth1 = $db->prepare("UPDATE app.exam_marks
																SET term_id = :toTermId,
																		class_sub_exam_id = :toClassSubExamId,
																		modified_date = now()
																WHERE student_id = :studentId
																AND term_id = :fromTermId
																AND class_sub_exam_id = :classSubExamId;");

					$sth1->execute( array(':toTermId' => $toTermId,
																':classSubExamId' => $classSubExamId,
																':toClassSubExamId' => $toClassSubExamId,
																':studentId' => $studentId,
																':fromTermId' => $fromTermId
															) );
				}

			}
			$db->commit();
		}elseif($operation == 'delete'){

			$db->beginTransaction();
			foreach( $marks as $subjMark )
			{
				$classSubExamId =	( isset($subjMark['class_sub_exam_id']) ? $subjMark['class_sub_exam_id']: null);
				$studentId =	( isset($subjMark['student_id']) ? $subjMark['student_id']: null);
				$subjectId =	( isset($subjMark['subject_id']) ? $subjMark['subject_id']: null);

				$sth0 = $db->prepare("DELETE
															FROM app.exam_marks em
															USING app.class_subject_exams cse
															WHERE em.class_sub_exam_id = cse.class_sub_exam_id
															AND em.class_sub_exam_id = :classSubExamId
															AND student_id = :studentId
															AND term_id = :termId;");
				$sth0->execute( array(':studentId' => $studentId,
															':termId' => $termId,
															':classSubExamId' => $classSubExamId
														) );

			}
			/*
			foreach( $marks as $subjMark )
			{
				$classSubExamId =	( isset($subjMark['class_sub_exam_id']) ? $subjMark['class_sub_exam_id']: null);
				$studentId =	( isset($subjMark['student_id']) ? $subjMark['student_id']: null);
				$subjectId =	( isset($subjMark['subject_id']) ? $subjMark['subject_id']: null);

				$sth1 = $db->prepare("DELETE
															FROM app.class_subject_exams cse
															USING app.exam_marks em
															WHERE cse.class_sub_exam_id = em.class_sub_exam_id
															AND cse.class_sub_exam_id = :classSubExamId;");
				$sth1->execute( array(':classSubExamId' => $classSubExamId) );

			}
			*/
			$db->commit();
		}

		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "data" => $results));
		$db = null;
	} catch(PDOException $e) {
		$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	}

});
?>
