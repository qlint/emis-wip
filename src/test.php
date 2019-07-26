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

  var_dump($_SERVER['DOCUMENT_ROOT']);
  echo "<br><br>";
  var_dump(getcwd());
  echo "<br><br>";
  $scanthis = $_SERVER['DOCUMENT_ROOT'];
  $showresults = scandir($scanthis, 1);
  var_dump($showresults);
  echo "<br><br>";
  var_dump($_SERVER['HTTP_HOST']);
  echo "<br><br>";
  var_dump(__DIR__);
  echo "<br><br>";
  var_dump(realpath(__DIR__ . "/../api"));
  echo "<br><br>";
  echo 'Current script owner: ' . get_current_user();
  echo "<br><br>";
  echo "subdomain = " . array_shift((explode('.', $_SERVER['HTTP_HOST'])));
  echo "<br><br>";
  $school = array_shift((explode('.', $_SERVER['HTTP_HOST'])));
  if( $school == "kingsinternational" )
  {
    echo "This is ". $school;
  }else{
    echo "The subdomain is not " . $school;
  }
  echo "<br><br>";
  echo ("http://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke");

  echo "<br><br>";
  $guardians = "2,4,6,8";
  $guardiansInt = trim($guardians,'"');
  echo $guardiansInt;

  echo "<br><br>Today is " . date("m/d/Y") . "<br>";

  echo "Disabled functions test :::" . "<br>";
  var_dump(ini_get('safe_mode'));
  echo "<br><br>";

  echo "Disabled functions p.2 :::" . "<br>";
  var_dump(explode(',',ini_get('disable_functions')));
  echo "<br><br>";

  echo "Testing console :::" . "<br>";
  echo exec('ping eduweb.co.ke');
  echo "<br><br>";

  $json_string = json_encode('{"response":{"version": "0.1","features":{"forecast": "rain"}}}');
  var_dump(json_decode($json_string))."<br>";
  $parsed_json = json_decode($json_string);
  $version  = $parsed_json->{'response'}->{'version'};
  $forecast    = $parsed_json->{'response'}->{'features'}->{'forecast'};
  echo $version.'<br>';
  echo $forecast.'<br>';
  echo "<br><br>";

  $postId = array(10,25,30,45,50);
  $singlePostId = '{' . implode(',', $postId) . '}';
  echo $singlePostId;
  echo "<br><br>";

  $readUrl = $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
  $lastSegmentOfUrl = explode('/', $readUrl);
  echo "Last part of current url is " . end($lastSegmentOfUrl);
  echo "<br><br>";

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
