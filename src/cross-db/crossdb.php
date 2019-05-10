<?php

    error_reporting(E_ALL);

    include("db.php"); // db with all clients
    
    // Query 1 - Get all databases
    $fetchDbs = pg_query($db,"SELECT subdomain FROM clients");
    
    $dbArray = array(); // we'll put our db's here
    
    while ($dbResults = pg_fetch_assoc($fetchDbs)) 
    {
        $dbCreate = 'eduweb_' . $dbResults['subdomain']; // full name of the db's
        array_push($dbArray,$dbCreate); // push into dbArray the value of dbCreate
    }

    // $queryInTextFile = file_get_contents('create-db.txt'); // for larger or more complex queries and create statements, we put in a text file
    
    foreach ($dbArray as $key => $value) {
        
    	    $dbOutput = $key . ' : ' . $value . '<br>';
    	    
    	    // now we can create a second db connect for each of the db's above and execute a query on each
    	    
    	    $schoolDb = pg_connect("host=localhost port=5432 dbname=" . $value . " user=postgres password=pg_edu@8947"); // the db connect
    	    // $executeOnSchoolDb = pg_query($schoolDb,"$queryInTextFile"); // executing the query
    	    $executeOnSchoolDb = pg_query($schoolDb,"ALTER TABLE app.settings ALTER COLUMN value DROP NOT NULL;"); // executing the query
    	    /*
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Bank Branch');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Bank Branch 2');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Bank Name');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Bank Name 2');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Account Name');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Account Name 2');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Account Number');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Account Number 2');");
    	    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.settings(name) VALUES ('Mpesa Details');");
    	    */
    	    
    	    echo $dbOutput; // just an output of all our db's
    }
?>