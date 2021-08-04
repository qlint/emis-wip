<?php
$app->get('/getReportCardYears', function () {
    //Show report card years

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT DISTINCT date_part('year',creation_date) AS year
														FROM app.report_cards
														ORDER BY year ASC");
		$sth->execute();
		$years = $sth->fetchAll(PDO::FETCH_OBJ);

        if($years) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $years ));
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

$app->get('/getAllStudentReportCardsInYear/:class_id/:year', function ($classId,$year) {
    //Get report cards for class

	$app = \Slim\Slim::getInstance();

    try
    {

		$db = getDB();

		$sth = $db->prepare("SELECT report_cards.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
									admission_number, report_cards.class_id, class_cat_id,
									class_name, report_cards.term_id, term_name, date_part('year', start_date) as year, report_data, report_cards.report_card_type,
									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
									report_cards.creation_date::date as date, published, terms.term_number, 'TERM '|| terms.term_number AS alt_term_name
							FROM app.report_cards
							INNER JOIN app.students ON report_cards.student_id = students.student_id
							INNER JOIN app.classes ON report_cards.class_id = classes.class_id
							INNER JOIN app.terms ON report_cards.term_id = terms.term_id
							LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
							WHERE report_cards.class_id = :classId
							AND date_part('year',report_cards.creation_date) = :year
							AND students.active IS TRUE
							ORDER BY students.student_id");

		$sth->execute( array($classId,$year) );
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

		$sth = $db->prepare("SELECT a.*, 'TERM ' || (SELECT term_number FROM app.terms WHERE term_id = a.term_id) AS alt_term_name
                            FROM (
                            					SELECT report_card_id, report_cards.student_id, report_cards.class_id, class_name, term_name, report_cards.term_id,
                            									date_part('year', start_date) as year, report_data, report_cards.report_card_type, class_cat_id,
                            									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
                            									report_cards.creation_date::date as date, published
                            					FROM app.report_cards
                            					INNER JOIN app.students ON report_cards.student_id = students.student_id
                            					INNER JOIN app.classes ON report_cards.class_id = classes.class_id
                            					INNER JOIN app.terms ON report_cards.term_id = terms.term_id
                            					LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
                            					WHERE report_cards.student_id = :studentId
                            					ORDER BY report_card_id
                            	)a");
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

$app->get('/getReportCardData/:student_id/:class_id/:term_id', function ($studentId, $classId, $termId) {
    //Get student report card

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

		$sth = $db->prepare("SELECT d.*, (SELECT value FROM app.settings WHERE name = 'Exam Calculation') AS calculation_mode,
																(SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId)) AS entity_id,
                              	(
                              		SELECT array_to_json(ARRAY_AGG(c)) FROM
                              		(
                              			SELECT exam_type_id, exam_type, array_to_json(ARRAY_AGG(row_to_json(b))) AS exam_marks
                              			FROM (
																			SELECT student_id, exam_type_id, exam_type, exam_sort, subject_name, subject_id, mark,
																					(SELECT grade FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS grade,
																					(SELECT comment FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS comment,
																					out_of, subject_sort, parent_subject_id, percentage
																			FROM (
																				SELECT f.*,
																					g.student_id, g.mark, g.percentage, g.out_of
																				FROM
																				(
																				SELECT cs2.class_subject_id, s2.subject_name, s2.subject_id,
																					s2.sort_order AS subject_sort, s2.parent_subject_id,
																					cse.class_sub_exam_id, cse.exam_type_id, et.exam_type,
																					et.sort_order AS exam_sort
																				FROM app.subjects s2
																				LEFT JOIN app.class_subjects cs2 USING (subject_id)
																				LEFT JOIN app.class_subject_exams cse USING (class_subject_id)
																				LEFT JOIN app.exam_types et USING (exam_type_id)
																				WHERE cs2.class_id = :classId
																				--AND s2.use_for_grading IS TRUE
																				ORDER BY et.sort_order ASC, s2.sort_order ASC
																				)f
																				LEFT JOIN
																				(
																				SELECT e2.student_id, cse2.class_subject_id,
																					s2.subject_name, s2.subject_id, e2.mark,
																					round((mark/cse2.grade_weight::float)*100) AS percentage,
																					cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																					cse2.class_sub_exam_id
																				FROM app.subjects s2
																				LEFT JOIN app.class_subjects cs2 USING (subject_id)
																				LEFT JOIN app.class_subject_exams cse2 USING (class_subject_id)
																				LEFT JOIN app.exam_marks e2 USING (class_sub_exam_id)
																				LEFT JOIN app.exam_types et2 USING (exam_type_id)
																				WHERE e2.student_id = :studentId
																				AND cs2.class_id = :classId
																				AND e2.term_id = :termId
																				--AND s2.use_for_grading IS TRUE
																				)g
																				USING (class_sub_exam_id)
																			)a
                              			) b
                              			GROUP BY exam_type_id, exam_type
                              		) AS c
                              	) AS exam_marks,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS subject_overalls
										FROM (
											SELECT *, (SELECT grade FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS grade,
														(SELECT comment FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS comment,
														(SELECT grade2 FROM app.grading2 WHERE average >= min_mark AND average <= max_mark) AS grade2,
														(SELECT kiswahili_comment FROM app.grading2 WHERE average >= min_mark AND average <= max_mark) AS comment2
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
															FROM app.subjects s2
															LEFT JOIN app.class_subjects cs2 USING (subject_id)
															LEFT JOIN app.class_subject_exams cse2 USING (class_subject_id)
															LEFT JOIN app.exam_marks e2 USING (class_sub_exam_id)
															LEFT JOIN app.exam_types et2 USING (exam_type_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null --AND s2.use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS subject_overalls_column,
								(
									SELECT array_to_json(ARRAY_AGG(j)) AS total_marks FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS total_marks
										FROM (
												SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of,
														(SELECT grade FROM app.grading WHERE round(sum(mark)::float/sum(out_of))*100 >= min_mark AND round(sum(mark)::float/sum(out_of))*100 <= max_mark ) AS grade,
														exam_sort
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count, et2.sort_order AS exam_sort
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND cs2.class_id = :classId
													AND e2.term_id = :termId AND parent_subject_id IS null AND use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY student_id, exam_type_id, exam_type, exam_sort
										) h
									) AS j
								) AS totals,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
											(
												SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
												FROM (
													SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
														(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
														(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
													FROM (
														SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
														FROM(
															SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																	s2.subject_name, s2.subject_id, e2.mark,
																	cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																	(
																		SELECT COUNT(DISTINCT exam_type_id)
																		FROM app.class_subject_exams
																		INNER JOIN app.exam_marks USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects USING (class_subject_id)
																		WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_id = :classId
																	) AS class_exam_count
															FROM app.exam_marks e2
															INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
															INNER JOIN app.exam_types et2 USING (exam_type_id)
															INNER JOIN app.class_subjects cs2 USING (class_subject_id)
															INNER JOIN app.subjects s2 USING (subject_id)
															WHERE e2.student_id = :studentId
															AND cs2.class_id = :classId
															AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
															ORDER BY et2.sort_order ASC, s2.sort_order ASC
														)f
														GROUP BY subject_id, subject_name, class_exam_count
													)g
												) h
											) AS last_term_marks_and_grade
										FROM (
											SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
												(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
												(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS overall_marks_and_grade,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
											(
												SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
												FROM (
													SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
														(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
														(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
													FROM (
														SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
														FROM(
															SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																	s2.subject_name, s2.subject_id, e2.mark,
																	cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																	(
																		SELECT COUNT(DISTINCT exam_type_id)
																		FROM app.class_subject_exams
																		INNER JOIN app.exam_marks USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects USING (class_subject_id)
																		WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_id = :classId
																		AND class_subject_exams.exam_type_id = (
																			  SELECT cc.exam_type_id FROM (
																				SELECT * FROM (
																					SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																						SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																						FROM app.exam_marks em
																						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																						INNER JOIN app.class_subjects cs USING (class_subject_id)
																						INNER JOIN app.exam_types et USING (exam_type_id)
																						WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																						AND cs.class_id = :classId
																						ORDER BY em.creation_date DESC
																					)aa
																				)bb ORDER BY creation_date DESC LIMIT 1
																			  )cc
																			)
																	) AS class_exam_count
															FROM app.exam_marks e2
															INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
															INNER JOIN app.exam_types et2 USING (exam_type_id)
															INNER JOIN app.class_subjects cs2 USING (class_subject_id)
															INNER JOIN app.subjects s2 USING (subject_id)
															WHERE e2.student_id = :studentId
															AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
															AND cse2.exam_type_id = (
																			  SELECT cc.exam_type_id FROM (
																				SELECT * FROM (
																					SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																						SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																						FROM app.exam_marks em
																						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																						INNER JOIN app.class_subjects cs USING (class_subject_id)
																						INNER JOIN app.exam_types et USING (exam_type_id)
																						WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																						AND cs.class_id = :classId
																						ORDER BY em.creation_date DESC
																					)aa
																				)bb ORDER BY creation_date DESC LIMIT 1
																			  )cc
																			)
															ORDER BY et2.sort_order ASC, s2.sort_order ASC
														)f
														GROUP BY subject_id, subject_name, class_exam_count
													)g
												) h
											) AS last_term_marks_and_grade
										FROM (
											SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
												(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
												(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
																AND class_subject_exams.exam_type_id = (
																			  SELECT cc.exam_type_id FROM (
																				SELECT * FROM (
																					SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																						SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																						FROM app.exam_marks em
																						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																						INNER JOIN app.class_subjects cs USING (class_subject_id)
																						INNER JOIN app.exam_types et USING (exam_type_id)
																						WHERE em.term_id = :termId
																						AND cs.class_id = :classId
																						ORDER BY em.creation_date DESC
																					)aa
																				)bb ORDER BY creation_date DESC LIMIT 1
																			  )cc
																			)
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													AND cse2.exam_type_id = (
																			  SELECT cc.exam_type_id FROM (
																				SELECT * FROM (
																					SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																						SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																						FROM app.exam_marks em
																						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																						INNER JOIN app.class_subjects cs USING (class_subject_id)
																						INNER JOIN app.exam_types et USING (exam_type_id)
																						WHERE em.term_id = :termId
																						AND cs.class_id = :classId
																						ORDER BY em.creation_date DESC
																					)aa
																				)bb ORDER BY creation_date DESC LIMIT 1
																			  )cc
																			)
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS overall_marks_and_grade_by_last_exam,
								(
									SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
									(
											SELECT row_to_json(i) AS this_term_position,
												(
													SELECT row_to_json(j) AS last_term_position FROM
													(
														SELECT * FROM (
															SELECT
																student_id, total_mark, total_grade_weight,
																round((total_mark::float/total_grade_weight::float)*100) as percentage,
																rank() over w as position, position_out_of
															FROM (
																SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
																	round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
																	position_out_of
																FROM(
																	SELECT exam_marks.student_id,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					mark
																				end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					grade_weight
																				end),0) as total_grade_weight,
																		(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																				INNER JOIN app.class_subjects cs USING (class_subject_id)
																				INNER JOIN app.students s USING (student_id)
																				WHERE cs.class_id = :classId
																				AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																				AND s.active IS TRUE
																		) as position_out_of,
																		(
																									SELECT COUNT(DISTINCT exam_type_id)
																									FROM app.class_subject_exams
																									INNER JOIN app.exam_marks USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects USING (class_subject_id)
																									WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND class_id = :classId
																								) AS class_exam_count

																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types USING (exam_type_id)
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects
																	ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students USING (student_id)
																	WHERE class_subjects.class_id = :classId
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND students.active is true
																	AND mark IS NOT NULL

																	GROUP BY exam_marks.student_id
																)c
															) a
															WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
														)q WHERE student_id = :studentId
													)j
												) AS last_term_position
											FROM
											(
												SELECT * FROM (
													SELECT
														student_id, total_mark, total_grade_weight,
														round((total_mark::float/total_grade_weight::float)*100) as percentage,
														rank() over w as position, position_out_of
													FROM (
														SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
															round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
															position_out_of
														FROM(
															SELECT exam_marks.student_id,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			mark
																		end),0) as total_mark,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			grade_weight
																		end),0) as total_grade_weight,
																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.students s USING (student_id)
																		WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE
																) as position_out_of,
																(
																							SELECT COUNT(DISTINCT exam_type_id)
																							FROM app.class_subject_exams
																							INNER JOIN app.exam_marks USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects USING (class_subject_id)
																							WHERE term_id = :termId
																							AND class_id = :classId
																						) AS class_exam_count

															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types USING (exam_type_id)
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects
															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students USING (student_id)
															WHERE class_subjects.class_id = :classId
															AND term_id = :termId
															AND subjects.parent_subject_id is null
															AND subjects.use_for_grading is true
															AND students.active is true
															AND mark IS NOT NULL

															GROUP BY exam_marks.student_id
														)c
													) a
													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
												)p WHERE student_id = :studentId
											)i

									)l
								) AS positions,
								(
									SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
									(
											SELECT row_to_json(i) AS this_term_position,
												(
													SELECT row_to_json(j) AS last_term_position FROM
													(
														SELECT * FROM (
															SELECT
																student_id, total_mark, total_grade_weight,
																round((total_mark::float/total_grade_weight::float)*100) as percentage,
																rank() over w as position, position_out_of
															FROM (
																SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
																	round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
																	position_out_of
																FROM(
																	SELECT exam_marks.student_id,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					mark
																				end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					grade_weight
																				end),0) as total_grade_weight,
																		(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																				INNER JOIN app.class_subjects cs USING (class_subject_id)
																				INNER JOIN app.students s USING (student_id)
																				WHERE cs.class_id = :classId
																				AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																				AND s.active IS TRUE
																				AND cse.exam_type_id = (
																									SELECT exam_type_id FROM (
																										SELECT * FROM (
																											SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																												SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																												FROM app.exam_marks em
																												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects cs USING (class_subject_id)
																												INNER JOIN app.exam_types et USING (exam_type_id)
																												WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																												AND cs.class_id = :classId
																												ORDER BY em.creation_date DESC
																											)a
																										)b ORDER BY creation_date DESC LIMIT 1
																									)c
																								  )
																		) as position_out_of,
																		(
																									SELECT COUNT(DISTINCT exam_type_id)
																									FROM app.class_subject_exams
																									INNER JOIN app.exam_marks USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects USING (class_subject_id)
																									WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND class_id = :classId
																									AND class_subject_exams.exam_type_id = (
																									SELECT exam_type_id FROM (
																										SELECT * FROM (
																											SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																												SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																												FROM app.exam_marks em
																												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects cs USING (class_subject_id)
																												INNER JOIN app.exam_types et USING (exam_type_id)
																												WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																												AND cs.class_id = :classId
																												ORDER BY em.creation_date DESC
																											)a
																										)b ORDER BY creation_date DESC LIMIT 1
																									)c
																								  )
																								) AS class_exam_count

																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types USING (exam_type_id)
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects
																	ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students USING (student_id)
																	WHERE class_subjects.class_id = :classId
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND students.active is true
																	AND mark IS NOT NULL
																	AND class_subject_exams.exam_type_id = (
																									SELECT exam_type_id FROM (
																										SELECT * FROM (
																											SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																												SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																												FROM app.exam_marks em
																												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects cs USING (class_subject_id)
																												INNER JOIN app.exam_types et USING (exam_type_id)
																												WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																												AND cs.class_id = :classId
																												ORDER BY em.creation_date DESC
																											)a
																										)b ORDER BY creation_date DESC LIMIT 1
																									)c
																								  )
																	GROUP BY exam_marks.student_id
																)c
															) a
															WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
														)q WHERE student_id = :studentId
													)j
												) AS last_term_position
											FROM
											(
												SELECT * FROM (
													SELECT
														student_id, total_mark, total_grade_weight,
														round((total_mark::float/total_grade_weight::float)*100) as percentage,
														rank() over w as position, position_out_of
													FROM (
														SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
															round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
															position_out_of
														FROM(
															SELECT exam_marks.student_id,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			mark
																		end),0) as total_mark,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			grade_weight
																		end),0) as total_grade_weight,
																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.students s USING (student_id)
																		WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE
																		AND cse.exam_type_id = (
																									SELECT exam_type_id FROM (
																										SELECT * FROM (
																											SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																												SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																												FROM app.exam_marks em
																												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects cs USING (class_subject_id)
																												INNER JOIN app.exam_types et USING (exam_type_id)
																												WHERE em.term_id = :termId
																												AND cs.class_id = :classId
																												ORDER BY em.creation_date DESC
																											)a
																										)b ORDER BY creation_date DESC LIMIT 1
																									)c
																								  )
																) as position_out_of,
																(
																							SELECT COUNT(DISTINCT exam_type_id)
																							FROM app.class_subject_exams
																							INNER JOIN app.exam_marks USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects USING (class_subject_id)
																							WHERE term_id = :termId
																							AND class_id = :classId
																							AND class_subject_exams.exam_type_id = (
																									SELECT exam_type_id FROM (
																										SELECT * FROM (
																											SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																												SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																												FROM app.exam_marks em
																												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects cs USING (class_subject_id)
																												INNER JOIN app.exam_types et USING (exam_type_id)
																												WHERE em.term_id = :termId
																												AND cs.class_id = :classId
																												ORDER BY em.creation_date DESC
																											)a
																										)b ORDER BY creation_date DESC LIMIT 1
																									)c
																								  )
																						) AS class_exam_count

															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types USING (exam_type_id)
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects
															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students USING (student_id)
															WHERE class_subjects.class_id = :classId
															AND term_id = :termId
															AND subjects.parent_subject_id is null
															AND subjects.use_for_grading is true
															AND students.active is true
															AND mark IS NOT NULL
															AND class_subject_exams.exam_type_id = (
																									SELECT exam_type_id FROM (
																										SELECT * FROM (
																											SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																												SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																												FROM app.exam_marks em
																												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects cs USING (class_subject_id)
																												INNER JOIN app.exam_types et USING (exam_type_id)
																												WHERE em.term_id = :termId
																												AND cs.class_id = :classId
																												ORDER BY em.creation_date DESC
																											)a
																										)b ORDER BY creation_date DESC LIMIT 1
																									)c
																								  )
															GROUP BY exam_marks.student_id
														)c
													) a
													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
												)p WHERE student_id = :studentId
											)i

									)l
								) AS positions_by_last_exams,
								(
								  SELECT array_to_json(ARRAY_AGG(row_to_json(p))) AS subjects FROM
								  (
									SELECT DISTINCT ON (subject_id) subject_id, subject_name, teacher_id, sort_order, parent_subject_id,
										e.initials
									FROM app.subjects s
									LEFT JOIN app.class_subjects cs USING (subject_id)
									LEFT JOIN app.class_subject_exams cse USING (class_subject_id)
									LEFT JOIN app.exam_marks em USING (class_sub_exam_id)
									LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
									WHERE s.active IS TRUE
									AND cs.class_id = :classId
									AND em.term_id = :termId
									AND em.student_id = :studentId /* for when students select subjects */
									--AND s.use_for_grading IS TRUE
								  )p
								) AS subjects_column,
								(
									SELECT report_data::json FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								) AS report_card_comments,
								(
													SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
												) AS report_card_type,
								(
								SELECT CASE
										WHEN report_card_type = 'Playgroup'
										THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
									END AS report_card_data
								FROM (
									SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								)a
												) AS playgroup_report_card,
								(
								SELECT CASE
										WHEN report_card_type = 'Kindergarten'
										THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
									END AS report_card_data
								FROM (
									SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								)a
												) AS kindergarten_report_card
                              FROM (
                              	SELECT DISTINCT student_id, student_name, term_id, term_name, closing_date, year, next_term_begins, class_name,
                              		admission_number
                              	FROM(
                              		SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                              				em.term_id, t.term_name, t.end_date AS closing_date, date_part('year', t.start_date) AS year,
                              				(SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = :termId) LIMIT 1) AS next_term_begins,
                              				(SELECT class_name FROM app.classes WHERE class_id = :classId) AS class_name, s.admission_number
                              		FROM app.students s
                              		LEFT JOIN app.report_cards em USING (student_id) /* this is not a mistake */
                              		INNER JOIN app.terms t USING (term_id)
                              		WHERE em.term_id = :termId
																	AND em.class_id = :classId
                              		AND s.student_id = :studentId
                              	)a
                              )d");
		$sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
        $results = $sth->fetch(PDO::FETCH_OBJ);
		$results->exam_marks = ($results->exam_marks == null ? null : json_decode($results->exam_marks));
		$results->subject_overalls_column = json_decode($results->subject_overalls_column);
		$results->totals = json_decode($results->totals);
		$results->overall_marks_and_grade = json_decode($results->overall_marks_and_grade);
		$results->overall_marks_and_grade_by_last_exam = json_decode($results->overall_marks_and_grade_by_last_exam);
		$results->positions = json_decode($results->positions);
		$results->positions_by_last_exams = json_decode($results->positions_by_last_exams);
		$results->subjects_column = json_decode($results->subjects_column);
		$results->report_card_comments = json_decode($results->report_card_comments);
		$results->report_card_comments = ($results->report_card_comments->comments == null ? null : $results->report_card_comments->comments);
		$results->playgroup_report_card = json_decode($results->playgroup_report_card);
		$results->playgroup_report_card = ($results->playgroup_report_card == null ? null : $results->playgroup_report_card->subjects);
		$results->kindergarten_report_card = json_decode($results->kindergarten_report_card);
		$results->kindergarten_report_card = ($results->kindergarten_report_card == null ? null : $results->kindergarten_report_card->subjects);


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

