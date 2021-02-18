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
                $schoolDb = pg_connect("host=". $host . " port=" . $port . " dbname=eduweb_" . $value . " user=" . $user . " password=" . $pwd); // the db connect

                $data = new stdClass(); // general idea = {eduweb_lasalle : [{},{},...]} this will be pushed to all_data
                $data->{$value} = array();

                $creditsObj = new stdClass();
                $creditsQry = pg_query($schoolDb,"SELECT '$value' AS client_id, c.credit_id AS eduweb_credit_id, s.admission_number,
                                                  		c.amount, c.payment_id AS eduweb_payment_id, pii.inv_id AS eduweb_invoice_id, p.payment_date
                                                  FROM app.credits c
                                                  INNER JOIN app.students s USING (student_id)
                                                  INNER JOIN app.payments p USING (payment_id)
                                                  INNER JOIN app.payment_inv_items pii USING (payment_id)
                                                  WHERE p.reversed IS FALSE ORDER BY eduweb_payment_id ASC;"); // executing the query
                $creditsArray = pg_fetch_all($creditsQry );
                $creditsObj->credits = $creditsArray; // all credits in this iteration
                array_push($data->{$value},$creditsObj); // push into $data->{$value} the value $creditsObj

                // finally push the $data array into $output->all_data
                array_push($output->all_data,$data);

                // we now run the insert to the quickbooks db
                try{
                    // quickbooks db
                    $quickbooksDb = pg_connect("host=". $host . " port=" . $port . " dbname=quickbooks_api user=" . $user . " password=" . $pwd); // the db connect

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

          							$insertPaymentsQry = pg_query($quickbooksDb,"UPDATE public.to_quickbooks_credits SET payment_date = '$payment_date'
                                                                    WHERE eduweb_payment_id = $eduweb_payment_id;"); // executing the query
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

    } catch (Exception $ex){
		//append exception to errorLog
        $logErr = $ex->getMessage();
        $output->error_message = $logErr;
        $outputJson = json_encode($output);
		    print_r($outputJson);
	}
?>
