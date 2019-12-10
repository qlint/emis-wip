<?php
    // include pre-loaded client configuration details
    require_once 'v1/config/conf.php';
    
    $mpesa= new \Safaricom\Mpesa\Mpesa();

    $stkPushSimulation=$mpesa->STKPushSimulation(
                                                    $BusinessShortCode, 
                                                    $LipaNaMpesaPasskey, 
                                                    $TransactionType, 
                                                    $Amount, 
                                                    $PartyA, 
                                                    $PartyB, 
                                                    $PhoneNumber, 
                                                    $CallBackURL, 
                                                    $AccountReference, 
                                                    $TransactionDesc, 
                                                    $Remarks
                                                );
    $callbackData=$mpesa->getDataFromCallback();
?>