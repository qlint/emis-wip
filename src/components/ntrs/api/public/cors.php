<?php
error_reporting(E_ALL);  // uncomment this only when testing
ini_set('display_errors', 1); // uncomment this only when testing

require '../vendor/autoload.php';
require '../lib/CorsSlim.php';
//require '../lib/password.php';
require '../lib/db.php';
require '../bootstrap.php';

$inst = new CorsSlim();
echo $inst;

function pg_array_parse($literal)
{
    if ($literal == '') return;
    preg_match_all('/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|[0-9]+|x[0-9a-f]+))*)"\s*)(,|(?<!^\{)(?=\}$))/i', $literal, $matches, PREG_SET_ORDER);
    $values = array();
    foreach ($matches as $match) {
        $values[] = $match[3] != '' ? stripcslashes($match[3]) : (strtolower($match[2]) == 'null' ? null : $match[2]);
    }
    return $values;
}

/*
* https://gist.github.com/jorgeguberte/1270672
* DateTime::diff on PHP has a bug on Windows systems where it always outputs 6015. Here's a workaround]
* that i found on http://acme-tech.net/blog/2010/10/12/php-datetimediff-returns-6015/
*/
function dateDiff($dt1, $dt2) {
    $dt1 = new DateTime($dt1);
    $dt2 = new DateTime($dt2);
    $ts1 = $dt1->format('Y-m-d');
    $ts2 = $dt2->format('Y-m-d');
    $diff = abs(strtotime($ts1)-strtotime($ts2));
    $diff/= 3600*24;
    return $diff;
}
