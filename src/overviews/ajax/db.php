<?php
    /* db conn */
    $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
    $db = pg_connect("host=localhost port=5433 dbname=".$getDbname." user=postgres password=pg_edu@8947");
?>
