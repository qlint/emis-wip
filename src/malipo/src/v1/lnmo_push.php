<?php
    // include pre-loaded client configuration details
    require_once 'config/conf.php';
    require_once 'config/functions.php';

    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    // $password=base64_encode($BusinessShortCode.$LipaNaMpesaPasskey.$timestamp);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '. $safcomToken)); //setting custom header


    $curl_post_data = array(
        //Fill in the request parameters with valid values
        'BusinessShortCode' => $till,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => '10',
        'PartyA' => '254740334102',
        'PartyB' => $till,
        'PhoneNumber' => '254740334102',
        'CallBackURL' => 'https://malipo.eduweb.co.ke/v1/paths/results.php',
        'AccountReference' => 'adm#101 School Bus Payment',
        'TransactionDesc' => 'Paying Eduweb Technologies For System Integration'
    );

    $data_string = json_encode($curl_post_data);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    $curl_response = curl_exec($curl);
    var_dump($curl_response);

    getDataFromCallback();
    finishTransaction();
  ?>
