<?php
  header('Access-Control-Allow-Origin: *');

  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing

  $input = file_get_contents('php://input');
  var_dump($_FILES);
  file_put_contents('path.txt', print_r($input . PHP_EOL, true), FILE_APPEND);
  // var_dump($input);
  // print_r($_FILES);

  $subDomain = array_shift((explode('.', $_SERVER['HTTP_HOST'])));
  $path = $_SERVER['DOCUMENT_ROOT'] . '\assets\reportcards\\'.$subDomain;

  if (!file_exists($path)) {
      mkdir($path, 0777, true);
  }

  $all_files = count($_FILES['files']['tmp_name']);
  $countOutput = "The number of files is " . $all_files;
  file_put_contents('path.txt', print_r($countOutput . PHP_EOL, true), FILE_APPEND);

  for ($i = 0; $i < 1; $i++) {
    file_put_contents('path.txt', print_r($_FILES['files'][$i] . PHP_EOL, true), FILE_APPEND);
    $fileName = $_FILES['files']['name'][$i];
    $file_tmp = $_FILES['files']['tmp_name'][$i];
    $fileType = $_FILES['files']['type'][$i];
    $theFile = $path .'/' . $fileName;
    move_uploaded_file($file_tmp, $theFile);
  }

?>
