<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?> Deviations</title>
    <!-- Bootstrap core CSS -->
    <link rel="icon" type="image/png" href="../components/overviewFiles/images/icons/favicon.ico"/>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/fonts/font-awesome-4.7.0/css/font-awesome.min.css">

    <link href="template/scripts/jquerysctipttop.css" rel="stylesheet" type="text/css">
    <!-- <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"> -->
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/animate/animate.css">
    <!-- Custom styles for this template -->
    <link href="css/1-col-portfolio.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/dev-mean.css">
    <script src="template/scripts/jquery-1.12.4.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
    <script type="text/javascript" src="template/scripts/jszip.min.js"></script>
    <script type="text/javascript" src="template/external/FileSaver.min.js"></script>
    <script type="text/javascript" src="template/scripts/excel-gen.js"></script>
    <script type="text/javascript" src="template/scripts/demo.page.js"></script>
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
  <?php
    /* REMEMBER TO ENABLE > CREATE EXTENSION tablefunc; < ON THE DB IF NOT ALREADY ENABLED */

    // $db = pg_connect("host=localhost port=5432 dbname=eduweb_dev2 user=postgres password=postgres");
    $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
    $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");
    $i=0;$j=0;$k=0;$l=0;
  ?>

    <!-- DEVIATIONS ANALYSIS -->
    <!-- End of Header Nav -->
    <div class="limiter">
      <h4 style="text-align:center;margin-top:30px;">This page overviews the overall subject mean marks per class for the school within a term.</h4>
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
      $no_selection = 15;
      $term = (isset($_POST['submit']) ? $selected_val : $no_selection);
      $term_name = (isset($_POST['submit']) ? $selected_val : $no_selection);
      ?>
      <div class="container-table100">
         <div class="wrap-table100" style="margin-top: 0px !important;">
           <div class="table100 ver1 m-b-110">

        <h1 style="margin-left:15px;">Deviations</h1>
        <button id="generate-excel3" class="btn btn-success" style="margin-left:15px;">
            Generate Excel</button>
        <br /><br />
        <table id="test_table3">
          <div id='t1' class="table100-head">
            <thead>
              <tr class="row100 head">
                <th class="cell100 column50">CLASS</th>
                <th class="cell100 column51">MAT</th>
                <th class="cell100 column52">DEV</th>
                <th class="cell100 column53">ENG</th>
                <th class="cell100 column54">DEV</th>
                <th class="cell100 column55">LNG</th>
                <th class="cell100 column56">DEV</th>
                <th class="cell100 column57">CMP</th>
                <th class="cell100 column58">DEV</th>
                <th class="cell100 column59">KISW</th>
                <th class="cell100 column60">DEV</th>
                <th class="cell100 column61">LUG</th>
                <th class="cell100 column62">DEV</th>
                <th class="cell100 column63">INS</th>
                <th class="cell100 column64">DEV</th>
                <th class="cell100 column65">SCI</th>
                <th class="cell100 column66">DEV</th>
                <th class="cell100 column67">SSRE</th>
                <th class="cell100 column68">DEV</th>
                <th class="cell100 column69">SS</th>
                <th class="cell100 column70">DEV</th>
                <th class="cell100 column71">CRE</th>
                <th class="cell100 column72">DEV</th>
              </tr>
            </thead>
          </div>
          <div class="table100-body js-pscroll">
            <tbody>
              <?php
              /* CLASS 8 DEVIATIONS */

              $deviationsc8 = pg_query($db,"SELECT class_name, maths, (maths - maths2) as dv_mat, eng, (eng - eng2) as dv_eng, eng_lang, (eng_lang - eng_lang2) as dv_eng_lang, eng_comp, (eng_comp - eng_comp2) as dv_eng_comp, kisw, (kisw - kisw2) as dv_kisw, kisw_lugh, (kisw_lugh - kisw_lugh2) as dv_kisw_lugh, kisw_kus, (kisw_kus - kisw_kus2) as dv_kisw_kus, sci, (sci - sci2) as dv_sci, ssre, (ssre - ssre2) as dv_ssre, ss, (ss - ss2) as dv_ss, cre, (cre - cre2) as dv_cre FROM (
						SELECT *
                                                  FROM   crosstab('SELECT class_name, subject_name, marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 11
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 11
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct (class_name text, maths numeric, eng numeric, eng_lang numeric, eng_comp numeric, kisw numeric, kisw_lugh numeric, kisw_kus numeric, sci numeric, ssre numeric, ss numeric, cre numeric)

,   crosstab('SELECT 1 as class_name1, subject_name, max(marks) as marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 11
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 11
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four GROUP BY four.subject_name
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct2 (class_name1 text, maths2 numeric, eng2 numeric, eng_lang2 numeric, eng_comp2 numeric, kisw2 numeric, kisw_lugh2 numeric, kisw_kus2 numeric, sci2 numeric, ssre2 numeric, ss2 numeric, cre2 numeric)
)dev1");
              echo "<tr class='row100 body wider'>";

                  /* Deviations output */

              while ($c8Deviations = pg_fetch_assoc($deviationsc8)) {

                   echo "<td class='cell100 column50'>" . $c8Deviations['class_name'] . "</td>";
                   echo "<td class='cell100 column51'>" . $c8Deviations['maths'] . "</td>";
                   echo "<td class='cell100 column52'>" . $c8Deviations['dv_mat'] . "</td>";
                   echo "<td class='cell100 column53'>" . $c8Deviations['eng'] . "</td>";
                   echo "<td class='cell100 column54'>" . $c8Deviations['dv_eng'] . "</td>";
                   echo "<td class='cell100 column55'>" . $c8Deviations['eng_lang'] . "</td>";
                   echo "<td class='cell100 column56'>" . $c8Deviations['dv_eng_lang'] . "</td>";
                   echo "<td class='cell100 column57'>" . $c8Deviations['eng_comp'] . "</td>";
                   echo "<td class='cell100 column58'>" . $c8Deviations['dv_eng_comp'] . "</td>";
                   echo "<td class='cell100 column59'>" . $c8Deviations['kisw'] . "</td>";
                   echo "<td class='cell100 column60'>" . $c8Deviations['dv_kisw'] . "</td>";
                   echo "<td class='cell100 column61'>" . $c8Deviations['kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column62'>" . $c8Deviations['dv_kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column63'>" . $c8Deviations['kisw_kus'] . "</td>";
                   echo "<td class='cell100 column64'>" . $c8Deviations['dv_kisw_kus'] . "</td>";
                   echo "<td class='cell100 column65'>" . $c8Deviations['sci'] . "</td>";
                   echo "<td class='cell100 column66'>" . $c8Deviations['dv_sci'] . "</td>";
                   echo "<td class='cell100 column67'>" . $c8Deviations['ssre'] . "</td>";
                   echo "<td class='cell100 column68'>" . $c8Deviations['dv_ssre'] . "</td>";
                   echo "<td class='cell100 column69'>" . $c8Deviations['ss'] . "</td>";
                   echo "<td class='cell100 column70'>" . $c8Deviations['dv_ss'] . "</td>";
                   echo "<td class='cell100 column71'>" . $c8Deviations['cre'] . "</td>";
                   echo "<td class='cell100 column72'>" . $c8Deviations['dv_cre'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body highlight' style='height:18px;'>";
                  echo "<td class='cell100 column50'><b>*</b></td>";
                  echo "<td class='cell100 column51'><b>*</b></td>";
                  echo "<td class='cell100 column52'><b>*</b></td>";
                  echo "<td class='cell100 column53'><b>*</b></td>";
                  echo "<td class='cell100 column54'><b>*</b></td>";
                  echo "<td class='cell100 column55'><b>*</b></td>";
                  echo "<td class='cell100 column56'><b>*</b></td>";
                  echo "<td class='cell100 column57'><b>*</b></td>";
                  echo "<td class='cell100 column58'><b>*</b></td>";
                  echo "<td class='cell100 column59'><b>*</b></td>";
                  echo "<td class='cell100 column60'><b>*</b></td>";
                  echo "<td class='cell100 column61'><b>*</b></td>";
                  echo "<td class='cell100 column62'><b>*</b></td>";
                  echo "<td class='cell100 column63'><b>*</b></td>";
                  echo "<td class='cell100 column64'><b>*</b></td>";
                  echo "<td class='cell100 column65'><b>*</b></td>";
                  echo "<td class='cell100 column66'><b>*</b></td>";
                  echo "<td class='cell100 column67'><b>*</b></td>";
                  echo "<td class='cell100 column68'><b>*</b></td>";
                  echo "<td class='cell100 column69'><b>*</b></td>";
                  echo "<td class='cell100 column70'><b>*</b></td>";
                  echo "<td class='cell100 column71'><b>*</b></td>";
                  echo "<td class='cell100 column72'><b>*</b></td>";
              echo "</tr>";

              /* CLASS 7 DEVIATIONS */

              $deviationsc7 = pg_query($db,"SELECT class_name, maths, (maths - maths2) as dv_mat, eng, (eng - eng2) as dv_eng, eng_lang, (eng_lang - eng_lang2) as dv_eng_lang, eng_comp, (eng_comp - eng_comp2) as dv_eng_comp, kisw, (kisw - kisw2) as dv_kisw, kisw_lugh, (kisw_lugh - kisw_lugh2) as dv_kisw_lugh, kisw_kus, (kisw_kus - kisw_kus2) as dv_kisw_kus, sci, (sci - sci2) as dv_sci, ssre, (ssre - ssre2) as dv_ssre, ss, (ss - ss2) as dv_ss, cre, (cre - cre2) as dv_cre FROM (
						SELECT *
                                                  FROM   crosstab('SELECT class_name, subject_name, marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 10
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 10
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10 LIMIT 1) order by sort_order') AS ct (class_name text, maths numeric, eng numeric, eng_lang numeric, eng_comp numeric, kisw numeric, kisw_lugh numeric, kisw_kus numeric, sci numeric, ssre numeric, ss numeric, cre numeric)

,   crosstab('SELECT 1 as class_name1, subject_name, max(marks) as marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 10
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 10
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four GROUP BY four.subject_name
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 10 LIMIT 1) order by sort_order') AS ct2 (class_name1 text, maths2 numeric, eng2 numeric, eng_lang2 numeric, eng_comp2 numeric, kisw2 numeric, kisw_lugh2 numeric, kisw_kus2 numeric, sci2 numeric, ssre2 numeric, ss2 numeric, cre2 numeric)
)dev1");
              echo "<tr class='row100 body wider'>";

                  /* Deviations output */

              while ($c7Deviations = pg_fetch_assoc($deviationsc7)) {

                   echo "<td class='cell100 column50'>" . $c7Deviations['class_name'] . "</td>";
                   echo "<td class='cell100 column51'>" . $c7Deviations['maths'] . "</td>";
                   echo "<td class='cell100 column52'>" . $c7Deviations['dv_mat'] . "</td>";
                   echo "<td class='cell100 column53'>" . $c7Deviations['eng'] . "</td>";
                   echo "<td class='cell100 column54'>" . $c7Deviations['dv_eng'] . "</td>";
                   echo "<td class='cell100 column55'>" . $c7Deviations['eng_lang'] . "</td>";
                   echo "<td class='cell100 column56'>" . $c7Deviations['dv_eng_lang'] . "</td>";
                   echo "<td class='cell100 column57'>" . $c7Deviations['eng_comp'] . "</td>";
                   echo "<td class='cell100 column58'>" . $c7Deviations['dv_eng_comp'] . "</td>";
                   echo "<td class='cell100 column59'>" . $c7Deviations['kisw'] . "</td>";
                   echo "<td class='cell100 column60'>" . $c7Deviations['dv_kisw'] . "</td>";
                   echo "<td class='cell100 column61'>" . $c7Deviations['kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column62'>" . $c7Deviations['dv_kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column63'>" . $c7Deviations['kisw_kus'] . "</td>";
                   echo "<td class='cell100 column64'>" . $c7Deviations['dv_kisw_kus'] . "</td>";
                   echo "<td class='cell100 column65'>" . $c7Deviations['sci'] . "</td>";
                   echo "<td class='cell100 column66'>" . $c7Deviations['dv_sci'] . "</td>";
                   echo "<td class='cell100 column67'>" . $c7Deviations['ssre'] . "</td>";
                   echo "<td class='cell100 column68'>" . $c7Deviations['dv_ssre'] . "</td>";
                   echo "<td class='cell100 column69'>" . $c7Deviations['ss'] . "</td>";
                   echo "<td class='cell100 column70'>" . $c7Deviations['dv_ss'] . "</td>";
                   echo "<td class='cell100 column71'>" . $c7Deviations['cre'] . "</td>";
                   echo "<td class='cell100 column72'>" . $c7Deviations['dv_cre'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body highlight' style='height:18px;'>";
                  echo "<td class='cell100 column50'><b>*</b></td>";
                  echo "<td class='cell100 column51'><b>*</b></td>";
                  echo "<td class='cell100 column52'><b>*</b></td>";
                  echo "<td class='cell100 column53'><b>*</b></td>";
                  echo "<td class='cell100 column54'><b>*</b></td>";
                  echo "<td class='cell100 column55'><b>*</b></td>";
                  echo "<td class='cell100 column56'><b>*</b></td>";
                  echo "<td class='cell100 column57'><b>*</b></td>";
                  echo "<td class='cell100 column58'><b>*</b></td>";
                  echo "<td class='cell100 column59'><b>*</b></td>";
                  echo "<td class='cell100 column60'><b>*</b></td>";
                  echo "<td class='cell100 column61'><b>*</b></td>";
                  echo "<td class='cell100 column62'><b>*</b></td>";
                  echo "<td class='cell100 column63'><b>*</b></td>";
                  echo "<td class='cell100 column64'><b>*</b></td>";
                  echo "<td class='cell100 column65'><b>*</b></td>";
                  echo "<td class='cell100 column66'><b>*</b></td>";
                  echo "<td class='cell100 column67'><b>*</b></td>";
                  echo "<td class='cell100 column68'><b>*</b></td>";
                  echo "<td class='cell100 column69'><b>*</b></td>";
                  echo "<td class='cell100 column70'><b>*</b></td>";
                  echo "<td class='cell100 column71'><b>*</b></td>";
                  echo "<td class='cell100 column72'><b>*</b></td>";
              echo "</tr>";

              /* CLASS 6 DEVIATIONS */

              $deviationsc6 = pg_query($db,"SELECT class_name, maths, (maths - maths2) as dv_mat, eng, (eng - eng2) as dv_eng, eng_lang, (eng_lang - eng_lang2) as dv_eng_lang, eng_comp, (eng_comp - eng_comp2) as dv_eng_comp, kisw, (kisw - kisw2) as dv_kisw, kisw_lugh, (kisw_lugh - kisw_lugh2) as dv_kisw_lugh, kisw_kus, (kisw_kus - kisw_kus2) as dv_kisw_kus, sci, (sci - sci2) as dv_sci, ssre, (ssre - ssre2) as dv_ssre, ss, (ss - ss2) as dv_ss, cre, (cre - cre2) as dv_cre FROM (
						SELECT *
                                                  FROM   crosstab('SELECT class_name, subject_name, marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 9
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 9
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9 LIMIT 1) order by sort_order') AS ct (class_name text, maths numeric, eng numeric, eng_lang numeric, eng_comp numeric, kisw numeric, kisw_lugh numeric, kisw_kus numeric, sci numeric, ssre numeric, ss numeric, cre numeric)

,   crosstab('SELECT 1 as class_name1, subject_name, max(marks) as marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 9
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 9
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four GROUP BY four.subject_name
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9 LIMIT 1) order by sort_order') AS ct2 (class_name1 text, maths2 numeric, eng2 numeric, eng_lang2 numeric, eng_comp2 numeric, kisw2 numeric, kisw_lugh2 numeric, kisw_kus2 numeric, sci2 numeric, ssre2 numeric, ss2 numeric, cre2 numeric)
)dev1");
              echo "<tr class='row100 body wider'>";

                  /* Deviations output */

              while ($c6Deviations = pg_fetch_assoc($deviationsc6)) {

                   echo "<td class='cell100 column50'>" . $c6Deviations['class_name'] . "</td>";
                   echo "<td class='cell100 column51'>" . $c6Deviations['maths'] . "</td>";
                   echo "<td class='cell100 column52'>" . $c6Deviations['dv_mat'] . "</td>";
                   echo "<td class='cell100 column53'>" . $c6Deviations['eng'] . "</td>";
                   echo "<td class='cell100 column54'>" . $c6Deviations['dv_eng'] . "</td>";
                   echo "<td class='cell100 column55'>" . $c6Deviations['eng_lang'] . "</td>";
                   echo "<td class='cell100 column56'>" . $c6Deviations['dv_eng_lang'] . "</td>";
                   echo "<td class='cell100 column57'>" . $c6Deviations['eng_comp'] . "</td>";
                   echo "<td class='cell100 column58'>" . $c6Deviations['dv_eng_comp'] . "</td>";
                   echo "<td class='cell100 column59'>" . $c6Deviations['kisw'] . "</td>";
                   echo "<td class='cell100 column60'>" . $c6Deviations['dv_kisw'] . "</td>";
                   echo "<td class='cell100 column61'>" . $c6Deviations['kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column62'>" . $c6Deviations['dv_kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column63'>" . $c6Deviations['kisw_kus'] . "</td>";
                   echo "<td class='cell100 column64'>" . $c6Deviations['dv_kisw_kus'] . "</td>";
                   echo "<td class='cell100 column65'>" . $c6Deviations['sci'] . "</td>";
                   echo "<td class='cell100 column66'>" . $c6Deviations['dv_sci'] . "</td>";
                   echo "<td class='cell100 column67'>" . $c6Deviations['ssre'] . "</td>";
                   echo "<td class='cell100 column68'>" . $c6Deviations['dv_ssre'] . "</td>";
                   echo "<td class='cell100 column69'>" . $c6Deviations['ss'] . "</td>";
                   echo "<td class='cell100 column70'>" . $c6Deviations['dv_ss'] . "</td>";
                   echo "<td class='cell100 column71'>" . $c6Deviations['cre'] . "</td>";
                   echo "<td class='cell100 column72'>" . $c6Deviations['dv_cre'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body highlight' style='height:18px;'>";
                  echo "<td class='cell100 column50'><b>*</b></td>";
                  echo "<td class='cell100 column51'><b>*</b></td>";
                  echo "<td class='cell100 column52'><b>*</b></td>";
                  echo "<td class='cell100 column53'><b>*</b></td>";
                  echo "<td class='cell100 column54'><b>*</b></td>";
                  echo "<td class='cell100 column55'><b>*</b></td>";
                  echo "<td class='cell100 column56'><b>*</b></td>";
                  echo "<td class='cell100 column57'><b>*</b></td>";
                  echo "<td class='cell100 column58'><b>*</b></td>";
                  echo "<td class='cell100 column59'><b>*</b></td>";
                  echo "<td class='cell100 column60'><b>*</b></td>";
                  echo "<td class='cell100 column61'><b>*</b></td>";
                  echo "<td class='cell100 column62'><b>*</b></td>";
                  echo "<td class='cell100 column63'><b>*</b></td>";
                  echo "<td class='cell100 column64'><b>*</b></td>";
                  echo "<td class='cell100 column65'><b>*</b></td>";
                  echo "<td class='cell100 column66'><b>*</b></td>";
                  echo "<td class='cell100 column67'><b>*</b></td>";
                  echo "<td class='cell100 column68'><b>*</b></td>";
                  echo "<td class='cell100 column69'><b>*</b></td>";
                  echo "<td class='cell100 column70'><b>*</b></td>";
                  echo "<td class='cell100 column71'><b>*</b></td>";
                  echo "<td class='cell100 column72'><b>*</b></td>";
              echo "</tr>";

              /* CLASS 5 DEVIATIONS */

              $deviationsc5 = pg_query($db,"SELECT class_name, maths, (maths - maths2) as dv_mat, eng, (eng - eng2) as dv_eng, eng_lang, (eng_lang - eng_lang2) as dv_eng_lang, eng_comp, (eng_comp - eng_comp2) as dv_eng_comp, kisw, (kisw - kisw2) as dv_kisw, kisw_lugh, (kisw_lugh - kisw_lugh2) as dv_kisw_lugh, kisw_kus, (kisw_kus - kisw_kus2) as dv_kisw_kus, sci, (sci - sci2) as dv_sci, ssre, (ssre - ssre2) as dv_ssre, ss, (ss - ss2) as dv_ss, cre, (cre - cre2) as dv_cre FROM (
						SELECT *
                                                  FROM   crosstab('SELECT class_name, subject_name, marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 8
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 8
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8 LIMIT 1) order by sort_order') AS ct (class_name text, maths numeric, eng numeric, eng_lang numeric, eng_comp numeric, kisw numeric, kisw_lugh numeric, kisw_kus numeric, sci numeric, ssre numeric, ss numeric, cre numeric)

,   crosstab('SELECT 1 as class_name1, subject_name, max(marks) as marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 8
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 8
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four GROUP BY four.subject_name
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8 LIMIT 1) order by sort_order') AS ct2 (class_name1 text, maths2 numeric, eng2 numeric, eng_lang2 numeric, eng_comp2 numeric, kisw2 numeric, kisw_lugh2 numeric, kisw_kus2 numeric, sci2 numeric, ssre2 numeric, ss2 numeric, cre2 numeric)
)dev1");
              echo "<tr class='row100 body wider'>";

                  /* Deviations output */

              while ($c5Deviations = pg_fetch_assoc($deviationsc5)) {

                   echo "<td class='cell100 column50'>" . $c5Deviations['class_name'] . "</td>";
                   echo "<td class='cell100 column51'>" . $c5Deviations['maths'] . "</td>";
                   echo "<td class='cell100 column52'>" . $c5Deviations['dv_mat'] . "</td>";
                   echo "<td class='cell100 column53'>" . $c5Deviations['eng'] . "</td>";
                   echo "<td class='cell100 column54'>" . $c5Deviations['dv_eng'] . "</td>";
                   echo "<td class='cell100 column55'>" . $c5Deviations['eng_lang'] . "</td>";
                   echo "<td class='cell100 column56'>" . $c5Deviations['dv_eng_lang'] . "</td>";
                   echo "<td class='cell100 column57'>" . $c5Deviations['eng_comp'] . "</td>";
                   echo "<td class='cell100 column58'>" . $c5Deviations['dv_eng_comp'] . "</td>";
                   echo "<td class='cell100 column59'>" . $c5Deviations['kisw'] . "</td>";
                   echo "<td class='cell100 column60'>" . $c5Deviations['dv_kisw'] . "</td>";
                   echo "<td class='cell100 column61'>" . $c5Deviations['kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column62'>" . $c5Deviations['dv_kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column63'>" . $c5Deviations['kisw_kus'] . "</td>";
                   echo "<td class='cell100 column64'>" . $c5Deviations['dv_kisw_kus'] . "</td>";
                   echo "<td class='cell100 column65'>" . $c5Deviations['sci'] . "</td>";
                   echo "<td class='cell100 column66'>" . $c5Deviations['dv_sci'] . "</td>";
                   echo "<td class='cell100 column67'>" . $c5Deviations['ssre'] . "</td>";
                   echo "<td class='cell100 column68'>" . $c5Deviations['dv_ssre'] . "</td>";
                   echo "<td class='cell100 column69'>" . $c5Deviations['ss'] . "</td>";
                   echo "<td class='cell100 column70'>" . $c5Deviations['dv_ss'] . "</td>";
                   echo "<td class='cell100 column71'>" . $c5Deviations['cre'] . "</td>";
                   echo "<td class='cell100 column72'>" . $c5Deviations['dv_cre'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body highlight' style='height:18px;'>";
                  echo "<td class='cell100 column50'><b>*</b></td>";
                  echo "<td class='cell100 column51'><b>*</b></td>";
                  echo "<td class='cell100 column52'><b>*</b></td>";
                  echo "<td class='cell100 column53'><b>*</b></td>";
                  echo "<td class='cell100 column54'><b>*</b></td>";
                  echo "<td class='cell100 column55'><b>*</b></td>";
                  echo "<td class='cell100 column56'><b>*</b></td>";
                  echo "<td class='cell100 column57'><b>*</b></td>";
                  echo "<td class='cell100 column58'><b>*</b></td>";
                  echo "<td class='cell100 column59'><b>*</b></td>";
                  echo "<td class='cell100 column60'><b>*</b></td>";
                  echo "<td class='cell100 column61'><b>*</b></td>";
                  echo "<td class='cell100 column62'><b>*</b></td>";
                  echo "<td class='cell100 column63'><b>*</b></td>";
                  echo "<td class='cell100 column64'><b>*</b></td>";
                  echo "<td class='cell100 column65'><b>*</b></td>";
                  echo "<td class='cell100 column66'><b>*</b></td>";
                  echo "<td class='cell100 column67'><b>*</b></td>";
                  echo "<td class='cell100 column68'><b>*</b></td>";
                  echo "<td class='cell100 column69'><b>*</b></td>";
                  echo "<td class='cell100 column70'><b>*</b></td>";
                  echo "<td class='cell100 column71'><b>*</b></td>";
                  echo "<td class='cell100 column72'><b>*</b></td>";
              echo "</tr>";

              /* CLASS 4 DEVIATIONS */

              $deviationsc4 = pg_query($db,"SELECT class_name, maths, (maths - maths2) as dv_mat, eng, (eng - eng2) as dv_eng, eng_lang, (eng_lang - eng_lang2) as dv_eng_lang, eng_comp, (eng_comp - eng_comp2) as dv_eng_comp, kisw, (kisw - kisw2) as dv_kisw, kisw_lugh, (kisw_lugh - kisw_lugh2) as dv_kisw_lugh, kisw_kus, (kisw_kus - kisw_kus2) as dv_kisw_kus, sci, (sci - sci2) as dv_sci, ssre, (ssre - ssre2) as dv_ssre, ss, (ss - ss2) as dv_ss, cre, (cre - cre2) as dv_cre FROM (
						SELECT *
                                                  FROM   crosstab('SELECT class_name, subject_name, marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 7
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 7
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7 LIMIT 1) order by sort_order') AS ct (class_name text, maths numeric, eng numeric, eng_lang numeric, eng_comp numeric, kisw numeric, kisw_lugh numeric, kisw_kus numeric, sci numeric, ssre numeric, ss numeric, cre numeric)

,   crosstab('SELECT 1 as class_name1, subject_name, max(marks) as marks FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 7
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
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
                                                                    INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
                                                                    WHERE class_cats.entity_id = 7
                                                                    AND term_id = $term
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four GROUP BY four.subject_name
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7 LIMIT 1) order by sort_order') AS ct2 (class_name1 text, maths2 numeric, eng2 numeric, eng_lang2 numeric, eng_comp2 numeric, kisw2 numeric, kisw_lugh2 numeric, kisw_kus2 numeric, sci2 numeric, ssre2 numeric, ss2 numeric, cre2 numeric)
)dev1");
              echo "<tr class='row100 body wider'>";

                  /* Deviations output */

              while ($c4Deviations = pg_fetch_assoc($deviationsc4)) {

                   echo "<td class='cell100 column50'>" . $c4Deviations['class_name'] . "</td>";
                   echo "<td class='cell100 column51'>" . $c4Deviations['maths'] . "</td>";
                   echo "<td class='cell100 column52'>" . $c4Deviations['dv_mat'] . "</td>";
                   echo "<td class='cell100 column53'>" . $c4Deviations['eng'] . "</td>";
                   echo "<td class='cell100 column54'>" . $c4Deviations['dv_eng'] . "</td>";
                   echo "<td class='cell100 column55'>" . $c4Deviations['eng_lang'] . "</td>";
                   echo "<td class='cell100 column56'>" . $c4Deviations['dv_eng_lang'] . "</td>";
                   echo "<td class='cell100 column57'>" . $c4Deviations['eng_comp'] . "</td>";
                   echo "<td class='cell100 column58'>" . $c4Deviations['dv_eng_comp'] . "</td>";
                   echo "<td class='cell100 column59'>" . $c4Deviations['kisw'] . "</td>";
                   echo "<td class='cell100 column60'>" . $c4Deviations['dv_kisw'] . "</td>";
                   echo "<td class='cell100 column61'>" . $c4Deviations['kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column62'>" . $c4Deviations['dv_kisw_lugh'] . "</td>";
                   echo "<td class='cell100 column63'>" . $c4Deviations['kisw_kus'] . "</td>";
                   echo "<td class='cell100 column64'>" . $c4Deviations['dv_kisw_kus'] . "</td>";
                   echo "<td class='cell100 column65'>" . $c4Deviations['sci'] . "</td>";
                   echo "<td class='cell100 column66'>" . $c4Deviations['dv_sci'] . "</td>";
                   echo "<td class='cell100 column67'>" . $c4Deviations['ssre'] . "</td>";
                   echo "<td class='cell100 column68'>" . $c4Deviations['dv_ssre'] . "</td>";
                   echo "<td class='cell100 column69'>" . $c4Deviations['ss'] . "</td>";
                   echo "<td class='cell100 column70'>" . $c4Deviations['dv_ss'] . "</td>";
                   echo "<td class='cell100 column71'>" . $c4Deviations['cre'] . "</td>";
                   echo "<td class='cell100 column72'>" . $c4Deviations['dv_cre'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body highlight' style='height:18px;'>";
                  echo "<td class='cell100 column50'><b>*</b></td>";
                  echo "<td class='cell100 column51'><b>*</b></td>";
                  echo "<td class='cell100 column52'><b>*</b></td>";
                  echo "<td class='cell100 column53'><b>*</b></td>";
                  echo "<td class='cell100 column54'><b>*</b></td>";
                  echo "<td class='cell100 column55'><b>*</b></td>";
                  echo "<td class='cell100 column56'><b>*</b></td>";
                  echo "<td class='cell100 column57'><b>*</b></td>";
                  echo "<td class='cell100 column58'><b>*</b></td>";
                  echo "<td class='cell100 column59'><b>*</b></td>";
                  echo "<td class='cell100 column60'><b>*</b></td>";
                  echo "<td class='cell100 column61'><b>*</b></td>";
                  echo "<td class='cell100 column62'><b>*</b></td>";
                  echo "<td class='cell100 column63'><b>*</b></td>";
                  echo "<td class='cell100 column64'><b>*</b></td>";
                  echo "<td class='cell100 column65'><b>*</b></td>";
                  echo "<td class='cell100 column66'><b>*</b></td>";
                  echo "<td class='cell100 column67'><b>*</b></td>";
                  echo "<td class='cell100 column68'><b>*</b></td>";
                  echo "<td class='cell100 column69'><b>*</b></td>";
                  echo "<td class='cell100 column70'><b>*</b></td>";
                  echo "<td class='cell100 column71'><b>*</b></td>";
                  echo "<td class='cell100 column72'><b>*</b></td>";
              echo "</tr>";

              ?>

            </tbody>
          </div>

        </table>
      </div>
    </div>
  </div>
    </div>

    <script type="text/javascript">
        var targetCells = document.getElementById("test_table3").getElementsByClassName("cell100");
        $(document).ready(function() {
            $('td').html(function(i, html){
              return html.replace(/0.00/g, "<b style='background-color:#91E4A4'>TOP</b>");
            });

            var c8meanTot = 0;
            $("#c8totals tr").each(function(){
                  c8meanTot += parseFloat($(this).find('.c8totalVals').text());
            });
            console.log("Our mean total = " + c8meanTot);
        });
    </script>

    <!-- Footer -->
    <footer class="py-2 bg-dark" style="position: fixed !important; bottom: 0 !important; width: 100% !important;">
      <div class="container">
        <p class="m-0 text-center text-white"><small>&copy; Eduweb <script type="text/javascript">document.write((new Date()).getFullYear())</script></small></p>
      </div>
      <!-- /.container -->
    </footer>
</body>
</html>
