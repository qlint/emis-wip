<?php
    // include pre-loaded client configuration details
    require_once 'config/conf.php';
    require_once 'config/functions.php';

    $regUrl = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';
  
    $regCurl = curl_init();
    curl_setopt($regCurl, CURLOPT_URL, $regUrl);
    curl_setopt($regCurl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '. $safcomToken)); //setting custom header
    
    
    $regCurl_post_data = array(
        //Fill in the request parameters with valid values
        'ShortCode' => $till,
        'ResponseType' => 'Confirmed',
        'ConfirmationURL' => 'https://malipo.eduweb.co.ke/v1/paths/confirmation.php',
        'ValidationURL' => 'https://malipo.eduweb.co.ke/v1/paths/validation.php'
    );
    
    $data_string = json_encode($regCurl_post_data);
    
    curl_setopt($regCurl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($regCurl, CURLOPT_POST, true);
    curl_setopt($regCurl, CURLOPT_POSTFIELDS, $data_string);
    
    $regCurl_response = curl_exec($regCurl);
    print_r($regCurl_response);
    file_put_contents('paths/logs/reg_url-messages.txt', print_r($regCurl_response . PHP_EOL, true), FILE_APPEND);
    file_put_contents('paths/logs/full-log.txt', print_r($regCurl_response . PHP_EOL, true), FILE_APPEND);

    getDataFromCallback();
    finishTransaction();
  ?>