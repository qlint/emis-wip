<?php
$app->get('/getClassAnalysis/:classId/:examType/:term', function ($classId,$examTypeId,$termId) {
  // Compute class analysis for upper classes (4-8)

  $app = \Slim\Slim::getInstance();

  try
  {
    // need to make sure class, term and type are integers
	if( is_numeric($classId) && is_numeric($termId)  && is_numeric($examTypeId) )
	{
        $db = getDB();

        $query = "select app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name
							  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
							  ,exam_type,exam_marks.student_id,mark,grade_weight,subjects.sort_order
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
						INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
						                        ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						                        ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						WHERE class_subjects.class_id = $classId
						AND term_id = $termId
						AND class_subject_exams.exam_type_id = $examTypeId
						AND subjects.use_for_grading is true
						AND students.active is true
						WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
						',
						array['gender','student_id','student_name','exam_type'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";
        $query2 = "select *,
									(
										SELECT rank FROM (
											SELECT student_id, total_mark, rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
												INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
												INNER JOIN app.class_subjects
												INNER JOIN app.subjects
													ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId AND class_subject_exams.exam_type_id = $examTypeId
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as rank,
									(
										SELECT total_mark FROM (
											SELECT student_id, total_mark, rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
												INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
												INNER JOIN app.class_subjects
												INNER JOIN app.subjects
													ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId AND class_subject_exams.exam_type_id = $examTypeId
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as total_mark
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
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStreamAnalysis/:classId/:examType/:term', function ($classId,$examTypeId,$termId) {
  // Compute class analysis for upper classes (4-8)

  $app = \Slim\Slim::getInstance();

  try
  {
    // need to make sure class, term and type are integers
	if( is_numeric($classId) && is_numeric($termId)  && is_numeric($examTypeId) )
	{
        $db = getDB();

        $query = "select app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name
							  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
							  ,exam_type,exam_marks.student_id,mark,grade_weight,subjects.sort_order
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects
						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
						INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
						                        ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						                        ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes INNER JOIN app.class_cats USING (class_cat_id) WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId)))
						AND term_id = $termId
            AND exam_types.sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId)
						AND subjects.use_for_grading is true
						AND students.active is true
						WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
						',
						array['gender','student_id','student_name','exam_type'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";
        $query2 = "select *,
									(
										SELECT rank FROM (
											SELECT student_id, total_mark, rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
												INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
												INNER JOIN app.class_subjects
												INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes INNER JOIN app.class_cats USING (class_cat_id) WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId)))
												AND term_id = $termId AND exam_types.sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId)
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as rank,
									(
										SELECT total_mark FROM (
											SELECT student_id, total_mark, rank() over w as rank
											FROM (
												SELECT exam_marks.student_id,
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams
												INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
												INNER JOIN app.class_subjects
												INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												    ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes INNER JOIN app.class_cats USING (class_cat_id) WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId)))
												AND term_id = $termId AND exam_types.sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = $examTypeId)
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as total_mark
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
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getClassMean/:classId/:termId/:examTypeId', function ($classId,$termId,$examTypeId) {
  // Compute class subjects mean score

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT class_name, subject_name, mean FROM (
										SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
												SELECT table1.*, table2.* FROM (
													SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
														SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,classes.class_name,
															 subject_name,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'') as parent_subject_name,
															  exam_type,exam_marks.student_id,mark,grade_weight,subjects.sort_order
														FROM app.exam_marks
														INNER JOIN app.class_subject_exams
														INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
														INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
														INNER JOIN app.class_subjects
														INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
														INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
														INNER JOIN app.students ON exam_marks.student_id = students.student_id
														WHERE class_subjects.class_id = :classId
														AND exam_marks.term_id = :termId
														AND class_subject_exams.exam_type_id = :examTypeId
														AND subjects.use_for_grading is true
														AND students.active is true
														WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
													)one
													GROUP BY class_id, class_name, subject_name, exam_type, sort_order
													ORDER BY sort_order
												) AS table1
												INNER JOIN
												(
												SELECT class_id as class_id2, subject_name as subject_name2, sort_order as sort_order2, exam_type as exam_type2, count(mark) FROM (
																	SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,subject_name,
																		coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'') as parent_subject_name,exam_type,
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
																	WHERE class_subjects.class_id = :classId
																	AND exam_marks.term_id = :termId
																	AND class_subject_exams.exam_type_id = :examTypeId
																	AND subjects.use_for_grading is true
																	AND students.active is true
																	WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
																)a WHERE mark is not null GROUP BY a.class_id, a.subject_name, a.sort_order, a.exam_type ORDER BY sort_order
												) AS table2
												ON table1.class_id = table2.class_id2 AND table1.subject_name = table2.subject_name2 AND table1.exam_type = table2.exam_type2
											)table3
										)table4
										GROUP BY class_id, class_name, subject_name, sort_order
										ORDER BY class_name DESC, sort_order ASC
										)table5
                                    ORDER BY 1");
    $sth->execute(array(':classId' => $classId, ':termId' => $termId, ':examTypeId' => $examTypeId));
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

