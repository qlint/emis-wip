<?php
    // include pre-loaded client configuration details
    require_once 'v1/config/conf.php';

    $mpesa= new \Safaricom\Mpesa\Mpesa();

    $b2bTransaction=$mpesa->c2b(
                                $ShortCode, 
                                $CommandID, 
                                $Amount, 
                                $Msisdn, 
                                $BillRefNumber 
                            );
    $callbackData=$mpesa->getDataFromCallback();
?>