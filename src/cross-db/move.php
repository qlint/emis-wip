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

        $executeOnSchoolDb = pg_query($schoolDb,"SELECT (SELECT value as bank_name FROM app.settings WHERE name = 'Bank Name 6') AS bank_name,
                                                        (SELECT value as bank_branch FROM app.settings WHERE name = 'Bank Branch 6') AS bank_branch,
                                                        (SELECT value as account_name FROM app.settings WHERE name = 'Account Name 6') AS account_name,
                                                        (SELECT value as account_number FROM app.settings WHERE name = 'Account Number 6') AS account_number");
        $bankDetails = pg_fetch_assoc($executeOnSchoolDb);
        if($bankDetails['bank_name'] != null){
          $bnkName = $bankDetails['bank_name'];
          $bnkBrank = $bankDetails['bank_branch'];
          $bnkAccName = $bankDetails['account_name'];
          $bnkAccNum = $bankDetails['account_number'];
          $insertOnSchoolDb = pg_query($schoolDb,"INSERT INTO app.school_bnks(name, branch, acc_name, acc_number)
                                                  VALUES ('$bnkName', '$bnkBrank', '$bnkAccName', '$bnkAccNum');");
          $output = json_encode($bankDetails);
          print_r($output);
        }

      }
      // $output = json_encode($output_object);
      // print_r($output);

    }catch (Exception $e) {
        $output_object->ready_status = $e->getMessage();
        $output = json_encode($output_object);
        print_r($output);
    }
?>
