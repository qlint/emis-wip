<?php
    $rawOutput = file_get_contents('php://input');

    file_put_contents('logs/results-messages.txt', print_r($rawOutput . PHP_EOL, true), FILE_APPEND);
    file_put_contents('logs/full-log.txt', print_r($rawOutput . PHP_EOL, true), FILE_APPEND);

    // check if transaction was successful then save to db
    $rawResult = json_decode($rawOutput,true);
    $encodedJson = $rawResult;

if( isset( $encodedJson['Body']['stkCallback'] ) ){
    // echo $encodedJson['Body']['stkCallback']['ResultDesc'] . ' ('. $encodedJson['Body']['stkCallback']['ResultCode'] . ')'; // this should be a success message with code 0
    $code = $encodedJson['Body']['stkCallback']['ResultCode'];
    if( $code === 0 ){
        // get the callback metadata and save to DB
        if( isset( $encodedJson['Body']['stkCallback']['CallbackMetadata'] ) ){
            $responseArray = $encodedJson['Body']['stkCallback']['CallbackMetadata'];
            // var_dump($responseArray);
            foreach ($responseArray as $items){
                foreach ($items as $item){
                    $itemName = $item['Name'];
                    if($itemName === 'Amount'){
                        $amount = $item['Value'];
                    }
                    if($itemName === 'MpesaReceiptNumber'){
                        $receipt = $item['Value'];
                    }
                    if($itemName === 'TransactionDate'){
                        $date = $item['Value'];
                    }
                    if($itemName === 'PhoneNumber'){
                        $phone = $item['Value'];
                    }

                }
            }
            $merchantRequestId = $encodedJson['Body']['stkCallback']['MerchantRequestID'];
            $checkoutRequestId = $encodedJson['Body']['stkCallback']['CheckoutRequestID'];
            $save = "$phone sent $amount with receipt number $receipt on date $date the merchant request is $merchantRequestId and the checkout is $checkoutRequestId";
            print_r($save);

            $dbhost="localhost";
            $dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5434");
            $dbuser = "postgres";
            $dbpass = "pg_edu@8947";
            $dbname = "dev";
            $dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $db = $dbConnection; // the db connect
            $insertPaymentData = $db->query("INSERT INTO app.mpesa_received_payments(amount, mpesa_receipt, transaction_date, phone_number, merchant_request_id, checkout_request_id)
                                            VALUES ($amount, '$receipt', '$date', '$phone', '$merchantRequestId', '$checkoutRequestId');");
            
        }
    }else{
        echo "There seems to be an issue with the payment";
    }
}else{
    // echo "NOT FOUND";
    print_r($encodedJson);
}
?>