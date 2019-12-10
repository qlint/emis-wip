<?php
    // include pre-loaded client configuration details
    require_once 'v1/config/conf.php';

    $mpesa= new \Safaricom\Mpesa\Mpesa();

    $b2cTransaction=$mpesa->b2c(
                                $InitiatorName, 
                                $SecurityCredential, 
                                $CommandID, 
                                $Amount, 
                                $PartyA, 
                                $PartyB, 
                                $Remarks, 
                                $QueueTimeOutURL, 
                                $ResultURL, 
                                $Occasion
                            );
    $callbackData=$mpesa->getDataFromCallback();
?>