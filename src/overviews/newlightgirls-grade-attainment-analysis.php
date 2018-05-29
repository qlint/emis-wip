<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?> grade analysis</title>
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
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Grade Attainment</span></a>
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
    <h4 style="text-align:center;margin-top:85px;">This page overviews the overall grades attained by the respective classes within a term for the entire school.</h4>
    <div style="border: 3px dashed #397C49; width:20%; margin-left:auto;margin-right:auto;text-align:center;padding-bottom:7px;padding-top:7px;">
      <h5 style="text-align:center;">Select A Term.</h5>
      <form style="margin-left:auto; margin-right:auto; text-align:center;" action="#" method="post">
        <select name="Term">
          <option value='1'>Term 1</option>
          <option value='2'>Term 2</option>
          <option value='3'>Term 3</option>
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

    // $db = pg_connect("host=localhost port=5432 dbname=eduweb_highschool_newlightgirls user=postgres password=postgres");
    $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
    $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");
    ?>
      <div class="container-table100">

        <!-- Table for overall grade count -->

        <div class="wrap-table100">
          <h3 id="expTitle">Overall Grade Count: Term <?php echo $term_name ?></h3>
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
                                              												    AND term_id = $term
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
                                              												    AND term_id = $term
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
                                              												    AND term_id = $term
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
                                              												    AND term_id = $term
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
        var intendedName = document.getElementById('expTitle');
        var docName = intendedName.innerHTML;
        $('#table6').DataTable( {
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
