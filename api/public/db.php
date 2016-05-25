<?php

function getDB() {

	$subDomain = getSubDomain();

	$dbhost="localhost";
	$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5434");
	$dbuser="postgres";
	$dbpass="postgres";
	$dbname="eduweb_mis";
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass); 
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	//return $dbConnection;
	
	
	$sth = $dbConnection->prepare("SELECT client_id, dbusername, dbpassword FROM clients WHERE subdomain = :subDomain");
	$sth->execute(array(':subDomain' => $subDomain));
	$appData = $sth->fetch(PDO::FETCH_OBJ);
	
	if( $appData )
	{
		$dbuser = $appData->dbusername;
		$dbpass = $appData->dbpassword;
		$dbname = "eduweb_" . $subDomain;
		$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass); 
		$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbConnection;
		
	}
	else{
		echo "No user found!";
	}
	

}


function getSubDomain()
{

	$data = explode('.', $_SERVER['HTTP_REFERER']); // Get the sub-domain here
	
	 
	// Add some sanitization for $data
	 
	if (!empty($data[0])) {
		$subdomain = substr($data[0], strpos($data[0], '//') + 2 ); // The * of *.mydummyapp.com will be now stored in $subdomain
	}
	return $subdomain;
}

?>