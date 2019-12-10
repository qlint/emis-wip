<?php
    // include pre-loaded client configuration details
    require_once 'config/conf.php';
    require_once 'config/functions.php';
    // require_once 'register_url.php';

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
            'BillRefNumber' => 'Eduweb App Monthly'
        );
    
        $data_string = json_encode($simulate_curl_post_data);
    
        curl_setopt($simulateCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($simulateCurl, CURLOPT_POST, true);
        curl_setopt($simulateCurl, CURLOPT_POSTFIELDS, $data_string);
    
        $sim_curl_response = curl_exec($simulateCurl);
        file_put_contents('paths/logs/simulate.txt', print_r($sim_curl_response . PHP_EOL, true), FILE_APPEND);
        var_dump($sim_curl_response);

        getDataFromCallback();
        finishTransaction();
?>