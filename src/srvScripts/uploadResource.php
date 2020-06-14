<?php
  header('Access-Control-Allow-Origin: *');

  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing

  $input = file_get_contents('php://input');
  var_dump($input);
  print_r($_FILES);

  $subDomain = array_shift((explode('.', $_SERVER['HTTP_HOST'])));
  $path = $_SERVER['DOCUMENT_ROOT'] . '\assets\resources\\'.$subDomain;

  // we make sure code is execited only if request comes via POST request
  // if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // create the directory if it doesn't exist
    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }

    // check if file is set to proceed
    if (isset($_FILES['files'])) {
        $errors = [];
        $extensions = [
                        'tiff', 'jpg', 'jpeg', 'png', 'gif',
                        'doc', 'docx', 'odf', 'pdf', 'xls', 'csv', 'ppsx', 'ppt', 'pptx', 'pptm',
                        'mp4', 'm4v', 'flv', 'avi', 'mov', 'wmv', 'webm',
                        'mp3', 'm4a', 'f4v', 'f4a', '3gp', 'wma', 'wav', 'flac', 'ogg', 'acc', 'midi'
                    ];

        $all_files = count($_FILES['files']['tmp_name']);

        for ($i = 0; $i < $all_files; $i++) {
            $file_name = $_FILES['files']['name'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];
            $file_type = $_FILES['files']['type'][$i];
            $file_size = $_FILES['files']['size'][$i];

            $file_name_arr = explode (".", $file_name);
            $actual_file_type = end($file_name_arr);

            file_put_contents('log-file-data.txt', print_r($actual_file_type . PHP_EOL, true), FILE_APPEND);
            $subDir = "";
            if($actual_file_type === 'tiff' || $actual_file_type === 'jpg' || $actual_file_type === 'jpeg' || $actual_file_type === 'png' || $actual_file_type === 'gif'){
                $subDir = "images";
            }elseif($actual_file_type === 'doc' || $actual_file_type === 'docx' || $actual_file_type === 'pdf' || $actual_file_type === 'odf' || $actual_file_type === 'xls' || $actual_file_type === 'csv' || $actual_file_type === 'xlsx' || $actual_file_type === 'ppt' || $actual_file_type === 'pptx' || $actual_file_type === 'ppsx' || $actual_file_type === 'pptm'){
                $subDir = "documents";
            }elseif($actual_file_type === 'mp4' || $actual_file_type === 'm4v' || $actual_file_type === 'flv' || $actual_file_type === 'avi' || $actual_file_type === 'mov' || $actual_file_type === 'wmv' || $actual_file_type === 'webm' || $actual_file_type === 'f4v'){
                $subDir = "videos";
            }elseif($actual_file_type === 'mp3' || $actual_file_type === 'm4a' || $actual_file_type === '3gp' || $actual_file_type === 'f4a' || $actual_file_type === 'wma' || $actual_file_type === 'wav' || $actual_file_type === 'flacc' || $actual_file_type === 'ogg' || $actual_file_type === 'aac' || $actual_file_type === 'midi'){
                $subDir = "audios";
            }
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '\assets\resources\\'.$subDomain.'\\'.$subDir;
            // create the directory if it doesn't exist
            if (!file_exists($fullPath)) {
              mkdir($fullPath, 0777, true);
            }
            $file_ext = strtolower(end(explode('.', $_FILES['files']['name'][$i])));

            // $file = $path .'/' . $file_name;
            $file = $fullPath .'/' . $file_name;

            // capture extension violations
            if (!in_array($file_ext, $extensions)) {
                $errors[] = 'Extension not allowed: ' . $file_name . ' ' . $file_type;
            }

            // capture file size violations ie 1GB(1024mb) * 1024 * 1024
            if ($file_size > 1073741824) {
                $errors[] = 'File size exceeds limit: ' . $file_name . ' ' . $file_type;
            }

            // check for any captured errors above, if none - upload file to dir
            if (empty($errors)) {
                move_uploaded_file($file_tmp, $file);
            }
        }

        if ($errors) print_r($errors);
    }
  // }
?>