$app->get('/getClassReportCardData/:class_id/:term_id', function ($classId, $termId) {
    //Get student report card

	$app = \Slim\Slim::getInstance();
	$output_object = new stdClass;
	$output_object->report_cards = array();

    try
    {
        $db = getDB();

				$classQry = $db->prepare("SELECT student_id FROM (
																		SELECT DISTINCT ON (em.student_id, em.term_id) em.student_id, em.term_id  FROM
																		app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.classes c USING (class_id)
																		WHERE em.term_id = :termId
																		AND c.class_id = :classId
																	)x");
				$classQry->execute( array(':classId' => $classId, ':termId' => $termId) );
				$classStudents = $classQry->fetchAll(PDO::FETCH_OBJ);
				$output_object->class_students = $classStudents;

				foreach ($classStudents as $student) {
					$student_object = new stdClass;
					$student_object->student_id = $student->student_id;
					$studentId = $student->student_id;

					$sth = $db->prepare("SELECT d.*, (SELECT value FROM app.settings WHERE name = 'Exam Calculation') AS calculation_mode,
																			(SELECT student_image FROM app.students WHERE student_id = :studentId) AS student_image,
			                              	(
			                              		SELECT array_to_json(ARRAY_AGG(c)) FROM
			                              		(
			                              			SELECT exam_type_id, exam_type, array_to_json(ARRAY_AGG(row_to_json(b))) AS exam_marks
			                              			FROM (
																						SELECT student_id, exam_type_id, exam_type, exam_sort, subject_name, subject_id, mark,
																								(SELECT grade FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS grade,
																								(SELECT comment FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS comment,
																								out_of, subject_sort, parent_subject_id, percentage
																						FROM (
					                              				SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
					                              						s2.subject_name, s2.subject_id, e2.mark,
																										round((mark/cse2.grade_weight::float)*100) AS percentage,
					                              						cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
					                              				FROM app.exam_marks e2
					                              				INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
					                              				INNER JOIN app.exam_types et2 USING (exam_type_id)
					                              				INNER JOIN app.class_subjects cs2 USING (class_subject_id)
					                              				INNER JOIN app.subjects s2 USING (subject_id)
					                              				WHERE e2.student_id = :studentId
					                              				AND e2.term_id = :termId AND s2.use_for_grading IS TRUE
					                              				ORDER BY et2.sort_order ASC, s2.sort_order ASC
																						)a
			                              			) b
			                              			GROUP BY exam_type_id, exam_type
			                              		) AS c
			                              	) AS exam_marks,
											(
												SELECT array_to_json(ARRAY_AGG(j)) FROM
												(
													SELECT ARRAY_AGG(row_to_json(h)) AS subject_overalls
													FROM (
														SELECT *, (SELECT grade FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS grade,
																	(SELECT comment FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS comment,
																	(SELECT grade2 FROM app.grading2 WHERE average >= min_mark AND average <= max_mark) AS grade2,
																	(SELECT kiswahili_comment FROM app.grading2 WHERE average >= min_mark AND average <= max_mark) AS comment2
														FROM (
															SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
															FROM(
																SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																		s2.subject_name, s2.subject_id, e2.mark,
																		cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																		(
																			SELECT COUNT(DISTINCT exam_type_id)
																			FROM app.class_subject_exams
																			INNER JOIN app.exam_marks USING (class_sub_exam_id)
																			INNER JOIN app.class_subjects USING (class_subject_id)
																			WHERE term_id = :termId
																			AND class_id = :classId
																		) AS class_exam_count
																FROM app.exam_marks e2
																INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
																INNER JOIN app.exam_types et2 USING (exam_type_id)
																INNER JOIN app.class_subjects cs2 USING (class_subject_id)
																INNER JOIN app.subjects s2 USING (subject_id)
																WHERE e2.student_id = :studentId
																AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
																ORDER BY et2.sort_order ASC, s2.sort_order ASC
															)f
															GROUP BY subject_id, subject_name, class_exam_count
														)g
													) h
												) AS j
											) AS subject_overalls_column,
											(
												SELECT array_to_json(ARRAY_AGG(j)) AS total_marks FROM
												(
													SELECT ARRAY_AGG(row_to_json(h)) AS total_marks
													FROM (
															SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of,
																	(SELECT grade FROM app.grading WHERE round(sum(mark)::float/sum(out_of))*100 >= min_mark AND round(sum(mark)::float/sum(out_of))*100 <= max_mark ) AS grade
															FROM(
																SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																		s2.subject_name, s2.subject_id, e2.mark,
																		cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																		(
																			SELECT COUNT(DISTINCT exam_type_id)
																			FROM app.class_subject_exams
																			INNER JOIN app.exam_marks USING (class_sub_exam_id)
																			INNER JOIN app.class_subjects USING (class_subject_id)
																			WHERE term_id = :termId
																			AND class_id = :classId
																		) AS class_exam_count
																FROM app.exam_marks e2
																INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
																INNER JOIN app.exam_types et2 USING (exam_type_id)
																INNER JOIN app.class_subjects cs2 USING (class_subject_id)
																INNER JOIN app.subjects s2 USING (subject_id)
																WHERE e2.student_id = :studentId
																AND cs2.class_id = :classId
																AND e2.term_id = :termId AND parent_subject_id IS null AND use_for_grading IS TRUE
																ORDER BY et2.sort_order ASC, s2.sort_order ASC
															)f
															GROUP BY student_id, exam_type_id, exam_type
													) h
												) AS j
											) AS totals,
											(
												SELECT array_to_json(ARRAY_AGG(j)) FROM
												(
													SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
														(
															SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
															FROM (
																SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
																	(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
																	(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
																FROM (
																	SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
																	FROM(
																		SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																				s2.subject_name, s2.subject_id, e2.mark,
																				cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																				(
																					SELECT COUNT(DISTINCT exam_type_id)
																					FROM app.class_subject_exams
																					INNER JOIN app.exam_marks USING (class_sub_exam_id)
																					INNER JOIN app.class_subjects USING (class_subject_id)
																					WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																					AND class_id = :classId
																				) AS class_exam_count
																		FROM app.exam_marks e2
																		INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
																		INNER JOIN app.exam_types et2 USING (exam_type_id)
																		INNER JOIN app.class_subjects cs2 USING (class_subject_id)
																		INNER JOIN app.subjects s2 USING (subject_id)
																		WHERE e2.student_id = :studentId
																		AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
																		ORDER BY et2.sort_order ASC, s2.sort_order ASC
																	)f
																	GROUP BY subject_id, subject_name, class_exam_count
																)g
															) h
														) AS last_term_marks_and_grade
													FROM (
														SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
															(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
															(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
														FROM (
															SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
															FROM(
																SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																		s2.subject_name, s2.subject_id, e2.mark,
																		cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																		(
																			SELECT COUNT(DISTINCT exam_type_id)
																			FROM app.class_subject_exams
																			INNER JOIN app.exam_marks USING (class_sub_exam_id)
																			INNER JOIN app.class_subjects USING (class_subject_id)
																			WHERE term_id = :termId
																			AND class_id = :classId
																		) AS class_exam_count
																FROM app.exam_marks e2
																INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
																INNER JOIN app.exam_types et2 USING (exam_type_id)
																INNER JOIN app.class_subjects cs2 USING (class_subject_id)
																INNER JOIN app.subjects s2 USING (subject_id)
																WHERE e2.student_id = :studentId
																AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
																ORDER BY et2.sort_order ASC, s2.sort_order ASC
															)f
															GROUP BY subject_id, subject_name, class_exam_count
														)g
													) h
												) AS j
											) AS overall_marks_and_grade,
											(
												SELECT array_to_json(ARRAY_AGG(j)) FROM
												(
													SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
														(
															SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
															FROM (
																SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
																	(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
																	(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
																FROM (
																	SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
																	FROM(
																		SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																				s2.subject_name, s2.subject_id, e2.mark,
																				cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																				(
																					SELECT COUNT(DISTINCT exam_type_id)
																					FROM app.class_subject_exams
																					INNER JOIN app.exam_marks USING (class_sub_exam_id)
																					INNER JOIN app.class_subjects USING (class_subject_id)
																					WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																					AND class_id = :classId
																					AND class_subject_exams.exam_type_id = (
																						  SELECT cc.exam_type_id FROM (
																							SELECT * FROM (
																								SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																									SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																									FROM app.exam_marks em
																									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects cs USING (class_subject_id)
																									INNER JOIN app.exam_types et USING (exam_type_id)
																									WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND cs.class_id = :classId
																									ORDER BY em.creation_date DESC
																								)aa
																							)bb ORDER BY creation_date DESC LIMIT 1
																						  )cc
																						)
																				) AS class_exam_count
																		FROM app.exam_marks e2
																		INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
																		INNER JOIN app.exam_types et2 USING (exam_type_id)
																		INNER JOIN app.class_subjects cs2 USING (class_subject_id)
																		INNER JOIN app.subjects s2 USING (subject_id)
																		WHERE e2.student_id = :studentId
																		AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
																		AND cse2.exam_type_id = (
																						  SELECT cc.exam_type_id FROM (
																							SELECT * FROM (
																								SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																									SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																									FROM app.exam_marks em
																									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects cs USING (class_subject_id)
																									INNER JOIN app.exam_types et USING (exam_type_id)
																									WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND cs.class_id = :classId
																									ORDER BY em.creation_date DESC
																								)aa
																							)bb ORDER BY creation_date DESC LIMIT 1
																						  )cc
																						)
																		ORDER BY et2.sort_order ASC, s2.sort_order ASC
																	)f
																	GROUP BY subject_id, subject_name, class_exam_count
																)g
															) h
														) AS last_term_marks_and_grade
													FROM (
														SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
															(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
															(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
														FROM (
															SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
															FROM(
																SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																		s2.subject_name, s2.subject_id, e2.mark,
																		cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																		(
																			SELECT COUNT(DISTINCT exam_type_id)
																			FROM app.class_subject_exams
																			INNER JOIN app.exam_marks USING (class_sub_exam_id)
																			INNER JOIN app.class_subjects USING (class_subject_id)
																			WHERE term_id = :termId
																			AND class_id = :classId
																			AND class_subject_exams.exam_type_id = (
																						  SELECT cc.exam_type_id FROM (
																							SELECT * FROM (
																								SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																									SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																									FROM app.exam_marks em
																									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects cs USING (class_subject_id)
																									INNER JOIN app.exam_types et USING (exam_type_id)
																									WHERE em.term_id = :termId
																									AND cs.class_id = :classId
																									ORDER BY em.creation_date DESC
																								)aa
																							)bb ORDER BY creation_date DESC LIMIT 1
																						  )cc
																						)
																		) AS class_exam_count
																FROM app.exam_marks e2
																INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
																INNER JOIN app.exam_types et2 USING (exam_type_id)
																INNER JOIN app.class_subjects cs2 USING (class_subject_id)
																INNER JOIN app.subjects s2 USING (subject_id)
																WHERE e2.student_id = :studentId
																AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
																AND cse2.exam_type_id = (
																						  SELECT cc.exam_type_id FROM (
																							SELECT * FROM (
																								SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																									SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																									FROM app.exam_marks em
																									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects cs USING (class_subject_id)
																									INNER JOIN app.exam_types et USING (exam_type_id)
																									WHERE em.term_id = :termId
																									AND cs.class_id = :classId
																									ORDER BY em.creation_date DESC
																								)aa
																							)bb ORDER BY creation_date DESC LIMIT 1
																						  )cc
																						)
																ORDER BY et2.sort_order ASC, s2.sort_order ASC
															)f
															GROUP BY subject_id, subject_name, class_exam_count
														)g
													) h
												) AS j
											) AS overall_marks_and_grade_by_last_exam,
											(
												SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
												(
														SELECT row_to_json(i) AS this_term_position,
															(
																SELECT row_to_json(j) AS last_term_position FROM
																(
																	SELECT * FROM (
																		SELECT
																			student_id, total_mark, total_grade_weight,
																			round((total_mark::float/total_grade_weight::float)*100) as percentage,
																			rank() over w as position, position_out_of
																		FROM (
																			SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
																				round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
																				position_out_of
																			FROM(
																				SELECT exam_marks.student_id,
																					coalesce(sum(case when subjects.parent_subject_id is null then
																								mark
																							end),0) as total_mark,
																					coalesce(sum(case when subjects.parent_subject_id is null then
																								grade_weight
																							end),0) as total_grade_weight,
																					(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																							INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects cs USING (class_subject_id)
																							INNER JOIN app.students s USING (student_id)
																							WHERE cs.class_id = :classId
																							AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																							AND s.active IS TRUE
																					) as position_out_of,
																					(
																												SELECT COUNT(DISTINCT exam_type_id)
																												FROM app.class_subject_exams
																												INNER JOIN app.exam_marks USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects USING (class_subject_id)
																												WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																												AND class_id = :classId
																											) AS class_exam_count

																				FROM app.exam_marks
																				INNER JOIN app.class_subject_exams
																				INNER JOIN app.exam_types USING (exam_type_id)
																				INNER JOIN app.class_subjects
																				INNER JOIN app.subjects
																				ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																				ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																				ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																				INNER JOIN app.students USING (student_id)
																				WHERE class_subjects.class_id = :classId
																				AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																				AND subjects.parent_subject_id is null
																				AND subjects.use_for_grading is true
																				AND students.active is true
																				AND mark IS NOT NULL

																				GROUP BY exam_marks.student_id
																			)c
																		) a
																		WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
																	)q WHERE student_id = :studentId
																)j
															) AS last_term_position
														FROM
														(
															SELECT * FROM (
																SELECT
																	student_id, total_mark, total_grade_weight,
																	round((total_mark::float/total_grade_weight::float)*100) as percentage,
																	rank() over w as position, position_out_of
																FROM (
																	SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
																		round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
																		position_out_of
																	FROM(
																		SELECT exam_marks.student_id,
																			coalesce(sum(case when subjects.parent_subject_id is null then
																						mark
																					end),0) as total_mark,
																			coalesce(sum(case when subjects.parent_subject_id is null then
																						grade_weight
																					end),0) as total_grade_weight,
																			(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																					INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																					INNER JOIN app.class_subjects cs USING (class_subject_id)
																					INNER JOIN app.students s USING (student_id)
																					WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE
																			) as position_out_of,
																			(
																										SELECT COUNT(DISTINCT exam_type_id)
																										FROM app.class_subject_exams
																										INNER JOIN app.exam_marks USING (class_sub_exam_id)
																										INNER JOIN app.class_subjects USING (class_subject_id)
																										WHERE term_id = :termId
																										AND class_id = :classId
																									) AS class_exam_count

																		FROM app.exam_marks
																		INNER JOIN app.class_subject_exams
																		INNER JOIN app.exam_types USING (exam_type_id)
																		INNER JOIN app.class_subjects
																		INNER JOIN app.subjects
																		ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																		ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																		ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																		INNER JOIN app.students USING (student_id)
																		WHERE class_subjects.class_id = :classId
																		AND term_id = :termId
																		AND subjects.parent_subject_id is null
																		AND subjects.use_for_grading is true
																		AND students.active is true
																		AND mark IS NOT NULL

																		GROUP BY exam_marks.student_id
																	)c
																) a
																WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
															)p WHERE student_id = :studentId
														)i

												)l
											) AS positions,
											(
												SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
												(
														SELECT row_to_json(i) AS this_term_position,
															(
																SELECT row_to_json(j) AS last_term_position FROM
																(
																	SELECT * FROM (
																		SELECT
																			student_id, total_mark, total_grade_weight,
																			round((total_mark::float/total_grade_weight::float)*100) as percentage,
																			rank() over w as position, position_out_of
																		FROM (
																			SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
																				round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
																				position_out_of
																			FROM(
																				SELECT exam_marks.student_id,
																					coalesce(sum(case when subjects.parent_subject_id is null then
																								mark
																							end),0) as total_mark,
																					coalesce(sum(case when subjects.parent_subject_id is null then
																								grade_weight
																							end),0) as total_grade_weight,
																					(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																							INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects cs USING (class_subject_id)
																							INNER JOIN app.students s USING (student_id)
																							WHERE cs.class_id = :classId
																							AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																							AND s.active IS TRUE
																							AND cse.exam_type_id = (
																												SELECT exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																															AND cs.class_id = :classId
																															ORDER BY em.creation_date DESC
																														)a
																													)b ORDER BY creation_date DESC LIMIT 1
																												)c
																											  )
																					) as position_out_of,
																					(
																												SELECT COUNT(DISTINCT exam_type_id)
																												FROM app.class_subject_exams
																												INNER JOIN app.exam_marks USING (class_sub_exam_id)
																												INNER JOIN app.class_subjects USING (class_subject_id)
																												WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																												AND class_id = :classId
																												AND class_subject_exams.exam_type_id = (
																												SELECT exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																															AND cs.class_id = :classId
																															ORDER BY em.creation_date DESC
																														)a
																													)b ORDER BY creation_date DESC LIMIT 1
																												)c
																											  )
																											) AS class_exam_count

																				FROM app.exam_marks
																				INNER JOIN app.class_subject_exams
																				INNER JOIN app.exam_types USING (exam_type_id)
																				INNER JOIN app.class_subjects
																				INNER JOIN app.subjects
																				ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																				ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																				ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																				INNER JOIN app.students USING (student_id)
																				WHERE class_subjects.class_id = :classId
																				AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																				AND subjects.parent_subject_id is null
																				AND subjects.use_for_grading is true
																				AND students.active is true
																				AND mark IS NOT NULL
																				AND class_subject_exams.exam_type_id = (
																												SELECT exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																															AND cs.class_id = :classId
																															ORDER BY em.creation_date DESC
																														)a
																													)b ORDER BY creation_date DESC LIMIT 1
																												)c
																											  )
																				GROUP BY exam_marks.student_id
																			)c
																		) a
																		WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
																	)q WHERE student_id = :studentId
																)j
															) AS last_term_position
														FROM
														(
															SELECT * FROM (
																SELECT
																	student_id, total_mark, total_grade_weight,
																	round((total_mark::float/total_grade_weight::float)*100) as percentage,
																	rank() over w as position, position_out_of
																FROM (
																	SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
																		round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
																		position_out_of
																	FROM(
																		SELECT exam_marks.student_id,
																			coalesce(sum(case when subjects.parent_subject_id is null then
																						mark
																					end),0) as total_mark,
																			coalesce(sum(case when subjects.parent_subject_id is null then
																						grade_weight
																					end),0) as total_grade_weight,
																			(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																					INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																					INNER JOIN app.class_subjects cs USING (class_subject_id)
																					INNER JOIN app.students s USING (student_id)
																					WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE
																					AND cse.exam_type_id = (
																												SELECT exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = :termId
																															AND cs.class_id = :classId
																															ORDER BY em.creation_date DESC
																														)a
																													)b ORDER BY creation_date DESC LIMIT 1
																												)c
																											  )
																			) as position_out_of,
																			(
																										SELECT COUNT(DISTINCT exam_type_id)
																										FROM app.class_subject_exams
																										INNER JOIN app.exam_marks USING (class_sub_exam_id)
																										INNER JOIN app.class_subjects USING (class_subject_id)
																										WHERE term_id = :termId
																										AND class_id = :classId
																										AND class_subject_exams.exam_type_id = (
																												SELECT exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = :termId
																															AND cs.class_id = :classId
																															ORDER BY em.creation_date DESC
																														)a
																													)b ORDER BY creation_date DESC LIMIT 1
																												)c
																											  )
																									) AS class_exam_count

																		FROM app.exam_marks
																		INNER JOIN app.class_subject_exams
																		INNER JOIN app.exam_types USING (exam_type_id)
																		INNER JOIN app.class_subjects
																		INNER JOIN app.subjects
																		ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																		ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																		ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																		INNER JOIN app.students USING (student_id)
																		WHERE class_subjects.class_id = :classId
																		AND term_id = :termId
																		AND subjects.parent_subject_id is null
																		AND subjects.use_for_grading is true
																		AND students.active is true
																		AND mark IS NOT NULL
																		AND class_subject_exams.exam_type_id = (
																												SELECT exam_type_id FROM (
																													SELECT * FROM (
																														SELECT DISTINCT ON (exam_type_id, exam_type) exam_type_id, exam_type, creation_date, term_id, class_id FROM (
																															SELECT em.creation_date, exam_type, cse.exam_type_id, em.term_id, cs.class_id
																															FROM app.exam_marks em
																															INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																															INNER JOIN app.class_subjects cs USING (class_subject_id)
																															INNER JOIN app.exam_types et USING (exam_type_id)
																															WHERE em.term_id = :termId
																															AND cs.class_id = :classId
																															ORDER BY em.creation_date DESC
																														)a
																													)b ORDER BY creation_date DESC LIMIT 1
																												)c
																											  )
																		GROUP BY exam_marks.student_id
																	)c
																) a
																WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
															)p WHERE student_id = :studentId
														)i

												)l
											) AS positions_by_last_exams,
											(
											  SELECT array_to_json(ARRAY_AGG(row_to_json(p))) AS subjects FROM
											  (
												SELECT DISTINCT ON (subject_id) subject_id, subject_name, teacher_id, sort_order, parent_subject_id,
													e.initials
												FROM app.subjects s
												INNER JOIN app.class_subjects cs USING (subject_id)
												INNER JOIN app.class_subject_exams cse USING (class_subject_id)
												INNER JOIN app.exam_marks em USING (class_sub_exam_id)
												LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
												WHERE s.active IS TRUE
												AND cs.class_id = :classId
												AND em.term_id = :termId
												AND s.use_for_grading IS TRUE
											  )p
											) AS subjects_column,
											(
												SELECT report_data::json FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
											) AS report_card_comments,
											(
																SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
															) AS report_card_type,
											(
											SELECT CASE
													WHEN report_card_type IN ('Playgroup','CBC')
													THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
												END AS report_card_data
											FROM (
												SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
											)a
															) AS playgroup_report_card,
											(
											SELECT CASE
													WHEN report_card_type = 'Kindergarten'
													THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
												END AS report_card_data
											FROM (
												SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
											)a
															) AS kindergarten_report_card
			                              FROM (
			                              	SELECT DISTINCT student_id, student_name, term_id, term_name, closing_date, year, next_term_begins, class_name,
			                              		admission_number
			                              	FROM(
																				/*
			                              		SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
			                              				em.term_id, t.term_name, t.end_date AS closing_date, date_part('year', t.start_date) AS year,
			                              				(SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = :termId) LIMIT 1) AS next_term_begins,
			                              				(SELECT class_name FROM app.classes WHERE class_id = :classId) AS class_name, s.admission_number
			                              		FROM app.students s
			                              		LEFT JOIN app.report_cards em USING (student_id) --this is not a mistake
			                              		INNER JOIN app.terms t USING (term_id)
			                              		WHERE em.term_id = :termId
			                              		AND s.student_id = :studentId
																				*/
																				SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
			                              				em.term_id, t.term_name, t.end_date AS closing_date, date_part('year', t.start_date) AS year,
			                              				(SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = :termId) LIMIT 1) AS next_term_begins,
			                              				(SELECT class_name FROM app.classes WHERE class_id = :classId) AS class_name, s.admission_number
			                              		FROM app.students s
			                              		LEFT JOIN app.exam_marks em USING (student_id) /* this is not a mistake */
			                              		INNER JOIN app.terms t USING (term_id)
			                              		WHERE em.term_id = :termId
			                              		AND s.student_id = :studentId
			                              	)a
			                              )d");
					$sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
					$results = $sth->fetch(PDO::FETCH_OBJ);

					$sth4 = $db->prepare("SELECT student_id,
																	case when denominator > 1 then round(total_mark/denominator) else total_mark end as current_term_marks,
																	case when denominator > 1 then round(total_grade_weight/denominator) else '500' end as current_term_marks_out_of, percentage,
																	(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
																	rank AS position, position_out_of
																FROM (
																	SELECT student_id,class_id,total_mark,total_grade_weight,
																		round((total_grade_weight/500)) as denominator,round((total_mark/total_grade_weight)*100) as percentage,
																		rank() over w as rank,position_out_of
																	FROM (
																			SELECT student_id, first_name, middle_name, last_name, class_id, class_name, sum(total_mark) AS total_mark, sum(total_grade_weight) AS total_grade_weight, position_out_of
																			FROM (
																				SELECT student_id, first_name, middle_name, last_name, subject_name, class_id, class_name, round(total_mark::float/3) AS total_mark,
																					round(total_grade_weight/3) AS total_grade_weight, position_out_of
																				FROM (
																					SELECT exam_marks.student_id,first_name,middle_name,last_name,subject_name,class_subjects.class_id,class_subjects.subject_id,class_name,
																						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
																						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
																						(SELECT count(student_id) FROM app.students WHERE active IS TRUE AND current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (select class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))) as position_out_of
																					FROM app.exam_marks
																					INNER JOIN app.students ON exam_marks.student_id = students.student_id
																					INNER JOIN app.class_subject_exams
																					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
																					INNER JOIN app.class_subjects
																					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
																					INNER JOIN app.classes ON class_subjects.class_id = classes.class_id AND classes.active is true
																								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																					WHERE term_id = :termId
																					AND students.active is true AND parent_subject_id IS NULL
																					AND class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id IN (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))
																					GROUP BY exam_marks.student_id, first_name, middle_name, last_name, subject_name, class_subjects.class_id, class_name, class_subjects.subject_id
																				)b
																				GROUP BY student_id, first_name, middle_name, last_name, subject_name, class_id, class_name, position_out_of, total_mark, total_grade_weight
																				ORDER BY student_id ASC
																			)c
																			GROUP BY student_id, first_name, middle_name, last_name, class_id, class_name, position_out_of
																			ORDER BY total_mark DESC
																	) a
																	WINDOW w AS (PARTITION BY position_out_of ORDER BY total_mark desc)
																) q
																WHERE student_id = :studentId");
					$sth4->execute( array(':studentId' => $studentId, ':termId' => $termId) );
					$results2 = $sth4->fetch(PDO::FETCH_OBJ);
					// json_decode($results->exam_marks)
					if($results){
						$results->exam_marks = (isset($results->exam_marks) ? json_decode($results->exam_marks) : null);
						$results->subject_overalls_column = json_decode($results->subject_overalls_column);
						$results->totals = json_decode($results->totals);
						$results->overall_marks_and_grade = json_decode($results->overall_marks_and_grade);
						$results->overall_marks_and_grade_by_last_exam = json_decode($results->overall_marks_and_grade_by_last_exam);
						$results->positions = json_decode($results->positions);
						$results->positions_by_last_exams = json_decode($results->positions_by_last_exams);
						$results->subjects_column = json_decode($results->subjects_column);
						$results->report_card_comments = (isset($report_card_comments) ? json_decode($report_card_comments) : null);
						$results->playgroup_report_card = json_decode($results->playgroup_report_card);
						$results->playgroup_report_card = ($results->playgroup_report_card == null ? null : $results->playgroup_report_card->subjects);
						$results->kindergarten_report_card = json_decode($results->kindergarten_report_card);
						$results->kindergarten_report_card = ($results->kindergarten_report_card == null ? null : $results->kindergarten_report_card->subjects);
						$results->stream_pos = $results2;

						$student_object->report_card = $results;

						array_push($output_object->report_cards,$student_object);
						$student_object = null;
					}

				}

        if($classStudents) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $output_object ));
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

