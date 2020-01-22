<?php
    /* access control header */
    header('Access-Control-Allow-Origin: *');

    /*db conn */
    include("ajax/db.php");

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
  <!-- End of Header Nav -->
  <div class="limiter">
    <h4 style="text-align:center;margin-top:85px;">This page overviews the student's paid fee items and the respective balances within the current term.</h4>
    <div style="border: 3px dashed #397C49; width:20%; margin-left:auto;margin-right:auto;text-align:center;padding-bottom:7px;padding-top:7px;">
      <h5 style="text-align:center;">Select A Term.</h5>
      <form style="margin-left:auto; margin-right:auto; text-align:center;" action="#" method="post">
        <select name="Term" id="termVal">
            <?php
                while ($term = pg_fetch_assoc($terms)) {
                    echo "<option value='" . $term['term_id'] . "'>" . $term['term_name'] . "</option>";
                }
            ?>
        </select>
        <div><input style="margin-left:auto; margin-right:auto; text-align:center;" type="submit" name="submit" value="Get Results For This Term" /></div>
      </form>
    </div>
    <?php
    if(isset($_POST['submit'])){
    $selected_val1 = $_POST['Term'];
    $selected_val = trim($selected_val1,"'");
    // $no_selection = 0;  // Storing Selected Value In Variable
    // echo "Term " .$selected_val . " stream results";  // Displaying Selected Value
    }
    $no_selection = 1;
    $term = (isset($_POST['submit']) ? $selected_val : $no_selection);
    $term_name = (isset($_POST['submit']) ? $selected_val : $no_selection);
    $class_name = "";
    // if($term == 1){$term_name = "Term 1";}elseif($term == 2){$term_name = "Term 2";}elseif($term == 3){$term_name = "Term 3";}
    $termName = pg_query($db,"SELECT term_name FROM app.terms WHERE term_id = $term;");
    $term_name = pg_fetch_result($termName, 0, 0);
    ?>
    <div class="container-table100">
  	   <div class="wrap-table100">
         <p style="text-align:center;">Currently showing results for <?php echo $term_name; ?></p>
         <h4>Total amounts due and balances per fee item for the school. (Due <?php echo $term_name; ?>)</h4><hr>
<?php

   echo "<h4>Student invoice balances and credits report for ". $term_name ." (Ordered by Names Ascending)</h4><hr>";
   $table3 = pg_query($db,"SELECT s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
		class_name, three.*,
		CASE WHEN balance >=0 THEN balance ELSE 0 END AS real_balance,
		CASE WHEN balance < 0 THEN balance ELSE 0 END AS credit
FROM (
	SELECT two.*, inv_amount - total_paid AS balance
	FROM (
		SELECT one.inv_id, one.student_id, one.inv_date, one.total_amount AS inv_amount, one.term_id, one.term_name,
				pp.payment_method, pp.total_paid, pp.amount AS sum_amount
		FROM (
			SELECT i.inv_id, i.student_id, i.inv_date, i.total_amount, i.term_id, t.term_name
			FROM app.invoices i
			INNER JOIN app.terms t USING (term_id)
			WHERE canceled = FALSE AND term_id = ". $term ."
		)one
		INNER JOIN
		(
			SELECT payment_id, student_id, total_paid, payment_method, inv_id, sum(amount) AS amount FROM (
				SELECT p.payment_id, p.student_id, p.amount AS total_paid, p.payment_method,
						pi.inv_id, pi.inv_item_id, pi.amount
				FROM app.payments p
				INNER JOIN app.payment_inv_items pi USING (payment_id)
				WHERE p.reversed IS FALSE
			)p
			GROUP BY payment_id, student_id, total_paid, payment_method, inv_id
			ORDER BY student_id ASC
		)pp
		ON one.inv_id = pp.inv_id AND one.student_id = pp.student_id
	)two
)three
INNER JOIN app.students s USING (student_id)
INNER JOIN app.classes c ON s.current_class = c.class_id
WHERE s.active IS TRUE");

   // $col1 = NULL;
   echo "<div class='table100 ver1 m-b-110'>";
      echo "<table id='table3'>";
        echo "<div id='t3' class='table100-head'>";
           echo "<thead>";
            echo "<tr class='row100 head'>";
              echo "<th class='cell100 column1'>STUDENT NAME</th>";
              echo "<th class='cell100 column1'>CLASS</th>";
              echo "<th class='cell100 column7'>INVOICE #</th>";
              echo "<th class='cell100 column8'>INV DATE</th>";
              echo "<th class='cell100 column10'>DEFAULT AMT</th>";
              echo "<th class='cell100 column9'>INV AMOUNT</th>";
              echo "<th class='cell100 column11'>PAID</th>";
              echo "<th class='cell100 column12'>BALANCE</th>";
              echo "<th class='cell100 column12'>CREDIT</th>";
            echo "</tr>";
           echo "</thead>";
       echo "</div>";
       echo "<div class='table100-body js-pscroll'>";
         echo "<tbody>";
           while ($row3 = pg_fetch_assoc($table3)) {
             // $text1 = '';
             echo "<tr class='row100 body'>";
                echo "<td class='cell100 column1'>" . $row3['student_name'] . "</td>";
                echo "<td class='cell100 column1'>" . $row3['class_name'] . "</td>";
                echo "<td class='cell100 column7'># " . $row3['inv_id'] . "</td>";
                echo "<td class='cell100 column8'>" . $row3['inv_date'] . "</td>";
                echo "<td class='cell100 column10'>" . $row3['payment_method'] . "</td>";
                echo "<td class='cell100 column9'>" . number_format($row3['inv_amount']) . "</td>";
                echo "<td class='cell100 column11'>" . number_format($row3['total_paid']) . "</td>";
                echo "<td class='cell100 column12'>" . number_format($row3['real_balance']) . "</td>";
                echo "<td class='cell100 column12'>" . number_format($row3['credit']) . "</td>";
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
	<script src="components/overviewFiles/vendor/jquery/jquery-3.2.1.min.js"></script>
	<script src="components/overviewFiles/vendor/bootstrap/js/popper.js"></script>
	<script src="components/overviewFiles/vendor/bootstrap/js/bootstrap.min.js"></script>
	<script src="components/overviewFiles/vendor/select2/select2.min.js"></script>
	<script src="components/overviewFiles/vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
	<script>
		$('.js-pscroll').each(function(){
			var ps = new PerfectScrollbar(this);

			$(window).on('resize', function(){
				ps.update();
			})
		});
	</script>
	<script src="components/overviewFiles/js/main.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $('#table3').DataTable( {
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
                title: 'All-Students-Balances-and-Credits'
            },
            {
              extend: 'csvHtml5',
              title: 'All-Students-Balances-and-Credits'
          },
            {
                extend: 'pdfHtml5',
                title: 'All-Students-Balances-and-Credits'
            }
          ],
          "order": [[ 0, "asc" ]]
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
