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
    <link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/mean.css">
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
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Performance Analysis</span></a>
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
    <div class="limiter">
      <div class="container-table100">
         <div class="wrap-table100">
           <div class="table100 ver1 m-b-110">
      <?php
        /* REMEMBER TO ENABLE > CREATE EXTENSION tablefunc; < ON THE DB IF NOT ALREADY ENABLED */

        // $db = pg_connect("host=localhost port=5432 dbname=eduweb_kingsinternational user=postgres password=postgres");
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
                <th class="cell100 column10">Ins.</th>
                <th class="cell100 column11">SCI.</th>
                <th class="cell100 column12">SS/RE.</th>
                <th class="cell100 column13">Ss.</th>
                <th class="cell100 column14">Cre.</th>
                <th class="cell100 column15">COM.</th>
                <th class="cell100 column16">FRE.</th>
                <th class="cell100 column17">MEAN</th>
                <th class="cell100 column18">C/MEAN</th>
              </tr>
            </thead>
          </div>
          <div class="table100-body js-pscroll">
            <tbody>
              <?php
              /* CLASS 6 QUERY FOR SUBJECT MEAN SCORES */

              $c6ClassesAndSubjects = pg_query($db,"SELECT t1.*, t2.sum as mean
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
                                                                    WHERE class_cats.entity_id = 9
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)

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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                              GROUP BY class_name ORDER BY class_name DESC
                                                      ) AS t2
                                                      ON t1.class_name = t2.class_name2");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* CLASS 6 TABLE FOR SUBJECT MEAN SCORES */

              while ($rowc6 = pg_fetch_assoc($c6ClassesAndSubjects)) {

                   echo "<th class='cell100 column1' rowspan='1'>6</th>";
                   echo "<td class='cell100 column2'>" . $rowc6['class_name'] . "</td>";
                   echo "<td class='cell100 column3'>xx</td>";
                   echo "<td class='cell100 column4'>" . $rowc6['mathematics'] . "</td>";
                   echo "<td class='cell100 column5'>" . $rowc6['english'] . "</td>";
                   echo "<td class='cell100 column6'>" . $rowc6['eng_lang'] . "</td>";
                   echo "<td class='cell100 column7'>" . $rowc6['eng_comp'] . "</td>";
                   echo "<td class='cell100 column8'>" . $rowc6['kiswahili'] . "</td>";
                   echo "<td class='cell100 column9'>" . $rowc6['lugha'] . "</td>";
                   echo "<td class='cell100 column10'>" . $rowc6['insha'] . "</td>";
                   echo "<td class='cell100 column11'>" . $rowc6['science'] . "</td>";
                   echo "<td class='cell100 column12'>" . $rowc6['ss_cre'] . "</td>";
                   echo "<td class='cell100 column13'>" . $rowc6['ss'] . "</td>";
                   echo "<td class='cell100 column14'>" . $rowc6['cre'] . "</td>";
                   echo "<td class='cell100 column15'>" . $rowc6['computer'] . "</td>";
                   echo "<td class='cell100 column16'>" . $rowc6['french'] . "</td>";
                   echo "<td class='cell100 column17'>" . $rowc6['mean'] . "</td>";
                   echo "<th class='cell100 column18' rowspan='1'>" . max(array($rowc6['mean'])) . "</th>";
               echo "</tr>";
              }

              /* CLASS 6 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

              $c6meansCombined = pg_query($db,"SELECT * FROM   crosstab('SELECT 1, subject_name, marks FROM (
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
                                                                    WHERE class_cats.entity_id = 9
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)");
              echo "<tr class='row100 body highlight'>";
                echo "<td class='cell100 column1'>-</td>";
                echo "<td class='cell100 column2'>-</td>";
                echo "<td class='cell100 column3'>Subject Avg</td>";
                while ($rowc6mean = pg_fetch_assoc($c6meansCombined)) {
                  echo "<td class='cell100 column4'>" . $rowc6mean['mathematics'] . "</td>";
                  echo "<td class='cell100 column5'>" . $rowc6mean['english'] . "</td>";
                  echo "<td class='cell100 column6'>" . $rowc6mean['eng_lang'] . "</td>";
                  echo "<td class='cell100 column7'>" . $rowc6mean['eng_comp'] . "</td>";
                  echo "<td class='cell100 column8'>" . $rowc6mean['kiswahili'] . "</td>";
                  echo "<td class='cell100 column9'>" . $rowc6mean['lugha'] . "</td>";
                  echo "<td class='cell100 column10'>" . $rowc6mean['insha'] . "</td>";
                  echo "<td class='cell100 column11'>" . $rowc6mean['science'] . "</td>";
                  echo "<td class='cell100 column12'>" . $rowc6mean['ss_cre'] . "</td>";
                  echo "<td class='cell100 column13'>" . $rowc6mean['ss'] . "</td>";
                  echo "<td class='cell100 column14'>" . $rowc6mean['cre'] . "</td>";
                  echo "<td class='cell100 column15'>" . $rowc6mean['computer'] . "</td>";
                  echo "<td class='cell100 column16'>" . $rowc6mean['french'] . "</td>";
                  echo "<td class='cell100 column19'>-</td>";
                  echo "<td class='cell100 column20'>-</td>";
                echo "</tr>";
                }

                /* CLASS 5 QUERY FOR SUBJECT MEAN SCORES */

              $c5ClassesAndSubjects = pg_query($db,"SELECT t1.*, t2.sum as mean
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
                                                                    WHERE class_cats.entity_id = 8
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)

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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                              GROUP BY class_name ORDER BY class_name DESC
                                                      ) AS t2
                                                      ON t1.class_name = t2.class_name2");

              /* CLASS 5 TABLE FOR SUBJECT MEAN SCORES */

              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>5</th>";

                  /* CLASS 5 TABLE FOR SUBJECT MEAN SCORES */

                  while ($rowc5 = pg_fetch_assoc($c5ClassesAndSubjects)) {


                      echo "<th class='cell100 column1' rowspan='1'>5</th>";
                       echo "<td class='cell100 column2'>" . $rowc5['class_name'] . "</td>";
                       echo "<td class='cell100 column3'>xx</td>";
                       echo "<td class='cell100 column4'>" . $rowc5['mathematics'] . "</td>";
                       echo "<td class='cell100 column5'>" . $rowc5['english'] . "</td>";
                       echo "<td class='cell100 column6'>" . $rowc5['eng_lang'] . "</td>";
                       echo "<td class='cell100 column7'>" . $rowc5['eng_comp'] . "</td>";
                       echo "<td class='cell100 column8'>" . $rowc5['kiswahili'] . "</td>";
                       echo "<td class='cell100 column9'>" . $rowc5['lugha'] . "</td>";
                       echo "<td class='cell100 column10'>" . $rowc5['insha'] . "</td>";
                       echo "<td class='cell100 column11'>" . $rowc5['science'] . "</td>";
                       echo "<td class='cell100 column12'>" . $rowc5['ss_cre'] . "</td>";
                       echo "<td class='cell100 column13'>" . $rowc5['ss'] . "</td>";
                       echo "<td class='cell100 column14'>" . $rowc5['cre'] . "</td>";
                       echo "<td class='cell100 column15'>" . $rowc5['computer'] . "</td>";
                       echo "<td class='cell100 column16'>" . $rowc5['french'] . "</td>";
                       echo "<td class='cell100 column17'>" . $rowc5['mean'] . "</td>";
                       echo "<th class='cell100 column18' rowspan='1'>" . max(array($rowc5['mean'])) . "</th>";
                   echo "</tr>";
                  }

              /* CLASS 5 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

              $c5meansCombined = pg_query($db,"SELECT * FROM   crosstab('SELECT 1, subject_name, marks FROM (
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
                                                                    WHERE class_cats.entity_id = 8
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)");
              echo "<tr class='row100 body highlight'>";
                echo "<td class='cell100 column1'>-</td>";
                echo "<td class='cell100 column2'>-</td>";
                echo "<td class='cell100 column3'>Subject Avg</td>";
                while ($rowc5mean = pg_fetch_assoc($c5meansCombined)) {
                  echo "<td class='cell100 column4'>" . $rowc5mean['mathematics'] . "</td>";
                  echo "<td class='cell100 column5'>" . $rowc5mean['english'] . "</td>";
                  echo "<td class='cell100 column6'>" . $rowc5mean['eng_lang'] . "</td>";
                  echo "<td class='cell100 column7'>" . $rowc5mean['eng_comp'] . "</td>";
                  echo "<td class='cell100 column8'>" . $rowc5mean['kiswahili'] . "</td>";
                  echo "<td class='cell100 column9'>" . $rowc5mean['lugha'] . "</td>";
                  echo "<td class='cell100 column10'>" . $rowc5mean['insha'] . "</td>";
                  echo "<td class='cell100 column11'>" . $rowc5mean['science'] . "</td>";
                  echo "<td class='cell100 column12'>" . $rowc5mean['ss_cre'] . "</td>";
                  echo "<td class='cell100 column13'>" . $rowc5mean['ss'] . "</td>";
                  echo "<td class='cell100 column14'>" . $rowc5mean['cre'] . "</td>";
                  echo "<td class='cell100 column15'>" . $rowc5mean['computer'] . "</td>";
                  echo "<td class='cell100 column16'>" . $rowc5mean['french'] . "</td>";
                  echo "<td class='cell100 column19'>-</td>";
                  echo "<td class='cell100 column20'>-</td>";
                echo "</tr>";
                }

              /* CLASS 4 QUERY FOR SUBJECT MEAN SCORES */

              $c4ClassesAndSubjects = pg_query($db,"SELECT t1.*, t2.sum as mean
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
                                                                    WHERE class_cats.entity_id = 7
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)

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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                              GROUP BY class_name ORDER BY class_name DESC
                                                      ) AS t2
                                                      ON t1.class_name = t2.class_name2");

              /* CLASS 4 TABLE FOR SUBJECT MEAN SCORES */

              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>4</th>";

                  while ($rowc4 = pg_fetch_assoc($c4ClassesAndSubjects)) {

                        echo "<th class='cell100 column1' rowspan='1'>4</th>";
                       echo "<td class='cell100 column2'>" . $rowc4['class_name'] . "</td>";
                       echo "<td class='cell100 column3'>xx</td>";
                       echo "<td class='cell100 column4'>" . $rowc4['mathematics'] . "</td>";
                       echo "<td class='cell100 column5'>" . $rowc4['english'] . "</td>";
                       echo "<td class='cell100 column6'>" . $rowc4['eng_lang'] . "</td>";
                       echo "<td class='cell100 column7'>" . $rowc4['eng_comp'] . "</td>";
                       echo "<td class='cell100 column8'>" . $rowc4['kiswahili'] . "</td>";
                       echo "<td class='cell100 column9'>" . $rowc4['lugha'] . "</td>";
                       echo "<td class='cell100 column10'>" . $rowc4['insha'] . "</td>";
                       echo "<td class='cell100 column11'>" . $rowc4['science'] . "</td>";
                       echo "<td class='cell100 column12'>" . $rowc4['ss_cre'] . "</td>";
                       echo "<td class='cell100 column13'>" . $rowc4['ss'] . "</td>";
                       echo "<td class='cell100 column14'>" . $rowc4['cre'] . "</td>";
                       echo "<td class='cell100 column15'>" . $rowc4['computer'] . "</td>";
                       echo "<td class='cell100 column16'>" . $rowc4['french'] . "</td>";
                       echo "<td class='cell100 column17'>" . $rowc4['mean'] . "</td>";
                       echo "<th class='cell100 column18' rowspan='1'>" . max(array($rowc4['mean'])) . "</th>";
                   echo "</tr>";
                  }

              /* CLASS 4 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

              $c4meansCombined = pg_query($db,"SELECT * FROM   crosstab('SELECT 1, subject_name, marks FROM (
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
                                                                    WHERE class_cats.entity_id = 7
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)");
              echo "<tr class='row100 body highlight'>";
              echo "<td class='cell100 column1'>-</td>";
              echo "<td class='cell100 column2'>-</td>";
              echo "<td class='cell100 column3'>Subject Avg</td>";
              while ($rowc4mean = pg_fetch_assoc($c4meansCombined)) {
                echo "<td class='cell100 column4'>" . $rowc4mean['mathematics'] . "</td>";
                echo "<td class='cell100 column5'>" . $rowc4mean['english'] . "</td>";
                echo "<td class='cell100 column6'>" . $rowc4mean['eng_lang'] . "</td>";
                echo "<td class='cell100 column7'>" . $rowc4mean['eng_comp'] . "</td>";
                echo "<td class='cell100 column8'>" . $rowc4mean['kiswahili'] . "</td>";
                echo "<td class='cell100 column9'>" . $rowc4mean['lugha'] . "</td>";
                echo "<td class='cell100 column10'>" . $rowc4mean['insha'] . "</td>";
                echo "<td class='cell100 column11'>" . $rowc4mean['science'] . "</td>";
                echo "<td class='cell100 column12'>" . $rowc4mean['ss_cre'] . "</td>";
                echo "<td class='cell100 column13'>" . $rowc4mean['ss'] . "</td>";
                echo "<td class='cell100 column14'>" . $rowc4mean['cre'] . "</td>";
                echo "<td class='cell100 column15'>" . $rowc4mean['computer'] . "</td>";
                echo "<td class='cell100 column16'>" . $rowc4mean['french'] . "</td>";
                echo "<td class='cell100 column19'>-</td>";
                echo "<td class='cell100 column20'>-</td>";
              echo "</tr>";
              }

              /* CLASS 3 QUERY FOR SUBJECT MEAN SCORES */

              $c3ClassesAndSubjects = pg_query($db,"SELECT t1.*, t2.sum as mean
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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 6 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)

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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
                                                                    AND subjects.use_for_grading is true
                                                                    AND students.active is true
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                                                                  )a GROUP BY a.subject_name, a.class_name, a.sort_order ORDER BY sort_order
                                                                  )two
                                                                  ON one.class_name = two.class_name2 AND one.subject_name = two.subject_name2
                                                                )three
                                                                ORDER BY class_name DESC, sort_order
                                                              )four
                                                              GROUP BY class_name ORDER BY class_name DESC
                                                      ) AS t2
                                                      ON t1.class_name = t2.class_name2");

              /* CLASS 3 TABLE FOR SUBJECT MEAN SCORES */

              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>3</th>";
                  while ($rowc3 = pg_fetch_assoc($c3ClassesAndSubjects)) {

                        echo "<th class='cell100 column1' rowspan='1'>3</th>";
                       echo "<td class='cell100 column2'>" . $rowc3['class_name'] . "</td>";
                       echo "<td class='cell100 column3'>xx</td>";
                       echo "<td class='cell100 column4'>" . $rowc3['mathematics'] . "</td>";
                       echo "<td class='cell100 column5'>" . $rowc3['english'] . "</td>";
                       echo "<td class='cell100 column6'>" . $rowc3['eng_lang'] . "</td>";
                       echo "<td class='cell100 column7'>" . $rowc3['eng_comp'] . "</td>";
                       echo "<td class='cell100 column8'>" . $rowc3['kiswahili'] . "</td>";
                       echo "<td class='cell100 column9'>" . $rowc3['lugha'] . "</td>";
                       echo "<td class='cell100 column10'>" . $rowc3['insha'] . "</td>";
                       echo "<td class='cell100 column11'>" . $rowc3['science'] . "</td>";
                       echo "<td class='cell100 column12'>" . $rowc3['ss_cre'] . "</td>";
                       echo "<td class='cell100 column13'>" . $rowc3['ss'] . "</td>";
                       echo "<td class='cell100 column14'>" . $rowc3['cre'] . "</td>";
                       echo "<td class='cell100 column15'>" . $rowc3['computer'] . "</td>";
                       echo "<td class='cell100 column16'>" . $rowc3['french'] . "</td>";
                       echo "<td class='cell100 column17'>" . $rowc3['mean'] . "</td>";
                       echo "<th class='cell100 column18' rowspan='1'>" . max(array($rowc3['mean'])) . "</th>";
                   echo "</tr>";
                  }

              /* CLASS 3 COMBINED SUBJECT MEANS (MEANS OF THE MEANS) */

              $c3meansCombined = pg_query($db,"SELECT * FROM   crosstab('SELECT 1, subject_name, marks FROM (
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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 6 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)");
              echo "<tr class='row100 body highlight'>";
              echo "<td class='cell100 column1'>Subject Avg</td>";
              echo "<td class='cell100 column2'>-</td>";
              echo "<td class='cell100 column3'>-</td>";
              while ($rowc3mean = pg_fetch_assoc($c3meansCombined)) {
                echo "<td class='cell100 column4'>" . $rowc3mean['mathematics'] . "</td>";
                echo "<td class='cell100 column5'>" . $rowc3mean['english'] . "</td>";
                echo "<td class='cell100 column6'>" . $rowc3mean['eng_lang'] . "</td>";
                echo "<td class='cell100 column7'>" . $rowc3mean['eng_comp'] . "</td>";
                echo "<td class='cell100 column8'>" . $rowc3mean['kiswahili'] . "</td>";
                echo "<td class='cell100 column9'>" . $rowc3mean['lugha'] . "</td>";
                echo "<td class='cell100 column10'>" . $rowc3mean['insha'] . "</td>";
                echo "<td class='cell100 column11'>" . $rowc3mean['science'] . "</td>";
                echo "<td class='cell100 column12'>" . $rowc3mean['ss_cre'] . "</td>";
                echo "<td class='cell100 column13'>" . $rowc3mean['ss'] . "</td>";
                echo "<td class='cell100 column14'>" . $rowc3mean['cre'] . "</td>";
                echo "<td class='cell100 column15'>" . $rowc3mean['computer'] . "</td>";
                echo "<td class='cell100 column16'>" . $rowc3mean['french'] . "</td>";
                echo "<td class='cell100 column19'>-</td>";
                echo "<td class='cell100 column20'>-</td>";
              echo "</tr>";
              }
              ?>
              <tr class="row100 head">
                <th class="cell100 column1">-</th>
                <th class="cell100 column2">-</th>
                <th class="cell100 column3">Overall</th>
                <th class="cell100 column4">MAT.</th>
                <th class="cell100 column5">ENG.</th>
                <th class="cell100 column6">Lng.</th>
                <th class="cell100 column7">Cmp.</th>
                <th class="cell100 column8">KIS.</th>
                <th class="cell100 column9">Lug.</th>
                <th class="cell100 column10">Ins.</th>
                <th class="cell100 column11">SCI.</th>
                <th class="cell100 column12">SS/CRE.</th>
                <th class="cell100 column13">SS.</th>
                <th class="cell100 column14">CRE.</th>
                <th class="cell100 column15">COM.</th>
                <th class="cell100 column16">FRNCH.</th>
                <th class="cell100 column16" colspan="2">SCHOOL MEAN (x&#772;)</th>
              </tr>
              <?php
                /* QUERY FOR TOTAL SUBJECT MEAN IN SCHOOL (MEAN OF TOTAL CLASS MEANS) */

                $totalMean = pg_query($db,"
                SELECT 1 as total, trunc(cast(avg(mathematics) as numeric),2) as mathematics, trunc(cast(avg(english) as numeric),2) as english, trunc(cast(avg(eng_lang) as numeric),2) as eng_lang, trunc(cast(avg(eng_comp) as numeric),2) as eng_com, trunc(cast(avg(kiswahili) as numeric),2) as kiswahili, trunc(cast(avg(lugha) as numeric),2) as lugha, trunc(cast(avg(insha) as numeric),2) as insha, trunc(cast(avg(science) as numeric),2) as science, trunc(cast(avg(ss_cre) as numeric),2) as ss_cre, trunc(cast(avg(ss) as numeric),2) as ss, trunc(cast(avg(cre) as numeric),2) as cre, trunc(cast(avg(computer) as numeric),2) as computer, trunc(cast(avg(french) as numeric),2) as french
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
                                                                    WHERE class_cats.entity_id = 9
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 9 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)

                UNION
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
                                                                    WHERE class_cats.entity_id = 8
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 8 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)

                UNION
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
                                                                    WHERE class_cats.entity_id = 7
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 7 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)

                UNION
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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                                    WHERE class_cats.entity_id = 6
                                                                    AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
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
                                                  ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 6 LIMIT 1) order by sort_order') AS ct (class_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric, computer numeric, french numeric)
                )six");

                /*TABLE FOR TOTAL SCHOOL MEAN */

                echo "<tr class='row100 head'>";
                  echo "<th class='cell100 column1'>*</th>";
                  echo "<td class='cell100 column2'>*</td>";
                  echo "<td class='cell100 column3'>*</td>";
                  while ($rowTotal = pg_fetch_assoc($totalMean)) {
                    echo "<td class='cell100 column4'><b>" . $rowTotal['mathematics'] . "</b></td>";
                    echo "<td class='cell100 column5'><b>" . $rowTotal['english'] . "</b></td>";
                    echo "<td class='cell100 column6'><b>" . $rowTotal['eng_lang'] . "</b></td>";
                    echo "<td class='cell100 column7'><b>" . $rowTotal['eng_com'] . "</b></td>";
                    echo "<td class='cell100 column8'><b>" . $rowTotal['kiswahili'] . "</b></td>";
                    echo "<td class='cell100 column9'><b>" . $rowTotal['lugha'] . "</b></td>";
                    echo "<td class='cell100 column10'><b>" . $rowTotal['insha'] . "</b></td>";
                    echo "<td class='cell100 column11'><b>" . $rowTotal['science'] . "</b></td>";
                    echo "<td class='cell100 column12'><b>" . $rowTotal['ss_cre'] . "</b></td>";
                    echo "<td class='cell100 column13'><b>" . $rowTotal['ss'] . "</b></td>";
                    echo "<td class='cell100 column14'><b>" . $rowTotal['cre'] . "</b></td>";
                    echo "<td class='cell100 column15'><b>" . $rowTotal['computer'] . "</b></td>";
                    echo "<td class='cell100 column16'><b>" . $rowTotal['french'] . "</b></td>";
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

    <div class="limiter" style="margin-top: -210px;">
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
                <th class="cell100 column31">CLS.</th>
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
              /* CLASS 6 QUERY FOR GRADE COUNT */

              $c6GradeCount = pg_query($db,"SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 9
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 9
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* CLASS 6 TABLE FOR GRADE COUNT */

              while ($rowc6grade = pg_fetch_assoc($c6GradeCount)) {

                   echo "<td class='cell100 column31'>" . $rowc6grade['class_name'] . "</td>";
                   echo "<td class='cell100 column32'>" . $rowc6grade['exam_type'] . "</td>";
                   echo "<td class='cell100 column33'>" . $rowc6grade['a'] . "</td>";
                   echo "<td class='cell100 column34'>" . $rowc6grade['a_m'] . "</td>";
                   echo "<td class='cell100 column35'>" . $rowc6grade['b_p'] . "</td>";
                   echo "<td class='cell100 column36'>" . $rowc6grade['b'] . "</td>";
                   echo "<td class='cell100 column37'>" . $rowc6grade['b_m'] . "</td>";
                   echo "<td class='cell100 column38'>" . $rowc6grade['c_p'] . "</td>";
                   echo "<td class='cell100 column39'>" . $rowc6grade['c'] . "</td>";
                   echo "<td class='cell100 column40'>" . $rowc6grade['c_m'] . "</td>";
                   echo "<td class='cell100 column41'>" . $rowc6grade['d_p'] . "</td>";
                   echo "<td class='cell100 column42'>" . $rowc6grade['d'] . "</td>";
                   echo "<td class='cell100 column43'>" . $rowc6grade['d_m'] . "</td>";
                   echo "<td class='cell100 column44'>" . $rowc6grade['e'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body' style='height:13px;'>";
                echo "<th class='cell100 column31'>*</th>";
                echo "<th class='cell100 column32'>*</th>";
                echo "<th class='cell100 column33'>*</th>";
                echo "<th class='cell100 column34'>*</th>";
                echo "<th class='cell100 column35'>*</th>";
                echo "<th class='cell100 column36'>*</th>";
                echo "<th class='cell100 column37'>*</th>";
                echo "<th class='cell100 column38'>*</th>";
                echo "<th class='cell100 column39'>*</th>";
                echo "<th class='cell100 column40'>*</th>";
                echo "<th class='cell100 column41'>*</th>";
                echo "<th class='cell100 column42'>*</th>";
                echo "<th class='cell100 column43'>*</th>";
                echo "<th class='cell100 column44'>*</th>";
              echo "</tr>";

              /* CLASS 5 QUERY FOR GRADE COUNT */

              $c5GradeCount = pg_query($db,"SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 8
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 8
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* CLASS 6 TABLE FOR GRADE COUNT */

              while ($rowc5grade = pg_fetch_assoc($c5GradeCount)) {

                   echo "<td class='cell100 column31'>" . $rowc5grade['class_name'] . "</td>";
                   echo "<td class='cell100 column32'>" . $rowc5grade['exam_type'] . "</td>";
                   echo "<td class='cell100 column33'>" . $rowc5grade['a'] . "</td>";
                   echo "<td class='cell100 column34'>" . $rowc5grade['a_m'] . "</td>";
                   echo "<td class='cell100 column35'>" . $rowc5grade['b_p'] . "</td>";
                   echo "<td class='cell100 column36'>" . $rowc5grade['b'] . "</td>";
                   echo "<td class='cell100 column37'>" . $rowc5grade['b_m'] . "</td>";
                   echo "<td class='cell100 column38'>" . $rowc5grade['c_p'] . "</td>";
                   echo "<td class='cell100 column39'>" . $rowc5grade['c'] . "</td>";
                   echo "<td class='cell100 column40'>" . $rowc5grade['c_m'] . "</td>";
                   echo "<td class='cell100 column41'>" . $rowc5grade['d_p'] . "</td>";
                   echo "<td class='cell100 column42'>" . $rowc5grade['d'] . "</td>";
                   echo "<td class='cell100 column43'>" . $rowc5grade['d_m'] . "</td>";
                   echo "<td class='cell100 column44'>" . $rowc5grade['e'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body' style='height:13px;'>";
                echo "<th class='cell100 column31'>*</th>";
                echo "<th class='cell100 column32'>*</th>";
                echo "<th class='cell100 column33'>*</th>";
                echo "<th class='cell100 column34'>*</th>";
                echo "<th class='cell100 column35'>*</th>";
                echo "<th class='cell100 column36'>*</th>";
                echo "<th class='cell100 column37'>*</th>";
                echo "<th class='cell100 column38'>*</th>";
                echo "<th class='cell100 column39'>*</th>";
                echo "<th class='cell100 column40'>*</th>";
                echo "<th class='cell100 column41'>*</th>";
                echo "<th class='cell100 column42'>*</th>";
                echo "<th class='cell100 column43'>*</th>";
                echo "<th class='cell100 column44'>*</th>";
              echo "</tr>";

              /* CLASS 4 QUERY FOR GRADE COUNT */

              $c4GradeCount = pg_query($db,"SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 7
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 7
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* CLASS 4 TABLE FOR GRADE COUNT */

              while ($rowc4grade = pg_fetch_assoc($c4GradeCount)) {

                   echo "<td class='cell100 column31'>" . $rowc4grade['class_name'] . "</td>";
                   echo "<td class='cell100 column32'>" . $rowc4grade['exam_type'] . "</td>";
                   echo "<td class='cell100 column33'>" . $rowc4grade['a'] . "</td>";
                   echo "<td class='cell100 column34'>" . $rowc4grade['a_m'] . "</td>";
                   echo "<td class='cell100 column35'>" . $rowc4grade['b_p'] . "</td>";
                   echo "<td class='cell100 column36'>" . $rowc4grade['b'] . "</td>";
                   echo "<td class='cell100 column37'>" . $rowc4grade['b_m'] . "</td>";
                   echo "<td class='cell100 column38'>" . $rowc4grade['c_p'] . "</td>";
                   echo "<td class='cell100 column39'>" . $rowc4grade['c'] . "</td>";
                   echo "<td class='cell100 column40'>" . $rowc4grade['c_m'] . "</td>";
                   echo "<td class='cell100 column41'>" . $rowc4grade['d_p'] . "</td>";
                   echo "<td class='cell100 column42'>" . $rowc4grade['d'] . "</td>";
                   echo "<td class='cell100 column43'>" . $rowc4grade['d_m'] . "</td>";
                   echo "<td class='cell100 column44'>" . $rowc4grade['e'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body' style='height:13px;'>";
                echo "<th class='cell100 column31'>*</th>";
                echo "<th class='cell100 column32'>*</th>";
                echo "<th class='cell100 column33'>*</th>";
                echo "<th class='cell100 column34'>*</th>";
                echo "<th class='cell100 column35'>*</th>";
                echo "<th class='cell100 column36'>*</th>";
                echo "<th class='cell100 column37'>*</th>";
                echo "<th class='cell100 column38'>*</th>";
                echo "<th class='cell100 column39'>*</th>";
                echo "<th class='cell100 column40'>*</th>";
                echo "<th class='cell100 column41'>*</th>";
                echo "<th class='cell100 column42'>*</th>";
                echo "<th class='cell100 column43'>*</th>";
                echo "<th class='cell100 column44'>*</th>";
              echo "</tr>";

              /* CLASS 3 QUERY FOR GRADE COUNT */

              $c3GradeCount = pg_query($db,"SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 6
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 6
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* CLASS 3 TABLE FOR GRADE COUNT */

              while ($rowc3grade = pg_fetch_assoc($c3GradeCount)) {

                   echo "<td class='cell100 column31'>" . $rowc3grade['class_name'] . "</td>";
                   echo "<td class='cell100 column32'>" . $rowc3grade['exam_type'] . "</td>";
                   echo "<td class='cell100 column33'>" . $rowc3grade['a'] . "</td>";
                   echo "<td class='cell100 column34'>" . $rowc3grade['a_m'] . "</td>";
                   echo "<td class='cell100 column35'>" . $rowc3grade['b_p'] . "</td>";
                   echo "<td class='cell100 column36'>" . $rowc3grade['b'] . "</td>";
                   echo "<td class='cell100 column37'>" . $rowc3grade['b_m'] . "</td>";
                   echo "<td class='cell100 column38'>" . $rowc3grade['c_p'] . "</td>";
                   echo "<td class='cell100 column39'>" . $rowc3grade['c'] . "</td>";
                   echo "<td class='cell100 column40'>" . $rowc3grade['c_m'] . "</td>";
                   echo "<td class='cell100 column41'>" . $rowc3grade['d_p'] . "</td>";
                   echo "<td class='cell100 column42'>" . $rowc3grade['d'] . "</td>";
                   echo "<td class='cell100 column43'>" . $rowc3grade['d_m'] . "</td>";
                   echo "<td class='cell100 column44'>" . $rowc3grade['e'] . "</td>";
               echo "</tr>";
              }

              echo "<tr class='row100 body' style='height:13px;'>";
                echo "<th class='cell100 column31'>*</th>";
                echo "<th class='cell100 column32'>*</th>";
                echo "<th class='cell100 column33'>*</th>";
                echo "<th class='cell100 column34'>*</th>";
                echo "<th class='cell100 column35'>*</th>";
                echo "<th class='cell100 column36'>*</th>";
                echo "<th class='cell100 column37'>*</th>";
                echo "<th class='cell100 column38'>*</th>";
                echo "<th class='cell100 column39'>*</th>";
                echo "<th class='cell100 column40'>*</th>";
                echo "<th class='cell100 column41'>*</th>";
                echo "<th class='cell100 column42'>*</th>";
                echo "<th class='cell100 column43'>*</th>";
                echo "<th class='cell100 column44'>*</th>";
              echo "</tr>";

              /* SCHOOL GRADE COUNT TOTALS */

              $gradeTotals = pg_query($db,"SELECT 1 AS totals, sum(a) AS a, sum(a_m) AS a_m, sum(b_p) as b_p, sum(b) as b, sum(b_m) as b_m, sum(c_p) as c_p, sum(c) as c, sum(c_m) as c_m, sum(d_p) as d_p, sum(d) as d, sum(d_m) as d_m, sum(e) as e FROM (
					    SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 9
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 9
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type
                                            UNION
                                            SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 8
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 8
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type
                                            UNION
                                            SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 7
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 7
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type
                                            UNION
                                            SELECT t2.class_name, t1.*
                                            FROM
                                            (

                                            SELECT *
                                            FROM   crosstab('SELECT exam_type, grade, grd_count FROM (
                                            SELECT class_name, exam_type, grade, count(grade) as grd_count FROM (
                                            SELECT class_name, exam_type, (select grade from app.grading where round((total_mark/total_grade_weight)*100) between min_mark and max_mark) as grade FROM (
                                            SELECT student_name, class_name, exam_type, sum(total_mark) as total_mark, sum(total_grade_weight) as total_grade_weight
                                            FROM(
                                            SELECT  student_name, class_name, exam_type, subject_name,
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
                                            WHERE class_cats.entity_id = 6
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                            ORDER BY 1)c
                                            ORDER BY 1','SELECT grade FROM app.grading ORDER BY grade_id ASC') AS ct (exam_type text, a bigint, a_m bigint, b_p bigint, b bigint, b_m bigint, c_p bigint, c bigint, c_m bigint, d_p bigint, d bigint, d_m bigint, e bigint)

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
                                            WHERE class_cats.entity_id = 6
                                            AND term_id = (SELECT t.term_id FROM app.terms t WHERE t.term_number=1)
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
                                                ON t1.exam_type = t2.exam_type
                                            )tot");
              echo "<tr class='row100 body'>";
                  // echo "<th class='cell100 column1' rowspan='1'>6</th>";

                  /* GRADE TOTALS TABLE */

              while ($gTotals = pg_fetch_assoc($gradeTotals)) {

                   echo "<td class='cell100 column31'>OVERALL</td>";
                   echo "<td class='cell100 column32'>*</td>";
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

    <!-- Footer -->
    <footer class="py-2 bg-dark" style="position: fixed !important; bottom: 0 !important; width: 100% !important;">
      <div class="container">
        <p class="m-0 text-center text-white"><small>&copy; Eduweb <script type="text/javascript">document.write((new Date()).getFullYear())</script></small></p>
      </div>
      <!-- /.container -->
    </footer>
</body>
</html>
