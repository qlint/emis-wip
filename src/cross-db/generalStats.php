<?php
    error_reporting(E_ALL);

    include("db.php");

    // Query 1
    $generalStats = pg_query($db,"SELECT
                                  (SELECT COUNT(*) AS total_clients FROM clients WHERE active IS TRUE) AS total_clients,
                                  (SELECT COUNT(*) AS total_parents FROM parents WHERE active IS TRUE) AS total_parents,
                                  (SELECT COUNT(*) AS total_parents_2018 FROM parents WHERE active IS TRUE AND date_part('year', creation_date) = '2018') AS total_parents_2018,
                                  (SELECT COUNT(*) AS total_parents_2017 FROM parents WHERE active IS TRUE AND date_part('year', creation_date) = '2017') AS total_parents_2017,
                                  (SELECT COUNT(*) AS total_parents_2016 FROM parents WHERE active IS TRUE AND date_part('year', creation_date) = '2016') AS total_parents_2016,
                                  (SELECT COUNT(*) AS total_app_users FROM parents WHERE active IS TRUE AND device_user_id IS NOT NULL) AS total_app_users,
                                  (SELECT COUNT(*) AS total_app_users FROM parents WHERE active IS TRUE AND device_user_id IS NOT NULL AND date_part('year', creation_date) = '2018') AS total_app_users_2018,
                                  (SELECT COUNT(*) AS total_app_users FROM parents WHERE active IS TRUE AND device_user_id IS NOT NULL AND date_part('year', creation_date) = '2017') AS total_app_users_2017,
                                  (SELECT COUNT(*) AS total_app_users FROM parents WHERE active IS TRUE AND device_user_id IS NOT NULL AND date_part('year', creation_date) = '2016') AS total_app_users_2016");

    	$results = pg_fetch_assoc($generalStats);
      echo json_encode($results);
      /*
    	foreach ($row as $column => $value) {
    	    $results = $column . ' : ' . $value;
    	    echo $results;
      }
      */

?>
