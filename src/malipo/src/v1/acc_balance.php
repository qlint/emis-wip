<?php
    // include pre-loaded client configuration details
    require_once 'config/conf.php';
    require_once 'config/functions.php';

    $accBalanceURL = 'https://sandbox.safaricom.co.ke/mpesa/accountbalance/v1/query';
    
    $accBalCurl = curl_init();
    curl_setopt($accBalCurl, CURLOPT_URL, $accBalanceURL);
    curl_setopt($accBalCurl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '. $safcomToken)); //setting custom header

    /* IDENTIFIER TYPE VARIES:
    * if MSISDN, then type=1
    * if TILL, then type=2
    * if PAYBILL, then type=4
    */
    $accBalCurl_post_data = array(
        //Fill in the request parameters with valid values
        'Initiator' => $initiatorName,
        'SecurityCredential' => $generatedSecCred,
        'CommandID' => 'AccountBalance',
        'PartyA' => $payBill,
        'IdentifierType' => '4',
        'Remarks' => 'Account Balance For '. $payBill,
        'QueueTimeOutURL' => 'https://malipo.eduweb.co.ke/v1/paths/timeout.php',
        'ResultURL' => 'https://malipo.eduweb.co.ke/v1/paths/results.php'
    );
    
    $data_string = json_encode($accBalCurl_post_data);

    curl_setopt($accBalCurl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($accBalCurl, CURLOPT_POST, true);
    curl_setopt($accBalCurl, CURLOPT_POSTFIELDS, $data_string);

    $accBalCurl_response = curl_exec($accBalCurl);
    // print_r($accBalCurl_response);
    // echo "<br>";
    $accBalObj = json_decode($accBalCurl_response);
    var_dump($accBalObj);
    file_put_contents('paths/logs/account-balance-request.txt', print_r($accBalCurl_response . PHP_EOL, true), FILE_APPEND);
    file_put_contents('paths/logs/full-log.txt', print_r($accBalCurl_response . PHP_EOL, true), FILE_APPEND);

?>
