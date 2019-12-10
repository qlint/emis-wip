<?php
    // include pre-loaded client configuration details
    require_once 'v1/config/conf.php';
    
    $mpesa= new \Safaricom\Mpesa\Mpesa();

    $STKPushRequestStatus=$mpesa->STKPushQuery(
                                                $checkoutRequestID,
                                                $businessShortCode,
                                                $password,
                                                $timestamp
                                            );
    $callbackData=$mpesa->getDataFromCallback();
?>