$app->get('/getLiveReportCardData/:student_id/:class_id/:term_id', function ($studentId, $classId, $termId) {
    //Get student report card

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

				$sth = $db->prepare("SELECT d.*,
                              	(
                              		SELECT array_to_json(ARRAY_AGG(c)) FROM
                              		(
                              			SELECT exam_type_id, exam_type, array_to_json(ARRAY_AGG(row_to_json(b))) AS exam_marks
                              			FROM (
																			SELECT student_id, exam_type_id, exam_type, exam_sort, subject_name, subject_id, mark,
																					(SELECT grade FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS grade,
																					(SELECT comment FROM app.grading WHERE percentage >= min_mark AND percentage <= max_mark) AS comment,
																					out_of, subject_sort, parent_subject_id, percentage
																			FROM (
		                              				SELECT e2.student_id, cse2.exam_type_id, et2.exam_type, et2.sort_order AS exam_sort,
		                              						s2.subject_name, s2.subject_id, e2.mark,
																							round((mark/cse2.grade_weight::float)*100) AS percentage,
		                              						cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id
		                              				FROM app.exam_marks e2
		                              				INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
		                              				INNER JOIN app.exam_types et2 USING (exam_type_id)
		                              				INNER JOIN app.class_subjects cs2 USING (class_subject_id)
		                              				INNER JOIN app.subjects s2 USING (subject_id)
		                              				WHERE e2.student_id = :studentId
		                              				AND e2.term_id = :termId AND s2.use_for_grading IS TRUE
		                              				ORDER BY et2.sort_order ASC, s2.sort_order ASC
																			)a
                              			) b
                              			GROUP BY exam_type_id, exam_type
                              		) AS c
                              	) AS exam_marks,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS subject_overalls
										FROM (
											SELECT *, (SELECT grade FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS grade,
														(SELECT comment FROM app.grading WHERE average >= min_mark AND average <= max_mark) AS comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS subject_overalls_column,
								(
									SELECT array_to_json(ARRAY_AGG(j)) AS total_marks FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS total_marks
										FROM (
												SELECT student_id, exam_type_id, exam_type, sum(mark) AS total, sum(out_of) AS out_of,
														(SELECT grade FROM app.grading WHERE round(sum(mark)::float/sum(out_of))*100 >= min_mark AND round(sum(mark)::float/sum(out_of))*100 <= max_mark ) AS grade
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND cs2.class_id = :classId
													AND e2.term_id = :termId AND parent_subject_id IS null AND use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY student_id, exam_type_id, exam_type
										) h
									) AS j
								) AS totals,
								(
									SELECT array_to_json(ARRAY_AGG(j)) FROM
									(
										SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade,
											(
												SELECT ARRAY_AGG(row_to_json(h)) AS this_term_marks_and_grade
												FROM (
													SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
														(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
														(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
													FROM (
														SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
														FROM(
															SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
																	s2.subject_name, s2.subject_id, e2.mark,
																	cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
																	(
																		SELECT COUNT(DISTINCT exam_type_id)
																		FROM app.class_subject_exams
																		INNER JOIN app.exam_marks USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects USING (class_subject_id)
																		WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																		AND class_id = :classId
																	) AS class_exam_count
															FROM app.exam_marks e2
															INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
															INNER JOIN app.exam_types et2 USING (exam_type_id)
															INNER JOIN app.class_subjects cs2 USING (class_subject_id)
															INNER JOIN app.subjects s2 USING (subject_id)
															WHERE e2.student_id = :studentId
															AND e2.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
															ORDER BY et2.sort_order ASC, s2.sort_order ASC
														)f
														GROUP BY subject_id, subject_name, class_exam_count
													)g
												) h
											) AS last_term_marks_and_grade
										FROM (
											SELECT sum(average) || '/500' AS overall_mark, round((sum(average)::float/500)*100) AS percentage,
												(SELECT grade FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS overall_grade,
												(SELECT principal_comment FROM app.grading WHERE round((sum(average)::float/500)*100) >= min_mark AND round((sum(average)::float/500)*100) <= max_mark) AS principal_comment
											FROM (
												SELECT subject_id, subject_name AS parent_subject_name, round(sum(mark)::float/NULLIF(class_exam_count,0)) AS average
												FROM(
													SELECT e2.student_id, cse2.exam_type_id, et2.exam_type,
															s2.subject_name, s2.subject_id, e2.mark,
															cse2.grade_weight AS out_of, s2.sort_order AS subject_sort, s2.parent_subject_id,
															(
																SELECT COUNT(DISTINCT exam_type_id)
																FROM app.class_subject_exams
																INNER JOIN app.exam_marks USING (class_sub_exam_id)
																INNER JOIN app.class_subjects USING (class_subject_id)
																WHERE term_id = :termId
																AND class_id = :classId
															) AS class_exam_count
													FROM app.exam_marks e2
													INNER JOIN app.class_subject_exams cse2 USING (class_sub_exam_id)
													INNER JOIN app.exam_types et2 USING (exam_type_id)
													INNER JOIN app.class_subjects cs2 USING (class_subject_id)
													INNER JOIN app.subjects s2 USING (subject_id)
													WHERE e2.student_id = :studentId
													AND e2.term_id = :termId AND parent_subject_id IS null AND s2.use_for_grading IS TRUE
													ORDER BY et2.sort_order ASC, s2.sort_order ASC
												)f
												GROUP BY subject_id, subject_name, class_exam_count
											)g
										) h
									) AS j
								) AS overall_marks_and_grade,
								(
									SELECT array_to_json(ARRAY_AGG(l)) AS positions FROM
									(
											SELECT row_to_json(i) AS this_term_position,
												(
													SELECT row_to_json(j) AS last_term_position FROM
													(
														SELECT * FROM (
															SELECT
																student_id, total_mark, total_grade_weight,
																round((total_mark::float/total_grade_weight::float)*100) as percentage,
																rank() over w as position, position_out_of
															FROM (
																SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
																	round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
																	position_out_of
																FROM(
																	SELECT exam_marks.student_id,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					mark
																				end),0) as total_mark,
																		coalesce(sum(case when subjects.parent_subject_id is null then
																					grade_weight
																				end),0) as total_grade_weight,
																		(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																				INNER JOIN app.class_subjects cs USING (class_subject_id)
																				INNER JOIN app.students s USING (student_id)
																				WHERE cs.class_id = :classId
																				AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																				AND s.active IS TRUE
																		) as position_out_of,
																		(
																									SELECT COUNT(DISTINCT exam_type_id)
																									FROM app.class_subject_exams
																									INNER JOIN app.exam_marks USING (class_sub_exam_id)
																									INNER JOIN app.class_subjects USING (class_subject_id)
																									WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																									AND class_id = :classId
																								) AS class_exam_count

																	FROM app.exam_marks
																	INNER JOIN app.class_subject_exams
																	INNER JOIN app.exam_types USING (exam_type_id)
																	INNER JOIN app.class_subjects
																	INNER JOIN app.subjects
																	ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
																	INNER JOIN app.students USING (student_id)
																	WHERE class_subjects.class_id = :classId
																	AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
																	AND subjects.parent_subject_id is null
																	AND subjects.use_for_grading is true
																	AND students.active is true
																	AND mark IS NOT NULL

																	GROUP BY exam_marks.student_id
																)c
															) a
															WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
														)q WHERE student_id = :studentId
													)j
												) AS last_term_position
											FROM
											(
												SELECT * FROM (
													SELECT
														student_id, total_mark, total_grade_weight,
														round((total_mark::float/total_grade_weight::float)*100) as percentage,
														rank() over w as position, position_out_of
													FROM (
														SELECT student_id, round(total_mark::float/class_exam_count) AS total_mark,
															round(total_grade_weight::float/class_exam_count) AS total_grade_weight,
															position_out_of
														FROM(
															SELECT exam_marks.student_id,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			mark
																		end),0) as total_mark,
																coalesce(sum(case when subjects.parent_subject_id is null then
																			grade_weight
																		end),0) as total_grade_weight,
																(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
																		INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
																		INNER JOIN app.class_subjects cs USING (class_subject_id)
																		INNER JOIN app.students s USING (student_id)
																		WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE
																) as position_out_of,
																(
																							SELECT COUNT(DISTINCT exam_type_id)
																							FROM app.class_subject_exams
																							INNER JOIN app.exam_marks USING (class_sub_exam_id)
																							INNER JOIN app.class_subjects USING (class_subject_id)
																							WHERE term_id = :termId
																							AND class_id = :classId
																						) AS class_exam_count

															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types USING (exam_type_id)
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects
															ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
															ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
															ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students USING (student_id)
															WHERE class_subjects.class_id = :classId
															AND term_id = :termId
															AND subjects.parent_subject_id is null
															AND subjects.use_for_grading is true
															AND students.active is true
															AND mark IS NOT NULL

															GROUP BY exam_marks.student_id
														)c
													) a
													WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
												)p WHERE student_id = :studentId
											)i

									)l
								) AS positions,
								(
								  SELECT array_to_json(ARRAY_AGG(row_to_json(p))) AS subjects FROM
								  (
									SELECT DISTINCT ON (subject_id) subject_id, subject_name, teacher_id, sort_order, parent_subject_id,
										e.initials
									FROM app.subjects s
									INNER JOIN app.class_subjects cs USING (subject_id)
									INNER JOIN app.class_subject_exams cse USING (class_subject_id)
									INNER JOIN app.exam_marks em USING (class_sub_exam_id)
									LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
									WHERE s.active IS TRUE
									AND cs.class_id = :classId
									AND em.term_id = :termId
									AND s.use_for_grading IS TRUE
								  )p
								) AS subjects_column,
								(
									SELECT report_data::json FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								) AS report_card_comments,
								(
													SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
												) AS report_card_type,
								(
								SELECT CASE
										WHEN report_card_type IN ('Playgroup','CBC')
										THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
									END AS report_card_data
								FROM (
									SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								)a
												) AS playgroup_report_card,
								(
								SELECT CASE
										WHEN report_card_type = 'Kindergarten'
										THEN (SELECT report_data FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId)
									END AS report_card_data
								FROM (
									SELECT report_card_type FROM app.report_cards rc WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId
								)a
												) AS kindergarten_report_card
                              FROM (
                              	SELECT DISTINCT student_id, student_name, term_id, term_name, closing_date, year, next_term_begins, class_name,
                              		admission_number
                              	FROM(
                              		SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                              				em.term_id, t.term_name, t.end_date AS closing_date, date_part('year', t.start_date) AS year,
                              				(SELECT start_date FROM app.terms WHERE start_date > (SELECT end_date FROM app.terms WHERE term_id = :termId) LIMIT 1) AS next_term_begins,
                              				(SELECT class_name FROM app.classes WHERE class_id = :classId) AS class_name, s.admission_number
                              		FROM app.students s
                              		LEFT JOIN app.exam_marks em USING (student_id) /* this is not a mistake */
                              		INNER JOIN app.terms t USING (term_id)
                              		WHERE em.term_id = :termId
                              		AND s.student_id = :studentId
                              	)a
                              )d");
		$sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$results = $sth->fetch(PDO::FETCH_OBJ);
		if($results) {
		$results->exam_marks = (isset($results->exam_marks) ? json_decode($results->exam_marks) : null);;
		$results->subject_overalls_column = json_decode($results->subject_overalls_column);
		$results->totals = json_decode($results->totals);
		$results->overall_marks_and_grade = json_decode($results->overall_marks_and_grade);
		$results->positions = json_decode($results->positions);
		$results->subjects_column = json_decode($results->subjects_column);
		/* these are commented out because "liveReportCard" is for ungenerated report cards so comments don't exist */
		// $results->report_card_comments = json_decode($results->report_card_comments);
		// $results->report_card_comments = ($results->report_card_comments->comments ? null : $results->report_card_comments->comments);
		// $results->playgroup_report_card = json_decode($results->playgroup_report_card);
		// $results->playgroup_report_card = ($results->playgroup_report_card == null ? null : $results->playgroup_report_card->subjects);
		// $results->kindergarten_report_card = json_decode($results->kindergarten_report_card);
		// $results->kindergarten_report_card = ($results->kindergarten_report_card == null ? null : $results->kindergarten_report_card->subjects);



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
								(select comment from app.grading where round((mark::float/grade_weight::float)*100) between min_mark and max_mark) as comment,
								(select kiswahili_comment from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as kiswahili_comment,
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
									(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
									(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
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
		$sth2ByAvg = $db->prepare("SELECT subject_name, total_mark, total_grade_weight, percentage, grade, comment, kiswahili_comment, sort_order, grade as overall_grade2
																	FROM(
																		SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
																		(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
																		(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
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
									(select comment from app.grading where percentage >= min_mark and  percentage <= max_mark) as comment,
									(select kiswahili_comment from app.grading where percentage >= min_mark and  percentage <= max_mark) as kiswahili_comment,
									(SELECT principal_comment FROM app.grading WHERE percentage >= min_mark and  percentage <= max_mark) AS principal_comment,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight,
										ceil(total_mark::float/total_grade_weight::float*100) as percentage,
										rank() over w as rank, position_out_of,

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
											  ,(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
													INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
													INNER JOIN app.class_subjects cs USING (class_subject_id)
													INNER JOIN app.students s USING (student_id)
													WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE) as position_out_of

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

		$sth3ByAverage = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage,
	(SELECT grade FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS grade,
	(SELECT principal_comment FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS principal_comment,
	principal_comment, (SELECT COUNT(DISTINCT student_id) FROM (
								SELECT student_id, mark FROM (
									SELECT em.student_id, em.mark FROM app.exam_marks em
									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
									INNER JOIN app.class_subjects cs USING (class_subject_id)
									WHERE cs.class_id = :classId AND em.term_id = :termId
								)one WHERE mark IS NOT NULL
							)two) AS position_out_of,
	current_term_marks,current_term_marks_out_of
FROM(
	SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, percentages.principal_comment, marks.position_out_of,
		percentages.total_marks_percent as current_term_marks,
		(case
			WHEN positions.current_term_marks_out_of between 0 and 700 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) >= 14 THEN 700
			WHEN positions.current_term_marks_out_of between 701 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) >= 14 THEN 800
			WHEN positions.current_term_marks_out_of between 0 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 13 THEN 800
			WHEN positions.current_term_marks_out_of between 801 and 1200 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 13 THEN 1200
			WHEN (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 12 THEN 1200
			ELSE 500
		end) as current_term_marks_out_of
	FROM
	(SELECT student_id, total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
		(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade, position_out_of
	FROM (
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
			rank() over w as rank, position_out_of,
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
				(SELECT COUNT(DISTINCT student_id) FROM (
					SELECT student_id, mark FROM (
						SELECT em.student_id, em.mark FROM app.exam_marks em
						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
						INNER JOIN app.class_subjects cs USING (class_subject_id)
						WHERE cs.class_id = :classId AND em.term_id = :termId
					)one WHERE mark IS NOT NULL
				)two) as position_out_of
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
	(SELECT student_id, round(avg(percentage)) AS percentage, sum(percentage) as total_marks_percent, (SELECT grade FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS grade, (SELECT principal_comment FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS principal_comment
	FROM (
		SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM (
			SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
				SELECT class_id,subject_id,subject_name,student_id,round(total_mark::float/count) as total_mark,total_grade_weight,sort_order FROM (
					SELECT class_id, subject_id, subject_name, student_id, sum(total_mark) AS total_mark, round(avg(total_grade_weight)) AS total_grade_weight, sort_order, count FROM (
						SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
							coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
							coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
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
						AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId
						AND mark IS NOT NULL
						GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, count, class_subject_exams.exam_type_id
						ORDER BY sort_order ASC
					)b
					GROUP BY class_id, subject_id, subject_name, student_id, sort_order, count
				)a
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
					INNER JOIN app.students s USING (student_id)
					WHERE class_subjects.class_id = :classId AND s.active IS TRUE
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
		$sth4 = $db->prepare("SELECT total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
		(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
		(select comment from app.grading where percentage >= min_mark and  percentage <= max_mark) as comment,
		(select kiswahili_comment from app.grading where percentage >= min_mark and  percentage <= max_mark) as kiswahili_comment,
		(SELECT principal_comment FROM app.grading WHERE percentage >= min_mark and  percentage <= max_mark) AS principal_comment,
		position_out_of
	FROM (
		SELECT
			student_id, total_mark, total_grade_weight,
			ceil(total_mark::float/total_grade_weight::float*100) as percentage,
			rank() over w as rank, position_out_of,

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
				  ,(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
					INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
					INNER JOIN app.class_subjects cs USING (class_subject_id)
					WHERE cs.class_id = :classId AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )) as position_out_of

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
			AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
			AND subjects.parent_subject_id is null
			AND subjects.use_for_grading is true
			AND students.active is true
			AND mark IS NOT NULL

			AND class_subject_exams.exam_type_id = (SELECT exam_type_id FROM (
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

		// get overall position last term by average
		$sth40 = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage,
		(SELECT grade FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS grade,
		(SELECT principal_comment FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS principal_comment,
		principal_comment, (SELECT COUNT(DISTINCT student_id) FROM (
								SELECT student_id, mark FROM (
									SELECT em.student_id, em.mark FROM app.exam_marks em
									INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
									INNER JOIN app.class_subjects cs USING (class_subject_id)
									WHERE cs.class_id = :classId AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
								)one WHERE mark IS NOT NULL
							)two) AS position_out_of,
		current_term_marks,current_term_marks_out_of
	FROM(
		SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, percentages.principal_comment, marks.position_out_of,
			percentages.total_marks_percent as current_term_marks,
			(case
				WHEN positions.current_term_marks_out_of between 0 and 700 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) >= 14 THEN 700
				WHEN positions.current_term_marks_out_of between 701 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) >= 14 THEN 800
				WHEN positions.current_term_marks_out_of between 0 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 13 THEN 800
				WHEN positions.current_term_marks_out_of between 801 and 1200 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 13 THEN 1200
				WHEN (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 12 THEN 1200
				ELSE 500
			end) as current_term_marks_out_of
		FROM
		(SELECT student_id, total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
			(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade, position_out_of
		FROM (
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
				rank() over w as rank, position_out_of,
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
					(SELECT COUNT(DISTINCT student_id) FROM (
						SELECT student_id, mark FROM (
							SELECT em.student_id, em.mark FROM app.exam_marks em
							INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
							INNER JOIN app.class_subjects cs USING (class_subject_id)
							WHERE cs.class_id = :classId AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
						)one WHERE mark IS NOT NULL
					)two) as position_out_of
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
		(SELECT student_id, round(avg(percentage)) AS percentage, sum(percentage) as total_marks_percent, (SELECT grade FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS grade, (SELECT principal_comment FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS principal_comment
		FROM (
			SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM (
				SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
					SELECT class_id,subject_id,subject_name,student_id,round(total_mark::float/count) as total_mark,total_grade_weight,sort_order FROM (
						SELECT class_id, subject_id, subject_name, student_id, sum(total_mark) AS total_mark, round(avg(total_grade_weight)) AS total_grade_weight, sort_order, count FROM (
							SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
								coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
								coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
								subjects.sort_order, class_subject_exams.exam_type_id,
								(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = :classId AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )) as count
							FROM app.exam_marks
							INNER JOIN app.class_subject_exams
							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							INNER JOIN app.class_subjects
							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
							INNER JOIN app.students s USING (student_id)
							WHERE class_subjects.class_id = :classId AND s.active IS TRUE
							AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId
							AND mark IS NOT NULL
							GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, count, class_subject_exams.exam_type_id
							ORDER BY sort_order ASC
						)b
						GROUP BY class_id, subject_id, subject_name, student_id, sort_order, count
					)a
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
							(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = :classId AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )) as count
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students s USING (student_id)
						WHERE class_subjects.class_id = :classId AND s.active IS TRUE
						AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 ) AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND mark IS NOT NULL
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
		$sth40->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$overallLastTermByAverage = $sth40->fetch(PDO::FETCH_OBJ);

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
														SELECT student_id, exam_type_id, sum(average_grade) as average_grade, exam_type, sort_order FROM (
															SELECT  em.student_id, cse.exam_type_id, (case when s.parent_subject_id is null and s.use_for_grading is true then mark end) AS average_grade, et.exam_type, et.sort_order
															FROM app.exam_marks  em
															LEFT JOIN app.class_subject_exams cse USING (class_sub_exam_id)
															LEFT JOIN app.exam_types et USING (exam_type_id)
															LEFT JOIN app.class_subjects cs USING (class_subject_id)
															LEFT JOIN app.subjects s USING(subject_id)
															WHERE em.term_id = :termId
															ORDER BY em.student_id DESC
														) foo
														GROUP BY exam_type,exam_type_id,student_id, sort_order
													)level1 WHERE average_grade is not null and student_id= :studentId order by sort_order ASC");
		$sth5->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$graphPoints = $sth5->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->details = $details;
		$results->subjectOverall = $subjectOverall;
		$results->subjectOverallByAvg = $subjectOverallByAvg;
		$results->overall = $overall;
		$results->overallByAverage = $overallByAverage;
		$results->overallLastTerm = $overallLastTerm;
		$results->overallLastTermByAverage = $overallLastTermByAverage;
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

$app->get('/getSpecialExamMarksforReportCard/:student_id/:class/:term(/:teacherId)', function ($studentId,$classId,$termId,$teacherId=null) {
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
								(select comment from app.grading where round((mark::float/grade_weight::float)*100) between min_mark and max_mark) as comment,
								(select kiswahili_comment from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as kiswahili_comment,
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
						AND exam_types.is_special_exam IS TRUE
						";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId) ";
			$params[':teacherId'] = $teacherId;
		}
		$query .= "ORDER BY subjects.sort_order, exam_types.exam_type_id";

		$sth = $db->prepare($query);
		$sth->execute( $params );
		$specialDetails = $sth->fetchAll(PDO::FETCH_OBJ);


		// get overall marks per subjects, only use parent subjects
		$sth2 = $db->prepare("SELECT  subject_name,
									total_mark,
									total_grade_weight,
									ceil(total_mark::float/total_grade_weight::float*100) as percentage,
									(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
									(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
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
									AND exam_types.is_special_exam IS TRUE
									GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading
								) q
							 ORDER BY sort_order  ");
		$sth2->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$specialSubjectOverall = $sth2->fetchAll(PDO::FETCH_OBJ);

		// get overall marks (determined by average of exams done)
		$sth2ByAvg = $db->prepare("SELECT subject_name, total_mark, total_grade_weight, percentage, grade, comment, kiswahili_comment, sort_order, grade as overall_grade2
																	FROM(
																		SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
																		(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
																		(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
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
																						AND exam_types.is_special_exam IS TRUE
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
		$specialSubjectOverallByAvg = $sth2ByAvg->fetchAll(PDO::FETCH_OBJ);

		// get overall position
		$sth3 = $db->prepare("SELECT total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
									(select comment from app.grading where percentage >= min_mark and  percentage <= max_mark) as comment,
									(select kiswahili_comment from app.grading where percentage >= min_mark and  percentage <= max_mark) as kiswahili_comment,
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
											  ,(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
												INNER JOIN app.class_subjects cs USING (class_subject_id)
												INNER JOIN app.students s USING (student_id)
												WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE) as position_out_of

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
										AND exam_types.is_special_exam IS TRUE
										AND class_subject_exams.exam_type_id = (SELECT exam_type_id FROM (
                                                                                SELECT DISTINCT ON (cse.exam_type_id, et.exam_type, em.student_id,  et.sort_order ) cse.exam_type_id, et.exam_type, em.student_id,  et.sort_order FROM app.exam_marks em
                                                                                INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
                                                                                INNER JOIN app.exam_types et USING (exam_type_id)
                                                                                INNER JOIN app.class_subjects cs USING (class_subject_id)
                                                                                WHERE em.term_id = :termId AND cs.class_id = :classId AND em.student_id = :studentId
																																								AND et.is_special_exam IS TRUE
                                                                                ORDER BY et.sort_order DESC
                                                                                )one ORDER BY sort_order DESC LIMIT 1)

										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
								 ) q
								 where student_id = :studentId");
		$sth3->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
		$specialOverall = $sth3->fetch(PDO::FETCH_OBJ);

		// get overall position (exactly same as above) but current term marks by the average

		$sth3ByAverage = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage,
	(SELECT grade FROM app.grading WHERE round((current_term_marks::float/current_term_marks_out_of::float)*100) between min_mark and max_mark) AS grade,
	principal_comment, (SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
						INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
						INNER JOIN app.class_subjects cs USING (class_subject_id)
						INNER JOIN app.students s USING (student_id)
						WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE) AS position_out_of,
	current_term_marks,current_term_marks_out_of
FROM(
	SELECT marks.total_mark, marks.total_grade_weight, positions.rank, percentages.percentage, percentages.grade, percentages.principal_comment, marks.position_out_of,
		percentages.total_marks_percent as current_term_marks,
		(case
			WHEN positions.current_term_marks_out_of between 0 and 700 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) >= 14 THEN 700
			WHEN positions.current_term_marks_out_of between 701 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) >= 14 THEN 800
			WHEN positions.current_term_marks_out_of between 0 and 800 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 13 THEN 800
			WHEN positions.current_term_marks_out_of between 801 and 1200 and (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 13 THEN 1200
			WHEN (select entity_id from app.class_cats where class_cat_id=(select class_cat_id from app.classes where class_id=:classId)) = 12 THEN 1200
			ELSE 500
		end) as current_term_marks_out_of
	FROM
	(SELECT student_id, total_mark/num_exam_types as total_mark, total_grade_weight/num_exam_types as total_grade_weight, rank, percentage,
		(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade, position_out_of
	FROM (
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
					AND exam_types.is_special_exam IS TRUE
					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id
				) q
				GROUP BY q.sort_order,q.subject_name
				ORDER BY sort_order
				) a
			)) as percentage,
			rank() over w as rank, position_out_of,
			/*commented by tom for a quick hack, remember to remove*/
			(SELECT COUNT(*) FROM (
				SELECT DISTINCT exam_type_id FROM app.exam_marks
				INNER JOIN app.class_subject_exams
				INNER JOIN app.class_subjects ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
				INNER JOIN app.exam_types USING (exam_type_id)
				WHERE student_id = a.student_id
				AND exam_types.is_special_exam IS TRUE
				AND class_subjects.class_id = :classId AND term_id = :termId
				) AS temp
			), 1 as num_exam_types
		FROM (
			SELECT exam_marks.student_id,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
				coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
				(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
				INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
				INNER JOIN app.class_subjects cs USING (class_subject_id)
				INNER JOIN app.students s USING (student_id)
				WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE) as position_out_of
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
			AND exam_types.is_special_exam IS TRUE
			/*hack by tom, remember to remove*/
			AND class_subject_exams.exam_type_id = (SELECT  exam_type_id FROM app.exam_types WHERE exam_type_id=(select distinct exam_type_id from app.class_subject_exams cse inner join app.exam_marks em on cse.class_sub_exam_id=em.class_sub_exam_id where em.student_id=:studentId and em.term_id=:termId order by exam_type_id DESC LIMIT 1) AND exam_types.is_special_exam IS TRUE) GROUP BY exam_marks.student_id
		) a WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)
	) q WHERE student_id = :studentId) AS marks
	FULL OUTER JOIN
	(SELECT student_id, round(avg(percentage)) AS percentage, sum(percentage) as total_marks_percent, (SELECT grade FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS grade, (SELECT principal_comment FROM app.grading WHERE round(avg(percentage)) between min_mark and max_mark) AS principal_comment
	FROM (
		SELECT subject_name, total_mark, total_grade_weight, percentage, sort_order, student_id FROM (
			SELECT  subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage, sort_order, student_id FROM (
				SELECT class_id,subject_id,subject_name,student_id,round(total_mark::float/count) as total_mark,total_grade_weight,sort_order FROM (
					SELECT class_id, subject_id, subject_name, student_id, sum(total_mark) AS total_mark, round(avg(total_grade_weight)) AS total_grade_weight, sort_order, count FROM (
						SELECT class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,
							coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
							coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
							subjects.sort_order, class_subject_exams.exam_type_id,
							(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id INNER JOIN app.exam_types USING (exam_type_id) WHERE cs.class_id = :classId AND term_id = :termId AND exam_types.is_special_exam IS TRUE) as count
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId
						AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND student_id = :studentId
						AND mark IS NOT NULL
						AND exam_types.is_special_exam IS TRUE
						GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, count, class_subject_exams.exam_type_id
						ORDER BY sort_order ASC
					)b
					GROUP BY class_id, subject_id, subject_name, student_id, sort_order, count
				)a
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
						(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id INNER JOIN app.exam_types USING (exam_type_id) WHERE cs.class_id = :classId AND term_id = :termId AND exam_types.is_special_exam IS TRUE) as count
					FROM app.exam_marks
					INNER JOIN app.class_subject_exams
					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
					INNER JOIN app.class_subjects
					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
					WHERE class_subjects.class_id = :classId
					AND term_id = :termId AND subjects.parent_subject_id is null AND subjects.use_for_grading is true AND mark IS NOT NULL
					AND exam_types.is_special_exam IS TRUE
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
		$specialOverallByAverage = $sth3ByAverage->fetch(PDO::FETCH_OBJ);

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
											  	,(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
													INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
													INNER JOIN app.class_subjects cs USING (class_subject_id)
													WHERE cs.class_id = :classId AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )) as position_out_of
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
		$specialOverallLastTerm = $sth4->fetch(PDO::FETCH_OBJ);

        //get average grade per CAT to plot a graph for the term
		$sth5 = $db->prepare("SELECT * FROM (
														SELECT student_id, exam_type_id, sum(average_grade) as average_grade, exam_type, sort_order FROM (
															SELECT  em.student_id, cse.exam_type_id, (case when s.parent_subject_id is null and s.use_for_grading is true then mark end) AS average_grade, et.exam_type, et.sort_order
															FROM app.exam_marks  em
															LEFT JOIN app.class_subject_exams cse USING (class_sub_exam_id)
															LEFT JOIN app.exam_types et USING (exam_type_id)
															LEFT JOIN app.class_subjects cs USING (class_subject_id)
															LEFT JOIN app.subjects s USING(subject_id)
															WHERE em.term_id = :termId
															AND et.is_special_exam IS TRUE
															ORDER BY em.student_id DESC
														) foo
														GROUP BY exam_type,exam_type_id,student_id, sort_order
													)level1 WHERE average_grade is not null and student_id= :studentId order by sort_order ASC");
		$sth5->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$specialGraphPoints = $sth5->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->specailDetails = $specialDetails;
		$results->specialSubjectOverall = $specialSubjectOverall;
		$results->specialSubjectOverallByAvg = $specialSubjectOverallByAvg;
		$results->specialOverall = $specialOverall;
		$results->specialOverallByAverage = $specialOverallByAverage;
		$results->specialOverallLastTerm = $specialOverallLastTerm;
		$results->specialGraphPoints = $specialGraphPoints;

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
											  ,(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
												INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
												INNER JOIN app.class_subjects cs USING (class_subject_id)
												INNER JOIN app.students s USING (student_id)
												WHERE cs.class_id = :classId AND em.term_id = :termId AND s.active IS TRUE) as position_out_of

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
											  	,(SELECT COUNT(DISTINCT(em.student_id)) AS student_id from app.exam_marks em
													INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
													INNER JOIN app.class_subjects cs USING (class_subject_id)
													WHERE cs.class_id = :classId AND em.term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )) as position_out_of
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
		$sth7 = $db->prepare("SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
		case when denominator > 1 then round(total_mark/denominator) else total_mark end as current_term_marks,
		case when denominator > 1 then round(total_grade_weight/denominator) else '500' end as current_term_marks_out_of, percentage,
		(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
		rank AS position, position_out_of
	FROM (
		SELECT student_id,first_name,middle_name,last_name,class_id,class_name,total_mark,total_grade_weight,
			round((total_grade_weight/500)) as denominator,round((total_mark/total_grade_weight)*100) as percentage,
			rank() over w as rank,position_out_of
		FROM (
				SELECT student_id, first_name, middle_name, last_name, class_id, class_name, sum(total_mark) AS total_mark, sum(total_grade_weight) AS total_grade_weight, position_out_of
				FROM (
					SELECT student_id, first_name, middle_name, last_name, subject_name, class_id, class_name, round(total_mark::float/3) AS total_mark,
						round(total_grade_weight/3) AS total_grade_weight, position_out_of
					FROM (
						SELECT exam_marks.student_id,first_name,middle_name,last_name,subject_name,class_subjects.class_id,class_subjects.subject_id,class_name,
							coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
							coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
							(SELECT count(student_id) FROM app.students WHERE active IS TRUE AND current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (select class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))) as position_out_of
						FROM app.exam_marks
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
						INNER JOIN app.classes ON class_subjects.class_id = classes.class_id AND classes.active is true
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
									ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE term_id = :termId
						AND students.active is true AND parent_subject_id IS NULL
						AND class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id IN (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))
						GROUP BY exam_marks.student_id, first_name, middle_name, last_name, subject_name, class_subjects.class_id, class_name, class_subjects.subject_id
					)b
					GROUP BY student_id, first_name, middle_name, last_name, subject_name, class_id, class_name, position_out_of, total_mark, total_grade_weight
					ORDER BY student_id ASC
				)c
				GROUP BY student_id, first_name, middle_name, last_name, class_id, class_name, position_out_of
				ORDER BY total_mark DESC
		) a
		WINDOW w AS (PARTITION BY position_out_of ORDER BY total_mark desc)
	) q
	WHERE student_id = :studentId");
		$sth7->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$streamRank = $sth7->fetchAll(PDO::FETCH_OBJ);

		// stream positions by the last exam done
		$sth7ByLastExam = $db->prepare("SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
	case when denominator > 1 then round(total_mark/denominator) else total_mark end as current_term_marks,
	case when denominator > 1 then round(total_grade_weight/denominator) else '500' end as current_term_marks_out_of,
	rank AS position, percentage,
	(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
	position_out_of
FROM (
	SELECT student_id,first_name,middle_name,last_name,class_id,class_name,total_mark,total_grade_weight,
		round((total_grade_weight/500)) as denominator,round((total_mark/total_grade_weight)*100) as percentage,
		rank() over w as rank,position_out_of
	FROM (
		SELECT student_id, first_name, middle_name, last_name, class_id, class_name, round(sum(total_mark)/3) AS total_mark,
			round(sum(total_grade_weight)/3) AS total_grade_weight, position_out_of
		FROM (
			SELECT exam_marks.student_id,first_name,middle_name,last_name,class_subjects.class_id,class_name,
				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
				coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
				(SELECT count(student_id) FROM app.students WHERE active IS TRUE AND current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (select class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))) as position_out_of,
				exam_types.exam_type_id
			FROM app.exam_marks
			INNER JOIN app.students ON exam_marks.student_id = students.student_id
			INNER JOIN app.class_subject_exams
			INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
			INNER JOIN app.class_subjects
			INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
			INNER JOIN app.classes ON class_subjects.class_id = classes.class_id AND classes.active is true
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
			WHERE term_id = :termId
			AND students.active is true
			AND class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id IN (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))
			GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name, exam_types.exam_type_id
		)b
		GROUP BY student_id, first_name, middle_name, last_name, class_id, class_name, position_out_of
		ORDER BY total_mark DESC
	) a
	WINDOW w AS (PARTITION BY position_out_of ORDER BY total_mark desc)
) q
WHERE student_id = :studentId");
		$sth7ByLastExam->execute(  array(':studentId' => $studentId, ':termId' => $termId) );
		$streamRankByLastExam = $sth7ByLastExam->fetchAll(PDO::FETCH_OBJ);

		// stream positions last term
		$sth7LstTm = $db->prepare("SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
		case when denominator > 1 then round(total_mark/denominator) else total_mark end as current_term_marks,
		case when denominator > 1 then round(total_grade_weight/denominator) else '500' end as current_term_marks_out_of,
		rank AS position, percentage,
		(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
		position_out_of
	FROM (
		SELECT student_id,first_name,middle_name,last_name,class_id,class_name,total_mark,total_grade_weight,
			round((total_grade_weight/500)) as denominator,round((total_mark/total_grade_weight)*100) as percentage,
			rank() over w as rank,position_out_of
		FROM (
			SELECT student_id, first_name, middle_name, last_name, class_id, class_name, round(sum(total_mark)/3) AS total_mark,
				round(sum(total_grade_weight)/3) AS total_grade_weight, position_out_of
			FROM (
				SELECT exam_marks.student_id,first_name,middle_name,last_name,class_subjects.class_id,class_name,
					coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
					coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
					(SELECT count(student_id) FROM app.students WHERE active IS TRUE AND current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (select class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))) as position_out_of,
					exam_types.exam_type_id
				FROM app.exam_marks
				INNER JOIN app.students ON exam_marks.student_id = students.student_id
				INNER JOIN app.class_subject_exams
				INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
				INNER JOIN app.class_subjects
				INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
				INNER JOIN app.classes ON class_subjects.class_id = classes.class_id AND classes.active is true
							ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
							ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
				WHERE term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
				AND students.active is true
				AND class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id IN (SELECT class_cat_id FROM app.classes WHERE class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)))))
				GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name, exam_types.exam_type_id
			)b
			GROUP BY student_id, first_name, middle_name, last_name, class_id, class_name, position_out_of
			ORDER BY total_mark DESC
		) a
		WINDOW w AS (PARTITION BY position_out_of ORDER BY total_mark desc)
	) q
	WHERE student_id = :studentId");
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

$app->post('/updateReportCardData', function () use($app) {
  // update report card(s)
	// FLOW:
	// 1. Update each students subject marks (or insert if it doesn't exist)
	// 2. Calculate the overall subject marks received above eg AVERAGE OF CAT 1 + CAT 2 + CAT 3
	// 3. When the above process is done for all students, update the class positions and overall marks

	$allPostVars = json_decode($app->request()->getBody(),true);
	$examMarks =	( isset($allPostVars['exam_marks']) ? $allPostVars['exam_marks']: null);

  try
  {
		// print_r(json_encode($examMarks));
		// loop through the above arr to access each student's data
		foreach($examMarks as $mark)
		{
			$studentId = ( isset($mark['student_id']) ? $mark['student_id']: null);
			$classId = ( isset($mark['class_id']) ? $mark['class_id']: null);
			$className = ( isset($mark['class_name']) ? $mark['class_name']: null);
			$termId = ( isset($mark['term_id']) ? $mark['term_id']: null);
			$termName = ( isset($mark['term_name']) ? $mark['term_name']: null);
			$examTypeId = ( isset($mark['subject_exam_type_id']) ? $mark['subject_exam_type_id']: null);
			$examTypeName = ( isset($mark['subject_exam_type']) ? $mark['subject_exam_type']: null);
			$subjectId = ( isset($mark['subject_id']) ? $mark['subject_id']: null);
			$subjectName = ( isset($mark['subject_name']) ? $mark['subject_name']: null);
			$parentSubjectName = ( isset($mark['parent_subject_name']) ? $mark['parent_subject_name']: null);
			$parentSubjectId = ( isset($mark['parent_subject_id']) ? $mark['parent_subject_id']: null);
			$isParent = ( isset($mark['is_parent']) ? $mark['is_parent']: null);
			$mark = ( isset($mark['mark']) && !empty($mark['mark']) ? $mark['mark']: null);
			$gradeWeight = ( isset($mark['grade_weight']) && !empty($mark['grade_weight']) ? $mark['grade_weight']: null);
			$grade = ( isset($mark['grade']) ? $mark['grade']: null);
			$useForGrading = ( isset($mark['use_for_grading']) ? $mark['grade']: null);
			$teacherId = ( isset($mark['subject_teacher_id']) ? $mark['subject_teacher_id']: null);
			$teacherInitials = ( isset($mark['teacher_initials']) ? $mark['teacher_initials']: null);
			$classTeacherId = ( isset($mark['class_teacher_id']) ? $mark['class_teacher_id']: null);
			$classTeacherName = ( isset($mark['class_teacher_name']) ? $mark['class_teacher_name']: null);
			$sortOrder = ( isset($mark['sort_order']) ? $mark['sort_order']: null);

			$db = getDB();

			$insertOrUpdate = $db->prepare("SELECT CASE
																				WHEN EXISTS (SELECT reportcard_data_id FROM app.reportcard_data WHERE student_id = :studentId AND subject_id = :subjectId AND exam_type_id = :examTypeId AND term_id = :termId)
																				THEN 'update'
																				ELSE 'insert'
																			END AS status");

			$insert = $db->prepare("INSERT INTO app.reportcard_data(
																student_id,
																class_id,
																class_name,
																term_id,
																term_name,
																exam_type_id,
																exam_type,
																subject_id,
																subject_name,
																parent_subject_name,
																mark,
																grade_weight,
																grade,
																use_for_grading,
																teacher_id,
																teacher_initials,
																creation_date)
																VALUES (:studentId, :classId, :className,
																				:termId, :termName, :examTypeId,
																				:examTypeName, :subjectId, :subjectName,
																				:parentSubjectName, :mark, :gradeWeight,
																				:grade,
																				:useForGrading, :teacherId,
																				:teacherInitials, now())");

			$update = $db->prepare("UPDATE app.reportcard_data
										SET subject_name = :subjectName,
											parent_subject_name = :parentSubjectName,
											mark = :mark,
											grade_weight = :gradeWeight,
											grade = :grade /* (SELECT CASE WHEN :mark IS NULL
																				    THEN NULL
																				    ELSE
																				    (SELECT grade FROM app.grading WHERE (:mark::float/:gradeWeight::float)*100 between min_mark and max_mark)
																				    END
																				) */,
											use_for_grading = :useForGrading,
											teacher_id = :teacherId,
											teacher_initials = :teacherInitials,
											modified_date = now()
										WHERE student_id = :studentId
										AND term_id = :termId
										AND subject_id = :subjectId
										AND exam_type_id = :examTypeId");

			$db->beginTransaction();

			$insertOrUpdate->execute( array(':studentId' => $studentId, ':subjectId' => $subjectId, ':examTypeId' => $examTypeId, ':termId' => $termId ) );
			$proceedStatus = $insertOrUpdate->fetch(PDO::FETCH_OBJ);

			if( $proceedStatus->status === 'update' )
			{
				$update->execute( array(':subjectName' => $subjectName,
				                        ':parentSubjectName' => $parentSubjectName,
				                        ':mark' => $mark,
				                        ':gradeWeight' => $gradeWeight,
				                        ':grade' => $grade,
				                        ':useForGrading' => $useForGrading,
				                        ':teacherId' => $teacherId,
				                        ':teacherInitials' => $teacherInitials,
				                        ':studentId' => $studentId,
				                        ':termId' => $termId,
				                        ':subjectId' => $subjectId,
				                        ':examTypeId' => $examTypeId
				                    ) );
			}
			elseif ( $proceedStatus->status === 'insert' )
			{
				$insert->execute( array(':studentId' => $studentId,
										':classId' => $classId,
										':className' => $className,
										':termId' => $termId,
										':termName' => $termName,
										':examTypeId' => $examTypeId,
										':examTypeName' => $examTypeName,
										':subjectId' => $subjectId,
										':subjectName' => $subjectName,
										':parentSubjectName' => $parentSubjectName,
										':mark' => $mark,
										':gradeWeight' => $gradeWeight,
										':grade' => $grade,
										':useForGrading' => $useForGrading,
										':teacherId' => $teacherId,
										':teacherInitials' => $teacherInitials
									) );
			}
	    $db->commit();
		}

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1, "data" => json_encode($examMarks), "message" => "Exam data has been successfully saved for the report cards."));
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
