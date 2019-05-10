<?php
header('Access-Control-Allow-Origin: *');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
	<link rel="icon" type="image/png" href="components/overviewFiles/images/icons/favicon.ico"/>
  <title>Tables Mod</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>

<?php

// $getDbname = 'eduweb_mis';
$getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
$db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=pg_edu@8947");
$db2 = pg_connect("host=localhost port=5432 dbname=eduweb_mis user=postgres password=pg_edu@8947");


/* -------------------------execute the below ------------------------- */
// $table1 = pg_query($db,"CREATE EXTENSION dblink;");

// $table1 = pg_query($db,"UPDATE app.users
// SET password = 'benny2018admin' WHERE username = 'appleton'");

// $table1 = pg_query($db,"UPDATE app.students SET pick_up_drop_off_individual_img = 'dev_363_blackfemalestudent.jpg' WHERE admission_number = 'JOSNAH35'");

// $table1 = pg_query($db,"ALTER TABLE app.buses
//   ADD CONSTRAINT \"FK_route_id\" FOREIGN KEY (route_id)
//       REFERENCES app.transport_routes (transport_id) MATCH FULL
//       ON UPDATE NO ACTION ON DELETE NO ACTION;");

/*****************************************************************/
// $table1 = pg_query($db,"SELECT student_image FROM app.students WHERE student_image IS NOT NULL");
//
// $zip = new ZipArchive();
// $zip_name = /* array_shift((explode('.', $_SERVER['HTTP_HOST']))) .*/"test_student-images_on_".time().".zip"; // Zip name
// echo "Our zip will be called " . $zip_name;
// echo "<br>";
// $zip->open($zip_name,  ZipArchive::CREATE);
// if ($zip->open($zip_name) !== TRUE) {
//     die ("Could not open ". $zip_name ." archive");
// }
//
// $row = pg_fetch_assoc($table1);
// foreach ($row as $column => $val) {
//     // echo $column . ' - ' . $val . "\n";
//     echo $path = "assets/students/".$val;
//     if(file_exists($path)){
//       echo "<br><br>"."File exists in the given path". "<br>";
//       echo "Attempting to add file to zip ---";
//       $zip->addFromString(basename($path),  file_get_contents($path));
//     }
//     else{
//      echo"file does not exist";
//     }
// }
//
// $zip->close();
/***********************************************************/

// $todaysDate = date("m/d/Y");
// $table1 = pg_query($db,"UPDATE app.students
// SET dob = '$todaysDate' WHERE dob IS NULL");

// $table1 = pg_query($db,"UPDATE app.students SET admission_number = 'earlydays255' WHERE student_id = 255");

// $table1 = pg_query($db,"INSERT INTO app.users(username, password, active, first_name, last_name, email, user_type, creation_date)
// VALUES('mathew', 'mathew', TRUE, 'mathew', 'mathew', 'mathew@mail.com', 'SYS_ADMIN', now());");

// $table1 = pg_query($db2,"INSERT INTO staff(
//             staff_id, first_name, middle_name, last_name, telephone, email,
//             emp_id, user_id, user_type, subdomain, usernm, password, active,
//             creation_date)
//     VALUES ((SELECT max(staff_id)+1 FROM staff), 'Stanley', 'Mudogo', 'Eboya', '0720902703', 'stanleyeboya@gmail.com',
//             201, 39, 'TEACHER', 'kingsinternational', 'stanleyeboya@gmail.com', 'stanley', TRUE,
//             now());");

// $subdom = array_shift((explode('.', $_SERVER['HTTP_HOST'])));
// $table1 = pg_query($db,"INSERT INTO app.settings(name, value) VALUES('subdomain', '$subdom');");

// $table1 = pg_query($db,"UPDATE app.students SET gender = 'M' WHERE gender = 'B';
// UPDATE app.students SET gender = 'F' WHERE gender = 'G';
// ");

// $table1 = pg_query($db,"UPDATE parents SET password = username
// WHERE parent_id IN (SELECT parent_id FROM parent_students WHERE subdomain = 'benedicta');
// ");

// $table1 = pg_query($db,"INSERT INTO app.subjects(subject_name, class_cat_id, active, sort_order, use_for_grading)
// VALUES('Computer', 10, 'TRUE', 12, 'TRUE')");

