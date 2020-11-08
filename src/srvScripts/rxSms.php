<?php
  header('Access-Control-Allow-Origin: *');

  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing
  ini_set('max_execution_time', 2000); // increasing max execution time to 5600 seconds

  $inp = file_get_contents('php://input');
  // logging
  $dte = date("Y-m-d");
  $tme = date("h:i:sa");
  $datetime = $dte . " " . $tme;
  file_put_contents('smsLog.txt', print_r($datetime . PHP_EOL, true), FILE_APPEND);
  file_put_contents('smsLog.txt', print_r($inp . PHP_EOL, true), FILE_APPEND);

  $msg_results = new stdClass;
  $msg_results->received = $inp;

  try{
    $dbhost="localhost";
  	$dbport= "5433";
  	$dbuser = "postgres";
  	$dbpass = "pg_edu@8947";
    $dbname = "sms_server";
    $dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db = $dbConnection; // the db connect

    $decodedInput = json_decode($inp,true);
    foreach($decodedInput as $msg){
      $dlr_report = ( isset($msg["dlr_report"]) ? $msg["dlr_report"]: null);
      $msg_id = ( isset($msg["msg_id"]) ? $msg["msg_id"]: null);

      $updateQry = $db->query("UPDATE public.recipients SET delivery_report= '$dlr_report'
                                WHERE api_messageid = '$msg_id';");
    }

    $output = json_encode($msg_results);
    file_put_contents('smsLog.txt', print_r($output . PHP_EOL, true), FILE_APPEND);
  } catch (Exception $e) {
    $msg_results->status = "ERROR";
    $msg_results->status_message = $e->getMessage();
    $output = json_encode($msg_results);
    file_put_contents('smsLog.txt', print_r($output . PHP_EOL, true), FILE_APPEND);
  }
?>
