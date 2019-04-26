<?php

include("db.php");

if(isset($_POST['c_id'])) {
	
	$params = $_POST['c_id'];
	$data = json_decode($params,true);
    $term = $data["term"];
    $class = $data["class"];
    $exam = $data["exam"];
	$docTitle = pg_query($db,"SELECT class_name || ' / ' || term_name || ' / ' || exam_name AS title FROM (
                                	SELECT (SELECT class_name FROM app.classes WHERE class_id = $class) AS class_name,
                                		(SELECT exam_type FROM app.exam_types where exam_type_id = $exam) AS exam_name,
                                		(SELECT term_name FROM app.terms WHERE term_id = $term) AS term_name
                                ) AS title");
                                
	/* echo "class=" . $class . " exam=" . $exam . " term=" . $term; */
	while ($title = pg_fetch_assoc($docTitle)) { 
	    
	    /* We want to write the string to a txt file and have php read from there */
	    /* This is because dataTables refresh the page on data load hence other JS variables are lost */
	    $fileName = __DIR__ . "/docTitles/" . array_shift((explode('.', $_SERVER['HTTP_HOST']))) . "_docTitle.txt";
	    $myFile = fopen($fileName, "w") or die("Unable to open file!");
	    
	    /* $title is our string to be written */
        fwrite($myFile, $title['title']);
        fclose($myFile);
        
        /* This is just an ajax response */
	    echo $title['title']; 
	    
	}
	
} else {
	header('location: ./');
}
?>