// $table1 = pg_query($db,"SELECT device_user_ids, message, sent FROM notifications WHERE subdomain = 'dev' ORDER BY notification_id DESC LIMIT 5");
// while ($row = pg_fetch_assoc($table1)) {
//   echo  $row['device_user_ids'] . "<<< +++ >>>" . $row['message'] . "<<< +++ >>>" . $row['sent'] . "<br>" ;
// }

// $table1 = pg_query($db,"ALTER TABLE app.schoolbus_history
// ADD COLUMN student_id integer;");

// $table1 = pg_query($db,"ALTER TABLE app.invoices
// ADD COLUMN custom_invoice_no character varying;ALTER TABLE app.payments
// ADD COLUMN custom_receipt_no character varying;");

// $table1 = pg_query($db,"ALTER TABLE app.grading
// ADD COLUMN comment character varying,
// ADD COLUMN kiswahili_comment character varying,
// ADD COLUMN principal_comment character varying;");

// $table1 = pg_query($db,"CREATE TABLE app.grading2
// (
//   grade2_id serial NOT NULL,
//   grade2 character varying NOT NULL,
//   min_mark integer NOT NULL,
//   max_mark integer NOT NULL,
//   comment character varying,
//   kiswahili_comment character varying,
//   CONSTRAINT \"PK_grade2_id\" PRIMARY KEY (grade2_id ),
//   CONSTRAINT grading_unique_grade2_contraint UNIQUE (grade2 )
// )
// WITH (
//   OIDS=FALSE
// );
// ALTER TABLE app.grading2
//   OWNER TO postgres;");

// $table1 = pg_query($db,"ALTER TABLE app.communications
// ADD COLUMN guardians character varying, ADD COLUMN students character varying;");

// $table1 = pg_query($db,"ALTER TABLE app.class_cats
// ADD COLUMN entity_id integer;");

// $table1 = pg_query($db,"UPDATE app.users
// SET username = subquery.email || '' || users.user_id
// FROM ( SELECT user_id, email FROM app.users WHERE user_type = 'SYS_ADMIN' ) AS subquery
// WHERE users.user_id = subquery.user_id;");

// $table1 = pg_query($db,"UPDATE app.students
// SET student_image = subquery.student_name || '.jpg'
// FROM ( SELECT admission_number, students.first_name || ' ' || students.middle_name AS student_name FROM app.students WHERE current_class IN (1) AND middle_name IS NOT NULL AND octet_length(middle_name) > 1 ) AS subquery
// WHERE students.admission_number = subquery.admission_number;");

// $table1 = pg_query($db,"UPDATE app.students
// SET student_image = subquery.student_name || '.jpg'
// FROM ( SELECT admission_number, students.first_name || ' ' || students.last_name AS student_name FROM app.students WHERE current_class IN (17, 18, 19, 20) ) AS subquery
// WHERE students.admission_number = subquery.admission_number;");

// $table1 = pg_query($db,"UPDATE app.guardians
// SET telephone = NULL
// FROM ( SELECT s.student_id, g.guardian_id FROM app.students s
// INNER JOIN app.student_guardians USING (student_id)
// INNER JOIN app.guardians g USING (guardian_id)
// WHERE s.active IS FALSE ) AS subquery
// WHERE guardians.guardian_id = subquery.guardian_id;");

// $table1 = pg_query($db,"INSERT INTO app.student_fee_items (student_id, fee_item_id, amount, payment_method, active)
// (
// 	SELECT DISTINCT ON (s.student_id) s.student_id, 8 AS fee_item_id, 52000 as amount, 'Installments' as payment_method, TRUE as active
// 	FROM app.students s
// 	INNER JOIN app.classes c ON s.current_class = c.class_id
// 	INNER JOIN app.fee_items fi ON c.class_cat_id = ANY(fi.class_cats_restriction)
// 	WHERE s.student_id NOT IN (SELECT student_id FROM app.student_fee_items WHERE fee_item_id = 8)
// );");

// $table1 = pg_query($db,"INSERT INTO app.student_fee_items (student_id, fee_item_id, amount, payment_method, active)
// (
// 	SELECT student_id, 4 AS fee_item_id, 38000 AS amount, 'Installments' AS payment_method, TRUE AS active FROM app.students s
// 	WHERE s.student_type = 'Boarder'
// );");

