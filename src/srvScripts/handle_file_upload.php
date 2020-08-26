<?php
  header('Access-Control-Allow-Origin: *');

  error_reporting(E_ALL);  // uncomment this only when testing
  ini_set('display_errors', 1); // uncomment this only when testing
  $output_object = new stdClass;

  try{

    $host = $_SERVER['HTTP_HOST'];
    $splitHost = explode('.', $host);
    $subDomain = array_shift(($splitHost));
    $path = $_SERVER['DOCUMENT_ROOT'] . '\assets\reportcards\\'.$subDomain;
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    $img = $_POST['imgBase64'];
    $name = $_POST['fileName'];
    $student = $_POST['student_id'];
    $term = $_POST['term_id'];

    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $fileData = base64_decode($img);
    //saving
    $fileName = pg_escape_string($name);

    $output_object->student_id = $student;
    $output_object->term_id = $term;
    $output_object->file_name = $fileName;
    $output_object->file_path = $path . '/' . $fileName;

    file_put_contents($path.'/'.$fileName, $fileData);

    /* db conn */
    $db = pg_connect("host=localhost port=5433 dbname=eduweb_$subDomain user=postgres password=pg_edu@8947");
    $enterRecord = pg_query($db,"INSERT INTO app.report_card_files (student_id, term_id, file_name)
                                VALUES ($student, $term, '".$fileName."')
                                ON CONFLICT (file_name) DO UPDATE
                                  SET modified_date = now();");

    $output_object->save_status = "Report card saved successfully.";
    $output = json_encode($output_object);
    echo $output;

  } catch (Exception $e) {
    file_put_contents('path.txt', print_r($e->getMessage() . PHP_EOL, true), FILE_APPEND);
    $output_object->save_status = $e->getMessage();
    $output = json_encode($output_object);
    echo $output;
  }

?>
