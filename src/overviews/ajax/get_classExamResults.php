<?php
    error_reporting(E_ALL);
    
    include("db.php");
    
    // Process the received data
    $params = $_POST['param_ids'];
    $data = json_decode($params,true);
    $term = $data["term"];
    $class = $data["class"];
    $exam = $data["exam"];
    
    // Run the query
	
    	/* -------------------------EXAM MARKS QUERY------------------------- */
    	
    	$examResults = pg_query($db,"SELECT app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,
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
                                                				WHERE class_subjects.class_id = $class AND term_id = $term 
                                                				AND class_subject_exams.exam_type_id = $exam AND subjects.use_for_grading is true
                                                				AND students.active is true
                                                				WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)',
                                                		array['gender','student_name'], array['parent_subject_name','subject_name'], '#.mark', null);
                                                SELECT *, ( SELECT rank FROM (
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
                                                				WHERE class_subjects.class_id = $class AND term_id = $term AND class_subject_exams.exam_type_id = $exam
                                                				AND students.active IS TRUE
                                                				GROUP BY exam_marks.student_id, students.first_name, students.middle_name, students.last_name
                                                			) a
                                                			WINDOW w AS (ORDER BY total_mark DESC)
                                                		) q
                                                		WHERE student_name = _exam_marks.student_name
                                                	) AS rank
                                                FROM _exam_marks ORDER BY rank;");
                                    
    	/* We want to write the string to a txt file and have php read from there */
	    /* This is because dataTables refresh the page on data load hence other JS variables are lost */
	    $fileName = __DIR__ . "/docTitles/" . array_shift((explode('.', $_SERVER['HTTP_HOST']))) . "_docTitle.txt";
	    $myFile = fopen($fileName, "w") or die("Unable to open file!");
	    
	    /* This is a temporary header that will be overwritten on form submit */
        fwrite($myFile, "Please Select A Term, Class and Exam");
        fclose($myFile);
        
    	/* echo "<tr class='row100 body'>"; */
    	$row = pg_fetch_assoc($examResults);
    	
    	foreach ($row as $column => $value) {
    	    $results = $column . '-' . $value;
    	    echo $results;
            print_r($results);
        }
        
?>