// $table1 = pg_query($db,"UPDATE app.student_fee_items
// SET active = FALSE
// FROM (
// 	SELECT sfi.student_id, sfi.fee_item_id, sfi.active
// 	FROM app.student_fee_items sfi
// 	WHERE student_id IN (
// 				SELECT DISTINCT ON (student_id) student_id FROM app.students s
// 				INNER JOIN app.classes c ON s.current_class = c.class_id
// 				INNER JOIN app.student_fee_items USING (student_id)
// 				WHERE fee_item_id IN (8,13)
// 				AND c.class_cat_id = 34
// 			)
// 	AND sfi.fee_item_id IN (8,13)
// ) AS subquery
// WHERE student_fee_items.student_id = subquery.student_id
// AND student_fee_items.fee_item_id = subquery.fee_item_id;");

// $table1 = pg_query($db,"UPDATE app.guardians
// SET id_number = ceil(random()*(112233445-9999999999)+9999999999)
// WHERE guardian_id <29;");

// $table1 = pg_query($db,"UPDATE app.grading AS g SET
//     points = c.points
// FROM (VALUES
//     ('A', 12),
//     ('A-', 11),
//     ('B+', 10),
//     ('B', 9),
//     ('B-', 8),
//     ('C+', 7),
//     ('C', 6),
//     ('C-', 5),
//     ('D+', 4),
//     ('D', 3),
//     ('D-', 2),
//     ('E', 1)
// ) AS c(grade, points)
// WHERE c.grade = g.grade");

// $table1 = pg_query($db,"DELETE FROM app.communication_emails WHERE (email_id) IN ( SELECT MAX(email_id) FROM app.communication_emails );
// DELETE FROM app.communication_sms WHERE (sms_id) IN ( SELECT MAX(sms_id) FROM app.communication_sms );
// DELETE FROM app.communications WHERE (com_id) IN ( SELECT MAX(com_id) FROM app.communications );");

// $table1 = pg_query($db,"alter sequence app.guardians_guardian_id_seq restart with 850;
// alter sequence app.students_student_id_seq restart with 850;
// alter sequence app.student_guardians_student_guardian_id_seq restart with 1200;");

// $table1 = pg_query($db,"SELECT username, password FROM app.users");
// while ($row = pg_fetch_assoc($table1)) {
//   echo $row['username'] . "<<< +++ >>>" .  $row['password'] . "<br><br>" ;
// }

// $table1 = pg_query($db,"SELECT sum(tot_inv_pymt) as total_paid FROM (
// 	SELECT DISTINCT payment_id, tot_inv_pymt FROM (
// 		SELECT payment_id, payments.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name, class_id, class_cat_id,
// 			payments.amount as tot_inv_pymt, payment_date, payments.payment_method, fee_items.fee_item, invoices.inv_id, payment_inv_items.amount as amount_paid, invoice_line_items.amount as default_amount,
// 			students.active as status, replacement_payment, reversed
// 		FROM app.payments
// 		INNER JOIN app.payment_inv_items using (payment_id)
// 		INNER JOIN app.invoices on payment_inv_items.inv_id  = invoices.inv_id and canceled = false
// 		INNER JOIN app.invoice_line_items using (inv_item_id)
// 		INNER JOIN app.student_fee_items using (student_fee_item_id)
// 		INNER JOIN app.fee_items using (fee_item_id)
// 		INNER JOIN app.students ON payments.student_id = students.student_id
// 		INNER JOIN app.classes ON students.current_class = classes.class_id
// 		WHERE payment_date between (SELECT start_date FROM app.terms WHERE CURRENT_DATE between start_date and end_date) and (SELECT end_date FROM app.terms WHERE CURRENT_DATE between start_date and end_date)
// 		AND payments.payment_method != 'Credit'
// 		AND payment_id = payments.payment_id
// 		AND reversed = 'false'
// 		AND students.active is true
// 		ORDER BY student_id ASC, payment_date ASC
// 	)foo
// )foo2");
// while ($row = pg_fetch_assoc($table1)) {
//   echo "Total Paid = " . $row['total_paid']/* . "<<< +++ >>>"  . $row['class_name']*/ . "<br>" ;
// }

// $table1 = pg_query($db,"SELECT student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, class_name, report_cards.creation_date
// FROM app.report_cards
// INNER JOIN app.students using(student_id)
// INNER JOIN app.classes using(class_id)
// ORDER BY report_card_id DESC LIMIT 35");
// while ($row = pg_fetch_assoc($table1)) {
//   echo $row['student_id'] . "<<< +++ >>>"  . $row['student_name'] . "<<< +++ >>>"  . $row['class_name'] . "<<< +++ >>>"  . $row['creation_date'] . "<br>" ;
// }

