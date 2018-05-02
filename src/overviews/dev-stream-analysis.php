<?php
header('Access-Control-Allow-Origin: *');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
	<link rel="icon" type="image/png" href="../components/overviewFiles/images/icons/favicon.ico"/>
	<!-- <link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/bootstrap/css/bootstrap.min.css"> -->
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/animate/animate.css">
	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/select2/select2.min.css">
	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/perfect-scrollbar/perfect-scrollbar.css">
	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/util.css">
	<link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/main.css">
  <link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/jquery.dataTables.min.css">
  <link rel="stylesheet" type="text/css" href="../components/overviewFiles/css/buttons.dataTables.min.css">
  <link href="template/scripts/jquerysctipttop.css" rel="stylesheet" type="text/css">
  <!-- <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"> -->
  <link rel="stylesheet" type="text/css" href="../components/overviewFiles/vendor/animate/animate.css">
  <!-- Custom styles for this template -->
  <link href="css/1-col-portfolio.css" rel="stylesheet">
  <title>Streams Analysis</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Stream Analysis</span></a>
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
  <h3 style="text-align:center;margin-top:15px;">This page overviews the student's current performance in the entire stream.</h3>
  <div class="limiter">
    <div class="container-table100">
  	   <div class="wrap-table100">
         <h4>STREAM: Class 8</h4><hr>
<?php
$db = pg_connect("host=localhost port=5432 dbname=eduweb_dev2 user=postgres password=postgres");
// $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
// $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");


/* -------------------------CLASS 8 QUERY ------------------------- */
$table1 = pg_query($db,"SELECT student_name, mathematics, english, eng_lang, eng_comp, kiswahili, lugha, kusoma, science, ss_cre, ss, cre, tot, round((tot::float/500)*100) as percentage, (select grade from app.grading where round((tot::float/500)*100) between min_mark and max_mark) as grade, pos FROM (
  SELECT t1.*, t2.avg as tot, t2.position as pos
FROM
(
/* CREATE EXTENSION tablefunc; */

SELECT *
FROM   crosstab('SELECT student_name, subject_name, sum(marks) as marks FROM (
SELECT student_id, student_name, class_name, exam_type, subject_name, total_mark as marks_bak, t_mark2 as marks, total_grade_weight as out_of_bak, tg_weight2 as out_of, percentage, grade, sort_order
FROM(
SELECT  student_id, student_name, class_name, exam_type, subject_name,
total_mark,coalesce(sum(total_mark),0) as t_mark2, total_grade_weight, coalesce(sum(total_grade_weight),0) as tg_weight2,
round(total_mark::float/total_grade_weight::float*100) as percentage,
(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
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
AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
AND subjects.parent_subject_id is null
AND subjects.use_for_grading is true
AND mark IS NOT NULL
GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
) q GROUP BY student_id, student_name, class_name, exam_type, subject_name, total_mark, total_grade_weight, sort_order
ORDER BY sort_order
)v GROUP BY v.student_id, v.student_name, v.class_name, v.exam_type, v.subject_name, v.total_mark, v.total_grade_weight, v.percentage, v.grade, v.sort_order, v.t_mark2, v.tg_weight2
ORDER BY student_name ASC, sort_order ASC
)a
GROUP BY student_name, subject_name
ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 11 LIMIT 1) order by sort_order') AS ct (student_name text, mathematics numeric, english numeric, eng_lang numeric, eng_comp numeric, kiswahili numeric, lugha numeric, kusoma numeric, science numeric, ss_cre numeric, ss numeric, cre numeric)

) AS t1
    FULL OUTER JOIN
    (
	SELECT * FROM (
		SELECT avg, student_id, student_name, class_name, rank() over(order by avg desc) AS position,
			(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = 11 AND students.active is true) AS position_out_of
		FROM (
			SELECT sum(total_mark) AS avg, student_id, student_name, class_name
			FROM (
				SELECT  subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
					(SELECT grade FROM app.grading WHERE (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) AS grade,
					sort_order, exam_type_id, student_id, student_name, class_name
				FROM (
					SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
						subjects.sort_order, class_subject_exams.exam_type_id
					FROM app.exam_marks
					INNER JOIN app.students ON exam_marks.student_id = students.student_id
					INNER JOIN app.class_subject_exams
					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
					INNER JOIN app.class_subjects
					INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
								 ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
								 ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
					WHERE class_cats.entity_id = 11
					AND term_id = (SELECT term_id FROM app.terms WHERE term_number = 2)
					AND subjects.parent_subject_id is null
					AND subjects.use_for_grading is true
					AND students.student_id = exam_marks.student_id
					AND mark IS NOT NULL
					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
						use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
				) q ORDER BY sort_order
			) AS foo GROUP BY student_id,student_name, class_name ORDER BY avg DESC
		) AS FOO2
	) AS foo3
    ) AS t2
    ON t1.student_name = t2.student_name
    order by position ASC
    )foo4 order by pos ASC");
