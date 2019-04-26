<?php

$app->get('/sendNotifications/', function () {

  $app = \Slim\Slim::getInstance();
  try{
    $db = getMISDB();
    $subdomain = getSubDomain();

    $sth = $db->prepare("SELECT * FROM notifications WHERE sent is false AND subdomain = :subdomain");
    $sth->execute( array(':subdomain' => $subdomain) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {

      $notifications = array();
      foreach( $results as $result )
      {
        // for each notification, break out the device ids
        // each notification can only have a max of 2000 device ids
        $deviceIds = pg_array_parse($result->device_user_ids);
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
        $update = $db->prepare('UPDATE notifications
                                SET sent = true,
                                    result = :result,
                                    response = :response
                                WHERE notification_id = :notificationId');
        $update->execute( array(':response' => $response, ':notificationId' => $notification->notification_id, ':result' => $result) );
      }
    }

    $db = null;
    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array('response' => 'notifications success', 'data' => $responseJson ));

  }
  catch(PDOException $e){
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array('response' => 'notifications error', 'data' => $e->getMessage()  ));
  }
});

function sendMessage ($message, $deviceIds) {

  require('../lib/token.php');
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

  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
  header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

  $response = curl_exec($ch);
  curl_close($ch);
  print("\nJSON response:\n");
  print($response);
  return $response;

}

?>
