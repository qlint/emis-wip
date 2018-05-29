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
  <title>Data Overviews</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="/" style="color:#0cff05;"><?php echo htmlspecialchars( array_shift((explode('.', $_SERVER['HTTP_HOST']))) ); ?>.eduweb.co.ke <span style="color:#ffffff;"> - Financial Analysis</span></a>
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
  <h3 style="text-align:center;margin-top:15px;">This page overviews financial data in tables.</h3>
  <div class="limiter">
    <div class="container-table100">
  	   <div class="wrap-table100">
         <h4 id="expTitle">Amount paid by each student for their fee items (Ordered by Names Ascending & Dates Descending)</h4><hr>
<?php
// $db = pg_connect("host=localhost port=5432 dbname=eduweb_highschool_newlightgirls user=postgres password=postgres");
$getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
$db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");


 $table2 = pg_query($db,"SELECT * FROM (
                                  	SELECT s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name, fi.fee_item, pii.amount AS amount_paid, ili.amount AS default_amount, pii.creation_date AS date
                                          FROM app.students s
                                          INNER JOIN app.classes c ON s.current_class = c.class_cat_id
                                          INNER JOIN app.student_fee_items sfi ON s.student_id = sfi.student_id
                                          INNER JOIN app.fee_items fi ON sfi.fee_item_id = fi.fee_item_id
                                          INNER JOIN app.invoice_line_items ili ON sfi.student_fee_item_id = ili.student_fee_item_id
                                          INNER JOIN app.payment_inv_items pii ON ili.inv_item_id = pii.inv_item_id
                                          INNER JOIN app.payments p ON pii.payment_id = p.payment_id
                                          WHERE s.active IS TRUE AND c.active IS TRUE
                                          ORDER BY student_name ASC, date DESC
                                  )a WHERE date >= (SELECT start_date FROM app.terms WHERE now() between start_date and end_date)");

 // $col1 = NULL;
 echo "<div class='table100 ver1 m-b-110'>";
    echo "<table id='table2'>";
      echo "<div id='t2' class='table100-head'>";
         echo "<thead>";
          echo "<tr class='row100 head'>";
            echo "<th class='cell100 column1'>STUDENT NAME</th>";
            echo "<th class='cell100 column7'>CLASS</th>";
            echo "<th class='cell100 column8'>FEE ITEM</th>";
            echo "<th class='cell100 column9'>PAID</th>";
            echo "<th class='cell100 column10'>DEFAULT AMT</th>";
            echo "<th class='cell100 column11'>DATE</th>";
          echo "</tr>";
         echo "</thead>";
     echo "</div>";
     echo "<div class='table100-body js-pscroll'>";
       echo "<tbody>";
         while ($row2 = pg_fetch_assoc($table2)) {
           // $text1 = '';
           echo "<tr class='row100 body'>";
              echo "<td class='cell100 column1'>" . $row2['student_name'] . "</td>";
              echo "<td class='cell100 column7'>" . $row2['class_name'] . "</td>";
              echo "<td class='cell100 column8'>" . $row2['fee_item'] . "</td>";
              echo "<td class='cell100 column9'>" . number_format($row2['amount_paid']) . "</td>";
              echo "<td class='cell100 column10'>" . number_format($row2['default_amount']) . "</td>";
              echo "<td class='cell100 column11'>" . date( 'M j, Y', strtotime($row2['date'])) . "</td>";
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
      $('#table2').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: 'Students-Fee-Items-Analysis'
            },
            {
              extend: 'csvHtml5',
              title: 'Students-Fee-Items-Analysis'
          },
            {
                extend: 'pdfHtml5',
                title: 'Students-Fee-Items-Analysis'
            }
          ]
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
