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

$table1 = pg_query($db,"SELECT all_fee_items as fee_item, sum(default_amt) as total_due, sum(paid2) as total_paid, (sum(default_amt) - sum(paid2)) as balance FROM (
                      	SELECT student_name, '#' || ' ' || inv_id as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2, (default_amt - paid2) as balance FROM (
                      		SELECT DISTINCT ON (student_name, inv_id, inv_date, fee_item_id, all_fee_items, default_amt, paid2) student_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2 FROM (
                      			SELECT student_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, p_id, default_amt, sum(paid) over (partition by student_name, fee_item_id, inv_id order by student_name, fee_item_id, inv_id DESC) as paid2 FROM (
                      				SELECT all_stdnt_names as student_name, all_invs as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, all_dflt_amt as default_amt, coalesce(paid,0) as paid, (all_dflt_amt - coalesce(paid,0)) as balance, p_id FROM (
                      					SELECT term, all_stdnt_id, all_stdnt_names, all_invs, inv_date, tot_inv_amt, fee_item_id, all_fee_items, all_dflt_amt, paid_inv, paid_fee_item, p_id, paid FROM (
                      						SELECT * FROM
                      						(
                      						SELECT invoices.inv_id as all_invs, fee_item as all_fee_items, student_fee_items.fee_item_id, /*student_fee_items.amount*/invoice_line_items.amount as all_dflt_amt, /*payment_inv_items.amount as paid,*/ invoices.student_id as all_stdnt_id,
                      							students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS all_stdnt_names,
                      							invoices.inv_date, invoices.total_amount as tot_inv_amt, invoices.canceled, terms.term_name as term, invoices.term_id
                      						FROM app.invoices
                      						INNER JOIN app.terms ON invoices.term_id = terms.term_id
                      						INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
                      						INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                      						INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                      						--INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
                      						INNER JOIN app.students ON invoices.student_id = students.student_id
                      						WHERE /*extract(year from invoices.inv_date) = 2018*/ invoices.term_id = ". $term ."
                      						AND students.active IS TRUE AND fee_items.active IS TRUE
                                  AND invoices.canceled IS FALSE
                      						ORDER BY all_stdnt_names ASC, all_invs DESC
                      						) AS one
                      						FULL OUTER JOIN
                      						(
                      						SELECT invoices.inv_id as paid_inv, fee_item as paid_fee_item, student_fee_items.fee_item_id as fee_item_id2, /*student_fee_items.amount*/invoice_line_items.amount as dflt_amt, payment_inv_items.payment_id as p_id, payment_inv_items.amount as paid, invoices.student_id,
                      							students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                      							invoices.inv_date as inv_date2, invoices.total_amount, invoices.canceled, terms.term_name, invoices.term_id
                      						FROM app.invoices
                      						INNER JOIN app.terms ON invoices.term_id = terms.term_id
                      						INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
                      						INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                      						INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                      						INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
                      						INNER JOIN app.students ON invoices.student_id = students.student_id
                      						WHERE /*extract(year from invoices.inv_date) = 2018*/ invoices.term_id = ". $term ."
                      						AND students.active IS TRUE AND fee_items.active IS TRUE
                                  AND invoices.canceled IS FALSE
                      						ORDER BY student_name ASC, paid_inv DESC
                      						) AS two
                      						ON one.all_invs = two.paid_inv AND one.fee_item_id = two.fee_item_id2
                      					)three
                      				)four
                      			)five ORDER BY student_name ASC, fee_item_id ASC
                      		)six
                      	)seven
                      )eight GROUP BY all_fee_items ORDER BY all_fee_items ASC");

