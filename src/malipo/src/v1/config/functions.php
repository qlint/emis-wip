<?php
    function finishTransaction($status = true)
    {
        if ($status === true) {
            $resultArray=[
                "ResultDesc"=>"Confirmation Service request accepted successfully",
                "ResultCode"=>"0"
            ];
        } else {
            $resultArray=[
                "ResultDesc"=>"Confirmation Service not accepted",
                "ResultCode"=>"1"
            ];
        }

        header('Content-Type: application/json');
        $finishedTransaction = json_encode($resultArray);
        // var_dump($finishedTransaction);
        file_put_contents('paths/logs/data_from_finished-transaction.txt', print_r($finishedTransaction . PHP_EOL, true), FILE_APPEND);
        file_put_contents('paths/logs/full-log.txt', print_r($finishedTransaction . PHP_EOL, true), FILE_APPEND); 
    }

    function getDataFromCallback(){
        $callbackJSONData=file_get_contents('php://input');
        // var_dump($callbackJSONData);
        file_put_contents('paths/logs/data_from_callback.txt', print_r($callbackJSONData . PHP_EOL, true), FILE_APPEND);
        file_put_contents('paths/logs/full-log.txt', print_r($callbackJSONData . PHP_EOL, true), FILE_APPEND);
    }
?>