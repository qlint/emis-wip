<?php

include("db.php");

if(isset($_POST['c_id'])) {
	
	$class = $_POST['c_id'];
	$examTypes = pg_query($db,"SELECT exam_type_id, exam_type FROM app.exam_types 
                                WHERE class_cat_id = (SELECT class_cat_id FROM app.classes WHERE class_id = " . pg_escape_string($class) . ") 
                                ORDER BY sort_order DESC, exam_type_id DESC");
                                
    $className = pg_query($db,"SELECT class_name FROM app.classes WHERE class_id = " . pg_escape_string($class) . "");
                                
    /* We want to write the string to a txt file and have php read from there */
	/* This is because dataTables refresh the page on data load hence other JS variables are lost */
	$fileName = __DIR__ . "/docTitles/" . array_shift((explode('.', $_SERVER['HTTP_HOST']))) . "_docTitle.txt";
	$myFile = fopen($fileName, "w") or die("Unable to open file!");
	    
	/* This is a temporary header that will be overwritten on form submit */
	while ($clsNm = pg_fetch_assoc($className)) { fwrite($myFile, $clsNm['class_name']); }
    fclose($myFile);
                                
	while ($exmType = pg_fetch_assoc($examTypes)) { echo "<option value='" . $exmType['exam_type_id'] . "'>" . $exmType['exam_type'] . "</option>"; }
	
} else {
	header('location: ./');
}
?>