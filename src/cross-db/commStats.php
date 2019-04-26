<?php
    error_reporting(E_ALL);

    include("schoolList.php");
    
    $allSchoolCommunications = array();
    
    /* db conn and query */
    foreach ($schools as &$value) {
      $getDbname = 'eduweb_'.$value;
      $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");

      $eachSchoolCommunications = pg_query($db,"SELECT
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018') AS total_messages,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND send_as_sms IS TRUE) AS total_sms,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND send_as_email IS TRUE) AS total_app,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '01') AS tot_comms_jan,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '02') AS tot_comms_feb,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '03') AS tot_comms_mar,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '04') AS tot_comms_apr,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '05') AS tot_comms_may,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '06') AS tot_comms_jun,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '07') AS tot_comms_jul,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '08') AS tot_comms_aug,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '09') AS tot_comms_sep,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '10') AS tot_comms_oct,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '11') AS tot_comms_nov,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '12') AS tot_comms_dec,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '01' AND send_as_sms IS TRUE) AS tot_sms_jan,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '02' AND send_as_sms IS TRUE) AS tot_sms_feb,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '03' AND send_as_sms IS TRUE) AS tot_sms_mar,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '04' AND send_as_sms IS TRUE) AS tot_sms_apr,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '05' AND send_as_sms IS TRUE) AS tot_sms_may,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '06' AND send_as_sms IS TRUE) AS tot_sms_jun,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '07' AND send_as_sms IS TRUE) AS tot_sms_jul,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '08' AND send_as_sms IS TRUE) AS tot_sms_aug,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '09' AND send_as_sms IS TRUE) AS tot_sms_sep,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '10' AND send_as_sms IS TRUE) AS tot_sms_oct,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '11' AND send_as_sms IS TRUE) AS tot_sms_nov,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '12' AND send_as_sms IS TRUE) AS tot_sms_dec,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '01' AND send_as_email IS TRUE) AS tot_app_jan,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '02' AND send_as_email IS TRUE) AS tot_app_feb,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '03' AND send_as_email IS TRUE) AS tot_app_mar,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '04' AND send_as_email IS TRUE) AS tot_app_apr,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '05' AND send_as_email IS TRUE) AS tot_app_may,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '06' AND send_as_email IS TRUE) AS tot_app_jun,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '07' AND send_as_email IS TRUE) AS tot_app_jul,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '08' AND send_as_email IS TRUE) AS tot_app_aug,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '09' AND send_as_email IS TRUE) AS tot_app_sep,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '10' AND send_as_email IS TRUE) AS tot_app_oct,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '11' AND send_as_email IS TRUE) AS tot_app_nov,
                                                (SELECT COUNT(*) FROM app.communications WHERE date_part('year', creation_date) = '2018' AND date_part('month', creation_date) = '12' AND send_as_email IS TRUE) AS tot_app_dec,
                                  '$value' as subdomain");
      $schoolCommunications = json_encode(pg_fetch_assoc($eachSchoolCommunications));
      array_push($allSchoolCommunications, $schoolCommunications);
    }

    echo json_encode($allSchoolCommunications);

?>
