<?php
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);  // uncomment this only when testing
ini_set('display_errors', 1); // uncomment this only when testing

require_once '../config/functions.php';

try
{
    //Set the response content type to application/json
    header("Content-Type:application/json");
    $resp = '{"ResultCode":0,"ResultDesc":"THE REQUEST TIMED OUT - S"}';
    //read incoming request
    $postData = file_get_contents('php://input');

    //Parse payload to json
    $jdata = json_decode($postData,true);
    //perform business operations here
    //open text file for logging messages by appending
    file_put_contents('logs/timeout-messages.txt', print_r($jdata . PHP_EOL, true), FILE_APPEND);
    file_put_contents('logs/full-log.txt', print_r($postData . PHP_EOL, true), FILE_APPEND);

    // we finish the transaction successfully
    finishTransaction();

} catch (Exception $ex){
    //set failure response
    $resp = '{"ResultCode": 1, "ResultDesc":"THE REQUEST TIMED OUT - F"}';

    file_put_contents('logs/timeout-errors.txt', print_r($ex->getMessage() . PHP_EOL, true), FILE_APPEND);
    file_put_contents('logs/timeout-erros.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
    file_put_contents('logs/full-log.txt', print_r($resp . PHP_EOL, true), FILE_APPEND);
    file_put_contents('logs/full-log.txt', print_r($ex->getMessage() . PHP_EOL, true), FILE_APPEND);

    // validation failed, we finish the transaction by passing false to the callback
    finishTransaction(false);
    
}


?>