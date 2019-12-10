<?php
    // include pre-loaded client configuration details
    require_once 'v1/config/conf.php';

    $mpesa= new \Safaricom\Mpesa\Mpesa();

    $trasactionStatus=$mpesa->transactionStatus(
                                                $Initiator, 
                                                $SecurityCredential, 
                                                $CommandID, 
                                                $TransactionID, 
                                                $PartyA, 
                                                $IdentifierType, 
                                                $ResultURL, 
                                                $QueueTimeOutURL, 
                                                $Remarks, 
                                                $Occasion
                                            );
    $callbackData=$mpesa->getDataFromCallback();
?>