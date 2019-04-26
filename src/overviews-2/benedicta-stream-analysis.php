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
  <!-- End of Header Nav -->
  <div class="limiter">
    <h4 style="text-align:center;margin-top:25px;">This page overviews the student's performance in the entire stream in a given term.</h4>
    <div style="border: 3px dashed #397C49; width:20%; margin-left:auto;margin-right:auto;text-align:center;padding-bottom:7px;padding-top:7px;">
      <h5 style="text-align:center;">Select A Term &amp; Class.</h5>
      <form style="margin-left:auto; margin-right:auto; text-align:center;" action="#" method="post">
        <select name="Term">
          <option value='2'>Term 3 2018</option>
          <!-- <option value='3'>Term 1 2019</option>
          <option value='4'>Term 2 2019</option> -->
        </select>
        <select name="Classes">
          <option value='11'>CLASS 8</option>
          <option value='10'>CLASS 7</option>
          <option value='9'>CLASS 6</option>
          <option value='8'>CLASS 5</option>
          <option value='7'>CLASS 4</option>
        </select>
        <div><input style="margin-left:auto; margin-right:auto; text-align:center;" type="submit" name="submit" value="Get Results For This Term" /></div>
      </form>
    </div>
    <?php
    if(isset($_POST['submit'])){
    $selected_val1 = $_POST['Term'];
    $selected_val2 = $_POST['Classes'];
    $selected_val_1 = trim($selected_val1,"'");
    $selected_val_2 = trim($selected_val2,"'");
    // $no_selection = 0;  // Storing Selected Value In Variable
    // echo "Term " .$selected_val . " stream results";  // Displaying Selected Value
    }
    $no_selection = 2;
    $no_selection2 = 11;
    $term = (isset($_POST['submit']) ? $selected_val_1 : $no_selection);
    $class= (isset($_POST['submit']) ? $selected_val_2 : $no_selection2);
    $class_name = "";
    if($class == 11){$class_name = "Class 8";}elseif($class == 10){$class_name = "Class 7";}
    elseif($class == 9){$class_name = "Class 6";}elseif($class == 8){$class_name = "Class 5";}
    elseif($class == 7){$class_name = "Class 4";}
    ?>
    <div class="container-table100">
      <div>Show/ Hide Columns:
          <a class="toggle-vis" data-column="0" href="">Name</a> -
          <a class="toggle-vis" data-column="1" href="">Maths</a> -
          <a class="toggle-vis" data-column="2" href="">English</a> -
          <a class="toggle-vis" data-column="3" href="">Kiswahili</a> -
          <a class="toggle-vis" data-column="4" href="">Science</a> -
          <a class="toggle-vis" data-column="5" href="">S/Stud</a> -
          <a class="toggle-vis" data-column="6" href="">Total</a> -
          <a class="toggle-vis" data-column="7" href="">Prev</a> -
          <a class="toggle-vis" data-column="8" href="">Diff</a> -
          <a class="toggle-vis" data-column="9" href="">%</a> -
          <a class="toggle-vis" data-column="10" href="">Grade</a> -
          <a class="toggle-vis" data-column="11" href="">Pos</a>
      </div>
  	   <div class="wrap-table100">
         <h4 id="expTitle">STREAM: <?php echo $class_name ?></h4><hr>
<?php
// $db = pg_connect("host=localhost port=5432 dbname=eduweb_dev2 user=postgres password=postgres");
$getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
$db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");


