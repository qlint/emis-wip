<?php
$app->post('/parentLogin', function () use($app) {
	// Log parent in
		$allPostVars = $app->request->post();
		$username = $allPostVars['user_name'];
		$pwd = $allPostVars['user_pwd'];
		
		//$hash = password_hash($pwd, PASSWORD_BCRYPT);
	 
		try 
		{
			$db = getLoginDB();
			$sth = $db->prepare("SELECT parents.parent_id, username, active, first_name, middle_name, last_name, email, 
										first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name
									FROM parents
									INNER JOIN parent_students ON parents.parent_id = parent_students.parent_id
									WHERE username= :username 
									AND password = :password 
									AND active is true");
			$sth->execute( array(':username' => $username, ':password' => $pwd) );
	 
			$result = $sth->fetch(PDO::FETCH_OBJ);

			if($result) {
				// get the parents students and add to result
				$sth2 = $db->prepare("SELECT student_id, subdomain, dbusername, dbpassword FROM parent_students WHERE parent_id = :parentId");
				$sth2->execute(array(':parentId' => $result->parent_id));
				$students = $sth2->fetchAll(PDO::FETCH_OBJ);
				$db = null;
				
				$studentDetails = Array();
				$curSubDomain = '';
				foreach( $students as $student )
				{
					// get individual student details
					// only get new db connection if different subdomain
					if( $curSubDomain != $student->subdomain ) 
					{
						if( $db !== null ) $db = null;
						$db = setDBConnection($student->subdomain);
					}
					$sth3 = $db->prepare("SELECT student_id, first_name, middle_name, last_name, student_image,
												 first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
												 students.active, class_name, class_id,
												 (SELECT value FROM app.settings WHERE name = 'School Name') as school_name
											FROM app.students
											INNER JOIN app.classes ON students.current_class = classes.class_id AND classes.active is true 
											WHERE student_id = :studentId");
					$sth3->execute(array(':studentId' => $student->student_id));
					$details = $sth3->fetch(PDO::FETCH_OBJ);
					$details->school = $student->subdomain;
					$studentDetails[] = $details;
					$curSubDomain = $student->subdomain;
										
				}
				
				$result->students = $studentDetails;
				
				$app->response->setStatus(200);
				$app->response()->headers->set('Content-Type', 'application/json');
				$db = null;
				
				echo json_encode(array('response' => 'success', 'data' => $result ));
				
			} else {
				throw new PDOException('The username or password you have entered is incorrect.');
			}
	 
		} catch(PDOException $e) {
			$app->response()->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		}
});

$app->get('/getBlog/:school/:student_id(/:pageNumber)', function ($school, $studentId, $pageNumber=null) {
    // Get published blog posts associated with student for this current school year
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
		$db = setDBConnection($school);
		
		if( $pageNumber === null )
		{
			$sth = $db->prepare("SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by, 
									feature_image, blog_posts.modified_date,
									class_name, blogs.class_id, blogs.blog_name
							 FROM app.blogs 
								INNER JOIN app.blog_posts 
									INNER JOIN app.employees 
									ON blog_posts.created_by = employees.emp_id
									LEFT JOIN app.blog_post_types
									ON blog_posts.post_type_id = blog_post_types.post_type_id
									INNER JOIN app.blog_post_statuses
									ON blog_posts.post_status_id = blog_post_statuses.post_status_id
								ON blogs.blog_id = blog_posts.blog_id			
								INNER JOIN app.classes 
								ON blogs.class_id = classes.class_id
							  INNER JOIN app.students ON blogs.class_id = students.current_class
							  WHERE student_id = :studentId
							  AND blog_posts.post_status_id = 1
							 -- AND blog_posts.post_type_id = 1
							  AND blog_posts.creation_date > (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year') ORDER BY end_date desc LIMIT 1 )  
								AND blog_posts.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) 
							  ORDER BY blog_posts.creation_date desc
							  ");
			$sth->execute( array(':studentId' => $studentId) ); 
			$results = $sth->fetchAll(PDO::FETCH_OBJ);
		}
		else
		{
			// need pagination
			$limit = 3;
			$offset = ($pageNumber * $limit) - $limit;
			$sth1 = $db->prepare("SELECT count(post_id) as num_posts
							 FROM app.blogs 
							 INNER JOIN app.blog_posts ON blogs.blog_id = blog_posts.blog_id			
							 INNER JOIN app.classes ON blogs.class_id = classes.class_id
							 INNER JOIN app.students ON blogs.class_id = students.current_class
							 WHERE student_id = :studentId
							 AND blog_posts.post_status_id = 1
							-- AND blog_posts.post_type_id = 1
							 AND blog_posts.creation_date > (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year') ORDER BY end_date desc LIMIT 1 )  
								AND blog_posts.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) 
							  ");
							  
			$sth2 = $db->prepare("SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by, 
									feature_image, blog_posts.modified_date,
									class_name, blogs.class_id, blogs.blog_name
							 FROM app.blogs 
								INNER JOIN app.blog_posts 
									INNER JOIN app.employees 
									ON blog_posts.created_by = employees.emp_id
									LEFT JOIN app.blog_post_types
									ON blog_posts.post_type_id = blog_post_types.post_type_id
									INNER JOIN app.blog_post_statuses
									ON blog_posts.post_status_id = blog_post_statuses.post_status_id
								ON blogs.blog_id = blog_posts.blog_id			
								INNER JOIN app.classes 
								ON blogs.class_id = classes.class_id
							  INNER JOIN app.students ON blogs.class_id = students.current_class
							  WHERE student_id = :studentId
							  AND blog_posts.post_status_id = 1
							  --AND blog_posts.post_type_id = 1
							  AND blog_posts.creation_date > (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year') ORDER BY end_date desc LIMIT 1 )  
								AND blog_posts.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) 
							  ORDER BY blog_posts.creation_date desc
							  OFFSET :offset LIMIT :limit
							  ");
							  
			$db->beginTransaction();
			$sth1->execute( array(':studentId' => $studentId) );
			$sth2->execute( array(':studentId' => $studentId, ':offset' => $offset, ':limit' => $limit) ); 
			$db->commit();
			
			$count = $sth1->fetch(PDO::FETCH_OBJ);
			$posts = $sth2->fetchAll(PDO::FETCH_OBJ);
			
			$results = new stdClass();
			$results->count = $count->num_posts;
			$results->posts = $posts;
		}
 
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

$app->get('/getHomework/:school/:student_id', function ($school, $studentId) {
    // Get homework associated with student for current week
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
		$db = setDBConnection($school);
		$sth = $db->prepare("SELECT homework_id, assigned_date, title, body, homework.post_status_id, post_status,
									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by, 
									attachment, homework.modified_date,
									class_name, class_subjects.class_id, due_date, subject_name
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
								WHERE student_id = :studentId
								AND homework.post_status_id = 1
								AND (homework.creation_date > (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year') ORDER BY end_date desc LIMIT 1 )  
									AND homework.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) )
								AND assigned_date between date_trunc('week', now())::date	and (date_trunc('week', now())+ '6 days'::interval)::date
								ORDER BY homework.assigned_date, subjects.sort_order
							  ");
		$sth->execute( array(':studentId' => $studentId) ); 
		$results = $sth->fetchAll(PDO::FETCH_OBJ);
		/*
        $sth = $db->prepare("SELECT homework_date, description
							  FROM app.homework
							  INNER JOIN app.students ON homework.class_id = students.current_class
							  WHERE student_id = :studentId
							  AND homework_date between date_trunc('week', now())::date	and (date_trunc('week', now())+ '6 days'::interval)::date
							  ORDER BY homework_date asc
							  ");
		$sth->execute( array(':studentId' => $studentId) ); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
		*/
 
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
