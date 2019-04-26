<?php
    /* access control header */
    header('Access-Control-Allow-Origin: *');
    
    /* db conn */
    $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
    $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");
    
    /*classes*/
    $classes = pg_query($db,"SELECT entity_id, class_cat_name FROM app.class_cats WHERE ACTIVE IS TRUE AND entity_id IS NOT NULL ORDER BY entity_id DESC;");
    
    /*terms*/
    $terms = pg_query($db,"SELECT term_id, term_name FROM app.terms;");
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
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - My Tests</span></a>
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
    <h4 style="text-align:center;margin-top:25px;">My tests.</h4>
    
    
    <div class="container-table100">
      
  	   <div class="wrap-table100">
         <h4 id="expTitle" style="border-left:7px solid;border-color:#3DE100;background-color:#D8FFC9;">My Test Results</h4><hr>
<?php
// $db = pg_connect("host=localhost port=5432 dbname=eduweb_pefadonholm user=postgres password=postgres");
$getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
$db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");


/* -------------------------CLASS (x) QUERY ------------------------- */
$table3 = pg_query($db,"SELECT g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name AS parent_name, sg.relationship, tb2.user_name, tb2.password, 
	s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name
FROM app.guardians g
INNER JOIN app.student_guardians sg USING (guardian_id)
INNER JOIN app.students s USING (student_id)
INNER JOIN app.classes c ON s.current_class = c.class_id 
LEFT JOIN ( SELECT * FROM   dblink('host=localhost user=postgres password=postgres dbname=eduweb_mis','SELECT guardian_id, first_name, last_name, username, password FROM parents
		INNER JOIN parent_students USING (parent_id)
		WHERE subdomain = ''pefadonholm'' ')
		AS  tb2(guardian_id integer, first_name character varying, last_name character varying, user_name character varying, password character varying)
	    ) AS tb2 ON tb2.guardian_id = g.guardian_id
ORDER BY class_name ASC, parent_name ASC, student_name ASC");
/* -------------------------CLASS (x) TABLE ------------------------- */
echo "<div class='table100 ver1 m-b-110'>";
echo "<table id='table3'  class='display'>";
echo "<div id='t1' class='table100-head'>";
// echo "<table id='table1'>";
echo "<thead>";
    echo "<tr class='row100 head'>";
        echo "<th class='cell100 column1'>PARENT NAME</th>";
        echo "<th class='cell100 column2'>RELATIONSHIP</th>";
        echo "<th class='cell100 column3'>USERNAME</th>";
        echo "<th class='cell100 column4'>PASSWORD</th>";
        echo "<th class='cell100 column5'>STUDENT NAME</th>";
        echo "<th class='cell100 column6'>CLASS</th>";
    echo "</tr>";
echo "</thead>";
// echo "</table>";
echo "</div>";
echo "<div class='table100-body js-pscroll'>";
// echo "<table id='table1-2'>";
echo "<tbody>";
while ($row3 = pg_fetch_assoc($table3)) {
  echo "<tr class='row100 body'>";
     echo "<td class='cell100 column1'>" . $row3['parent_name'] . "</td>";
     echo "<td class='cell100 column2'>" . $row3['relationship'] . "</td>";
     echo "<td class='cell100 column3'>" . $row3['user_name'] . "</td>";
     echo "<td class='cell100 column4'>" . $row3['password'] . "</td>";
     echo "<td class='cell100 column5'>" . $row3['student_name'] . "</td>";
     echo "<td class='cell100 column6'>" . $row3['class_name'] . "</td>";
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
                title: 'pefa-credentials'
            },
            {
              extend: 'csvHtml5',
              title: 'pefa-credentials'
          },
            {
                extend: 'pdfHtml5',
                title: 'pefa-credentials'
            }
          ],
          "order": [[ 0, "asc" ]]
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
                title: 'pefa-credentials'
            },
            {
              extend: 'csvHtml5',
              title: 'pefa-credentials'
          },
            {
                extend: 'pdfHtml5',
                title: 'pefa-credentials'
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
