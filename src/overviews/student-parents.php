<?php
    /* access control header */
    header('Access-Control-Allow-Origin: *');

    /*db conn */
    include("ajax/db.php");
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
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Students And Parents</span></a>
      <a class="navbar-brand"></a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item active">
            <a class="nav-link" href="<?php echo htmlspecialchars("https://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke"); ?>">Home
              <span class="sr-only">(current)</span>
            </a>
          </li>
          <li class="nav-item active">
            <a class="nav-link" href="<?php echo htmlspecialchars("https://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke/overviews"); ?>" style="color:#0cff05;">Go Back
              <span class="sr-only">(current)</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- End of Header Nav -->
  <div class="limiter">
    <h4 style="text-align:center;margin-top:85px;">This page lists all current students in the school alongside their parents details.</h4>

    <div class="container-table100" style="align-items: flex-start;">
  	    <div class="wrap-table100">
            <h4 id="expTitle" style="border-left:7px solid;border-color:#3DE100;background-color:#D8FFC9;">
                <?php $subDom = array_shift((explode('.', $_SERVER['HTTP_HOST']))); echo $subDom . " students and parents details"; ?>
            </h4>
            <hr>

            <!-- ******************** QUERY ******************** -->

            <?php
            $tableQuery = pg_query($db,"SELECT students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, admission_number, gender, class_name,
                                        	guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name AS parent_name, relationship,
                                        	telephone, email
                                        FROM app.students
                                        LEFT JOIN app.student_guardians USING (student_id)
                                        LEFT JOIN app.guardians USING (guardian_id)
                                        INNER JOIN app.classes ON students.current_class = classes.class_id
                                        WHERE students.active IS TRUE
                                        ORDER BY student_name ASC");

            ?>

            <div class='table100 ver1 m-b-110'>
                <table id='table1'>
                    <div id='t1' class='table100-head'>
                        <thead>
                            <tr class='row100 head'  id="tblHeader">
                                <th class='cell100 column1'>STUDENT NAME</th>
                                <th class='cell100 column2'>ADM. #</th>
                                <th class='cell100 column6'>GND.</th>
                                <th class='cell100 column2'>CLASS</th>
                                <th class='cell100 column3'>PARENT</th>
                                <th class='cell100 column4'>RELATIONSHIP</th>
                                <th class='cell100 column5'>TELEPHONE</th>
                                <th class='cell100 column5'>EMAIL</th>
                            </tr>
                        </thead>
                    </div>
                    <div class='table100-body js-pscroll'>
                        <tbody id="tblBody">
                            <?php
                               while ($row3 = pg_fetch_assoc($tableQuery)) {
                                  echo "<tr class='row100 body'>";
                                     echo "<td class='cell100 column1'>" . $row3['student_name'] . "</td>";
                                     echo "<td class='cell100 column2'>" . $row3['admission_number'] . "</td>";
                                     echo "<td class='cell100 column6'>" . $row3['gender'] . "</td>";
                                     echo "<td class='cell100 column2'>" . $row3['class_name'] . "</td>";
                                     echo "<td class='cell100 column3'>" . $row3['parent_name'] . "</td>";
                                     echo "<td class='cell100 column4'>" . $row3['relationship'] . "</td>";
                                     echo "<td class='cell100 column5'>" . $row3['telephone'] . "</td>";
                                     echo "<td class='cell100 column5'>" . $row3['email'] . "</td>";
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
      var docName = window.location.host.split('.')[0];
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
                    title: docName +' Students-and-Parents'
                },
                {
                  extend: 'csvHtml5',
                  title: docName +' Students-and-Parents'
              },
                {
                    extend: 'pdfHtml5',
                    title: docName +' Students-and-Parents'
                }
              ],
              "order": [[orderCol,"asc"]],
              "bStateSave": true
        } );

    //}
  </script>

</body>
</html>
