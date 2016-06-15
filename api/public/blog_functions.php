<?php
$app->get('/getClassPosts/:class_id/:status', function ($classId, $status) {
    // Get all posts for class
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$params = array(':classId' => $classId);
		$query = "SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as posted_by, feature_image,
									blog_posts.modified_date, class_name 
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
							";
							
		if( $status == 'All' )
		{
			$query .= "AND blog_posts.post_status_id in (1,2)";
		}
		else
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
	$featureImage =	( isset($allPostVars['post']['feature_image']) ? $allPostVars['post']['feature_image']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.blog_posts(blog_id, created_by, body, title, post_status_id, feature_image) 
            VALUES(:blogId, :userId, :body, :title, :postStatusId, :featureImage)");

		$sth->execute( array(':blogId' => $blogId, ':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId , ':featureImage' => $featureImage , ':userId' => $userId  ) );
 
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
    // Update user
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$postId = 		( isset($allPostVars['post']['post_id']) ? $allPostVars['post']['post_id']: null);
	$title = 		( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
	$body = 		( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
	$postStatusId =	( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
	$featureImage =	( isset($allPostVars['post']['feature_image']) ? $allPostVars['post']['feature_image']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.blog_posts
			SET title = :title,
				body = :body,
				post_status_id = :postStatusId,
				feature_image = :featureImage,
				modified_date = now(),
				modified_by = :userId
            WHERE post_id = :postId");
 
        $sth->execute( array(':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId,
							 ':featureImage' => $featureImage, ':userId' => $userId, ':postId' => $postId	) );
 
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


?>
