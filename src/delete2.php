<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    function httpGet($url)
    {
        $ch = curl_init();  
    
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    //  curl_setopt($ch,CURLOPT_HEADER, false);
    
        $output=curl_exec($ch);
    
        curl_close($ch);
        var_dump($output);
    }
    
    echo httpGet("https://api.eduweb.co.ke/getStudentReportCardData/lasalle/90/5/12");
?>