<?php
    header('Access-Control-Allow-Origin: *');

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing

    $host = "localhost";
    $port = "5433";
    $user = "postgres";
    $pwd = "pg_edu@8947";

    $output = new stdClass();

    try{
        /* db conn */
        $rootDb = pg_connect("host=" . $host . " port=" . $port . " dbname=eduweb_mis user=" . $user . " password=" . $pwd);

        // Get all school subdomains to build db names
        $fetchDbs = pg_query($rootDb,"SELECT subdomain FROM clients WHERE using_quickbooks IS TRUE");
        $dbArray = array(); // we'll put our db's here

        while ($dbResults = pg_fetch_assoc($fetchDbs))
        {
            $dbCreate = $dbResults['subdomain'];
            array_push($dbArray,$dbCreate); // push into dbArray the value of dbCreate
        }
        $output->databases = $dbArray;

        $output->all_data = array(); // general idea = [{},{},{},...]
        // we now loop through each school db and get students, fee items, invoices, payments & credits
        // but only for schools that utilize quickbooks integration
        foreach ($dbArray as $key => $value) {
            try{
                $data = new stdClass(); // general idea = {eduweb_lasalle : [{},{},...]} this will be pushed to all_data
                $data->{$value} = array();

                $studentsObj = new stdClass();
                $schoolDb = pg_connect("host=". $host . " port=" . $port . " dbname=eduweb_" . $value . " user=" . $user . " password=" . $pwd); // the db connect
                $studentsQry = pg_query($schoolDb,"SELECT '$value' AS client_id, student_id, first_name, middle_name, last_name, admission_number,
                                                        first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS full_name
                                                    FROM app.students WHERE /* active IS TRUE AND */ in_quickbooks IS FALSE
                                                    ORDER BY student_id ASC;"); // executing the query
                $studentsArray = pg_fetch_all($studentsQry );
                $studentsObj->students = $studentsArray; // all students in this iteration
                array_push($data->{$value},$studentsObj); // push into $data->{$value} the value $studentsObj

                $feeItemsObj = new stdClass();
                $feeItemsQry = pg_query($schoolDb,"SELECT '$value' AS subdomain, '$value' AS client_identifier,
                                                        (CASE WHEN char_length(fee_item) > 27 THEN substring(fee_item,1,27) || '...' ELSE substring(fee_item,1,27) END) AS fee_item,
                                                        default_amount AS amount,
                                                        null AS quickbooks_fee_item_id, fee_item_id AS eduweb_fee_item_id
                                                        FROM app.fee_items WHERE /* active IS TRUE AND */ in_quickbooks IS FALSE ORDER BY eduweb_fee_item_id ASC;"); // executing the query
                $feeItemsArray = pg_fetch_all($feeItemsQry );
                $feeItemsObj->fee_items = $feeItemsArray; // all fee items in this iteration
                array_push($data->{$value},$feeItemsObj); // push into $data->{$value} the value $feeItemsObj

                $invoicesObj = new stdClass();
                $invoicesQry = pg_query($schoolDb,"SELECT '$value' AS client_id, i.inv_id AS eduweb_invoice_id, ili.amount,
                                                        (CASE WHEN char_length(fi.fee_item) > 27 THEN substring(fi.fee_item,1,27) || '...' ELSE substring(fi.fee_item,1,27) END) AS fee_item,
                                                        s.admission_number, i.due_date, i.inv_date AS invoice_date
                                                    FROM app.invoices i
                                                    INNER JOIN app.invoice_line_items ili USING (inv_id)
                                                    INNER JOIN app.student_fee_items sfi USING (student_fee_item_id)
                                                    INNER JOIN app.fee_items fi USING (fee_item_id)
                                                    INNER JOIN app.students s ON i.student_id = s.student_id
                                                    WHERE i.canceled IS FALSE AND i.in_quickbooks IS FALSE ORDER BY eduweb_invoice_id ASC;"); // executing the query
                $invoicesArray = pg_fetch_all($invoicesQry );
                $invoicesObj->invoices = $invoicesArray; // all invoices in this iteration
                array_push($data->{$value},$invoicesObj); // push into $data->{$value} the value $invoicesObj

                $paymentsObj = new stdClass();
                $paymentsQry = pg_query($schoolDb,"SELECT '$value' AS client_id, p.payment_id AS eduweb_payment_id, s.admission_number, pii.amount,
                                                        p.payment_date, pii.inv_id AS eduweb_invoice_id, p.payment_id AS receipt_number
                                                    FROM app.payments p
                                                    INNER JOIN app.students s USING (student_id)
                                                    INNER JOIN app.payment_inv_items pii USING (payment_id)
                                                    WHERE p.reversed IS FALSE AND p.in_quickbooks IS FALSE ORDER BY eduweb_payment_id ASC;"); // executing the query
                $paymentsArray = pg_fetch_all($paymentsQry );
                $paymentsObj->payments = $paymentsArray; // all payments in this iteration
                array_push($data->{$value},$paymentsObj); // push into $data->{$value} the value $paymentsObj

                $creditsObj = new stdClass();
                $creditsQry = pg_query($schoolDb,"SELECT '$value' AS client_id, c.credit_id AS eduweb_credit_id, s.admission_number,
                                                  		c.amount, c.payment_id AS eduweb_payment_id, pii.inv_id AS eduweb_invoice_id, p.payment_date, c.payment_id AS receipt_number
                                                  FROM app.credits c
                                                  INNER JOIN app.students s USING (student_id)
                                                  INNER JOIN app.payments p USING (payment_id)
                                                  INNER JOIN app.payment_inv_items pii USING (payment_id)
                                                  WHERE p.reversed IS FALSE AND p.in_quickbooks IS FALSE ORDER BY eduweb_payment_id ASC;"); // executing the query
                $creditsArray = pg_fetch_all($creditsQry );
                $creditsObj->credits = $creditsArray; // all credits in this iteration
                array_push($data->{$value},$creditsObj); // push into $data->{$value} the value $creditsObj

                // finally push the $data array into $output->all_data
                array_push($output->all_data,$data);

                // we now run the insert to the quickbooks db
                try{
                    // quickbooks db
                    $quickbooksDb = pg_connect("host=". $host . " port=" . $port . " dbname=quickbooks_api user=" . $user . " password=" . $pwd); // the db connect

                    // STUDENTS
          					if(is_array($studentsArray)){
          						foreach ($studentsArray as $key => $val) {
          							$client_id = $val["client_id"];
          							$admission_number = pg_escape_string($val["admission_number"]);
          							$first_name = pg_escape_string($val["first_name"]);
          							$middle_name = pg_escape_string($val["middle_name"]);
          							$last_name = pg_escape_string($val["last_name"]);
          							$full_name = pg_escape_string($val["full_name"]);

          							$insertStudentsQry = pg_query($quickbooksDb,"INSERT INTO public.client_students(client_id, admission_number, first_name, middle_name, last_name, full_name)
          																		SELECT '$client_id', '$admission_number', '$first_name', '$middle_name', '$last_name', '$full_name'
          																		WHERE NOT EXISTS (SELECT admission_number FROM public.client_students WHERE admission_number = '$admission_number' AND client_id = '$client_id' LIMIT 1)
          																		;"); // executing the query
                        /*
                        $dte_1 = date("Y-m-d");
                        $tme_1 = date("h:i:sa");
                        $datetime_1 = $dte_1 . " " . $tme_1;

                        file_put_contents('qb_logs/students.txt', print_r($datetime_1 . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('qb_logs/students.txt', print_r($client_id. ' Student ' . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('qb_logs/students.txt', print_r(json_encode($val) . PHP_EOL, true), FILE_APPEND);
                        */
          						}
          					}

                    // FEE ITEMS
          					if(is_array($feeItemsArray)){
          						foreach ($feeItemsArray as $key => $value) {
          							$subdomain = pg_escape_string($value["subdomain"]);
          							$client_identifier = pg_escape_string($value["client_identifier"]);
          							$fee_item = pg_escape_string($value["fee_item"]);
          							$amount = $value["amount"];
          							$eduweb_fee_item_id = $value["eduweb_fee_item_id"];

          							$insertItemsQry = pg_query($quickbooksDb,"INSERT INTO public.fee_items(subdomain, client_identifier, fee_item, amount, eduweb_fee_item_id)
          																	SELECT '$subdomain', '$client_identifier', '$fee_item', $amount, $eduweb_fee_item_id
          																	WHERE NOT EXISTS (SELECT eduweb_fee_item_id FROM public.fee_items WHERE eduweb_fee_item_id = $eduweb_fee_item_id AND subdomain='$subdomain' LIMIT 1)
          														"); // executing the query
          						}
          					}

                    // INVOICES
          					if(is_array($invoicesArray)){
          						foreach ($invoicesArray as $key => $value) {
          							$eduweb_invoice_id = $value["eduweb_invoice_id"];
          							$client_id = pg_escape_string($value["client_id"]);
          							$admission_number = pg_escape_string($value["admission_number"]);
          							$fee_item = pg_escape_string($value["fee_item"]);
          							$amount = $value["amount"];
          							$due_date = pg_escape_string($value["due_date"]);
          							$invoice_date = pg_escape_string($value["invoice_date"]);

          							  // check for existence and either updte or insert

          							  // $checkInvoiceQry = pg_query($quickbooksDb, "SELECT eduweb_invoice_id FROM public.to_quickbooks_invoices WHERE eduweb_invoice_id = $eduweb_invoice_id");
          							  /*
          							  while ($checkInvs = pg_fetch_assoc($checkInvoiceQry))
          							  {
          								  $dbCreate = 'eduweb_' . $dbResults['subdomain']; // full name of the db's
          								  array_push($dbArray,$dbCreate); // push into dbArray the value of dbCreate
          							  }
          							  */

          							  //update if it exists
          							  $updateInvoicesQry = pg_query($quickbooksDb,"UPDATE public.to_quickbooks_invoices SET amount = $amount, due_date = '$due_date', invoice_date = '$invoice_date', in_quickbooks = false
          																		WHERE eduweb_invoice_id = $eduweb_invoice_id AND client_id = '$client_id' AND admission_number = '$admission_number' AND fee_item = '$fee_item'
          															"); // executing the query

          								// insert if it doesn't exist
          								$insertInvoicesQry = pg_query($quickbooksDb,"INSERT INTO public.to_quickbooks_invoices(eduweb_invoice_id, client_id, admission_number, fee_item, amount, due_date, invoice_date)
          																		SELECT $eduweb_invoice_id, '$client_id', '$admission_number', '$fee_item', $amount, '$due_date', '$invoice_date'
          																		WHERE NOT EXISTS (SELECT eduweb_invoice_id FROM public.to_quickbooks_invoices WHERE eduweb_invoice_id = $eduweb_invoice_id AND fee_item = '$fee_item' AND amount = $amount LIMIT 1)
          															"); // executing the query
          								/*
          								$insertInvoicesQry = pg_query($quickbooksDb,"INSERT INTO public.to_quickbooks_invoices(
          																				eduweb_invoice_id, client_id, admission_number, fee_item, amount, due_date, invoice_date)
          																			VALUES (
          																					$eduweb_invoice_id,
          																					'$client_id',
          																					'$admission_number',
          																					'$fee_item',
          																					$amount,
          																					'$due_date',
          																					'$invoice_date'
          																				);"); // executing the query
          								*/
          						}
          					}

                    // PAYMENTS
          					if(is_array($paymentsArray)){
          						foreach ($paymentsArray as $key => $value) {
          							$eduweb_payment_id = $value["eduweb_payment_id"];
          							$client_id = pg_escape_string($value["client_id"]);
          							$admission_number = pg_escape_string($value["admission_number"]);
          							$amount = $value["amount"];
          							$payment_date = pg_escape_string($value["payment_date"]);
          							$eduweb_invoice_id = $value["eduweb_invoice_id"];
          							$receipt_number = $value["receipt_number"];

          							  // update if it exists
          							  $updateInvoicesQry = pg_query($quickbooksDb,"UPDATE public.to_quickbooks_payments SET amount = $amount, payment_date = '$payment_date', in_quickbooks = false
          																		WHERE eduweb_payment_id = $eduweb_payment_id AND client_id = '$client_id' AND admission_number = '$admission_number' AND eduweb_invoice_id = $eduweb_invoice_id AND receipt_number = '$receipt_number'
          															"); // executing the query

          							// insert if it doesn't exist
          							$insertPaymentsQry = pg_query($quickbooksDb,"INSERT INTO public.to_quickbooks_payments(eduweb_payment_id, client_id, admission_number, amount, payment_date, eduweb_invoice_id, receipt_number)
          																		SELECT $eduweb_payment_id, '$client_id', '$admission_number', $amount, '$payment_date', $eduweb_invoice_id, '$receipt_number'
          																		WHERE NOT EXISTS (SELECT eduweb_payment_id FROM public.to_quickbooks_payments WHERE eduweb_payment_id = $eduweb_payment_id AND amount = $amount AND admission_number = '$admission_number' LIMIT 1)
          																			;"); // executing the query
          							/*
          							$insertPaymentsQry = pg_query($quickbooksDb,"INSERT INTO public.to_quickbooks_payments(
          																			eduweb_payment_id, client_id, admission_number, amount, payment_date, eduweb_invoice_id)
          																		VALUES (
          																				$eduweb_payment_id,
          																				'$client_id',
          																				'$admission_number',
          																				$amount,
          																				'$payment_date',
          																				$eduweb_invoice_id
          																			);"); // executing the query
          							*/
          						}
          					}

          					// CREDITS
          					if(is_array($creditsArray)){
          						foreach ($creditsArray as $key => $value) {
          							$eduweb_credit_id = $value["eduweb_credit_id"];
          							$client_id = pg_escape_string($value["client_id"]);
          							$admission_number = pg_escape_string($value["admission_number"]);
          							$amount = $value["amount"];
          							$eduweb_payment_id = $value["eduweb_payment_id"];
          							$eduweb_invoice_id = $value["eduweb_invoice_id"];
          							$payment_date = pg_escape_string($value["payment_date"]);
          							$receipt_number = $value["receipt_number"];

          							// check for existence and either updte or insert
          							$insertPaymentsQry = pg_query($quickbooksDb,"INSERT INTO public.to_quickbooks_credits(client_id, eduweb_credit_id, admission_number, amount, eduweb_payment_id, eduweb_invoice_id, payment_date, receipt_number)
          																		SELECT '$client_id', $eduweb_credit_id, '$admission_number', $amount, $eduweb_payment_id, $eduweb_invoice_id, '$payment_date', '$receipt_number'
          																		WHERE NOT EXISTS (SELECT eduweb_credit_id FROM public.to_quickbooks_credits WHERE eduweb_credit_id = $eduweb_credit_id AND amount = $amount AND admission_number = '$admission_number' LIMIT 1)
          																			;"); // executing the query
          							/*
          							$insertPaymentsQry = pg_query($quickbooksDb,"INSERT INTO public.to_quickbooks_payments(
          																			eduweb_payment_id, client_id, admission_number, amount, payment_date, eduweb_invoice_id)
          																		VALUES (
          																				$eduweb_payment_id,
          																				'$client_id',
          																				'$admission_number',
          																				$amount,
          																				'$payment_date',
          																				$eduweb_invoice_id
          																			);"); // executing the query
          							*/
          						}
          					}

                } catch (Exception $ex1){
                    $logErr1 = $ex1->getMessage();
                    $output->insert_error = $logErr1;
                }
            } catch (Exception $ex0){
                $logErr0 = $ex0->getMessage();
                $output->loop_error = $logErr0;
            }
        }
        $outputJson = json_encode($output);
    		print_r($outputJson); // save this to log
    		$dte_5 = date("Y-m-d");
        $tme_5 = date("h:i:sa");
        $datetime_5 = $dte_5 . " " . $tme_5;

        // file_put_contents('to_qb_log.txt', print_r($datetime_5 . PHP_EOL, true), FILE_APPEND);
        // file_put_contents('to_qb_log.txt', print_r($outputJson . PHP_EOL, true), FILE_APPEND);

    } catch (Exception $ex){
		    //append exception to errorLog
        $logErr = $ex->getMessage();
        $output->error_message = $logErr;
        $outputJson = json_encode($output);
		    print_r($outputJson);
	}
?>
