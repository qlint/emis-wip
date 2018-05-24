<?php
header('Access-Control-Allow-Origin: *');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
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
  <title>Streams Analysis</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Class Analysis</span></a>
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
    <h4 style="text-align:center;margin-top:85px;">This page overviews the student's performance in the entire stream in a given term.</h4>
    <div style="border: 3px dashed #397C49; width:20%; margin-left:auto;margin-right:auto;text-align:center;padding-bottom:7px;padding-top:7px;">
      <h5 style="text-align:center;">Select A Term &amp; Class.</h5>
      <form style="margin-left:auto; margin-right:auto; text-align:center;" action="#" method="post">
        <select name="Term">
          <option value='1'>Term 1</option>
          <option value='2'>Term 2</option>
          <option value='3'>Term 3</option>
        </select>
        <select name="Classes">
          <option value='1'>Form 1SK</option>
          <option value='2'>Form 1S</option>
          <option value='3'>Form 2SK</option>
          <option value='4'>Form 2S</option>
          <option value='5'>Form 2E</option>
          <option value='7'>Form 3SK</option>
          <option value='8'>Form 3S</option>
          <option value='9'>Form 3E</option>
          <option value='11'>Form 4SK</option>
          <option value='12'>Form 4S</option>
          <option value='13'>Form 4E</option>
          <option value='14'>Form 4B1</option>
          <option value='15'>Form 4B2</option>
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
    $no_selection = 1;
    $term = (isset($_POST['submit']) ? $selected_val_1 : $no_selection);
    $class= (isset($_POST['submit']) ? $selected_val_2 : $no_selection);
    $class_name = "";
    if($class == 1){$class_name = "Form 1SK";}elseif($class == 2){$class_name = "Form 1S";}
    elseif($class == 3){$class_name = "Form 2SK";}elseif($class == 4){$class_name = "Form 2S";}elseif($class == 5){$class_name = "Form 2E";}
    elseif($class == 7){$class_name = "Form 3SK";}elseif($class == 8){$class_name = "Form 3S";}elseif($class == 9){$class_name = "Form 3E";}
    elseif($class == 11){$class_name = "Form 4SK";}elseif($class == 12){$class_name = "Form 4S";}elseif($class == 13){$class_name = "Form 4E";}elseif($class == 14){$class_name = "Form 4B1";}elseif($class == 15){$class_name = "Form 4B2";}
    ?>
    <div class="container-table100">
  	   <div class="wrap-table100">
         <h4 id="expTitle"><?php echo $class_name . " (Term " . $term . ")"; ?></h4><hr>
<?php
// $db = pg_connect("host=localhost port=5432 dbname=eduweb_highschool_newlightgirls user=postgres password=postgres");
$getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
$db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");


