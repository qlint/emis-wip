<?php

$db = pg_connect("host=localhost port=5433 dbname=eduweb_mis user=postgres password=postgres");
// $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
// $db = pg_connect("host=localhost port=5433 dbname=".$getDbname." user=postgres password=postgres");

    $sth = pg_query($db,"SELECT * FROM notifications WHERE sent is false");
    $results = pg_fetch_assoc($sth);

    if($results) {
      function pg_array_parse($literal)
      {
          if ($literal == '') return;
          preg_match_all('/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|[0-9]+|x[0-9a-f]+))*)"\s*)(,|(?<!^\{)(?=\}$))/i', $literal, $matches, PREG_SET_ORDER);
          $values = array();
          foreach ($matches as $match) {
              $values[] = $match[3] != '' ? stripcslashes($match[3]) : (strtolower($match[2]) == 'null' ? null : $match[2]);
          }
          return $values;
      }

      $notifications = array();
      foreach( $results as $result )
      {
        // for each notification, break out the device ids
        // each notification can only have a max of 2000 device ids
        $deviceIds = pg_array_parse($result->device_user_ids);
        $notifications[] = $result;
        //var_dump($deviceIds);
        $deviceChunks = array_chunk($deviceIds, 2000);
        foreach ($deviceChunks as $chunk) {
          $result->device_user_ids = $chunk;
          $notifications[] = $result;
        }
      }
      //var_dump($notifications);

      // loop through these notifications and send
      foreach($notifications as $notification) {
        $response = sendMessage($notification->message, $notification->device_user_ids);
        $responseJson = json_decode($response);
        $result = isset($responseJson->error) ? false : true;

        // update database as sent
        pg_query($db,'UPDATE notifications
                                SET sent = true,
                                    result = {'.$result .'},
                                    response = {'.$response .'}
                                WHERE notification_id = {'.$notification->notification_id .'}');
        // $update->pg_execute( array($response, $notification->notification_id, $result) );

    }
  }

    $db = null;
    echo json_encode(array('response' => 'notification success', 'data' => $responseJson ));



function sendMessage ($message, $deviceIds) {

  require(__DIR__ . "/../api/lib/token.php");
  $fields = array(
    'app_id' => "b6987dd6-80c8-40da-83e0-3ada5d55876c",
    'include_player_ids' => $deviceIds,
    'contents' => array(
      "en" => $message
    )
  );

  $fields = json_encode($fields);
  print("\nJSON sent:\n");
  print($fields);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                         'Authorization: Basic ' . $_onesignal));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

  $response = curl_exec($ch);
  curl_close($ch);
  print("\nJSON response:\n");
  print($response);
  return $response;

}

?>
