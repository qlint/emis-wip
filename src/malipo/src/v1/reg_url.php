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
        'ShortCode' => $payBill,
        'ResponseType' => ' ',
        'ConfirmationURL' => 'https://malipo.eduweb.co.ke/v1/paths/confirmation.php?token='.$safcomToken,
        'ValidationURL' => 'https://malipo.eduweb.co.ke/v1/paths/validation.php?token='.$safcomToken
    );
    
    $regUrl_data_string = json_encode($regCurl_post_data);
    
    curl_setopt($regCurl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($regCurl, CURLOPT_POST, true);
    curl_setopt($regCurl, CURLOPT_POSTFIELDS, $regUrl_data_string);
    
    $regCurl_response = curl_exec($regCurl);
    print_r($regCurl_response);

    $simulateUrl = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';
    
        $simulateCurl = curl_init();
        curl_setopt($simulateCurl, CURLOPT_URL, $simulateUrl);
        curl_setopt($simulateCurl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '. $safcomToken)); //setting custom header
    
    
        $simulate_curl_post_data = array(
                //Fill in the request parameters with valid values
            'ShortCode' => $payBill,
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => '10',
            'Msisdn' => '254740334102',
            'BillRefNumber' => '00000'
        );
    
        $sim_data_string = json_encode($simulate_curl_post_data);
    
        curl_setopt($simulateCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($simulateCurl, CURLOPT_POST, true);
        curl_setopt($simulateCurl, CURLOPT_POSTFIELDS, $sim_data_string);
    
        $sim_curl_response = curl_exec($simulateCurl);
        print_r($sim_curl_response);

    getDataFromCallback();
    finishTransaction();
  ?>