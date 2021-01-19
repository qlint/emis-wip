<?php
    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing
    
    require '../vendor/autoload.php';
    require '../lib/CorsSlim.php';
    //require '../lib/password.php';
    require '../lib/db.php';
    require '../bootstrap.php';

    $app->run();

    function sendSms($textBy, $text, $recipients, $client) {
        $dte = date("Y-m-d");
        $tme = date("h:i:sa");
        $datetime = $dte . " " . $tme;
    
        // file_put_contents('smsLog.txt', print_r("RECIPIENTS" . PHP_EOL, true), FILE_APPEND);
        // file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
        // file_put_contents('smsLog.txt', print_r($recipients . PHP_EOL, true), FILE_APPEND);
        $recipients = json_decode($recipients);
        print_r("RECIPIENTS >");
        var_dump($recipients);
        $userPhone = $recipients[0]->phone_number;
        $userNm = $recipients[0]->name;
    
        // generate token & get client data
        $db = getSmsDB();
        $sth = $db->prepare("SELECT * FROM subscribers WHERE subscriber_name = :subdomain");
        $sth->execute(array(':subdomain' => $client));
    
        $results = $sth->fetch(PDO::FETCH_OBJ);
        // file_put_contents('smsLog.txt', print_r("CLIENT DATA" . PHP_EOL, true), FILE_APPEND);
        // file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
        // file_put_contents('smsLog.txt', print_r(json_encode($results) . PHP_EOL, true), FILE_APPEND);
        print_r("CLIENT DATA >");
        var_dump($results);
        if($results) {
                $usrNme = $results->user_name;
                $pwd = $results->password;
                $token = md5($pwd);
                $source = $results->source;
                $subscriberId = $results->subscriber_id;
                $msgLength = strlen($text);
                $pages = ceil($msgLength/160);
    
                $data = new stdClass();
                $data->user_name = $usrNme;
                $data->token = $token;
                $data->source = $source;
                $data->timestamp = date("d") . date("m") . date("Y") . date("h") . date("i") . date("s");
                $data->subscriber_id = $subscriberId;
    
                function generateRandomString($length) {
                $include_chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
                /* Uncomment below to include symbols */
                /* $include_chars .= "[{(!@#$%^/&*_+;?\:)}]"; */
                $charLength = strlen($include_chars);
                $randomString = '';
                for ($i = 0; $i < $length; $i++) {
                    $randomString .= $include_chars [rand(0, $charLength - 1)];
                }
                        $dte = date("Y-m-d");
                        $tme = date("h:i:sa");
                        $datetime = $dte . " " . $tme;
                        file_put_contents('smsLog.txt', print_r("CORRELATOR" . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('smsLog.txt', print_r($randomString . PHP_EOL, true), FILE_APPEND);
                return $randomString;
            }
    
            // Call function
            $length = 12; # Set result string lenght
            $data->correlator = generateRandomString($length);
    
                // build sms object
                $payload = new stdClass();
                $payload->AuthDetails = array();
                $payload->MessageType = array("3");
                $payload->BatchType = array("1");
                $payload->SourceAddr = array($data->source);
                $payload->MessagePayload = array();
                $payload->DestinationAddr = array();
                $payload->DeliveryRequest = array();
    
                $authDetailsObj = new stdClass();
                $authDetailsObj->UserID = $data->user_name;
                $authDetailsObj->Token = $data->token;
                $authDetailsObj->Timestamp = $data->timestamp;
                array_push($payload->AuthDetails,$authDetailsObj);
    
                $msgPayloadObj = new stdClass();
                $msgPayloadObj->Text = $text;
                array_push($payload->MessagePayload,$msgPayloadObj);
    
                if(count($recipients) == 1){
                    $recipientsObj = new stdClass();
                    $recipientsObj->MSISDN = $userPhone;
                    $recipientsObj->LinkID = "";
                    $recipientsObj->SourceID = 0;
                    array_push($payload->DestinationAddr,$recipientsObj);
                }else{
                    for ($i=0; $i < count($recipients); $i++) {
                        file_put_contents('smsLog.txt', print_r("EACH RECIPIENT" . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('smsLog.txt', print_r($recipients[$i] . PHP_EOL, true), FILE_APPEND);
                        $recipientsObj = new stdClass();
                        $recipientsObj->MSISDN = $recipients[$i]->phone_number;
                        $recipientsObj->LinkID = "";
                        $recipientsObj->SourceID = 0;
                        array_push($payload->DestinationAddr,$recipientsObj);
                    }
                }
    
                $deliveryReqObj = new stdClass();
                $deliveryReqObj->EndPoint = "https://" . $client . ".eduweb.co.ke/srvScripts/rxSms.php";
                $deliveryReqObj->Correlator = 'ED' . $data->correlator;
                array_push($payload->DeliveryRequest,$deliveryReqObj);
    
                file_put_contents('smsLog.txt', print_r("PAYLOAD" . PHP_EOL, true), FILE_APPEND);
                file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                file_put_contents('smsLog.txt', print_r(json_encode($payload) . PHP_EOL, true), FILE_APPEND);
    
                try
            {
                $sth2 = $db->prepare("INSERT INTO public.messages(token, message_date, message_by, message_length, page_count, subscriber_id, message_text)
                                      VALUES (:token, now(), :msgBy, :msgLen, :pageCount, :subscriberId, :msgTxt) returning message_id;");
                $sth2->execute( array(':token' => $data->token, ':msgBy' => $textBy, ':msgLen' => $msgLength, ':pageCount' => $pages, ':subscriberId' => $data->subscriber_id, ':msgTxt' => $text ) );
                $msgId = $sth2->fetch(PDO::FETCH_OBJ);
                $msgId = $msgId->message_id;
    
                        file_put_contents('smsLog.txt', print_r("MESSAGE ID" . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                        file_put_contents('smsLog.txt', print_r($msgId . PHP_EOL, true), FILE_APPEND);
    
                $db->beginTransaction();
    
                        if(count($recipients) == 1){
                            $sth3 = $db->prepare("INSERT INTO public.recipients(message_id, phone_number, recipient_name)
                            VALUES (:msgId, :phoneNumber, :name);");
                            $sth3->execute( array(':msgId' => $msgId, ':phoneNumber' => $userPhone, ':name' => $userNm ) );
                        }else{
                            foreach( $recipients as $recipient )
                    {
                      $phoneNumber = ( isset($recipient->phone_number) ? "+".$recipient->phone_number: null);
                      $name = ( isset($recipient->recipient_name) ? $recipient->recipient_name : null);
    
                      $sth3 = $db->prepare("INSERT INTO public.recipients(message_id, phone_number, recipient_name)
                      VALUES (:msgId, :phoneNumber, :name);");
                      $sth3->execute( array(':msgId' => $msgId, ':phoneNumber' => $phoneNumber, ':name' => $name ) );
                    }
                        }
    
                        try {
                            // send the payload
                            $ch1 = curl_init();
                            curl_setopt($ch1, CURLOPT_URL, "https://api.pasha.biz/submit1.php"); // the endpoint url
                            curl_setopt($ch1, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch1)
                            curl_setopt($ch1, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8')); // the content type headers
                            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, TRUE);
                            curl_setopt($ch1, CURLOPT_HEADER, FALSE);
                            curl_setopt($ch1, CURLOPT_POST, TRUE); // the request type
                            curl_setopt($ch1, CURLOPT_POSTFIELDS, json_encode($payload)); // the data to post
                            curl_setopt($ch1, CURLOPT_FRESH_CONNECT, true); // this works like jquery ajax (asychronous)
                            curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE); // disable SSL certificate checks and it's complications
    
                            curl_exec($ch1);
                            if (curl_errno($ch1)) {
                                $error_msg = curl_error($ch1);
                                file_put_contents('smsLog.txt', print_r("CURL ERROR" . PHP_EOL, true), FILE_APPEND);
                                file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                          file_put_contents('smsLog.txt', print_r($error_msg . PHP_EOL, true), FILE_APPEND);
                            }
                            // file_put_contents('smsLog.txt', print_r("SUCCESS" . PHP_EOL, true), FILE_APPEND);
                            // file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                      // file_put_contents('smsLog.txt', print_r($resp1 . PHP_EOL, true), FILE_APPEND);
                            curl_close($ch1);
                        } catch(\Exception $e3) {
                            file_put_contents('smsLog.txt', print_r("ERROR 3" . PHP_EOL, true), FILE_APPEND);
                            file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                      file_put_contents('smsLog.txt', print_r($e3 . PHP_EOL, true), FILE_APPEND);
                    }
    
                        $db->commit();
              } catch(PDOException $e2) {
                    $errMsg2 = $e2->getMessage();
                    file_put_contents('smsLog.txt', print_r("ERROR 2" . PHP_EOL, true), FILE_APPEND);
                    file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
                    file_put_contents('smsLog.txt', print_r($errMsg2 . PHP_EOL, true), FILE_APPEND);
              }
        }
    }

    sendSms("Clint A", "Debugging the system", '[{"recipient_name":"Eduweb","phone_number":"+254740334102"}]', "dev2");
?>