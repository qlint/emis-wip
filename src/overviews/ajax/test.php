<?php
header('Access-Control-Allow-Origin: *');
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.2/jquery.js" type="text/javascript"></script>
<title>Test</title>
  <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
  <![endif]-->
</head>

<body>
  <?php
  error_reporting(E_ALL);

  // var_dump($_SERVER['DOCUMENT_ROOT']);
  // echo "<br><br>";
  // var_dump(getcwd());
  // echo "<br><br>";
  // $scanthis = $_SERVER['DOCUMENT_ROOT'];
  // $showresults = scandir($scanthis, 1);
  // var_dump($showresults);
  // echo "<br><br>";
  var_dump($_SERVER['HTTP_HOST']);
  echo "<br><br>";
  var_dump(__DIR__ . '/docTitles');
  echo "<br><br>";
  var_dump(realpath(__DIR__ . "/../api"));
  echo "<br><br>";
  echo 'Current script owner: ' . get_current_user();
  echo "<br><br>";
  echo "subdomain = " . array_shift((explode('.', $_SERVER['HTTP_HOST'])));
  echo "<br><br>";
  $school = array_shift((explode('.', $_SERVER['HTTP_HOST'])));
  if( $school = "localhost:8008" )
  {
    echo "Nimefika";
  }else{
    echo "Sijafika bado";
  }
  echo "<br><br>";
  echo ("http://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke");

  echo "<br><br>";
  $guardians = "2,4,6,8";
  $guardiansInt = trim($guardians,'"');
  echo $guardiansInt;

  echo "<br><br>Today is " . date("m/d/Y") . "<br>";

  ?>

<form action="#" method="post">
  <select name="Color">
    <option value="Red">Red</option>
    <option value="Green">Green</option>
    <option value="Blue">Blue</option>
    <option value="Pink">Pink</option>
    <option value="Yellow">Yellow</option>
  </select>
  <select name="Shade">
    <option value="20">20%</option>
    <option value="40">40%</option>
    <option value="60">60%</option>
    <option value="80">80%</option>
    <option value="100">100%</option>
  </select>
  <input type="submit" name="submit" value="Get Selected Values" />
</form>
<?php
if(isset($_POST['submit'])){
$selected_val = $_POST['Color'];  // Storing Selected Value In Variable
$selected_val2 = $_POST['Shade'];
echo "You have selected :" .$selected_val . " With shade of ". $selected_val2 ."%";  // Displaying Selected Value
}
?>
</body>
</html>