/* -------------------------CLASS QUERY ------------------------- */
$table1 = pg_query($db,"SELECT t1.*, t3.marks, t2.rank as position FROM
                          (
                          /* CREATE EXTENSION tablefunc; */

                          SELECT *
                          FROM   crosstab('SELECT student_name, subject_name, marks FROM (
                          			SELECT student_name, subject_name, sum(mark) as marks, sum(grade_weight) as grade_weight FROM(
                          				SELECT student_name, class_id, subject_name, parent_subject_name, exam_type_id, exam_type, student_id, sort_order,
                          				(case
                          					WHEN count = 1 THEN
                          						mark
                          					WHEN count = 2 THEN
                          						sum(mark)
                          					WHEN count = 3 THEN
                          						(CASE
                          							WHEN is_last_exam is true THEN
                          								round(mark * 0.7)

                          							WHEN is_last_exam is false THEN
                          								round(mark * 0.3)
                          						END)
                          				end) as mark,
                          				(case
                          					WHEN count = 1 THEN
                          						grade_weight
                          					WHEN count = 2 THEN
                          						sum(grade_weight)
                          					WHEN count = 3 THEN
                          						(CASE
                          							WHEN is_last_exam is true THEN
                          								round(grade_weight * 0.7)

                          							WHEN is_last_exam is false THEN
                          								round(grade_weight * 0.3)
                          						END)
                          				end) as grade_weight FROM (
                          					SELECT student_name, class_id, subject_name, parent_subject_name, exam_type_id, (SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = $class AND term_id = $term) as count, exam_type, is_last_exam, student_id, mark, grade_weight, sort_order FROM (
                          						SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,classes.class_id,subject_name,
                          							  coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name,
                          							  class_subject_exams.exam_type_id,exam_type,is_last_exam,exam_marks.student_id,mark,grade_weight,subjects.sort_order
                          						FROM app.exam_marks
                          						INNER JOIN app.class_subject_exams
                          						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                          						INNER JOIN app.class_subjects
                          						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
                          						INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                          						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                          						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                          						INNER JOIN app.students ON exam_marks.student_id = students.student_id
                          						WHERE class_subjects.class_id = $class
                          						AND term_id = $term
                          						AND subjects.use_for_grading is true
                          						AND students.active is true

                          						WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                          						)one
                          						)two
                          						GROUP BY student_name, class_id, subject_name, parent_subject_name, exam_type_id, exam_type, student_id, sort_order, count, mark, grade_weight, is_last_exam
                          						ORDER BY student_name ASC, sort_order ASC, exam_type_id ASC
                          						)three
                          						GROUP BY student_name, subject_name
                          						ORDER BY student_name ASC
                          						)four
                          ORDER BY 1','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = $class) order by sort_order') AS ct (student_name text, english numeric, kiswahili numeric, mathematics numeric, biology numeric, physics numeric, chemistry numeric, history numeric, geography numeric, cre numeric, computer numeric, french numeric, bst numeric)

                          ) AS t1
                          FULL OUTER JOIN
                          (
                          	SELECT student_name, rank FROM (
                          		SELECT student_id, student_name, total_mark, dense_rank() over w as rank FROM (
                          			SELECT exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                          				coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark
                          			FROM app.exam_marks
                          			INNER JOIN app.class_subject_exams
                          			INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                          			INNER JOIN app.class_subjects
                          			INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
                          						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
                          						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                          			INNER JOIN app.students ON exam_marks.student_id = students.student_id
                          			WHERE class_subjects.class_id = ". $class ." AND term_id = ". $term ." AND students.active is true
                          			GROUP BY exam_marks.student_id, students.first_name, students.middle_name, students.last_name
                          		) a
                          		WINDOW w AS (ORDER BY total_mark desc)
                          	) q
                          ) AS t2
                          ON t1.student_name = t2.student_name
                          FULL OUTER JOIN
                          (
                          SELECT student_name, sum(marks) as marks, sum(grade_weight) as total FROM (
                          			SELECT student_name, subject_name, sum(mark) as marks, sum(grade_weight) as grade_weight FROM(
                          				SELECT student_name, class_id, subject_name, parent_subject_name, exam_type_id, exam_type, student_id, sort_order,
                          				(case
                          					WHEN count = 1 THEN
                          						mark
                          					WHEN count = 2 THEN
                          						sum(mark)
                          					WHEN count = 3 THEN
                          						(CASE
                          							WHEN is_last_exam is true THEN
                          								round(mark * 0.7)

                          							WHEN is_last_exam is false THEN
                          								round(mark * 0.3)
                          						END)
                          				end) as mark,
                          				(case
                          					WHEN count = 1 THEN
                          						grade_weight
                          					WHEN count = 2 THEN
                          						sum(grade_weight)
                          					WHEN count = 3 THEN
                          						(CASE
                          							WHEN is_last_exam is true THEN
                          								round(grade_weight * 0.7)

                          							WHEN is_last_exam is false THEN
                          								round(grade_weight * 0.3)
                          						END)
                          				end) as grade_weight FROM (
                          					SELECT student_name, class_id, subject_name, parent_subject_name, exam_type_id, (SELECT count(distinct cse.exam_type_id) FROM app.class_subject_exams cse INNER JOIN app.class_subjects cs ON cse.class_subject_id = cs.class_subject_id INNER JOIN app.exam_marks em ON cse.class_sub_exam_id = em.class_sub_exam_id WHERE cs.class_id = ". $class ." AND term_id = ". $term .") as count, exam_type, is_last_exam, student_id, mark, grade_weight, sort_order FROM (
                          						SELECT first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,classes.class_id,subject_name,
                          							  coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'') as parent_subject_name,
                          							  class_subject_exams.exam_type_id,exam_type,is_last_exam,exam_marks.student_id,mark,grade_weight,subjects.sort_order
                          						FROM app.exam_marks
                          						INNER JOIN app.class_subject_exams
                          						INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
                          						INNER JOIN app.class_subjects
                          						INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
                          						INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                          						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
                          						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
                          						INNER JOIN app.students ON exam_marks.student_id = students.student_id
                          						WHERE class_subjects.class_id = ". $class ."
                          						AND term_id = ". $term ."
                          						AND subjects.use_for_grading is true
                          						AND students.active is true AND exam_marks.mark IS NOT null

                          						WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
                          						)one
                          						)two
                          						GROUP BY student_name, class_id, subject_name, parent_subject_name, exam_type_id, exam_type, student_id, sort_order, count, mark, grade_weight, is_last_exam
                          						ORDER BY student_name ASC, sort_order ASC, exam_type_id ASC
                          						)three
                          						GROUP BY student_name, subject_name
                          						ORDER BY student_name ASC
                          						)four
                          						GROUP BY student_name ORDER BY student_name ASC
                          ) AS t3
                          ON t2.student_name = t3.student_name
                          ");

                        /* -------------------------CLASS TABLE ------------------------- */
                        echo "<div class='table100 ver1 m-b-110'>";
                        echo "<table id='table1'>";
                          echo "<div id='t1' class='table100-head'>";
                            // echo "<table id='table1'>";
                              echo "<thead>";
                               echo "<tr class='row100 head'>";
                                 echo "<th class='cell100 column1'>STUDENT</th>";
                                 echo "<th class='cell100 column2'>Eng.</th>";
                                 echo "<th class='cell100 column3'>Kis.</th>";
                                 echo "<th class='cell100 column4'>Mth.</th>";
                                 echo "<th class='cell100 column5'>Bio.</th>";
                                 echo "<th class='cell100 column6'>Phy.</th>";
                                 echo "<th class='cell100 column7'>Chm.</th>";
                                 echo "<th class='cell100 column8'>Hst.</th>";
                                 echo "<th class='cell100 column9'>Geo.</th>";
                                 echo "<th class='cell100 column10'>CRE</th>";
                                 echo "<th class='cell100 column11'>Comp.</th>";
                                 echo "<th class='cell100 column12'>Fnch.</th>";
                                 echo "<th class='cell100 column13'>B/S.</th>";
                                 echo "<th class='cell100 column14'>TOT.</th>";
                                 echo "<th class='cell100 column15'>POS.</th>";
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
                                     echo "<td class='cell100 column2'>" . $row['english'] . "</td>";
                                     echo "<td class='cell100 column3'>" . $row['kiswahili'] . "</td>";
                                     echo "<td class='cell100 column4'>" . $row['mathematics'] . "</td>";
                                     echo "<td class='cell100 column5'>" . $row['biology'] . "</td>";
                                     echo "<td class='cell100 column6'>" . $row['physics'] . "</td>";
                                     echo "<td class='cell100 column7'>" . $row['chemistry'] . "</td>";
                                     echo "<td class='cell100 column8'>" . $row['history'] . "</td>";
                                     echo "<td class='cell100 column9'>" . $row['geography'] . "</td>";
                                     echo "<td class='cell100 column10'>" . $row['cre'] . "</td>";
                                     echo "<td class='cell100 column11'>" . $row['computer'] . "</td>";
                                     echo "<td class='cell100 column12'>" . $row['french'] . "</td>";
                                     echo "<td class='cell100 column13'>" . $row['bst'] . "</td>";
                                     echo "<td class='cell100 column14'>" . $row['marks'] . "</td>";
                                     echo "<td class='cell100 column15'>" . $row['position'] . "</td>";
                                 echo "</tr>";
                                }
                              echo "</tbody>";
                            // echo "</table>";
                          echo "</div>";
                          echo "</table>";
                         echo "</div>";
                         /* -------------------------END OF CLASS TABLE ------------------------- */

