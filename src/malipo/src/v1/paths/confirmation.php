<?php
    header('Access-Control-Allow-Origin: *');
    //Set the response content type to application/json
    header("Content-Type:application/json");

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing

    try
    {
        $resp = '{"ResultCode":0,"ResultDesc":"Confirmation recieved successfully"}';
        //read incoming request
        $postData = file_get_contents('php://input');
        //Parse payload to json
        $jdata = json_decode($postData,true);
        file_put_contents('logs/confirmation-messages.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/confirmation-messages.txt', print_r($postData . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/full-log.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/full-log.txt', print_r($postData . PHP_EOL, true), FILE_APPEND);

        // BUSINESS LOGIC
        // get subdomain to use to db connection
        $dbhost="localhost";
        $dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5434");
        $dbuser = "postgres";
        $dbpass = "pg_edu@8947";
        $dbname = "digital_payments";
        $dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $clientsDb = $dbConnection; // the db connect
        $fetchClientSubdomain = $clientsDb->query("SELECT domain AS subdomain FROM clients WHERE pb_shortcode = '600755';");
        $subdomain = $fetchClientSubdomain->fetch(PDO::FETCH_OBJ);
        $subdomain = $subdomain->subdomain;

        $transactiontype= pg_escape_string($jdata['TransactionType']); 
        $transid= pg_escape_string($jdata['TransID']); 
        $transtime= pg_escape_string($jdata['TransTime']); 
        $transamount= pg_escape_string($jdata['TransAmount']); 
        $businessshortcode= pg_escape_string($jdata['BusinessShortCode']); 
        $billrefno= pg_escape_string($jdata['BillRefNumber']); 
        $invoiceno= pg_escape_string($jdata['InvoiceNumber']); 
        $msisdn= pg_escape_string($jdata['MSISDN']); 
        $orgaccountbalance= pg_escape_string($jdata['OrgAccountBalance']); 
        $firstname= pg_escape_string($jdata['FirstName']); 
        $middlename= pg_escape_string($jdata['MiddleName']); 
        $lastname= pg_escape_string($jdata['LastName']);
        
    } catch (Exception $ex){
        //append exception to errorLog
        $logErr = $ex->getMessage();
        file_put_contents('logs/confirmation-errors.txt', print_r($logErr . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/full-log.txt', print_r($logErr . PHP_EOL, true), FILE_APPEND);
    }
        //echo response
        // echo $resp;
?>