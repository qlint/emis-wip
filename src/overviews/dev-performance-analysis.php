<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?></title>
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
    <div class="limiter" style="margin-top: -80px;">
      <div class="container-table100">
         <div class="wrap-table100">
           <div class="table100 ver1 m-b-110">
      <?php
        /* REMEMBER TO ENABLE > CREATE EXTENSION tablefunc; < ON THE DB IF NOT ALREADY ENABLED */

        // $db = pg_connect("host=localhost port=5432 dbname=eduweb_dev2 user=postgres password=postgres");
        $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
        $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");
        $i=0;$j=0;$k=0;$l=0;
      ?>
        <h1 style="margin-left:15px;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke Mean Analysis</h1>
        <button id="generate-excel" class="btn btn-success" style="margin-left:15px;">
            Generate Excel</button>
        <br /><br />
        <table id="test_table">
          <div id='t1' class="table100-head">
            <thead>
              <tr class="row100 head">
                <th class="cell100 column1">CLS.</th>
                <th class="cell100 column2">STR</th>
                <th class="cell100 column3">ROLL</th>
                <th class="cell100 column4">MAT.</th>
                <th class="cell100 column5">ENG.</th>
                <th class="cell100 column6">Lng.</th>
                <th class="cell100 column7">Cmp.</th>
                <th class="cell100 column8">KIS.</th>
                <th class="cell100 column9">Lgh.</th>
                <th class="cell100 column10">Kus.</th>
                <th class="cell100 column11">SCI.</th>
                <th class="cell100 column12">SS/RE.</th>
                <th class="cell100 column13">Ss.</th>
                <th class="cell100 column14">Cre.</th>
                <th class="cell100 column17">MEAN</th>
                <th class="cell100 column18">C/MEAN</th>
              </tr>
            </thead>
          </div>
          <div class="table100-body js-pscroll">
            <tbody>
              <?php
              /* CLASS 8 QUERY FOR SUBJECT MEAN SCORES */

              $c8ClassesAndSubjects = pg_query($db,"SELECT t1.*, t2.sum as mean
                                                  FROM
                                                  (
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, kusoma numeric, science numeric, ss_cre numeric, ss numeric, cre numeric)

                                                  ) AS t1
                                                      FULL OUTER JOIN
                                                      (
                                                    SELECT class_name as class_name2, sum(marks) FROM (
                                                                SELECT class_name, subject_name, sort_order, trunc(cast((marks::float/count::float) as numeric),2) AS marks FROM
                                                                (
                                                                  SELECT one.*, two.* FROM
                                                                  (
                                                                  SELECT subject_name, class_name, sort_order, count(mark) FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
                                                                      coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'parent') as parent_subject_name,exam_type,
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a WHERE mark is not null AND parent_subject_name = 'parent'
                                                                  GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )one
                                                                  FULL OUTER JOIN
                                                                  (
                                                                  SELECT subject_name AS subject_name2, class_name AS class_name2, sort_order AS sort_order2, sum(mark) as marks FROM (
                                                                    SELECT class_name,classes.class_id,subject_name,
                                                                      coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'parent') as parent_subject_name,exam_type,
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a
                                                                  WHERE parent_subject_name = 'parent'
                                                                  GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                              GROUP BY class_name ORDER BY class_name DESC
                                                      ) AS t2
                                                      ON t1.class_name = t2.class_name2
                                                      ORDER BY mean DESC");
              echo "<tr class='row100 body'>";
                  echo "<th class='cell100 column1' rowspan='4'>8</th>";

                  /* CLASS 8 TABLE FOR SUBJECT MEAN SCORES */

              while ($rowc8 = pg_fetch_assoc($c8ClassesAndSubjects)) {

                   // echo "<th class='cell100 column1' rowspan='4'>8</th>";
                   echo "<td class='cell100 column2'>" . $rowc8['class_name'] . "</td>";
                   echo "<td class='cell100 column3'>xx</td>";
                   echo "<td class='cell100 column4'>" . $rowc8['mathematics'] . "</td>";
                   echo "<td class='cell100 column5'>" . $rowc8['english'] . "</td>";
                   echo "<td class='cell100 column6'>" . $rowc8['eng_lang'] . "</td>";
                   echo "<td class='cell100 column7'>" . $rowc8['eng_comp'] . "</td>";
                   echo "<td class='cell100 column8'>" . $rowc8['kiswahili'] . "</td>";
                   echo "<td class='cell100 column9'>" . $rowc8['lugha'] . "</td>";
                   echo "<td class='cell100 column10'>" . $rowc8['kusoma'] . "</td>";
                   echo "<td class='cell100 column11'>" . $rowc8['science'] . "</td>";
                   echo "<td class='cell100 column12'>" . $rowc8['ss_cre'] . "</td>";
                   echo "<td class='cell100 column13'>" . $rowc8['ss'] . "</td>";
                   echo "<td class='cell100 column14'>" . $rowc8['cre'] . "</td>";
                   echo "<td class='cell100 column17'>" . $rowc8['mean'] . "</td>";
                   if (!$i++) echo "<th class='cell100 column18' rowspan='4'>" . max(array($rowc8['mean'])) . "</th>";
               echo "</tr>";
              }

              /* CLASS 8 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

              $c8meansCombined = pg_query($db,"SELECT * FROM   crosstab('SELECT 1, subject_name, marks FROM (
                    SELECT sort_order, subject_name, trunc(cast(avg(marks) as numeric),2) as marks FROM (
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                              GROUP BY subject_name, sort_order ORDER BY sort_order ASC
                                                            )five
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, kusoma numeric, science numeric, ss_cre numeric, ss numeric, cre numeric)");
              echo "<tr class='row100 body highlight' id='c8totals'>";
                echo "<td class='cell100 column1' colspan='3'><b>SUBJECT MEAN AVERAGE</b></td>";
                // echo "<td class='cell100 column2'>-</td>";
                // echo "<td class='cell100 column3'>Subject Avg</td>";
                while ($rowc8mean = pg_fetch_assoc($c8meansCombined)) {
                  echo "<td class='cell100 column4 c8totalVals'>" . $rowc8mean['mathematics'] . "</td>";
                  echo "<td class='cell100 column5 c8totalVals'>" . $rowc8mean['english'] . "</td>";
                  echo "<td class='cell100 column6 c8totalVals'>" . $rowc8mean['eng_lang'] . "</td>";
                  echo "<td class='cell100 column7 c8totalVals'>" . $rowc8mean['eng_comp'] . "</td>";
                  echo "<td class='cell100 column8 c8totalVals'>" . $rowc8mean['kiswahili'] . "</td>";
                  echo "<td class='cell100 column9 c8totalVals'>" . $rowc8mean['lugha'] . "</td>";
                  echo "<td class='cell100 column10 c8totalVals'>" . $rowc8mean['kusoma'] . "</td>";
                  echo "<td class='cell100 column11 c8totalVals'>" . $rowc8mean['science'] . "</td>";
                  echo "<td class='cell100 column12 c8totalVals'>" . $rowc8mean['ss_cre'] . "</td>";
                  echo "<td class='cell100 column13 c8totalVals'>" . $rowc8mean['ss'] . "</td>";
                  echo "<td class='cell100 column14 c8totalVals'>" . $rowc8mean['cre'] . "</td>";
                  echo "<td class='cell100 column19'>-</td>";
                  echo "<td class='cell100 column20'>-</td>";
                echo "</tr>";
                }

              ?>
              <tr class="row100 head">
                <th class="cell100 column1">*</th>
                <th class="cell100 column2">*</th>
                <th class="cell100 column3">*</th>
                <th class="cell100 column4">MAT.</th>
                <th class="cell100 column5">ENG.</th>
                <th class="cell100 column6">Lng.</th>
                <th class="cell100 column7">Cmp.</th>
                <th class="cell100 column8">KIS.</th>
                <th class="cell100 column9">Lug.</th>
                <th class="cell100 column10">Kus.</th>
                <th class="cell100 column11">SCI.</th>
                <th class="cell100 column12">SS/CRE.</th>
                <th class="cell100 column13">SS.</th>
                <th class="cell100 column14">CRE.</th>
                <th class="cell100 column16" colspan="2">SCHOOL MEAN (x&#772;)</th>
              </tr>
              <?php
                /* QUERY FOR TOTAL SUBJECT MEAN IN SCHOOL (MEAN OF TOTAL CLASS MEANS) */

                $totalMean = pg_query($db,"
                SELECT 1 as total, trunc(cast(avg(mathematics) as numeric),2) as mathematics, trunc(cast(avg(english) as numeric),2) as english, trunc(cast(avg(eng_lang) as numeric),2) as eng_lang, trunc(cast(avg(eng_comp) as numeric),2) as eng_com, trunc(cast(avg(kiswahili) as numeric),2) as kiswahili, trunc(cast(avg(lugha) as numeric),2) as lugha, trunc(cast(avg(kusoma) as numeric),2) as kusoma, trunc(cast(avg(science) as numeric),2) as science, trunc(cast(avg(ss_cre) as numeric),2) as ss_cre, trunc(cast(avg(ss) as numeric),2) as ss, trunc(cast(avg(cre) as numeric),2) as cre
                FROM (
                  SELECT * FROM   crosstab('SELECT 1, subject_name, marks FROM (
                    SELECT sort_order, subject_name, trunc(cast(avg(marks) as numeric),2) as marks FROM (
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                              GROUP BY subject_name, sort_order ORDER BY sort_order ASC
                                                            )five
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, kusoma numeric, science numeric, ss_cre numeric, ss numeric, cre numeric)
		             /*
                UNION
                 repeat above for other entities */
                )six");

                /*TABLE FOR TOTAL SCHOOL MEAN */

                echo "<tr class='row100 head'>";
                  echo "<th class='cell100 column1' colspan='3'>SCHOOL MEAN AVERAGE</th>";
                  // echo "<td class='cell100 column2'><b>*</b></td>";
                  // echo "<td class='cell100 column3'><b>*</b></td>";
                  while ($rowTotal = pg_fetch_assoc($totalMean)) {
                    echo "<td class='cell100 column4'><b>" . $rowTotal['mathematics'] . "</b></td>";
                    echo "<td class='cell100 column5'><b>" . $rowTotal['english'] . "</b></td>";
                    echo "<td class='cell100 column6'><b>" . $rowTotal['eng_lang'] . "</b></td>";
                    echo "<td class='cell100 column7'><b>" . $rowTotal['eng_com'] . "</b></td>";
                    echo "<td class='cell100 column8'><b>" . $rowTotal['kiswahili'] . "</b></td>";
                    echo "<td class='cell100 column9'><b>" . $rowTotal['lugha'] . "</b></td>";
                    echo "<td class='cell100 column10'><b>" . $rowTotal['kusoma'] . "</b></td>";
                    echo "<td class='cell100 column11'><b>" . $rowTotal['science'] . "</b></td>";
                    echo "<td class='cell100 column12'><b>" . $rowTotal['ss_cre'] . "</b></td>";
                    echo "<td class='cell100 column13'><b>" . $rowTotal['ss'] . "</b></td>";
                    echo "<td class='cell100 column14'><b>" . $rowTotal['cre'] . "</b></td>";
                    echo "<th class='cell100 column17' colspan='2'>TT</th>";
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

    <!-- GRADES ANALYSIS -->

    <div class="limiter" style="margin-top: -450px;">
      <div class="container-table100">
         <div class="wrap-table100">
           <div class="table100 ver1 m-b-110">

        <h1 style="margin-left:15px;">Class Marks Attainment Comparison</h1>
        <button id="generate-excel4" class="btn btn-success" style="margin-left:15px;">
            Generate Excel</button>
        <br /><br />
        <table id="test_table2">
          <div id='t1' class="table100-head">
            <thead>
              <tr class="row100 head">
                <th class="cell100 column31">CLASS</th>
                <th class="cell100 column32">EXAM</th>
                <th class="cell100 column33">400 +</th>
                <th class="cell100 column34">399 - 300</th>
                <th class="cell100 column35">299 - 200</th>
                <th class="cell100 column36">< 100</th>
              </tr>
            </thead>
          </div>
          <div class="table100-body js-pscroll">
            <tbody>
              <?php
              /* CLASS MARKS ATTAINMENT */

              $marksCount = pg_query($db,"SELECT class_name, exam_type, sum(four) as four, sum(three) as three, sum(two) as two, sum(below) as below FROM (
                                            SELECT class_name, class_id, exam_type, total_mark, --count(total_mark) as ttl_count
						(CASE
							WHEN total_mark > 399 THEN
								count(total_mark)

						END) as four,
						(CASE
							WHEN total_mark > 299 and total_mark < 400 THEN
								count(total_mark)

						END) as three,
						(CASE
							WHEN total_mark > 199 and total_mark < 300 THEN
								count(total_mark)

						END) as two,
						(CASE
							WHEN total_mark < 99 THEN
								count(total_mark)

						END) as below
                                            FROM (
                                            SELECT class_name, class_id, exam_type, total_mark FROM (
                                            SELECT student_name, class_name, class_id, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                            FROM (
                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
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
                                            WHERE class_cats.entity_id = 11
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=2)
                                            AND subjects.parent_subject_id is null
                                            AND subjects.use_for_grading is true
                                            AND mark IS NOT NULL
                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
                                            ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                            ORDER BY sort_order
                                            )v GROUP BY v.student_name, v.class_name, v.class_id, v.exam_type
                                            ORDER BY student_name ASC, exam_type ASC
                                            )a
                                            )b
                                            GROUP BY b.class_name, b.class_id, b.exam_type, b.total_mark
                                            ORDER BY 1)c
                                            GROUP BY class_name, exam_type");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* CLASS 8 TABLE FOR MARKS COUNT */

              while ($c8marks = pg_fetch_assoc($marksCount)) {

                   echo "<td class='cell100 column31'>" . $c8marks['class_name'] . "</td>";
                   echo "<td class='cell100 column32'>" . $c8marks['exam_type'] . "</td>";
                   echo "<td class='cell100 column33'>" . $c8marks['four'] . "</td>";
                   echo "<td class='cell100 column34'>" . $c8marks['three'] . "</td>";
                   echo "<td class='cell100 column35'>" . $c8marks['two'] . "</td>";
                   echo "<td class='cell100 column36'>" . $c8marks['below'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body highlight' style='height:13px;'>";
                echo "<th class='cell100 column31'><b>*</b></th>";
                echo "<th class='cell100 column32'><b>*</b></th>";
                echo "<th class='cell100 column33'><b>*</b></th>";
                echo "<th class='cell100 column34'><b>*</b></th>";
                echo "<th class='cell100 column35'><b>*</b></th>";
                echo "<th class='cell100 column36'><b>*</b></th>";
              echo "</tr>";

              /* SCHOOL GRADE COUNT TOTALS */

              $marksTotForC8 = pg_query($db,"SELECT '' as class_name, '' as exam_type, SUM(four) as four, sum(three) as three, sum(two) as two, sum(below) as below FROM (
					SELECT class_name, exam_type, sum(four) as four, sum(three) as three, sum(two) as two, sum(below) as below FROM (
                                            SELECT class_name, class_id, exam_type, total_mark, --count(total_mark) as ttl_count
						(CASE
							WHEN total_mark > 399 THEN
								count(total_mark)

						END) as four,
						(CASE
							WHEN total_mark > 299 and total_mark < 400 THEN
								count(total_mark)

						END) as three,
						(CASE
							WHEN total_mark > 199 and total_mark < 300 THEN
								count(total_mark)

						END) as two,
						(CASE
							WHEN total_mark < 99 THEN
								count(total_mark)

						END) as below
                                            FROM (
                                            SELECT class_name, class_id, exam_type, total_mark FROM (
                                            SELECT student_name, class_name, class_id, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                            FROM (
                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
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
                                            WHERE class_cats.entity_id = 11
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=2)
                                            AND subjects.parent_subject_id is null
                                            AND subjects.use_for_grading is true
                                            AND mark IS NOT NULL
                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
                                            ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                            ORDER BY sort_order
                                            )v GROUP BY v.student_name, v.class_name, v.class_id, v.exam_type
                                            ORDER BY student_name ASC, exam_type ASC
                                            )a
                                            )b
                                            GROUP BY b.class_name, b.class_id, b.exam_type, b.total_mark
                                            ORDER BY 1)c
                                            GROUP BY class_name, exam_type
                                            )d");
              echo "<tr class='row100 body'>";

                  /* TOTAL MARKS ATTAINMENT COUNT */

              while ($c8marksTot = pg_fetch_assoc($marksTotForC8)) {

                   echo "<td class='cell100 column31'><b>OVERALL</b></td>";
                   echo "<td class='cell100 column32'><b>*</b></td>";
                   echo "<td class='cell100 column33'><b>" . $c8marksTot['four'] . "</b></td>";
                   echo "<td class='cell100 column34'><b>" . $c8marksTot['three'] . "</b></td>";
                   echo "<td class='cell100 column35'><b>" . $c8marksTot['two'] . "</b></td>";
                   echo "<td class='cell100 column36'><b>" . $c8marksTot['below'] . "</b></td>";
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

    <!-- GRADES ANALYSIS -->

    <div class="limiter" style="margin-top: -450px;">
      <div class="container-table100">
         <div class="wrap-table100">
           <div class="table100 ver1 m-b-110">

        <h1 style="margin-left:15px;">Class Grade Attainment Comparison</h1>
        <button id="generate-excel2" class="btn btn-success" style="margin-left:15px;">
            Generate Excel</button>
        <br /><br />
        <table id="test_table2">
          <div id='t1' class="table100-head">
            <thead>
              <tr class="row100 head">
                <th class="cell100 column31">CLASS</th>
                <th class="cell100 column32">EXAM</th>
                <th class="cell100 column33">A</th>
                <th class="cell100 column34">A-</th>
                <th class="cell100 column35">B+</th>
                <th class="cell100 column36">B</th>
                <th class="cell100 column37">B-</th>
                <th class="cell100 column38">C+</th>
                <th class="cell100 column39">C</th>
                <th class="cell100 column40">C-</th>
                <th class="cell100 column41">D+</th>
                <th class="cell100 column42">D</th>
                <th class="cell100 column43">D-</th>
                <th class="cell100 column44">E</th>
              </tr>
            </thead>
          </div>
          <div class="table100-body js-pscroll">
            <tbody>
              <?php
              /* CLASS 8 QUERY FOR GRADE COUNT */

              $c8GradeCount = pg_query($db,"SELECT t2.exam_type, t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT class_name, grade, grd_count FROM (
                                            SELECT class_name, class_id, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, class_id, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, class_id, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                            FROM (
                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
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
                                            WHERE class_cats.entity_id = 11
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=2)
                                            AND subjects.parent_subject_id is null
                                            AND subjects.use_for_grading is true
                                            AND mark IS NOT NULL
                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
                                            ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                            ORDER BY sort_order
                                            )v GROUP BY v.student_name, v.class_name, v.class_id, v.exam_type
                                            ORDER BY student_name ASC, exam_type ASC
                                            )a
                                            )b
                                            GROUP BY b.class_name, b.class_id, b.exam_type, b.grade
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (class_id text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

                                            ) AS t1
                                                FULL OUTER JOIN
                                                (
                                                SELECT DISTINCT exam_type, class_name, class_id FROM(
                                            SELECT class_name, class_id,  exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, class_id, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, class_id, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                            FROM (
                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
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
                                            WHERE class_cats.entity_id = 11
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=2)
                                            AND subjects.parent_subject_id is null
                                            AND subjects.use_for_grading is true
                                            AND mark IS NOT NULL
                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
                                            ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                            ORDER BY sort_order
                                            )v GROUP BY v.student_name, v.class_name, v.class_id, v.exam_type
                                            ORDER BY student_name ASC, exam_type ASC
                                            )a
                                            )b
                                            GROUP BY b.class_name, b.class_id, b.exam_type, b.grade
                                            )c
                                            ) AS t2
                                                ON t1.class_id = t2.class_name");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* CLASS 8 TABLE FOR GRADE COUNT */

              while ($rowc8grade = pg_fetch_assoc($c8GradeCount)) {

                   echo "<td class='cell100 column31'>" . $rowc8grade['class_name'] . "</td>";
                   echo "<td class='cell100 column32'>" . $rowc8grade['exam_type'] . "</td>";
                   echo "<td class='cell100 column33'>" . $rowc8grade['a'] . "</td>";
                   echo "<td class='cell100 column34'>" . $rowc8grade['a_m'] . "</td>";
                   echo "<td class='cell100 column35'>" . $rowc8grade['b_p'] . "</td>";
                   echo "<td class='cell100 column36'>" . $rowc8grade['b'] . "</td>";
                   echo "<td class='cell100 column37'>" . $rowc8grade['b_m'] . "</td>";
                   echo "<td class='cell100 column38'>" . $rowc8grade['c_p'] . "</td>";
                   echo "<td class='cell100 column39'>" . $rowc8grade['c'] . "</td>";
                   echo "<td class='cell100 column40'>" . $rowc8grade['c_m'] . "</td>";
                   echo "<td class='cell100 column41'>" . $rowc8grade['d_p'] . "</td>";
                   echo "<td class='cell100 column42'>" . $rowc8grade['d'] . "</td>";
                   echo "<td class='cell100 column43'>" . $rowc8grade['d_m'] . "</td>";
                   echo "<td class='cell100 column44'>" . $rowc8grade['e'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body' style='height:13px;'>";
                echo "<th class='cell100 column31'><b>*</b></th>";
                echo "<th class='cell100 column32'><b>*</b></th>";
                echo "<th class='cell100 column33'><b>*</b></th>";
                echo "<th class='cell100 column34'><b>*</b></th>";
                echo "<th class='cell100 column35'><b>*</b></th>";
                echo "<th class='cell100 column36'><b>*</b></th>";
                echo "<th class='cell100 column37'><b>*</b></th>";
                echo "<th class='cell100 column38'><b>*</b></th>";
                echo "<th class='cell100 column39'><b>*</b></th>";
                echo "<th class='cell100 column40'><b>*</b></th>";
                echo "<th class='cell100 column41'><b>*</b></th>";
                echo "<th class='cell100 column42'><b>*</b></th>";
                echo "<th class='cell100 column43'><b>*</b></th>";
                echo "<th class='cell100 column44'><b>*</b></th>";
              echo "</tr>";

              /* SCHOOL GRADE COUNT TOTALS */

              $gradeTotals = pg_query($db,"SELECT 1 AS totals, sum(a) AS a, sum(a_m) AS a_m, sum(b_p) as b_p, sum(b) as b, sum(b_m) as b_m, sum(c_p) as c_p, sum(c) as c, sum(c_m) as c_m, sum(d_p) as d_p, sum(d) as d, sum(d_m) as d_m, sum(e) as e FROM (
					    SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT class_name, grade, grd_count FROM (
                                            SELECT class_name, class_id, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, class_id, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, class_id, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, class_id, exam_type, subject_name,
                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                            FROM (
                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
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
                                            WHERE class_cats.entity_id = 11
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=2)
                                            AND subjects.parent_subject_id is null
                                            AND subjects.use_for_grading is true
                                            AND mark IS NOT NULL
                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
                                            ) q GROUP BY student_name, class_name, class_id, exam_type, subject_name, sort_order
                                            ORDER BY sort_order
                                            )v GROUP BY v.student_name, v.class_name, v.class_id, v.exam_type
                                            ORDER BY student_name ASC, exam_type ASC
                                            )a
                                            )b
                                            GROUP BY b.class_name, b.class_id, b.exam_type, b.grade
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (class_id text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

                                            ) AS t1
                                                FULL OUTER JOIN
                                                (
                                                SELECT DISTINCT exam_type, class_name FROM(
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
                                            sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight, sort_order
                                            FROM (
                                            SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
                                              coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
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
                                            WHERE class_cats.entity_id = 11
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=2)
                                            AND subjects.parent_subject_id is null
                                            AND subjects.use_for_grading is true
                                            AND mark IS NOT NULL
                                            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
                                            ) q GROUP BY student_name, class_name, exam_type, subject_name, sort_order
                                            ORDER BY sort_order
                                            )v GROUP BY v.student_name, v.class_name, v.exam_type
                                            ORDER BY student_name ASC, exam_type ASC
                                            )a
                                            )b
                                            GROUP BY b.class_name, b.exam_type, b.grade
                                            )c
                                            ) AS t2
                                                ON t1.class_id = t2.class_name
                                            /*UNION
                                            REPLICATE ABOVE TO OTHER ENTITIES*/
                                            )tot");
              echo "<tr class='row100 body'>";

                  /* GRADE TOTALS TABLE */

              while ($gTotals = pg_fetch_assoc($gradeTotals)) {

                   echo "<td class='cell100 column31'><b>OVERALL</b></td>";
                   echo "<td class='cell100 column32'><b>*</b></td>";
                   echo "<td class='cell100 column33'><b>" . $gTotals['a'] . "</b></td>";
                   echo "<td class='cell100 column34'><b>" . $gTotals['a_m'] . "</b></td>";
                   echo "<td class='cell100 column35'><b>" . $gTotals['b_p'] . "</b></td>";
                   echo "<td class='cell100 column36'><b>" . $gTotals['b'] . "</b></td>";
                   echo "<td class='cell100 column37'><b>" . $gTotals['b_m'] . "</b></td>";
                   echo "<td class='cell100 column38'><b>" . $gTotals['c_p'] . "</b></td>";
                   echo "<td class='cell100 column39'><b>" . $gTotals['c'] . "</b></td>";
                   echo "<td class='cell100 column40'><b>" . $gTotals['c_m'] . "</b></td>";
                   echo "<td class='cell100 column41'><b>" . $gTotals['d_p'] . "</b></td>";
                   echo "<td class='cell100 column42'><b>" . $gTotals['d'] . "</b></td>";
                   echo "<td class='cell100 column43'><b>" . $gTotals['d_m'] . "</b></td>";
                   echo "<td class='cell100 column44'><b>" . $gTotals['e'] . "</b></td>";
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

    <div class="limiter" style="margin-top: -450px;">
      <div class="container-table100">
         <div class="wrap-table100">
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
                <th class="cell100 column63">KUS</th>
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
              /* DEVIATIONS */

              $deviations = pg_query($db,"SELECT class_name, maths, (maths - maths2) as dv_mat, eng, (eng - eng2) as dv_eng, eng_lang, (eng_lang - eng_lang2) as dv_eng_lang, eng_comp, (eng_comp - eng_comp2) as dv_eng_comp, kisw, (kisw - kisw2) as dv_kisw, kisw_lugh, (kisw_lugh - kisw_lugh2) as dv_kisw_lugh, kisw_kus, (kisw_kus - kisw_kus2) as dv_kisw_kus, sci, (sci - sci2) as dv_sci, ssre, (ssre - ssre2) as dv_ssre, ss, (ss - ss2) as dv_ss, cre, (cre - cre2) as dv_cre FROM (
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
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
                                                                    AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
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

              while ($c8Deviations = pg_fetch_assoc($deviations)) {

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
