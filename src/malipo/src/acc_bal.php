<?php
    header('Access-Control-Allow-Origin: *');

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing

    // include pre-loaded client configuration details
    require_once 'v1/config/conf.php';
    require_once 'Mpesa.php';

    $mpesa= new \Safaricom\Mpesa\Mpesa();
    $CommandID = 'AccountBalance';
    $Initiator = 'apitest456';
    $SecurityCredential = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
    $PartyA = '601456';
    $IdentifierType = '4';
    $Remarks = 'Account balance';
    $QueueTimeOutURL = 'https://malipo.eduweb.co.ke/v1/paths/timeout.php';
    $ResultURL = 'https://malipo.eduweb.co.ke/v1/paths/validation.php';

    $balanceInquiry=$mpesa->accountBalance(
                                            $CommandID,
                                            $Initiator,
                                            $SecurityCredential,
                                            $PartyA,
                                            $IdentifierType,
                                            $Remarks,
                                            $QueueTimeOutURL,
                                            $ResultURL
                                        );
    $callbackData=$mpesa->getDataFromCallback();
    var_dump($callbackData);
?>
