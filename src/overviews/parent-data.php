<?php
    /* access control header */
    header('Access-Control-Allow-Origin: *');
    
    /* db conn */
    $schoolName = array_shift((explode('.', $_SERVER['HTTP_HOST'])));
    $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
    $db = pg_connect("host=localhost port=5432 dbname=eduweb_mis user=postgres password=pg_edu@8947");
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
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Credentials</span></a>
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
    <h4 style="text-align:center;margin-top:25px;">Parent Data.</h4>
    
    
    <div class="container-table100">
      
  	   <div class="wrap-table100">
         <h4 id="expTitle" style="border-left:7px solid;border-color:#3DE100;background-color:#D8FFC9;">Parent Data</h4><hr>
<?php

/* -------------------------QUERY ------------------------- */

$table3 = pg_query($db,"SELECT parent_name, relationship, user_name, password, student_name, class_name, 
                              (CASE WHEN device_user_id = '' THEN 'Not-Captured' ELSE device_user_id END) as device
                        FROM (
                        	SELECT p.first_name || ' ' || coalesce(p.middle_name,'') || ' ' || p.last_name AS parent_name, p.device_user_id, 
                        		p.username as user_name, p.password, ps.subdomain, ps.student_id,
                        		tb2.relationship, tb2.student_name, tb2.class_name, p.creation_date
                        	FROM parents p
                        	INNER JOIN parent_students ps ON p.parent_id = ps.parent_id 
                        	INNER JOIN ( SELECT * FROM   dblink('host=localhost user=postgres password=pg_edu@8947 dbname=$getDbname','SELECT s.student_id, s.first_name || '' '' || coalesce(s.middle_name,'''') || '' '' || s.last_name AS student_name, s.current_class, c.class_name, sg.guardian_id, sg.relationship FROM app.students s INNER JOIN app.classes c ON s.current_class = c.class_id INNER JOIN app.student_guardians sg USING (student_id) WHERE s.active IS TRUE')
                        			AS  tb2(student_id integer, student_name character varying, current_class integer, class_name character varying, guardian_id integer, relationship character varying)
                        		    ) AS tb2 ON tb2.student_id = ps.student_id AND tb2.guardian_id = ps.guardian_id
                        	WHERE ps.subdomain='$schoolName'
                        )a");

/* -------------------------TABLE ------------------------- */

echo "<div class='table100 ver1 m-b-110'>";
    echo "<table id='table3'  class='display'>";
        echo "<div id='t1' class='table100-head'>";
        
            echo "<thead>";
                echo "<tr class='row100 head'>";
                    echo "<th class='cell100 column1'>PARENT NAME</th>";
                    echo "<th class='cell100 column2'>RELATIONSHIP</th>";
                    echo "<th class='cell100 column3'>USERNAME</th>";
                    echo "<th class='cell100 column4'>PASSWORD</th>";
                    echo "<th class='cell100 column5'>STUDENT NAME</th>";
                    echo "<th class='cell100 column6'>CLASS</th>";
                    echo "<th class='cell100 column6'>DEVICE</th>";
                echo "</tr>";
            echo "</thead>";

        echo "</div>";
        echo "<div class='table100-body js-pscroll'>";
            echo "<tbody>";
                while ($row3 = pg_fetch_assoc($table3)) {
                  echo "<tr class='row100 body'>";
                     echo "<td class='cell100 column1'>" . $row3['parent_name'] . "</td>";
                     echo "<td class='cell100 column2'>" . $row3['relationship'] . "</td>";
                     echo "<td class='cell100 column3'>" . $row3['user_name'] . "</td>";
                     echo "<td class='cell100 column4'>" . $row3['password'] . "</td>";
                     echo "<td class='cell100 column5'>" . $row3['student_name'] . "</td>";
                     echo "<td class='cell100 column6'>" . $row3['class_name'] . "</td>";
                     echo "<td class='cell100 column6'>" . $row3['device'] . "</td>";
                 echo "</tr>";
                }
            echo "</tbody>";
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
                title: 'credentials'
            },
            {
              extend: 'csvHtml5',
              title: 'credentials'
          },
            {
                extend: 'pdfHtml5',
                title: 'credentials'
            }
          ],
          "order": [[ 0, "asc" ]]
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
