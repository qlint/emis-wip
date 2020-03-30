<?php
    header('Access-Control-Allow-Origin: *');

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing
    ini_set('max_execution_time', 2000); // increasing max execution time to 5600 seconds

    $msg_results = new stdClass;

    try{
      if(isset($_POST['src']) && isset($_POST['school'])) {

              $postId = $_POST['src']; // this is the com_id for the message we want
              $school = $_POST['school']; // the subdomain of the school
              $msg_results->status = "Parameters received, setting up message to be sent...";
              $msg_results->school = $school;

              // db connect
              $getDbname = 'eduweb_' . $school;

              $db = pg_connect("host=localhost port=5433 dbname=".$getDbname." user=postgres password=pg_edu@8947");

              // we get the data of this message
              $messageQuery = pg_query($db,"SELECT communication_sms.com_id, communication_sms.creation_date as message_date, communications.message as message_text,
                                                  employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as message_by, communication_sms.first_name ||' ' || communication_sms.last_name AS recipient_name,
                                                  communication_sms.sim_number AS phone_number
                                              FROM app.communication_sms
                                              INNER JOIN app.communications ON communication_sms.com_id = communications.com_id
                                              INNER JOIN app.employees ON communications.message_from = employees.emp_id
                                              WHERE communication_sms.com_id = $postId");
              $messageQueryResults = pg_fetch_all($messageQuery);
              $size = count($messageQueryResults);

              // we want to create an object to hold this message
              $rawMessageObj = new stdClass(); $rawRecipientsObj = new stdClass(); $newMessage = new stdClass();
              $rawMessageObj->message_recipients = Array(); // the recipients key will be an array

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
              $msg_results->raw_message = $rawMessageObj;
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
                  $msg_results->new_message = $newMessage;
                  // echo json_encode($newMessage);


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
                      $msg_results->sending_status = 'CURL ERROR: ' . curl_error($ch);
                  }
                  else
                  {
                      $msg_results->sending_status = 'Message(s) successfully Sent. Wait for API response... ';
                      $msg_results->sending_status_success = $response;
                  }
                  echo json_encode($msg_results);
                  curl_close($ch);


                  $newMessage->message_recipients = Array(); // we need to clear the array so that these recipients are not in the subsequent loops

                  // give each message 2 second for a response
                  sleep (2);
              }

      } else {
          $msg_results->status = "There was a problem sending the message(s). Paremeters were not set / received.";
          echo json_encode($msg_results);
      }
  } catch (Exception $e) {
    $msg_results->status = "ERROR";
    $msg_results->status_message = $e->getMessage();
    echo json_encode($msg_results);
  }
?>
