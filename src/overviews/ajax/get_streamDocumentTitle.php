<?php

include("db.php");

if(isset($_POST['c_id'])) {
	
	$params = $_POST['c_id'];
	$data = json_decode($params,true);
    $term = $data["term"];
    $class = $data["class"];
	$docTitle = pg_query($db,"SELECT class_name || ' / ' || term_name AS title FROM (
                            	SELECT (case
                            			WHEN entity = 11 THEN
                            				'CLASS 8'
                            			WHEN entity = 10 THEN
                            				'CLASS 7'
                            			WHEN entity = 9 THEN
                            				'CLASS 6'
                            			WHEN entity = 8 THEN
                            				'CLASS 5'
                            			WHEN entity = 7 THEN
                            				'CLASS 4'
                            		end) as class_name, term_name FROM (
                            		SELECT ($class) AS entity, (SELECT term_name FROM app.terms WHERE term_id = $term) AS term_name
                            	)ttle
                            ) AS title");
                                
	/* echo "class=" . $class . " exam=" . $exam . " term=" . $term; */
	while ($title = pg_fetch_assoc($docTitle)) { 
	    
	    /* We want to write the string to a txt file and have php read from there */
	    /* This is because dataTables refresh the page on data load hence other JS variables are lost */
	    $fileName = __DIR__ . "/docTitles/" . array_shift((explode('.', $_SERVER['HTTP_HOST']))) . "_" . $class . "_" . $term . "_streamDocTitle.txt";
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