echo "<div class='table100 ver1 m-b-110'>";
echo "<table id='table1'>";
  echo "<div id='t1' class='table100-head'>";
    // echo "<table id='table1'>";
      echo "<thead>";
       echo "<tr class='row100 head'>";
         echo "<th class='cell100 column1'>FEE ITEM</th>";
         echo "<th class='cell100 column3'>TOTAL DUE</th>";
         echo "<th class='cell100 column4'>TOTAL PAID</th>";
         echo "<th class='cell100 column5'>BALANCE</th>";
       echo "</tr>";
      echo "</thead>";
    // echo "</table>";
  echo "</div>";
  echo "<div class='table100-body js-pscroll'>";
    // echo "<table id='table1-2'>";
      echo "<tbody>";
        while ($row = pg_fetch_assoc($table1)) {
          echo "<tr class='row100 body'>";
             echo "<td class='cell100 column1'>" . $row['fee_item'] . "</td>";
             echo "<td class='cell100 column3'>" . number_format($row['total_due']) . "</td>";
             echo "<td class='cell100 column4'>" . number_format($row['total_paid']) . "</td>";
             echo "<td class='cell100 column5'>" . number_format($row['balance']) . "</td>";
         echo "</tr>";
        }
      echo "</tbody>";
    // echo "</table>";
  echo "</div>";
  echo "</table>";
 echo "</div>";

