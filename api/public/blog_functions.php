<?php
$app->get('/getClassPosts/:class_id/:status', function ($classId, $status) {
    // Get all posts for class for current school year
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$params = array(':classId' => $classId);
		$query = "SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as posted_by, feature_image,
									blog_posts.modified_date, class_name, blogs.class_id
							FROM app.blogs 
							INNER JOIN app.blog_posts 
								INNER JOIN app.employees 
								ON blog_posts.created_by = employees.emp_id
								LEFT JOIN app.blog_post_types
								ON blog_posts.post_type_id = blog_post_types.post_type_id
								INNER JOIN app.blog_post_statuses
								ON blog_posts.post_status_id = blog_post_statuses.post_status_id
							ON blogs.blog_id = blog_posts.blog_id			
							INNER JOIN app.classes ON blogs.class_id = classes.class_id
							WHERE blogs.class_id = :classId 
							AND blog_posts.creation_date > coalesce((select end_date from app.terms 
																	where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year')
																	ORDER BY end_date desc LIMIT 1 ) , (select date_trunc('year',now()) ))
								AND blog_posts.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) 
							";
							
		if( $status != 'All' )
		{
			$query .= "AND blog_posts.post_status_id = :status ";
			$params[':status'] = $status;
		}
		
		$query .= " ORDER BY blog_posts.creation_date desc";
		
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
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getPost/:post_id', function ($postId) {
    // Get post
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as posted_by, feature_image, blog_posts.modified_date,
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
							WHERE post_id = :postId 
							");
		$sth->execute( array(':postId' => $postId) ); 
        $results = $sth->fetch(PDO::FETCH_OBJ);
 
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

$app->get('/getBlogPostTypes', function () {
    // Get blog post types
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT * FROM app.blog_post_types ORDER BY post_type");
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

$app->get('/getBlogPostStatuses', function () {
    // Get blog post types
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT * FROM app.blog_post_statuses ORDER BY post_status desc");
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

$app->post('/addBlog', function () use($app) {
    // Add blog
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$blogName = 	( isset($allPostVars['blog_name']) ? $allPostVars['blog_name']: null);
	$teacherId = 	( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$classId = 		( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.blogs(teacher_id, class_id, blog_name) 
            VALUES(:teacherId, :classId, :blogName)");
 		
		$sth2 = $db->prepare("SELECT currval('app.blogs_blog_id_seq') as blog_id");
		
		$db->beginTransaction();
		
		$sth->execute( array(':teacherId' => $teacherId, ':classId' => $classId, ':blogName' => $blogName ) );
		$sth2->execute();
		$blogId = $sth2->fetch(PDO::FETCH_OBJ);
		$db->commit();
 
		if( $blogId )
		{ 
			$result = $blogId->blog_id;
			$app->response->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo json_encode(array("response" => "success", "data" => $result));
			$db = null;
		}
		else
		{
			$app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => $e->getMessage() ));
            $db = null;
		}
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateBlog', function () use($app) {
    // Update blog
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$blogId = 		( isset($allPostVars['blog_id']) ? $allPostVars['blog_id']: null);
	$blogName = 	( isset($allPostVars['blog_name']) ? $allPostVars['blog_name']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.blogs
			SET blog_name = :blogName
            WHERE blog_id = :blogId");
 
        $sth->execute( array(':blogName' => $blogName, ':blogId' => $blogId) );
 
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

$app->post('/addPost', function () use($app) {
    // Add blog post
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$blogId = 		( isset($allPostVars['blog_id']) ? $allPostVars['blog_id']: null);
	$title = 		( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
	$body = 		( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
	$postStatusId =	( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
	$postTypeId =	( isset($allPostVars['post']['post_type_id']) ? $allPostVars['post']['post_type_id']: null);
	$featureImage =	( isset($allPostVars['post']['feature_image']) ? $allPostVars['post']['feature_image']: null);
	$postedBy =		( isset($allPostVars['post']['posted_by']) ? $allPostVars['post']['posted_by']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.blog_posts(blog_id, created_by, body, title, post_status_id, feature_image, post_type_id) 
            VALUES(:blogId, :postedBy, :body, :title, :postStatusId, :featureImage, :postTypeId)");

		$sth->execute( array(':blogId' => $blogId, ':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId , 
							':featureImage' => $featureImage , ':postedBy' => $postedBy, ':postTypeId' => $postTypeId,
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

$app->put('/updatePost', function () use($app) {
    // Update post
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$postId = 		( isset($allPostVars['post']['post_id']) ? $allPostVars['post']['post_id']: null);
	$title = 		( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
	$body = 		( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
	$postStatusId =	( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
	$postTypeId =	( isset($allPostVars['post']['post_type_id']) ? $allPostVars['post']['post_type_id']: null);
	$featureImage =	( isset($allPostVars['post']['feature_image']) ? $allPostVars['post']['feature_image']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.blog_posts
			SET title = :title,
				body = :body,
				post_status_id = :postStatusId,
				post_type_id = :postTypeId,
				feature_image = :featureImage,
				modified_date = now(),
				modified_by = :userId
            WHERE post_id = :postId");
 
        $sth->execute( array(':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId, ':postTypeId' => $postTypeId,
							 ':featureImage' => $featureImage, ':userId' => $userId, ':postId' => $postId,
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

$app->delete('/deletePost/:post_id', function ($postId) {
    // delete post
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.blog_posts WHERE post_id = :postId");
		$sth->execute( array(':postId' => $postId) );
 
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

$app->get('/getHomeworkPosts/:status/:class_subject_id(/:class_id/:teacher_id)', function ($status, $classSubjectId, $classId = null, $teacherId = null) {
    // Get all homework for class for current school year
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
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
								WHERE (homework.creation_date > coalesce((select end_date from app.terms 
																			where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year')
																			ORDER BY end_date desc LIMIT 1 ) , (select date_trunc('year',now()) ))
									AND homework.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) )
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
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getHomeworkPost/:post_id', function ($postId) {
    // Get post
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
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
							WHERE homework_id = :postId 
							");
		$sth->execute( array(':postId' => $postId) ); 
        $results = $sth->fetch(PDO::FETCH_OBJ);
 
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

$app->post('/addHomework', function () use($app) {
    // Add homework post
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classSubjectId = ( isset($allPostVars['class_subject_id']) ? $allPostVars['class_subject_id']: null);
	$title = 		( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
	$body = 		( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
	$postStatusId =	( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
	$attachment =	( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
	$dueDate	 =	( isset($allPostVars['post']['due_date']) ? $allPostVars['post']['due_date']: null);
	$assignedDate =	( isset($allPostVars['post']['assigned_date']) ? $allPostVars['post']['assigned_date']: null);
	$postedBy =		( isset($allPostVars['post']['posted_by']) ? $allPostVars['post']['posted_by']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.homework(class_subject_id, created_by, body, title, post_status_id, attachment, due_date, assigned_date) 
            VALUES(:classSubjectId, :postedBy, :body, :title, :postStatusId, :attachment, :dueDate, :assignedDate)");

		$sth->execute( array(':classSubjectId' => $classSubjectId, ':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId , 
							':attachment' => $attachment , ':postedBy' => $postedBy, ':dueDate' => $dueDate, ':assignedDate' => $assignedDate,
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

$app->put('/updateHomework', function () use($app) {
    // Update homework
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$homeworkId = 	( isset($allPostVars['post']['post_id']) ? $allPostVars['post']['post_id']: null);
	$title = 		( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
	$body = 		( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
	$postStatusId =	( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
	$attachment =	( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
	$dueDate	 =	( isset($allPostVars['post']['due_date']) ? $allPostVars['post']['due_date']: null);
	$assignedDate =	( isset($allPostVars['post']['assigned_date']) ? $allPostVars['post']['assigned_date']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
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

$app->delete('/deleteHomework/:homework_id', function ($homeworkId) {
    // delete homework
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.homework WHERE homework_id = :homeworkId");
		$sth->execute( array(':homeworkId' => $homeworkId) );
 
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

$app->get('/getCommunicationOptions', function () {
    // Get communication types and audiences
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth1 = $db->prepare("SELECT * FROM app.communication_types ORDER BY com_type");
		$sth2 = $db->prepare("SELECT * FROM app.communication_audience ORDER BY audience");
		$sth1->execute(); 
		$sth2->execute(); 
        $types = $sth1->fetchAll(PDO::FETCH_OBJ);
		$audiences = $sth2->fetchAll(PDO::FETCH_OBJ);
		
		$results = new stdClass();
		$results->com_types = $types;
		$results->audiences = $audiences;
 
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

$app->get('/getTeacherCommunications/:teacherId', function ($teacherId) {
    // Get all communications for teacher for current school year
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();

        $sth = $db->prepare( "SELECT com_id as post_id, com_date, communications.creation_date, communications.com_type_id, com_type, subject, message, send_as_email, send_as_sms, 
									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
									communications.audience_id, audience, attachment, reply_to,
									students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
									guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
									communications.guardian_id, communications.student_id, classes.class_name, communications.class_id, communications.post_status_id, post_status,
									sent, sent_date, message_from
							FROM app.communications 	
							LEFT JOIN app.students ON communications.student_id = students.student_id
							LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
							LEFT JOIN app.classes ON communications.class_id = classes.class_id
							INNER JOIN app.employees ON communications.message_from = employees.emp_id							
							INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
							INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
							INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
							WHERE message_from = :teacherId
							AND communications.creation_date > coalesce((select end_date from app.terms 
																where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year')
																ORDER BY end_date desc LIMIT 1 ) , (select date_trunc('year',now()) ))
								AND communications.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) 
							ORDER BY communications.creation_date desc" );
		$sth->execute( array(':teacherId' => $teacherId) ); 
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

$app->get('/getSchoolCommunications', function () {
    // Get all communications for current school year
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();

        $sth = $db->prepare( "SELECT com_id as post_id, com_date, communications.creation_date, communications.com_type_id, com_type, subject, message, send_as_email, send_as_sms, 
									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
									communications.audience_id, audience, attachment, reply_to,
									students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
									guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
									communications.guardian_id, communications.student_id, classes.class_name, communications.class_id,communications.post_status_id, post_status,
									sent, sent_date, message_from
							FROM app.communications 	
							LEFT JOIN app.students ON communications.student_id = students.student_id
							LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
							LEFT JOIN app.classes ON communications.class_id = classes.class_id
							INNER JOIN app.employees ON communications.message_from = employees.emp_id							
							INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
							INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
							INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
							WHERE communications.creation_date > coalesce((select end_date from app.terms 
																			where date_trunc('year', end_date) = date_trunc('year', now() - interval '1 year')
																			ORDER BY end_date desc LIMIT 1 ) , (select date_trunc('year',now()) ))
								AND communications.creation_date <= (select end_date from app.terms where date_trunc('year', end_date) = date_trunc('year', now()) ORDER BY end_date desc LIMIT 1 ) 
							ORDER BY communications.creation_date desc" );
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

$app->post('/addCommunication', function () use($app) {
    // Add communication
	
	$allPostVars = json_decode($app->request()->getBody(),true);

	$subject = 		( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
	$message = 		( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
	$attachment =	( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
	$audienceId =	( isset($allPostVars['post']['audience_id']) ? $allPostVars['post']['audience_id']: null);
	$comTypeId =	( isset($allPostVars['post']['com_type_id']) ? $allPostVars['post']['com_type_id']: null);
	$studentId	 =	( isset($allPostVars['post']['student_id']) ? $allPostVars['post']['student_id']: null);
	$guardianId =	( isset($allPostVars['post']['guardian_id']) ? $allPostVars['post']['guardian_id']: null);
	$classId =		( isset($allPostVars['post']['class_id']) ? $allPostVars['post']['class_id']: null);
	$sendAsEmail =	( isset($allPostVars['post']['send_as_email']) ? $allPostVars['post']['send_as_email']: 'f');
	$sendAsSms =	( isset($allPostVars['post']['send_as_sms']) ? $allPostVars['post']['send_as_sms']: 'f');
	$replyTo =		( isset($allPostVars['post']['reply_to']) ? $allPostVars['post']['reply_to']: null);
	$postStatus =	( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
	$messageFrom =	( isset($allPostVars['post']['message_from']) ? $allPostVars['post']['message_from']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.communications(com_date, audience_id, com_type_id, subject, message, attachment, message_from, student_id, guardian_id, class_id, send_as_email, send_as_sms, created_by, reply_to, post_status_id) 
							 VALUES(now(), :audienceId, :comTypeId, :subject, :message, :attachment, :messageFrom, :studentId, :guardianId, :classId, :sendAsEmail, :sendAsSms, :userId, :replyTo, :postStatus)");

		$sth->execute( array(':audienceId' => $audienceId, ':comTypeId' => $comTypeId, ':subject' => $subject, ':message' => $message , 
							':attachment' => $attachment , ':userId' => $userId, ':studentId' => $studentId, ':guardianId' => $guardianId,
							':classId' => $classId, ':sendAsEmail' => $sendAsEmail, ':sendAsSms' => $sendAsSms, ':replyTo' => $replyTo,
							':messageFrom' => $messageFrom, ':postStatus' => $postStatus) );
 
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

$app->get('/getCommunication/:post_id', function ($postId) {
    // Get communication
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT com_id as post_id, com_date, communications.creation_date, communications.com_type_id, com_type, subject, message, send_as_email, send_as_sms, 
									employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
									communications.audience_id, audience, attachment, reply_to,
									students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
									guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
									communications.guardian_id, communications.student_id, classes.class_name, communications.class_id, communications.post_status_id, post_status,
									sent, sent_date, message_from
							FROM app.communications 	
							LEFT JOIN app.students ON communications.student_id = students.student_id
							LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
							LEFT JOIN app.classes ON communications.class_id = classes.class_id
							INNER JOIN app.employees ON communications.message_from = employees.emp_id							
							INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
							INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
							INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
							WHERE com_id = :postId 
							");
		$sth->execute( array(':postId' => $postId) ); 
        $results = $sth->fetch(PDO::FETCH_OBJ);
 
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

$app->put('/updateCommunication', function () use($app) {
    // Update communication
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$comId = 		( isset($allPostVars['post']['post_id']) ? $allPostVars['post']['post_id']: null);
	$subject = 		( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
	$message = 		( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
	$postStatusId =	( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
	$attachment =	( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
	$replyTo =		( isset($allPostVars['post']['reply_to']) ? $allPostVars['post']['reply_to']: null);
	$messageFrom =	( isset($allPostVars['post']['message_from']) ? $allPostVars['post']['message_from']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.communications
			SET subject = :subject,
				message = :message,
				message_from = :messageFrom,
				post_status_id = :postStatusId,
				attachment = :attachment,
				reply_to = :replyTo,
				modified_date = now(),
				modified_by = :userId
            WHERE com_id = :comId");
 
        $sth->execute( array(':subject' => $subject, ':message' => $message, ':postStatusId' => $postStatusId,
							 ':attachment' => $attachment, ':userId' => $userId, ':comId' => $comId,
							 ':replyTo' => $replyTo, ':messageFrom' => $messageFrom) );
 
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

$app->delete('/deleteCommunication/:com_id', function ($comId) {
    // delete communication
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.communications WHERE com_id = :comId");
		$sth->execute( array(':comId' => $comId) );
 
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

?>