// $table1 = pg_query($db,"SELECT first_name || ' ' || last_name AS student_name, admission_number, class_name
// FROM app.students
// INNER JOIN app.classes ON students.current_class = classes.class_id
// WHERE students.active IS TRUE
// AND students.student_image IS NULL
// ORDER BY class_name ASC, student_name ASC");
// while ($row = pg_fetch_assoc($table1)) {
//   echo $row['student_name'] . " -- "  . $row['class_name'] . '<br>';
// }

// $queryInTextFile = file_get_contents('tables.txt');
// echo "$queryInTextFile";
// $table1 = pg_query($db,"$queryInTextFile");

// $table1 = pg_query($db,"INSERT INTO app.communication_feedback (subject, message, message_from, student_id, guardian_id) VALUES ('Fee Balance', 'Last time I checked, I never had any balance. What is going on?', 'Daniel Kisiva', 513, 148);
// INSERT INTO app.communication_feedback (subject, message, message_from, student_id, guardian_id) VALUES ('PTA Meeting', 'I shall not be able to make it for the PTA as I have to travel for business during that week. I will however drop by as soon as I am back', 'Ann Muchau', 511, 146);
// INSERT INTO app.communication_feedback (subject, message, message_from, student_id, guardian_id) VALUES ('App Inquiry', 'Hello, I am new to the mobile app. How do I see the next term fees? Thank you.', 'Bernard Karenju', 509, 144);
// INSERT INTO app.communication_feedback (subject, message, message_from, student_id, guardian_id) VALUES ('Discipline', 'My sons behaviour and academics seems to be detoriating with every passing day. Are you not teaching him anything in school? Why do I pay fees if I cannot see the value of it? Just look at his report card! Nini mbaya?', 'Mathew Ndote', 513, 148);
// ");

// $table1 = pg_query($db,"SELECT report_data FROM app.report_cards ORDER BY report_card_id DESC LIMIT 5
// ");
// while ($row = pg_fetch_assoc($table1)) {
//   echo json_encode(pg_fetch_assoc($table1));
// }

// $table1 = pg_query($db,"SELECT username, password, user_type FROM app.users
// ");
// while ($row = pg_fetch_assoc($table1)) {
//   echo json_encode(pg_fetch_assoc($table1));
// }

$table1 = pg_query($db2,"SELECT * FROM staff");
while ($row = pg_fetch_assoc($table1)) {
  echo json_encode(pg_fetch_assoc($table1));
}

// $table1 = pg_query($db,"SELECT * FROM app.users WHERE first_name = 'Stanley'");
// while ($row = pg_fetch_assoc($table1)) {
//   echo json_encode(pg_fetch_assoc($table1));
// }

// $value = null;
// $guardian = pg_query($db2,"SELECT guardian_id FROM parent_students WHERE parent_id = 1663");
// $row2 = pg_fetch_assoc($guardian);
// foreach ($row2 as $column => $value) {
//     echo 'Our data is ' . $column . ' - ' . $value . "\n";
// }
// echo "Reading externally, value = " . $value;
// $table1 = pg_query($db,"SELECT * FROM app.guardians WHERE guardian_id = $value");
// $row = pg_fetch_assoc($table1);
// foreach ($row as $column => $val) {
//     echo $column . ' - ' . $val . "\n";
// }

// $table1 = pg_query($db,"ALTER TABLE app.communication_feedback DROP CONSTRAINT \"FK_com_guardian_id\"");

// $table1 = pg_query($db,"SELECT * FROM app.guardians");
// $row = pg_fetch_assoc($table1);
// foreach ($row as $column => $value) {
//     echo $column . ' ' . $value . "\n";
// }

// $table1 = pg_query($db,"SELECT grade, max_mark || '-' || min_mark as mark FROM app.grading");
// while ($row = pg_fetch_assoc($table1)) {
//   echo json_encode(pg_fetch_assoc($table1));
// }

// $table1 = pg_query($db,"INSERT INTO app.settings(name, value) VALUES('Bank Name 2',null);
// INSERT INTO app.settings(name, value) VALUES('Bank Branch 2',null);
// INSERT INTO app.settings(name, value) VALUES('Account Name 2',null);
// INSERT INTO app.settings(name, value) VALUES('Account Number 2',null);
// INSERT INTO app.settings(name, value) VALUES('Mpesa Details',null);
// ");

