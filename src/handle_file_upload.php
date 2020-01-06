<?php

  $fileName = $_FILES['ourFile']['name'];
  $fileType = $_FILES['ourFile']['type'];
  $theFile = $_FILES['ourFile'];

  $fileContent = file_get_contents($_FILES['ourFile']['tmp_name']);
  $dataUrl = 'data:' . $fileType . ';base64,' . base64_encode($fileContent);
  $json = json_encode(array(
    'name' => $fileName,
    'type' => $fileType,
    'dataUrl' => $dataUrl,
    'school' => $_REQUEST['school']
  ));
  echo $json;

?>
