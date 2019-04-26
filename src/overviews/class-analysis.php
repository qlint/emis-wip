<?php
    /* access control header */
    header('Access-Control-Allow-Origin: *');
    
    /*db conn */
    include("ajax/db.php");
    
    /*classes*/
    $classes = pg_query($db,"SELECT class_id, class_name FROM app.classes
                            INNER JOIN app.class_cats USING (class_cat_id)
                            WHERE entity_id > 6
                            ORDER BY classes.sort_order DESC;");
    /* Entity_id is greater than 6 as we don't want to rank class three and below. Class three is entity id 6 */
    
    /*terms*/
    $terms = pg_query($db,"SELECT term_id, term_name FROM app.terms ORDER BY term_id DESC;");
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
    <title><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?> Class Analysis</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript" src="ajax/js.js"></script>
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
    <h4 style="text-align:center;margin-top:85px;">This page overviews the student's performance in the class in a given term.</h4>
    <div style="border: 3px dashed #397C49; width:20%; margin-left:auto;margin-right:auto;text-align:center;padding-bottom:7px;padding-top:7px;">
      <h5 style="text-align:center;">Select A Term &amp; Class.</h5>
      <form style="margin-left:auto; margin-right:auto; text-align:center;" action="#" method="post">
        <select name="Term" id="termVal">
            <?php
                while ($term = pg_fetch_assoc($terms)) {
                    echo "<option value='" . $term['term_id'] . "'>" . $term['term_name'] . "</option>";
                }
            ?>
        </select>
        <select name="Classes" id="classes">
            <?php
                while ($class = pg_fetch_assoc($classes)) {
                    echo "<option value='" . $class['class_id'] . "'>" . $class['class_name'] . "</option>";
                }
            ?>
        </select>
        <select name="Examtypes" id="examTypes">
            <!-- Exam types populated using JS -->
        </select>
        <div><input style="margin-left:auto; margin-right:auto; text-align:center;" type="submit" name="submit" value="Get Results For This Term" onclick="getTitle();" /></div>
      </form>
    </div>
    <?php
        if(isset($_POST['submit'])){
            $selected_val1 = $_POST['Term'];
            $selected_val2 = $_POST['Classes'];
            $selected_val3 = $_POST['Examtypes'];
            $selected_val_1 = trim($selected_val1,"'");
            $selected_val_2 = trim($selected_val2,"'");
            $selected_val_3 = trim($selected_val3,"'");
            // $no_selection = 0;  // Storing Selected Value In Variable
            // echo "Term " .$selected_val . " stream results";  // Displaying Selected Value
        }
        $no_selection = 1;
        $term = (isset($_POST['submit']) ? $selected_val_1 : $no_selection);
        $class= (isset($_POST['submit']) ? $selected_val_2 : $no_selection);
        $exam= (isset($_POST['submit']) ? $selected_val_3 : $no_selection);
        $class_name = "";
        
        /* class name */
        /*
        if($class == 10){$class_name = "Class Four Fr.";}elseif($class == 28){$class_name = "Class Four Bn.";}
        elseif($class == 12){$class_name = "Class Five Bn.";}elseif($class == 27){$class_name = "Class Five Fr.";}elseif($class == 15){$class_name = "Class Six Fr.";}
        elseif($class == 14){$class_name = "Class Six Bn.";}elseif($class == 16){$class_name = "Class Seven Bn.";}elseif($class == 25){$class_name = "Class Seven Fr.";}
        elseif($class == 23){$class_name = "Class Eight Bn.";}elseif($class == 24){$class_name = "Class Eight Fr.";}else{$class_name = "Please Select A Class";}
        */
        
        /* term name */
        /*
        if($term == 6){$term_name = "Term 3 2018";}elseif($term == 5){$term_name = "Term 2 2018";}elseif($term == 4){$term_name = "Term 1 2018";}elseif($term == 3){$term_name = "Term 3 2017";}
        elseif($term == 1){$term_name = "Term 2 2017";}
        */
    
    ?>
    <div class="container-table100" style="align-items: flex-start;">
  	    <div class="wrap-table100">
            <h4 id="expTitle" style="border-left:7px solid;border-color:#3DE100;background-color:#D8FFC9;">
                <?php 
                    $titleQuery = pg_query($db,"SELECT class_name || ' [ ' || exam_type || ' ] ' || term_name AS title FROM(
                                                	SELECT class_name, exam_type, (SELECT term_name FROM app.terms WHERE term_id = $term) AS term_name FROM
                                                	app.classes
                                                	INNER JOIN app.exam_types USING (class_cat_id)
                                                	WHERE class_id = $class
                                                	AND exam_type_id = $exam
                                                )a");
                    while ($title = pg_fetch_assoc($titleQuery)) {
                      echo  $title['title'];
                    }
                ?>
                
            </h4>
            <hr>
            
            <!-- ******************** CLASS TABLE ******************** -->
            
            <?php
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
                                                                    WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order asc, mark desc)',
                                                                array['gender','student_name'], array['sort_order','subject_name'], '#.mark', null);
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

            ?>
            
            <div class='table100 ver1 m-b-110'>
                <table id='table1'>
                    <div id='t1' class='table100-head'>
                        <thead>
                            <tr class='row100 head' id="tblHeader">
                                <!-- Table header -->
                                <?php
                                    $header = pg_fetch_assoc($examResults);

                                    foreach ($header as $column => $value) {
                                        $subjectString = trim($column,"'");
                                        $subjectName = str_replace ('.', '', $subjectString);
                                        
                                        if(stripos($subjectName, 'student_name') !== false){$subjectName = "STUDENT";}
                                        if(stripos($subjectName, 'rank') !== false){$subjectName = "POS";}
                                        if(stripos($subjectName, 'gender') !== false){$subjectName = "GND";}
                                        if(stripos($subjectName, 'Math') !== false){$subjectName = "MAT";}
                                        if(stripos($subjectName, 'Engl') !== false){$subjectName = "ENG";}
                                        if(stripos($subjectName, '&') !== false){$subjectName = "SS/RE";}
                                        if(stripos($subjectName, 'Social Studies') !== false){$subjectName = "SS";}
                                        if(stripos($subjectName, 'Kisw') !== false){$subjectName = "KIS";}
                                        if(stripos($subjectName, 'Scie') !== false){$subjectName = "SCI";}
                                        if(stripos($subjectName, 'CRE') !== false){$subjectName = "CRE";}
                                        if(stripos($subjectName, 'Lang') !== false){$subjectName = "E.Lng";}
                                        if(stripos($subjectName, 'Compo') !== false){$subjectName = "E.Cmp";}
                                        if(stripos($subjectName, 'Lugh') !== false){$subjectName = "K.Lgh";}
                                        if(stripos($subjectName, 'Insh') !== false){$subjectName = "K.Ins";}
                                        /*
                                        if(strlen(utf8_decode($subjectName)) >= 14){$subjectName = "SS/RE";}
                                        if( (strlen(utf8_decode($subjectName)) >= 4) && (strlen(utf8_decode($subjectName)) < 14) && ($subjectName != "STUDENT") ){$subjectName = substr($subjectName, 0, 4);}
                                        */
                                        
                                        echo "<th class='cell100 column2'>" . $subjectName . "</th>";
                                    }
                                ?>
                            </tr>
                        </thead>
                    </div>
                    <div class='table100-body js-pscroll'>
                        <tbody id="tblBody">
                            <?php
                               pg_result_seek($examResults, 0);
                               /* $body = pg_fetch_assoc($examResults); */
                               while ($body = pg_fetch_assoc($examResults)) {
                                    echo "<tr class='row100 body'>";
                                        foreach ($body as $column2 => $value2) {
                                            echo "<td class='cell100 column2'>" . $value2 . "</td>";
                                        }
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </div>
                </table>
            </div>
            <!-- ******************** END OF CLASS TABLE ******************** -->

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
  <script src="../components/overviewFiles/js/jquery.dataTables.min.js"></script>
  <script src="../components/overviewFiles/js/dataTables.buttons.min.js"></script>
  <script src="../components/overviewFiles/js/jszip.min.js"></script>
  <script src="../components/overviewFiles/js/pdfmake.min.js"></script>
  <script src="../components/overviewFiles/js/vfs_fonts.js"></script>
  <script src="../components/overviewFiles/js/buttons.html5.min.js"></script>
  
  <script type="text/javascript">
    //function reExecute() {
      var intendedName = document.getElementById('expTitle');
      var docName = intendedName.innerHTML;
      var targetTable = document.getElementById('table1').rows[0].cells.length;
      var orderCol = targetTable - 1;
      console.log(document.getElementById('table1').rows[0].cells.length);
      
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
              "order": [[orderCol,"asc"]],
              "bStateSave": true
        } );
      
    //}
  </script>

</body>
</html>
