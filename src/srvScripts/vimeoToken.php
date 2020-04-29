<?php
    header('Access-Control-Allow-Origin: *');

    error_reporting(E_ALL);  // uncomment this only when testing
    ini_set('display_errors', 1); // uncomment this only when testing
    
    $x = '047f83cd854363004872dbdf06398728a2ec889f'; // client ID
    $y = 'EZl2LTH3nmuQA4iGXE1VZDmkyucj4aJUe3/hh0/RoQZsRPCUPx6s9BhKHNhwp/6Hg3g3SR/g/oD6kRbJovqPlun84SjyCGlL7ko+rZw08IzNcNlQdCZmXEScLyFZL6zP'; // client secret
    
    $body = '{"grant_type":"client_credentials","scope":"private"}'; // a 'public' scope has limited access privileges

    $client_data = new stdClass;

    try{

        // now we get a token from vimeo before we proceed
        $authUrl = 'https://api.vimeo.com/oauth/authorize/client';

        $authCurl = curl_init();
        curl_setopt($authCurl, CURLOPT_URL, $authUrl);
        $credentials = base64_encode($x.':'.$y);
        curl_setopt($authCurl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials, 'Content-Type: application/json', 'Accept: application/vnd.vimeo.*+json;version=3.4')); //setting a custom header
        curl_setopt($authCurl, CURLOPT_HEADER, false); // I set this to false to make it easier to work with the output (json only)
        curl_setopt($authCurl, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($authCurl, CURLOPT_POSTFIELDS, $body); 
        curl_setopt($authCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($authCurl, CURLOPT_RETURNTRANSFER, true); // this ensures the response is stored in a var instead of outputing to the page

        $auth_curl_response = curl_exec($authCurl);
        
        $authResponse = json_decode($auth_curl_response);
        print_r($auth_curl_response);
        // $vimeoToken = $authResponse->access_token;
        // print_r($vimeoToken);
        
        curl_close($authCurl);
        
        return $auth_curl_response;

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

?>
