<?php
  header('Access-Control-Allow-Origin: *');

  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing
  ini_set('max_execution_time', 2000); // increasing max execution time to 5600 seconds

  $payload = new stdClass;
  $payload->AuthDetails = array();
  $payload->MessageType = array("3");
  $payload->BatchType = array("1");
  $payload->SourceAddr = array();
  $payload->MessagePayload = array();
  $payload->DestinationAddr = array();
  $payload->DeliveryRequest = array();

  $authDetailsObj = new stdClass;
  $authDetailsObj->UserID = '';
  $authDetailsObj->Token = '';
  $authDetailsObj->Timestamp = '';
  /*
  let params = {
    AuthDetails: [
      {
        UserID: data.user_name,
        Token: data.token,
        Timestamp: data.timestamp
      }
    ],
    SourceAddr: [data.source],
    MessagePayload: [{Text: null }],
    DestinationAddr: [],
    DeliveryRequest: [
      {
        EndPoint: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/srvScripts/rxSms.php",
        Correlator: "ED" + makeid(12)
      }
    ]
  }
  */

  try{
    if( isset($_POST['src']) ) {}
  } catch (Exception $e) {
    $msg_results->status = "ERROR";
    $msg_results->status_message = $e->getMessage();
    echo json_encode($msg_results);
  }
?>