/* -------------------------CLASS 8 TABLE ------------------------- */
echo "<div class='table100 ver1 m-b-110'>";
echo "<table id='table1'>";
  echo "<div id='t1' class='table100-head'>";
    // echo "<table id='table1'>";
      echo "<thead>";
       echo "<tr class='row100 head'>";
         echo "<th class='cell100 column1'>STUDENT NAME</th>";
         echo "<th class='cell100 column2'>MATHS</th>";
         echo "<th class='cell100 column3'>ENGLISH</th>";
         echo "<th class='cell100 column4'>KISWAHILI</th>";
         echo "<th class='cell100 column5'>SCIENCE</th>";
         echo "<th class='cell100 column6'>S/STUD.</th>";
         echo "<th class='cell100 column14'>TOT.</th>";
         echo "<th class='cell100 column15'>%</th>";
         echo "<th class='cell100 column16'>GRD.</th>";
         echo "<th class='cell100 column17'>POS.</th>";
       echo "</tr>";
      echo "</thead>";
    // echo "</table>";
  echo "</div>";
  echo "<div class='table100-body js-pscroll'>";
    // echo "<table id='table1-2'>";
      echo "<tbody>";
        while ($row = pg_fetch_assoc($table1)) {
          echo "<tr class='row100 body'>";
             echo "<td class='cell100 column1'>" . $row['student_name'] . "</td>";
             echo "<td class='cell100 column2'>" . $row['mathematics'] . "</td>";
             echo "<td class='cell100 column3'>" . $row['english'] . "</td>";
             echo "<td class='cell100 column4'>" . $row['kiswahili'] . "</td>";
             echo "<td class='cell100 column5'>" . $row['science'] . "</td>";
             echo "<td class='cell100 column6'>" . $row['ss_cre'] . "</td>";
             echo "<td class='cell100 column14'>" . $row['tot'] . "</td>";
             echo "<td class='cell100 column15'>" . $row['percentage'] . "</td>";
             echo "<td class='cell100 column16'>" . $row['grade'] . "</td>";
             echo "<td class='cell100 column17'>" . $row['pos'] . "</td>";
         echo "</tr>";
        }
      echo "</tbody>";
    // echo "</table>";
  echo "</div>";
  echo "</table>";
 echo "</div>";

?>
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
                title: 'Class-8-Stream-Analysis'
            },
            {
              extend: 'csvHtml5',
              title: 'Class-8-Stream-Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: 'Class-8-Stream-Analysis'
            }
          ],
          "order": [[ 9, "asc" ]]
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
                title: 'Form-3'
            },
            {
              extend: 'csvHtml5',
              title: 'Form-3'
          },
            {
                extend: 'pdfHtml5',
                title: 'Form-3'
            }
          ],
          "order": [[ 9, "asc" ]]
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
                title: 'Form-2'
            },
            {
              extend: 'csvHtml5',
              title: 'Form-2'
          },
            {
                extend: 'pdfHtml5',
                title: 'Form-2'
            }
          ],
          "order": [[ 9, "asc" ]]
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
                title: 'Form-1'
            },
            {
              extend: 'csvHtml5',
              title: 'Form-1'
          },
            {
                extend: 'pdfHtml5',
                title: 'Form-1'
            }
          ],
          "order": [[ 9, "asc" ]]
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
