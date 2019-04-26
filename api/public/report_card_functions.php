<?php
$app->get('/getAllStudentReportCards/:class_id', function ($classId) {
    //Get report cards for class

	$app = \Slim\Slim::getInstance();

    try
    {

		$db = getDB();

		$sth = $db->prepare("SELECT report_cards.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
									admission_number, report_cards.class_id, class_cat_id,
									class_name, report_cards.term_id, term_name, date_part('year', start_date) as year, report_data, report_cards.report_card_type,
									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
									report_cards.creation_date::date as date, published
							FROM app.report_cards
							INNER JOIN app.students ON report_cards.student_id = students.student_id
							INNER JOIN app.classes ON report_cards.class_id = classes.class_id
							INNER JOIN app.terms ON report_cards.term_id = terms.term_id
							LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
							WHERE report_cards.class_id IN (SELECT class_id FROM app.student_class_history WHERE student_id IN (SELECT student_id FROM app.students WHERE current_class = :classId))
							AND students.active IS TRUE
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
									date_part('year', start_date) as year, report_data, report_cards.report_card_type, class_cat_id,
									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
									report_cards.creation_date::date as date, published
					FROM app.report_cards
					INNER JOIN app.students ON report_cards.student_id = students.student_id
					INNER JOIN app.classes ON report_cards.class_id = classes.class_id
					INNER JOIN app.terms ON report_cards.term_id = terms.term_id
					LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
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

		$sth = $db->prepare("SELECT report_card_id, report_cards.student_id, class_name, classes.class_id, term_name, report_cards.term_id,
									date_part('year', start_date) as year, report_data, report_cards.report_card_type,
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
		$query = "SELECT mark,
								grade_weight,
								exam_type,
								(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
								subject_name,
								subjects.teacher_id,
								employees.initials,
								use_for_grading
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
						INNER JOIN app.subjects
						LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
								                ON class_subjects.subject_id = subjects.subject_id
						INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
							                    ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						                        ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId AND term_id = :termId AND student_id = :studentId AND mark IS NOT NULL
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
		$sth2 = $db->prepare("SELECT  subject_name,
									total_mark,
									total_grade_weight,
									ceil(total_mark::float/total_grade_weight::float*100) as percentage,
									(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
									sort_order
							FROM (
									SELECT class_id
												,class_subjects.subject_id
												,subject_name
												,exam_marks.student_id
												,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												,coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight
											,subjects.sort_order
									FROM app.exam_marks
									INNER JOIN app.class_subject_exams
										INNER JOIN app.exam_types
										ON class_subject_exams.exam_type_id = exam_types.exam_type_id
										INNER JOIN app.class_subjects
											INNER JOIN app.subjects
											ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
									WHERE class_subjects.class_id = :classId
									AND term_id = :termId
									AND subjects.parent_subject_id is null
									AND subjects.use_for_grading is true
									AND student_id = :studentId
									AND mark IS NOT NULL
									GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading
								) q
							 ORDER BY sort_order  ");
		$sth2->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$subjectOverall = $sth2->fetchAll(PDO::FETCH_OBJ);
		
		// get overall marks (determined by average of exams done)
		$sth2ByAvg = $db->prepare("SELECT subject_name, total_mark, total_grade_weight, percentage, grade, sort_order, grade as overall_grade2
																	FROM(
																		SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
																			(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
																		FROM (
																			SELECT class_id,subject_id,subject_name,student_id,total_mark,total_grade_weight,sort_order FROM (
																				SELECT class_id,subject_id,subject_name,student_id,
																					round(sum(total_mark)::float/count) as total_mark,
																					round(avg(total_grade_weight)) as total_grade_weight,sort_order
																				FROM
																					(
																						SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																							mark as total_mark,
																							grade_weight as total_grade_weight,subjects.sort_order,
																							(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = :classId AND term_id = :termId) as count
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
																					GROUP BY class_id, subject_name, student_id, subject_id, sort_order, count
																					ORDER BY sort_order ASC
																			)a
																			ORDER BY sort_order ASC
																		) q
																		ORDER BY sort_order
																	)v ORDER BY sort_order");
		$sth2ByAvg->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$subjectOverallByAvg = $sth2ByAvg->fetchAll(PDO::FETCH_OBJ);

		// get overall position
		$sth3 = $db->prepare("SELECT total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight,
										ceil(total_mark::float/total_grade_weight::float*100) as percentage,
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
												) AS temp)*/ 1 as num_exam_types
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

										AND class_subject_exams.exam_type_id = (SELECT exam_type_id FROM (
                                                                                SELECT DISTINCT ON (cse.exam_type_id, et.exam_type, em.student_id,  et.sort_order ) cse.exam_type_id, et.exam_type, em.student_id,  et.sort_order FROM app.exam_marks em
                                                                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                                                INNER JOIN app.exam_types et USING (exam_type_id)
                                                                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                                                WHERE em.term_id = :termId AND cs.class_id = :classId AND em.student_id = :studentId
                                                                                ORDER BY et.sort_order DESC
                                                                                )one ORDER BY sort_order DESC LIMIT 1)

										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
								 ) q
								 where student_id = :studentId");
		$sth3->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overall = $sth3->fetch(PDO::FETCH_OBJ);
		
		// get overall position (exactly same as above) but current term marks by the average
		$sth3ByAverage = $db->prepare("SELECT total_mark, total_grade_weight, rank, round((current_term_marks::float/current_term_marks_out_of::float)*100) AS percentage, (SELECT grade FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS grade, position_out_of, current_term_marks, current_term_marks_out_of FROM(
																			SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, marks.position_out_of,
																				positions.current_term_marks as current_term_marks,
																				(case
																					WHEN positions.current_term_marks_out_of between 0 and 500 THEN
																						500
																					WHEN positions.current_term_marks_out_of between 501 and 600 THEN
																						600
																					WHEN positions.current_term_marks_out_of between 601 and 700 THEN
																						700
																					WHEN positions.current_term_marks_out_of between 701 and 800 THEN
																						800
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
																			SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM (
																				SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
																					SELECT class_id,subject_id,subject_name,student_id,coalesce(round(sum(total_mark)::float/count)) as total_mark,coalesce(round(avg(total_grade_weight))) as total_grade_weight,sort_order FROM (
																						SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
																							coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																							coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																							subjects.sort_order,
																							(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = :classId AND term_id = :termId) as count
																						FROM app.exam_marks
																						INNER JOIN app.class_subject_exams
																						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																						INNER JOIN app.class_subjects
																						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																						WHERE class_subjects.class_id = :classId
																						AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId AND mark IS NOT NULL
																						GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, count
																						ORDER BY sort_order ASC
																					)a
																					GROUP BY class_id,subject_id,subject_name,student_id,sort_order, count
																					ORDER BY sort_order ASC
																				) q ORDER BY sort_order
																				)v ORDER BY sort_order
																			)r GROUP BY student_id) AS percentages
																			FULL OUTER JOIN
																			(SELECT avg AS current_term_marks, avg_out_of AS current_term_marks_out_of, student_id, position AS rank FROM (
																				SELECT avg, avg_out_of, student_id, rank() over(order by avg desc)  as position FROM (
																					SELECT round(sum(((total_mark)::float/(total_grade_weight)::float)*100)) AS avg, sum(total_grade_weight) AS avg_out_of, student_id FROM (
																						SELECT  round(sum(total_mark)::float/count) as total_mark, round(avg(total_grade_weight)) as total_grade_weight, student_id, subject_id
																						FROM (
																							SELECT class_id, class_subjects.subject_id, subject_name, exam_marks.student_id,
																								coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																								sum(grade_weight) as total_grade_weight,
																								/*coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,*/
																								subjects.sort_order, class_subject_exams.exam_type_id,
																								(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = :classId AND term_id = :termId) as count
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
																								subjects.parent_subject_id,exam_marks.mark,class_subject_exams.grade_weight
																							ORDER BY student_id ASC
																						) q GROUP BY student_id, subject_id, count ORDER BY student_id
																					) AS foo GROUP BY student_id ORDER BY avg DESC
																				) AS FOO2
																			) AS foo3 WHERE student_id= :studentId) AS positions
																			ON percentages.student_id = positions.student_id
																			ON marks.student_id = percentages.student_id
																			) AS foo5");
		$sth3ByAverage->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overallByAverage = $sth3ByAverage->fetch(PDO::FETCH_OBJ);

		// get overall position last term
		$sth4 = $db->prepare("SELECT total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight,
																 rank, percentage,
																(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
																position_out_of
								FROM (
									SELECT student_id, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
												dense_rank() over w as rank, position_out_of,

										/*hack by tom , remember to remove*/

										/*(SELECT COUNT(*) FROM (
													SELECT DISTINCT exam_type_id
													FROM app.exam_marks
													INNER JOIN app.class_subject_exams
														INNER JOIN app.class_subjects
														ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
													ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
													WHERE student_id = a.student_id
													AND class_subjects.class_id = :classId
													AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
												) AS temp)*/ 1 as num_exam_types
									FROM (
										SELECT exam_marks.student_id, coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark
											  	,coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight
											  	,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
										FROM app.exam_marks
										INNER JOIN app.class_subject_exams
										INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
										INNER JOIN app.class_subjects
										INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
										INNER JOIN app.students ON exam_marks.student_id = students.student_id
										WHERE class_subjects.class_id = :classId
										AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
										AND subjects.parent_subject_id is null
										AND subjects.use_for_grading is true
										AND students.active is true
										AND mark IS NOT NULL
										/*hack by tom, remember to remove*/
										AND class_subject_exams.exam_type_id =(SELECT exam_type_id FROM (
                                                                                SELECT DISTINCT ON (cse.exam_type_id, et.exam_type, em.student_id,  et.sort_order ) cse.exam_type_id, et.exam_type, em.student_id,  et.sort_order FROM app.exam_marks em
                                                                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                                                INNER JOIN app.exam_types et USING (exam_type_id)
                                                                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                                                WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
                                                                                AND cs.class_id = :classId AND em.student_id = :studentId
                                                                                ORDER BY et.sort_order DESC
                                                                                )one ORDER BY sort_order DESC LIMIT 1)
										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
								 ) q
								 where student_id = :studentId");
		$sth4->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overallLastTerm = $sth4->fetch(PDO::FETCH_OBJ);

		//get average grade per CAT to plot a graph for the term
// 		$sth5 = $db->prepare("SELECT * FROM (
// 														SELECT * FROM (
// 															SELECT  em.student_id, cse.exam_type_id, round( avg((em.mark::decimal/cse.grade_weight::decimal) * 100)) AS average_grade, et.exam_type
// 															FROM app.exam_marks  em
// 															LEFT JOIN app.class_subject_exams cse USING (class_sub_exam_id)
// 															LEFT JOIN app.exam_types et USING (exam_type_id)
// 															LEFT JOIN app.class_subjects cs USING (class_subject_id)
// 															LEFT JOIN app.subjects s USING(subject_id)
// 															WHERE em.term_id = :termId
// 															GROUP BY exam_type,et.sort_order,cse.exam_type_id,em.student_id
// 															ORDER BY em.student_id DESC
// 														) foo
// 													)level1 WHERE average_grade is not null and student_id= :studentId order by exam_type_id ASC");
// 		$sth5->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
// 		$graphPoints = $sth5->fetchAll(PDO::FETCH_OBJ);

        //get average grade per CAT to plot a graph for the term
		$sth5 = $db->prepare("SELECT * FROM (
														SELECT student_id, exam_type_id, sum(average_grade) as average_grade, exam_type FROM (
															SELECT  em.student_id, cse.exam_type_id, (case when s.parent_subject_id is null and s.use_for_grading is true then mark end) AS average_grade, et.exam_type
															FROM app.exam_marks  em
															LEFT JOIN app.class_subject_exams cse USING (class_sub_exam_id)
															LEFT JOIN app.exam_types et USING (exam_type_id)
															LEFT JOIN app.class_subjects cs USING (class_subject_id)
															LEFT JOIN app.subjects s USING(subject_id)
															WHERE em.term_id = :termId
															ORDER BY em.student_id DESC
														) foo
														GROUP BY exam_type,exam_type_id,student_id
													)level1 WHERE average_grade is not null and student_id= :studentId order by exam_type_id ASC");
		$sth5->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$graphPoints = $sth5->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->details = $details;
		$results->subjectOverall = $subjectOverall;
		$results->subjectOverallByAvg = $subjectOverallByAvg;
		$results->overall = $overall;
		$results->overallByAverage = $overallByAverage;
		$results->overallLastTerm = $overallLastTerm;
		$results->graphPoints = $graphPoints;

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

$app->get('/getLowerSchoolExamMarksforReportCard/:student_id/:class/:term(/:teacherId)', function ($studentId,$classId,$termId,$teacherId=null) {
	//Get student exam marks

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();

		// get exam marks by exam type
		$params = array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId);
		$query = "SELECT mark,
								grade_weight,
								exam_type,
								(select grade2 from app.grading2 where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade,
								(select comment from app.grading2 where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as comment,
								(select kiswahili_comment from app.grading2 where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as kiswahili_comment,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
								subject_name,
								subjects.teacher_id,
								employees.initials,
								use_for_grading
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
						INNER JOIN app.subjects
						LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
								                ON class_subjects.subject_id = subjects.subject_id
						INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
							                    ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						                        ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId AND term_id = :termId AND student_id = :studentId AND mark IS NOT NULL
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
		$sth2 = $db->prepare("SELECT  subject_name,
									total_mark,
									total_grade_weight,
									ceil(total_mark::float/total_grade_weight::float*100) as percentage,
									(select grade2 from app.grading2 where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
									(select comment from app.grading2 where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as comment,
									(select kiswahili_comment from app.grading2 where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as kiswahili_comment,
									sort_order
							FROM (
									SELECT class_id
												,class_subjects.subject_id
												,subject_name
												,exam_marks.student_id
												,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												,coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight
											,subjects.sort_order
									FROM app.exam_marks
									INNER JOIN app.class_subject_exams
										INNER JOIN app.exam_types
										ON class_subject_exams.exam_type_id = exam_types.exam_type_id
										INNER JOIN app.class_subjects
											INNER JOIN app.subjects
											ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
									WHERE class_subjects.class_id = :classId
									AND term_id = :termId
									AND subjects.parent_subject_id is null
									AND subjects.use_for_grading is true
									AND student_id = :studentId
									AND mark IS NOT NULL
									GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading
								) q
							 ORDER BY sort_order  ");
		$sth2->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$subjectOverall = $sth2->fetchAll(PDO::FETCH_OBJ);

		// get overall position
		$sth3 = $db->prepare("SELECT total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
									(select grade2 from app.grading2 where percentage >= min_mark and  percentage <= max_mark) as grade,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight,
										ceil(total_mark::float/total_grade_weight::float*100) as percentage,
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
												) AS temp)*/ 1 as num_exam_types
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

										AND class_subject_exams.exam_type_id = (SELECT cse.exam_type_id FROM app.class_subject_exams cse INNER JOIN app.exam_types et USING (exam_type_id) INNER JOIN app.classes USING (class_cat_id) WHERE class_id = :classId ORDER BY et.sort_order DESC LIMIT 1)

										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
								 ) q
								 where student_id = :studentId");
		$sth3->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overall = $sth3->fetch(PDO::FETCH_OBJ);

		// get overall position last term
		$sth4 = $db->prepare("SELECT total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight,
																 rank, percentage,
																(select grade2 from app.grading2 where percentage >= min_mark and  percentage <= max_mark) as grade,
																position_out_of
								FROM (
									SELECT student_id, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
												dense_rank() over w as rank, position_out_of,

										/*hack by tom , remember to remove*/

										/*(SELECT COUNT(*) FROM (
													SELECT DISTINCT exam_type_id
													FROM app.exam_marks
													INNER JOIN app.class_subject_exams
														INNER JOIN app.class_subjects
														ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
													ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
													WHERE student_id = a.student_id
													AND class_subjects.class_id = :classId
													AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
												) AS temp)*/ 1 as num_exam_types
									FROM (
										SELECT exam_marks.student_id, coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark
											  	,coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight
											  	,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
										FROM app.exam_marks
										INNER JOIN app.class_subject_exams
										INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
										INNER JOIN app.class_subjects
										INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
										INNER JOIN app.students ON exam_marks.student_id = students.student_id
										WHERE class_subjects.class_id = :classId
										AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
										AND subjects.parent_subject_id is null
										AND subjects.use_for_grading is true
										AND students.active is true
										AND mark IS NOT NULL
										/*hack by tom, remember to remove*/
										AND class_subject_exams.exam_type_id =(SELECT exam_type_id from app.exam_types where class_cat_id=(SELECT class_cat_id from app.classes where class_id=:classId) order by sort_order desc LIMIT 1)
										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
								 ) q
								 where student_id = :studentId");
		$sth4->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overallLastTerm = $sth4->fetch(PDO::FETCH_OBJ);

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
		$sth5 = $db->prepare("SELECT * FROM (
														SELECT * FROM (
															SELECT  em.student_id, cse.exam_type_id, round( avg((em.mark::decimal/cse.grade_weight::decimal) * 100)) AS average_grade, et.exam_type
															FROM app.exam_marks  em
															LEFT JOIN app.class_subject_exams cse USING (class_sub_exam_id)
															LEFT JOIN app.exam_types et USING (exam_type_id)
															LEFT JOIN app.class_subjects cs USING (class_subject_id)
															LEFT JOIN app.subjects s USING(subject_id)
															--WHERE em.student_id= 446
															WHERE em.term_id = :termId
															GROUP BY exam_type,et.sort_order,cse.exam_type_id,em.student_id
															ORDER BY em.student_id DESC
														) foo
													)level1 WHERE average_grade is not null and student_id= :studentId order by exam_type_id ASC");
		$sth5->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$graphPoints = $sth5->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->details = $details;
		$results->subjectOverall = $subjectOverall;
		$results->overall = $overall;
		$results->overallLastTerm = $overallLastTerm;
		$results->graphPoints = $graphPoints;

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

$app->get('/getStreamPosition/:student_id/:termId', function ($studentId,$termId) {
	//Get stream position

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();

		// get exam marks by exam type
		$params = array(':studentId' => $studentId, ':termId' => $termId);

		// stream positions
		$sth7 = $db->prepare("SELECT * FROM (
														SELECT student_id, student_name, avg, class_name, rank() over(order by avg desc) AS position,
															(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId))) AND students.active is true) AS position_out_of
														FROM (
															SELECT student_id, student_name, class_name, sum(total_mark) as avg, sum(total_grade_weight) as total_grade_weight FROM (
																SELECT round(avg(total_mark)) AS total_mark, round(avg(total_grade_weight)) AS total_grade_weight, student_id, student_name, class_name, subject_name
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
																		WHERE class_cats.entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))
																		AND term_id = :termId
																		AND class_subject_exams.exam_type_id = (SELECT cse.exam_type_id FROM app.class_subject_exams cse INNER JOIN app.exam_types et USING (exam_type_id) INNER JOIN app.class_cats USING (class_cat_id) WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId))) ORDER BY et.sort_order DESC LIMIT 1)
																		AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.student_id = exam_marks.student_id AND mark IS NOT NULL AND students.active IS TRUE
																		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
																			use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
																		ORDER BY exam_marks.student_id ASC
																	) q GROUP BY student_id, student_name, class_id, class_name, subject_id, subject_name, exam_type_id ORDER BY student_id ASC
																) AS foo GROUP BY student_id,student_name, class_name, subject_name ORDER BY student_id ASC, total_mark DESC
															) AS FOO2 GROUP BY student_id, student_name, class_name
														) AS foo3
													) AS foo3 WHERE student_id = :studentId");
		$sth7->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$streamRank = $sth7->fetchAll(PDO::FETCH_OBJ);

		// stream positions last term
		$sth7LstTm = $db->prepare("SELECT * FROM (
														SELECT student_id, student_name, avg, class_name, rank() over(order by avg desc) AS position,
															(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId))) AND students.active is true) AS position_out_of
														FROM (
															SELECT student_id, student_name, class_name, sum(total_mark) as avg, sum(total_grade_weight) as total_grade_weight FROM (
																SELECT round(avg(total_mark)) AS total_mark, round(avg(total_grade_weight)) AS total_grade_weight, student_id, student_name, class_name, subject_name
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
																		WHERE class_cats.entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))
																		AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_subject_exams.exam_type_id = (SELECT cse.exam_type_id FROM app.class_subject_exams cse INNER JOIN app.exam_types et USING (exam_type_id) INNER JOIN app.class_cats USING (class_cat_id) WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId))) ORDER BY et.sort_order DESC LIMIT 1)
																		AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND students.student_id = exam_marks.student_id AND mark IS NOT NULL AND students.active IS TRUE
																		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
																			use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
																		ORDER BY exam_marks.student_id ASC
																	) q GROUP BY student_id, student_name, class_id, class_name, subject_id, subject_name, exam_type_id ORDER BY student_id ASC
																) AS foo GROUP BY student_id,student_name, class_name, subject_name ORDER BY student_id ASC, total_mark DESC
															) AS FOO2 GROUP BY student_id, student_name, class_name
														) AS foo3
													) AS foo3 WHERE student_id = :studentId");
		$sth7LstTm->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$streamRankLastTerm = $sth7LstTm->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->streamRank = $streamRank;
		$results->streamRankLastTerm = $streamRankLastTerm;

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
	$termId =			( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$classId =			( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$reportCardType =	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$reportData =		( isset($allPostVars['report_data']) ? $allPostVars['report_data']: null);
	$published =		( isset($allPostVars['published']) ? $allPostVars['published']: 'f');

  try
  {
    $db = getDB();

		$getReport = $db->prepare("SELECT report_card_id FROM app.report_cards WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId");

		$addReport = $db->prepare("INSERT INTO app.report_cards(student_id, class_id, term_id, report_data, created_by, report_card_type, teacher_id, published)
								VALUES(:studentId, :classId, :termId, :reportData, :userId, :reportCardType, :teacherId, :published)");

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
											':published' => $published
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
    echo json_encode(array("response" => "success", "code" => 1, "data" => $allPostVars));
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
