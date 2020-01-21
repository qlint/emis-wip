<?php

  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing

  // $db_dir = $_SERVER['ProgramFiles'] . "..\/";
  $db_dir =  realpath(__DIR__ . '/..//..//..//..//..//Users');
  var_dump($db_dir);
  echo "<br>";echo "<br>";
  /* raw db path = C:\Users\clint\Documents\common\db-backup */

  foreach(glob($db_dir.'/*.*') as $file) {
      print_r($file);
      echo "<br>";
  }


?>
