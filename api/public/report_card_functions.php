<?php
$app->get('/getAllStudentReportCards/:class_id', function ($classId) {
    //Get report cards for class

	$app = \Slim\Slim::getInstance();

    try
    {

		$db = getDB();

		$sth = $db->prepare("SELECT report_cards.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
									admission_number, report_cards.class_id, class_cat_id,
									class_name, report_cards.term_id, term_name, date_part('year', start_date) as year, report_data, json_data, report_cards.report_card_type,
									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
									report_cards.creation_date::date as date, published
							FROM app.report_cards
							INNER JOIN app.students ON report_cards.student_id = students.student_id
							INNER JOIN app.classes ON report_cards.class_id = classes.class_id
							INNER JOIN app.terms ON report_cards.term_id = terms.term_id
							LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
							WHERE report_cards.class_id = :classId
							ORDER BY students.student_id");

		$sth->execute( array($classId) );
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

$app->get('/getStudentReportCards/:student_id', function ($studentId) {
    //Get student report cards

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

		$sth = $db->prepare("SELECT report_card_id, report_cards.student_id, report_cards.class_id, class_name, term_name, report_cards.term_id,
									date_part('year', start_date) as year, report_data, json_data, report_cards.report_card_type, c.class_cat_id,
									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
									report_cards.creation_date::date as date, published, cc.entity_id
					FROM app.report_cards
					INNER JOIN app.students ON report_cards.student_id = students.student_id
					INNER JOIN app.classes c ON report_cards.class_id = c.class_id
					INNER JOIN app.terms ON report_cards.term_id = terms.term_id
					LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
					left join app.class_cats cc on cc.class_cat_id = c.class_cat_id
					WHERE report_cards.student_id = :studentId
					ORDER BY report_card_id");
		$sth->execute( array(':studentId' => $studentId) );


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

$app->get('/getStudentReportCard/:student_id/:class_id/:term_id', function ($studentId, $classId, $termId) {
    //Get student report card

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

		$sth = $db->prepare("SELECT report_cards.student_id, report_card_id, class_name, classes.class_id, term_name, report_cards.term_id,
									date_part('year', start_date) as year, report_data, json_data, report_cards.report_card_type,
									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
									report_cards.creation_date::date as date, published
					FROM app.report_cards
					INNER JOIN app.students ON report_cards.student_id = students.student_id
					INNER JOIN app.classes ON report_cards.class_id = classes.class_id
					INNER JOIN app.terms ON report_cards.term_id = terms.term_id
					LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
					WHERE report_cards.student_id = :studentId
					AND report_cards.class_id = :classId
					AND report_cards.term_id = :termId");
		$sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
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

$app->get('/getExamMarksforReportCard/:student_id/:class/:term(/:teacherId)', function ($studentId,$classId,$termId,$teacherId=null) {
	//Get student exam marks

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();

		// get exam marks by exam type
		$params = array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId);
		$query = "SELECT student_id, mark,
								grade_weight,
								exam_type,
								(select grade from app.grading where round((mark::float/grade_weight::float)*100) between min_mark and max_mark) as grade,
								(select comment from app.grading where round((mark::float/grade_weight::float)*100) between min_mark and max_mark) as comment,
								(select kiswahili_comment from app.grading where round((mark::float/grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
								(select principal_comment from app.grading where round((mark::float/grade_weight::float)*100) between min_mark and max_mark) as principal_comment,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
								subject_name,
								subjects.teacher_id,
								employees.initials,
								use_for_grading
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
							INNER JOIN app.exam_types
							ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							INNER JOIN app.class_subjects
								INNER JOIN app.subjects
									LEFT JOIN app.employees
									ON subjects.teacher_id = employees.emp_id
								ON class_subjects.subject_id = subjects.subject_id
								INNER JOIN app.classes
								ON class_subjects.class_id = classes.class_id
							ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId
						AND term_id = :termId
						AND student_id = :studentId
						AND mark IS NOT NULL
						";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId) ";
			$params[':teacherId'] = $teacherId;
		}
		$query .= "ORDER BY subjects.sort_order, exam_types.exam_type_id";

		$sth = $db->prepare($query);
		$sth->execute( $params );
		$details = $sth->fetchAll(PDO::FETCH_OBJ);


		// get overall marks per subjects, only use parent subjects
			$sth2 = $db->prepare("SELECT subject_name, total_mark, tot30, tot70, tot100, total_grade_weight, percentage1, (coalesce(tot30, 0) + coalesce(tot70, 0)) as percentage,
															(select grade from app.grading where (coalesce(tot30, 0) + coalesce(tot70, 0)) between min_mark and max_mark) as grade,
															(select comment from app.grading where (coalesce(tot30, 0) + coalesce(tot70, 0)) between min_mark and max_mark) as comment,
															(select kiswahili_comment from app.grading where (coalesce(tot30, 0) + coalesce(tot70, 0)) between min_mark and max_mark) as kiswahili_comment,
															(select principal_comment from app.grading where (coalesce(tot30, 0) + coalesce(tot70, 0)) between min_mark and max_mark) as principal_comment, sort_order, grade as overall_grade2
														FROM(
															SELECT  subject_name, total_mark, tot30, tot70, tot100, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage1,
																(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
																(select comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as comment,
																(select kiswahili_comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as kiswahili_comment,
																(select principal_comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as principal_comment, sort_order
															FROM (
																SELECT class_id,subject_id,subject_name,student_id,coalesce(sum(total_mark)) as total_mark,coalesce(sum(total_grade_weight)) as total_grade_weight,coalesce(sum(tot30)) as tot30,coalesce(sum(tot70)) as tot70,coalesce(sum(tot100)) as tot100,sort_order FROM (
																	SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																		/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,*/
																		(CASE
																			WHEN exam_types.is_last_exam is true THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																			WHEN exam_types.is_last_exam is false and exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse on et.exam_type_id = cse.exam_type_id inner join app.exam_marks em on cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' and em.term_id= :termId and class_cat_id=(select class_cat_id from app.classes where class_id= :classId) LIMIT 1) THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																			WHEN not exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse on et.exam_type_id = cse.exam_type_id inner join app.exam_marks em on cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' and em.term_id= :termId and class_cat_id=(select class_cat_id from app.classes where class_id= :classId) LIMIT 1) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																			--ELSE
																				--round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)
																		END) as total_mark,
																		(CASE
																			WHEN exam_types.is_last_exam is false and exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse on et.exam_type_id = cse.exam_type_id inner join app.exam_marks em on cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' and em.term_id= :termId and class_cat_id=(select class_cat_id from app.classes where class_id= :classId) LIMIT 1) THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)
																			WHEN exam_types.is_last_exam is false and not exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse on et.exam_type_id = cse.exam_type_id inner join app.exam_marks em on cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' and em.term_id= :termId and class_cat_id=(select class_cat_id from app.classes where class_id= :classId) LIMIT 1) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)
																			END) as tot30,
																		(CASE
																			WHEN exam_types.is_last_exam is true THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.7)
																		END) as tot70,
																		/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																		(CASE
																			WHEN exam_types.is_last_exam is true THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																			WHEN exam_types.is_last_exam is false and exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse on et.exam_type_id = cse.exam_type_id inner join app.exam_marks em on cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' and em.term_id= :termId and class_cat_id=(select class_cat_id from app.classes where class_id= :classId) LIMIT 1) THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																			WHEN not exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse on et.exam_type_id = cse.exam_type_id inner join app.exam_marks em on cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' and em.term_id= :termId and class_cat_id=(select class_cat_id from app.classes where class_id= :classId) LIMIT 1) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																			--ELSE
																				--round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)
																		END) as total_grade_weight,
																		(CASE
																			WHEN exam_types.is_last_exam is false and exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse on et.exam_type_id = cse.exam_type_id inner join app.exam_marks em on cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' and em.term_id= 1 and class_cat_id=(select class_cat_id from app.classes where class_id= 4) LIMIT 1) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)
																		END) as tot100,
																		subjects.sort_order,is_last_exam
																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																				ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																				ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	WHERE class_subjects.class_id = :classId
																	AND term_id = :termId
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND student_id = :studentId
																	AND mark IS NOT NULL
																	GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, exam_types.is_last_exam
																	ORDER BY sort_order ASC
																)a
																GROUP BY class_id,subject_id,subject_name,student_id,sort_order
																ORDER BY sort_order ASC
															) q
															ORDER BY sort_order
														)v ORDER BY sort_order"
				/*"SELECT subject_name, total_mark, total_grade_weight, percentage, grade, sort_order, exam_type_id,
	(select grade from app.grading where (v.percentage) between min_mark and max_mark) as overall_grade2
FROM(
	SELECT subject_name, total_mark, total_grade_weight, (avg(percentage) over (partition by subject_name))::float as percentage,
	grade, sort_order, exam_type_id, (select grade from app.grading where (foo.percentage) between min_mark and max_mark) as overall_grade
	 from
	(
	       SELECT  subject_name,total_mark,total_grade_weight,ceil(total_mark::float/total_grade_weight::float*100) as percentage,
			 (select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
			 sort_order, exam_type_id
	       FROM (
			 SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
				coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
				subjects.sort_order, class_subject_exams.exam_type_id
			 FROM app.exam_marks
			 INNER JOIN app.class_subject_exams
			 INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
			 INNER JOIN app.class_subjects
			 INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
			 WHERE class_subjects.class_id = :classId
			 AND term_id = :termId
			 AND subjects.parent_subject_id is null
			 AND subjects.use_for_grading is true
			 AND student_id = :studentId
			 AND mark IS NOT NULL
			 GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
		) q
	ORDER BY sort_order
	) as foo ORDER BY sort_order
)v ORDER BY sort_order"*/);
		$sth2->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$subjectOverall = $sth2->fetchAll(PDO::FETCH_OBJ);

		// get overall marks per subjects, only use parent subjects (overall determined by summation)
			$sth2BySum = $db->prepare("SELECT subject_name, total_mark, total_grade_weight, percentage, grade, comment, kiswahili_comment, principal_comment, sort_order, grade as overall_grade2
																	FROM(
																		SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
																			(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
																			(select comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as comment,
																			(select kiswahili_comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as kiswahili_comment,
																			(select principal_comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as principal_comment, sort_order
																		FROM (
																			SELECT class_id,subject_id,subject_name,student_id,coalesce(sum(total_mark)) as total_mark,coalesce(sum(total_grade_weight)) as total_grade_weight,sort_order FROM (
																				SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																					coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																					coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																					subjects.sort_order
																				FROM app.exam_marks
																				INNER JOIN app.class_subject_exams
																				INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																				INNER JOIN app.class_subjects
																				INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																							ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																							ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																				WHERE class_subjects.class_id = :classId
																				AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																				GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading
																				ORDER BY sort_order ASC
																			)a
																			GROUP BY class_id,subject_id,subject_name,student_id,sort_order
																			ORDER BY sort_order ASC
																		) q
																		ORDER BY sort_order
																	)v ORDER BY sort_order");
		$sth2BySum->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$subjectOverallBySum = $sth2BySum->fetchAll(PDO::FETCH_OBJ);

		// get overall marks per subjects, only use parent subjects (overall determined by average)
			$sth2ByAvg = $db->prepare("SELECT subject_name, total_mark, total_grade_weight, percentage, grade, comment, kiswahili_comment, principal_comment, sort_order, grade as overall_grade2
																	FROM(
																		SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
																			(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
																			(select comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as comment,
																			(select kiswahili_comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as kiswahili_comment,
																			(select principal_comment from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as principal_comment, sort_order
																		FROM (
																			SELECT class_id,subject_id,subject_name,student_id,total_mark,total_grade_weight,sort_order FROM (
																				SELECT class_id,subject_id,subject_name,student_id,
																					round(avg(total_mark)) as total_mark,
																					round(avg(total_grade_weight)) as total_grade_weight,sort_order
																				FROM
																					(
																						SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																							mark as total_mark,
																							grade_weight as total_grade_weight,subjects.sort_order,is_last_exam
																						FROM app.exam_marks
																						INNER JOIN app.class_subject_exams
																						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																						INNER JOIN app.class_subjects
																						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																						WHERE class_subjects.class_id = :classId
																						AND term_id = :termId
																						AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																						ORDER BY sort_order ASC
																					)b
																					GROUP BY class_id, subject_name, student_id, subject_id, sort_order
																					ORDER BY sort_order ASC
																			)a
																			ORDER BY sort_order ASC
																		) q
																		ORDER BY sort_order
																	)v ORDER BY sort_order");
		$sth2ByAvg->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$subjectOverallByAvg = $sth2ByAvg->fetchAll(PDO::FETCH_OBJ);

		// get overall position
		$sth3 = $db->prepare("SELECT total_mark, total_grade_weight, rank, round((current_term_marks::float/current_term_marks_out_of::float)*100) as percentage, (select grade from app.grading where round((current_term_marks::float/current_term_marks_out_of::float)*100) >= min_mark and  round((current_term_marks::float/current_term_marks_out_of::float)*100) <= max_mark) as grade, principal_comment, position_out_of, current_term_marks, current_term_marks_out_of FROM (
														SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, percentages.principal_comment, marks.position_out_of, positions.current_term_marks,
															(case
																WHEN positions.current_term_marks_out_of < 800 THEN
																	800
																WHEN positions.current_term_marks_out_of = 800 THEN
																	800
																WHEN positions.current_term_marks_out_of > 800 THEN
																	1200
															end) as current_term_marks_out_of
														FROM
															(SELECT student_id, total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage, (select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade, position_out_of FROM (
																SELECT student_id, total_mark, total_grade_weight,
																	round((SELECT trunc(cast(avg(a.percentage) as numeric),2) AS percentage FROM (
																			SELECT  subject_name, avg(round(total_mark::float/total_grade_weight::float*100)) as percentage FROM (
																				SELECT subject_name, coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																					coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight, subjects.sort_order
																				FROM app.exam_marks
																				INNER JOIN app.class_subject_exams
																				INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																				INNER JOIN app.class_subjects
																				INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																							ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																							ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																				WHERE class_subjects.class_id = :classId
																				AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																				GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
																			) q
																			GROUP BY q.sort_order,q.subject_name
																			ORDER BY sort_order
																		) a
																	)) as percentage,
																	dense_rank() over w as rank, position_out_of,
																	/*commented by tom for a quick hack, remember to remove*/
																	(SELECT COUNT(*) FROM (
																				SELECT DISTINCT exam_type_id FROM app.exam_marks
																				INNER JOIN app.class_subject_exams
																				INNER JOIN app.class_subjects ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																				WHERE student_id = a.student_id
																				AND class_subjects.class_id = :classId AND term_id = :termId
																			) AS temp
																	), 1 as num_exam_types
																FROM (
																	SELECT exam_marks.student_id,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																		(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																				ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																				ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students ON exam_marks.student_id = students.student_id
																	WHERE class_subjects.class_id = :classId
																	AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.active is true AND mark IS NOT NULL
																	/*hack by tom, remember to remove*/
																	AND class_subject_exams.exam_type_id = (SELECT  exam_type_id FROM app.exam_types WHERE exam_type_id=(select distinct exam_type_id from app.class_subject_exams cse inner join app.exam_marks em on cse.class_sub_exam_id=em.class_sub_exam_id where em.student_id=:studentId and em.term_id = :termId order by exam_type_id DESC LIMIT 1)) GROUP BY exam_marks.student_id
																) a WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
															) q WHERE student_id = :studentId) AS marks
															FULL OUTER JOIN
															(SELECT student_id, round(avg(percentage)) AS percentage, (SELECT grade FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS grade, (SELECT principal_comment FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS principal_comment FROM (
																SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM(
																	SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
																		SELECT class_id,subject_id,subject_name,student_id,coalesce(sum(total_mark)) as total_mark,coalesce(sum(total_grade_weight)) as total_grade_weight,sort_order FROM (
																			SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																				/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,*/
																				(CASE
																					WHEN exam_types.is_last_exam is true THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																					WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																					WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																					--ELSE
																						--round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)
																				END) as total_mark,
																				/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																				(CASE
																					WHEN exam_types.is_last_exam is true THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																					WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																					WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																					--ELSE
																						--round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)
																				END) as total_grade_weight,
																				subjects.sort_order, is_last_exam
																			FROM app.exam_marks
																			INNER JOIN app.class_subject_exams
																			INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																			INNER JOIN app.class_subjects
																			INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																			WHERE class_subjects.class_id = :classId
																			AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																			GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, exam_types.is_last_exam
																			ORDER BY sort_order ASC
																		)a
																		GROUP BY class_id,subject_id,subject_name,student_id,sort_order
																		ORDER BY sort_order ASC
																	) q ORDER BY sort_order
																)v ORDER BY sort_order
															)r GROUP BY student_id) AS percentages
															FULL OUTER JOIN
															(SELECT student_id, avg AS current_term_marks, avg_out_of AS current_term_marks_out_of, position AS rank FROM (
																SELECT student_id, avg, avg_out_of, rank() over(order by points desc)  as position FROM (
																	SELECT student_id, total_mark AS avg, floor((total_grade_weight + 99)/100)*100 as avg_out_of, points
																	FROM (
																		SELECT  student_id, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, avg(points) as points
																		FROM (
																			SELECT class_id, subject_id, subject_name, student_id, tm2 as total_mark, tgw2 as total_grade_weight, points FROM (
																				SELECT class_id, subject_id, subject_name, student_id, sum(total_mark2) as tm2, sum(total_grade_weight2) as tgw2, (SELECT points FROM app.grading WHERE round((sum(total_mark2)::float/sum(total_grade_weight2))*100) between min_mark and max_mark) AS points FROM (
																					SELECT class_id, class_subjects.subject_id, subject_name, exam_marks.student_id,
																						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																						(CASE
																							WHEN exam_types.is_last_exam is true THEN
																								/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*/
																								round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.7)

																							WHEN exam_types.is_last_exam is false and (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = :termId LIMIT 1) is not null THEN
																								round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																							WHEN not exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = :termId LIMIT 1) THEN
																								coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																						END) as total_mark2,
																						/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																						(CASE
																							WHEN exam_types.is_last_exam is true THEN
																								/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*/
																								round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.7)

																							WHEN exam_types.is_last_exam is false and (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = :termId LIMIT 1) is not null THEN
																								round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																							WHEN not exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = :termId LIMIT 1) THEN
																								coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																						END) as total_grade_weight2,
																						subjects.sort_order, class_subject_exams.exam_type_id
																					FROM app.exam_marks
																					INNER JOIN app.class_subject_exams
																					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																					INNER JOIN app.class_subjects
																					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																					WHERE class_subjects.class_id = :classId
																					AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND mark IS NOT NULL
																					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,
																						exam_types.is_last_exam,subjects.parent_subject_id,exam_marks.mark,class_subject_exams.grade_weight
																				)middle1
																				GROUP BY class_id, subject_id, subject_name, student_id
																		)middle
																		) q GROUP BY student_id ORDER BY points DESC
																	) AS foo ORDER BY points DESC
																) AS FOO2
																) AS foo3 WHERE student_id= :studentId
															) AS positions
															ON percentages.student_id = positions.student_id
															ON marks.student_id = percentages.student_id
														) AS foo4"
				/*"SELECT total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight,
										round((SELECT trunc(cast(avg(a.percentage) as numeric),2) AS percentage
											FROM (SELECT  subject_name, avg(ceil(total_mark::float/total_grade_weight::float*100)) as percentage
												FROM (
													SELECT subject_name, coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
														coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
														subjects.sort_order
													FROM app.exam_marks
													INNER JOIN app.class_subject_exams
													INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects
													INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
													WHERE class_subjects.class_id = :classId
													AND term_id = :termId
													AND subjects.parent_subject_id is null
													AND subjects.use_for_grading is true
													AND student_id = :studentId
													AND mark IS NOT NULL
													GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
												) q
												GROUP BY q.sort_order,q.subject_name
											ORDER BY sort_order) a)) as percentage,
										dense_rank() over w as rank, position_out_of,

										/*commented by tom for a quick hack, remember to remove*/

										/*(SELECT COUNT(*) FROM (
													SELECT DISTINCT exam_type_id
													FROM app.exam_marks
													INNER JOIN app.class_subject_exams
														INNER JOIN app.class_subjects
														ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
													ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
													WHERE student_id = a.student_id
													AND class_subjects.class_id = :classId
													AND term_id = :termId
												) AS temp), 1 as num_exam_types
									FROM (
										SELECT
											  exam_marks.student_id
											  ,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
											  , coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight
											  ,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of

										FROM app.exam_marks
										INNER JOIN app.class_subject_exams
											INNER JOIN app.exam_types
											ON class_subject_exams.exam_type_id = exam_types.exam_type_id
											INNER JOIN app.class_subjects
												INNER JOIN app.subjects
												ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
											ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
										INNER JOIN app.students
										ON exam_marks.student_id = students.student_id
										WHERE class_subjects.class_id = :classId
										AND term_id = :termId
										AND subjects.parent_subject_id is null
										AND subjects.use_for_grading is true
										AND students.active is true
										AND mark IS NOT NULL

										/*hack by tom, remember to remove*/

										/*AND class_subject_exams.exam_type_id =    (SELECT  exam_type_id FROM (SELECT

											  coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
											  , coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight
											  ,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of,
											  class_subject_exams.exam_type_id

										FROM app.exam_marks
										INNER JOIN app.class_subject_exams
											INNER JOIN app.exam_types
											ON class_subject_exams.exam_type_id = exam_types.exam_type_id
											INNER JOIN app.class_subjects
												INNER JOIN app.subjects
												ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
											ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
										INNER JOIN app.students
										ON exam_marks.student_id = students.student_id
										WHERE class_subjects.class_id = :classId
										AND term_id = :termId
										AND subjects.parent_subject_id is null
										AND subjects.use_for_grading is true
										AND students.active is true
										AND mark IS NOT NULL


										GROUP BY class_subject_exams.exam_type_id )  as inner_foo   order by exam_type_id desc LIMIT 1)

										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
								 ) q
								 where student_id = :studentId"*/
							 );
		$sth3->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overall = $sth3->fetch(PDO::FETCH_OBJ);

		// get overall position (exactly same as above) but current term marks by the average
		$sth3ByAverage = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage, (SELECT grade FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS grade, principal_comment,position_out_of,current_term_marks,current_term_marks_out_of FROM(
																			SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, percentages.principal_comment, marks.position_out_of,
																				percentages.total_marks_percent as current_term_marks,
																				(case
																					WHEN positions.current_term_marks_out_of between 0 and 700 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) > 13 THEN
																						700
																					WHEN positions.current_term_marks_out_of between 701 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) > 13 THEN
																						800
																					WHEN (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) < 14 THEN
																						1200
																					--WHEN positions.current_term_marks_out_of = 800 THEN
																						--800
																					--WHEN positions.current_term_marks_out_of > 800 THEN
																						--1200
																				end) as current_term_marks_out_of
																			FROM
																			(SELECT student_id, total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage, (select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade, position_out_of FROM (
																				SELECT student_id, total_mark, total_grade_weight,
																					round((SELECT trunc(cast(avg(a.percentage) as numeric),2) AS percentage FROM (
																						SELECT  subject_name, avg(round(total_mark::float/total_grade_weight::float*100)) as percentage FROM (
																							SELECT subject_name, coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																								coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight, subjects.sort_order
																							FROM app.exam_marks
																							INNER JOIN app.class_subject_exams
																							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																							INNER JOIN app.class_subjects
																							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																							WHERE class_subjects.class_id = :classId
																							AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																							GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
																						) q
																						GROUP BY q.sort_order,q.subject_name
																						ORDER BY sort_order
																						) a
																					)) as percentage,
																					dense_rank() over w as rank, position_out_of,
																					/*commented by tom for a quick hack, remember to remove*/
																					(SELECT COUNT(*) FROM (
																						SELECT DISTINCT exam_type_id FROM app.exam_marks
																						INNER JOIN app.class_subject_exams
																						INNER JOIN app.class_subjects ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																						WHERE student_id = a.student_id
																						AND class_subjects.class_id = :classId AND term_id = :termId
																					) AS temp
																					), 1 as num_exam_types
																				FROM (
																					SELECT exam_marks.student_id,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																						(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
																					FROM app.exam_marks
																					INNER JOIN app.class_subject_exams
																					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																					INNER JOIN app.class_subjects
																					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																					INNER JOIN app.students ON exam_marks.student_id = students.student_id
																					WHERE class_subjects.class_id = :classId
																					AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.active is true AND mark IS NOT NULL
																					/*hack by tom, remember to remove*/
																					AND class_subject_exams.exam_type_id = (SELECT  exam_type_id FROM app.exam_types WHERE exam_type_id=(select distinct exam_type_id from app.class_subject_exams cse inner join app.exam_marks em on cse.class_sub_exam_id=em.class_sub_exam_id where em.student_id=:studentId and em.term_id=:termId order by exam_type_id DESC LIMIT 1)) GROUP BY exam_marks.student_id
																				) a WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
																			) q WHERE student_id = :studentId) AS marks
																			FULL OUTER JOIN
																			(SELECT student_id, round(avg(percentage)) AS percentage, sum(percentage) as total_marks_percent, (SELECT grade FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS grade, (SELECT principal_comment FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS principal_comment FROM (
																			SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM(
																				SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
																					SELECT class_id,subject_id,subject_name,student_id,coalesce(sum(total_mark)) as total_mark,coalesce(sum(total_grade_weight)) as total_grade_weight,sort_order FROM (
																						SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																							/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,*/
																							(CASE
																								WHEN exam_types.is_last_exam is true THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																								WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																								WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																								--ELSE
																									--round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)
																							END) as total_mark,
																							/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																							(CASE
																								WHEN exam_types.is_last_exam is true THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																								WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																								WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																								--ELSE
																									--round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)
																							END) as total_grade_weight,
																							subjects.sort_order, is_last_exam
																						FROM app.exam_marks
																						INNER JOIN app.class_subject_exams
																						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																						INNER JOIN app.class_subjects
																						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																						WHERE class_subjects.class_id = :classId
																						AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																						GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, exam_types.is_last_exam
																						ORDER BY sort_order ASC
																					)a
																					GROUP BY class_id,subject_id,subject_name,student_id,sort_order
																					ORDER BY sort_order ASC
																				) q ORDER BY sort_order
																				)v ORDER BY sort_order
																			)r GROUP BY student_id) AS percentages
																			FULL OUTER JOIN
																			(SELECT avg AS current_term_marks, avg_out_of AS current_term_marks_out_of, student_id, position AS rank FROM (
																				SELECT avg, avg_out_of, student_id, rank() over(order by avg desc)  as position FROM (
																					SELECT round(sum(((total_mark)::float/(total_grade_weight)::float)*100)) AS avg, sum(total_grade_weight) AS avg_out_of, student_id FROM (
																						SELECT  sum(total_mark) as total_mark, avg(total_grade_weight) as total_grade_weight, student_id, subject_id
																						FROM (
																							SELECT class_id, class_subjects.subject_id, subject_name, exam_marks.student_id,
																								coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																								sum(grade_weight) as total_grade_weight,
																								/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																								subjects.sort_order, class_subject_exams.exam_type_id
																							FROM app.exam_marks
																							INNER JOIN app.class_subject_exams
																							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																							INNER JOIN app.class_subjects
																							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																							WHERE class_subjects.class_id = :classId
																							AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND mark IS NOT NULL
																							GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,
																								exam_types.is_last_exam,subjects.parent_subject_id,exam_marks.mark,class_subject_exams.grade_weight
																						) q GROUP BY student_id, subject_id ORDER BY student_id
																					) AS foo GROUP BY student_id ORDER BY avg DESC
																				) AS FOO2
																			) AS foo3 WHERE student_id= :studentId
																			) AS positions
																			ON percentages.student_id = positions.student_id
																			ON marks.student_id = percentages.student_id
																			) AS foo5");
		$sth3ByAverage->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overallByAverage = $sth3ByAverage->fetch(PDO::FETCH_OBJ);

		// get overall position last term
		$sth4 = $db->prepare("SELECT total_mark, total_grade_weight, rank, round((current_term_marks::float/current_term_marks_out_of::float)*100) as percentage, (select grade from app.grading where round((current_term_marks::float/current_term_marks_out_of::float)*100) >= min_mark and  round((current_term_marks::float/current_term_marks_out_of::float)*100) <= max_mark) as grade, principal_comment, position_out_of, current_term_marks as last_term_marks, current_term_marks_out_of as last_term_marks_out_of FROM (
														SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, percentages.principal_comment, marks.position_out_of, positions.current_term_marks,
															(case
																WHEN positions.current_term_marks_out_of < 800 THEN
																	800
																WHEN positions.current_term_marks_out_of = 800 THEN
																	800
																WHEN positions.current_term_marks_out_of > 800 THEN
																	1200
															end) as current_term_marks_out_of
														FROM
															(SELECT student_id, total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage, (select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade, position_out_of FROM (
																SELECT student_id, total_mark, total_grade_weight,
																	round((SELECT trunc(cast(avg(a.percentage) as numeric),2) AS percentage FROM (
																			SELECT  subject_name, avg(round(total_mark::float/total_grade_weight::float*100)) as percentage FROM (
																				SELECT subject_name, coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																					coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight, subjects.sort_order
																				FROM app.exam_marks
																				INNER JOIN app.class_subject_exams
																				INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																				INNER JOIN app.class_subjects
																				INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																							ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																							ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																				WHERE class_subjects.class_id = :classId
																				AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																				GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
																			) q
																			GROUP BY q.sort_order,q.subject_name
																			ORDER BY sort_order
																		) a
																	)) as percentage,
																	dense_rank() over w as rank, position_out_of,
																	/*commented by tom for a quick hack, remember to remove*/
																	(SELECT COUNT(*) FROM (
																				SELECT DISTINCT exam_type_id FROM app.exam_marks
																				INNER JOIN app.class_subject_exams
																				INNER JOIN app.class_subjects ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																				WHERE student_id = a.student_id
																				AND class_subjects.class_id = :classId AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																			) AS temp
																	), 1 as num_exam_types
																FROM (
																	SELECT exam_marks.student_id,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																		(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																				ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																				ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students ON exam_marks.student_id = students.student_id
																	WHERE class_subjects.class_id = :classId
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.active is true AND mark IS NOT NULL
																	/*hack by tom, remember to remove*/
																	AND class_subject_exams.exam_type_id = (SELECT  exam_type_id FROM app.exam_types WHERE exam_type_id=(select distinct exam_type_id from app.class_subject_exams cse inner join app.exam_marks em on cse.class_sub_exam_id=em.class_sub_exam_id where em.student_id=:studentId order by exam_type_id DESC LIMIT 1)) GROUP BY exam_marks.student_id
																) a WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
															) q WHERE student_id = :studentId) AS marks
															FULL OUTER JOIN
															(SELECT student_id, round(avg(percentage)) AS percentage, (SELECT grade FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS grade, (SELECT principal_comment FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS principal_comment FROM (
																SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM(
																	SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
																		SELECT class_id,subject_id,subject_name,student_id,coalesce(sum(total_mark)) as total_mark,coalesce(sum(total_grade_weight)) as total_grade_weight,sort_order FROM (
																			SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																				/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,*/
																				(CASE
																					WHEN exam_types.is_last_exam is true THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																					WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																					WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																					--ELSE
																						--round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)
																				END) as total_mark,
																				/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																				(CASE
																					WHEN exam_types.is_last_exam is true THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																					WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																					WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																					--ELSE
																						--round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)
																				END) as total_grade_weight,
																				subjects.sort_order, is_last_exam
																			FROM app.exam_marks
																			INNER JOIN app.class_subject_exams
																			INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																			INNER JOIN app.class_subjects
																			INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																			WHERE class_subjects.class_id = :classId
																			AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																			GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, exam_types.is_last_exam
																			ORDER BY sort_order ASC
																		)a
																		GROUP BY class_id,subject_id,subject_name,student_id,sort_order
																		ORDER BY sort_order ASC
																	) q ORDER BY sort_order
																)v ORDER BY sort_order
															)r GROUP BY student_id) AS percentages
															FULL OUTER JOIN
															(SELECT student_id, avg AS current_term_marks, avg_out_of AS current_term_marks_out_of, position AS rank FROM (
																SELECT student_id, avg, avg_out_of, rank() over(order by points desc)  as position FROM (
																	SELECT student_id, total_mark AS avg, floor((total_grade_weight + 99)/100)*100 as avg_out_of, points
																	FROM (
																		SELECT  student_id, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, avg(points) as points
																		FROM (
																			SELECT class_id, subject_id, subject_name, student_id, tm2 as total_mark, tgw2 as total_grade_weight, points FROM (
																				SELECT class_id, subject_id, subject_name, student_id, sum(total_mark2) as tm2, sum(total_grade_weight2) as tgw2, (SELECT points FROM app.grading WHERE round((sum(total_mark2)::float/sum(total_grade_weight2))*100) between min_mark and max_mark) AS points FROM (
																					SELECT class_id, class_subjects.subject_id, subject_name, exam_marks.student_id,
																						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																						(CASE
																							WHEN exam_types.is_last_exam is true THEN
																								/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*/
																								round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.7)

																							WHEN exam_types.is_last_exam is false and (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) LIMIT 1) is not null THEN
																								round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																							WHEN not exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) LIMIT 1) THEN
																								coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																						END) as total_mark2,
																						/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																						(CASE
																							WHEN exam_types.is_last_exam is true THEN
																								/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*/
																								round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.7)

																							WHEN exam_types.is_last_exam is false and (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) LIMIT 1) is not null THEN
																								round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																							WHEN not exists (select et.is_last_exam from app.exam_types et inner join app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id inner join app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId) AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) LIMIT 1) THEN
																								coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																						END) as total_grade_weight2,
																						subjects.sort_order, class_subject_exams.exam_type_id
																					FROM app.exam_marks
																					INNER JOIN app.class_subject_exams
																					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																					INNER JOIN app.class_subjects
																					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																					WHERE class_subjects.class_id = :classId
																					AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND mark IS NOT NULL
																					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,
																						exam_types.is_last_exam,subjects.parent_subject_id,exam_marks.mark,class_subject_exams.grade_weight
																				)middle1
																				GROUP BY class_id, subject_id, subject_name, student_id
																		)middle
																		) q GROUP BY student_id ORDER BY points DESC
																	) AS foo ORDER BY points DESC
																) AS FOO2
																) AS foo3 WHERE student_id= :studentId
															) AS positions
															ON percentages.student_id = positions.student_id
															ON marks.student_id = percentages.student_id
														) AS foo4");
		$sth4->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overallLastTerm = $sth4->fetch(PDO::FETCH_OBJ);

		// get overall position last term by average
		$sth4ByAverage = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage, (SELECT grade FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS grade, principal_comment,position_out_of,current_term_marks,current_term_marks_out_of FROM(
																			SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, percentages.principal_comment, marks.position_out_of,
																				percentages.total_marks_percent as current_term_marks,
																				(case
																					WHEN positions.current_term_marks_out_of between 0 and 700 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) > 13 THEN
																						700
																					WHEN positions.current_term_marks_out_of between 701 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) > 13 THEN
																						800
																					WHEN (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) < 14 THEN
																						1200
																					--WHEN positions.current_term_marks_out_of = 800 THEN
																						--800
																					--WHEN positions.current_term_marks_out_of > 800 THEN
																						--1200
																				end) as current_term_marks_out_of
																			FROM
																			(SELECT student_id, total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage, (select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade, position_out_of FROM (
																				SELECT student_id, total_mark, total_grade_weight,
																					round((SELECT trunc(cast(avg(a.percentage) as numeric),2) AS percentage FROM (
																						SELECT  subject_name, avg(round(total_mark::float/total_grade_weight::float*100)) as percentage FROM (
																							SELECT subject_name, coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																								coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight, subjects.sort_order
																							FROM app.exam_marks
																							INNER JOIN app.class_subject_exams
																							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																							INNER JOIN app.class_subjects
																							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																							WHERE class_subjects.class_id = :classId
																							AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																							GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
																						) q
																						GROUP BY q.sort_order,q.subject_name
																						ORDER BY sort_order
																						) a
																					)) as percentage,
																					dense_rank() over w as rank, position_out_of,
																					/*commented by tom for a quick hack, remember to remove*/
																					(SELECT COUNT(*) FROM (
																						SELECT DISTINCT exam_type_id FROM app.exam_marks
																						INNER JOIN app.class_subject_exams
																						INNER JOIN app.class_subjects ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																						WHERE student_id = a.student_id
																						AND class_subjects.class_id = :classId AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																					) AS temp
																					), 1 as num_exam_types
																				FROM (
																					SELECT exam_marks.student_id,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																						(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
																					FROM app.exam_marks
																					INNER JOIN app.class_subject_exams
																					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																					INNER JOIN app.class_subjects
																					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																					INNER JOIN app.students ON exam_marks.student_id = students.student_id
																					WHERE class_subjects.class_id = :classId
																					AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.active is true AND mark IS NOT NULL
																					/*hack by tom, remember to remove*/
																					AND class_subject_exams.exam_type_id = (SELECT  exam_type_id FROM app.exam_types WHERE exam_type_id=(select distinct exam_type_id from app.class_subject_exams cse inner join app.exam_marks em on cse.class_sub_exam_id=em.class_sub_exam_id where em.student_id=:studentId and em.term_id=(select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) order by exam_type_id DESC LIMIT 1)) GROUP BY exam_marks.student_id
																				) a WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
																			) q WHERE student_id = :studentId) AS marks
																			FULL OUTER JOIN
																			(SELECT student_id, round(avg(percentage)) AS percentage, sum(percentage) as total_marks_percent, (SELECT grade FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS grade, (SELECT principal_comment FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS principal_comment FROM (
																			SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM(
																				SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
																					SELECT class_id,subject_id,subject_name,student_id,coalesce(sum(total_mark)) as total_mark,coalesce(sum(total_grade_weight)) as total_grade_weight,sort_order FROM (
																						SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																							/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,*/
																							(CASE
																								WHEN exam_types.is_last_exam is true THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																								WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																								WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																								--ELSE
																									--round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)
																							END) as total_mark,
																							/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																							(CASE
																								WHEN exam_types.is_last_exam is true THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																								WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																								WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.classes where class_id= :classId)) THEN
																									coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																								--ELSE
																									--round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)
																							END) as total_grade_weight,
																							subjects.sort_order, is_last_exam
																						FROM app.exam_marks
																						INNER JOIN app.class_subject_exams
																						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																						INNER JOIN app.class_subjects
																						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																						WHERE class_subjects.class_id = :classId
																						AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																						GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, exam_types.is_last_exam
																						ORDER BY sort_order ASC
																					)a
																					GROUP BY class_id,subject_id,subject_name,student_id,sort_order
																					ORDER BY sort_order ASC
																				) q ORDER BY sort_order
																				)v ORDER BY sort_order
																			)r GROUP BY student_id) AS percentages
																			FULL OUTER JOIN
																			(SELECT avg AS current_term_marks, avg_out_of AS current_term_marks_out_of, student_id, position AS rank FROM (
																				SELECT avg, avg_out_of, student_id, rank() over(order by avg desc)  as position FROM (
																					SELECT round(sum(((total_mark)::float/(total_grade_weight)::float)*100)) AS avg, sum(total_grade_weight) AS avg_out_of, student_id FROM (
																						SELECT  sum(total_mark) as total_mark, avg(total_grade_weight) as total_grade_weight, student_id, subject_id
																						FROM (
																							SELECT class_id, class_subjects.subject_id, subject_name, exam_marks.student_id,
																								coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																								sum(grade_weight) as total_grade_weight,
																								/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																								subjects.sort_order, class_subject_exams.exam_type_id
																							FROM app.exam_marks
																							INNER JOIN app.class_subject_exams
																							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																							INNER JOIN app.class_subjects
																							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																							WHERE class_subjects.class_id = :classId
																							AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND mark IS NOT NULL
																							GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,
																								exam_types.is_last_exam,subjects.parent_subject_id,exam_marks.mark,class_subject_exams.grade_weight
																						) q GROUP BY student_id, subject_id ORDER BY student_id
																					) AS foo GROUP BY student_id ORDER BY avg DESC
																				) AS FOO2
																			) AS foo3 WHERE student_id= :studentId
																			) AS positions
																			ON percentages.student_id = positions.student_id
																			ON marks.student_id = percentages.student_id
																			) AS foo5");
		$sth4ByAverage->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overallLastTermByAverage = $sth4ByAverage->fetch(PDO::FETCH_OBJ);

		// get average grade per term per to plot a graph for the year
		// $sth5 = $db->prepare("SELECT * FROM
		// 												(
		// 													SELECT  ceil( avg((em.mark::decimal/cse.grade_weight::decimal) * 100)) AS average_grade, t.term_name
		// 												FROM app.exam_marks  em
		// 												LEFT JOIN app.class_subject_exams cse USING (class_sub_exam_id)
		// 												LEFT JOIN app.terms t USING (term_id)
		// 												LEFT JOIN app.class_subjects cs USING (class_subject_id)
		// 												LEFT JOIN app.subjects s USING(subject_id)
		// 												WHERE em.student_id = :studentId
		// 												GROUP BY term_name,t.creation_date
		// 												ORDER BY t.creation_date DESC
		// 												) foo LIMIT 3");
		// $sth5->execute(  array(':studentId' => $studentId) );
		// $graphPoints = $sth5->fetchAll(PDO::FETCH_OBJ);

		//get average grade per CAT to plot a graph for the term
		$sth5 = $db->prepare("SELECT * FROM
															(
																SELECT  round( avg((em.mark::decimal/cse.grade_weight::decimal) * 100)) AS average_grade, et.exam_type
																FROM app.exam_marks  em
																LEFT JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																LEFT JOIN app.exam_types et USING (exam_type_id)
																LEFT JOIN app.class_subjects cs USING (class_subject_id)
																LEFT JOIN app.subjects s USING(subject_id)
																WHERE em.student_id= :studentId
																AND em.term_id = :termId
																GROUP BY exam_type,et.sort_order
																ORDER BY et.sort_order ASC
															) foo LIMIT 4");
		$sth5->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$graphPoints = $sth5->fetchAll(PDO::FETCH_OBJ);

		// current class position of student
		$sth6 = $db->prepare("SELECT position FROM (
              SELECT avg, student_id, rank() over(order by avg desc)  as position FROM (
               SELECT sum(total_mark)/count(*) AS avg, student_id FROM (
                SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
                 (SELECT grade FROM app.grading WHERE (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) AS grade,
                 sort_order, exam_type_id, student_id
                FROM (
                 SELECT class_id, class_subjects.subject_id, subject_name, exam_marks.student_id,
                  coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                  coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                  subjects.sort_order, class_subject_exams.exam_type_id
                 FROM app.exam_marks
                 INNER JOIN app.class_subject_exams
                 INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                 INNER JOIN app.class_subjects
                 INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                    ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                    WHERE class_subjects.class_id = :classId
                    AND term_id = :termId
                    AND subjects.parent_subject_id is null
                    AND subjects.use_for_grading is true
                    AND mark IS NOT NULL
                 GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
                ) q ORDER BY sort_order
               ) AS foo GROUP BY student_id ORDER BY avg DESC
              ) AS FOO2
             ) AS foo3 WHERE student_id= :studentId");
		$sth6->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$currentClassPosition = $sth6->fetchAll(PDO::FETCH_OBJ);

		// $sth7 = $db->prepare("	SELECT * FROM (
		// 						              SELECT avg, student_id, first_name, last_name, class_name, rank() over(order by avg desc) AS position,
		// 						              (SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = 15 AND students.active is true) AS position_out_of FROM (
		// 						               SELECT sum(total_mark) AS avg, student_id, first_name, last_name, class_name FROM (
		// 						                SELECT  subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
		// 						                 (SELECT grade FROM app.grading WHERE (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) AS grade,
		// 						                 sort_order, exam_type_id, student_id, first_name, last_name, class_name
		// 						                FROM (
		// 						                 SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name, students.last_name, classes.class_name,
		// 						                  coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
		// 						                  coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
		// 						                  subjects.sort_order, class_subject_exams.exam_type_id
		// 						                 FROM app.exam_marks
		// 						                 INNER JOIN app.students ON exam_marks.student_id = students.student_id
		// 						                 INNER JOIN app.class_subject_exams
		// 						                 INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
		// 						                 INNER JOIN app.class_subjects
		// 						                 INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
		// 						                 INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
		// 						                 INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
		// 						                    ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
		// 						                    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
		// 						                    WHERE class_cats.entity_id = :entityId
		// 						                    AND term_id = :termId
		// 						                    AND subjects.parent_subject_id is null
		// 						                    AND subjects.use_for_grading is true
		// 						                    AND mark IS NOT NULL
		// 						                 GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.last_name
		// 						                ) q ORDER BY sort_order
		// 						               ) AS foo GROUP BY student_id,first_name, last_name, class_name ORDER BY avg DESC
		// 						              ) AS FOO2
		// 						             ) AS foo3 WHERE student_id = :studentId");
		// $sth7->execute(  array(':studentId' => $studentId, ':termId' => $termId, ':entityId' => $entityId) );
		// $streamRank = $sth7->fetchAll(PDO::FETCH_OBJ);

		//get the last exam done by the student within a term
		$sth8 = $db->prepare("SELECT et.exam_type, et.is_last_exam FROM app.exam_types et
													INNER JOIN app.class_subject_exams cse ON cse.exam_type_id = et.exam_type_id
													INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id
													WHERE em.student_id = :studentId AND em.term_id = :termId ORDER BY cse.exam_type_id DESC LIMIT 1");
		$sth8->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$latestExamType = $sth8->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->details = $details;
		$results->subjectOverall = $subjectOverall;
		$results->subjectOverallBySum = $subjectOverallBySum;
		$results->subjectOverallByAvg = $subjectOverallByAvg;
		$results->overall = $overall;
		$results->overallByAverage = $overallByAverage;
		$results->overallLastTerm = $overallLastTerm;
		$results->graphPoints = $graphPoints;
		$results->currentClassPosition = $currentClassPosition;
		$results->latestExamType = $latestExamType;
		// $results->streamRank = $streamRank;

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

$app->get('/getStreamPosition/:student_id/:entityId/:termId', function ($studentId,$entityId,$termId) {
	//Get stream position

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();

		// get exam marks by exam type
		$params = array(':studentId' => $studentId, ':termId' => $termId, ':entityId' => $entityId);

		// stream positions using points
		$sth7 = $db->prepare("SELECT * FROM (
														SELECT student_id, student_name, avg, class_name, rank() over(order by avg desc) AS position,
															(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = :entityId AND students.active is true) AS position_out_of
														FROM (
															SELECT avg(points) AS avg, student_id, student_name, class_name
															FROM (
																SELECT  student_id, student_name, class_id, class_name, subject_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, (SELECT points FROM app.grading WHERE round((sum(total_mark)::float/sum(total_grade_weight))*100) between min_mark and max_mark) AS points
																FROM (
																	SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
																		--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																		--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																		(CASE
																			WHEN exam_types.is_last_exam is true THEN
																				/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*/
																				round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.7)

																			WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																			WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																		END) as total_mark,
																		/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																		(CASE
																			WHEN exam_types.is_last_exam is true THEN
																				/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*/
																				round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.7)

																			WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																			WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																		END) as total_grade_weight,
																		subjects.sort_order, class_subject_exams.exam_type_id
																	FROM app.exam_marks
																	INNER JOIN app.students ON exam_marks.student_id = students.student_id
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																	INNER JOIN app.class_subjects
																	INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
																	INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
																	INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																				ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																				ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	WHERE class_cats.entity_id = :entityId
																	AND term_id = :termId
																	AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.student_id = exam_marks.student_id AND mark IS NOT NULL AND students.active is true
																	GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
																		use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name, exam_types.is_last_exam
																) q GROUP BY student_id, student_name, class_id, class_name, subject_id, subject_name ORDER BY student_id ASC
															) AS foo GROUP BY student_id,student_name, class_name ORDER BY avg DESC
														) AS FOO2
													) AS foo3 WHERE student_id = :studentId");
		$sth7->execute(  array(':studentId' => $studentId, ':termId' => $termId, ':entityId' => $entityId) );
		$streamRank = $sth7->fetchAll(PDO::FETCH_OBJ);

		// stream positions using marks
		$sth7ByMarks = $db->prepare("SELECT * FROM (
														SELECT student_id, student_name, avg, class_name, rank() over(order by avg desc) AS position,
															(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = :entityId AND students.active is true) AS position_out_of
														FROM (
															SELECT student_id, student_name, class_name, round(avg(total_mark)) as avg, round(avg(total_grade_weight)) as total_grade_weight FROM (
																SELECT sum(total_mark) AS total_mark, sum(total_grade_weight) AS total_grade_weight, student_id, student_name, class_name, exam_type_id
																FROM (
																	SELECT  student_id, student_name, class_id, class_name, subject_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, exam_type_id
																	FROM (
																		SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
																			coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																			coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																			subjects.sort_order, class_subject_exams.exam_type_id
																		FROM app.exam_marks
																		INNER JOIN app.students ON exam_marks.student_id = students.student_id
																		INNER JOIN app.class_subject_exams
																		INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																		INNER JOIN app.class_subjects
																		INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
																		INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
																		INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																					ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																					ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																		WHERE class_cats.entity_id = :entityId
																		AND term_id = :termId
																		AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.student_id = exam_marks.student_id AND mark IS NOT NULL
																		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
																			use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name, exam_types.is_last_exam
																		ORDER BY exam_marks.student_id ASC
																	) q GROUP BY student_id, student_name, class_id, class_name, subject_id, subject_name, exam_type_id ORDER BY student_id ASC
																) AS foo GROUP BY student_id,student_name, class_name, exam_type_id ORDER BY student_id ASC, total_mark DESC
															) AS FOO2 GROUP BY student_id, student_name, class_name
														) AS foo3
													) AS foo3 WHERE student_id = :studentId

													");
		$sth7ByMarks->execute(  array(':studentId' => $studentId, ':termId' => $termId, ':entityId' => $entityId) );
		$streamRankByMarks = $sth7ByMarks->fetchAll(PDO::FETCH_OBJ);

		// stream positions last term using points
		$sth7LstTm = $db->prepare("SELECT * FROM (
														SELECT student_id, student_name, avg, class_name, rank() over(order by avg desc) AS position,
															(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = :entityId AND students.active is true) AS position_out_of
														FROM (
															SELECT avg(points) AS avg, student_id, student_name, class_name
															FROM (
																SELECT  student_id, student_name, class_id, class_name, subject_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, (SELECT points FROM app.grading WHERE round((sum(total_mark)::float/sum(total_grade_weight))*100) between min_mark and max_mark) AS points
																FROM (
																	SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
																		--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																		--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																		(CASE
																			WHEN exam_types.is_last_exam is true THEN
																				/*coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*/
																				round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.7)

																			WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

																			WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)

																		END) as total_mark,
																		/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																		(CASE
																			WHEN exam_types.is_last_exam is true THEN
																				/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*/
																				round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.7)

																			WHEN exam_types.is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				round (coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)*0.3)

																			WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam = 'TRUE' AND class_cat_id=(select class_cat_id from app.class_cats where entity_id= :entityId limit 1)) THEN
																				coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

																		END) as total_grade_weight,
																		subjects.sort_order, class_subject_exams.exam_type_id
																	FROM app.exam_marks
																	INNER JOIN app.students ON exam_marks.student_id = students.student_id
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																	INNER JOIN app.class_subjects
																	INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
																	INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
																	INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																				ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																				ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	WHERE class_cats.entity_id = :entityId
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.student_id = exam_marks.student_id AND mark IS NOT NULL
																	GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
																		use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name, exam_types.is_last_exam
																) q GROUP BY student_id, student_name, class_id, class_name, subject_id, subject_name ORDER BY student_id ASC
															) AS foo GROUP BY student_id,student_name, class_name ORDER BY avg DESC
														) AS FOO2
													) AS foo3 WHERE student_id = :studentId");
		$sth7LstTm->execute(  array(':studentId' => $studentId, ':termId' => $termId, ':entityId' => $entityId) );
		$streamRankLastTerm = $sth7LstTm->fetchAll(PDO::FETCH_OBJ);

		// stream positions last term using marks
		$sth7LstTmByMarks = $db->prepare("SELECT * FROM (
														SELECT student_id, student_name, avg, class_name, rank() over(order by avg desc) AS position,
															(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = :entityId AND students.active is true) AS position_out_of
														FROM (
															SELECT student_id, student_name, class_name, round(avg(avg)) as avg, round(avg(total_grade_weight)) as total_grade_weight FROM (
																SELECT sum(total_mark) AS avg, sum(total_grade_weight) as total_grade_weight, student_id, student_name, class_name, exam_type_id
																FROM (
																	SELECT  student_id, student_name, class_id, class_name, subject_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, exam_type_id
																	FROM (
																		SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
																			coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																			coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																			subjects.sort_order, class_subject_exams.exam_type_id
																		FROM app.exam_marks
																		INNER JOIN app.students ON exam_marks.student_id = students.student_id
																		INNER JOIN app.class_subject_exams
																		INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																		INNER JOIN app.class_subjects
																		INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
																		INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
																		INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																					ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																					ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																		WHERE class_cats.entity_id = :entityId
																		AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.student_id = exam_marks.student_id AND mark IS NOT NULL
																		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
																			use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name, exam_types.is_last_exam
																	) q GROUP BY student_id, student_name, class_id, class_name, subject_id, subject_name, exam_type_id ORDER BY student_id ASC
																) AS foo GROUP BY student_id,student_name, class_name, exam_type_id ORDER BY student_id ASC
															)foo4 GROUP BY student_id, student_name, class_name order by avg DESC
														) AS FOO2
													) AS foo3 WHERE student_id = :studentId");
		$sth7LstTmByMarks->execute(  array(':studentId' => $studentId, ':termId' => $termId, ':entityId' => $entityId) );
		$streamRankLastTermByMarks = $sth7LstTmByMarks->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->streamRank = $streamRank;
		$results->streamRankByMarks = $streamRankByMarks;
		$results->streamRankLastTerm = $streamRankLastTerm;
		$results->streamRankLastTermByMarks = $streamRankLastTermByMarks;

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

$app->post('/addReportCard', function () use($app) {
  // Add report card

	$allPostVars = json_decode($app->request()->getBody(),true);

	$studentId =		( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$kcpeMarks =		( isset($allPostVars['kcpe_marks']) ? $allPostVars['kcpe_marks']: null);
	$schoolHse =		( isset($allPostVars['school_house']) ? $allPostVars['school_house']: null);
	$termId =			( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$classId =			( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$reportCardType =	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$reportData =		( isset($allPostVars['report_data']) ? $allPostVars['report_data']: null);
	$jsonData  = 	( isset($allPostVars['json_data']) ? $allPostVars['json_data']: null);
	$published =		( isset($allPostVars['published']) ? $allPostVars['published']: 'f');

  try
  {
    $db = getDB();

		$getReport = $db->prepare("SELECT report_card_id FROM app.report_cards WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId");

		$addReport = $db->prepare("INSERT INTO app.report_cards(student_id, class_id, term_id, report_data, created_by, report_card_type, teacher_id, published, kcpe_marks, school_house, json_data)
								VALUES(:studentId, :classId, :termId, :reportData, :userId, :reportCardType, :teacherId, :published, :kcpe_marks, :school_house, :json_data)");

		$updateReport = $db->prepare("UPDATE app.report_cards
									SET report_data = :reportData,
											published = :published,
										modified_date = now(),
										modified_by = :userId
									WHERE report_card_id = :reportCardId");


		$db->beginTransaction();

		$getReport->execute( array(':studentId' => $studentId, ':classId' => $classId,':termId' => $termId ) );
		$reportCardId = $getReport->fetch(PDO::FETCH_OBJ);

		if( $reportCardId )
		{
			$updateReport->execute( array(':reportData' => $reportData, ':userId' => $userId, ':reportCardId' => $reportCardId->report_card_id, ':published' => $published ) );
		}
		else
		{
			$addReport->execute( array(':studentId' => $studentId,
											':classId' => $classId,
											':reportCardType' => $reportCardType,
											':termId' => $termId,
											':reportData' => $reportData,
											':teacherId' => $teacherId,
											':userId' => $userId,
											':published' => $published,
											':kcpe_marks' => $kcpeMarks,
											':school_house' => $schoolHse,
											':json_data' => $jsonData
											) );
		}
    $db->commit();
    if( $published )
    {
      // report card was published, need to add entry for notifications
      // get student name
      $studentName = $db->prepare("SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name
                            FROM app.students
                            WHERE student_id = :studentId");
      $studentName->execute(array(':studentId' => $studentId));
      $nameResult = $studentName->fetch(PDO::FETCH_OBJ);

      $db = null;

      // blog was published, need to add entry for notifications
      $db = getMISDB();
      $subdomain = getSubDomain();
      $message = "New report card for " . $nameResult->student_name . '!';

      // get all device ids
      $getDeviceIds = $db->prepare("SELECT device_user_id
                                    FROM parents
                                    INNER JOIN parent_students
                                    ON parents.parent_id = parent_students.parent_id
                                    WHERE subdomain = :subdomain
                                    AND student_id = :studentId");
      $getDeviceIds->execute( array(':studentId' => $studentId, ':subdomain' => $subdomain) );
      $results = $getDeviceIds->fetchAll(PDO::FETCH_OBJ);

      $deviceIds = array();
      foreach($results as $result) {
        $id = $result->device_user_id;
        if( !empty($id) && !in_array($id, $deviceIds) ) $deviceIds[] = $id;
      }

      if( count($deviceIds) > 0 ) {
        $deviceIdStr = '{' . implode(',',$deviceIds) . '}';

        $add = $db->prepare("INSERT INTO notifications(subdomain, device_user_ids, message)
                             VALUES(:subdomain, :deviceIds, :message)");
        $add->execute(
          array(
            ':subdomain' => $subdomain,
            ':deviceIds' => $deviceIdStr,
            ':message' => $message
          )
        );
      }
    }


		$app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;

    }
    catch(PDOException $e) {
      $app->response()->setStatus(404);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->delete('/deleteReportCard/:report_card_id', function ($reportCardId) {
    // delete report card

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.report_cards WHERE report_card_id = :reportCardId");

		$sth->execute( array(':reportCardId' => $reportCardId) );

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
