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
    // echo $queryInTextFile ."<br>";

    foreach ($dbArray as $key => $value) {

    	    $dbOutput = $key . ' = ' . $value . '<br>';

    	    // now we can create a second db connection for each of the db's above and execute a query on each

    	    $schoolDb = pg_connect("host=localhost port=5432 dbname=" . $value . " user=postgres password=pg_edu@8947"); // the db connect
    	    $executeOnSchoolDb = pg_query($schoolDb,"ALTER TABLE app.employees ADD COLUMN telephone2 character varying;"); // executing the query
    	    echo $dbOutput; // just an output of all our db's
    }
?>
