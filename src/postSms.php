<?php
    header('Access-Control-Allow-Origin: *');
    
    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing
    ini_set('max_execution_time', 800); // increasing max execution time to 10 mins
    
    if(isset($_POST['src']) && isset($_POST['school'])) {
        
            $postId = $_POST['src']; // this is the com_id for the message we want
            $school = $_POST['school']; // the subdomain of the school
            
            // db connect
            $getDbname = 'eduweb_' . $school;
            $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=pg_edu@8947");
            
            // we get the data of this message
            $messageQuery = pg_query($db,"SELECT communication_sms.com_id, communication_sms.creation_date as message_date, communications.message as message_text,
                                                employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as message_by, communication_sms.first_name ||' ' || communication_sms.last_name AS recipient_name,
                                                communication_sms.sim_number AS phone_number
                                            FROM app.communication_sms
                                            INNER JOIN app.communications ON communication_sms.com_id = communications.com_id
                                            INNER JOIN app.employees ON communications.message_from = employees.emp_id
                                            WHERE communication_sms.com_id = $postId");
            
            // pg_result_seek($messageQuery, 0);
            
            // we want to create an object to hold this message
            $rawMessageObj = new stdClass(); $rawRecipientsObj = new stdClass(); $newMessage = new stdClass();
            $rawMessageObj->message_recipients = Array(); // the recipients key will be an array
            
            $messageQueryResults = pg_fetch_all($messageQuery);
            // print_r($messageQueryResults[4]);
            $size = count($messageQueryResults);
            for( $j = 0; $j < $size; $j++ ) {
                $rawRecipientsObj->recipient_name = $messageQueryResults[$j]['recipient_name'];
                $rawRecipientsObj->phone_number = "+254" . $messageQueryResults[$j]['phone_number'];
                
                array_push($rawMessageObj->message_recipients, clone $rawRecipientsObj);
                // var_dump(json_encode($rawRecipientsObj));
            }
            // var_dump(json_encode($rawRecipientsObj));
            
            while ($row = pg_fetch_assoc($messageQuery)) {
                
                // we create an object
                for( $i = 0; $i < 1; $i++ ) {
         
                    $rawMessageObj->message_by = $row['message_by'];
                    $rawMessageObj->message_date = $row['message_date'];
                    $rawMessageObj->message_text = $row['message_text'];
                    $rawMessageObj->subscriber_name = $school;

                }
                
            }
            
            // var_dump(json_encode($rawMessageObj)); // now all our message particulars are in this object
            
            // we want to split messages with over 100 recipients to groups of 80 in a new variable
            $batch_of = 1;
            $batch = array_chunk($rawMessageObj->message_recipients, $batch_of);
            foreach($batch as $b) {
            
                $newMessage->message_by = $rawMessageObj->message_by;
                $newMessage->message_date = $rawMessageObj->message_date;
                $newMessage->message_text = $rawMessageObj->message_text;
                $newMessage->subscriber_name = $rawMessageObj->subscriber_name;
                // $newMessage->message_recipients = Array();
                $newMessage->message_recipients = $b;
                
                // array_push($newMessage->message_recipients, $b);
                
                // var_dump(json_encode($b)); // this contains our recipients of each loop
                var_dump(json_encode($newMessage));
                
                
                // we send the message
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://sms_api.eduweb.co.ke/api/sendBulkSms"); // the endpoint url
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8')); // the content type headers
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE); // the request type
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newMessage)); // the data to post
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // this works like jquery ajax (asychronous)
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // disable SSL certificate checks and it's complications
                
                $response = curl_exec($ch);
                
                if($response === false)
                {
                    echo 'Curl error: ' . curl_error($ch);
                }
                else
                {
                    echo 'PHP Response: Operation completed without any errors. Wait for API response... ';
                    var_dump($response);
                }
                
                curl_close($ch);
                
                
                $newMessage->message_recipients = Array(); // we need to clear the array so that these recipients are not in the subsequent loops
                
                // give each message 1 second for a response - using 1.5 seconds
                sleep (1);
            }
            
    } else {
        var_dump("There was a problem sending the message(s). Paremeters were not set / received.");
        header('location: ./');
    }
?>