<?php

function getDB() {
	$dbhost="localhost";
	$dbport="5434";
	$dbuser="postgres";
	$dbpass="postgres";
	$dbname="eduweb_hog";
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass); 
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}
?>