$app->get('/getStreamMean/:classId/:termId/:examTypeId', function ($classId,$termId,$examTypeId) {
  // Compute stream subjects mean score

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT class_name, subject_name, mean FROM (
										SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
												SELECT table1.*, table2.* FROM (
													SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
														SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,classes.class_name,
															 subject_name,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'') as parent_subject_name,
															  exam_type,exam_marks.student_id,mark,grade_weight,subjects.sort_order
														FROM app.exam_marks
														INNER JOIN app.class_subject_exams
														INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
														INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
														INNER JOIN app.class_subjects
														INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
														INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
																	ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																	ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
														INNER JOIN app.students ON exam_marks.student_id = students.student_id
														WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes INNER JOIN app.class_cats USING (class_cat_id) WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId)))
														AND exam_marks.term_id = :termId
														AND exam_types.sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = :examTypeId)
														AND subjects.use_for_grading is true
														AND students.active is true
														WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
													)one
													GROUP BY class_id, class_name, subject_name, exam_type, sort_order
													ORDER BY sort_order
												) AS table1
												INNER JOIN
												(
												SELECT class_id as class_id2, subject_name as subject_name2, sort_order as sort_order2, exam_type as exam_type2, count(mark) FROM (
																	SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,subject_name,
																		coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'') as parent_subject_name,exam_type,
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes INNER JOIN app.class_cats USING (class_cat_id) WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId)))
																	AND exam_marks.term_id = :termId
																	AND exam_types.sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = :examTypeId)
																	AND subjects.use_for_grading is true
																	AND students.active is true
																	WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
																)a WHERE mark is not null GROUP BY a.class_id, a.subject_name, a.sort_order, a.exam_type ORDER BY sort_order
												) AS table2
												ON table1.class_id = table2.class_id2 AND table1.subject_name = table2.subject_name2 AND table1.exam_type = table2.exam_type2
											)table3
										)table4
										GROUP BY class_id, class_name, subject_name, sort_order
										ORDER BY class_name DESC, sort_order ASC
										)table5
                                    ORDER BY 1");
    $sth->execute(array(':classId' => $classId, ':termId' => $termId, ':examTypeId' => $examTypeId));
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