/* -------------------------CLASS (x) QUERY ------------------------- */
$table3 = pg_query($db,"SELECT student_name, mathematics, english, eng_lang, eng_com, kiswahili, lugha, insha, science, ss_cre, ss, cre, tot, tot2, (tot - tot2) as diff, round((tot::float/500)*100) as percentage, (select grade from app.grading where round((tot::float/500)*100) between min_mark and max_mark) as grade, pos FROM (
              SELECT t1.*, t2.avg as tot, t2.position as pos, t3.avg as tot2
            FROM
            (
            /* CREATE EXTENSION tablefunc; */

            SELECT *
            FROM   crosstab('SELECT student_name, subject_name, marks FROM (
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
            WHERE class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $class) ORDER BY exam_type_id DESC LIMIT (SELECT COUNT(exam_type_id) FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $class)))
            AND class_cats.entity_id = $class
            AND term_id = $term
            AND subjects.parent_subject_id is null
            AND subjects.use_for_grading is true
            AND mark IS NOT NULL
            GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
            ) q GROUP BY student_id, student_name, class_name, exam_type, subject_name, total_mark, total_grade_weight, sort_order
            ORDER BY sort_order
            )v GROUP BY v.student_id, v.student_name, v.class_name, v.exam_type, v.subject_name, v.total_mark, v.total_grade_weight, v.percentage, v.grade, v.sort_order, v.t_mark2, v.tg_weight2
            ORDER BY student_name ASC, sort_order ASC
            )a
            ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = $class LIMIT 1) AND use_for_grading IS true order by sort_order') AS ct (student_name text, mathematics numeric, english numeric, eng_lang numeric, eng_com numeric, kiswahili numeric, lugha numeric, insha numeric, science numeric, ss_cre numeric, ss numeric, cre numeric)

            ) AS t1
                FULL OUTER JOIN
                (
            	SELECT * FROM (
            		SELECT avg, student_id, student_name, class_name, rank() over(order by avg desc) AS position,
            			(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = ". $class ." AND students.active is true) AS position_out_of
            		FROM (
            			SELECT student_id, student_name, class_name, sum(avg) as avg FROM (
            				SELECT subject_name, round(avg(total_mark)) AS avg, student_id, student_name, class_name
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
            						WHERE class_subject_exams.exam_type_id IN (SELECT exam_type_id FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = ". $class .") ORDER BY exam_type_id DESC LIMIT (SELECT COUNT(exam_type_id) FROM app.exam_types WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = ". $class .")) - (SELECT COUNT(class_id) FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = ". $class .")))
            						AND class_cats.entity_id = ". $class ."
            						AND term_id = ". $term ."
            						AND subjects.parent_subject_id is null
            						AND subjects.use_for_grading is true
            						AND students.student_id = exam_marks.student_id
            						AND mark IS NOT NULL
            						GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
            							use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
            					) q ORDER BY student_name ASC, sort_order
            				) AS foo GROUP BY subject_name, student_id,student_name, class_name ORDER BY student_name ASC
            			) as foo4 GROUP BY student_id, student_name, class_name ORDER BY student_name ASC
            		) AS FOO2
            	) AS foo3
                ) AS t2
                ON t1.student_name = t2.student_name
                FULL OUTER JOIN
                (
            	SELECT * FROM (
            		SELECT avg, student_id AS student_id2, student_name AS student_name2, class_name, rank() over(order by avg desc) AS position,
            			(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = ". $class ." AND students.active is true) AS position_out_of
            		FROM (
            			SELECT student_id, student_name, class_name, sum(avg) as avg FROM (
            				SELECT round(avg(total_mark)) AS avg, student_id, student_name, class_name, subject_name
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
            						WHERE class_subject_exams.exam_type_id IN (SELECT DISTINCT cse.exam_type_id FROM app.exam_types et INNER JOIN app.class_subject_exams cse ON et.exam_type_id = cse.exam_type_id INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.classes c ON cs.class_id = c.class_id WHERE cs.class_id IN (SELECT class_id FROM app.classes WHERE class_cat_id IN (SELECT class_cat_id FROM app.class_cats WHERE entity_id = ". $class .")) ORDER BY cse.exam_type_id DESC OFFSET (SELECT COUNT(class_cat_id) FROM app.class_cats WHERE entity_id = ". $class .") ROW FETCH FIRST (2) ROW ONLY)
            						AND class_cats.entity_id = ". $class ."
            						AND term_id = ". $term ."
            						AND subjects.parent_subject_id is null
            						AND subjects.use_for_grading is true
            						AND students.student_id = exam_marks.student_id
            						AND mark IS NOT NULL
            						GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order,
            							use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
            					) q ORDER BY sort_order
            				) AS foo GROUP BY student_id,student_name, class_name, subject_name ORDER BY student_name ASC
            			) AS foo4 GROUP BY student_id, student_name, class_name ORDER BY student_name ASC
            		) AS FOO2
            	) AS foo3
                ) AS t3
                ON t2.student_id = t3.student_id2
                order by t2.position ASC
                )foo4 order by pos ASC");
/* -------------------------CLASS (x) TABLE ------------------------- */
echo "<div class='table100 ver1 m-b-110'>";
echo "<table id='table3'  class='display'>";
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
echo "<th class='cell100 column14'>TOTAL.</th>";
echo "<th class='cell100 column18'>PREV Exm.</th>";
echo "<th class='cell100 column19'>DIFF.</th>";
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
while ($row3 = pg_fetch_assoc($table3)) {
  echo "<tr class='row100 body'>";
     echo "<td class='cell100 column1'>" . $row3['student_name'] . "</td>";
     echo "<td class='cell100 column2'>" . $row3['mathematics'] . "</td>";
     echo "<td class='cell100 column3'>" . $row3['english'] . "</td>";
     echo "<td class='cell100 column4'>" . $row3['kiswahili'] . "</td>";
     echo "<td class='cell100 column5'>" . $row3['science'] . "</td>";
     echo "<td class='cell100 column6'>" . $row3['ss_cre'] . "</td>";
     echo "<td class='cell100 column14'>" . $row3['tot'] . "</td>";
     echo "<td class='cell100 column18'>" . $row3['tot2'] . "</td>";
     echo "<td class='cell100 column14'>" . $row3['diff'] . "</td>";
     echo "<td class='cell100 column15'>" . $row3['percentage'] . "</td>";
     echo "<td class='cell100 column16'>" . $row3['grade'] . "</td>";
     echo "<td class='cell100 column17'>" . $row3['pos'] . "</td>";
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
      var intendedName = document.getElementById('expTitle');
      var docName = intendedName.innerHTML;
      console.log(docName);
      $('#table1').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          "columnDefs": [
            {"className": "dt-center", "targets": "_all"}
          ],
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
          "order": [[ 11, "asc" ]]
      } );
      $('#table2').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          "columnDefs": [
            {"className": "dt-center", "targets": "_all"}
          ],
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: docName + ' Analysis'
            },
            {
              extend: 'csvHtml5',
              title: docName + ' Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: docName + ' Analysis'
            }
          ],
          "order": [[ 11, "asc" ]]
      } );

      var table = $('#table3').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          "columnDefs": [
            {"className": "dt-center", "targets": "_all"}
          ],
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: docName + ' Analysis'
            },
            {
              extend: 'csvHtml5',
              title: docName + ' Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: docName + ' Analysis'
            }
          ],
          "order": [[ 11, "asc" ]]
      } );
      $('a.toggle-vis').on( 'click', function (e) {
        e.preventDefault();

        // Get the column API object
        var column = table.column( $(this).attr('data-column') );

        // Toggle the visibility
        column.visible( ! column.visible() );
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
