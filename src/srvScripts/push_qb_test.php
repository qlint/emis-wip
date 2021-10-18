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
        $fetchDbs = pg_query($rootDb,"SELECT subdomain FROM clients WHERE using_quickbooks IS TRUE LIMIT 1");
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

                $schoolDb = pg_connect("host=". $host . " port=" . $port . " dbname=eduweb_" . $value . " user=" . $user . " password=" . $pwd); // the db connect

                $feeItemsObj = new stdClass();
                $feeItemsQry = pg_query($schoolDb,"SELECT '$value' AS subdomain, '$value' AS client_identifier,
                                                        (CASE WHEN char_length(fee_item) > 27 THEN substring(fee_item,1,27) || '...' ELSE substring(fee_item,1,27) END) AS fee_item,
                                                        default_amount AS amount,
                                                        null AS quickbooks_fee_item_id, fee_item_id AS eduweb_fee_item_id
                                                        FROM app.fee_items ORDER BY eduweb_fee_item_id ASC;"); // executing the query
                $feeItemsArray = pg_fetch_all($feeItemsQry );
                $feeItemsObj->fee_items = $feeItemsArray; // all fee items in this iteration
                array_push($data->{$value},$feeItemsObj); // push into $data->{$value} the value $feeItemsObj

                array_push($output->all_data,$data);

                // we now run the insert to the quickbooks db
                try{
                    // quickbooks db
                    $quickbooksDb = pg_connect("host=". $host . " port=" . $port . " dbname=quickbooks_api user=" . $user . " password=" . $pwd); // the db connect

                    // FEE ITEMS
                    $statObj = new stdClass();
                    $output->statuses = array();
          					if(is_array($feeItemsArray)){
          						foreach ($feeItemsArray as $key => $value) {
          							$subdomain = pg_escape_string($value["subdomain"]);
          							$client_identifier = pg_escape_string($value["client_identifier"]);
          							$fee_item = pg_escape_string($value["fee_item"]);
          							$amount = $value["amount"];
          							$eduweb_fee_item_id = $value["eduweb_fee_item_id"];

                        $itemCheckQry = pg_query($quickbooksDb,"SELECT
                                                                CASE
                                                                	WHEN EXISTS (
                                                                                SELECT fee_item FROM fee_items
                                                                                WHERE fee_item = '$fee_item' AND subdomain = '$subdomain'
                                                                              )
                                                                  THEN 'update'
                                                                	ELSE 'insert'
                                                                END AS status");

                        while ($row = pg_fetch_row($itemCheckQry)) {
                          $status = $row[0];
                          if($status == 'insert'){
                            $statObj->{$fee_item} = 'insert';
                          }else{
                            $statObj->{$fee_item} = 'update';
                          }
                        }
          						}
                      array_push($output->statuses,$statObj);
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