$app->get('/getOverallFinancials/:termId', function ($termId) {
  // Overall school balances

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT all_fee_items as fee_item, sum(default_amt) as total_due, sum(paid2) as total_paid, (sum(default_amt) - sum(paid2)) as balance FROM (
                      	SELECT student_name, '#' || ' ' || inv_id as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2, (default_amt - paid2) as balance FROM (
                      		SELECT DISTINCT ON (student_name, inv_id, inv_date, fee_item_id, all_fee_items, default_amt, paid2) student_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2 FROM (
                      			SELECT student_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, p_id, default_amt, sum(paid) over (partition by student_name, fee_item_id, inv_id order by student_name, fee_item_id, inv_id DESC) as paid2 FROM (
                      				SELECT all_stdnt_names as student_name, all_invs as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, all_dflt_amt as default_amt, coalesce(paid,0) as paid, (all_dflt_amt - coalesce(paid,0)) as balance, p_id FROM (
                      					SELECT term, all_stdnt_id, all_stdnt_names, all_invs, inv_date, tot_inv_amt, fee_item_id, all_fee_items, all_dflt_amt, paid_inv, paid_fee_item, p_id, paid FROM (
                      						SELECT * FROM
                      						(
                      						SELECT invoices.inv_id as all_invs, fee_item as all_fee_items, student_fee_items.fee_item_id, /*student_fee_items.amount*/invoice_line_items.amount as all_dflt_amt, /*payment_inv_items.amount as paid,*/ invoices.student_id as all_stdnt_id,
                      							students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS all_stdnt_names,
                      							invoices.inv_date, invoices.total_amount as tot_inv_amt, invoices.canceled, terms.term_name as term, invoices.term_id
                      						FROM app.invoices
                      						INNER JOIN app.terms ON invoices.term_id = terms.term_id
                      						INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
                      						INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                      						INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                      						--INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
                      						INNER JOIN app.students ON invoices.student_id = students.student_id
                      						WHERE invoices.term_id = :termId
                      						AND students.active IS TRUE AND fee_items.active IS TRUE
                                            AND invoices.canceled IS FALSE
                      						ORDER BY all_stdnt_names ASC, all_invs DESC
                      						) AS one
                      						FULL OUTER JOIN
                      						(
                      						SELECT invoices.inv_id as paid_inv, fee_item as paid_fee_item, student_fee_items.fee_item_id as fee_item_id2, /*student_fee_items.amount*/invoice_line_items.amount as dflt_amt, payment_inv_items.payment_id as p_id, payment_inv_items.amount as paid, invoices.student_id,
                      							students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                      							invoices.inv_date as inv_date2, invoices.total_amount, invoices.canceled, terms.term_name, invoices.term_id
                      						FROM app.invoices
                      						INNER JOIN app.terms ON invoices.term_id = terms.term_id
                      						INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
                      						INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                      						INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                      						INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
                      						INNER JOIN app.students ON invoices.student_id = students.student_id
                      						WHERE invoices.term_id = :termId
                      						AND students.active IS TRUE AND fee_items.active IS TRUE
                                            AND invoices.canceled IS FALSE
                      						ORDER BY student_name ASC, paid_inv DESC
                      						) AS two
                      						ON one.all_invs = two.paid_inv AND one.fee_item_id = two.fee_item_id2
                      					)three
                      				)four
                      			)five ORDER BY student_name ASC, fee_item_id ASC
                      		)six
                      	)seven
                      )eight GROUP BY all_fee_items ORDER BY all_fee_items ASC");
    $sth->execute(array(':termId' => $termId));
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

$app->get('/getOverallStudentFeePayments/:termId', function ($termId) {
  // Overall school balances

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT student_name, '#' || ' ' || inv_id as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2, (default_amt - paid2) as balance FROM (
                        	SELECT DISTINCT ON (student_name, inv_id, inv_date, fee_item_id, all_fee_items, default_amt, paid2) student_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2 FROM (
                        		SELECT student_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, p_id, default_amt, sum(paid) over (partition by student_name, fee_item_id, inv_id order by student_name, fee_item_id, inv_id DESC) as paid2 FROM (
                        			SELECT all_stdnt_names as student_name, all_invs as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, all_dflt_amt as default_amt, coalesce(paid,0) as paid, (all_dflt_amt - coalesce(paid,0)) as balance, p_id FROM (
                        				SELECT term, all_stdnt_id, all_stdnt_names, all_invs, inv_date, tot_inv_amt, fee_item_id, all_fee_items, all_dflt_amt, paid_inv, paid_fee_item, p_id, paid FROM (
                        					SELECT * FROM
                        					(
                            					SELECT invoices.inv_id as all_invs, fee_item as all_fee_items, student_fee_items.fee_item_id, /*student_fee_items.amount*/invoice_line_items.amount as all_dflt_amt, /*payment_inv_items.amount as paid,*/ invoices.student_id as all_stdnt_id,
                            						students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS all_stdnt_names,
                            						invoices.inv_date, invoices.total_amount as tot_inv_amt, invoices.canceled, terms.term_name as term, invoices.term_id
                            					FROM app.invoices
                            					INNER JOIN app.terms ON invoices.term_id = terms.term_id
                            					INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
                            					INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                            					INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                            					--INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
                            					INNER JOIN app.students ON invoices.student_id = students.student_id
                            					WHERE invoices.term_id = :termId
                            					AND students.active IS TRUE AND fee_items.active IS TRUE
                                                AND invoices.canceled IS FALSE
                            					ORDER BY all_stdnt_names ASC, all_invs DESC
                        					) AS one
                        					FULL OUTER JOIN
                        					(
                            					SELECT invoices.inv_id as paid_inv, fee_item as paid_fee_item, student_fee_items.fee_item_id as fee_item_id2, /*student_fee_items.amount*/invoice_line_items.amount as dflt_amt, payment_inv_items.payment_id as p_id, payment_inv_items.amount as paid, invoices.student_id,
                            						students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                            						invoices.inv_date as inv_date2, invoices.total_amount, invoices.canceled, terms.term_name, invoices.term_id
                            					FROM app.invoices
                            					INNER JOIN app.terms ON invoices.term_id = terms.term_id
                            					INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
                            					INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                            					INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                            					INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
                            					INNER JOIN app.students ON invoices.student_id = students.student_id
                            					WHERE invoices.term_id = :termId
                            					AND students.active IS TRUE AND fee_items.active IS TRUE
                                                AND invoices.canceled IS FALSE
                            					ORDER BY student_name ASC, paid_inv DESC
                        					) AS two
                        					ON one.all_invs = two.paid_inv AND one.fee_item_id = two.fee_item_id2
                        				)three
                        			)four
                        		)five ORDER BY student_name ASC, fee_item_id ASC
                        	)six
                        )seven");
    $sth->execute(array(':termId' => $termId));
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

$app->get('/getSomeReport', function () {
  //Some report

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("");
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


?>
