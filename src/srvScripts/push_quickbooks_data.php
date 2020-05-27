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
        // we now loop through each school db and get students, fee items, invoices & payments
        // but only for schools that utilize quickbooks integration
        foreach ($dbArray as $key => $value) {
            try{
                $data = new stdClass(); // general idea = {eduweb_lasalle : [{},{},...]} this will be pushed to all_data
                $data->{$value} = array();

                $studentsObj = new stdClass();
                $schoolDb = pg_connect("host=". $host . " port=" . $port . " dbname=eduweb_" . $value . " user=" . $user . " password=" . $pwd); // the db connect
                $studentsQry = pg_query($schoolDb,"SELECT '$value' AS client_id, student_id, first_name, middle_name, last_name, admission_number,
                                                        first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS full_name
                                                    FROM app.students WHERE active IS TRUE AND in_quickbooks IS FALSE
                                                    ORDER BY student_id ASC;"); // executing the query
                $studentsArray = pg_fetch_all($studentsQry );
                $studentsObj->students = $studentsArray; // all students in this iteration
                array_push($data->{$value},$studentsObj); // push into $data->{$value} the value $studentsObj

                $feeItemsObj = new stdClass();
                $feeItemsQry = pg_query($schoolDb,"SELECT '$value' AS subdomain, '$value' AS client_identifier, fee_item, default_amount AS amount,
                                                        null AS quickbooks_fee_item_id, fee_item_id AS eduweb_fee_item_id
                                                        FROM app.fee_items WHERE active IS TRUE AND in_quickbooks IS FALSE ORDER BY eduweb_fee_item_id ASC;"); // executing the query
                $feeItemsArray = pg_fetch_all($feeItemsQry );
                $feeItemsObj->fee_items = $feeItemsArray; // all fee items in this iteration
                array_push($data->{$value},$feeItemsObj); // push into $data->{$value} the value $feeItemsObj

                $invoicesObj = new stdClass();
                $invoicesQry = pg_query($schoolDb,"SELECT '$value' AS client_id, i.inv_id AS eduweb_invoice_id, ili.amount, fi.fee_item,
                                                        s.admission_number, i.due_date
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
                                                        p.payment_date, pii.inv_id AS eduweb_invoice_id
                                                    FROM app.payments p
                                                    INNER JOIN app.students s USING (student_id)
                                                    INNER JOIN app.payment_inv_items pii USING (payment_id)
                                                    WHERE p.reversed IS FALSE AND p.in_quickbooks IS FALSE ORDER BY eduweb_payment_id ASC;"); // executing the query
                $paymentsArray = pg_fetch_all($paymentsQry );
                $paymentsObj->payments = $paymentsArray; // all payments in this iteration
                array_push($data->{$value},$paymentsObj); // push into $data->{$value} the value $paymentsObj

                // finally push the $data array into $output->all_data
                array_push($output->all_data,$data);

                // we now run the insert to the quickbooks db
                try{
                    // quickbooks db
                    $quickbooksDb = pg_connect("host=". $host . " port=" . $port . " dbname=quickbooks_api user=" . $user . " password=" . $pwd); // the db connect

                    // STUDENTS
                    foreach ($studentsArray as $key => $value) {
                        $client_id = $value["client_id"];
                        $admission_number = pg_escape_string($value["admission_number"]);
                        $first_name = pg_escape_string($value["first_name"]);
                        $middle_name = pg_escape_string($value["middle_name"]);
                        $last_name = pg_escape_string($value["last_name"]);
                        $full_name = pg_escape_string($value["full_name"]);

                        $insertStudentsQry = pg_query($quickbooksDb,"INSERT INTO public.client_students(
	                                                                    client_id, admission_number, first_name, middle_name, last_name, full_name)
                                                                    VALUES (
                                                                        '$client_id', 
                                                                        '$admission_number', 
                                                                        '$first_name', 
                                                                        '$middle_name', 
                                                                        '$last_name', 
                                                                        '$full_name'
                                                                    );"); // executing the query
                    }

                    // FEE ITEMS
                    foreach ($feeItemsArray as $key => $value) {
                        $subdomain = pg_escape_string($value["subdomain"]);
                        $client_identifier = pg_escape_string($value["client_identifier"]);
                        $fee_item = pg_escape_string($value["fee_item"]);
                        $amount = $value["amount"];
                        $eduweb_fee_item_id = $value["eduweb_fee_item_id"];

                        $insertItemsQry = pg_query($quickbooksDb,"INSERT INTO public.fee_items(
                                                                    subdomain, client_identifier, fee_item, amount, eduweb_fee_item_id)
                                                                VALUES (
                                                                        '$subdomain', 
                                                                        '$client_identifier', 
                                                                        '$fee_item', 
                                                                        $amount, 
                                                                        $eduweb_fee_item_id
                                                                    );"); // executing the query
                    }

                    // INVOICES
                    foreach ($invoicesArray as $key => $value) {
                        $eduweb_invoice_id = $value["eduweb_invoice_id"];
                        $client_id = pg_escape_string($value["client_id"]);
                        $admission_number = pg_escape_string($value["admission_number"]);
                        $fee_item = pg_escape_string($value["fee_item"]);
                        $amount = $value["amount"];
                        $due_date = pg_escape_string($value["due_date"]);

                        $insertInvoicesQry = pg_query($quickbooksDb,"INSERT INTO public.to_quickbooks_invoices(
                                                                        eduweb_invoice_id, client_id, admission_number, fee_item, amount, due_date)
                                                                    VALUES (
                                                                            $eduweb_invoice_id, 
                                                                            '$client_id', 
                                                                            '$admission_number', 
                                                                            '$fee_item', 
                                                                            $amount, 
                                                                            '$due_date'
                                                                        );"); // executing the query
                    }

                    // PAYMENTS
                    foreach ($paymentsArray as $key => $value) {
                        $eduweb_payment_id = $value["eduweb_payment_id"];
                        $client_id = pg_escape_string($value["client_id"]);
                        $admission_number = pg_escape_string($value["admission_number"]);
                        $amount = $value["amount"];
                        $payment_date = pg_escape_string($value["payment_date"]);
                        $eduweb_invoice_id = $value["eduweb_invoice_id"];

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
		print_r($outputJson);
        // print_r($output); // this is all the data for clarification purposes

    } catch (Exception $ex){
		//append exception to errorLog
        $logErr = $ex->getMessage();
        $output->error_message = $logErr;
        $outputJson = json_encode($output);
		print_r($outputJson);
	}
?>