// $table1 = pg_query($db,"INSERT INTO app.communication_audience (audience_id,audience) VALUES (8,'Drivers');
// INSERT INTO app.communication_audience (audience_id,audience) VALUES (9,'Employee');
// ");

/*THIS RUNS LARGE COMPLEX QUERIES. HAVE THE QUERY IN A TEXT FILE*/
// $stdentsTbl = file_get_contents('newdb2.txt');
// echo "$stdentsTbl";
// $table1 = pg_query($db,"$stdentsTbl");

// $table1 = pg_query($db,"CREATE DATABASE eduweb_dev
//   WITH OWNER = postgres
//        ENCODING = 'UTF8'
//        TABLESPACE = pg_default
//        LC_COLLATE = 'English_United States.1252'
//        LC_CTYPE = 'English_United States.1252'
//        CONNECTION LIMIT = -1");

/* THIS DROPS A TABLE WITH FOREIGN KEYS*/
// $table1 = pg_query($db,"DROP TABLE if exists app.guardians cascade;");
// $table1 = pg_query($db,"DROP TABLE if exists app.student_guardians cascade;");
// $table1 = pg_query($db,"DROP TABLE if exists app.students cascade;");

/* THIS DELETES SEQUENTIALLY WHERE THERE'S FOREIGN KEYS */
// $table1 = pg_query($db,"DELETE FROM app.payment_inv_items;");
// $table1 = pg_query($db,"DELETE FROM app.invoice_line_items;");
// $table1 = pg_query($db,"DELETE FROM app.invoices;");

// $class = 14; $term = 5; $exam = 27;
// $examResults = pg_query($db,"SELECT app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name,
//                                                 					classes.class_id,subject_name,
//                                                 					coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name,
//                                                 					exam_type, exam_marks.student_id, mark, grade_weight, subjects.sort_order
//                                                 				FROM app.exam_marks
//                                                 				INNER JOIN app.class_subject_exams
//                                                 				INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
//                                                 				INNER JOIN app.class_subjects
//                                                 				INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
//                                                 				INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
//                                                 							ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
//                                                 							ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
//                                                 				INNER JOIN app.students ON exam_marks.student_id = students.student_id
//                                                 				WHERE class_subjects.class_id = $class AND term_id = $term
//                                                 				AND class_subject_exams.exam_type_id = $exam AND subjects.use_for_grading is true
//                                                 				AND students.active is true
//                                                 				WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)',
//                                                 		array['gender','student_name'], array['parent_subject_name','subject_name'], '#.mark', null);
//                                                 SELECT *, ( SELECT rank FROM (
//                                                 			SELECT student_id, student_name, total_mark, rank() over w as rank
//                                                 			FROM (
//                                                 				SELECT exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
//                                                 					coalesce(sum(CASE WHEN subjects.parent_subject_id IS NULL THEN mark END),0) AS total_mark
//                                                 				FROM app.exam_marks
//                                                 				INNER JOIN app.class_subject_exams
//                                                 				INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
//                                                 				INNER JOIN app.class_subjects
//                                                 				INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active IS TRUE AND subjects.use_for_grading IS TRUE
//                                                 							ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
//                                                 							ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
//                                                 				INNER JOIN app.students ON exam_marks.student_id = students.student_id
//                                                 				WHERE class_subjects.class_id = $class AND term_id = $term AND class_subject_exams.exam_type_id = $exam
//                                                 				AND students.active IS TRUE
//                                                 				GROUP BY exam_marks.student_id, students.first_name, students.middle_name, students.last_name
//                                                 			) a
//                                                 			WINDOW w AS (ORDER BY total_mark DESC)
//                                                 		) q
//                                                 		WHERE student_name = _exam_marks.student_name
//                                                 	) AS rank
//                                                 FROM _exam_marks ORDER BY rank;");
//
//
//     	/* echo "<tr class='row100 body'>"; */
//     	$row = pg_fetch_assoc($examResults);
//
//     	foreach ($row as $column => $value) {
//     	    $results = $column . '-' . $value . "\n";;
//     	     print_r($results);
//       }

// $num = (int)'123';
// echo gettype($num);


// $communication = pg_query($db,"SELECT max(com_id) from app.communication_sms");
//
//
//     	$row = pg_fetch_assoc($communication);
//
//     	foreach ($row as $column => $value) {
//     	    $results = $column . '-' . $value . "\n";;
//     	     print_r($results);
//       }
?>


</body>
</html>
