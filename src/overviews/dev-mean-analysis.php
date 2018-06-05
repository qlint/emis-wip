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
  <!-- End of Header Nav -->
  <div class="limiter">
    <h4 style="text-align:center;margin-top:85px;">This page overviews the overall mean marks attained by the respective classes within a term.</h4>
    <div style="border: 3px dashed #397C49; width:20%; margin-left:auto;margin-right:auto;text-align:center;padding-bottom:7px;padding-top:7px;">
      <h5 style="text-align:center;">Select A Term.</h5>
      <form style="margin-left:auto; margin-right:auto; text-align:center;" action="#" method="post">
        <select name="Term">
          <option value='15'>Term 1</option>
          <option value='16'>Term 2</option>
          <option value='17'>Term 3</option>
        </select>
        <div><input style="margin-left:auto; margin-right:auto; text-align:center;" type="submit" name="submit" value="Get Results For This Term" /></div>
      </form>
    </div>
    <?php
    if(isset($_POST['submit'])){
    $selected_val1 = $_POST['Term'];
    $selected_val = trim($selected_val1,"'");
    // $no_selection = 0;  // Storing Selected Value In Variable
    // echo "Term " .$selected_val . " stream results";  // Displaying Selected Value
    }
    $no_selection = 1;
    $term = (isset($_POST['submit']) ? $selected_val : $no_selection);
    $term_name = (isset($_POST['submit']) ? $selected_val : $no_selection);
    ?>
      <div class="container-table100">
         <div class="wrap-table100">
           <h3 id="expTitle">Mean Analysis: Term <?php echo $term_name ?></h3>
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
                <th class="cell100 column4">MAT.</th>
                <th class="cell100 column5">ENG.</th>
                <th class="cell100 column6">ENG(Lang).</th>
                <th class="cell100 column7">ENG(Com).</th>
                <th class="cell100 column8">KIS.</th>
                <th class="cell100 column9">KIS(Lug).</th>
                <th class="cell100 column10">KIS(Ins).</th>
                <th class="cell100 column11">SCI.</th>
                <!-- <th class="cell100 column12">GEO.</th> -->
                <th class="cell100 column13">SS/RE.</th>
                <th class="cell100 column14">SS.</th>
                <th class="cell100 column15">CRE.</th>
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
														WHERE class_cats.entity_id = 11
														AND term_id = $term
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11) ORDER BY exam_type_id DESC LIMIT 4)
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11) ORDER BY class_id ASC)
																	AND term_id = $term
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11) ORDER BY exam_type_id DESC LIMIT 4)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                  /* FORM 4 TABLE FOR SUBJECT MEAN SCORES */

              while ($rowf4 = pg_fetch_assoc($f4ClassesAndSubjects)) {

                   echo "<td class='cell100 column2'>" . $rowf4['class_name'] . "</td>";
                   // echo "<td class='cell100 column3'>xx</td>";
                   echo "<td class='cell100 column4'>" . $rowf4['mathematics'] . "</td>";
                   echo "<td class='cell100 column5'>" . $rowf4['english'] . "</td>";
                   echo "<td class='cell100 column6'>" . $rowf4['eng_lang'] . "</td>";
                   echo "<td class='cell100 column7'>" . $rowf4['eng_com'] . "</td>";
                   echo "<td class='cell100 column8'>" . $rowf4['kiswahili'] . "</td>";
                   echo "<td class='cell100 column9'>" . $rowf4['kis_lug'] . "</td>";
                   echo "<td class='cell100 column10'>" . $rowf4['kis_ins'] . "</td>";
                   echo "<td class='cell100 column11'>" . $rowf4['science'] . "</td>";
                   // echo "<td class='cell100 column12'>" . $rowf4['geography'] . "</td>";
                   echo "<td class='cell100 column13'>" . $rowf4['ssre'] . "</td>";
                   echo "<td class='cell100 column14'>" . $rowf4['ss'] . "</td>";
                   echo "<td class='cell100 column15'>" . $rowf4['cre'] . "</td>";
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
																WHERE class_cats.entity_id = 11
																AND term_id = $term
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11) ORDER BY exam_type_id DESC LIMIT 5)
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11) ORDER BY class_id ASC)
																			AND term_id = $term
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11) ORDER BY exam_type_id DESC LIMIT 5)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct (total text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)");
              echo "<tr class='row100 body highlight'>";
                // echo "<td class='cell100 column1'>Average</td>";
                echo "<td class='cell100 column2'><b>Class 8 Avg</b></td>";
                // echo "<td class='cell100 column3'>-</td>";
                while ($rowf4mean = pg_fetch_assoc($f4meansCombined)) {
                  echo "<td class='cell100 column4'>" . $rowf4mean['mathematics'] . "</td>";
                  echo "<td class='cell100 column5'>" . $rowf4mean['english'] . "</td>";
                  echo "<td class='cell100 column6'>" . $rowf4mean['eng_lang'] . "</td>";
                  echo "<td class='cell100 column7'>" . $rowf4mean['eng_com'] . "</td>";
                  echo "<td class='cell100 column8'>" . $rowf4mean['kiswahili'] . "</td>";
                  echo "<td class='cell100 column9'>" . $rowf4mean['kis_lug'] . "</td>";
                  echo "<td class='cell100 column10'>" . $rowf4mean['kis_ins'] . "</td>";
                  echo "<td class='cell100 column11'>" . $rowf4mean['science'] . "</td>";
                  // echo "<td class='cell100 column12'>" . $rowf4mean['geography'] . "</td>";
                  echo "<td class='cell100 column13'>" . $rowf4mean['ssre'] . "</td>";
                  echo "<td class='cell100 column14'>" . $rowf4mean['ss'] . "</td>";
                  echo "<td class='cell100 column15'>" . $rowf4mean['cre'] . "</td>";
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
														WHERE class_cats.entity_id = 10
														AND term_id = $term
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10) ORDER BY exam_type_id DESC LIMIT 3)
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10) ORDER BY class_id ASC)
																	AND term_id = $term
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10) ORDER BY exam_type_id DESC LIMIT 3)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
                echo "<tr class='row100 body'>";
                    // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                    /* FORM 3 TABLE FOR SUBJECT MEAN SCORES */

                while ($rowf3 = pg_fetch_assoc($f3ClassesAndSubjects)) {

                     echo "<td class='cell100 column2'>" . $rowf3['class_name'] . "</td>";
                     // echo "<td class='cell100 column3'>xx</td>";
                     echo "<td class='cell100 column4'>" . $rowf3['mathematics'] . "</td>";
                     echo "<td class='cell100 column5'>" . $rowf3['english'] . "</td>";
                     echo "<td class='cell100 column6'>" . $rowf3['eng_lang'] . "</td>";
                     echo "<td class='cell100 column7'>" . $rowf3['eng_com'] . "</td>";
                     echo "<td class='cell100 column8'>" . $rowf3['kiswahili'] . "</td>";
                     echo "<td class='cell100 column9'>" . $rowf3['kis_lug'] . "</td>";
                     echo "<td class='cell100 column10'>" . $rowf3['kis_ins'] . "</td>";
                     echo "<td class='cell100 column11'>" . $rowf3['science'] . "</td>";
                     // echo "<td class='cell100 column12'>" . $rowf3['geography'] . "</td>";
                     echo "<td class='cell100 column13'>" . $rowf3['ssre'] . "</td>";
                     echo "<td class='cell100 column14'>" . $rowf3['ss'] . "</td>";
                     echo "<td class='cell100 column15'>" . $rowf3['cre'] . "</td>";
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
																WHERE class_cats.entity_id = 10
																AND term_id = $term
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10) ORDER BY exam_type_id DESC LIMIT 3)
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10) ORDER BY class_id ASC)
																			AND term_id = $term
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10) ORDER BY exam_type_id DESC LIMIT 3)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10 LIMIT 1) order by sort_order') AS ct (total text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)");
                echo "<tr class='row100 body highlight'>";
                  // echo "<td class='cell100 column1'>Average</td>";
                  echo "<td class='cell100 column2'><b>Class 7 Avg</b></td>";
                  // echo "<td class='cell100 column3'>-</td>";
                  while ($rowf3mean = pg_fetch_assoc($f3meansCombined)) {
                    echo "<td class='cell100 column4'>" . $rowf3mean['mathematics'] . "</td>";
                    echo "<td class='cell100 column5'>" . $rowf3mean['english'] . "</td>";
                    echo "<td class='cell100 column6'>" . $rowf3mean['eng_lang'] . "</td>";
                    echo "<td class='cell100 column7'>" . $rowf3mean['eng_com'] . "</td>";
                    echo "<td class='cell100 column8'>" . $rowf3mean['kiswahili'] . "</td>";
                    echo "<td class='cell100 column9'>" . $rowf3mean['kis_lug'] . "</td>";
                    echo "<td class='cell100 column10'>" . $rowf3mean['kis_ins'] . "</td>";
                    echo "<td class='cell100 column11'>" . $rowf3mean['science'] . "</td>";
                    // echo "<td class='cell100 column12'>" . $rowf3mean['geography'] . "</td>";
                    echo "<td class='cell100 column13'>" . $rowf3mean['ssre'] . "</td>";
                    echo "<td class='cell100 column14'>" . $rowf3mean['ss'] . "</td>";
                    echo "<td class='cell100 column15'>" . $rowf3mean['cre'] . "</td>";
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
														WHERE class_cats.entity_id = 9
														AND term_id = $term
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9) ORDER BY exam_type_id DESC LIMIT 3)
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9) ORDER BY class_id ASC)
																	AND term_id = $term
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9) ORDER BY exam_type_id DESC LIMIT 3)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
                echo "<tr class='row100 body'>";
                    // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                    /* FORM 2 TABLE FOR SUBJECT MEAN SCORES */

                while ($rowf2 = pg_fetch_assoc($f2ClassesAndSubjects)) {

                     echo "<td class='cell100 column2'>" . $rowf2['class_name'] . "</td>";
                     // echo "<td class='cell100 column3'>xx</td>";
                     echo "<td class='cell100 column4'>" . $rowf2['mathematics'] . "</td>";
                     echo "<td class='cell100 column5'>" . $rowf2['english'] . "</td>";
                     echo "<td class='cell100 column6'>" . $rowf2['eng_lang'] . "</td>";
                     echo "<td class='cell100 column7'>" . $rowf2['eng_com'] . "</td>";
                     echo "<td class='cell100 column8'>" . $rowf2['kiswahili'] . "</td>";
                     echo "<td class='cell100 column9'>" . $rowf2['kis_lug'] . "</td>";
                     echo "<td class='cell100 column10'>" . $rowf2['kis_ins'] . "</td>";
                     echo "<td class='cell100 column11'>" . $rowf2['science'] . "</td>";
                     // echo "<td class='cell100 column12'>" . $rowf2['geography'] . "</td>";
                     echo "<td class='cell100 column13'>" . $rowf2['ssre'] . "</td>";
                     echo "<td class='cell100 column14'>" . $rowf2['ss'] . "</td>";
                     echo "<td class='cell100 column15'>" . $rowf2['cre'] . "</td>";
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
																WHERE class_cats.entity_id = 9
																AND term_id = $term
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9) ORDER BY exam_type_id DESC LIMIT 3)
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9) ORDER BY class_id ASC)
																			AND term_id = $term
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9) ORDER BY exam_type_id DESC LIMIT 3)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9 LIMIT 1) order by sort_order') AS ct (total text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)");
                echo "<tr class='row100 body highlight'>";
                  // echo "<td class='cell100 column1'>Average</td>";
                  echo "<td class='cell100 column2'><b>Class 6 Avg</b></td>";
                  // echo "<td class='cell100 column3'>-</td>";
                  while ($rowf2mean = pg_fetch_assoc($f2meansCombined)) {
                    echo "<td class='cell100 column4'>" . $rowf2mean['mathematics'] . "</td>";
                    echo "<td class='cell100 column5'>" . $rowf2mean['english'] . "</td>";
                    echo "<td class='cell100 column6'>" . $rowf2mean['eng_lang'] . "</td>";
                    echo "<td class='cell100 column7'>" . $rowf2mean['eng_com'] . "</td>";
                    echo "<td class='cell100 column8'>" . $rowf2mean['kiswahili'] . "</td>";
                    echo "<td class='cell100 column9'>" . $rowf2mean['kis_lug'] . "</td>";
                    echo "<td class='cell100 column10'>" . $rowf2mean['kis_ins'] . "</td>";
                    echo "<td class='cell100 column11'>" . $rowf2mean['science'] . "</td>";
                    // echo "<td class='cell100 column12'>" . $rowf2mean['geography'] . "</td>";
                    echo "<td class='cell100 column13'>" . $rowf2mean['ssre'] . "</td>";
                    echo "<td class='cell100 column14'>" . $rowf2mean['ss'] . "</td>";
                    echo "<td class='cell100 column15'>" . $rowf2mean['cre'] . "</td>";
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
														WHERE class_cats.entity_id = 8
														AND term_id = $term
														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8) ORDER BY exam_type_id DESC LIMIT 2)
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
																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8) ORDER BY class_id ASC)
																	AND term_id = $term
																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8) ORDER BY exam_type_id DESC LIMIT 2)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)
                                                  )table6
                                                  ORDER BY class_name DESC");
                  echo "<tr class='row100 body'>";
                      // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                      /* FORM 1 TABLE FOR SUBJECT MEAN SCORES */

                  while ($rowf1 = pg_fetch_assoc($f1ClassesAndSubjects)) {

                       echo "<td class='cell100 column2'>" . $rowf1['class_name'] . "</td>";
                       // echo "<td class='cell100 column3'>xx</td>";
                       echo "<td class='cell100 column4'>" . $rowf1['mathematics'] . "</td>";
                       echo "<td class='cell100 column5'>" . $rowf1['english'] . "</td>";
                       echo "<td class='cell100 column6'>" . $rowf1['eng_lang'] . "</td>";
                       echo "<td class='cell100 column7'>" . $rowf1['eng_com'] . "</td>";
                       echo "<td class='cell100 column8'>" . $rowf1['kiswahili'] . "</td>";
                       echo "<td class='cell100 column9'>" . $rowf1['kis_lug'] . "</td>";
                       echo "<td class='cell100 column10'>" . $rowf1['kis_ins'] . "</td>";
                       echo "<td class='cell100 column11'>" . $rowf1['science'] . "</td>";
                       // echo "<td class='cell100 column12'>" . $rowf1['geography'] . "</td>";
                       echo "<td class='cell100 column13'>" . $rowf1['ssre'] . "</td>";
                       echo "<td class='cell100 column14'>" . $rowf1['ss'] . "</td>";
                       echo "<td class='cell100 column15'>" . $rowf1['cre'] . "</td>";
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
																WHERE class_cats.entity_id = 8
																AND term_id = $term
																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8) ORDER BY exam_type_id DESC LIMIT 2)
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
																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8) ORDER BY class_id ASC)
																			AND term_id = $term
																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8) ORDER BY exam_type_id DESC LIMIT 2)
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
                                                        ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8 LIMIT 1) order by sort_order') AS ct (total text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)");
                  echo "<tr class='row100 body highlight'>";
                    // echo "<td class='cell100 column1'>Average</td>";
                    echo "<td class='cell100 column2'><b>Class 5 Avg</b></td>";
                    // echo "<td class='cell100 column3'>-</td>";
                    while ($rowf1mean = pg_fetch_assoc($f1meansCombined)) {
                      echo "<td class='cell100 column4'>" . $rowf1mean['mathematics'] . "</td>";
                      echo "<td class='cell100 column5'>" . $rowf1mean['english'] . "</td>";
                      echo "<td class='cell100 column6'>" . $rowf1mean['eng_lang'] . "</td>";
                      echo "<td class='cell100 column7'>" . $rowf1mean['eng_com'] . "</td>";
                      echo "<td class='cell100 column8'>" . $rowf1mean['kiswahili'] . "</td>";
                      echo "<td class='cell100 column9'>" . $rowf1mean['kis_lug'] . "</td>";
                      echo "<td class='cell100 column10'>" . $rowf1mean['kis_ins'] . "</td>";
                      echo "<td class='cell100 column11'>" . $rowf1mean['science'] . "</td>";
                      // echo "<td class='cell100 column12'>" . $rowf1mean['geography'] . "</td>";
                      echo "<td class='cell100 column13'>" . $rowf1mean['ssre'] . "</td>";
                      echo "<td class='cell100 column14'>" . $rowf1mean['ss'] . "</td>";
                      echo "<td class='cell100 column15'>" . $rowf1mean['cre'] . "</td>";
                      // echo "<td class='cell100 column17'>-</td>";
                      // echo "<td class='cell100 column18'>Average</td>";
                    echo "</tr>";
                    }

                    /* CLASS 4 QUERY FOR SUBJECT MEAN SCORES */

                    $c4ClassesAndSubjects = pg_query($db,"SELECT * FROM (
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
  														WHERE class_cats.entity_id = 7
  														AND term_id = $term
  														AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7) ORDER BY exam_type_id DESC LIMIT 2)
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
  																	WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7) ORDER BY class_id ASC)
  																	AND term_id = $term
  																	AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7) ORDER BY exam_type_id DESC LIMIT 2)
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
                                                          ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)
                                                    )table6
                                                    ORDER BY class_name DESC");
                    echo "<tr class='row100 body'>";
                        // echo "<th class='cell100 column1' rowspan='5'>F4</th>";

                        /* FORM 1 TABLE FOR SUBJECT MEAN SCORES */

                    while ($rowc4 = pg_fetch_assoc($c4ClassesAndSubjects)) {

                         echo "<td class='cell100 column2'>" . $rowc4['class_name'] . "</td>";
                         // echo "<td class='cell100 column3'>xx</td>";
                         echo "<td class='cell100 column4'>" . $rowc4['mathematics'] . "</td>";
                         echo "<td class='cell100 column5'>" . $rowc4['english'] . "</td>";
                         echo "<td class='cell100 column6'>" . $rowc4['eng_lang'] . "</td>";
                         echo "<td class='cell100 column7'>" . $rowc4['eng_com'] . "</td>";
                         echo "<td class='cell100 column8'>" . $rowc4['kiswahili'] . "</td>";
                         echo "<td class='cell100 column9'>" . $rowc4['kis_lug'] . "</td>";
                         echo "<td class='cell100 column10'>" . $rowc4['kis_ins'] . "</td>";
                         echo "<td class='cell100 column11'>" . $rowc4['science'] . "</td>";
                         // echo "<td class='cell100 column12'>" . $rowf1['geography'] . "</td>";
                         echo "<td class='cell100 column13'>" . $rowc4['ssre'] . "</td>";
                         echo "<td class='cell100 column14'>" . $rowc4['ss'] . "</td>";
                         echo "<td class='cell100 column15'>" . $rowc4['cre'] . "</td>";
                         // echo "<td class='cell100 column16'>XX</td>";
                         // echo "<td class='cell100 column17'>" . $rowf4['mean'] . "</td>";
                         // if (!$i++) echo "<th class='cell100 column18' rowspan='5'>" . max(array($rowf4['mean'])) . "</th>";
                     echo "</tr>";
                    }

                    /* CLASS 4 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

                    $c4meansCombined = pg_query($db,"SELECT *
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
  																WHERE class_cats.entity_id = 7
  																AND term_id = $term
  																AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7) ORDER BY exam_type_id DESC LIMIT 2)
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
  																			WHERE class_subjects.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7) ORDER BY class_id ASC)
  																			AND term_id = $term
  																			AND class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN(SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7) ORDER BY exam_type_id DESC LIMIT 2)
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
                                                          ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7 LIMIT 1) order by sort_order') AS ct (total text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, kis_lug numeric, kis_ins numeric, science numeric, ssre numeric, ss numeric, cre numeric)");
                    echo "<tr class='row100 body highlight'>";
                      // echo "<td class='cell100 column1'>Average</td>";
                      echo "<td class='cell100 column2'><b>Class 4 Avg</b></td>";
                      // echo "<td class='cell100 column3'>-</td>";
                      while ($rowc4mean = pg_fetch_assoc($c4meansCombined)) {
                        echo "<td class='cell100 column4'>" . $rowc4mean['mathematics'] . "</td>";
                        echo "<td class='cell100 column5'>" . $rowc4mean['english'] . "</td>";
                        echo "<td class='cell100 column6'>" . $rowc4mean['eng_lang'] . "</td>";
                        echo "<td class='cell100 column7'>" . $rowc4mean['eng_com'] . "</td>";
                        echo "<td class='cell100 column8'>" . $rowc4mean['kiswahili'] . "</td>";
                        echo "<td class='cell100 column9'>" . $rowc4mean['kis_lug'] . "</td>";
                        echo "<td class='cell100 column10'>" . $rowc4mean['kis_ins'] . "</td>";
                        echo "<td class='cell100 column11'>" . $rowc4mean['science'] . "</td>";
                        // echo "<td class='cell100 column12'>" . $rowf1mean['geography'] . "</td>";
                        echo "<td class='cell100 column13'>" . $rowc4mean['ssre'] . "</td>";
                        echo "<td class='cell100 column14'>" . $rowc4mean['ss'] . "</td>";
                        echo "<td class='cell100 column15'>" . $rowc4mean['cre'] . "</td>";
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
        var intendedName = document.getElementById('expTitle');
        var docName = intendedName.innerHTML;
        $('#table1').DataTable( {
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [
                // 'excelHtml5',
                // 'csvHtml5',
                // 'pdfHtml5',
                {
                  extend: 'excelHtml5',
                  title: docName
              },
              {
                extend: 'csvHtml5',
                title: docName
            },
              {
                  extend: 'pdfHtml5',
                  title: docName
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
