<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?> mean analysis</title>
    <!-- Bootstrap core CSS -->
    <link rel="icon" type="image/png" href="../components/overviewFiles/images/icons/favicon.ico"/>
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/bootstrap/css/bootstrap.min.css">
  	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
  	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/animate/animate.css">
  	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/select2/select2.min.css">
  	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/perfect-scrollbar/perfect-scrollbar.css">
  	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/util.css">
  	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/main.css">
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/buttons.dataTables.min.css">
    <!-- Custom styles for this template -->
    <link href="css/1-col-portfolio.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/newlightgirls-mean.css">
    <!-- <script src="template/scripts/jquery-1.12.4.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script> -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Mean (x&#772;) Analysis</span></a>
      <a class="navbar-brand"></a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item active">
            <a class="nav-link" href="<?php echo htmlspecialchars("http://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke"); ?>">Home
              <span class="sr-only">(current)</span>
            </a>
          </li>
          <li class="nav-item active">
            <a class="nav-link" href="<?php echo htmlspecialchars("http://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke/overviews"); ?>" style="color:#0cff05;">Go Back
              <span class="sr-only">(current)</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
    <div class="limiter" style="margin-top: -80px;">
      <div class="container-table100">
         <div class="wrap-table100">
           <h3>Mean Analysis Per Subject</h3>
           <div class="table100 ver1 m-b-110">
      <?php
        /* REMEMBER TO ENABLE > CREATE EXTENSION tablefunc; < ON THE DB IF NOT ALREADY ENABLED */

        // $db = pg_connect("host=localhost port=5432 dbname=eduweb_highschool_newlightgirls user=postgres password=postgres");
        $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
        $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");
        $i=0;$j=0;$k=0;$l=0;
      ?>
        <table id="table1">
          <div id='t1' class="table100-head">
            <thead>
              <tr class="row100 head">
                <th class="cell100 column2">STREAM</th>
                <!-- <th class="cell100 column3">ROLL</th> -->
                <th class="cell100 column4">ENG.</th>
                <th class="cell100 column5">KIS.</th>
                <th class="cell100 column6">MTH.</th>
                <th class="cell100 column7">CRE</th>
                <th class="cell100 column8">PHY.</th>
                <th class="cell100 column9">BIO.</th>
                <th class="cell100 column10">CHM.</th>
                <th class="cell100 column11">HST.</th>
                <th class="cell100 column12">GEO.</th>
                <th class="cell100 column13">B/S.</th>
                <th class="cell100 column14">CMP.</th>
                <th class="cell100 column15">FRN.</th>
                <!-- <th class="cell100 column16">TOT.</th> -->
                <!-- <th class="cell100 column17">MEAN</th>
                <th class="cell100 column18">C/MEAN</th> -->
              </tr>
            </thead>
          </div>
          <div class="table100-body js-pscroll">
            <tbody>
              <?php
              /* FORM 4 QUERY FOR SUBJECT MEAN SCORES */

              $f4ClassesAndSubjects = pg_query($db,"SELECT * FROM (
							SELECT * FROM   crosstab('SELECT class_name, subject_name, mean FROM (
										SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
												SELECT table1.*, table2.* FROM (
													SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
														SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
															 ,classes.class_id
															 ,classes.class_name
															  ,subject_name
															  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
															  ,exam_type
															  ,exam_marks.student_id
															  ,mark
															  ,grade_weight
															  ,subjects.sort_order
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
														WHERE class_cats.entity_id = 15
														AND term_id = 1
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15) ORDER BY exam_type_id DESC LIMIT 5)
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
																	SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15) ORDER BY class_id ASC)
																	AND term_id = 1
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15) ORDER BY exam_type_id DESC LIMIT 5)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15 LIMIT 1) order by sort_order') AS ct (class_name text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                  /* FORM 4 TABLE FOR SUBJECT MEAN SCORES */

              while ($rowf4 = pg_fetch_assoc($f4ClassesAndSubjects)) {

                   echo "<td class='cell100 column2'>" . $rowf4['class_name'] . "</td>";
                   // echo "<td class='cell100 column3'>xx</td>";
                   echo "<td class='cell100 column4'>" . $rowf4['english'] . "</td>";
                   echo "<td class='cell100 column5'>" . $rowf4['kiswahili'] . "</td>";
                   echo "<td class='cell100 column6'>" . $rowf4['mathematics'] . "</td>";
                   echo "<td class='cell100 column7'>" . $rowf4['cre'] . "</td>";
                   echo "<td class='cell100 column8'>" . $rowf4['physics'] . "</td>";
                   echo "<td class='cell100 column9'>" . $rowf4['biology'] . "</td>";
                   echo "<td class='cell100 column10'>" . $rowf4['chemistry'] . "</td>";
                   echo "<td class='cell100 column11'>" . $rowf4['history'] . "</td>";
                   echo "<td class='cell100 column12'>" . $rowf4['geography'] . "</td>";
                   echo "<td class='cell100 column13'>" . $rowf4['bs_studies'] . "</td>";
                   echo "<td class='cell100 column14'>" . $rowf4['computer'] . "</td>";
                   echo "<td class='cell100 column15'>" . $rowf4['french'] . "</td>";
                   // echo "<td class='cell100 column16'>XX</td>";
                   // echo "<td class='cell100 column17'>" . $rowf4['mean'] . "</td>";
                   // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
               echo "</tr>";
              }

              /* FORM 4 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

              $f4meansCombined = pg_query($db,"SELECT *
                                                        FROM   crosstab('SELECT 1 as total, subject_name, mean FROM (
										SELECT subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_name, sort_order, subject_name, mean FROM (
												SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
													SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
														SELECT table1.*, table2.* FROM (
															SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
																SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
																	 ,classes.class_id
																	 ,classes.class_name
																	  ,subject_name
																	  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
																	  ,exam_type
																	  ,exam_marks.student_id
																	  ,mark
																	  ,grade_weight
																	  ,subjects.sort_order
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
																WHERE class_cats.entity_id = 15
																AND term_id = 1
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15) ORDER BY exam_type_id DESC LIMIT 5)
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
																			SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15) ORDER BY class_id ASC)
																			AND term_id = 1
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15) ORDER BY exam_type_id DESC LIMIT 5)
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
											ORDER BY sort_order ASC
										)table6
										GROUP BY subject_name, sort_order
										ORDER BY sort_order ASC
									)table7
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15 LIMIT 1) order by sort_order') AS ct (total text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)");
              echo "<tr class='row100 body highlight'>";
                // echo "<td class='cell100 column1'>Average</td>";
                echo "<td class='cell100 column2'><b>Form 4 Avg</b></td>";
                // echo "<td class='cell100 column3'>-</td>";
                while ($rowf4mean = pg_fetch_assoc($f4meansCombined)) {
                  echo "<td class='cell100 column4'>" . $rowf4mean['english'] . "</td>";
                  echo "<td class='cell100 column5'>" . $rowf4mean['kiswahili'] . "</td>";
                  echo "<td class='cell100 column6'>" . $rowf4mean['mathematics'] . "</td>";
                  echo "<td class='cell100 column7'>" . $rowf4mean['cre'] . "</td>";
                  echo "<td class='cell100 column8'>" . $rowf4mean['physics'] . "</td>";
                  echo "<td class='cell100 column9'>" . $rowf4mean['biology'] . "</td>";
                  echo "<td class='cell100 column10'>" . $rowf4mean['chemistry'] . "</td>";
                  echo "<td class='cell100 column11'>" . $rowf4mean['history'] . "</td>";
                  echo "<td class='cell100 column12'>" . $rowf4mean['geography'] . "</td>";
                  echo "<td class='cell100 column13'>" . $rowf4mean['bs_studies'] . "</td>";
                  echo "<td class='cell100 column14'>" . $rowf4mean['computer'] . "</td>";
                  echo "<td class='cell100 column15'>" . $rowf4mean['french'] . "</td>";
                  // echo "<td class='cell100 column17'>-</td>";
                  // echo "<td class='cell100 column18'>Average</td>";
                echo "</tr>";
                }

                /* FORM 3 QUERY FOR SUBJECT MEAN SCORES */

                $f3ClassesAndSubjects = pg_query($db,"SELECT * FROM (
							SELECT * FROM   crosstab('SELECT class_name, subject_name, mean FROM (
										SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
												SELECT table1.*, table2.* FROM (
													SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
														SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
															 ,classes.class_id
															 ,classes.class_name
															  ,subject_name
															  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
															  ,exam_type
															  ,exam_marks.student_id
															  ,mark
															  ,grade_weight
															  ,subjects.sort_order
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
														WHERE class_cats.entity_id = 14
														AND term_id = 1
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14) ORDER BY exam_type_id DESC LIMIT 3)
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
																	SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14) ORDER BY class_id ASC)
																	AND term_id = 1
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14) ORDER BY exam_type_id DESC LIMIT 3)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14 LIMIT 1) order by sort_order') AS ct (class_name text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
                echo "<tr class='row100 body'>";
                    // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                    /* FORM 3 TABLE FOR SUBJECT MEAN SCORES */

                while ($rowf3 = pg_fetch_assoc($f3ClassesAndSubjects)) {

                     echo "<td class='cell100 column2'>" . $rowf3['class_name'] . "</td>";
                     // echo "<td class='cell100 column3'>xx</td>";
                     echo "<td class='cell100 column4'>" . $rowf3['english'] . "</td>";
                     echo "<td class='cell100 column5'>" . $rowf3['kiswahili'] . "</td>";
                     echo "<td class='cell100 column6'>" . $rowf3['mathematics'] . "</td>";
                     echo "<td class='cell100 column7'>" . $rowf3['cre'] . "</td>";
                     echo "<td class='cell100 column8'>" . $rowf3['physics'] . "</td>";
                     echo "<td class='cell100 column9'>" . $rowf3['biology'] . "</td>";
                     echo "<td class='cell100 column10'>" . $rowf3['chemistry'] . "</td>";
                     echo "<td class='cell100 column11'>" . $rowf3['history'] . "</td>";
                     echo "<td class='cell100 column12'>" . $rowf3['geography'] . "</td>";
                     echo "<td class='cell100 column13'>" . $rowf3['bs_studies'] . "</td>";
                     echo "<td class='cell100 column14'>" . $rowf3['computer'] . "</td>";
                     echo "<td class='cell100 column15'>" . $rowf3['french'] . "</td>";
                     // echo "<td class='cell100 column16'>XX</td>";
                     // echo "<td class='cell100 column17'>" . $rowf4['mean'] . "</td>";
                     // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                 echo "</tr>";
                }

                /* FORM 3 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

                $f3meansCombined = pg_query($db,"SELECT *
                                                        FROM   crosstab('SELECT 1 as total, subject_name, mean FROM (
										SELECT subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_name, sort_order, subject_name, mean FROM (
												SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
													SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
														SELECT table1.*, table2.* FROM (
															SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
																SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
																	 ,classes.class_id
																	 ,classes.class_name
																	  ,subject_name
																	  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
																	  ,exam_type
																	  ,exam_marks.student_id
																	  ,mark
																	  ,grade_weight
																	  ,subjects.sort_order
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
																WHERE class_cats.entity_id = 14
																AND term_id = 1
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14) ORDER BY exam_type_id DESC LIMIT 3)
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
																			SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14) ORDER BY class_id ASC)
																			AND term_id = 1
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14) ORDER BY exam_type_id DESC LIMIT 3)
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
											ORDER BY sort_order ASC
										)table6
										GROUP BY subject_name, sort_order
										ORDER BY sort_order ASC
									)table7
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14 LIMIT 1) order by sort_order') AS ct (total text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)");
                echo "<tr class='row100 body highlight'>";
                  // echo "<td class='cell100 column1'>Average</td>";
                  echo "<td class='cell100 column2'><b>Form 3 Avg</b></td>";
                  // echo "<td class='cell100 column3'>-</td>";
                  while ($rowf3mean = pg_fetch_assoc($f3meansCombined)) {
                    echo "<td class='cell100 column4'>" . $rowf3mean['english'] . "</td>";
                    echo "<td class='cell100 column5'>" . $rowf3mean['kiswahili'] . "</td>";
                    echo "<td class='cell100 column6'>" . $rowf3mean['mathematics'] . "</td>";
                    echo "<td class='cell100 column7'>" . $rowf3mean['cre'] . "</td>";
                    echo "<td class='cell100 column8'>" . $rowf3mean['physics'] . "</td>";
                    echo "<td class='cell100 column9'>" . $rowf3mean['biology'] . "</td>";
                    echo "<td class='cell100 column10'>" . $rowf3mean['chemistry'] . "</td>";
                    echo "<td class='cell100 column11'>" . $rowf3mean['history'] . "</td>";
                    echo "<td class='cell100 column12'>" . $rowf3mean['geography'] . "</td>";
                    echo "<td class='cell100 column13'>" . $rowf3mean['bs_studies'] . "</td>";
                    echo "<td class='cell100 column14'>" . $rowf3mean['computer'] . "</td>";
                    echo "<td class='cell100 column15'>" . $rowf3mean['french'] . "</td>";
                    // echo "<td class='cell100 column17'>-</td>";
                    // echo "<td class='cell100 column18'>Average</td>";
                  echo "</tr>";
                  }

                /* FORM 2 QUERY FOR SUBJECT MEAN SCORES */

                $f2ClassesAndSubjects = pg_query($db,"SELECT * FROM (
							SELECT * FROM   crosstab('SELECT class_name, subject_name, mean FROM (
										SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
												SELECT table1.*, table2.* FROM (
													SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
														SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
															 ,classes.class_id
															 ,classes.class_name
															  ,subject_name
															  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
															  ,exam_type
															  ,exam_marks.student_id
															  ,mark
															  ,grade_weight
															  ,subjects.sort_order
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
														WHERE class_cats.entity_id = 13
														AND term_id = 1
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13) ORDER BY exam_type_id DESC LIMIT 3)
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
																	SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13) ORDER BY class_id ASC)
																	AND term_id = 1
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13) ORDER BY exam_type_id DESC LIMIT 3)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13 LIMIT 1) order by sort_order') AS ct (class_name text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
                echo "<tr class='row100 body'>";
                    // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                    /* FORM 2 TABLE FOR SUBJECT MEAN SCORES */

                while ($rowf2 = pg_fetch_assoc($f2ClassesAndSubjects)) {

                     echo "<td class='cell100 column2'>" . $rowf2['class_name'] . "</td>";
                     // echo "<td class='cell100 column3'>xx</td>";
                     echo "<td class='cell100 column4'>" . $rowf2['english'] . "</td>";
                     echo "<td class='cell100 column5'>" . $rowf2['kiswahili'] . "</td>";
                     echo "<td class='cell100 column6'>" . $rowf2['mathematics'] . "</td>";
                     echo "<td class='cell100 column7'>" . $rowf2['cre'] . "</td>";
                     echo "<td class='cell100 column8'>" . $rowf2['physics'] . "</td>";
                     echo "<td class='cell100 column9'>" . $rowf2['biology'] . "</td>";
                     echo "<td class='cell100 column10'>" . $rowf2['chemistry'] . "</td>";
                     echo "<td class='cell100 column11'>" . $rowf2['history'] . "</td>";
                     echo "<td class='cell100 column12'>" . $rowf2['geography'] . "</td>";
                     echo "<td class='cell100 column13'>" . $rowf2['bs_studies'] . "</td>";
                     echo "<td class='cell100 column14'>" . $rowf2['computer'] . "</td>";
                     echo "<td class='cell100 column15'>" . $rowf2['french'] . "</td>";
                     // echo "<td class='cell100 column16'>XX</td>";
                     // echo "<td class='cell100 column17'>" . $rowf4['mean'] . "</td>";
                     // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                 echo "</tr>";
                }

                /* FORM 2 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

                $f2meansCombined = pg_query($db,"SELECT *
                                                        FROM   crosstab('SELECT 1 as total, subject_name, mean FROM (
										SELECT subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_name, sort_order, subject_name, mean FROM (
												SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
													SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
														SELECT table1.*, table2.* FROM (
															SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
																SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
																	 ,classes.class_id
																	 ,classes.class_name
																	  ,subject_name
																	  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
																	  ,exam_type
																	  ,exam_marks.student_id
																	  ,mark
																	  ,grade_weight
																	  ,subjects.sort_order
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
																WHERE class_cats.entity_id = 13
																AND term_id = 1
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13) ORDER BY exam_type_id DESC LIMIT 3)
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
																			SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13) ORDER BY class_id ASC)
																			AND term_id = 1
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13) ORDER BY exam_type_id DESC LIMIT 3)
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
											ORDER BY sort_order ASC
										)table6
										GROUP BY subject_name, sort_order
										ORDER BY sort_order ASC
									)table7
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13 LIMIT 1) order by sort_order') AS ct (total text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)");
                echo "<tr class='row100 body highlight'>";
                  // echo "<td class='cell100 column1'>Average</td>";
                  echo "<td class='cell100 column2'><b>Form 2 Avg</b></td>";
                  // echo "<td class='cell100 column3'>-</td>";
                  while ($rowf2mean = pg_fetch_assoc($f2meansCombined)) {
                    echo "<td class='cell100 column4'>" . $rowf2mean['english'] . "</td>";
                    echo "<td class='cell100 column5'>" . $rowf2mean['kiswahili'] . "</td>";
                    echo "<td class='cell100 column6'>" . $rowf2mean['mathematics'] . "</td>";
                    echo "<td class='cell100 column7'>" . $rowf2mean['cre'] . "</td>";
                    echo "<td class='cell100 column8'>" . $rowf2mean['physics'] . "</td>";
                    echo "<td class='cell100 column9'>" . $rowf2mean['biology'] . "</td>";
                    echo "<td class='cell100 column10'>" . $rowf2mean['chemistry'] . "</td>";
                    echo "<td class='cell100 column11'>" . $rowf2mean['history'] . "</td>";
                    echo "<td class='cell100 column12'>" . $rowf2mean['geography'] . "</td>";
                    echo "<td class='cell100 column13'>" . $rowf2mean['bs_studies'] . "</td>";
                    echo "<td class='cell100 column14'>" . $rowf2mean['computer'] . "</td>";
                    echo "<td class='cell100 column15'>" . $rowf2mean['french'] . "</td>";
                    // echo "<td class='cell100 column17'>-</td>";
                    // echo "<td class='cell100 column18'>Average</td>";
                  echo "</tr>";
                  }

                  /* FORM 1 QUERY FOR SUBJECT MEAN SCORES */

                  $f1ClassesAndSubjects = pg_query($db,"SELECT * FROM (
							SELECT * FROM   crosstab('SELECT class_name, subject_name, mean FROM (
										SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
												SELECT table1.*, table2.* FROM (
													SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
														SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
															 ,classes.class_id
															 ,classes.class_name
															  ,subject_name
															  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
															  ,exam_type
															  ,exam_marks.student_id
															  ,mark
															  ,grade_weight
															  ,subjects.sort_order
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
														WHERE class_cats.entity_id = 12
														AND term_id = 1
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12) ORDER BY exam_type_id DESC LIMIT 2)
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
																	SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12) ORDER BY class_id ASC)
																	AND term_id = 1
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12) ORDER BY exam_type_id DESC LIMIT 2)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12 LIMIT 1) order by sort_order') AS ct (class_name text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
                  echo "<tr class='row100 body'>";
                      // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                      /* FORM 1 TABLE FOR SUBJECT MEAN SCORES */

                  while ($rowf1 = pg_fetch_assoc($f1ClassesAndSubjects)) {

                       echo "<td class='cell100 column2'>" . $rowf1['class_name'] . "</td>";
                       // echo "<td class='cell100 column3'>xx</td>";
                       echo "<td class='cell100 column4'>" . $rowf1['english'] . "</td>";
                       echo "<td class='cell100 column5'>" . $rowf1['kiswahili'] . "</td>";
                       echo "<td class='cell100 column6'>" . $rowf1['mathematics'] . "</td>";
                       echo "<td class='cell100 column7'>" . $rowf1['cre'] . "</td>";
                       echo "<td class='cell100 column8'>" . $rowf1['physics'] . "</td>";
                       echo "<td class='cell100 column9'>" . $rowf1['biology'] . "</td>";
                       echo "<td class='cell100 column10'>" . $rowf1['chemistry'] . "</td>";
                       echo "<td class='cell100 column11'>" . $rowf1['history'] . "</td>";
                       echo "<td class='cell100 column12'>" . $rowf1['geography'] . "</td>";
                       echo "<td class='cell100 column13'>" . $rowf1['bs_studies'] . "</td>";
                       echo "<td class='cell100 column14'>" . $rowf1['computer'] . "</td>";
                       echo "<td class='cell100 column15'>" . $rowf1['french'] . "</td>";
                       // echo "<td class='cell100 column16'>XX</td>";
                       // echo "<td class='cell100 column17'>" . $rowf4['mean'] . "</td>";
                       // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                   echo "</tr>";
                  }

                  /* FORM 1 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

                  $f1meansCombined = pg_query($db,"SELECT *
                                                        FROM   crosstab('SELECT 1 as total, subject_name, mean FROM (
										SELECT subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
											SELECT class_name, sort_order, subject_name, mean FROM (
												SELECT class_id, class_name, subject_name, sort_order, trunc(cast((avg(mean)) as numeric),2) as mean FROM (
													SELECT class_id, class_name, subject_name, sort_order, exam_type, (mark::float/count::float) as mean FROM (
														SELECT table1.*, table2.* FROM (
															SELECT class_id, class_name, subject_name, exam_type, sum(mark) as mark, sort_order FROM (
																SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
																	 ,classes.class_id
																	 ,classes.class_name
																	  ,subject_name
																	  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
																	  ,exam_type
																	  ,exam_marks.student_id
																	  ,mark
																	  ,grade_weight
																	  ,subjects.sort_order
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
																WHERE class_cats.entity_id = 12
																AND term_id = 1
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12) ORDER BY exam_type_id DESC LIMIT 2)
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
																			SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12) ORDER BY class_id ASC)
																			AND term_id = 1
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12) ORDER BY exam_type_id DESC LIMIT 2)
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
											ORDER BY sort_order ASC
										)table6
										GROUP BY subject_name, sort_order
										ORDER BY sort_order ASC
									)table7
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12 LIMIT 1) order by sort_order') AS ct (total text, english numeric, kiswahili numeric, mathematics numeric, cre numeric, physics numeric, biology numeric, chemistry numeric, history numeric, geography numeric, bs_studies numeric, computer numeric, french numeric)");
                  echo "<tr class='row100 body highlight'>";
                    // echo "<td class='cell100 column1'>Average</td>";
                    echo "<td class='cell100 column2'><b>Form 1 Avg</b></td>";
                    // echo "<td class='cell100 column3'>-</td>";
                    while ($rowf1mean = pg_fetch_assoc($f1meansCombined)) {
                      echo "<td class='cell100 column4'>" . $rowf1mean['english'] . "</td>";
                      echo "<td class='cell100 column5'>" . $rowf1mean['kiswahili'] . "</td>";
                      echo "<td class='cell100 column6'>" . $rowf1mean['mathematics'] . "</td>";
                      echo "<td class='cell100 column7'>" . $rowf1mean['cre'] . "</td>";
                      echo "<td class='cell100 column8'>" . $rowf1mean['physics'] . "</td>";
                      echo "<td class='cell100 column9'>" . $rowf1mean['biology'] . "</td>";
                      echo "<td class='cell100 column10'>" . $rowf1mean['chemistry'] . "</td>";
                      echo "<td class='cell100 column11'>" . $rowf1mean['history'] . "</td>";
                      echo "<td class='cell100 column12'>" . $rowf1mean['geography'] . "</td>";
                      echo "<td class='cell100 column13'>" . $rowf1mean['bs_studies'] . "</td>";
                      echo "<td class='cell100 column14'>" . $rowf1mean['computer'] . "</td>";
                      echo "<td class='cell100 column15'>" . $rowf1mean['french'] . "</td>";
                      // echo "<td class='cell100 column17'>-</td>";
                      // echo "<td class='cell100 column18'>Average</td>";
                    echo "</tr>";
                    }
              ?>

            </tbody>
          </div>

        </table>
      </div>
    </div>

    <!-- Table for subject grade count -->

    <div class="wrap-table100">
      <h3>Grade Attainment In The Individual Subjects (Form 4)</h3>
      <div class="table100 ver1 m-b-110">
         <table id="table2">
           <div id='t2' class="table100-head">
             <thead>
               <tr class="row100 head">
                 <th class="cell100 column16">CLASS</th>
                 <th class="cell100 column17">SUBJECT</th>
                 <th class="cell100 column18">GRADE</th>
                 <th class="cell100 column19">COUNT</th>
               </tr>
             </thead>
           </div>
           <div class="table100-body js-pscroll">
             <tbody>
               <?php
               /* QUERY FOR SUBJECT GRADE COUNT */

               $subjectGrdCountF4 = pg_query($db,"SELECT class_name, subject_name, grade, count(grade) as grd_count FROM (
                                              			SELECT student_name, class_name, class_id, subject_name, (select grade from app.grading where total_mark between min_mark and max_mark) as grade, sort_order FROM (
                                              				SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                              					SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                                                                          sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                                                                      FROM (
                                                                                          SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                              						--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              						(CASE
                                              						  WHEN is_last_exam is true THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                              						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                              						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              						    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                              						END) as total_mark,
                                              						--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                              						(CASE
                                              						  WHEN is_last_exam is true THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                              						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                              						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              						    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                              						END) as total_grade_weight,
                                              						subjects.sort_order, exam_type
                                                                                          FROM app.exam_marks
                                                                                          INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                                                                          INNER JOIN app.class_subject_exams
                                                                                          INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                                                                          INNER JOIN app.class_subjects
                                                                                          INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                                                                                ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                                                                                ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                                                                          INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                                                                          INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                                                                          WHERE class_cats.entity_id = 15
                                                                                          AND term_id = 1
                                                                                          AND subjects.parent_subject_id is null
                                                                                          AND subjects.use_for_grading is true
                                                                                          AND mark IS NOT NULL
                                                                                          GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                                                                       ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                                                                          ORDER BY sort_order
                                              				)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                              			)a ORDER BY grade ASC, student_name ASC, sort_order ASC
                                              		)b GROUP BY class_name, subject_name, grade ORDER BY class_name DESC, subject_name ASC");
               echo "<tr class='row100 body'>";
                   // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                   /* FORM 4 TABLE FOR SUBJECT MEAN SCORES */

               while ($rowf4Grd = pg_fetch_assoc($subjectGrdCountF4)) {

                    echo "<td class='cell100 column2'>" . $rowf4Grd['class_name'] . "</td>";
                    echo "<td class='cell100 column4'>" . $rowf4Grd['subject_name'] . "</td>";
                    echo "<td class='cell100 column5'>" . $rowf4Grd['grade'] . "</td>";
                    echo "<td class='cell100 column6'>" . $rowf4Grd['grd_count'] . "</td>";
                    // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                echo "</tr>";
               }
               ?>
             </tbody>
           </div>

         </table>
       </div>
     </div>

     <!-- Table for subject grade count -->

     <div class="wrap-table100">
       <h3>Grade Attainment In The Individual Subjects (Form 3)</h3>
       <div class="table100 ver1 m-b-110">
          <table id="table3">
            <div id='t3' class="table100-head">
              <thead>
                <tr class="row100 head">
                  <th class="cell100 column16">CLASS</th>
                  <th class="cell100 column17">SUBJECT</th>
                  <th class="cell100 column18">GRADE</th>
                  <th class="cell100 column19">COUNT</th>
                </tr>
              </thead>
            </div>
            <div class="table100-body js-pscroll">
              <tbody>
                <?php
                /* QUERY FOR SUBJECT GRADE COUNT - Form 3 */

                $subjectGrdCountF3 = pg_query($db,"SELECT class_name, subject_name, grade, count(grade) as grd_count FROM (
                                              			SELECT student_name, class_name, class_id, subject_name, (select grade from app.grading where total_mark between min_mark and max_mark) as grade, sort_order FROM (
                                              				SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                              					SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                                                                          sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                                                                      FROM (
                                                                                          SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                              						--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              						(CASE
                                              						  WHEN is_last_exam is true THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                              						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                              						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              						    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                              						END) as total_mark,
                                              						--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                              						(CASE
                                              						  WHEN is_last_exam is true THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                              						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                              						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              						    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                              						END) as total_grade_weight,
                                              						subjects.sort_order, exam_type
                                                                                          FROM app.exam_marks
                                                                                          INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                                                                          INNER JOIN app.class_subject_exams
                                                                                          INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                                                                          INNER JOIN app.class_subjects
                                                                                          INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                                                                                ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                                                                                ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                                                                          INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                                                                          INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                                                                          WHERE class_cats.entity_id = 14
                                                                                          AND term_id = 1
                                                                                          AND subjects.parent_subject_id is null
                                                                                          AND subjects.use_for_grading is true
                                                                                          AND mark IS NOT NULL
                                                                                          GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                                                                       ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                                                                          ORDER BY sort_order
                                              				)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                              			)a ORDER BY grade ASC, student_name ASC, sort_order ASC
                                              		)b GROUP BY class_name, subject_name, grade ORDER BY class_name DESC, subject_name ASC");
                echo "<tr class='row100 body'>";
                    // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                    /* FORM 3 TABLE FOR SUBJECT MEAN SCORES */

                while ($rowf3Grd = pg_fetch_assoc($subjectGrdCountF3)) {

                     echo "<td class='cell100 column2'>" . $rowf3Grd['class_name'] . "</td>";
                     echo "<td class='cell100 column4'>" . $rowf3Grd['subject_name'] . "</td>";
                     echo "<td class='cell100 column5'>" . $rowf3Grd['grade'] . "</td>";
                     echo "<td class='cell100 column6'>" . $rowf3Grd['grd_count'] . "</td>";
                     // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                 echo "</tr>";
                }
                ?>
              </tbody>
            </div>

          </table>
        </div>
      </div>

      <!-- Table for subject grade count -->

      <div class="wrap-table100">
        <h3>Grade Attainment In The Individual Subjects (Form 2)</h3>
        <div class="table100 ver1 m-b-110">
           <table id="table4">
             <div id='t4' class="table100-head">
               <thead>
                 <tr class="row100 head">
                   <th class="cell100 column16">CLASS</th>
                   <th class="cell100 column17">SUBJECT</th>
                   <th class="cell100 column18">GRADE</th>
                   <th class="cell100 column19">COUNT</th>
                 </tr>
               </thead>
             </div>
             <div class="table100-body js-pscroll">
               <tbody>
                 <?php
                 /* QUERY FOR SUBJECT GRADE COUNT - Form 2 */

                 $subjectGrdCountF2 = pg_query($db,"SELECT class_name, subject_name, grade, count(grade) as grd_count FROM (
                                                			SELECT student_name, class_name, class_id, subject_name, (select grade from app.grading where total_mark between min_mark and max_mark) as grade, sort_order FROM (
                                                				SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                                					SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                                                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                                                                        FROM (
                                                                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                                						--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                                						(CASE
                                                						  WHEN is_last_exam is true THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                                						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                                						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                                						    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                                						END) as total_mark,
                                                						--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                                						(CASE
                                                						  WHEN is_last_exam is true THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                                						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                                						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                                						    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                                						END) as total_grade_weight,
                                                						subjects.sort_order, exam_type
                                                                                            FROM app.exam_marks
                                                                                            INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                                                                            INNER JOIN app.class_subject_exams
                                                                                            INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                                                                            INNER JOIN app.class_subjects
                                                                                            INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                                                                                  ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                                                                                  ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                                                                            INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                                                                            INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                                                                            WHERE class_cats.entity_id = 13
                                                                                            AND term_id = 1
                                                                                            AND subjects.parent_subject_id is null
                                                                                            AND subjects.use_for_grading is true
                                                                                            AND mark IS NOT NULL
                                                                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                                                                         ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                                                                            ORDER BY sort_order
                                                				)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                                			)a ORDER BY grade ASC, student_name ASC, sort_order ASC
                                                		)b GROUP BY class_name, subject_name, grade ORDER BY class_name DESC, subject_name ASC");
                 echo "<tr class='row100 body'>";
                     // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                     /* FORM 3 TABLE FOR SUBJECT MEAN SCORES */

                 while ($rowf2Grd = pg_fetch_assoc($subjectGrdCountF2)) {

                      echo "<td class='cell100 column2'>" . $rowf2Grd['class_name'] . "</td>";
                      echo "<td class='cell100 column4'>" . $rowf2Grd['subject_name'] . "</td>";
                      echo "<td class='cell100 column5'>" . $rowf2Grd['grade'] . "</td>";
                      echo "<td class='cell100 column6'>" . $rowf2Grd['grd_count'] . "</td>";
                      // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                  echo "</tr>";
                 }
                 ?>
               </tbody>
             </div>

           </table>
         </div>
       </div>

       <!-- Table for subject grade count -->

       <div class="wrap-table100">
         <h3>Grade Attainment In The Individual Subjects (Form 1)</h3>
         <div class="table100 ver1 m-b-110">
            <table id="table5">
              <div id='t5' class="table100-head">
                <thead>
                  <tr class="row100 head">
                    <th class="cell100 column16">CLASS</th>
                    <th class="cell100 column17">SUBJECT</th>
                    <th class="cell100 column18">GRADE</th>
                    <th class="cell100 column19">COUNT</th>
                  </tr>
                </thead>
              </div>
              <div class="table100-body js-pscroll">
                <tbody>
                  <?php
                  /* QUERY FOR SUBJECT GRADE COUNT - Form 1 */

                  $subjectGrdCountF1 = pg_query($db,"SELECT class_name, subject_name, grade, count(grade) as grd_count FROM (
                                                			SELECT student_name, class_name, class_id, subject_name, (select grade from app.grading where total_mark between min_mark and max_mark) as grade, sort_order FROM (
                                                				SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                                					SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                                                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                                                                        FROM (
                                                                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                                						--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                                						(CASE
                                                						  WHEN is_last_exam is true THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                                						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                                						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                                						    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                                						END) as total_mark,
                                                						--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                                						(CASE
                                                						  WHEN is_last_exam is true THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                                						  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                                						    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                                						  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                                						    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                                						END) as total_grade_weight,
                                                						subjects.sort_order, exam_type
                                                                                            FROM app.exam_marks
                                                                                            INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                                                                            INNER JOIN app.class_subject_exams
                                                                                            INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                                                                            INNER JOIN app.class_subjects
                                                                                            INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                                                                                  ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                                                                                  ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                                                                            INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                                                                            INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                                                                            WHERE class_cats.entity_id = 12
                                                                                            AND term_id = 1
                                                                                            AND subjects.parent_subject_id is null
                                                                                            AND subjects.use_for_grading is true
                                                                                            AND mark IS NOT NULL
                                                                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                                                                         ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                                                                            ORDER BY sort_order
                                                				)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                                			)a ORDER BY grade ASC, student_name ASC, sort_order ASC
                                                		)b GROUP BY class_name, subject_name, grade ORDER BY class_name DESC, subject_name ASC");
                  echo "<tr class='row100 body'>";
                      // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                      /* FORM 3 TABLE FOR SUBJECT MEAN SCORES */

                  while ($rowf1Grd = pg_fetch_assoc($subjectGrdCountF1)) {

                       echo "<td class='cell100 column2'>" . $rowf1Grd['class_name'] . "</td>";
                       echo "<td class='cell100 column4'>" . $rowf1Grd['subject_name'] . "</td>";
                       echo "<td class='cell100 column5'>" . $rowf1Grd['grade'] . "</td>";
                       echo "<td class='cell100 column6'>" . $rowf1Grd['grd_count'] . "</td>";
                       // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                   echo "</tr>";
                  }
                  ?>
                </tbody>
              </div>

            </table>
          </div>
        </div>

        <!-- Table for overall grade count -->

        <div class="wrap-table100">
          <h3>Overall Grade Count</h3>
          <div class="table100 ver1 m-b-110">
             <table id="table6">
               <div id='t6' class="table100-head">
                 <thead>
                   <tr class="row100 head">
                     <th class="cell100 column30">CLASS</th>
                     <th class="cell100 column31">A</th>
                     <th class="cell100 column32">A-</th>
                     <th class="cell100 column33">B+</th>
                     <th class="cell100 column34">B</th>
                     <th class="cell100 column35">B-</th>
                     <th class="cell100 column36">C+</th>
                     <th class="cell100 column37">C</th>
                     <th class="cell100 column38">C-</th>
                     <th class="cell100 column39">D+</th>
                     <th class="cell100 column40">D</th>
                     <th class="cell100 column41">D-</th>
                     <th class="cell100 column42">E</th>
                   </tr>
                 </thead>
               </div>
               <div class="table100-body js-pscroll">
                 <tbody>
                   <?php
                   /* OVERALL GRADE COUNT FORM 4 */

                   $overallF4 = pg_query($db,"SELECT *
                                            FROM   crosstab('SELECT class_name, grade, grade_count FROM (
                                              								SELECT class_name, grade, count(grade) as grade_count FROM (
                                              									SELECT student_name, class_name, round((total_mark::float/800)*100) as percentage, (select grade from app.grading where round((total_mark::float/800)*100) between min_mark and max_mark) as grade FROM (
                                              										SELECT student_name, class_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight FROM (
                                              											SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                              												SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                              												    sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                              												FROM (
                                              												    SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              													    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                              													END) as total_mark,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=15 limit 1)) THEN
                                              													    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                              													END) as total_grade_weight,
                                              													subjects.sort_order, exam_type
                                              												    FROM app.exam_marks
                                              												    INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                              												    INNER JOIN app.class_subject_exams
                                              												    INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                              												    INNER JOIN app.class_subjects
                                              												    INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                              													  ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                              													  ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                              												    INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                              												    INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                              												    WHERE class_cats.entity_id = 15
                                              												    AND term_id = 1
                                              												    AND subjects.parent_subject_id is null
                                              												    AND subjects.use_for_grading is true
                                              												    AND mark IS NOT NULL
                                              												    GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                              												 ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                              												    ORDER BY sort_order
                                              											)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                              										)w GROUP BY student_name, class_name
                                              									)x ORDER BY class_name DESC, grade ASC
                                              								)y GROUP BY class_name, grade ORDER BY class_name DESC
                                              							)z
                                                                                          ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (class_id text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)");
                   echo "<tr class='row100 body'>";
                       // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                       /* OUTPUT OVERALL F4 GRADES */

                   while ($f4gradeCount = pg_fetch_assoc($overallF4)) {

                        echo "<td class='cell100 column30'>" . $f4gradeCount['class_id'] . "</td>";
                        echo "<td class='cell100 column31'>" . $f4gradeCount['a'] . "</td>";
                        echo "<td class='cell100 column32'>" . $f4gradeCount['a_m'] . "</td>";
                        echo "<td class='cell100 column33'>" . $f4gradeCount['b_p'] . "</td>";
                        echo "<td class='cell100 column34'>" . $f4gradeCount['b'] . "</td>";
                        echo "<td class='cell100 column35'>" . $f4gradeCount['b_m'] . "</td>";
                        echo "<td class='cell100 column36'>" . $f4gradeCount['c_p'] . "</td>";
                        echo "<td class='cell100 column37'>" . $f4gradeCount['c'] . "</td>";
                        echo "<td class='cell100 column38'>" . $f4gradeCount['c_m'] . "</td>";
                        echo "<td class='cell100 column39'>" . $f4gradeCount['d_p'] . "</td>";
                        echo "<td class='cell100 column40'>" . $f4gradeCount['d'] . "</td>";
                        echo "<td class='cell100 column41'>" . $f4gradeCount['d_m'] . "</td>";
                        echo "<td class='cell100 column42'>" . $f4gradeCount['e'] . "</td>";
                        // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                    echo "</tr>";
                   }
                   echo "<tr class='row100 body highlight'>";
                        echo "<td class='cell100 column30'><b>*</b></td>";
                        echo "<td class='cell100 column31'><b>*</b></td>";
                        echo "<td class='cell100 column32'><b>*</b></td>";
                        echo "<td class='cell100 column33'><b>*</b></td>";
                        echo "<td class='cell100 column34'><b>*</b></td>";
                        echo "<td class='cell100 column35'><b>*</b></td>";
                        echo "<td class='cell100 column36'><b>*</b></td>";
                        echo "<td class='cell100 column37'><b>*</b></td>";
                        echo "<td class='cell100 column38'><b>*</b></td>";
                        echo "<td class='cell100 column39'><b>*</b></td>";
                        echo "<td class='cell100 column40'><b>*</b></td>";
                        echo "<td class='cell100 column41'><b>*</b></td>";
                        echo "<td class='cell100 column42'><b>*</b></td>";
                        // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                    echo "</tr>";

                    /* OVERALL GRADE COUNT FORM 3 */

                    $overallF3 = pg_query($db,"SELECT *
                                            FROM   crosstab('SELECT class_name, grade, grade_count FROM (
                                              								SELECT class_name, grade, count(grade) as grade_count FROM (
                                              									SELECT student_name, class_name, round((total_mark::float/800)*100) as percentage, (select grade from app.grading where round((total_mark::float/800)*100) between min_mark and max_mark) as grade FROM (
                                              										SELECT student_name, class_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight FROM (
                                              											SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                              												SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                              												    sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                              												FROM (
                                              												    SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              													    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                              													END) as total_mark,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=14 limit 1)) THEN
                                              													    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                              													END) as total_grade_weight,
                                              													subjects.sort_order, exam_type
                                              												    FROM app.exam_marks
                                              												    INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                              												    INNER JOIN app.class_subject_exams
                                              												    INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                              												    INNER JOIN app.class_subjects
                                              												    INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                              													  ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                              													  ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                              												    INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                              												    INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                              												    WHERE class_cats.entity_id = 14
                                              												    AND term_id = 1
                                              												    AND subjects.parent_subject_id is null
                                              												    AND subjects.use_for_grading is true
                                              												    AND mark IS NOT NULL
                                              												    GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                              												 ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                              												    ORDER BY sort_order
                                              											)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                              										)w GROUP BY student_name, class_name
                                              									)x ORDER BY class_name DESC, grade ASC
                                              								)y GROUP BY class_name, grade ORDER BY class_name DESC
                                              							)z
                                                                                          ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (class_id text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)");
                    echo "<tr class='row100 body'>";
                        // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                        /* OUTPUT OVERALL F3 GRADES */

                    while ($f3gradeCount = pg_fetch_assoc($overallF3)) {

                         echo "<td class='cell100 column30'>" . $f3gradeCount['class_id'] . "</td>";
                         echo "<td class='cell100 column31'>" . $f3gradeCount['a'] . "</td>";
                         echo "<td class='cell100 column32'>" . $f3gradeCount['a_m'] . "</td>";
                         echo "<td class='cell100 column33'>" . $f3gradeCount['b_p'] . "</td>";
                         echo "<td class='cell100 column34'>" . $f3gradeCount['b'] . "</td>";
                         echo "<td class='cell100 column35'>" . $f3gradeCount['b_m'] . "</td>";
                         echo "<td class='cell100 column36'>" . $f3gradeCount['c_p'] . "</td>";
                         echo "<td class='cell100 column37'>" . $f3gradeCount['c'] . "</td>";
                         echo "<td class='cell100 column38'>" . $f3gradeCount['c_m'] . "</td>";
                         echo "<td class='cell100 column39'>" . $f3gradeCount['d_p'] . "</td>";
                         echo "<td class='cell100 column40'>" . $f3gradeCount['d'] . "</td>";
                         echo "<td class='cell100 column41'>" . $f3gradeCount['d_m'] . "</td>";
                         echo "<td class='cell100 column42'>" . $f3gradeCount['e'] . "</td>";
                         // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                     echo "</tr>";
                    }

                    echo "<tr class='row100 body highlight'>";
                         echo "<td class='cell100 column30'><b>*</b></td>";
                         echo "<td class='cell100 column31'><b>*</b></td>";
                         echo "<td class='cell100 column32'><b>*</b></td>";
                         echo "<td class='cell100 column33'><b>*</b></td>";
                         echo "<td class='cell100 column34'><b>*</b></td>";
                         echo "<td class='cell100 column35'><b>*</b></td>";
                         echo "<td class='cell100 column36'><b>*</b></td>";
                         echo "<td class='cell100 column37'><b>*</b></td>";
                         echo "<td class='cell100 column38'><b>*</b></td>";
                         echo "<td class='cell100 column39'><b>*</b></td>";
                         echo "<td class='cell100 column40'><b>*</b></td>";
                         echo "<td class='cell100 column41'><b>*</b></td>";
                         echo "<td class='cell100 column42'><b>*</b></td>";
                         // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                     echo "</tr>";

                     /* OVERALL GRADE COUNT FORM 2 */

                     $overallF2 = pg_query($db,"SELECT *
                                            FROM   crosstab('SELECT class_name, grade, grade_count FROM (
                                              								SELECT class_name, grade, count(grade) as grade_count FROM (
                                              									SELECT student_name, class_name, round((total_mark::float/800)*100) as percentage, (select grade from app.grading where round((total_mark::float/800)*100) between min_mark and max_mark) as grade FROM (
                                              										SELECT student_name, class_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight FROM (
                                              											SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                              												SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                              												    sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                              												FROM (
                                              												    SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                              													    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                              													END) as total_mark,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=13 limit 1)) THEN
                                              													    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                              													END) as total_grade_weight,
                                              													subjects.sort_order, exam_type
                                              												    FROM app.exam_marks
                                              												    INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                              												    INNER JOIN app.class_subject_exams
                                              												    INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                              												    INNER JOIN app.class_subjects
                                              												    INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                              													  ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                              													  ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                              												    INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                              												    INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                              												    WHERE class_cats.entity_id = 13
                                              												    AND term_id = 1
                                              												    AND subjects.parent_subject_id is null
                                              												    AND subjects.use_for_grading is true
                                              												    AND mark IS NOT NULL
                                              												    GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                              												 ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                              												    ORDER BY sort_order
                                              											)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                              										)w GROUP BY student_name, class_name
                                              									)x ORDER BY class_name DESC, grade ASC
                                              								)y GROUP BY class_name, grade ORDER BY class_name DESC
                                              							)z
                                                                                          ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (class_id text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)");
                     echo "<tr class='row100 body'>";
                         // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                         /* OUTPUT OVERALL F2 GRADES */

                     while ($f2gradeCount = pg_fetch_assoc($overallF2)) {

                          echo "<td class='cell100 column30'>" . $f2gradeCount['class_id'] . "</td>";
                          echo "<td class='cell100 column31'>" . $f2gradeCount['a'] . "</td>";
                          echo "<td class='cell100 column32'>" . $f2gradeCount['a_m'] . "</td>";
                          echo "<td class='cell100 column33'>" . $f2gradeCount['b_p'] . "</td>";
                          echo "<td class='cell100 column34'>" . $f2gradeCount['b'] . "</td>";
                          echo "<td class='cell100 column35'>" . $f2gradeCount['b_m'] . "</td>";
                          echo "<td class='cell100 column36'>" . $f2gradeCount['c_p'] . "</td>";
                          echo "<td class='cell100 column37'>" . $f2gradeCount['c'] . "</td>";
                          echo "<td class='cell100 column38'>" . $f2gradeCount['c_m'] . "</td>";
                          echo "<td class='cell100 column39'>" . $f2gradeCount['d_p'] . "</td>";
                          echo "<td class='cell100 column40'>" . $f2gradeCount['d'] . "</td>";
                          echo "<td class='cell100 column41'>" . $f2gradeCount['d_m'] . "</td>";
                          echo "<td class='cell100 column42'>" . $f2gradeCount['e'] . "</td>";
                          // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                      echo "</tr>";
                     }

                     echo "<tr class='row100 body highlight'>";
                          echo "<td class='cell100 column30'><b>*</b></td>";
                          echo "<td class='cell100 column31'><b>*</b></td>";
                          echo "<td class='cell100 column32'><b>*</b></td>";
                          echo "<td class='cell100 column33'><b>*</b></td>";
                          echo "<td class='cell100 column34'><b>*</b></td>";
                          echo "<td class='cell100 column35'><b>*</b></td>";
                          echo "<td class='cell100 column36'><b>*</b></td>";
                          echo "<td class='cell100 column37'><b>*</b></td>";
                          echo "<td class='cell100 column38'><b>*</b></td>";
                          echo "<td class='cell100 column39'><b>*</b></td>";
                          echo "<td class='cell100 column40'><b>*</b></td>";
                          echo "<td class='cell100 column41'><b>*</b></td>";
                          echo "<td class='cell100 column42'><b>*</b></td>";
                          // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                      echo "</tr>";

                      /* OVERALL GRADE COUNT FORM 1 */

                      $overallF1 = pg_query($db,"SELECT *
                                            FROM   crosstab('SELECT class_name, grade, grade_count FROM (
                                              								SELECT class_name, grade, count(grade) as grade_count FROM (
                                              									SELECT student_name, class_name, round((total_mark::float/800)*100) as percentage, (select grade from app.grading where round((total_mark::float/800)*100) between min_mark and max_mark) as grade FROM (
                                              										SELECT student_name, class_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight FROM (
                                              											SELECT student_name, class_name, class_id, subject_name, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order FROM (
                                              												SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                              												    sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                              												FROM (
                                              												    SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0)*0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                              													    coalesce(coalesce(sum(case when subjects.parent_subject_id is null then mark end),0),0)

                                              													END) as total_mark,
                                              													--coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
                                              													(CASE
                                              													  WHEN is_last_exam is true THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.7)

                                              													  WHEN is_last_exam is false and exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                              													    round(coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) *0.3)

                                              													  WHEN not exists (select exam_types.is_last_exam from app.exam_types where is_last_exam is TRUE AND class_cat_id=(select class_cat_id from app.class_cats where entity_id=12 limit 1)) THEN
                                              													    coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0)

                                              													END) as total_grade_weight,
                                              													subjects.sort_order, exam_type
                                              												    FROM app.exam_marks
                                              												    INNER JOIN app.students ON exam_marks.student_id = students.student_id
                                              												    INNER JOIN app.class_subject_exams
                                              												    INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                                              												    INNER JOIN app.class_subjects
                                              												    INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
                                              													  ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                                              													  ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                                              												    INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                              												    INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
                                              												    WHERE class_cats.entity_id = 12
                                              												    AND term_id = 1
                                              												    AND subjects.parent_subject_id is null
                                              												    AND subjects.use_for_grading is true
                                              												    AND mark IS NOT NULL
                                              												    GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type, exam_types.is_last_exam
                                              												 ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                              												    ORDER BY sort_order
                                              											)v GROUP BY student_name, class_name, class_id, subject_name, sort_order ORDER BY student_name ASC, class_name DESC, sort_order ASC
                                              										)w GROUP BY student_name, class_name
                                              									)x ORDER BY class_name DESC, grade ASC
                                              								)y GROUP BY class_name, grade ORDER BY class_name DESC
                                              							)z
                                                                                          ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (class_id text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)");
                      echo "<tr class='row100 body'>";
                          // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                          /* OUTPUT OVERALL F1 GRADES */

                      while ($f1gradeCount = pg_fetch_assoc($overallF1)) {

                           echo "<td class='cell100 column30'>" . $f1gradeCount['class_id'] . "</td>";
                           echo "<td class='cell100 column31'>" . $f1gradeCount['a'] . "</td>";
                           echo "<td class='cell100 column32'>" . $f1gradeCount['a_m'] . "</td>";
                           echo "<td class='cell100 column33'>" . $f1gradeCount['b_p'] . "</td>";
                           echo "<td class='cell100 column34'>" . $f1gradeCount['b'] . "</td>";
                           echo "<td class='cell100 column35'>" . $f1gradeCount['b_m'] . "</td>";
                           echo "<td class='cell100 column36'>" . $f1gradeCount['c_p'] . "</td>";
                           echo "<td class='cell100 column37'>" . $f1gradeCount['c'] . "</td>";
                           echo "<td class='cell100 column38'>" . $f1gradeCount['c_m'] . "</td>";
                           echo "<td class='cell100 column39'>" . $f1gradeCount['d_p'] . "</td>";
                           echo "<td class='cell100 column40'>" . $f1gradeCount['d'] . "</td>";
                           echo "<td class='cell100 column41'>" . $f1gradeCount['d_m'] . "</td>";
                           echo "<td class='cell100 column42'>" . $f1gradeCount['e'] . "</td>";
                           // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                       echo "</tr>";
                      }

                      echo "<tr class='row100 body highlight'>";
                           echo "<td class='cell100 column30'><b>*</b></td>";
                           echo "<td class='cell100 column31'><b>*</b></td>";
                           echo "<td class='cell100 column32'><b>*</b></td>";
                           echo "<td class='cell100 column33'><b>*</b></td>";
                           echo "<td class='cell100 column34'><b>*</b></td>";
                           echo "<td class='cell100 column35'><b>*</b></td>";
                           echo "<td class='cell100 column36'><b>*</b></td>";
                           echo "<td class='cell100 column37'><b>*</b></td>";
                           echo "<td class='cell100 column38'><b>*</b></td>";
                           echo "<td class='cell100 column39'><b>*</b></td>";
                           echo "<td class='cell100 column40'><b>*</b></td>";
                           echo "<td class='cell100 column41'><b>*</b></td>";
                           echo "<td class='cell100 column42'><b>*</b></td>";
                           // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                       echo "</tr>";
                   ?>
                 </tbody>
               </div>

             </table>
           </div>
         </div>
  </div>
    </div>

    <!-- Footer -->
    <footer class="py-2 bg-dark" style="position: fixed !important; bottom: 0 !important; width: 100% !important;">
      <div class="container">
        <p class="m-0 text-center text-white"><small>&copy; Eduweb <script type="text/javascript">document.write((new Date()).getFullYear())</script></small></p>
      </div>
      <!-- /.container -->
    </footer>
    <script src="../components/overviewFiles/vendor/jquery/jquery-3.2.1.min.js"></script>
  	<script src="../components/overviewFiles/vendor/bootstrap/js/popper.js"></script>
  	<script src="../components/overviewFiles/vendor/bootstrap/js/bootstrap.min.js"></script>
  	<script src="../components/overviewFiles/vendor/select2/select2.min.js"></script>
  	<script src="../components/overviewFiles/vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  	<script>
  		$('.js-pscroll').each(function(){
  			var ps = new PerfectScrollbar(this);

  			$(window).on('resize', function(){
  				ps.update();
  			})
  		});


  	</script>
  	<script src="../components/overviewFiles/js/main.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
        $('#table1').DataTable( {
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [
                // 'excelHtml5',
                // 'csvHtml5',
                // 'pdfHtml5',
                {
                  extend: 'excelHtml5',
                  title: 'Mean-Analysis'
              },
              {
                extend: 'csvHtml5',
                title: 'Mean-Analysis'
            },
              {
                  extend: 'pdfHtml5',
                  title: 'Mean-Analysis'
              }
            ],
            "ordering": false,
            "paging": false
        } );
        $('#table2').DataTable( {
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [
                // 'excelHtml5',
                // 'csvHtml5',
                // 'pdfHtml5',
                {
                  extend: 'excelHtml5',
                  title: 'Form-4 Subject Grades'
              },
              {
                extend: 'csvHtml5',
                title: 'Form-4 Subject Grades'
            },
              {
                  extend: 'pdfHtml5',
                  title: 'Form-4 Subject Grades'
              }
            ],
            "order": [[ 0, "desc" ]]
        } );
        $('#table3').DataTable( {
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [
                // 'excelHtml5',
                // 'csvHtml5',
                // 'pdfHtml5',
                {
                  extend: 'excelHtml5',
                  title: 'Form-3 Subject Grades'
              },
              {
                extend: 'csvHtml5',
                title: 'Form-3 Subject Grades'
            },
              {
                  extend: 'pdfHtml5',
                  title: 'Form-3 Subject Grades'
              }
            ],
            "order": [[ 0, "desc" ]]
        } );
        $('#table4').DataTable( {
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [
                // 'excelHtml5',
                // 'csvHtml5',
                // 'pdfHtml5',
                {
                  extend: 'excelHtml5',
                  title: 'Form-2 Subject Grades'
              },
              {
                extend: 'csvHtml5',
                title: 'Form-2 Subject Grades'
            },
              {
                  extend: 'pdfHtml5',
                  title: 'Form-2 Subject Grades'
              }
            ],
            "order": [[ 0, "desc" ]]
        } );
        $('#table5').DataTable( {
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [
                // 'excelHtml5',
                // 'csvHtml5',
                // 'pdfHtml5',
                {
                  extend: 'excelHtml5',
                  title: 'Form-1 Subject Grades'
              },
              {
                extend: 'csvHtml5',
                title: 'Form-1 Subject Grades'
            },
              {
                  extend: 'pdfHtml5',
                  title: 'Form-1 Subject Grades'
              }
            ],
            "order": [[ 0, "desc" ]]
        } );
        $('#table6').DataTable( {
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [
                // 'excelHtml5',
                // 'csvHtml5',
                // 'pdfHtml5',
                {
                  extend: 'excelHtml5',
                  title: 'Overall Grade Count'
              },
              {
                extend: 'csvHtml5',
                title: 'Overall Grade Count'
            },
              {
                  extend: 'pdfHtml5',
                  title: 'Overall Grade Count'
              }
            ],
            "ordering": false,
            "paging": false
        } );
      } );
    </script>
    <script src="../components/overviewFiles/js/jquery.dataTables.min.js"></script>
    <script src="../components/overviewFiles/js/dataTables.buttons.min.js"></script>
    <script src="../components/overviewFiles/js/jszip.min.js"></script>
    <script src="../components/overviewFiles/js/pdfmake.min.js"></script>
    <script src="../components/overviewFiles/js/vfs_fonts.js"></script>
    <script src="../components/overviewFiles/js/buttons.html5.min.js"></script>
</body>
</html>
