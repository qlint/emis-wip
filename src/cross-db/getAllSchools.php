<?php
    
    include("schoolList.php");
    
    $allSchoolsStats = array();
    /* db conn and query */
    foreach ($schools as &$value) {
      $getDbname = 'eduweb_'.$value;
      $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");

      $schoolStats = pg_query($db,"SELECT
                                  (SELECT COUNT(*) FROM app.students WHERE active IS TRUE) AS total_students,
                                  (SELECT COUNT(*) FROM app.employees INNER JOIN app.employee_cats USING (emp_cat_id) WHERE employees.active IS TRUE AND LOWER(employee_cats.emp_cat_name) = LOWER('TEACHING')) AS total_teachers,
                                  (SELECT COUNT(*) FROM app.guardians WHERE active IS TRUE) AS total_parents,
                                  (SELECT COUNT(*) FROM app.guardians WHERE active IS TRUE AND date_part('year', creation_date) = '2018') AS total_parents_2018,
                                  (SELECT COUNT(*) FROM app.guardians WHERE active IS TRUE AND date_part('year', creation_date) = '2017') AS total_parents_2017,
                                  (SELECT COUNT(*) FROM app.guardians WHERE active IS TRUE AND date_part('year', creation_date) = '2016') AS total_parents_2016,
                                  '$value' as subdomain");
      $schoolStatsResults = json_encode(pg_fetch_assoc($schoolStats));
      array_push($allSchoolsStats, $schoolStatsResults);
    }

    echo json_encode($allSchoolsStats);
?>
