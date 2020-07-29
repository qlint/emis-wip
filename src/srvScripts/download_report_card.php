<?php
  header('Access-Control-Allow-Origin: *');

  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing

  $schoolName = $_POST['school_name'];
  $fileName = $_POST['file_name'];
  $filePath = $_POST['file_path'];
  $name = $_POST['name'];
  $path = $_SERVER['DOCUMENT_ROOT'] . '\assets\reportcards\\'.$schoolName;
  if (!file_exists($path)) {
      mkdir($path, 0777, true);
  }

  require('fpdf.php');

  $output_object = new stdClass;

  $output_object->file_name = $fileName;
  $output_object->name = $name;
  $output_object->file_path = $path . '/' . $fileName;
  $output_object->school_name = $schoolName;

  try{
    $image = $path .'/'. $fileName;
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Image($image,20,20,170,240);
    $pdf->Output('F',$path .'/'. $name . '.pdf');

    $output_object->convert_status = "Report card pdf converted successfully.";
    $output_object->return = "Success";
    $output = json_encode($output_object);
    echo $output;

  } catch (Exception $e) {
    file_put_contents('path.txt', print_r($e->getMessage() . PHP_EOL, true), FILE_APPEND);
    $output_object->save_status = $e->getMessage();
    $output = json_encode($output_object);
    echo $output;
  }

?>
