<?php

        error_reporting(E_ALL);

        /*echo sizeof(json_decode($_POST['src'],true));*/
        $loopArr = json_decode($_POST['src'],true);
        $arrLength = count($loopArr);

        /* DB Connection */
        $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
        $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");

        /* Loop and INSERT */
        for ($n=0; $n < $arrLength; $n++) {
            /* echo $loopArr[$n]['first_name'] . "\n"; */
            $student_id = $loopArr[$n]['student_id'];
            $admission_number = $loopArr[$n]['admission_number'];
            $first_name = $loopArr[$n]['first_name'];
            $middle_name = $loopArr[$n]['middle_name'];
            $last_name = $loopArr[$n]['last_name'];
            $class_id = $loopArr[$n]['class_id'];
            $gender = $loopArr[$n]['gender'];
            $table1 = pg_query($db,"INSERT INTO app.students(student_id, admission_number, gender, first_name, middle_name, last_name, student_category, active, current_class, creation_date, payment_method)
                          VALUES($student_id, '$admission_number', '$gender', '$first_name', '$middle_name', '$last_name', 'Regular', TRUE, $class_id, now(), 'Installments');");
        }

        $array = isset($_POST['src']) ? "success" : "error";
        echo $array;
        
?>
