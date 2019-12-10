<?php
    header('Access-Control-Allow-Origin: *');

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing

    $subDomain = 'dev'; // this should come from a POST req
    // db connect
    $dbhost="localhost";
    $dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5434");
    $dbuser = "postgres";
    $dbpass = "pg_edu@8947";
    $dbname = "digital_payments";
    $dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db = $dbConnection; // the db connect
    $fetchClientData = $db->query("SELECT * FROM clients WHERE domain = '$subDomain';");
    $clientData = $fetchClientData->fetch(PDO::FETCH_OBJ);
    $payBill = $clientData->pb_shortcode;
    $till = $clientData->lnmo_shortcode;
    $client = $clientData->domain;
    $consKey = $clientData->ck;
    $consSec = $clientData->sk;
    $psKey = $clientData->pk;
    $initiatorName = $clientData->initiator_name;
    $initiatorSecCred = $clientData->initiator_sec_cred;
    $generatedSecCred = $clientData->generated_initiator_cred;
    $timestamp ='20'.date(    "ymdhis");
    $password = base64_encode($till.$psKey.$timestamp);
    
    $plainTextPwd = 'ACCOUNTS15';
    $pubKeyFile =  __DIR__ . "/cert/sandbox-cert.cer";
    $publicKey = '';
    if(\is_file($pubKeyFile)){
        $publicKey = file_get_contents($pubKeyFile);
        // echo "Certificate exists\n";
        // print_r($publicKey);
        openssl_public_encrypt($plainTextPwd, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        $securityCredential = base64_encode($encrypted);
        // echo "\n The security credential is ". $securityCredential;
    }else{
        throw new \Exception("Please provide a valid public key file");
    }

    // now we get a token from safaricom before we proceed
    $authUrl = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $authCurl = curl_init();
    curl_setopt($authCurl, CURLOPT_URL, $authUrl);
    $credentials = base64_encode($consKey.':'.$consSec);
    curl_setopt($authCurl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials)); //setting a custom header
    curl_setopt($authCurl, CURLOPT_HEADER, false); // I set this to false to make it easier to work with the output (json only)
    curl_setopt($authCurl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($authCurl, CURLOPT_RETURNTRANSFER, true); // this ensures the response is stored in a var instead of outputing to the page

    $auth_curl_response = curl_exec($authCurl);
    // curl_close($authCurl);
    $authResponse = json_decode($auth_curl_response);
    $safcomToken = $authResponse->access_token;
    // print_r($safcomToken);
    curl_close($authCurl);

    /* WE HAVE NOW SET ALL THE REQUIRED PARAMETERS FOR THE VARIOUS API'S
    * 1) PAYBILL NUMBER = $payBill
    * 2) LIPA NA MPESA = $till [an organization can either have (PAYBILL) or (LIPA NA MPESA)]
    * 3) AUTH_TOKEN = $safcomToken
    * 4) SECURITY_CREDENTIAL = $securityCredential
    * 5) PASSWORD = $password
    * 6) INITIATOR = $initiatorName
    */

?>
