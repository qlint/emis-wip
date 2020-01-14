<?php
$app->get('/getClassAnalysis/:classId/:term', function ($classId,$termId) {
  // Compute class analysis for upper classes (4-8)

  $app = \Slim\Slim::getInstance();

  try
  {
    // need to make sure class, term and type are integers
	if( is_numeric($classId) && is_numeric($termId) )
	{
        $db = getDB();

        $query = "select app.colpivot('_exam_marks', 'SELECT student_id, student_name, gender, subject_name, total_mark AS mark, total_grade_weight AS grade_weight, sort_order, parent_subject_name
		FROM(
			SELECT  student_id, student_name, gender, subject_name, parent_subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
			(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
			(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
				(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
			FROM (
				SELECT student_id, student_name, gender, class_id,subject_id,subject_name,parent_subject_name,total_mark,total_grade_weight,sort_order FROM (
					SELECT student_id, student_name, gender, class_id,subject_id,subject_name, parent_subject_name,
						round(sum(total_mark)::float/count) as total_mark,
						round(avg(total_grade_weight)) as total_grade_weight,sort_order
					FROM
						(
							SELECT exam_marks.student_id, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name AS student_name, gender, class_id,class_subjects.subject_id,subject_name,
								mark as total_mark,
								grade_weight as total_grade_weight,subjects.sort_order,
								coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name,
								(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = $classId AND term_id = $termId) as count
							FROM app.exam_marks
							INNER JOIN app.class_subject_exams
							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							INNER JOIN app.class_subjects
							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
							INNER JOIN app.students s USING (student_id)
							WHERE class_subjects.class_id = $classId
							AND term_id = $termId
							AND subjects.use_for_grading is true
							AND s.active IS TRUE
							AND mark IS NOT NULL
							ORDER BY sort_order ASC
						)b
						GROUP BY class_id, subject_name, parent_subject_name, student_id, student_name, gender, subject_id, sort_order, count
						ORDER BY sort_order ASC
				)a
				ORDER BY sort_order ASC
			) q
			ORDER BY sort_order
		)v ORDER BY student_name ASC
',
array['gender','student_id','student_name'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";
        $query2 = "select *,
		(
			SELECT rank FROM (
				SELECT student_id, total_mark, rank() over w as rank FROM (
									SELECT student_id, sum(total_mark) AS total_mark FROM (
										SELECT student_id, student_name, total_mark FROM(
											SELECT  student_id, student_name, gender, subject_name, parent_subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
											(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
											(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
												(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
											FROM (
												SELECT student_id, student_name, gender, class_id,subject_id,subject_name,parent_subject_name,total_mark,total_grade_weight,sort_order FROM (
													SELECT student_id, student_name, gender, class_id,subject_id,subject_name, parent_subject_name,
														round(sum(total_mark)::float/count) as total_mark,
														round(avg(total_grade_weight)) as total_grade_weight,sort_order
													FROM
														(
															SELECT exam_marks.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, gender, class_id,class_subjects.subject_id,subject_name,
																mark as total_mark,
																grade_weight as total_grade_weight,subjects.sort_order,
																(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
																(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = $classId AND term_id = $termId) as count
															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																		ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																		ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students s USING (student_id)
															WHERE class_subjects.class_id = $classId
															AND term_id = $termId
															AND subjects.use_for_grading is true
															AND s.active IS TRUE
															AND mark IS NOT NULL
															ORDER BY sort_order ASC
														)b
														GROUP BY class_id, subject_name, parent_subject_name, student_id, student_name, gender, subject_id, sort_order, count
														ORDER BY sort_order ASC
												)a
												ORDER BY sort_order ASC
											) q
											ORDER BY sort_order
										)v WHERE total_grade_weight = 100
										ORDER BY student_id ASC
									)f GROUP BY student_id
								)x WINDOW w AS (ORDER BY total_mark desc)
			) q
			WHERE student_id = _exam_marks.student_id
		) as rank,
		(
			SELECT total_mark FROM (
				SELECT student_id, sum(total_mark) AS total_mark FROM (
										SELECT student_id, student_name, total_mark FROM(
											SELECT  student_id, student_name, gender, subject_name, parent_subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
											(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
											(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
												(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
											FROM (
												SELECT student_id, student_name, gender, class_id,subject_id,subject_name,parent_subject_name,total_mark,total_grade_weight,sort_order FROM (
													SELECT student_id, student_name, gender, class_id,subject_id,subject_name, parent_subject_name,
														round(sum(total_mark)::float/count) as total_mark,
														round(avg(total_grade_weight)) as total_grade_weight,sort_order
													FROM
														(
															SELECT exam_marks.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, gender, class_id,class_subjects.subject_id,subject_name,
																mark as total_mark,
																grade_weight as total_grade_weight,subjects.sort_order,
																(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
																(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = $classId AND term_id = $termId) as count
															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																		ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																		ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students s USING (student_id)
															WHERE class_subjects.class_id = $classId
															AND term_id = $termId
															AND subjects.use_for_grading is true
															AND s.active IS TRUE
															AND mark IS NOT NULL
															ORDER BY sort_order ASC
														)b
														GROUP BY class_id, subject_name, parent_subject_name, student_id, student_name, gender, subject_id, sort_order, count
														ORDER BY sort_order ASC
												)a
												ORDER BY sort_order ASC
											) q
											ORDER BY sort_order
										)v WHERE total_grade_weight = 100
										ORDER BY student_id ASC
									)f GROUP BY student_id
			) q
			WHERE student_id = _exam_marks.student_id
		) as total_mark
		from _exam_marks order by rank;";

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

$app->get('/getStreamAnalysis/:classId/:term', function ($classId,$termId) {
  // Compute class analysis for upper classes (4-8)

  $app = \Slim\Slim::getInstance();

  try
  {
    // need to make sure class, term and type are integers
	if( is_numeric($classId) && is_numeric($termId) )
	{
        $db = getDB();

        $query = "select app.colpivot('_exam_marks', 'SELECT student_id, student_name, gender, subject_name, total_mark AS mark, total_grade_weight AS grade_weight, sort_order, parent_subject_name
		FROM(
			SELECT  student_id, student_name, gender, subject_name, parent_subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
			(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
			(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
				(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
			FROM (
				SELECT student_id, student_name, gender, class_id,subject_id,subject_name,parent_subject_name,total_mark,total_grade_weight,sort_order FROM (
					SELECT student_id, student_name, gender, class_id,subject_id,subject_name, parent_subject_name,
						round(sum(total_mark)::float/count) as total_mark,
						round(avg(total_grade_weight)) as total_grade_weight,sort_order
					FROM
						(
							SELECT exam_marks.student_id, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name AS student_name, gender, class_id,class_subjects.subject_id,subject_name,
								mark as total_mark,
								grade_weight as total_grade_weight,subjects.sort_order,
								coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name,
								(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id IN (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId)))) AND term_id = $termId) as count
							FROM app.exam_marks
							INNER JOIN app.class_subject_exams
							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							INNER JOIN app.class_subjects
							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
							INNER JOIN app.students s USING (student_id)
							WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id IN (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))))
							AND term_id = $termId
							AND subjects.use_for_grading is true
							AND s.active IS TRUE
							AND mark IS NOT NULL
							ORDER BY sort_order ASC
						)b
						GROUP BY class_id, subject_name, parent_subject_name, student_id, student_name, gender, subject_id, sort_order, count
						ORDER BY sort_order ASC
				)a
				ORDER BY sort_order ASC
			) q
			ORDER BY sort_order
		)v ORDER BY student_name ASC
',
array['gender','student_id','student_name'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";
        $query2 = "select *,
		(
			SELECT rank FROM (
				SELECT student_id, total_mark, rank() over w as rank FROM (
									SELECT student_id, sum(total_mark) AS total_mark FROM (
										SELECT student_id, student_name, total_mark FROM(
											SELECT  student_id, student_name, gender, subject_name, parent_subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
											(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
											(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
												(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
											FROM (
												SELECT student_id, student_name, gender, class_id,subject_id,subject_name,parent_subject_name,total_mark,total_grade_weight,sort_order FROM (
													SELECT student_id, student_name, gender, class_id,subject_id,subject_name, parent_subject_name,
														round(sum(total_mark)::float/count) as total_mark,
														round(avg(total_grade_weight)) as total_grade_weight,sort_order
													FROM
														(
															SELECT exam_marks.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, gender, class_id,class_subjects.subject_id,subject_name,
																mark as total_mark,
																grade_weight as total_grade_weight,subjects.sort_order,
																(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
																(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id IN (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId)))) AND term_id = $termId) as count
															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																		ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																		ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students s USING (student_id)
															WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id IN (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))))
															AND term_id = $termId
															AND subjects.use_for_grading is true
															AND s.active IS TRUE
															AND mark IS NOT NULL
															ORDER BY sort_order ASC
														)b
														GROUP BY class_id, subject_name, parent_subject_name, student_id, student_name, gender, subject_id, sort_order, count
														ORDER BY sort_order ASC
												)a
												ORDER BY sort_order ASC
											) q
											ORDER BY sort_order
										)v WHERE total_grade_weight = 100
										ORDER BY student_id ASC
									)f GROUP BY student_id
								)x WINDOW w AS (ORDER BY total_mark desc)
			) q
			WHERE student_id = _exam_marks.student_id
		) as rank,
		(
			SELECT total_mark FROM (
				SELECT student_id, sum(total_mark) AS total_mark FROM (
										SELECT student_id, student_name, total_mark FROM(
											SELECT  student_id, student_name, gender, subject_name, parent_subject_name, total_mark, total_grade_weight, round(total_mark::float/total_grade_weight::float*100) as percentage,
											(select comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as comment,
											(select kiswahili_comment from app.grading where round((total_mark::float/total_grade_weight::float)*100) between min_mark and max_mark) as kiswahili_comment,
												(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
											FROM (
												SELECT student_id, student_name, gender, class_id,subject_id,subject_name,parent_subject_name,total_mark,total_grade_weight,sort_order FROM (
													SELECT student_id, student_name, gender, class_id,subject_id,subject_name, parent_subject_name,
														round(sum(total_mark)::float/count) as total_mark,
														round(avg(total_grade_weight)) as total_grade_weight,sort_order
													FROM
														(
															SELECT exam_marks.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, gender, class_id,class_subjects.subject_id,subject_name,
																mark as total_mark,
																grade_weight as total_grade_weight,subjects.sort_order,
																(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
																(SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id IN (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId)))) AND term_id = $termId) as count
															FROM app.exam_marks
															INNER JOIN app.class_subject_exams
															INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
															INNER JOIN app.class_subjects
															INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
																		ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
																		ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
															INNER JOIN app.students s USING (student_id)
															WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id IN (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $classId))))
															AND term_id = $termId
															AND subjects.use_for_grading is true
															AND s.active IS TRUE
															AND mark IS NOT NULL
															ORDER BY sort_order ASC
														)b
														GROUP BY class_id, subject_name, parent_subject_name, student_id, student_name, gender, subject_id, sort_order, count
														ORDER BY sort_order ASC
												)a
												ORDER BY sort_order ASC
											) q
											ORDER BY sort_order
										)v WHERE total_grade_weight = 100
										ORDER BY student_id ASC
									)f GROUP BY student_id
			) q
			WHERE student_id = _exam_marks.student_id
		) as total_mark
		from _exam_marks order by rank;";

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

$app->get('/getAllStudentsWithTransport', function () {
  // Get all students taking transport

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT * FROM (
                        	SELECT DISTINCT ON (s.student_id)s.student_id, admission_number, first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                        		c.class_name, route, CASE WHEN s.destination IS NULL THEN 'Not Assigned' ELSE s.destination END AS neighborhood
                        	FROM app.students s
                        	INNER JOIN app.classes c ON s.current_class = c.class_id
                        	INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                        	INNER JOIN app.student_fee_items sfi USING (student_id)
                        	INNER JOIN app.fee_items USING (fee_item_id)
                          WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                        )a
                        ORDER BY class_name ASC, student_name ASC");
    $sth->execute(array());
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

$app->get('/getAllStudentsInTrip/:tripId', function ($tripId) {
  // All students in trip

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT two.*, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e2.last_name AS driver_name, e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e.last_name AS guide_name FROM (
                        	SELECT student_id, admission_number, student_name, class_name, trip_id, trip_name, bus_type || ' - ' || bus_registration AS bus, bus_driver, bus_guide, student_destination, route FROM (
                        		SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, class_name,
                        			UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS trip_id, s.destination AS student_destination, tr.route
                        		FROM app.students s
                        		INNER JOIN app.classes c ON s.current_class = c.class_id
                            INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                        		WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                        	)one
                        	INNER JOIN app.schoolbus_bus_trips sbt ON one.trip_id = sbt.bus_trip_id
                        	INNER JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                        	INNER JOIN app.buses USING (bus_id)
                        	WHERE sbt.schoolbus_trip_id = :tripId
                        )two
                        LEFT JOIN app.employees e ON two.bus_driver = e.emp_id
                        LEFT JOIN app.employees e2 ON two.bus_guide = e2.emp_id
                        ORDER BY class_name ASC, student_name ASC");
    $sth->execute(array(':tripId' => $tripId));
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

$app->get('/getAllStudentsInTranspZone', function () {
  // Get all students in their transport zones

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT student_id, admission_number, student_name, class_name, student_destination, route, amount FROM (
                    		SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, class_name,
                    			s.destination AS student_destination, s.transport_route_id
                    		FROM app.students s
                    		INNER JOIN app.classes c ON s.current_class = c.class_id
                    		WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                    	)one
                    	INNER JOIN app.transport_routes tr ON one.transport_route_id = tr.transport_id
                    	ORDER BY class_name ASC, student_name ASC");
    $sth->execute(array());
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

$app->get('/getAllStudentsWithTranspBalance', function () {
  // Get all students with transport balace

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT *, amount-payment AS balance FROM (
                        	SELECT sfi.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, class_name, destination,
                        		sfi.fee_item_id, fi.fee_item, tr.route, fi.default_amount, sfi.amount, COALESCE(pii.amount, 0) AS payment
                        	FROM app.student_fee_items sfi
                        	INNER JOIN app.fee_items fi USING (fee_item_id)
                        	INNER JOIN app.students s USING (student_id)
                        	INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                        	LEFT JOIN app.invoice_line_items ili USING (student_fee_item_id)
                        	LEFT JOIN app.payment_inv_items pii USING (inv_item_id)
                        	INNER JOIN app.classes c ON s.current_class = c.class_id
                          WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                        )one
                        ORDER BY class_name ASC, student_name ASC");
    $sth->execute(array());
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

$app->get('/getClassStudentsWithTransp/:classCatId', function ($classCatId) {
  // All students with transport in class

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT * FROM (
                        	SELECT DISTINCT ON (s.student_id)s.student_id, admission_number, first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                        		c.class_name, route, CASE WHEN s.destination IS NULL THEN 'Not Assigned' ELSE s.destination END AS neighborhood
                        	FROM app.students s
                        	INNER JOIN app.classes c ON s.current_class = c.class_id
                        	INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                        	INNER JOIN app.student_fee_items sfi USING (student_id)
                        	INNER JOIN app.fee_items USING (fee_item_id)
                        	WHERE s.current_class IN (SELECT class_id FROM app.classes c WHERE c.class_cat_id = :classCatId)
                          AND s.active IS TRUE AND s.transport_route_id IS NOT NULL
                        )a
                        ORDER BY class_name ASC, student_name ASC");
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

$app->get('/getClassStudentsInBus/:busId/:classCatId', function ($busId,$classCatId) {
  // Get class students in the given school bus

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT one.*, b.bus_id, b.bus_type || ' - ' || b.bus_registration AS bus, b.destinations, b.bus_driver,
                						b.bus_guide, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as driver_name,
                						e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name, st.trip_name,
                						st.class_cats
                					FROM (
                						SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name, class_name, current_class,
                                          				s.destination AS student_destination, UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS student_trips, tr.route
                                                                  FROM app.students s
                                                                  INNER JOIN app.classes c ON s.current_class = c.class_id
                                                                  INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                                                                  WHERE s.current_class IN (SELECT class_id FROM app.classes c WHERE c.class_cat_id = :classCatId)
                                                                  AND s.active IS TRUE AND s.transport_route_id IS NOT NULL
                					)one
                					INNER JOIN app.schoolbus_bus_trips sbt ON one.student_trips = sbt.bus_trip_id
                                                        INNER JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                                                        INNER JOIN app.buses b USING (bus_id)
                                                        LEFT JOIN app.employees e ON b.bus_driver = e.emp_id
                					LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
                					WHERE sbt.bus_id = :busId
                					ORDER BY class_name ASC, student_name ASC");
    $sth->execute( array(':busId' => $busId, ':classCatId' => $classCatId) );
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

$app->get('/getClassStudentsInTrip/:tripId/:classCatId', function ($tripId,$classCatId) {
  // Get class students in the trip

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT two.*, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e2.last_name AS driver_name, e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e.last_name AS guide_name FROM (
                          	SELECT student_id, admission_number, student_name, class_name, trip_id, trip_name, bus_type || ' - ' || bus_registration AS bus, bus_driver, bus_guide, student_destination, route FROM (
                          		SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, class_name,
                          			UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS trip_id, s.destination AS student_destination, tr.route
                          		FROM app.students s
                          		INNER JOIN app.classes c ON s.current_class = c.class_id
                              INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                          		WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                              AND s.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = :classCatId)
                          	)one
                          	INNER JOIN app.schoolbus_bus_trips sbt ON one.trip_id = sbt.bus_trip_id
                          	INNER JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                          	INNER JOIN app.buses USING (bus_id)
                          	WHERE sbt.schoolbus_trip_id = :tripId
                          )two
                          LEFT JOIN app.employees e ON two.bus_driver = e.emp_id
                          LEFT JOIN app.employees e2 ON two.bus_guide = e2.emp_id
                          ORDER BY class_name ASC, student_name ASC");
    $sth->execute( array(':tripId' => $tripId, ':classCatId' => $classCatId) );
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

$app->get('/getAllStudentsInBusInTrip/:busId/:tripId', function ($busId,$tripId) {
  // Get class students in the trip

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT two.*, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e2.last_name AS driver_name, e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e.last_name AS guide_name FROM (
                          	SELECT student_id, admission_number, student_name, class_name, trip_id, trip_name, bus_type || ' - ' || bus_registration AS bus, bus_driver, bus_guide, student_destination, route FROM (
                          		SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, class_name,
                          			UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS trip_id, s.destination AS student_destination, tr.route
                          		FROM app.students s
                          		INNER JOIN app.classes c ON s.current_class = c.class_id
                          		INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                          		WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                          	)one
                          	INNER JOIN app.schoolbus_bus_trips sbt ON one.trip_id = sbt.bus_trip_id
                          	INNER JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                          	INNER JOIN app.buses USING (bus_id)
				WHERE sbt.schoolbus_trip_id = :tripId
                          	AND sbt.bus_id = :busId
                          )two
                          LEFT JOIN app.employees e ON two.bus_driver = e.emp_id
                          LEFT JOIN app.employees e2 ON two.bus_guide = e2.emp_id
                          ORDER BY class_name ASC, student_name ASC");
    $sth->execute( array(':tripId' => $tripId, ':busId' => $busId) );
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

$app->get('/getClassStudentsInBusInTrip/:classCatId/:busId/:tripId', function ($classCatId,$busId,$tripId) {
  // Get class students in the trip

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT two.*, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e2.last_name AS driver_name, e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e.last_name AS guide_name FROM (
                          	SELECT student_id, admission_number, student_name, class_name, trip_id, trip_name, bus_type || ' - ' || bus_registration AS bus, bus_driver, bus_guide, student_destination, route FROM (
                          		SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, class_name,
                          			UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS trip_id, s.destination AS student_destination, tr.route
                          		FROM app.students s
                          		INNER JOIN app.classes c ON s.current_class = c.class_id
                              INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                          		WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                              AND s.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = :classCatId)
                          	)one
                          	INNER JOIN app.schoolbus_bus_trips sbt ON one.trip_id = sbt.bus_trip_id
                          	INNER JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                          	INNER JOIN app.buses USING (bus_id)
				            WHERE sbt.schoolbus_trip_id = :tripId
                          	AND sbt.bus_id = :busId
                          )two
                          LEFT JOIN app.employees e ON two.bus_driver = e.emp_id
                          LEFT JOIN app.employees e2 ON two.bus_guide = e2.emp_id
                          ORDER BY class_name ASC, student_name ASC");
    $sth->execute( array(':tripId' => $tripId, ':classCatId' => $classCatId, ':busId' => $busId) );
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

$app->get('/getClassStudentsInTranspZone/:classCatId', function ($classCatId) {
  // Get class students in the transport zone

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT student_id, admission_number, student_name, class_name, student_destination, route, amount FROM (
                    		SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, class_name,
                    			s.destination AS student_destination, s.transport_route_id
                    		FROM app.students s
                    		INNER JOIN app.classes c ON s.current_class = c.class_id
                    		WHERE s.active IS TRUE AND s.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = :classCatId) AND s.transport_route_id IS NOT NULL
                    	)one
                    	INNER JOIN app.transport_routes tr ON one.transport_route_id = tr.transport_id
                    	ORDER BY class_name ASC, student_name ASC");
    $sth->execute( array(':classCatId' => $classCatId) );
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

$app->get('/getExamDeviations/:class_id/:term_id/:exam_type_id', function ($classId,$termId,$examTypeId) {

	// Get all exam types

	$app = \Slim\Slim::getInstance();

	try
	{
		$db = getDB();

		$sth = $db->prepare("SELECT gender, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,subject_name,
								exam_type,exam_marks.student_id,mark,grade_weight,subjects.sort_order, admission_number, class_name
							FROM app.exam_marks
							INNER JOIN app.class_subject_exams
							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							INNER JOIN app.class_subjects
							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
							INNER JOIN app.students ON exam_marks.student_id = students.student_id
							WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId))))
							AND term_id = :term_id
							AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId))) AND sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = :exam_type_id))
							AND subjects.use_for_grading is true
							AND students.active is true AND subjects.parent_subject_id IS NULL
							WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY first_name ASC, middle_name ASC, last_name ASC, subjects.sort_order ASC, mark desc)");
		$sth->execute(array(':classId' => $classId, ':term_id' => $termId, ':exam_type_id' => $examTypeId));
		$currentExamResults = $sth->fetchAll(PDO::FETCH_OBJ);

		$sth2 = $db->prepare("SELECT gender, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,subject_name,
								exam_type,exam_marks.student_id,mark,grade_weight,subjects.sort_order, admission_number, class_name
							FROM app.exam_marks
							INNER JOIN app.class_subject_exams
							INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							INNER JOIN app.class_subjects
							INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
										ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
										ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
							INNER JOIN app.students ON exam_marks.student_id = students.student_id
							WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId))))
							AND term_id = :term_id
							AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId))) AND sort_order = ((SELECT sort_order FROM app.exam_types WHERE exam_type_id = :exam_type_id)-1))
							AND subjects.use_for_grading is true
							AND students.active is true AND subjects.parent_subject_id IS NULL
							WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY first_name ASC, middle_name ASC, last_name ASC, subjects.sort_order ASC, mark desc)");
		$sth2->execute(array(':classId' => $classId, ':term_id' => $termId, ':exam_type_id' => $examTypeId));
		$previousExamResults = $sth2->fetchAll(PDO::FETCH_OBJ);

		$results =  new stdClass();
		$results->currentExamResults = $currentExamResults;
		$results->previousExamResults = $previousExamResults;

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

$app->get('/getGradesAttainment/:classId/:termId/:examTypeId', function ($classId,$termId,$examTypeId) {
	// Get class students in the transport zone
  
	$app = \Slim\Slim::getInstance();
  
	try
	{
	  $db = getDB();
  
	   $sth = $db->prepare("SELECT class_name, grade, COUNT(grade) AS grade_count FROM (
								SELECT class_name, student_id, coalesce(total,0) AS total, out_of, 
									(SELECT grade FROM app.grading WHERE round((coalesce(total,0)::float/out_of)*100) between min_mark and max_mark) AS grade
								FROM (
									SELECT class_id, class_name, student_id, sum(mark) AS total, sum(out_of) AS out_of FROM (
										SELECT c.class_id, c.class_name, em.student_id, em.term_id, em.mark, cse.grade_weight AS out_of
										FROM app.exam_marks em
										INNER JOIN app.class_subject_exams cse USING (class_sub_exam_id)
										INNER JOIN app.class_subjects cs USING (class_subject_id)
										INNER JOIN app.classes c USING (class_id)
										INNER JOIN app.class_cats cc USING (class_cat_id)
										WHERE cc.entity_id = (SELECT entity_id FROM app.class_cats WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId))
										AND term_id = :termId
										AND cse.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE sort_order = (SELECT sort_order FROM app.exam_types WHERE exam_type_id = :examTypeId) AND class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = :classId))
										AND cse.grade_weight = 100
									)one
									GROUP BY student_id, class_id, class_name
									ORDER BY total DESC
								)two
								ORDER BY total DESC
							)three
							GROUP BY class_name, grade");
	  $sth->execute( array(':classId' => $classId, ':termId' => $termId, ':examTypeId' => $examTypeId) );
	  $gradesAttainment = $sth->fetchAll(PDO::FETCH_OBJ);

	  $sth2 = $db->prepare("SELECT grade FROM app.grading");
	  $sth2->execute( array() );
	  $schoolGrades = $sth2->fetchAll(PDO::FETCH_OBJ);

	  $results =  new stdClass();
	  $results->gradesAttainment = $gradesAttainment;
	  $results->schoolGrades = $schoolGrades;
  
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
