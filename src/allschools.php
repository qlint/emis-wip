<?php

  // list of all schools - we use it to connect to db dynamically
  $schools = array('dev',
                  'kingsinternational',
                  'lasalle',
                  'pefadonholm',
                  'benedicta',
                  'appleton',
                  'hog',
                  'immaculate',
                  'nawiri',
                  'karemeno',
                  'rongaiboys',
                  'earlydayskindergarten',
                  'goodhope',
                  'jimcy',
                  'kinderville',
                  'newlightgirls',
                  'orchad',
                  'riaracampus',
                  'ridgewayshouse',
                  'rockside',
                  'solidarity',
                  'stelizabeth',
                  'diamondjunior',
                  'eduwebgroup',
                  'faulu',
                  'golfcourse',
                  'havard',
                  'huntersfield');

   // $resultsArr = array(); // we'd use this if we needed more operations with the query results

   // DB connect
   foreach ($schools as &$value) {
     $getDbname = 'eduweb_'.$value;
     $db = pg_connect("host=localhost port=5433 dbname=".$getDbname." user=postgres password=postgres");

     $query = pg_query($db,"ALTER TABLE app.students
                            ADD COLUMN pick_up_drop_off_individual_img character varying");
     // $ourQueryResults = json_encode(pg_fetch_assoc($query)); // we're inserting, we don't need the results


     // array_push($resultsArr, $ourQueryResults); // see $resultsArr comment above
   }
?>