echo "<h4>Amount paid by each student for their fee items for ". $term_name ." (Ordered by Names Ascending)</h4><hr>";
 $table2 = pg_query($db,/*"SELECT payments.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, classes.class_name, payments.payment_date, fee_items.fee_item,
                			invoice_line_items.amount as default_amount, payment_inv_items.amount as amount_paid, payments.amount as total_paid_amount
                		FROM app.payments
                		INNER JOIN app.students ON payments.student_id = students.student_id
                		INNER JOIN app.student_fee_items sfi ON payments.student_id = sfi.student_id
                		INNER JOIN app.payment_inv_items ON payments.payment_id = payment_inv_items.payment_id
                		--INNER JOIN app.payment_replacement_items ON sfi.student_fee_item_id = payment_replacement_items.student_fee_item_id
                		INNER JOIN app.invoice_line_items ON sfi.student_fee_item_id = invoice_line_items.student_fee_item_id
                						AND payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
                						AND payment_inv_items.inv_id = invoice_line_items.inv_id
                		INNER JOIN app.fee_items ON sfi.fee_item_id = fee_items.fee_item_id
                		INNER JOIN app.classes ON students.current_class = classes.class_id
                		WHERE payments.payment_date between (SELECT start_date FROM app.terms WHERE CURRENT_DATE between start_date and end_date) and (SELECT end_date FROM app.terms WHERE CURRENT_DATE between start_date and end_date)
                  ORDER BY payments.student_id ASC, payments.payment_date ASC"*/
                  "SELECT admission_number, student_name, class_name,  '#' || ' ' || inv_id as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2, (default_amt - paid2) as balance FROM (
	SELECT DISTINCT ON (admission_number, student_name, class_name, inv_id, inv_date, fee_item_id, all_fee_items, default_amt, paid2) admission_number, student_name, class_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, default_amt, paid2 FROM (
		SELECT admission_number, student_name, class_name, inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, p_id, default_amt, sum(paid) over (partition by student_name, fee_item_id, inv_id order by student_name, fee_item_id, inv_id DESC) as paid2 FROM (
			SELECT class_name, admission_number, all_stdnt_names as student_name, all_invs as inv_id, inv_date, fee_item_id, all_fee_items, paid_fee_item, all_dflt_amt as default_amt, coalesce(paid,0) as paid, (all_dflt_amt - coalesce(paid,0)) as balance, p_id FROM (
				SELECT class_name, term, all_stdnt_id, admission_number, all_stdnt_names, all_invs, inv_date, tot_inv_amt, fee_item_id, all_fee_items, all_dflt_amt, paid_inv, paid_fee_item, p_id, paid FROM (
					SELECT * FROM
					(
					SELECT invoices.inv_id as all_invs, fee_item as all_fee_items, student_fee_items.fee_item_id, /*student_fee_items.amount*/invoice_line_items.amount as all_dflt_amt, /*payment_inv_items.amount as paid,*/ invoices.student_id as all_stdnt_id,
						class_name, students.admission_number, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS all_stdnt_names,
						invoices.inv_date, invoices.total_amount as tot_inv_amt, invoices.canceled, terms.term_name as term, invoices.term_id
					FROM app.invoices
					INNER JOIN app.terms ON invoices.term_id = terms.term_id
					INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
					INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
					INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
					--INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
					INNER JOIN app.students ON invoices.student_id = students.student_id
          INNER JOIN app.classes ON students.current_class = classes.class_id
					WHERE /*extract(year from invoices.inv_date) = 2018*/ invoices.term_id = ". $term ."
					AND students.active IS TRUE AND fee_items.active IS TRUE
          AND invoices.canceled IS FALSE
					ORDER BY all_stdnt_names ASC, all_invs DESC
					) AS one
					FULL OUTER JOIN
					(
					SELECT invoices.inv_id as paid_inv, fee_item as paid_fee_item, student_fee_items.fee_item_id as fee_item_id2, /*student_fee_items.amount*/invoice_line_items.amount as dflt_amt, payment_inv_items.payment_id as p_id, payment_inv_items.amount as paid, invoices.student_id,
						students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
						invoices.inv_date as inv_date2, invoices.total_amount, invoices.canceled, terms.term_name, invoices.term_id
					FROM app.invoices
					INNER JOIN app.terms ON invoices.term_id = terms.term_id
					INNER JOIN app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id
					INNER JOIN app.student_fee_items ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
					INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
					INNER JOIN app.payment_inv_items ON invoices.inv_id = payment_inv_items.inv_id AND invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
					INNER JOIN app.students ON invoices.student_id = students.student_id
					WHERE /*extract(year from invoices.inv_date) = 2018*/ invoices.term_id = ". $term ."
					AND students.active IS TRUE AND fee_items.active IS TRUE
          AND invoices.canceled IS FALSE
					ORDER BY student_name ASC, paid_inv DESC
					) AS two
					ON one.all_invs = two.paid_inv AND one.fee_item_id = two.fee_item_id2
				)three
			)four
		)five ORDER BY student_name ASC, fee_item_id ASC
	)six
)seven");

 // $col1 = NULL;
 echo "<div class='table100 ver1 m-b-110'>";
    echo "<table id='table2'>";
      echo "<div id='t2' class='table100-head'>";
         echo "<thead>";
          echo "<tr class='row100 head'>";
            echo "<th class='cell100 column7'>ADM #</th>";
            echo "<th class='cell100 column1'>STUDENT NAME</th>";
            echo "<th class='cell100 column1'>CLASS</th>";
            echo "<th class='cell100 column7'>INVOICE #</th>";
            echo "<th class='cell100 column8'>INV DATE</th>";
            echo "<th class='cell100 column9'>FEE ITEM</th>";
            echo "<th class='cell100 column10'>DEFAULT AMT</th>";
            echo "<th class='cell100 column11'>PAID</th>";
            echo "<th class='cell100 column12'>BALANCE</th>";
          echo "</tr>";
         echo "</thead>";
     echo "</div>";
     echo "<div class='table100-body js-pscroll'>";
       echo "<tbody>";
         while ($row2 = pg_fetch_assoc($table2)) {
           // $text1 = '';
           echo "<tr class='row100 body'>";
              echo "<td class='cell100 column7'>" . $row2['admission_number'] . "</td>";
              echo "<td class='cell100 column1'>" . $row2['student_name'] . "</td>";
              echo "<td class='cell100 column1'>" . $row2['class_name'] . "</td>";
              echo "<td class='cell100 column7'>" . $row2['inv_id'] . "</td>";
              echo "<td class='cell100 column8'>" . $row2['inv_date'] . "</td>";
              echo "<td class='cell100 column9'>" . $row2['all_fee_items'] . "</td>";
              echo "<td class='cell100 column10'>" . number_format($row2['default_amt']) . "</td>";
              echo "<td class='cell100 column11'>" . number_format($row2['paid2']) . "</td>";
              echo "<td class='cell100 column12'>" . number_format($row2['balance']) . "</td>";
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
                title: 'Fee-Items-Overviews'
            },
            {
              extend: 'csvHtml5',
              title: 'Fee-Items-Overviews'
          },
            {
              extend: 'pdfHtml5',
              title: 'Fee-Items-Overviews'
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
                title: 'All-Students-Paid-Fee-Items'
            },
            {
              extend: 'csvHtml5',
              title: 'All-Students-Paid-Fee-Items'
          },
            {
                extend: 'pdfHtml5',
                title: 'All-Students-Paid-Fee-Items'
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
