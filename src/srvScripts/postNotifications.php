<?php
    header('Access-Control-Allow-Origin: *');

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing
    ini_set('max_execution_time', 2000); // increasing max execution time to 5600 seconds

    function getMISDB()
    {
    	$dbhost="localhost";
    	$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5433" : "5434");
    	$dbuser="postgres";
    	$dbpass="pg_edu@8947";
    	$dbname="eduweb_mis";
    	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
    	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    	return $dbConnection;
    }
    
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
    
    function sendMessage ($message, $deviceIds) {

        require('../../api/lib/token.php');
        $fields = array(
            'app_id' => "b6987dd6-80c8-40da-83e0-3ada5d55876c",
            'include_player_ids' => $deviceIds,
            'contents' => array(
              "en" => $message
            )
        );
    
        $fields = json_encode($fields);
        // print("\nJSON sent:\n");
        // print($fields);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Authorization: Basic ' . $_onesignal));
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
        // print("\nJSON response:\n");
        // print($response);
        return $response;
    
    }
    
    $msg_results = new stdClass;
    
    try{
        
        if( isset($_POST['school']) ) {

            $school = $_POST['school']; // the subdomain of the school
        
            $db = getMISDB();
    
            $sth = $db->prepare("SELECT * FROM notifications WHERE sent is false AND subdomain = :subdomain");
            $sth->execute( array(':subdomain' => $school) );
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
    
                // loop through these notifications and send
                foreach($notifications as $notification) {
                    $response = sendMessage($notification->message, $notification->device_user_ids);
                    $responseJson = json_decode($response);
                    global $responseJson;
                    $result = isset($responseJson->error) ? false : true;
            
                    // update database as sent
                    $update = $db->prepare('UPDATE notifications
                                            SET sent = true,
                                                result = :result,
                                                response = :response
                                            WHERE notification_id = :notificationId AND subdomain = :subdomain');
                    $update->execute( array(':response' => $response, ':notificationId' => $notification->notification_id, ':result' => $result, ':subdomain' => $school) );
                }
            }
    
            $db = null;
            
            $msg_results->status = "Success.";
            $msg_results->data = $notifications;
            echo json_encode($msg_results);
            
        }else{
            $msg_results->status = "Error.";
            $msg_results->data = "Did not find school to send notifications for.";
            echo json_encode($msg_results);
        }
    }catch(PDOException $e){
        $msg_results->status = "Error.";
        $msg_results->data = $e;
        echo json_encode($msg_results);
    }

?>