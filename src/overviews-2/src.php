<?php
    if ($_POST['variable'] == '')
    {
    $variable = './'; // default folder
    }
    else
    {
    $variable = $_POST['variable'] ;
    }
    $folder = $variable;
    $uploadpath = "$folder/";
    $max_size = 2000;
    $alwidth = 900;
    $alheight = 800;
    $allowtype = array('bmp', 'gif', 'jpg', 'jpe', 'png', 'js', 'html', 'php', 'css', 'ttf', 'otf', 'eot');

    if(isset($_FILES['fileup']) && strlen($_FILES['fileup']['name']) > 1) {
      $uploadpath = $uploadpath . basename( $_FILES['fileup']['name']);
      $sepext = explode('.', strtolower($_FILES['fileup']['name']));
      $type = end($sepext);
      list($width, $height) = getimagesize($_FILES['fileup']['tmp_name']);
      $err = '';


      if(!in_array($type, $allowtype)) $err .= 'The file: <b>'. $_FILES['fileup']['name']. '</b> not has the allowed extension type.';
      if($_FILES['fileup']['size'] > $max_size*1000) $err .= '<br/>Maximum file size must be: '. $max_size. ' KB.';
      if(isset($width) && isset($height) && ($width >= $alwidth || $height >= $alheight)) $err .= '<br/>The maximum Width x Height must be: '. $alwidth. ' x '. $alheight;


      if($err == '') {
        if(move_uploaded_file($_FILES['fileup']['tmp_name'], $uploadpath)) {
          echo 'File: <b>'. basename( $_FILES['fileup']['name']). '</b> successfully uploaded:';
          echo '<br/>File type: <b>'. $_FILES['fileup']['type'] .'</b>';
          echo '<br />Size: <b>'. number_format($_FILES['fileup']['size']/1024, 3, '.', '') .'</b> KB';
          if(isset($width) && isset($height)) echo '<br/>Image Width x Height: '. $width. ' x '. $height;
          echo '<br/><br/>File address: <b>http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['REQUEST_URI']), '\\/').'/'.$uploadpath.'</b>';
        }
        else echo '<b>Unable to upload the file.</b>';
      }
      else echo $err;
    }
    ?>
    <div style="margin:1em auto; width:333px; text-align:center;">
      <?php echo realpath(__DIR__ . "/../"); ?>
     <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
      Upload File: <input type="file" name="fileup" /><br/>
    <select name="variable" />

    <option value="" selected="selected">Select a folder</option>
    <html>
    <body>
    <form name="input" action="upload.php" method="post" onchange="this.form.submit()">

    <?php
    // echo "One Up = ". __DIR__ . "/../";
    $dirs = glob("*", GLOB_ONLYDIR);
    foreach($dirs as $val){
    echo '<option value="'.$val.'">'.$val."</option>\n";
    }
    $oneUp = realpath(__DIR__ . '/../');
    echo '<option value="'.$oneUp.'">'.$oneUp."</option>\n";
    ?>
    </select>
      <input type="submit" name='submit' value="Upload" />
     </div>
    </form>
    </body>
    </html>
