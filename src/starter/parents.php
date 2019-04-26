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
             
            $first_name = $loopArr[$n]['first_name'];
            $last_name = $loopArr[$n]['last_name'];
            $telephone = $loopArr[$n]['telephone'];
            $id_number = $loopArr[$n]['id_number'];
            $student_id = $loopArr[$n]['student_id'];
            $guardian_id = $loopArr[$n]['guardian_id'];
            /* $relationship = $loopArr[$n]['relationship']; */
            $email = trim($loopArr[$n]['email'],"'");
            
            $table1 = pg_query($db,"INSERT INTO app.guardians(guardian_id, first_name, last_name, id_number, telephone, email)
                          VALUES($guardian_id, '$first_name', '$last_name', '$id_number', '$telephone', '$email');");
            
            $table2 = pg_query($db,"INSERT INTO app.student_guardians(student_id, guardian_id)
                          VALUES($student_id, $guardian_id);");
        }

        $array = isset($_POST['src']) ? "success" : "error";
        echo $array;
        
?>
