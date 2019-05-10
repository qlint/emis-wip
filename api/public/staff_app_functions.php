<?php

$app->post('/staffLogin', function () use($app) {
  // Log staff in
  
  $allPostVars = $app->request->post();
  
  $username = $allPostVars['user_name'];
  $pwd = $allPostVars['user_pwd'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();
    
    $userCheckQry = $db->query("SELECT (CASE 
                                    		WHEN EXISTS (SELECT usernm FROM staff WHERE usernm = '$username') THEN 'proceed'
                                    		ELSE 'stop'
                                    	END) AS status");
    $userStatus = $userCheckQry->fetch(PDO::FETCH_OBJ);
    $userStatus = $userStatus->status;
    
    if($userStatus === "proceed"){
    
            $sth = $db->prepare("SELECT staff.staff_id, usernm, active, first_name, middle_name, last_name, email,
                          first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS staff_full_name, telephone, device_user_id, emp_id, user_id, user_type,
                          subdomain AS school
                        FROM staff
                        WHERE usernm= :username
                        AND password = :password
                        AND active is true");
            $sth->execute( array(':username' => $username, ':password' => $pwd) );
            $result = $sth->fetch(PDO::FETCH_OBJ);
        
            if($result) {
        
              $studentDetails = Array();
              $curSubDomain = '';
              $studentsBySchool = Array();
              
                // get individual student details
                // only get new db connection if different subdomain
                if( $curSubDomain != $result->school )
                {
                  if( $db !== null ) $db = null;
                  $db = setDBConnection($result->school);
                }
                
                // get the teacher's students
                if($result->user_type == "TEACHER"){
                    
                    $myStudents = Array();
                    
                    if( $db !== null ) $db = null;
                    $db = setDBConnection($result->school);
                    
                    $sth3 = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                                          classes.teacher_id as class_teacher_id, (SELECT value FROM app.settings WHERE name = 'School Name') as school_name
                                        FROM app.students
                                        INNER JOIN app.classes
                                        INNER JOIN app.class_subjects
                                        INNER JOIN app.subjects
                                        	ON class_subjects.subject_id = subjects.subject_id
                                        	ON classes.class_id = class_subjects.class_id
                                        	ON students.current_class = classes.class_id
                                        WHERE students.active = true
                                        AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
                                        GROUP BY students.student_id, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
                                        ORDER BY first_name, middle_name, last_name");
                    $sth3->execute(array(':teacherId' => $result->emp_id));
                    // $details = $sth3->fetchAll(PDO::FETCH_OBJ);
                    $myStudents[$result->school] = $sth3->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth4 = $db->prepare("SELECT class_subject_id, subjects.subject_id, subject_name, subjects.teacher_id, subjects.active, subjects.class_cat_id, class_cat_name, use_for_grading,
                                        	first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, class_subjects.class_id, class_name,
                                        	(SELECT count(*) FROM app.class_subjects 
                                        	INNER JOIN app.classes 
                                        	INNER JOIN app.students ON students.current_class = classes.class_id ON class_subjects.class_id = classes.class_id
                                        	WHERE subject_id = subjects.subject_id) as num_students
                                        FROM app.subjects 
                                        INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
                                        INNER JOIN app.employees ON subjects.teacher_id = employees.emp_id
                                        INNER JOIN app.class_subjects 
                                        INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                        			ON subjects.subject_id = class_subjects.subject_id
                                        WHERE subjects.teacher_id = :teacherId
                                        AND subjects.active = true
                                        ORDER BY subjects.sort_order");
                    $sth4->execute(array(':teacherId' => $result->emp_id));
                    $subjectsIteachOnly[$result->school] = $sth4->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth7 = $db->prepare("SELECT class_subject_id, class_name, subject_name, classes.class_id, subjects.subject_id, use_for_grading,
                                        	classes.sort_order AS class_order, subjects.sort_order AS subject_order
                                        FROM app.class_subjects
                                        INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                        INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
                                        WHERE classes.teacher_id = :teacherId
                                        AND classes.active = true AND subjects.active = true
                                        UNION
                                        SELECT class_subject_id, class_name, subject_name, classes.class_id, subjects.subject_id, use_for_grading,
                                        	classes.sort_order AS class_order, subjects.sort_order AS subject_order
                                        FROM app.subjects
                                        INNER JOIN app.class_subjects
                                        INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                        			ON subjects.subject_id = class_subjects.subject_id
                                        WHERE subjects.teacher_id = :teacherId
                                        AND subjects.active = true
                                        ORDER BY class_order, subject_order");
                    $sth7->execute(array(':teacherId' => $result->emp_id));
                    $subjectsWhereImAclassTeacher = $sth7->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth8 = $db->prepare("SELECT guardians.guardian_id, guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name AS parent_full_name,
                                        	email, telephone, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                        	students.student_id, students.current_class, class_name, relationship
                                        FROM app.classes
                                        INNER JOIN app.students
                                        INNER JOIN app.student_guardians
                                        INNER JOIN app.guardians ON student_guardians.guardian_id = guardians.guardian_id
                                        ON students.student_id = student_guardians.student_id AND student_guardians.active is true
                                        ON classes.class_id = students.current_class AND students.active is true
                                        WHERE classes.active is true
                                        AND classes.teacher_id = :teacherId
                                        ORDER BY guardians.first_name, guardians.middle_name, guardians.last_name");
                    $sth8->execute(array(':teacherId' => $result->emp_id));
                    $myStudentsParents = $sth8->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth9 = $db->prepare("SELECT  emp_id, emp_cat_id, dept_id, emp_number, id_number, gender, first_name,
            									middle_name, last_name, initials, dob, country, active, telephone, email, joined_date,
            									job_title, qualifications, experience, additional_info, emp_image
            							 FROM app.employees 
            							 WHERE emp_id = :teacherId");
                    $sth9->execute(array(':teacherId' => $result->emp_id));
                    $myProfile = $sth9->fetch(PDO::FETCH_OBJ);
                    
                    $sth10 = $db->prepare("SELECT employees.*,
                									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as employee_name,
                									emp_cat_name, dept_name,
                									(select array_agg(class_name) from app.classes where teacher_id = employees.emp_id and classes.active is true) as classes,
                									(select array_agg(subject_name || ' (' || class_cat_name || ')') from app.subjects inner join app.class_cats using (class_cat_id) where teacher_id = employees.emp_id and subjects.active is true) as subjects,
                									username, user_type, users.active as login_active
                							 FROM app.employees
                							 INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id AND employee_cats.active is true
                							 INNER JOIN app.departments ON employees.dept_id = departments.dept_id AND departments.active is TRUE
                							 LEFT JOIN app.users ON employees.login_id = users.user_id
                							 WHERE emp_id = :teacherId");
                    $sth10->execute(array(':teacherId' => $result->emp_id));
                    $mySchoolDetails = $sth10->fetch(PDO::FETCH_OBJ);
                    
                    $sth11 = $db->prepare("SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
							round(total_mark/denominator) as total_mark, 
							round(total_grade_weight/denominator) as total_grade_weight, 
							rank, percentage, 
							(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
							position_out_of
							FROM (
							SELECT
								 student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_id
									  ,class_name
									  ,total_mark    
									  ,total_grade_weight
									  ,round((total_grade_weight/500)) as denominator
									  ,round((total_mark/total_grade_weight)*100) as percentage
									  ,dense_rank() over w as rank
									  ,position_out_of
							FROM (
								SELECT    
									 exam_marks.student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_subjects.class_id
									  ,class_name
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										mark
										end),0) as total_mark
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										grade_weight
									   end),0) as total_grade_weight
									  ,(select count(*) from app.students where active is true and current_class = students.current_class) as position_out_of
								FROM app.exam_marks
								INNER JOIN app.students
								ON exam_marks.student_id = students.student_id
								INNER JOIN app.class_subject_exams 
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id AND classes.active is true 
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE term_id = (select term_id from app.current_term)
								AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId)
								AND students.active is true
								GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name
					) a
								WINDOW w AS (PARTITION BY class_id ORDER BY class_id desc, total_mark desc)
							 ) q
							 WHERE rank < 4");
                    $sth11->execute(array(':teacherId' => $result->emp_id));
                    $myTopStudents = $sth11->fetchAll(PDO::FETCH_OBJ);
            
                }
              
        
              // get news sent out by the school
              $news = Array();
            
                if( $db !== null ) $db = null;
                $db = setDBConnection($result->school);
        
                $sth5 = $db->prepare("SELECT
                            com_id, com_date, communications.creation_date, com_type, subject, message, send_as_email, send_as_sms,
                            employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                            audience, attachment, reply_to,
                            students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
                            guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
                            communications.guardian_id, communications.student_id, classes.class_name, post_status,
                            sent, sent_date, message_from,
                            case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method
                          FROM app.communications
                          LEFT JOIN app.students ON communications.student_id = students.student_id
                          LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
                          LEFT JOIN app.classes ON communications.class_id = classes.class_id
                          INNER JOIN app.employees ON communications.message_from = employees.emp_id
                          INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
                          INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
                          INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
                          WHERE communications.sent IS TRUE
                          AND communications.post_status_id = 1
                          ORDER BY creation_date desc");
        
                $sth5->execute(array());
                $news[$result->school] = $sth5->fetchAll(PDO::FETCH_OBJ);
        
        
              // get sent messages by student's parent
              $feedback = Array();
              
                if( $db !== null ) $db = null;
                $db = setDBConnection($result->school);
        
                $sth6 = $db->prepare("SELECT cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                        cf.message_from as posted_by,
                        s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                        g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                        c.class_name
                    FROM app.communication_feedback cf
                    LEFT JOIN app.students s USING (student_id)
                    LEFT JOIN app.guardians g USING (guardian_id)
                    LEFT JOIN app.classes c USING (class_id)
                    WHERE s.active IS TRUE
                    GROUP BY cf.com_feedback_id, subject, message, student_name, parent_full_name, class_name, opened
                    ORDER BY post_id DESC");
        
                $sth6->execute(array());
                $feedback[$result->school] = $sth6->fetchAll(PDO::FETCH_OBJ);
        
        
              $result->myStudents = $myStudents;
              $result->subjectsIteachOnly = $subjectsIteachOnly;
              $result->subjectsWhereImAclassTeacher = $subjectsWhereImAclassTeacher;
              $result->myStudentsParents = $myStudentsParents;
              $result->myProfile = $myProfile;
              $result->mySchoolDetails = $mySchoolDetails;
              $result->myTopStudents = $myTopStudents;
              $result->news = $news;
              $result->feedback = $feedback;
        
              $app->response->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              $db = null;
        
              echo json_encode(array('response' => 'success', 'data' => $result ));
        
            } else {
            
                $app->response->setStatus(200);
                $app->response()->headers->set('Content-Type', 'application/json');
                echo json_encode(array("response" => "error", "code" => 3, "data" => 'The password you have entered is incorrect. Please check the spelling and / or capitalization.'));
                
            }
    
    } else { 
        if($userStatus === "stop"){ 
        
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array("response" => "error", "code" => 2, "data" => 'The username you entered does not exist. Please confirm and try again.'));
            
        } else {
        
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array("response" => "error", "code" => 3, "data" => 'The password you have entered is incorrect. Please check the spelling and / or capitalization.'));
            
        }
    }
  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
  
});

$app->get('/getStaffData/:school/:user_id', function ($school, $userId) {
    // Get published blog posts associated with student for this current school year

  $app = \Slim\Slim::getInstance();

    try
    {
    
    $db = getLoginDB();
    
    $userCheckQry = $db->query("SELECT (CASE 
                                    		WHEN EXISTS (SELECT usernm FROM staff WHERE user_id = '$userId' AND subdomain = '$school') THEN 'proceed'
                                    		ELSE 'stop'
                                    	END) AS status");
    $userStatus = $userCheckQry->fetch(PDO::FETCH_OBJ);
    $userStatus = $userStatus->status;
    
    if($userStatus === "proceed"){
    
            $sth = $db->prepare("SELECT staff.staff_id, usernm, active, first_name, middle_name, last_name, email,
                          first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS staff_full_name, telephone, device_user_id, emp_id, user_id, user_type,
                          subdomain AS school
                        FROM staff
                        WHERE user_id = :userId AND subdomain = :school
                        AND active is true");
            $sth->execute( array(':userId' => $userId, ':school' => $school) );
            $result = $sth->fetch(PDO::FETCH_OBJ);
        
            if($result) {
        
              $studentDetails = Array();
              $curSubDomain = '';
              $studentsBySchool = Array();
              
                // get individual student details
                // only get new db connection if different subdomain
                if( $curSubDomain != $result->school )
                {
                  if( $db !== null ) $db = null;
                  $db = setDBConnection($result->school);
                }
                
                // get the teacher's students
                if($result->user_type == "TEACHER"){
                    
                    $myStudents = Array();
                    
                    if( $db !== null ) $db = null;
                    $db = setDBConnection($result->school);
                    
                    $sth3 = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                                          classes.teacher_id as class_teacher_id, (SELECT value FROM app.settings WHERE name = 'School Name') as school_name
                                        FROM app.students
                                        INNER JOIN app.classes
                                        INNER JOIN app.class_subjects
                                        INNER JOIN app.subjects
                                        	ON class_subjects.subject_id = subjects.subject_id
                                        	ON classes.class_id = class_subjects.class_id
                                        	ON students.current_class = classes.class_id
                                        WHERE students.active = true
                                        AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
                                        GROUP BY students.student_id, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
                                        ORDER BY first_name, middle_name, last_name");
                    $sth3->execute(array(':teacherId' => $result->emp_id));
                    // $details = $sth3->fetchAll(PDO::FETCH_OBJ);
                    $myStudents[$result->school] = $sth3->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth4 = $db->prepare("SELECT class_subject_id, subjects.subject_id, subject_name, subjects.teacher_id, subjects.active, subjects.class_cat_id, class_cat_name, use_for_grading,
                                        	first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, class_subjects.class_id, class_name,
                                        	(SELECT count(*) FROM app.class_subjects 
                                        	INNER JOIN app.classes 
                                        	INNER JOIN app.students ON students.current_class = classes.class_id ON class_subjects.class_id = classes.class_id
                                        	WHERE subject_id = subjects.subject_id) as num_students
                                        FROM app.subjects 
                                        INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
                                        INNER JOIN app.employees ON subjects.teacher_id = employees.emp_id
                                        INNER JOIN app.class_subjects 
                                        INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                        			ON subjects.subject_id = class_subjects.subject_id
                                        WHERE subjects.teacher_id = :teacherId
                                        AND subjects.active = true
                                        ORDER BY subjects.sort_order");
                    $sth4->execute(array(':teacherId' => $result->emp_id));
                    $subjectsIteachOnly[$result->school] = $sth4->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth7 = $db->prepare("SELECT class_subject_id, class_name, subject_name, classes.class_id, subjects.subject_id, use_for_grading,
                                        	classes.sort_order AS class_order, subjects.sort_order AS subject_order
                                        FROM app.class_subjects
                                        INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                        INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
                                        WHERE classes.teacher_id = :teacherId
                                        AND classes.active = true AND subjects.active = true
                                        UNION
                                        SELECT class_subject_id, class_name, subject_name, classes.class_id, subjects.subject_id, use_for_grading,
                                        	classes.sort_order AS class_order, subjects.sort_order AS subject_order
                                        FROM app.subjects
                                        INNER JOIN app.class_subjects
                                        INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
                                        			ON subjects.subject_id = class_subjects.subject_id
                                        WHERE subjects.teacher_id = :teacherId
                                        AND subjects.active = true
                                        ORDER BY class_order, subject_order");
                    $sth7->execute(array(':teacherId' => $result->emp_id));
                    $subjectsWhereImAclassTeacher = $sth7->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth8 = $db->prepare("SELECT guardians.guardian_id, guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name AS parent_full_name,
                                        	email, telephone, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                                        	students.student_id, students.current_class, class_name, relationship
                                        FROM app.classes
                                        INNER JOIN app.students
                                        INNER JOIN app.student_guardians
                                        INNER JOIN app.guardians ON student_guardians.guardian_id = guardians.guardian_id
                                        ON students.student_id = student_guardians.student_id AND student_guardians.active is true
                                        ON classes.class_id = students.current_class AND students.active is true
                                        WHERE classes.active is true
                                        AND classes.teacher_id = :teacherId
                                        ORDER BY guardians.first_name, guardians.middle_name, guardians.last_name");
                    $sth8->execute(array(':teacherId' => $result->emp_id));
                    $myStudentsParents = $sth8->fetchAll(PDO::FETCH_OBJ);
                    
                    $sth9 = $db->prepare("SELECT  emp_id, emp_cat_id, dept_id, emp_number, id_number, gender, first_name,
            									middle_name, last_name, initials, dob, country, active, telephone, email, joined_date,
            									job_title, qualifications, experience, additional_info, emp_image
            							 FROM app.employees 
            							 WHERE emp_id = :teacherId");
                    $sth9->execute(array(':teacherId' => $result->emp_id));
                    $myProfile = $sth9->fetch(PDO::FETCH_OBJ);
                    
                    $sth10 = $db->prepare("SELECT employees.*,
                									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as employee_name,
                									emp_cat_name, dept_name,
                									(select array_agg(class_name) from app.classes where teacher_id = employees.emp_id and classes.active is true) as classes,
                									(select array_agg(subject_name || ' (' || class_cat_name || ')') from app.subjects inner join app.class_cats using (class_cat_id) where teacher_id = employees.emp_id and subjects.active is true) as subjects,
                									username, user_type, users.active as login_active
                							 FROM app.employees
                							 INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id AND employee_cats.active is true
                							 INNER JOIN app.departments ON employees.dept_id = departments.dept_id AND departments.active is TRUE
                							 LEFT JOIN app.users ON employees.login_id = users.user_id
                							 WHERE emp_id = :teacherId");
                    $sth10->execute(array(':teacherId' => $result->emp_id));
                    $mySchoolDetails = $sth10->fetch(PDO::FETCH_OBJ);
                    
                    $sth11 = $db->prepare("SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
							round(total_mark/denominator) as total_mark, 
							round(total_grade_weight/denominator) as total_grade_weight, 
							rank, percentage, 
							(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
							position_out_of
							FROM (
							SELECT
								 student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_id
									  ,class_name
									  ,total_mark    
									  ,total_grade_weight
									  ,round((total_grade_weight/500)) as denominator
									  ,round((total_mark/total_grade_weight)*100) as percentage
									  ,dense_rank() over w as rank
									  ,position_out_of
							FROM (
								SELECT    
									 exam_marks.student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_subjects.class_id
									  ,class_name
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										mark
										end),0) as total_mark
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										grade_weight
									   end),0) as total_grade_weight
									  ,(select count(*) from app.students where active is true and current_class = students.current_class) as position_out_of
								FROM app.exam_marks
								INNER JOIN app.students
								ON exam_marks.student_id = students.student_id
								INNER JOIN app.class_subject_exams 
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND use_for_grading is true
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id AND classes.active is true 
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE term_id = (select term_id from app.current_term)
								AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId)
								AND students.active is true
								GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name
					) a
								WINDOW w AS (PARTITION BY class_id ORDER BY class_id desc, total_mark desc)
							 ) q
							 WHERE rank < 4");
                    $sth11->execute(array(':teacherId' => $result->emp_id));
                    $myTopStudents = $sth11->fetchAll(PDO::FETCH_OBJ);
            
                }
              
        
              // get news sent out by the school
              $news = Array();
            
                if( $db !== null ) $db = null;
                $db = setDBConnection($result->school);
        
                $sth5 = $db->prepare("SELECT
                            com_id, com_date, communications.creation_date, com_type, subject, message, send_as_email, send_as_sms,
                            employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                            audience, attachment, reply_to,
                            students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
                            guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
                            communications.guardian_id, communications.student_id, classes.class_name, post_status,
                            sent, sent_date, message_from,
                            case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method
                          FROM app.communications
                          LEFT JOIN app.students ON communications.student_id = students.student_id
                          LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
                          LEFT JOIN app.classes ON communications.class_id = classes.class_id
                          INNER JOIN app.employees ON communications.message_from = employees.emp_id
                          INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
                          INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
                          INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
                          WHERE communications.sent IS TRUE
                          AND communications.post_status_id = 1
                          ORDER BY creation_date desc");
        
                $sth5->execute(array());
                $news[$result->school] = $sth5->fetchAll(PDO::FETCH_OBJ);
        
        
              // get sent messages by student's parent
              $feedback = Array();
              
                if( $db !== null ) $db = null;
                $db = setDBConnection($result->school);
        
                $sth6 = $db->prepare("SELECT cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                        cf.message_from as posted_by,
                        s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                        g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_full_name,
                        c.class_name
                    FROM app.communication_feedback cf
                    LEFT JOIN app.students s USING (student_id)
                    LEFT JOIN app.guardians g USING (guardian_id)
                    LEFT JOIN app.classes c USING (class_id)
                    WHERE s.active IS TRUE
                    GROUP BY cf.com_feedback_id, subject, message, student_name, parent_full_name, class_name, opened
                    ORDER BY post_id DESC");
        
                $sth6->execute(array());
                $feedback[$result->school] = $sth6->fetchAll(PDO::FETCH_OBJ);
        
        
              $result->myStudents = $myStudents;
              $result->subjectsIteachOnly = $subjectsIteachOnly;
              $result->subjectsWhereImAclassTeacher = $subjectsWhereImAclassTeacher;
              $result->myStudentsParents = $myStudentsParents;
              $result->myProfile = $myProfile;
              $result->mySchoolDetails = $mySchoolDetails;
              $result->myTopStudents = $myTopStudents;
              $result->news = $news;
              $result->feedback = $feedback;
        
              $app->response->setStatus(200);
              $app->response()->headers->set('Content-Type', 'application/json');
              $db = null;
        
              echo json_encode(array('response' => 'success', 'data' => $result ));
        
            } else {
            
                $app->response->setStatus(200);
                $app->response()->headers->set('Content-Type', 'application/json');
                echo json_encode(array("response" => "error", "code" => 3, "data" => 'The data you are tying to get does not exist.'));
                
            }
    
    } else { 
        if($userStatus === "stop"){ 
        
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array("response" => "error", "code" => 2, "data" => 'There seems to be a mixup in the system. Please let us know about this issue.'));
            
        } else {
        
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array("response" => "error", "code" => 3, "data" => 'The data you are tying to get does not exist.'));
            
        }
    }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateStaffPassword', function () use($app) {
  // update password
  $allPostVars = json_decode($app->request()->getBody(),true);
  $userId = $allPostVars['staff_id'];
  $oldPwd = $allPostVars['user_pwd'];
  $newPwd = $allPostVars['new_password'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();

    $sth1 = $db->prepare("SELECT * FROM staff WHERE staff_id = :userId and password = :oldPwd");
    $sth1->execute( array(':userId' => $userId, ':oldPwd' => $oldPwd) );
    $result = $sth1->fetch(PDO::FETCH_OBJ);
    if( $result )
    {
      $sth2 = $db->prepare("UPDATE staff SET password = :newPwd WHERE staff_id = :userId");
      $sth2->execute( array(':userId' => $userId, ':newPwd' => $newPwd) );
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success. Password has been changed.", "code" => 1));
    }
    else
    {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "error", "data" => "Current password is incorrect." ));
    }


    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getHomeworkPostsForApp/:school/:status/:class_subject_id(/:class_id/:teacher_id)', function ($school, $status, $classSubjectId, $classId = null, $teacherId = null) {
    // Get all homework for class for current school year

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $params = array();
    $query = "SELECT homework_id as post_id, assigned_date, title, body, homework.post_status_id, post_status,
                employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                attachment, homework.modified_date,
                class_name, class_subjects.class_id, due_date, subject_name, homework.class_subject_id, homework.creation_date
              FROM app.homework
              INNER JOIN app.employees
              ON homework.created_by = employees.emp_id
              INNER JOIN app.blog_post_statuses
              ON homework.post_status_id = blog_post_statuses.post_status_id
              INNER JOIN app.class_subjects
                INNER JOIN app.classes
                  INNER JOIN app.students
                  ON classes.class_id = students.current_class
                ON class_subjects.class_id = classes.class_id
                INNER JOIN app.subjects
                ON class_subjects.subject_id = subjects.subject_id
              ON homework.class_subject_id = class_subjects.class_subject_id
              WHERE date_trunc('year', homework.creation_date) =  date_trunc('year', now())
            ";

  if( $status != 'All' )
  {
    $query .= "AND homework.post_status_id = :status ";
    $params[':status'] = $status;
  }
  if( $classSubjectId != 'All' )
  {
    $query .= "AND homework.class_subject_id = :classSubjectId ";
    $params[':classSubjectId'] = $classSubjectId;
  }
  else if( $classId !== null && $classId != 'All' )
  {
    $query .= "AND classes.class_id = :classId ";
    $params[':classId'] = $classId;
  }

  if( $teacherId !== '0' )
  {
    $query .= "AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId) ";
    $params[':teacherId'] = $teacherId;
  }

  $query .= " GROUP BY homework_id, assigned_date, title, body, homework.post_status_id, post_status,
                posted_by, homework.class_subject_id,
                attachment, homework.modified_date,
                class_name, class_subjects.class_id, due_date, subject_name
        ORDER BY homework.creation_date desc";

      $sth = $db->prepare( $query );
  $sth->execute( $params );
      $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getHomeworkPostForApp/:school/:post_id', function ($school,$postId) {
    // Get homework post

    $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT homework_id as post_id, homework.creation_date, title, body, homework.post_status_id, post_status,
                                first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as posted_by, attachment, homework.modified_date,
                                class_name, classes.class_id, homework.class_subject_id, subjects.subject_id, subject_name, assigned_date, due_date
                            FROM app.homework
                            INNER JOIN app.employees
                            ON homework.created_by = employees.emp_id
                            INNER JOIN app.blog_post_statuses
                            ON homework.post_status_id = blog_post_statuses.post_status_id
                            INNER JOIN app.class_subjects
                              INNER JOIN app.classes
                              ON class_subjects.class_id = classes.class_id
                              INNER JOIN app.subjects
                              ON class_subjects.subject_id = subjects.subject_id
                            ON homework.class_subject_id = class_subjects.class_subject_id
                            WHERE homework_id = :postId");
        $sth->execute( array(':postId' => $postId) );
        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $settings ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addHomeworkFromApp/:school', function ($school) {
    // Add homework post
  
    $app = \Slim\Slim::getInstance();
    
    $allPostVars = json_decode($app->request()->getBody(),true);

      $classSubjectId = ( isset($allPostVars['class_subject_id']) ? $allPostVars['class_subject_id']: null);
      $title =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
      $body =     ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
      $postStatusId = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
      $attachment = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
      $dueDate   =  ( isset($allPostVars['post']['due_date']) ? $allPostVars['post']['due_date']: null);
      $assignedDate = ( isset($allPostVars['post']['assigned_date']) ? $allPostVars['post']['assigned_date']: null);
      $postedBy =   ( isset($allPostVars['post']['posted_by']) ? $allPostVars['post']['posted_by']: null);
      $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("INSERT INTO app.homework(class_subject_id, created_by, body, title, post_status_id, attachment, due_date, assigned_date)
                          VALUES(:classSubjectId, :postedBy, :body, :title, :postStatusId, :attachment, :dueDate, :assignedDate)");

    $sth->execute( array(':classSubjectId' => $classSubjectId, ':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId ,
            ':attachment' => $attachment , ':postedBy' => $postedBy, ':dueDate' => $dueDate, ':assignedDate' => $assignedDate,
     ));
     
    // if homework was published
    if( $postStatusId === 1 )
    {
      // get class and subject
      $className = $db->prepare("SELECT class_name, subject_name
                                FROM app.class_subjects
                                INNER JOIN app.classes ON classes.class_id = class_subjects.class_id
                                INNER JOIN app.subjects ON subjects.subject_id = class_subjects.subject_id
                              WHERE class_subject_id = :classSubjectId");
      $className->execute(array(':classSubjectId' => $classSubjectId));
      $classNameResult = $className->fetch(PDO::FETCH_OBJ);

      $studentsInClass = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = (select class_id
                                                                from app.class_subjects
                                                                where class_subject_id = :classSubjectId)");
      $studentsInClass->execute( array(':classSubjectId' => $classSubjectId));
      $results = $studentsInClass->fetchAll(PDO::FETCH_OBJ);

      $studentIds = array();
      foreach($results as $result) {
        $studentIds[] = $result->student_id;
      }
      $studentIdStr = '{' . implode(',', $studentIds) . '}';

      $db = null;

      // homework was published, need to add entry for notifications
      $db = getMISDB();
      $subdomain = $school;
      $message = "New homework posted for " . $classNameResult->class_name . " " . $classNameResult->subject_name . "! " . $title;

      // get all device ids
      $getDeviceIds = $db->prepare("SELECT device_user_id
                                    FROM parents
                                    INNER JOIN parent_students
                                    ON parents.parent_id = parent_students.parent_id
                                    WHERE subdomain = :subdomain
                                    AND student_id = any(:studentIds)");
      $getDeviceIds->execute( array(':studentIds' => $studentIdStr, ':subdomain' => $subdomain) );
      $results = $getDeviceIds->fetchAll(PDO::FETCH_OBJ);

      $deviceIds = array();
      foreach($results as $result) {
        $id = $result->device_user_id;
        if( !empty($id) && !in_array($id, $deviceIds) ) $deviceIds[] = $id;
      }

      if( count($deviceIds) > 0 ) {
        $deviceIdStr = '{' . implode(',',$deviceIds) . '}';

        $add = $db->prepare("INSERT INTO notifications(subdomain, device_user_ids, message)
                             VALUES(:subdomain, :deviceIds, :message)");
        $add->execute(
          array(
            ':subdomain' => $subdomain,
            ':deviceIds' => $deviceIdStr,
            ':message' => $message
          )
        );
      }
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1, "data" => "Homework added successfully."));
    $db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/updateHomeworkFromApp/:school', function () use($school) {
  // Update homework
  $allPostVars = json_decode($app->request()->getBody(),true);
  $homeworkId =   ( isset($allPostVars['post']['post_id']) ? $allPostVars['post']['post_id']: null);
  $title =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
  $body =     ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
  $postStatusId = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
  $attachment = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
  $dueDate   =  ( isset($allPostVars['post']['due_date']) ? $allPostVars['post']['due_date']: null);
  $assignedDate = ( isset($allPostVars['post']['assigned_date']) ? $allPostVars['post']['assigned_date']: null);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = setDBConnection($school);

    if( $postStatusId === 1 )
    {
      // check the current homework status
      // if already published, send an notification that post was updated
      // if being updated to published, send notification of new post

      $check = $db->prepare("SELECT post_status_id FROM app.homework WHERE homework_id = :homeworkId");
      $check->execute(array(':homeworkId' => $homeworkId));
      $checkResult = $check->fetch(PDO::FETCH_OBJ);
    }

    $sth = $db->prepare("UPDATE app.homework
                        SET title = :title,
                          body = :body,
                          post_status_id = :postStatusId,
                          attachment = :attachment,
                          due_date = :dueDate,
                          assigned_date = :assignedDate,
                          modified_date = now(),
                          modified_by = :userId
                        WHERE homework_id = :homeworkId");

    $sth->execute( array(':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId,
             ':attachment' => $attachment, ':userId' => $userId, ':homeworkId' => $homeworkId,
             ':dueDate' => $dueDate, ':assignedDate' => $assignedDate) );

    // if homework was published
    if( $postStatusId === 1 )
    {
      // get class and subject
      $className = $db->prepare("SELECT class_name, subject_name
                                FROM app.homework
                                INNER JOIN app.class_subjects ON homework.class_subject_id = class_subjects.class_subject_id
                                INNER JOIN app.classes ON classes.class_id = class_subjects.class_id
                                INNER JOIN app.subjects ON subjects.subject_id = class_subjects.subject_id
                              WHERE homework_id = :homeworkId");
      $className->execute(array(':homeworkId' => $homeworkId));
      $classNameResult = $className->fetch(PDO::FETCH_OBJ);

      $studentsInClass = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = (select class_id
                                                                from app.homework
                                                                inner join app.class_subjects
                                                                ON homework.class_subject_id = class_subjects.class_subject_id
                                                                where homework_id = :homeworkId)");
      $studentsInClass->execute( array(':homeworkId' => $homeworkId));
      $results = $studentsInClass->fetchAll(PDO::FETCH_OBJ);

      $studentIds = array();
      foreach($results as $result) {
        $studentIds[] = $result->student_id;
      }
      $studentIdStr = '{' . implode(',', $studentIds) . '}';

      $db = null;

      // homework was published, need to add entry for notifications
      $db = getMISDB();
      $subdomain = getSubDomain();

      // blog was published, need to add entry for notifications
      if( $checkResult->post_status_id == 2 )
      {
        // previously a draft, now publishing
        $message = "New homework posted for " . $classNameResult->class_name . " " . $classNameResult->subject_name . "! " . $title;
      }
      else{
        // previously published, updating
        $message = "Homework for " . $classNameResult->class_name . " " . $classNameResult->subject_name . " updated! " . $title;
      }

      // get all device ids
      $getDeviceIds = $db->prepare("SELECT device_user_id
                                    FROM parents
                                    INNER JOIN parent_students
                                    ON parents.parent_id = parent_students.parent_id
                                    WHERE subdomain = :subdomain
                                    AND student_id = any(:studentIds)");
      $getDeviceIds->execute( array(':studentIds' => $studentIdStr, ':subdomain' => $subdomain) );
      $results = $getDeviceIds->fetchAll(PDO::FETCH_OBJ);

      $deviceIds = array();
      foreach($results as $result) {
        $id = $result->device_user_id;
        if( !empty($id) && !in_array($id, $deviceIds) ) $deviceIds[] = $id;
      }

      if( count($deviceIds) > 0 ) {
        $deviceIdStr = '{' . implode(',',$deviceIds) . '}';

        $add = $db->prepare("INSERT INTO notifications(subdomain, device_user_ids, message)
                             VALUES(:subdomain, :deviceIds, :message)");
        $add->execute(
          array(
            ':subdomain' => $subdomain,
            ':deviceIds' => $deviceIdStr,
            ':message' => $message
          )
        );
      }
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->delete('/deleteHomeworkFromApp/:school/:homework_id', function ($school,$homeworkId) {
    // Delete homework

    $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("DELETE FROM app.homework WHERE homework_id = :homeworkId");
        $sth->execute( array(':homeworkId' => $homeworkId) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1, "data" => "Homework deleted successfully."));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/updateDeviceUserId', function () use($app) {
  // update users device id
  $allPostVars = $app->request->post();
  $staffId = $allPostVars['staff_id'];
  $deviceUserId = $allPostVars['device_user_id'];

  try
  {
    $db = getLoginDB();

    $sth = $db->prepare("UPDATE staff SET device_user_id = :deviceUserId WHERE staff_id = :staffId");
    $sth->execute( array(':staffId' => $staffId, ':deviceUserId' => $deviceUserId) );

    $db = null;

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getSchoolCurrency/:school', function ($school) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT value FROM app.settings WHERE name = 'Currency'");
    $sth->execute();
    $settings = $sth->fetch(PDO::FETCH_OBJ);

        if($settings) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $settings ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getSchoolContactInfo/:school', function ($school) {
    //Show contact info

  $app = \Slim\Slim::getInstance();

    try
    {
      $db = setDBConnection($school);
      $sth = $db->prepare("SELECT name, value FROM app.settings");
      $sth->execute();
      $settings = $sth->fetchAll(PDO::FETCH_OBJ);

        if($settings) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $settings ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

/* ********** TERM API's ********** */

$app->get('/getCurrentTerm/:school', function ($school) {
    //Get current term

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    $query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.current_term");
    $query->execute();
        $results = $query->fetch(PDO::FETCH_ASSOC);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getPreviousTerm/:school', function ($school) {
    //Get previous term

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
    
        $query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.previous_term");
        $query->execute();
            $results = $query->fetch(PDO::FETCH_ASSOC);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getSchoolTerms/:school(/:year)', function ($school, $year = null) {
    //Show all terms for given year (or this year if null)

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    if( $year === null )
    {
      $query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name, start_date, end_date,
                      case when term_id = (select term_id from app.current_term) then true else false end as current_term, date_part('year',start_date) as year
                    FROM app.terms
                    ORDER BY date_part('year',start_date), term_name");
      $query->execute(array());
    }
    else
    {
      $query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name,start_date, end_date,
                      case when term_id = (select term_id from app.current_term) then true else false end as current_term,
                      date_part('year',start_date) as year
                    FROM app.terms
                    WHERE date_part('year',start_date) = :year
                    ORDER BY date_part('year',start_date), term_name");
      $query->execute(array(':year' => $year));
    }

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getTermRange/:school', function ($school) {
    //Get previous term

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
    
        $query = $db->prepare("SELECT 'previous' as type, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.previous_term 
														UNION
														SELECT 'current', term_name, start_date, end_date, date_part('year', start_date) as year FROM app.current_term 
														UNION
														SELECT 'next', term_name, start_date, end_date, date_part('year', start_date) as year FROM app.next_term 
														order by start_date");
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        
        $obj = array();
		foreach($results as $result){
			$obj[$result->type] = $result;
		}

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $obj ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addTerm/:school', function ($school) {
    // Add term
  
    $app = \Slim\Slim::getInstance();
    
    $allPostVars = json_decode($app->request()->getBody(),true);

    $termName =		( isset($allPostVars['term_name']) ? $allPostVars['term_name']: null);
	$startDate =	( isset($allPostVars['start_date']) ? $allPostVars['start_date']: null);
	$endDate =		( isset($allPostVars['end_date']) ? $allPostVars['end_date']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("INSERT INTO app.terms(term_name, start_date, end_date, created_by)
						VALUES(:termName, :startDate, :endDate, :userId)");

    $db->beginTransaction();

    $sth->execute( array(':termName' => $termName, ':startDate' => $startDate, ':endDate' => $endDate, ':userId' => $userId ) );

    $db->commit();

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1, "data" => "New term created successfully."));
    $db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/updateTerm/:school', function ($school) {
    // Update term

    $app = \Slim\Slim::getInstance();
    
	$allPostVars = json_decode($app->request()->getBody(),true);
	$termId =		( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$termName =		( isset($allPostVars['term_name']) ? $allPostVars['term_name']: null);
	$startDate =	( isset($allPostVars['start_date']) ? $allPostVars['start_date']: null);
	$endDate =		( isset($allPostVars['end_date']) ? $allPostVars['end_date']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("UPDATE app.terms
			                SET term_name = :termName,
                				start_date = :startDate,
                				end_date = :endDate
						    WHERE term_id = :termId");
        $sth->execute( array(':termName' => $termName, ':startDate' => $startDate, ':endDate' => $endDate, ':termId' => $termId ) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1, "data" => "Term updated successfully."));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->delete('/deleteTerm/:school/:term_id', function ($school,$termId) {
    // Delete term

    $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("DELETE FROM app.terms WHERE term_id = :termId");
        $sth->execute( array(':termId' => $termId) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1, "data" => "Term deleted successfully."));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

/* ********** TRANSPORT API's ********** */

$app->get('/getAllBuses/:school/:status', function ($school,$status) {
  //Show all school buses

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT * FROM app.buses WHERE active = :status");
    $sth->execute( array(':status' => $status) );
      
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getAllAssignedBuses/:school/:status', function ($school,$status) {
  //Show all assigned buses

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT bus_id, bus_type, bus_registration, route_id, route FROM app.buses
                        INNER JOIN app.transport_routes ON buses.route_id = transport_routes.transport_id
                        WHERE buses.active = :status
                        AND buses.route_id IS NOT NULL
                        ORDER BY bus_id DESC");
    $sth->execute( array(':status' => $status) );
      
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getActiveRoutes/:school/:status', function ($school,$status) {
  //Show all active routes

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT transport_id, route FROM app.transport_routes WHERE active = :status");
    $sth->execute( array(':status' => $status) );
      
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/assignBusToRoute/:school', function ($school) {
    // Update bus assignment

    $app = \Slim\Slim::getInstance();
    
	$allPostVars = json_decode($app->request()->getBody(),true);
	$busId =	( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);
	$routeId =	( isset($allPostVars['route_id']) ? $allPostVars['route_id']: null);

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("UPDATE app.buses
							SET route_id = :routeId,
								modified_date = now()
							WHERE bus_id = :busId
							");
        $sth->execute( array(':busId' => $busId,
							 ':routeId' => $routeId
					) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getAllDrivers/:school', function ($school) {
  //Show all employees who are employed under driver/drivers department

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT e.emp_id, e.dept_id, e.emp_number, d.dept_name,
                        	e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as driver_name,
                        	e.telephone, e.emp_image
                        FROM app.employees e
                        INNER JOIN app.departments d USING (dept_id)
                        WHERE e.active IS TRUE
                        AND LOWER(d.dept_name) = LOWER('Drivers')
                        OR LOWER(d.dept_name) = LOWER('Driver')");
    $sth->execute();
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getAllEmployeesExceptDrivers/:school', function ($school) {
  //Show all employees to assigne as schoolbus assistants

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT e.emp_id, e.dept_id, e.emp_number, d.dept_name,
                        	e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as assistant_name,
                        	e.telephone, e.emp_image
                        FROM app.employees e
                        INNER JOIN app.departments d USING (dept_id)
                        WHERE e.active IS TRUE
                        AND LOWER(d.dept_name) != LOWER('Drivers')
                        AND LOWER(d.dept_name) != LOWER('Driver')");
    $sth->execute();
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/assignPersonnelToBus/:school', function ($school) {
    // Update - assign driver and assistant to bus

	$app = \Slim\Slim::getInstance();
    
	$allPostVars = json_decode($app->request()->getBody(),true);
	$busId =	( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);
	$busDriver =	( isset($allPostVars['bus_driver']) ? $allPostVars['bus_driver']: null);
	$busGuide =	( isset($allPostVars['bus_guide']) ? $allPostVars['bus_guide']: null);

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("UPDATE app.buses
							SET bus_driver = :busDriver,
							    bus_guide = :busGuide,
								modified_date = now()
							WHERE bus_id = :busId
							");
        $sth->execute( array(':busId' => $busId, ':busDriver' => $busDriver, ':busGuide' => $busGuide ) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getAllBusesRoutesAndDrivers/:school/:status', function ($school,$status) {
  //Show all data relating to a bus

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT bus_id, bus_type, bus_registration, route_id, route, bus_driver,
                        	(case when driver_name is null then 'Unassigned' else driver_name end) as driver_name,
                        	bus_guide, (case when guide_name is null then 'Unassigned' else guide_name end) as guide_name
                        FROM(
                        	SELECT bus_id, bus_type, bus_registration, route_id, route, bus_driver, bus_guide,
                        		e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as driver_name,
                        		e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name
                        	FROM app.buses
                        	FULL OUTER JOIN app.transport_routes ON buses.route_id = transport_routes.transport_id
                        	FULL OUTER JOIN app.employees e ON buses.bus_driver = e.emp_id
                        	FULL OUTER JOIN app.employees e2 ON buses.bus_guide = e2.emp_id
                        	WHERE buses.active = :status
                        	ORDER BY bus_id DESC
                        )A");
    $sth->execute( array(':status' => $status) );
      
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getDriverOrGuideRouteBusStudents/:school/:empId', function ($school,$empId) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    /* Check if this employee is assigned a school bus */
    $checkEmployeeQry = $db->query("SELECT (CASE
                                        		WHEN EXISTS (SELECT bus_driver FROM app.buses WHERE bus_driver = $empId) THEN
                                        			'okay'
                                        		WHEN EXISTS (SELECT bus_guide FROM app.buses WHERE bus_guide = $empId) THEN
                                        			'okay'
                                        		ELSE 'stop'
                                        	END) AS emp_status");
    $employeeCheck = $checkEmployeeQry->fetch(PDO::FETCH_OBJ);
    $employeeCheck = $employeeCheck->emp_status;
    
    /* Check if this employee is assigned to one or more buses */
    $checkBusCount = $db->query("SELECT COUNT(*) FROM app.buses WHERE bus_driver = $empId OR bus_guide = $empId");
    $employeeCheckBusCount = $checkBusCount->fetch(PDO::FETCH_OBJ);
    $employeeCheckBusCount = $employeeCheckBusCount->count;
    
    /* Employee's assigned bus(es), route(s) */
    $employeeBuses = $db->query("SELECT bus_id, bus_type, bus_registration, route_id, e1.emp_id AS driver_id, e2.emp_id AS guide_id,
                                	e1.first_name || ' ' || coalesce(e1.middle_name,'') || ' ' || e1.last_name AS driver,
                                	e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name AS guide,
                                	ts.route
                                FROM app.buses
                                INNER JOIN app.transport_routes ts ON buses.route_id = ts.transport_id
                                INNER JOIN app.employees e1 ON buses.bus_driver = e1.emp_id
                                INNER JOIN app.employees e2 ON buses.bus_guide = e2.emp_id
                                WHERE e1.emp_id = $empId
                                OR e2.emp_id = $empId");
    $assignedBuses = $employeeBuses->fetchAll(PDO::FETCH_OBJ);
      
    $results =  new stdClass();
	$results->employeeCheck = $employeeCheck;
	$results->employeeCheckBusCount = $employeeCheckBusCount;
	$results->assignedBuses = $assignedBuses;/*
	$results->overallLastTerm = $overallLastTerm;
	$results->graphPoints = $graphPoints;*/

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentsInBus/:school/:busId', function ($school,$busId) {
  // Get all students in the given school bus

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);
    
     $sth = $db->prepare("SELECT student_id, student_name, class_name, array_agg('{' || 'guardian_id:' || guardian_id || ',' || 'guardian_name:' || guardian_name || ',' || 'relationship:' || relationship || ',' || 'telephone:' || telephone || '}') AS parents, driver_id, driver_name, guide_id, assistant_name, bus_id, bus_registration, bus_type, route_id, route FROM (
                    			SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name,
                                            	g.guardian_id, g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name AS guardian_name, sg.relationship, g.telephone,
                                            	b.bus_driver AS driver_id, e1.first_name || ' ' || coalesce(e1.middle_name,'') || ' ' || e1.last_name AS driver_name,
                                            	b.bus_guide AS guide_id, e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name AS assistant_name,
                                            	b.bus_id, b.bus_registration, b.bus_type, b.route_id, tr.route
                                            FROM app.students s
                                            INNER JOIN app.classes c ON s.current_class = c.class_id
                                            LEFT JOIN app.student_guardians sg USING (student_id)
                                            LEFT JOIN app.guardians g USING (guardian_id)
                                            INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                                            INNER JOIN app.buses b ON tr.transport_id = b.route_id
                                            INNER JOIN app.employees e1 ON b.bus_driver = e1.emp_id
                                            LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
                                            WHERE b.bus_id = :busId
                    )a
                    GROUP BY student_id, student_name, class_name, driver_id, driver_name, guide_id, assistant_name, bus_id, bus_registration, bus_type, route_id, route");
    $sth->execute( array(':busId' => $busId) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

} catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->post('/getStudentsFromClassInBus/:school', function ($school) {
  // Add school bus historical data
  
  $app = \Slim\Slim::getInstance();
    
  $allPostVars = json_decode($app->request()->getBody(),true);

  $busId = ( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);
  $classId = ( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
  $activity = ( isset($allPostVars['activity']) ? $allPostVars['activity']: null);

  try
  {
    $db = setDBConnection($school);

    $fetchStudents = $db->prepare("SELECT student_id, student_name, class_name, array_agg('{' || 'guardian_id:' || guardian_id || ',' || 'guardian_name:' || guardian_name || ',' || 'relationship:' || relationship || ',' || 'telephone:' || telephone || '}') AS parents, driver_id, driver_name, guide_id, assistant_name, bus_id, bus_registration, bus_type, route_id, route FROM (
                                        SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name,
                                        	g.guardian_id, g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name AS guardian_name, sg.relationship, g.telephone,
                                        	b.bus_driver AS driver_id, e1.first_name || ' ' || coalesce(e1.middle_name,'') || ' ' || e1.last_name AS driver_name,
                                        	b.bus_guide AS guide_id, e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name AS assistant_name,
                                        	b.bus_id, b.bus_registration, b.bus_type, b.route_id, tr.route
                                        FROM app.students s
                                        INNER JOIN app.classes c ON s.current_class = c.class_id
                                        LEFT JOIN app.student_guardians sg USING (student_id)
                                        LEFT JOIN app.guardians g USING (guardian_id)
                                        INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                                        INNER JOIN app.buses b ON tr.transport_id = b.route_id
                                        INNER JOIN app.employees e1 ON b.bus_driver = e1.emp_id
                                        LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
                                        WHERE b.bus_id = :busId
                                        AND s.student_id NOT IN (SELECT student_id FROM app.schoolbus_history WHERE date_part('year',creation_date) = (SELECT date_part('year',now()))
                                                        AND date_part('month',creation_date) = (SELECT date_part('month',now())) AND date_part('day',creation_date) = (SELECT date_part('day',now()))
                                                        AND student_id IS NOT NULL AND activity = :activity)
                                        AND s.current_class IN (".implode(',',$classId).")
                                )a
                                GROUP BY student_id, student_name, class_name, driver_id, driver_name, guide_id, assistant_name, bus_id, bus_registration, bus_type, route_id, route");

    $db->beginTransaction();

    $fetchStudents->execute( array(':busId' => $busId,':activity' => $activity) );
    $results = $fetchStudents->fetchAll(PDO::FETCH_OBJ);

    $db->commit();

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1, "data" => $results));
    $db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentsInARoute/:school/:routeId/:activity', function ($school,$routeId,$activity) {
  //Show all students in their respective routes

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name, s.current_class AS class_id, c.class_name,
                        	s.transport_route_id AS transport_id, tr.route
                        FROM app.students s
                        INNER JOIN app.classes c ON s.current_class = c.class_id
                        INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                        WHERE tr.active IS TRUE AND s.active IS TRUE
                        AND transport_id = :routeId
                        AND s.student_id NOT IN (SELECT student_id FROM app.schoolbus_history WHERE date_part('year',creation_date) = (SELECT date_part('year',now()))
                                                AND date_part('month',creation_date) = (SELECT date_part('month',now())) AND date_part('day',creation_date) = (SELECT date_part('day',now()))
                                                AND student_id IS NOT NULL AND activity = :activity)
                        ");
    $sth->execute( array(':routeId' => $routeId,':activity' => $activity) );
      
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getAllSchoolBusHistoryForUser/:school/:emp_id', function ($school, $empId) {
  //Get student report cards

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT sbh.*, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                        c.class_name, e1.first_name || ' ' || coalesce(e1.middle_name,'') || ' ' || e1.last_name as driver_name,
                        e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as bus_assistant_name
                        FROM app.schoolbus_history sbh
                        INNER JOIN app.students s USING (student_id)
                        INNER JOIN app.classes c ON s.current_class = c.class_id
                        LEFT JOIN app.employees e1 ON sbh.bus_driver = e1.emp_id
                        LEFT JOIN app.employees e2 ON sbh.bus_guide = e2.emp_id
                        WHERE bus_driver = :empId
                        OR bus_guide = :empId");
    $sth->execute( array(':empId' => $empId) );

    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getSpecificSchoolBusHistoryForUser/:school/:emp_id/:activity/:year/:month/:day', function ($school, $empId, $activity, $year, $month, $day) {
  //Get student report cards

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT sbh.*, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                        c.class_name, e1.first_name || ' ' || coalesce(e1.middle_name,'') || ' ' || e1.last_name as driver_name,
                        e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as bus_assistant_name
                        FROM app.schoolbus_history sbh
                        INNER JOIN app.students s USING (student_id)
                        INNER JOIN app.classes c ON s.current_class = c.class_id
                        LEFT JOIN app.employees e1 ON sbh.bus_driver = e1.emp_id
                        LEFT JOIN app.employees e2 ON sbh.bus_guide = e2.emp_id
                        WHERE sbh.activity = :activity
                        AND date_part('year', sbh.creation_date) = :year 
                        AND date_part('month', sbh.creation_date) = :month 
                        AND date_part('day', sbh.creation_date) = :day
                        AND sbh.bus_driver = :empId
                        OR sbh.bus_guide = :empId");
    $sth->execute( array(':empId' => $empId, ':activity' => $activity, ':year' => $year, ':month' => $month, ':day' => $day) );

    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->post('/createSchoolBusHistory/:school', function ($school) {
  // Add school bus historical data
  
  $app = \Slim\Slim::getInstance();
    
  $allPostVars = json_decode($app->request()->getBody(),true);

  $busId = ( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);
  $busType = ( isset($allPostVars['bus_type']) ? $allPostVars['bus_type']: null);
  $busRegistration = ( isset($allPostVars['bus_registration']) ? $allPostVars['bus_registration']: null);
  $routeId = ( isset($allPostVars['route_id']) ? $allPostVars['route_id']: null);
  $busDriver = ( isset($allPostVars['bus_driver']) ? $allPostVars['bus_driver']: null);
  $busGuide = ( isset($allPostVars['bus_guide']) ? $allPostVars['bus_guide']: null);
  $gps = ( isset($allPostVars['gps']) ? $allPostVars['gps']: null);
  $gpsTime = ( isset($allPostVars['gps_time']) ? $allPostVars['gps_time']: null);
  $gpsOrder = ( isset($allPostVars['gps_order']) ? $allPostVars['gps_order']: null);
  $activity = ( isset($allPostVars['activity']) ? $allPostVars['activity']: null);
  $studentId = ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);

  try
  {
    $db = setDBConnection($school);

    $busInsert = $db->prepare("INSERT INTO app.schoolbus_history(bus_id, bus_type, bus_registration, route_id, bus_driver, bus_guide, gps, gps_time, gps_order, activity, student_id)
            VALUES(:busId,:busType,:busRegistration,:routeId,:busDriver,:busGuide,:gps,now(),:gpsOrder,:activity,:studentId);");

    $db->beginTransaction();

    $busInsert->execute( array(':busId' => $busId, ':busType' => $busType,
              ':busRegistration' => $busRegistration, ':routeId' => $routeId, ':busDriver' => $busDriver, ':busGuide' => $busGuide, ':gps' => $gps, /* ':gpsTime' => $gpsTime, */ ':gpsOrder' => $gpsOrder, ':activity' => $activity, ':studentId' => $studentId
    ) );

    $db->commit();

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

/* ********** CLASSES API'S ********** */

$app->get('/getClassCategories/:school', function ($school) {
  // Get all class categories in the school

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);
    
     $sth = $db->prepare("SELECT class_cat_id, class_cat_name FROM app.class_cats WHERE active IS TRUE");
    $sth->execute( array() );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

} catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->get('/getAllClasses/:school/:status', function ($school,$status) {
  // Get all classes in the school

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);
    
     $sth = $db->prepare("SELECT class_id, class_name, class_cat_id, classes.teacher_id, classes.active, report_card_type,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name
							FROM app.classes
							LEFT JOIN app.employees ON classes.teacher_id = employees.emp_id
							WHERE classes.active = :status
							ORDER BY sort_order");
    $sth->execute( array(':status' => $status) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

} catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->get('/getClasses/:school/(:classCatid/:status)', function ($school,$classCatid = 'ALL', $status=true) {
  // Get all classes in a category the school

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);
    $params = array(':status' => $status);
    
    $query = "SELECT class_id, class_name, classes.class_cat_id, teacher_id, classes.active, class_cat_name,
            					classes.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, report_card_type,
            					(select array_agg(subject_name order by sort_order)
            						from (
            							select subject_name, sort_order
            							from app.class_subjects
            							inner join app.subjects on class_subjects.subject_id = subjects.subject_id and subjects.active is true
            							where class_subjects.class_id = classes.class_id
            							and class_subjects.active is true
            							group by subject_name, sort_order
            						)a ) as subjects
                        FROM app.classes
            			INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
            			LEFT JOIN app.employees ON classes.teacher_id = employees.emp_id
            			WHERE classes.active = :status ";
    if( $classCatid != 'ALL' )
	{
		$query .= "AND classes.class_cat_id = :classCatid";
		$params[':classCatid'] = $classCatid;
	}

	$query .= " ORDER BY sort_order";

	$sth = $db->prepare($query);
	$sth->execute( $params );

	$results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        foreach( $results as $result)
		{
			$result->subjects = pg_array_parse($result->subjects);
		}
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

} catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->get('/getTeacherClasses/:school/:teacher_id(/:status)', function ($school, $teacherId, $status = true) {
    //Show classes for specific teacher

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
    
        $sth = $db->prepare("SELECT classes.class_id, class_name, classes.class_cat_id, classes.teacher_id, classes.active, class_cat_name,
        					first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name,
        					(select array_agg(distinct subject_name)
        						from app.class_subjects
        						inner join app.subjects on class_subjects.subject_id = subjects.subject_id
        						where class_subjects.class_id = classes.class_id
        						and class_subjects.active is true
        						and teacher_id = :teacherId) as subjects,
        					(select count(*)
        								from app.students
        								inner join app.classes c
        								on students.current_class = c.class_id  AND c.active is true
        								where c.class_id = classes.class_id) as num_students,
        								blog_id, blog_name, report_card_type
        					FROM app.classes
        					INNER JOIN app.class_subjects
        						INNER JOIN app.subjects
        						ON class_subjects.subject_id = subjects.subject_id
        					ON classes.class_id = class_subjects.class_id
        					LEFT JOIN app.employees
        					ON classes.teacher_id = employees.emp_id
        					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
        					LEFT JOIN app.blogs
        					ON (classes.class_id = blogs.class_id AND classes.teacher_id = blogs.teacher_id) OR
        					   (class_subjects.class_id = blogs.class_id AND subjects.teacher_id = blogs.teacher_id)
        					WHERE (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
        					AND classes.active = :status
        					GROUP BY classes.class_id, class_name, classes.class_cat_id, classes.teacher_id, classes.active, class_cat_name,
        					teacher_name, subjects, num_students, blog_id, blog_name
        					ORDER BY classes.sort_order");
        $sth->execute( array(':teacherId' => $teacherId, ':status' => $status));

        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            foreach( $results as $result)
			{
				$result->subjects = pg_array_parse($result->subjects);
			}
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

/* ********** EXAM TYPES API'S ********** */

$app->get('/getExamTypes/:school/:class_cat_id', function ($school, $classCatId) {
    //Show all exam types in a class category

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
    
        $sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
							FROM app.exam_types 
							LEFT JOIN app.class_cats
							ON exam_types.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							WHERE exam_types.class_cat_id = :classCatId 
							ORDER BY sort_order");
        $sth->execute( array(':classCatId' => $classCatId));

        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            foreach( $results as $result)
			{
				$result->subjects = pg_array_parse($result->subjects);
			}
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addExamType/:school', function ($school) {
    // Add an exam type
  
    $app = \Slim\Slim::getInstance();
    
    $allPostVars = json_decode($app->request()->getBody(),true);
	
	$examType =		( isset($allPostVars['exam_type']) ? $allPostVars['exam_type']: null);
	$classCatId =	( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = setDBConnection($school);

    $sth0 = $db->prepare("SELECT max(sort_order) as sort_order FROM app.exam_types WHERE class_cat_id = :classCatId");
    
	/* get the next number for sort order */		
	$sth1 = $db->prepare("INSERT INTO app.exam_types(exam_type, class_cat_id, sort_order, created_by) 
								VALUES(:examType, :classCatId, :sortOrder, :userId)"); 
								
	$sth2 = $db->prepare("SELECT * FROM app.exam_types WHERE exam_type_id = currval('app.exam_types_exam_type_id_seq')");

    $db->beginTransaction();

    $sth0->execute( array(':classCatId' => $classCatId) );
	$sort = $sth0->fetch(PDO::FETCH_OBJ);
	$sortOrder = ($sort && $sort->sort_order !== NULL ? $sort->sort_order + 1 : 1);

	$sth1->execute( array(':examType' => $examType, ':classCatId' => $classCatId, ':sortOrder' => $sortOrder, ':userId' => $userId ) );
	$sth2->execute();
	$results = $sth2->fetch(PDO::FETCH_OBJ);

    $db->commit();

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1, "data" => $results));
    $db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->delete('/deleteExamType/:school/:exam_type_id', function ($school,$examTypeId) {
    // Delete term

    $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("DELETE FROM app.exam_types WHERE exam_type_id = :examTypeId");		
		$sth->execute( array(':examTypeId' => $examTypeId) );

		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1, "data" => "Exam type deleted successfully."));
        $db = null;


    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

/* ********** STUDENT'S API'S ********** */

$app->get('/getAllStudents/:school/:status(/:startDate/:endDate)', function ($school,$status,$startDate=null,$endDate=null) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        
        if( $startDate !== null )
    {
      $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                  classes.teacher_id as class_teacher_id,
                  (select array_agg(fee_items.fee_item_id) 
                    from app.student_fee_items
                    inner join app.fee_items
                    on student_fee_items.fee_item_id = fee_items.fee_item_id
                    where student_id = students.student_id
                    and optional is true) as enrolled_opt_courses
                 FROM app.students
                 INNER JOIN app.classes ON students.current_class = classes.class_id
                 WHERE students.active = :status
                 AND admission_date between :startDate and :endDate
                 ORDER BY first_name, middle_name, last_name");
      $sth->execute( array(':status' => $status, ':startDate' => $startDate, ':endDate' => $endDate) );
    }
    else
    {
      $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                  classes.teacher_id as class_teacher_id,
                  (select array_agg(fee_items.fee_item_id) 
                    from app.student_fee_items
                    inner join app.fee_items
                    on student_fee_items.fee_item_id = fee_items.fee_item_id
                    where student_id = students.student_id
                    and optional is true) as enrolled_opt_courses
                 FROM app.students
                 INNER JOIN app.classes ON students.current_class = classes.class_id
                 WHERE students.active = :status
                 ORDER BY first_name, middle_name, last_name");
      $sth->execute( array(':status' => $status));
    }
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            
            foreach( $results as $result)
            {
              $result->enrolled_opt_courses = pg_array_parse($result->enrolled_opt_courses);
            }
            
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getAllParents/:school', function ($school) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT guardians.guardian_id,
                  guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name AS parent_full_name,
                  email, telephone,
                  students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                  students.student_id, students.current_class, class_name, relationship
              FROM app.students
              INNER JOIN app.student_guardians
                INNER JOIN app.guardians
                ON student_guardians.guardian_id = guardians.guardian_id
              ON students.student_id = student_guardians.student_id AND student_guardians.active is true
              INNER JOIN app.classes
              ON students.current_class = classes.class_id AND students.active is true
              WHERE students.active is true
              ORDER BY guardians.first_name, guardians.middle_name, guardians.last_name");
        $sth->execute();
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentGenderCount/:school', function ($school) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT count(gender) AS total,
                        (SELECT count(gender) FROM app.students WHERE active IS TRUE and gender = 'M') AS boys,
                        (SELECT count(gender) FROM app.students WHERE active IS TRUE and gender = 'F') AS girls
                        FROM app.students
                        WHERE active IS TRUE");
        $sth->execute();
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentIteach/:school/:teacher_id/:status(/:startDate/:endDate)', function ($school, $teacherId, $status, $startDate=null, $endDate=null) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        
        if( $startDate !== null )
        {
          $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                      classes.teacher_id as class_teacher_id
                   FROM app.students
                   INNER JOIN app.classes
                    INNER JOIN app.class_subjects
                      INNER JOIN app.subjects
                      ON class_subjects.subject_id = subjects.subject_id
                    ON classes.class_id = class_subjects.class_id
                   ON students.current_class = classes.class_id
                   WHERE students.active = :status
                   AND admission_date between :startDate and :endDate
                   AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
                   GROUP BY students.student_id, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
                   ORDER BY first_name, middle_name, last_name");
          $sth->execute( array(':status' => $status, ':startDate' => $startDate, ':endDate' => $endDate) );
        }
        else
        {
    
          $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                      classes.teacher_id as class_teacher_id
                   FROM app.students
                   INNER JOIN app.classes
                    INNER JOIN app.class_subjects
                      INNER JOIN app.subjects
                      ON class_subjects.subject_id = subjects.subject_id
                    ON classes.class_id = class_subjects.class_id
                   ON students.current_class = classes.class_id
                   WHERE students.active = :status
                   AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
                   GROUP BY students.student_id, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
                   ORDER BY first_name, middle_name, last_name");
          $sth->execute( array(':status' => $status, ':teacherId' => $teacherId));
        }
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getMyStudentsParents/:school/:teacher_id', function ($school, $teacherId) {
    //Show parents

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        
        $sth = $db->prepare("SELECT guardians.guardian_id,
                  guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name AS parent_full_name,
                  email, telephone,
                  students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                  students.student_id, students.current_class, class_name, relationship
              FROM app.classes
              INNER JOIN app.students
                INNER JOIN app.student_guardians
                  INNER JOIN app.guardians
                  ON student_guardians.guardian_id = guardians.guardian_id
                ON students.student_id = student_guardians.student_id AND student_guardians.active is true
              ON classes.class_id = students.current_class AND students.active is true
              WHERE classes.active is true
              AND classes.teacher_id = :teacherId
              ORDER BY guardians.first_name, guardians.middle_name, guardians.last_name");
        $sth->execute( array(':teacherId' => $teacherId));
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentDetails/:school/:studentId', function ($school, $studentId) {
    //Get student details

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        
        $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, class_cats.entity_id, classes.class_name, classes.report_card_type,
                payment_plan_name || ' (' || num_payments || ' payments ' || payment_interval || ' ' || payment_interval2 || '(s) apart)' as payment_plan_name,
                classes.teacher_id as class_teacher_id
               FROM app.students
               INNER JOIN app.classes ON students.current_class = classes.class_id
               INNER JOIN app.class_cats USING (class_cat_id)
               LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
               WHERE student_id = :studentID
               ORDER BY first_name, middle_name, last_name");
        $sth->execute( array(':studentID' => $studentId));
        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {
            // get parents
              $sth2 = $db->prepare("SELECT *
                       FROM app.student_guardians
                       INNER JOIN app.guardians ON student_guardians.guardian_id = guardians.guardian_id
                       WHERE student_guardians.student_id = :studentID
                       AND student_guardians.active = true
                       ORDER BY relationship, last_name, first_name, middle_name");
              $sth2->execute( array(':studentID' => $studentId));
              $results2 = $sth2->fetchAll(PDO::FETCH_OBJ);
        
              $results->guardians = $results2;
        
              // get medical history
              $sth3 = $db->prepare("SELECT medical_id, illness_condition, age, comments, creation_date as date_medical_added
                       FROM app.student_medical_history
                       WHERE student_id = :studentID
                       ORDER BY creation_date");
              $sth3->execute( array(':studentID' => $studentId));
              $results3 = $sth3->fetchAll(PDO::FETCH_OBJ);
        
              $results->medical_history = $results3;
        
              // get fee items
              // TO DO: I only want fee items for this school year?
              $sth4 = $db->prepare("SELECT
                          student_fee_item_id,
                          student_fee_items.fee_item_id,
                          fee_item, amount,
                          payment_method,
                          (select sum(payment_inv_items.amount)
                            from app.payment_inv_items
                            inner join app.invoice_line_items
                            on payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
                            where invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                          ) as payment_made,
                          student_fee_items.active
                        FROM app.student_fee_items
                        INNER JOIN app.fee_items on student_fee_items.fee_item_id = fee_items.fee_item_id
                        WHERE student_id = :studentID
                        ORDER BY student_fee_items.creation_date");
              $sth4->execute( array(':studentID' => $studentId));
              $results4 = $sth4->fetchAll(PDO::FETCH_OBJ);
        
              $results->fee_items = $results4;
              
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentFeeBalance/:school/:studentId', function ($school, $studentId) {
    //Get student details

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        
        $sth = $db->prepare("SELECT fee_item, q.payment_method,
                          sum(invoice_total) AS total_due,
                          sum(total_paid) AS total_paid,
                          sum(total_paid) - sum(invoice_total) AS balance
                        FROM
                          ( SELECT invoice_line_items.amount as invoice_total,
                             fee_item,
                             student_fee_items.payment_method,
                             inv_item_id,
                             (SELECT COALESCE(sum(payment_inv_items.amount), 0)
                              FROM app.payment_inv_items
                              INNER JOIN app.payments
                              ON payment_inv_items.payment_id = payments.payment_id AND reversed is false
                              WHERE inv_item_id = invoice_line_items.inv_item_id) as total_paid
                            FROM app.invoices
                            INNER JOIN app.invoice_line_items
                              INNER JOIN app.student_fee_items
                                INNER JOIN app.fee_items
                                ON student_fee_items.fee_item_id = fee_items.fee_item_id
                              ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id AND student_fee_items.active = true
                            ON invoices.inv_id = invoice_line_items.inv_id
                            WHERE invoices.student_id = :studentID
                            AND invoices.canceled = false
                          ) q
                        GROUP BY fee_item, q.payment_method");
                        
        $sth->execute( array(':studentID' => $studentId));
        $fees = $sth->fetch(PDO::FETCH_OBJ);
        
        if( $fees )
        {
          $sth2 = $db->prepare("SELECT
                      (SELECT due_date FROM app.invoice_balances2 WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_due_date,
                      (SELECT balance from app.invoice_balances2 WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_amount,
                      COALESCE((SELECT sum(amount) from app.credits WHERE student_id = :studentID  ),0) AS total_credit,
                      (SELECT sum(balance) from app.invoice_balances2 WHERE student_id = :studentID AND due_date <= now()::date AND canceled = false) AS arrears
                      ");
          $sth2->execute( array(':studentID' => $studentId));
          $details = $sth2->fetch(PDO::FETCH_OBJ);
    
          if( $details )
          {
            //  set the next due summary
            $feeSummary = new Stdclass();
            $feeSummary->next_due_date = $details->next_due_date;
            $feeSummary->next_amount = $details->next_amount;
            //$feeSummary->unapplied_payments = $details->unapplied_payments;
            $feeSummary->total_credit = $details->total_credit;
            $feeSummary->arrears = $details->arrears;
    
            // is the next due date within 30 days?
            $diff = dateDiff("now", $details->next_due_date);
            $feeSummary->within30days = ( $diff < 30 ? true : false );
          }
    
          $balanceQry = $db->prepare("SELECT total_due, total_paid, total_paid - total_due as balance,
                                      case when (select count(*) from app.invoice_balances2 where student_id = :studentID and past_due is true) > 0 then true else false end as past_due
                                    FROM (
                                      SELECT
                                        coalesce(sum(total_amount),0) as total_due,
                                        coalesce((select sum(payment_inv_items.amount) from app.payments inner join app.payment_inv_items on payments.payment_id = payment_inv_items.payment_id where student_id = :studentID),0) as total_paid
                                      FROM app.invoices
                                      WHERE student_id = :studentID
                                      --AND date_part('year', due_date) = date_part('year',now())
                                      AND canceled = false
                                    )q");
          $balanceQry->execute( array(':studentID' => $studentId));
          $balance = $balanceQry->fetch(PDO::FETCH_OBJ);
    
          $feeSummary->total_due = ($balance ? $balance->total_due : 0);
          $feeSummary->total_paid = ($balance ? $balance->total_paid : 0);
          $feeSummary->balance = ($balance ? $balance->balance : 0);
          $feeSummary->past_due = ($balance ? $balance->past_due : false);
    
          $results = new stdClass();
          $results->fee_summary = $feeSummary;
          $results->fees = $fees;
        }

        if($fees) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentInvoices/:school/:studentId', function ($school, $studentId) {
    //Get student details

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        
        $sth = $db->prepare("SELECT invoice_balances2.*, ARRAY(select fee_item || ' (' || invoice_line_items.amount || ')'
                                    from app.invoice_line_items
                                    inner join app.student_fee_items
                                    inner join app.fee_items
                                    on student_fee_items.fee_item_id = fee_items.fee_item_id
                                    on invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                                    where inv_id = invoice_balances2.inv_id) as invoice_items,
                                    term_name,
                                    date_part('year',terms.start_date) as year
                          FROM app.invoice_balances2
                          INNER JOIN app.terms ON invoice_balances2.term_id = terms.term_id
                          WHERE student_id = :studentId
                          ORDER BY inv_date");
        $sth->execute( array(':studentId' => $studentId));
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
        

        if($results ) {
            
            foreach( $results as $result)
            {
              $result->invoice_line_items = pg_array_parse($result->invoice_items);
            }
        
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getOpenInvoices/:school/:studentId', function ($school,$studentId) {
    //Show open invoices for student

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT
                            students.student_id,
                            invoices.inv_id,
                            first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                            class_name, class_id, class_cat_id,
                            inv_date,
                            invoice_line_items.amount,
                            coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) as total_paid,
                            coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) - invoice_line_items.amount as balance,
                            due_date,
                            inv_item_id,
                            fee_item,
                            invoice_line_items.amount as line_item_amount
                          FROM app.invoices
                          INNER JOIN app.invoice_line_items
                            INNER JOIN app.student_fee_items
                              INNER JOIN app.fee_items
                              ON student_fee_items.fee_item_id = fee_items.fee_item_id
                            ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                          ON invoices.inv_id = invoice_line_items.inv_id
                          INNER JOIN app.students
                            INNER JOIN app.classes
                            ON students.current_class = classes.class_id
                          ON invoices.student_id = students.student_id
                          WHERE students.student_id = :studentId
                          AND (select coalesce(sum(amount),0) - invoices.total_amount from app.payment_inv_items where inv_id = invoices.inv_id) < 0
                          AND canceled = false
                          ORDER BY inv_id, due_date, fee_item");
    $sth->execute( array(':studentId' => $studentId) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentFeeItems/:school/:studentId', function ($school,$studentId) {
    //Show student's fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount,
                              CASE WHEN frequency = 'per term' THEN 3
                                   ELSE 1
                              END as frequency
                          FROM app.student_fee_items
                          INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true
                          WHERE student_id = :studentId
                          AND student_fee_items.active = true
                          ORDER BY fee_item");
        $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getReplaceableFeeItems/:school/:studentId', function ($school,$studentId) {
    // Get all students replaceable fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount
                              FROM app.student_fee_items
                              INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true
                              WHERE student_id = :studentId
                              AND student_fee_items.active = true
                              AND replaceable = true
                              ORDER BY fee_item");
        $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentPayments/:school/:studentId', function ($school,$studentId) {
    // Get all students replaceable fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT payments.payment_id,
                payment_date,
                payment_method,
                amount,
                reversed,
                reversed_date,
                replacement_payment,
                slip_cheque_no,
                COALESCE(
                CASE WHEN replacement_payment = true THEN
                 (SELECT string_agg(fee_item || ' Replacement', ',')
                     FROM app.payment_replacement_items
                     INNER JOIN app.student_fee_items using (student_fee_item_id)
                     INNER JOIN app.fee_items using (fee_item_id)
                     WHERE payment_id = payments.payment_id
                     )
                 ELSE
                  (SELECT
                    string_agg(item, '<br>')
                  FROM (
                    select 'Inv #' || payment_inv_items.inv_id || ' (' || string_agg(fee_item, ', ' order by fee_item) || ')' as item
                     FROM app.payment_inv_items
                     INNER JOIN app.invoices on payment_inv_items.inv_id  = invoices.inv_id and canceled = false
                     INNER JOIN app.invoice_line_items using (inv_item_id)
                     INNER JOIN app.student_fee_items using (student_fee_item_id)
                     INNER JOIN app.fee_items using (fee_item_id)
                     WHERE payment_id = payments.payment_id
                     group by payment_inv_items.inv_id
                  ) q
                     )
                END, 'Credit') as applied_to,
               COALESCE((
                  amount - coalesce((select coalesce(sum(amount),0) as sum
                            from app.payment_inv_items
                            inner join app.invoices using (inv_id)
                            where payment_id = payments.payment_id
                            and canceled = false ),0)
                ),0) AS unapplied_amount
                FROM app.payments
                WHERE student_id = :studentID
                GROUP BY payments.payment_id");
        $sth->execute( array(':studentID' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentCredits/:school/:studentId', function ($school,$studentId) {
    //Show student's fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT credit_id, credits.amount, payment_date, credits.payment_id, payment_method, slip_cheque_no
                            FROM app.credits
                            INNER JOIN app.payments ON credits.payment_id = payments.payment_id
                            WHERE credits.student_id = :studentID
                            AND reversed is false
                            ORDER BY payment_date");
        $sth->execute( array(':studentID' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentArrears/:school/:studentId/:date', function ($school,$studentId,$date) {
    //Show student's fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("select sum(total_paid - total_amount) as balance
                              from (
                                select invoices.inv_id, invoices.total_amount, coalesce(sum(amount),0) as total_paid
                                from app.invoices
                                left join app.payment_inv_items
                                on invoices.inv_id = payment_inv_items.inv_id
                                WHERE student_id = :studentID
                                AND canceled = false
                                AND due_date <= :date
                                group by invoices.inv_id
                              ) q");
        $sth->execute( array(':studentID' => $studentId, ':date' => $date) );
        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results && $results->balance !== null && $results->balance < 0 ) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentClasses/:school/:studentId', function ($school,$studentId) {
    // Get all students classes, present and past

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT 1 as ord, student_id, class_id, class_name,
                                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 1') then true else false end as term_1,
                                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 2') then true else false end as term_2,
                                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 3') then true else false end as term_3
                              FROM app.students
                              INNER JOIN app.classes ON students.current_class = classes.class_id
                              WHERE student_id = :studentId
                              UNION
                              SELECT class_history_id as ord, student_id, student_class_history.class_id, class_name, true, true, true
                              FROM app.student_class_history
                              INNER JOIN app.classes ON student_class_history.class_id = classes.class_id
                              WHERE student_id = :studentId
                              AND student_class_history.class_id != (select current_class from app.students where student_id = :studentId)
                              ORDER BY ord");
        $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

/* ********** EXAMS API'S ********** */

$app->get('/getStudentExamMarksForApp/:school/:student_id/:class/:term(/:type)', function ($school,$studentId,$classId,$termId,$examTypeId=null) {
    //Get selected student's exam marks by exam type

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
    
        $queryArray = array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId);
		$query = "SELECT subject_name, (select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name
							  ,exam_id
								,exam_type
							  ,mark
							  ,grade_weight
							  ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade,
							  class_subject_exams.class_sub_exam_id, subjects.parent_subject_id
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams 
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects 
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId
						AND term_id = :termId
						AND student_id = :studentId
						";
						
		if( $examTypeId !== null )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId ";
			$queryArray[':examTypeId'] = $examTypeId; 
		}
		
		$query .= "ORDER BY subjects.sort_order, exam_types.exam_type_id ";
		
		$sth = $db->prepare($query);
		$sth->execute( $queryArray ); 
		
		$results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo json_encode(array('response' => 'success', 'data' => $results ));
			$db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getClassExamMarksForApp/:school/:class_id/:term_id/:exam_type_id(/:teacher_id)', function ($school, $classId, $termId, $examTypeId, $teacherId=null) {
  //Show exam marks for all students in class

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);
    
    $params = array(':classId' => $classId, ':termId' => $termId, ':examTypeId' => $examTypeId);
	$query = "SELECT q.student_id, student_name, subject_name, q.class_sub_exam_id, mark, exam_marks.term_id, grade_weight, sort_order, parent_subject_id, subject_id, is_parent
							FROM (
								SELECT  students.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, 
									subject_name, class_subject_exams.class_sub_exam_id, grade_weight, subjects.sort_order, parent_subject_id, subjects.subject_id,
									case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is null then false else true end as is_parent
								FROM app.students 
								INNER JOIN app.classes 
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										INNER JOIN app.class_subject_exams
										ON class_subjects.class_subject_id = class_subject_exams.class_subject_id
									ON classes.class_id = class_subjects.class_id
								ON students.current_class = classes.class_id
								WHERE current_class = :classId
								AND exam_type_id = :examTypeId
								AND students.active IS TRUE
							";
	if( $teacherId !== null )
	{
		$query .= "AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId) ";
		$params[':teacherId'] = $teacherId;
	}
		
	$query .= ") q
							LEFT JOIN app.exam_marks 
								INNER JOIN app.terms 
								ON exam_marks.term_id = terms.term_id
							ON q.class_sub_exam_id = exam_marks.class_sub_exam_id AND exam_marks.term_id = :termId AND exam_marks.student_id = q.student_id
							ORDER BY student_name, sort_order, subject_name";
		
	$sth = $db->prepare($query);
	$sth->execute($params); 
	$results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

} catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->get('/getAllTheStudentsExamMarks/:school/:class/:term/:type(/:teacherId)', function ($school,$classId,$termId,$examTypeId,$teacherId=null) {
  //Get all student exam marks

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $query = "select app.colpivot('_exam_marks', 'SELECT gender, first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
							 ,classes.class_id
							  ,subject_name
							  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
							  ,exam_type
							  ,exam_marks.student_id
							  ,mark
							  ,grade_weight
							  ,subjects.sort_order
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams 
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects 
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes
							ON class_subjects.class_id = classes.class_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						WHERE class_subjects.class_id = $classId
						AND term_id = $termId
						AND class_subject_exams.exam_type_id = $examTypeId
						AND subjects.use_for_grading is true
						AND students.active is true
						";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = $teacherId OR classes.teacher_id = $teacherId) ";
		}
		
		$query .= "	WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
						',
						array['gender','student_id','student_name','exam_type'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";
			
			$query2 = "select *,
									(
										SELECT rank FROM (
											SELECT
												student_id,
												total_mark,
												rank() over w as rank
											FROM (
												SELECT exam_marks.student_id, 
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams 
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects 
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true AND subjects.use_for_grading is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												INNER JOIN app.students
												ON exam_marks.student_id = students.student_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId
												AND class_subject_exams.exam_type_id = $examTypeId
												AND students.active is true
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)
										) q
										WHERE student_id = _exam_marks.student_id
									) as rank
									from _exam_marks order by exam_type, rank;";

			$sth1 = $db->prepare($query);
			$sth2 = $db->prepare($query2);
			
			$db->beginTransaction();
			$sth1->execute(); 
			$sth2->execute();
			$results = $sth2->fetchAll(PDO::FETCH_OBJ);
			$db->commit();

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }

  } catch(PDOException $e) {
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->post('/addExamMarks/:school', function ($school) {
    // Add exam marks
  
    $app = \Slim\Slim::getInstance();
    
    $allPostVars = json_decode($app->request()->getBody(),true);

    $examMarks =	( isset($allPostVars['exam_marks']) ? $allPostVars['exam_marks']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = setDBConnection($school);

    $getExam = $db->prepare("SELECT exam_id FROM app.exam_marks WHERE student_id = :studentId AND class_sub_exam_id = :classSubExamId AND term_id = :termId");
		
		$addMark = $db->prepare("INSERT INTO app.exam_marks(student_id, class_sub_exam_id, term_id, mark, created_by) 
								VALUES(:studentId, :classSubExamId, :termId, :mark, :userId)"); 
		
		$updateMark = $db->prepare("UPDATE app.exam_marks
									SET mark = :mark,
										modified_date = now(),
										modified_by = :userId
									WHERE exam_id = :examId"); 
									
		$deleteMark = $db->prepare("DELETE FROM app.exam_marks WHERE exam_id = :examId");
		
		$db->beginTransaction();
		
		if( count($examMarks) > 0 )
		{
			foreach($examMarks as $mark)
			{
				$studentId = ( isset($mark['student_id']) ? $mark['student_id']: null);				
				$classSubExamId = ( isset($mark['class_sub_exam_id']) ? $mark['class_sub_exam_id']: null);
				$termId = ( isset($mark['term_id']) ? $mark['term_id']: null);
				$mark = ( isset($mark['mark']) && !empty($mark['mark']) ? $mark['mark']: null);
				
				$currentExamMarks = $getExam->execute(array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId ));
				$examId = $getExam->fetch(PDO::FETCH_OBJ);
				
				if( $examId )
				{
					$updateMark->execute( array(':mark' => $mark, ':examId' => $examId->exam_id, ':userId' => $userId  ) );
				}
				else
				{
					$addMark->execute( array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId, ':mark' => $mark, ':userId' => $userId ) );
				}
			}
		}
		
		$db->commit();
		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "code" => 1));
		$db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

/* ********** FEE API'S ********** */

$app->get('/getFeeItemsForApp/:school(/:status)', function ($school,$status = true) {
  //Show fee items

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);
    
    $sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount, frequency, active, class_cats_restriction, optional, new_student_only, replaceable
              FROM app.fee_items
              WHERE active = :status
              AND optional is false
              ORDER BY fee_item_id");
        $sth->execute( array(':status' => $status) );
        $requiredItems = $sth->fetchAll(PDO::FETCH_OBJ);

    $results = new stdClass();
    $results->required_items = $requiredItems;

    $sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount,
                CASE WHEN fee_item = 'Transport' THEN
                  (select min(to_char(amount, '999,999,999.99')) || '-' || max(to_char(amount, '999,999,999.99')) from app.transport_routes where active is true)
                END as range,
                frequency, active, class_cats_restriction, optional, new_student_only, replaceable
              FROM app.fee_items
              WHERE active = :status
              AND optional is true
              ORDER BY fee_item_id");
        $sth->execute( array(':status' => $status) );
        $optionalItems = $sth->fetchAll(PDO::FETCH_OBJ);

    $results->optional_items = $optionalItems;

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

} catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->get('/getTansportRoutesForFees/:school(/:status)', function ($school,$status = true) {
  //Show transport routes and costs

  $app = \Slim\Slim::getInstance();

   try
   {
        $db = setDBConnection($school);
        
        $sth = $db->prepare("SELECT transport_id, route, amount
                  FROM app.transport_routes
                  WHERE active = :status
                  ORDER BY route");
        $sth->execute( array(':status' => $status) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
    
        if($results) {
          $app->response->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo json_encode(array('response' => 'success', 'data' => $results ));
          $db = null;
        } else {
          $app->response->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
          $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

?>
