<?php

function getDB()
{
	$dbhost="localhost";
	$dbport= "5432";
	$dbuser = 'postgres';
	$dbpass = 'postgres';
	$dbname = 'np';
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}

function getMISDB()
{
	$dbhost="localhost";
	$dbport= "5432";
	$dbuser="postgres";
	$dbpass="postgres";
	$dbname="np_admin";
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;

}

function getClientDBData()
{
	$dbConnection = getMISDB();

	$subDomain = getSubDomain();
	$sth = $dbConnection->prepare("SELECT client_id, dbusername, dbpassword FROM clients WHERE subdomain = 'np'");
	$sth->execute(array());
	$appData = $sth->fetch(PDO::FETCH_OBJ);

	$dbData = new stdClass();
	if( $appData )
	{
		$dbData->dbuser = $appData->dbusername;
		$dbData->dbpass = $appData->dbpassword;
		$dbData->dbname = $subDomain;
		$dbData->subdomain = $subDomain;
	}
	$dbConnection = null;
	return $dbData;
}

function getLoginDB()
{
	$dbhost="localhost";
	$dbport= "5432";
	$dbuser = "postgres";
	$dbpass = "postgres";
	$dbname = "np_admin";
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}


function setDBConnection($subDomain)
{
	$dbConnection = getMISDB();
	$sth = $dbConnection->prepare("SELECT client_id, dbusername, dbpassword FROM clients WHERE subdomain = 'np'");
	$sth->execute(array());
	$appData = $sth->fetch(PDO::FETCH_OBJ);

	$dbhost="localhost";
	$dbport= "5432";
	$dbuser = 'postgres';
	$dbpass = 'postgres';
	$dbname = 'np';
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}

function getSubDomain()
{
	return "np";
}

?>
