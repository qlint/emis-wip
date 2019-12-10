<?php
    header('Access-Control-Allow-Origin: *');
    //Set the response content type to application/json
    header("Content-Type:application/json"); 

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when 
    
    require_once '../config/functions.php';

    try
    {
        
        $resp = '{"ResultCode":0,"ResultDesc":"Validation passed successfully"}';
        //read incoming request
        $postData = file_get_contents('php://input');
        //Parse payload to json
        $jdata = json_decode($postData,true);
        //perform business operations here
        //open text file for logging messages by appending
        file_put_contents('logs/validation-messages.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/validation-messages.txt', print_r($postData . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/full-log.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/full-log.txt', print_r($postData . PHP_EOL, true), FILE_APPEND);

        // we finish the transaction successfully
        finishTransaction();

    } catch (Exception $ex){
        //append exception to file
        $logErr = $ex->getMessage();
        
        //set failure response
        $resp = '{"ResultCode": 1, "ResultDesc":"Validation failure due to internal service error"}';
        file_put_contents('logs/validation-errors.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/validation-errors.txt', print_r($logErr . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/full-log.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
        file_put_contents('logs/full-log.txt', print_r($logErr . PHP_EOL, true), FILE_APPEND);

        // validation failed, we finish the transaction by passing false to the callback
        finishTransaction(false);
        
    }
    // echo $resp;

?>