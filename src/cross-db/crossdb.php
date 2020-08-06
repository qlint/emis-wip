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

      	    // $schoolDb = pg_connect("host=localhost port=5433 dbname=" . $value . " user=postgres password=pg_edu@8947"); // the db connect

            /*
            $executeOnSchoolDb = pg_query($schoolDb,"ALTER TABLE app.report_cards ADD COLUMN class_teacher_comment text;"); // executing the query

            $executeOnSchoolDb3 = pg_query($schoolDb,"ALTER TABLE app.payments
                                          ADD COLUMN in_quickbooks boolean NOT NULL DEFAULT false;"); // executing the query
            */
            $sch = substr($value, strpos($value, "_") + 1);
            $schoolDb = pg_connect("host=localhost port=5433 dbname=eduweb_mis user=postgres password=pg_edu@8947"); // the db connect
            $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO public.user_auth(subdomain, user_type_id, module_id, add, view, edit, delete, export)
                                                    SELECT '$sch', 7 AS user_type_id, 1 AS module_id, true AS add, true AS view, true AS edit, true AS delete, true AS export"); // executing the query
            $executeOnSchoolDb2 = pg_query($schoolDb,"INSERT INTO public.user_auth(subdomain, user_type_id, module_id, add, view, edit, delete, export)
                                                    SELECT '$sch', 7 AS user_type_id, 2 AS module_id, true AS add, true AS view, true AS edit, true AS delete, true AS export"); // executing the query
            $executeOnSchoolDb3 = pg_query($schoolDb,"INSERT INTO public.user_auth(subdomain, user_type_id, module_id, add, view, edit, delete, export)
                                                    SELECT '$sch', 7 AS user_type_id, 3 AS module_id, true AS add, true AS view, true AS edit, true AS delete, true AS export"); // executing the query
                                                    $executeOnSchoolDb = pg_query($schoolDb,"INSERT INTO public.user_auth(subdomain, user_type_id, module_id, add, view, edit, delete, export)
                                                                                            SELECT '$sch', 8 AS user_type_id, 1 AS module_id, true AS add, true AS view, true AS edit, true AS delete, true AS export"); // executing the query
                                                    $executeOnSchoolDb2 = pg_query($schoolDb,"INSERT INTO public.user_auth(subdomain, user_type_id, module_id, add, view, edit, delete, export)
                                                                                            SELECT '$sch', 8 AS user_type_id, 2 AS module_id, true AS add, true AS view, true AS edit, true AS delete, true AS export"); // executing the query
                                                    $executeOnSchoolDb3 = pg_query($schoolDb,"INSERT INTO public.user_auth(subdomain, user_type_id, module_id, add, view, edit, delete, export)
                                                                                            SELECT '$sch', 8 AS user_type_id, 3 AS module_id, true AS add, true AS view, true AS edit, true AS delete, true AS export"); // executing the query

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
