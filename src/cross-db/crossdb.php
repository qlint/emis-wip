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

      foreach ($dbArray as $key => $value) {

      	    $dbOutput = 'DB ' . $key . ' = ' . $value . '<br>';

      	    // now we can create a second db connection for each of the db's above and execute a query on each

      	    $schoolDb = pg_connect("host=localhost port=5433 dbname=" . $value . " user=postgres password=pg_edu@8947"); // the db connect
            try {

              $queryInTextFile = file_get_contents('create-db.txt'); // for larger or more complex queries and create statements, we put in a text file
              // echo $queryInTextFile ."<br>";

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

              $executeOnSchoolDb3 = pg_query($schoolDb,"ALTER TABLE app.invoice_line_items
                                                        ADD COLUMN in_quickbooks boolean NOT NULL DEFAULT false;"); // executing the query
              */
              $executeOnSchoolDb4 = pg_query($schoolDb,'ALTER TABLE app.report_cards_cbc
    OWNER to postgres;'); // executing the query

              // $executeOnSchoolDb5 = pg_query($schoolDb,"SELECT setval('app.students_student_id_seq', (SELECT MAX(student_id) FROM app.students)+1);");
              // $executeOnSchoolDb5 = pg_query($schoolDb,"SELECT setval('app.guardians_guardian_id_seq', (SELECT MAX(guardian_id) FROM app.guardians)+1);");

              $executeOnSchoolDb = pg_query($schoolDb,"$queryInTextFile"); // executing the query
        	    // echo $dbOutput; // just an output of all our db's
            }catch (Exception $e2){
              echo $e2->getMessage();
            }
      }
      $output = json_encode($output_object);
      print_r($output);

    }catch (Exception $e) {
        $output_object->ready_status = $e->getMessage();
        $output = json_encode($output_object);
        print_r($output);
    }
?>