// echo "<h4>STREAM: Form 3 (Term $term)</h4><hr>";
/* -------------------------ANOTHER CLASS TABLE ------------------------- */
 // $table2 = pg_query($db,"");

/* -------------------------ANOTHER CLASS TABLE ------------------------- */
 // echo "<div class='table100 ver1 m-b-110'>";
 //    echo "<table id='table2'>";
 //      echo "<div id='t2' class='table100-head'>";
 //         echo "<thead>";
 //          echo "<tr class='row100 head'>";
 //            echo "<th class='cell100 column1'>STUDENT NAME</th>";
 //            echo "<th class='cell100 column2'>Eng.</th>";
 //            echo "<th class='cell100 column3'>Kis.</th>";
 //            echo "<th class='cell100 column4'>Mth.</th>";
 //            echo "<th class='cell100 column5'>Bio.</th>";
 //            echo "<th class='cell100 column6'>Phy.</th>";
 //            echo "<th class='cell100 column7'>Chm.</th>";
 //            echo "<th class='cell100 column8'>Hst.</th>";
 //            echo "<th class='cell100 column9'>Geo.</th>";
 //            echo "<th class='cell100 column10'>CRE</th>";
 //            echo "<th class='cell100 column11'>Comp.</th>";
 //            echo "<th class='cell100 column12'>B/S.</th>";
 //            echo "<th class='cell100 column13'>Fnch.</th>";
 //            echo "<th class='cell100 column14'>TOT.</th>";
 //            echo "<th class='cell100 column15'>%</th>";
 //            echo "<th class='cell100 column16'>GRD.</th>";
 //            echo "<th class='cell100 column17'>POS.</th>";
 //          echo "</tr>";
 //         echo "</thead>";
 //     echo "</div>";
 //     echo "<div class='table100-body js-pscroll'>";
 //       echo "<tbody>";
 //         while ($row2 = pg_fetch_assoc($table2)) {
 //           // $text1 = '';
 //           echo "<td class='cell100 column1'>" . $row2['student_name'] . "</td>";
 //           echo "<td class='cell100 column2'>" . $row2['english'] . "</td>";
 //           echo "<td class='cell100 column3'>" . $row2['kiswahili'] . "</td>";
 //           echo "<td class='cell100 column4'>" . $row2['mathematics'] . "</td>";
 //           echo "<td class='cell100 column5'>" . $row2['biology'] . "</td>";
 //           echo "<td class='cell100 column6'>" . $row2['physics'] . "</td>";
 //           echo "<td class='cell100 column7'>" . $row2['chemistry'] . "</td>";
 //           echo "<td class='cell100 column8'>" . $row2['history'] . "</td>";
 //           echo "<td class='cell100 column9'>" . $row2['geography'] . "</td>";
 //           echo "<td class='cell100 column10'>" . $row2['cre'] . "</td>";
 //           echo "<td class='cell100 column11'>" . $row2['computer'] . "</td>";
 //           echo "<td class='cell100 column12'>" . $row2['bs_studies'] . "</td>";
 //           echo "<td class='cell100 column13'>" . $row2['french'] . "</td>";
 //           echo "<td class='cell100 column14'>" . $row2['tot'] . "</td>";
 //           echo "<td class='cell100 column15'>" . $row2['percentage'] . "</td>";
 //           echo "<td class='cell100 column16'>" . $row2['grade'] . "</td>";
 //           echo "<td class='cell100 column17'>" . $row2['pos'] . "</td>";
 //          echo "</tr>";
 //         }
 //       echo "</tbody>";
 //     echo "</div>";
 //   echo "</table>";
 //   echo "</div>";
 /* -------------------------END OF ANOTHER CLASS TABLE ------------------------- */

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
      $('#table1').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: docName + ' Class Analysis'
            },
            {
              extend: 'csvHtml5',
              title: docName + ' Class Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: docName + ' Class Analysis'
            }
          ],
          "order": [[ 14, "asc" ]]
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
                title: 'Class Analysis'
            },
            {
              extend: 'csvHtml5',
              title: 'Class Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: 'Class Analysis'
            }
          ],
          "order": [[ 14, "asc" ]]
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
                title: 'Class Analysis'
            },
            {
              extend: 'csvHtml5',
              title: 'Class Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: 'Class Analysis'
            }
          ],
          "order": [[ 14, "asc" ]]
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
                title: 'Class Analysis'
            },
            {
              extend: 'csvHtml5',
              title: 'Class Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: 'Class Analysis'
            }
          ],
          "order": [[ 14, "asc" ]]
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
