<?php
	ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
	
    $curl = curl_init();

	curl_setopt_array($curl, array(
	CURLOPT_URL => "https://api.eduweb.co.ke/getClassCats",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => array(
	"Referer:https://zlz.eduweb.co.ke"
	),
	));

	$response = curl_exec($curl);
	var_dump($response);
	curl_close($curl);
	
?>