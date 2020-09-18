<?php

    error_reporting(E_ALL);

    try{
      include("db.php"); // db with all clients
      $output_object = new stdClass;

      // Query 1 - Get all databases
      $fetchDbs = pg_query($db,"SELECT subdomain FROM clients");

      $dbArray = array(); // we'll put our db's here

      while ($dbResults = pg_fetch_assoc($fetchDbs))
      {
          $dbCreate = 'eduweb_' . $dbResults['subdomain']; // full name of the db's
          array_push($dbArray,$dbCreate); // push into dbArray the value of dbCreate
      }
  	  $output_object->ready_status = "DB's selected and ready to run";
      $output_object->school_dbs = $dbArray;
      // $queryInTextFile = file_get_contents('create-db.txt'); // for larger or more complex queries and create statements, we put in a text file
      // echo $queryInTextFile ."<br>";

      foreach ($dbArray as $key => $value) {

      	    $dbOutput = 'DB ' . $key . ' = ' . $value . '<br>';

      	    // now we can create a second db connection for each of the db's above and execute a query on each

      	    $schoolDb = pg_connect("host=localhost port=5433 dbname=" . $value . " user=postgres password=pg_edu@8947"); // the db connect

            /*
            $executeOnSchoolDb = pg_query($schoolDb,"WITH sample (term_id, term_num) AS (
                                                          SELECT term_id, CASE
                                                      				WHEN mnth <= 4 THEN 1
                                                      				WHEN mnth > 4 AND mnth <= 8 THEN 2
                                                      				ELSE 3
                                                      			END AS term_num FROM (
                                                      		SELECT term_id, date_part('month',end_date)::int AS mnth FROM app.terms
                                                      	)a
                                                      )
                                                      --And then proceed to your update
                                                      UPDATE app.terms
                                                          SET term_number = s.term_num
                                                          FROM sample s
                                                          WHERE terms.term_id = s.term_id;"); // executing the query
            */

            $executeOnSchoolDb3 = pg_query($schoolDb,"UPDATE app.countries SET currency_name = 'Kenyan Shilling', currency_symbol = 'KSH', curriculum = '8-4-4,I.G.C.S.E,Montessori,Dual Curriculum (8-4-4/IGCSE)'
                                                    WHERE countries_name = 'Kenya';"); // executing the query
            $executeOnSchoolDb4 = pg_query($schoolDb,"UPDATE app.countries SET currency_name = 'Canadian Dollar', currency_symbol = 'CAD', curriculum = 'ONTARIO K8,ONTARIO K12'
                                                    WHERE countries_name = 'Canada';");
            $executeOnSchoolDb5 = pg_query($schoolDb,"UPDATE app.countries SET currency_name = 'Ugandan Shilling', currency_symbol = 'USH', curriculum = '7-4-2-4,I.G.C.S.E,Montessori,Dual Curriculum'
                                                    WHERE countries_name = 'Uganda';");
            // $executeOnSchoolDb = pg_query($schoolDb,"$queryInTextFile"); // executing the query
      	    // echo $dbOutput; // just an output of all our db's
      }
      $output = json_encode($output_object);
      print_r($output);

    }catch (Exception $e) {
        $output_object->ready_status = $e->getMessage();
        $output = json_encode($output_object);
        print_r($output);
    }
?>
