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
                AND date_trunc('year', blog_posts.creation_date) =  date_trunc('year', now())
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

$app->get('/getSmsDetails/:postId', function ($postId) {
  // Get all posts for class for current school year

    $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $params = array(':postId' => $postId);
        $query = "SELECT communication_sms.com_id, communication_sms.creation_date as message_date, communications.message as message_text,
                                            employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as message_by, communication_sms.first_name ||' ' || communication_sms.last_name AS recipient_name,
                                            communication_sms.sim_number AS phone_number
                                        FROM app.communication_sms
                                        INNER JOIN app.communications ON communication_sms.com_id = communications.com_id
                                        INNER JOIN app.employees ON communications.message_from = employees.emp_id
                                        WHERE communication_sms.com_id = :postId";

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

$app->post('/addCommViaAfricasTalking', function () use($app) {
  // Send message via Africa's talking

    $allPostVars = json_decode($app->request()->getBody(),true);
    $accountId =   ( isset($allPostVars['account_id']) ? $allPostVars['account_id']: null);
    $recipients =  ( isset($allPostVars['recipients']) ? $allPostVars['recipients']: null);
    $msgText =    ( isset($allPostVars['messagetext']) ? $allPostVars['messagetext']: null);

    try
    {
        $db = getAfricasTalkingDB();
        $sth = $db->prepare("INSERT INTO sms.in_out(account_id, phone_number, messagetext, message_time)
                            VALUES(:accountId, :phoneNumber, :msgText, now())");

        $db->beginTransaction();

        foreach( $recipients as $recipient )
		{
			$phoneNumber = ( isset($recipient['phone_number']) ? $recipient['phone_number']: null);

			$sth->execute( array('accountId' => $accountId, ':phoneNumber' => $phoneNumber, ':msgText' => $msgText ) );
		}

        $db->commit();

        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "data" => "Message sent succcessfully!"));
        $db = null;


  } catch(PDOException $e) {
      $app->response()->setStatus(404);
        $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->post('/logToSmsServer', function () use($app) {
  // Log out going sms

    $allPostVars = json_decode($app->request()->getBody(),true);
    $msgTxt =   ( isset($allPostVars['message_text']) ? $allPostVars['message_text']: null);
    $msgBy =  ( isset($allPostVars['message_by']) ? $allPostVars['message_by']: null);
    $msgLen =    ( isset($allPostVars['message_length']) ? $allPostVars['message_length']: null);
    $pageCount =    ( isset($allPostVars['page_count']) ? $allPostVars['page_count']: null);
    $subscriberId =    ( isset($allPostVars['subscriber_id']) ? $allPostVars['subscriber_id']: null);
    $token =    ( isset($allPostVars['token']) ? $allPostVars['token']: null);
    $recipients =    ( isset($allPostVars['recipients']) ? $allPostVars['recipients']: null);

    try
    {
        $db = getSmsDB();
        $sth = $db->prepare("INSERT INTO public.messages(token, message_date, message_by, message_length, page_count, subscriber_id, message_text)
                              VALUES (:token, now(), :msgBy, :msgLen, :pageCount, :subscriberId, :msgTxt) returning message_id;");
        $sth->execute( array(':token' => $token, ':msgBy' => $msgBy, ':msgLen' => $msgLen, ':pageCount' => $pageCount, ':subscriberId' => $subscriberId, ':msgTxt' => $msgTxt ) );
        $msgId = $sth->fetch(PDO::FETCH_OBJ);
        $msgId = $msgId->message_id;

        $db->beginTransaction();

        foreach( $recipients as $recipient )
        {
          $phoneNumber = ( isset($recipient['MSISDN']) ? "+".$recipient['MSISDN']: null);
          $apiMessageId = ( isset($recipient['MessageID']) ? $recipient['MessageID'] : null);
          $respCode = ( isset($recipient['ResponseCode']) ? $recipient['ResponseCode'] : null);
          $name = ( isset($recipient['recipient_name']) ? $recipient['recipient_name'] : null);

          $sth2 = $db->prepare("INSERT INTO public.recipients(message_id, phone_number, recipient_name, status_code, api_messageid)
          VALUES (:msgId, :phoneNumber, :name, :respCode, :apiMessageId);");
          $sth2->execute( array(':msgId' => $msgId, ':phoneNumber' => $phoneNumber, ':name' => $name, ':respCode' => $respCode, ':apiMessageId' => $apiMessageId ) );
        }

        $db->commit();

        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "data" => "Message sent succcessfully!"));
        $db = null;


  } catch(PDOException $e) {
      $app->response()->setStatus(404);
        $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->post('/logFailedSms', function () use($app) {
  // log failed sms for reference purposes

    $allPostVars = json_decode($app->request()->getBody(),true);
    $messageBy = ( isset($allPostVars['message_by']) ? $allPostVars['message_by']: null);
    $messageDate = ( isset($allPostVars['message_date']) ? $allPostVars['message_date']: null);
    $msgText = ( isset($allPostVars['message_text']) ? $allPostVars['message_text']: null);
    $subscriberName = ( isset($allPostVars['subscriber_name']) ? $allPostVars['subscriber_name']: null);
    $messageRecipients = ( isset($allPostVars['message_recipients']) ? $allPostVars['message_recipients']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("INSERT INTO app.communications_failed_sms(subscriber_name, message_by, message_text, recipient_name, phone_number, message_date)
                            VALUES (:subscriberName, :messageBy, :msgText, :recipientName, :phoneNumber, :messageDate);");

        $db->beginTransaction();

        foreach( $messageRecipients as $recipient )
        {
          $phoneNumber = ( isset($recipient['phone_number']) ? $recipient['phone_number']: null);
          $recipientName = ( isset($recipient['recipient_name']) ? $recipient['recipient_name']: null);

          $sth->execute( array(
                                ':messageBy' => $messageBy,
                                ':messageDate' => $messageDate,
                                ':msgText' => $msgText,
                                ':subscriberName' => $subscriberName,
                                ':phoneNumber' => $phoneNumber,
                                ':recipientName' => $recipientName
                              ) );
        }

        $db->commit();

        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "data" => "Message sent succcessfully!"));
        $db = null;

  } catch(PDOException $e) {
      $app->response()->setStatus(404);
        $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getComUserToken/:subdomain', function ($subdomain) {
    //Return token if user exists

	$app = \Slim\Slim::getInstance();

    try
    {
      $db = getSmsDB();

			$sth = $db->prepare("SELECT * FROM subscribers WHERE subscriber_name = :subdomain");
			$sth->execute(array(':subdomain' => $subdomain));

      $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {
          $usrNme = $results->user_name;
          $pwd = $results->password;
          $token = md5($pwd);
          $source = $results->source;
          $subscriberId = $results->subscriber_id;

          $data = new stdClass();
          $data->user_name = $usrNme;
          $data->token = $token;
          $data->source = $source;
          $data->timestamp = date("d") . date("m") . date("Y") . date("h") . date("i") . date("s");
          $data->subscriber_id = $subscriberId;

          $app->response->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo json_encode(array('response' => 'success', 'data' => $data ));
          $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'SMS messages canno be sent from this account. Contact EDUWEB to enable this feature.' ));
            $db = null;
        }

    } catch(PDOException $e) {
      $app->response()->setStatus(404);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addBlog', function () use($app) {
  // Add blog

  $allPostVars = json_decode($app->request()->getBody(),true);
  $blogName =   ( isset($allPostVars['blog_name']) ? $allPostVars['blog_name']: null);
  $teacherId =  ( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
  $classId =    ( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);

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
$blogId =     ( isset($allPostVars['blog_id']) ? $allPostVars['blog_id']: null);
$blogName =   ( isset($allPostVars['blog_name']) ? $allPostVars['blog_name']: null);
$userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

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
  $blogId =     ( isset($allPostVars['blog_id']) ? $allPostVars['blog_id']: null);
  $title =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
  $body =     ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
  $postStatusId = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
  $postTypeId = ( isset($allPostVars['post']['post_type_id']) ? $allPostVars['post']['post_type_id']: null);
  $featureImage = ( isset($allPostVars['post']['feature_image']) ? $allPostVars['post']['feature_image']: null);
  $postedBy =   ( isset($allPostVars['post']['posted_by']) ? $allPostVars['post']['posted_by']: null);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = getDB();

    $sth = $db->prepare("INSERT INTO app.blog_posts(blog_id, created_by, body, title, post_status_id, feature_image, post_type_id)
                          VALUES(:blogId, :postedBy, :body, :title, :postStatusId, :featureImage, :postTypeId)");

    $sth->execute( array(':blogId' => $blogId, ':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId ,
      ':featureImage' => $featureImage , ':postedBy' => $postedBy, ':postTypeId' => $postTypeId,
    ));

    if( $postStatusId === 1 )
    {
      // get class name
      $className = $db->prepare("SELECT class_name
                                FROM app.classes
                                INNER JOIN app.blogs
                                ON classes.class_id = blogs.class_id
                              WHERE blog_id = :blogId");
      $className->execute(array(':blogId' => $blogId));
      $classNameResult = $className->fetch(PDO::FETCH_OBJ);
     // var_dump($classNameResult);

      $studentsInClass = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = (select class_id from app.blogs where blog_id = :blogId)");
      $studentsInClass->execute( array(':blogId' => $blogId));
      $results = $studentsInClass->fetchAll(PDO::FETCH_OBJ);
      $studentIds = array();
      foreach($results as $result) {
        $studentIds[] = $result->student_id;
      }
      $studentIdStr = '{' . implode(',', $studentIds) . '}';

      $db = null;

      // blog was published, need to add entry for notifications
      $db = getMISDB();
      $subdomain = getSubDomain();
      $message = "New blog post for " . $classNameResult->class_name . '! ' . $title;

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

    $db = null;
    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));

  }
  catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/updatePost', function () use($app) {
  // Update post

  $allPostVars = json_decode($app->request()->getBody(),true);
  $postId =     ( isset($allPostVars['post']['post_id']) ? $allPostVars['post']['post_id']: null);
  $title =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
  $body =     ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
  $postStatusId = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
  $postTypeId = ( isset($allPostVars['post']['post_type_id']) ? $allPostVars['post']['post_type_id']: null);
  $featureImage = ( isset($allPostVars['post']['feature_image']) ? $allPostVars['post']['feature_image']: null);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = getDB();

    if( $postStatusId === 1 )
    {
      // check the current post status
      // if already published, send an notification that blog post was updated
      // if being updated to published, send notification of new blog blog

      $check = $db->prepare("SELECT post_status_id FROM app.blog_posts WHERE post_id = :postId");
      $check->execute(array(':postId' => $postId));
      $checkResult = $check->fetch(PDO::FETCH_OBJ);
    }

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
    ));

    if( $postStatusId === 1 )
    {
      // get class name
      $className = $db->prepare("SELECT class_name
                                FROM app.classes
                                INNER JOIN app.blogs ON classes.class_id = blogs.class_id
                                INNER JOIN app.blog_posts ON blogs.blog_id = blog_posts.blog_id
                              WHERE post_id = :postId");
      $className->execute(array(':postId' => $postId));
      $classNameResult = $className->fetch(PDO::FETCH_OBJ);

      $studentsInClass = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = (select class_id
                                                                from app.blog_posts
                                                                inner join app.blogs on blog_posts.blog_id = blogs.blog_id
                                                                where post_id = :postId)");
      $studentsInClass->execute( array(':postId' => $postId));
      $results = $studentsInClass->fetchAll(PDO::FETCH_OBJ);

      $studentIds = array();
      foreach($results as $result) {
        $studentIds[] = $result->student_id;
      }
      $studentIdStr = '{' . implode(',', $studentIds) . '}';

      $db = null;

      $db = getMISDB();
      $subdomain = getSubDomain();

      // blog was published, need to add entry for notifications
      if( $checkResult->post_status_id == 2 )
      {
        // previously a draft, now publishing
        $message = "New blog post for " . $classNameResult->class_name . '! ' . $title;
      }
      else{
        // previously published, updating
        $message = "Blog post for " . $classNameResult->class_name . ' updated! ' . $title;
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

    $db = null;
    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));

  }
  catch(PDOException $e) {
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
                class_name, class_subjects.class_id, due_date, subject_name, homework.class_subject_id, homework.creation_date,
                seen_count AS overall_seen_count, seen_by AS overall_seen_by,/*,
                array_to_json(
                  ARRAY(
          					SELECT
          						student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name
          					FROM app.students
          					WHERE student_id = ANY(string_to_array(seen_by,',')::int[])
          				)
                ) AS seen_by*/
                (
                  SELECT array_to_json(array_agg(row_to_json(a)))
                  FROM (
                        SELECT
                  		student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name
                  	  FROM app.students
                      WHERE student_id = ANY(string_to_array(seen_by,',')::int[])
                  ) a
                ) AS seen_by,
                (
                  SELECT array_to_json(array_agg(row_to_json(a)))
                  FROM (
                        SELECT
                  		student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name
                  	  FROM app.students
                      WHERE student_id = ANY(string_to_array(students,',')::int[])
                  ) a
                ) AS sent_to,
                CASE WHEN students IS NULL THEN FALSE ELSE TRUE END AS student_specific
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
    $query .= "AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId OR homework.created_by = :teacherId) ";
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

$app->get('/getHomeworkFeedback(/:empId)', function ($empId = null) {
  // Get all homework feedback

$app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $params = array();
    $query = "SELECT hf.*,
                g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_name,
                s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                sg.relationship, c.teacher_id AS class_teacher, j.teacher_id AS subject_teacher,
                TO_CHAR(hf.creation_date :: DATE, 'dd/mm/yyyy') AS formated_date
              FROM app.homework_feedback hf
              INNER JOIN app.students s USING (student_id)
              INNER JOIN app.student_guardians sg ON hf.student_id = sg.student_id AND hf.guardian_id = sg.guardian_id
              INNER JOIN app.guardians g ON hf.guardian_id = g.guardian_id AND sg.guardian_id = g.guardian_id
              INNER JOIN app.classes c USING (class_id)
              INNER JOIN app.subjects j USING (subject_id)
            ";

  if( $empId !== '0' )
  {
    $query .= " WHERE (c.teacher_id = :empId OR j.teacher_id = :empId OR hf.emp_id = :empId) ";
    $params[':empId'] = $empId;
  }

  $query .= " ORDER BY homework_feedback_id DESC";

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

$app->get('/getAllTeacherComms(/:empId)', function ($empId = null) {
  // Get all communications by teachers

$app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $params = array();
    $query = "SELECT c.com_id, TO_CHAR(c.creation_date :: DATE, 'dd/mm/yyyy') AS creation_date, c.audience_id,
            		ca.audience, c.com_type_id, ct.com_type, c.subject, c.message, c.attachment, c.message_from,
            		e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as teacher_name, e.emp_image,
            		c.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
            		c.guardian_id, g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_name,
            		c.class_id, cl.class_name, send_as_email, send_as_sms, seen_count,
            		(
            			SELECT array_to_json(array_agg(row_to_json(a)))
            			FROM (
            				SELECT
            					student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name
            				FROM app.students
            				WHERE student_id = ANY(string_to_array(seen_by,',')::int[])
            			) a
            		) AS seen_by,
            		(
            			CASE WHEN send_as_sms IS TRUE THEN 'SMS'
            			ELSE 'APP'
            			END
            		) AS comm_mode
            FROM app.communications c
            INNER JOIN app.communication_audience ca USING (audience_id)
            INNER JOIN app.communication_types ct USING (com_type_id)
            INNER JOIN app.employees e ON c.message_from = e.emp_id
            INNER JOIN app.employee_cats ec ON e.emp_cat_id = ec.emp_cat_id
            LEFT JOIN app.students s USING (student_id)
            LEFT JOIN app.guardians g USING (guardian_id)
            LEFT JOIN app.classes cl USING (class_id)
            WHERE e.active IS TRUE AND LOWER(ec.emp_cat_name) = LOWER('TEACHING')
            ORDER BY c.com_id DESC";

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

$app->get('/getAllHomeworkFeedback(/:empId)', function ($empId = null) {
  // Get all homework feedback

$app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $params = array();
    $query = "SELECT hf.*,
                g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_name,
                s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                sg.relationship, c.teacher_id AS class_teacher, j.teacher_id AS subject_teacher,
                TO_CHAR(hf.creation_date :: DATE, 'dd/mm/yyyy') AS formated_date
              FROM app.homework_feedback hf
              INNER JOIN app.students s USING (student_id)
              INNER JOIN app.student_guardians sg ON hf.student_id = sg.student_id AND hf.guardian_id = sg.guardian_id
              INNER JOIN app.guardians g ON hf.guardian_id = g.guardian_id AND sg.guardian_id = g.guardian_id
              INNER JOIN app.classes c USING (class_id)
              INNER JOIN app.subjects j USING (subject_id)
              ORDER BY homework_feedback_id DESC
            ";

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
                              class_name, classes.class_id, homework.class_subject_id, subjects.subject_id, subject_name, assigned_date, due_date,
                              array_to_string(ARRAY(
                      					SELECT
                      						first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name
                      					FROM app.students
                      					WHERE student_id = ANY(string_to_array(seen_by,',')::int[])
                      				),',') AS seen_by,
                              array_to_string(ARRAY(
                      					SELECT
                      						first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name
                      					FROM app.students
                      					WHERE student_id = ANY(string_to_array(students,',')::int[])
                      				),',') AS sent_to,
                      				seen_count
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

$app->get('/getAllHomework', function () {
    //Show all resources

	$app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();

        $sth = $db->prepare("SELECT h.homework_id, cs.class_id, c.class_name, cs.subject_id, s.subject_name,
                            		e.first_name, e.middle_name, e.last_name,
                                    e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as teacher_name,
                                    h.title, h.body, h.attachment, h.students,
                            		TO_CHAR(h.assigned_date :: DATE, 'dd/mm/yyyy') AS assigned_date,
                            		TO_CHAR(h.due_date :: DATE, 'dd/mm/yyyy') AS due_date,
                            		TO_CHAR(h.creation_date :: DATE, 'dd/mm/yyyy') AS creation_date,
                                h.post_status_id, post_status AS status
                            FROM app.homework h
                            INNER JOIN app.class_subjects cs USING (class_subject_id)
                            INNER JOIN app.subjects s USING (subject_id)
                            INNER JOIN app.classes c USING (class_id)
                            INNER JOIN app.employees e ON h.created_by = e.emp_id
                            INNER JOIN app.blog_post_statuses USING (post_status_id)
                            ORDER BY homework_id DESC");
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

$app->post('/addHomework', function () use($app) {
  // Add homework post

  $allPostVars = json_decode($app->request()->getBody(),true);
  $classSubjectId = ( isset($allPostVars['class_subject_id']) ? $allPostVars['class_subject_id']: null);
  $title =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
  $body =     ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
  $postStatusId = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
  $attachment = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
  $dueDate   =  ( isset($allPostVars['post']['due_date']) ? $allPostVars['post']['due_date']: null);
  $assignedDate = ( isset($allPostVars['post']['assigned_date']) ? $allPostVars['post']['assigned_date']: null);
  $postedBy =   ( isset($allPostVars['post']['posted_by']) ? $allPostVars['post']['posted_by']: null);
  $students =    ( isset($allPostVars['students']) ? $allPostVars['students']: null);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = getDB();
    $sth = $db->prepare("INSERT INTO app.homework(class_subject_id, created_by, body, title, post_status_id, attachment, due_date, assigned_date, students)
                          VALUES(:classSubjectId, :postedBy, :body, :title, :postStatusId, :attachment, :dueDate, :assignedDate, :students)");

    $sth->execute( array(':classSubjectId' => $classSubjectId, ':title' => $title, ':body' => $body, ':postStatusId' => $postStatusId ,
            ':attachment' => $attachment , ':postedBy' => $postedBy, ':dueDate' => $dueDate, ':assignedDate' => $assignedDate, ':students' => $students
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

      if(isset($allPostVars['students'])){
          $studentsInClass = $db->prepare("SELECT student_id
                                            FROM app.students
                                            WHERE current_class = (select class_id
                                                                    from app.class_subjects
                                                                    where class_subject_id = :classSubjectId)
                                            AND student_id::text = ANY (string_to_array(:students,','))");
          $studentsInClass->execute( array(':classSubjectId' => $classSubjectId, ':students' => $students));
          $results = $studentsInClass->fetchAll(PDO::FETCH_OBJ);
      }else{
          $studentsInClass = $db->prepare("SELECT student_id
                                            FROM app.students
                                            WHERE current_class = (select class_id
                                                                    from app.class_subjects
                                                                    where class_subject_id = :classSubjectId)");
          $studentsInClass->execute( array(':classSubjectId' => $classSubjectId));
          $results = $studentsInClass->fetchAll(PDO::FETCH_OBJ);
      }

      $studentIds = array();
      foreach($results as $result) {
        $studentIds[] = $result->student_id;
      }
      $studentIdStr = '{' . implode(',', $studentIds) . '}';

      $db = null;

      // homework was published, need to add entry for notifications
      $db = getMISDB();
      $subdomain = getSubDomain();
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

    $db = null;
    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));



  }
  catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/updateHomework', function () use($app) {
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

  try
  {
    $db = getDB();
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


  }
  catch(PDOException $e) {
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
    $sth2 = $db->prepare("SELECT * FROM app.communication_audience WHERE module is null ORDER BY audience");
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

  }
  catch(PDOException $e) {
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
            AND date_trunc('year', communications.creation_date) =  date_trunc('year', now())
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

      $sth = $db->prepare( " SELECT communications.com_id as post_id, communications.com_date, communications.creation_date, communications.com_type_id, com_type, subject, message, send_as_email, send_as_sms,
                employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                communications.audience_id, audience, communications.attachment, reply_to,
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
            left join  app.communication_attachments ca on ca.com_id=communications.com_id
   group by communications.com_id,  com_date, communications.creation_date, communications.com_type_id, com_type, subject, message, send_as_email, send_as_sms, posted_by, communications.audience_id, audience, reply_to, student_name,
            parent_full_name, communications.guardian_id, communications.student_id, class_name, communications.class_id, communications.post_status_id, post_status, sent, sent_date, message_from

  " );
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

  date_default_timezone_set("Africa/Nairobi");

  $allPostVars = json_decode($app->request()->getBody(),true);


  $subject =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
  $message =    ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
  $attachment = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
  // $attachment2 = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
  // $attachment = ( isset($allPostVars['post']['attachment']) ? 'TRUE': null);
  $audienceId = ( isset($allPostVars['post']['audience_id']) ? $allPostVars['post']['audience_id']: null);
  $comTypeId =  ( isset($allPostVars['post']['com_type_id']) ? $allPostVars['post']['com_type_id']: null);
  $studentId   =  ( isset($allPostVars['post']['student_id']) ? $allPostVars['post']['student_id']: null);
  $guardianId = ( isset($allPostVars['post']['guardian_id']) ? $allPostVars['post']['guardian_id']: null);
  $employeeId = ( isset($allPostVars['post']['emp_id']) ? $allPostVars['post']['emp_id']: null);
  $classId =    ( isset($allPostVars['post']['class_id']) ? $allPostVars['post']['class_id']: null);
  $sendAsEmail =  ( isset($allPostVars['post']['send_as_email']) ? $allPostVars['post']['send_as_email']: 'f');
  $sendAsSms =  ( isset($allPostVars['post']['send_as_sms']) ? $allPostVars['post']['send_as_sms']: 'f');
  $replyTo =    ( isset($allPostVars['post']['reply_to']) ? $allPostVars['post']['reply_to']: null);
  $postStatus = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
  $messageFrom =  ( isset($allPostVars['post']['message_from']) ? $allPostVars['post']['message_from']: null);
  $routeId =  ( isset($allPostVars['post']['transport_id']) ? $allPostVars['post']['transport_id']: null);
  $feeItem =  ( isset($allPostVars['post']['fee_item']) ? $allPostVars['post']['fee_item']: null);
  $deptId =  ( isset($allPostVars['post']['dept_id']) ? $allPostVars['post']['dept_id']: null);
  $empCatId =  ( isset($allPostVars['post']['emp_cat_id']) ? $allPostVars['post']['emp_cat_id']: null);
  $studentType =  ( isset($allPostVars['post']['student_type']) ? $allPostVars['post']['student_type']: null);
  $houseName =  ( isset($allPostVars['post']['house_name']) ? $allPostVars['post']['house_name']: null);
  $committeeName =  ( isset($allPostVars['post']['committee_name']) ? $allPostVars['post']['committee_name']: null);
  $toEmployee =  ( isset($allPostVars['post']['to_employee']) ? $allPostVars['post']['to_employee']: null);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);




  try
  {
    $db = getDB();
    $sth = $db->prepare("INSERT INTO app.communications(com_date, audience_id, com_type_id, subject, message, attachment, message_from, student_id, guardian_id, class_id, send_as_email, send_as_sms, created_by, reply_to, post_status_id, route, activity, dept_id, emp_cat_id, student_type, house_name, committee_name, to_employee)
               VALUES(now(), :audienceId, :comTypeId, :subject, :message, :attachment, :messageFrom, :studentId, :guardianId, :classId, :sendAsEmail, :sendAsSms, :userId, :replyTo, :postStatus, :route, :activity, :deptId, :empCatId, :studentType, :houseName, :committeeName, :toEmployee) RETURNING com_id AS postId");

    // if( $attachment === 'TRUE' )
    // {
    //
    //   $insertAttachments = $db->prepare("INSERT INTO app.communication_attachments(com_id, attachment)
    //                                     VALUES(CURRVAL('app.communications_com_id_seq'), :attachment2)");
    // }

    if( $postStatus === 1 )
    {

      // get list of receipients
      $subdomain = getSubDomain();
      $schoolNameQry = $db->query("SELECT value FROM app.settings where name='School Name'");
      $schoolName = $schoolNameQry->fetch(PDO::FETCH_OBJ);
      $schoolName = $schoolName->value;

      $params = array();
      if( $sendAsEmail === 't' )
      {
        if( $audienceId === 1 )
        {
          // school wide
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT email FROM app.guardians WHERE active is true AND email is not null
                                          UNION
                                          SELECT email FROM app.employees WHERE active is true AND email is not null");
          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students s
                                        INNER JOIN app.classes c ON s.current_class = c.class_id
                                        INNER JOIN app.class_cats cc USING (class_cat_id)
                                        WHERE s.active is true AND cc.entity_id != 100 OR entity_id IS null");
        }
        else if( $audienceId === 2 )
        {
          // class specific
          $className = $db->prepare("SELECT class_name
                                FROM app.classes
                              WHERE class_id = :classId");
          $className->execute(array(':classId' => $classId));
          $classNameResult = $className->fetch(PDO::FETCH_OBJ);
          $className = $classNameResult->class_name;

          $notifyMsg = "News posted from " . $className. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT email FROM app.guardians
                                          INNER JOIN app.student_guardians USING (guardian_id)
                                          INNER JOIN app.students USING (student_id)
                                          INNER JOIN app.classes c ON students.current_class = c.class_id
                                          INNER JOIN app.class_cats cc USING (class_cat_id)
                                          WHERE guardians.active IS TRUE AND email IS NOT NULL AND students.active IS TRUE AND cc.entity_id != 100 OR cc.entity_id IS null
                                          AND current_class = :classId");
          $params = array(':classId' => $classId);

          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = :classId
                                        AND active is true");

        }
        else if( $audienceId === 3 )
        {
          // all staff
          $pullRecipients = $db->prepare("SELECT email FROM app.employees WHERE active is true");
        }
        else if( $audienceId === 4 )
        {
          // all teachers
          $pullRecipients = $db->prepare("SELECT email FROM app.employees INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id WHERE employees.active is true AND LOWER(employee_cats.emp_cat_name) = LOWER('TEACHING')");
        }
        else if( $audienceId === 5 )
        {
          // specific parent
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT email FROM app.guardians WHERE guardian_id = :guardianId");
          $params = array(':guardianId' => $guardianId);

          $studentsToNotify = $db->prepare("SELECT students.student_id
                                        FROM app.students
                                        INNER JOIN app.student_guardians
                                        ON students.student_id = student_guardians.student_id
                                        WHERE guardian_id = :guardianId
                                        ");
        }
        else if( $audienceId === 6 )
        {
          // recipients by transport routes
          $pullRecipients = $db->prepare("SELECT guardians.email
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.transport_routes ON students.transport_route_id = transport_routes.transport_id
                                          INNER JOIN app.communications ON transport_routes.transport_id = communications.route
                                          INNER JOIN app.classes c ON students.current_class = c.class_id
                                          INNER JOIN app.class_cats cc USING (class_cat_id)
                                          WHERE students.active = TRUE AND guardians.active IS TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications) AND cc.entity_id != 100 OR cc.entity_id IS null");

          $notifyMsg = "Transport Route news posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT students.student_id FROM app.students
                                            INNER JOIN app.transport_routes ON students.transport_route_id = transport_routes.transport_id
                                            INNER JOIN app.communications ON transport_routes.transport_id = communications.route
                                            INNER JOIN app.classes c ON students.current_class = c.class_id
                                            INNER JOIN app.class_cats cc USING (class_cat_id)
                                            WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications) AND cc.entity_id != 100 OR cc.entity_id IS null");
        }
        else if( $audienceId === 7 )
        {
          // recipients by activity
          $pullRecipients = $db->prepare("SELECT guardians.email
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.student_fee_items ON students.student_id = student_fee_items.student_id
                                          INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                                          INNER JOIN app.communications ON fee_items.fee_item = communications.activity
                                          WHERE students.active = TRUE AND guardians.active IS TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT students.student_id FROM app.students
                                            INNER JOIN app.student_fee_items ON students.student_id = student_fee_items.student_id
                                            INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                                            INNER JOIN app.communications ON fee_items.fee_item = communications.activity
                                            WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 8 )
        {
          // recipients by student's house
          $pullRecipients = $db->prepare("SELECT g.email
                                          FROM app.guardians g
                                          INNER JOIN app.student_guardians sg ON g.guardian_id = sg.guardian_id
                                          INNER JOIN app.students s ON sg.student_id = s.student_id
                                          INNER JOIN app.communications c ON s.house = c.house
                                          WHERE s.active IS TRUE AND g.active IS TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications) ");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT s.student_id FROM app.students s
                                            INNER JOIN app.communications c ON s.house = c.house
                                            WHERE s.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 9 )
        {
          // recipients by employee's committee
          $pullRecipients = $db->prepare("SELECT e.email
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.committee = c.committee
                                          WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications) ");

          $notifyMsg = "Committee News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT e.emp_id FROM app.employees e
                                            INNER JOIN app.communications c ON e.house = c.house
                                            WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 10 )
        {
          // recipients by employee's category
          $pullRecipients = $db->prepare("SELECT e.email
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.emp_cat_id = c.emp_cat_id
                                          WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications) ");

          $notifyMsg = "Staff posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT e.emp_id FROM app.employees e
                                            INNER JOIN app.communications c ON e.emp_cat_id = c.emp_cat_id
                                            WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 11 )
        {
          // recipients by employee's category
          $pullRecipients = $db->prepare("SELECT e.email
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.dept_id = c.dept_id
                                          WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications) ");

          $notifyMsg = "Department News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT e.emp_id FROM app.employees e
                                            INNER JOIN app.communications c ON e.dept_id = c.dept_id
                                            WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 12 )
        {
          // recipients by student's type
          $pullRecipients = $db->prepare("SELECT g.email
                                          FROM app.guardians g
                                          INNER JOIN app.student_guardians sg ON g.guardian_id = sg.guardian_id
                                          INNER JOIN app.students s ON sg.student_id = s.student_id
                                          INNER JOIN app.communications c ON s.student_type = c.student_type
                                          WHERE s.active IS TRUE AND g.active IS TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications) ");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT s.student_id FROM app.students s
                                            INNER JOIN app.communications c ON s.student_type = c.student_type
                                            WHERE s.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 13 )
        {
          // recipients is one employee
          $pullRecipients = $db->prepare("SELECT e.email
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.emp_id = c.to_employee
                                          WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications) ");

          $notifyMsg = "Department News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT e.emp_id FROM app.employees e
                                            INNER JOIN app.communications c ON e.emp_id = c.to_employee
                                            WHERE e.active = TRUE AND c.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }

        $insertEmail = $db->prepare("INSERT INTO app.communication_emails(com_id, email_address)
                                     VALUES(CURRVAL('app.communications_com_id_seq'), :email)");

      }
      else if( $sendAsSms === 't' )
      {
        // TO DO how do we know their telephone number is their mobile device and they want to receive SMS...
        if( $audienceId === 1 )
        {
          // school wide
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT * FROM (
                                            SELECT DISTINCT ON (guardians.first_name, guardians.last_name, telephone) guardians.first_name, guardians.last_name, telephone FROM app.guardians
                                            INNER JOIN app.student_guardians USING (guardian_id)
                                            INNER JOIN app.students USING (student_id)
                                            INNER JOIN app.classes c ON students.current_class = c.class_id
                                            INNER JOIN app.class_cats cc USING (class_cat_id)
                                            WHERE guardians.active IS TRUE
                                            AND students.active IS TRUE
                                            AND student_guardians.active IS TRUE
                                            AND cc.entity_id != 100 OR cc.entity_id IS null
                                          )qry
                                          WHERE telephone IS NOT NULL
                                          /*UNION
                                          SELECT first_name, last_name, telephone FROM app.employees WHERE active is true AND telephone is not null*/");
          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE active is true");
        }
        else if( $audienceId === 2 )
        {
          // class specific
          $className = $db->prepare("SELECT class_name
                                FROM app.classes
                              WHERE class_id = :classId");
          $className->execute(array(':classId' => $classId));
          $classNameResult = $className->fetch(PDO::FETCH_OBJ);
          $className = $classNameResult->class_name;

          $notifyMsg = "News posted from " . $className. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT guardians.telephone, guardians.first_name, guardians.last_name FROM app.guardians
                                          INNER JOIN app.student_guardians
                                            INNER JOIN app.students
                                            ON student_guardians.student_id = students.student_id AND students.active is true
                                          ON guardians.guardian_id = student_guardians.guardian_id
                                          WHERE guardians.active IS TRUE AND students.active IS TRUE
                                          AND current_class = :classId");
          $params = array(':classId' => $classId);

          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = :classId
                                        AND active is true");
        }
        else if( $audienceId === 3 )
        {
          // all staff
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM app.employees WHERE active is true");
        }
        else if( $audienceId === 4 )
        {
          // all teachers
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM app.employees INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id WHERE employees.active is true AND LOWER(employee_cats.emp_cat_name) = LOWER('TEACHING')");
          //$params = array($_POST["attachment"]);
        }
        else if( $audienceId === 5 )
        {
          // specific parent
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM app.guardians WHERE guardian_id = :guardianId");
          $params = array(':guardianId' => $guardianId);

        }
        else if( $audienceId === 6 )
        {
          // recipients by transport routes
          $pullRecipients = $db->prepare("SELECT guardians.first_name, guardians.last_name, guardians.telephone
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.transport_routes ON students.transport_route_id = transport_routes.transport_id
                                          INNER JOIN app.communications ON transport_routes.transport_id = communications.route
                                          WHERE students.active IS TRUE AND guardians.active IS TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 7 )
        {


          // recipients by activity
          $pullRecipients = $db->prepare("SELECT guardians.first_name, guardians.last_name, guardians.telephone
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.student_fee_items ON students.student_id = student_fee_items.student_id
                                          INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                                          INNER JOIN app.communications ON fee_items.fee_item = communications.activity
                                          WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");


        }
        else if( $audienceId === 8 )
        {
          // recipients by the student's house
          $pullRecipients = $db->prepare("SELECT g.first_name, g.last_name, g.telephone
                                          FROM app.guardians g
                                          INNER JOIN app.student_guardians sg ON g.guardian_id = sg.guardian_id
                                          INNER JOIN app.students s ON s.student_id = sg.student_id
                                          INNER JOIN app.communications c ON s.house = c.house
                                          WHERE s.active is true AND c.com_id = (SELECT MAX(com_id) FROM app.communications)");


        }
        else if( $audienceId === 9 )
        {
          // employee committee
          $pullRecipients = $db->prepare("SELECT e.first_name, e.last_name, e.telephone
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.committee = c.committee
                                          WHERE e.active is true AND c.com_id = (SELECT MAX(com_id) FROM app.communications)");

        }
        else if( $audienceId === 10 )
        {
          // employee category
          $pullRecipients = $db->prepare("SELECT e.first_name, e.last_name, e.telephone
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.emp_cat_id = c.emp_cat_id
                                          WHERE e.active is true AND c.com_id = (SELECT MAX(com_id) FROM app.communications)");

        }
        else if( $audienceId === 11 )
        {
          // employee department
          $pullRecipients = $db->prepare("SELECT e.first_name, e.last_name, e.telephone
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.dept_id = c.dept_id
                                          WHERE e.active is true AND c.com_id = (SELECT MAX(com_id) FROM app.communications)");

        }
        else if( $audienceId === 12 )
        {
          // employee department
          $pullRecipients = $db->prepare("SELECT g.first_name, g.last_name, g.telephone
                                          FROM app.guardians g
                                          INNER JOIN app.student_guardians sg ON g.guardian_id = sg.guardian_id
                                          INNER JOIN app.students s ON sg.student_id = s.student_id
                                          INNER JOIN app.communications c ON s.student_type = c.student_type
                                          WHERE g.active is true AND c.com_id = (SELECT MAX(com_id) FROM app.communications)");

        }
        else if( $audienceId === 13 )
        {
          // one employee
          $pullRecipients = $db->prepare("SELECT e.first_name, e.last_name, e.telephone
                                          FROM app.employees e
                                          INNER JOIN app.communications c ON e.emp_id = c.to_employee
                                          WHERE e.active is true AND c.com_id = (SELECT MAX(com_id) FROM app.communications)");

        }

        $insertSMS = $db->prepare("INSERT INTO app.communication_sms(com_id, sim_number, first_name, last_name) VALUES(CURRVAL('app.communications_com_id_seq'), :mobileNumber, :firstName, :lastName)");
      }

    }



    $db->beginTransaction();
    $sth->execute( array(':audienceId' => $audienceId, ':comTypeId' => $comTypeId, ':subject' => $subject, ':message' => $message ,
            ':attachment' => $attachment , ':userId' => $userId, ':studentId' => $studentId, ':guardianId' => $guardianId,
            ':classId' => $classId, ':sendAsEmail' => $sendAsEmail, ':sendAsSms' => $sendAsSms, ':replyTo' => $replyTo,
            ':messageFrom' => $messageFrom, ':postStatus' => $postStatus, ':route' => $routeId, ':activity' => $feeItem,
            ':deptId' => $deptId, ':studentType' => $studentType, ':committeeName' => $committeeName, ':empCatId' => $empCatId, ':houseName' => $houseName, ':toEmployee' => $toEmployee) );

    // if( $attachment === 'TRUE' )
    // {
    //   $insertAttachments = $db->prepare("INSERT INTO app.communication_attachments(com_id, attachment)
    //                                     VALUES(CURRVAL('app.communications_com_id_seq'), :attachment2)");
    //   $alluploads = $attachment2;
    //   foreach($alluploads as $key => $value){
    //     $insertAttachments->execute( array(':attachment2' => $value) );
    //   }
    //   // $insertAttachments->execute( array(':attachment2' => $attachment2) );
    // }

    // if published, make entries into tables for email/sms service

    if( $postStatus === 1 )
    {

      $pullRecipients->execute($params);
      $results = $pullRecipients->fetchAll(PDO::FETCH_OBJ);

      foreach($results as $result){
         if( $sendAsEmail === 't' )
         {
            $insertEmail->execute( array(':email' => $result->email) );
         }
         else if( $sendAsSms === 't' )
         {
           $insertSMS->execute( array(':mobileNumber' => $result->telephone, ':firstName' => $result->first_name, ':lastName' => $result->last_name) );
         }
      }

      // get the device ids to send a notification
      if( $sendAsEmail === 't' && $audienceId == 1 || $sendAsEmail === 't' && $audienceId == 2 || $sendAsEmail === 't' && $audienceId == 5 || $sendAsEmail === 't' && $audienceId == 6 || $sendAsEmail === 't' && $audienceId == 7 )
      {
          $studentsToNotify->execute($params);
          $results = $studentsToNotify->fetchAll(PDO::FETCH_OBJ);
          $studentIds = array();
          foreach($results as $result) {
            $studentIds[] = $result->student_id;
          }
          $studentIdStr = '{' . implode(',', $studentIds) . '}';
      }

      $db->commit();
      $db = null;

      //  was published, need to add entry for notifications
      $db = getMISDB();

      // get all device ids
      if( $sendAsEmail === 't' && $audienceId == 1 || $sendAsEmail === 't' && $audienceId == 2 || $sendAsEmail === 't' && $audienceId == 5 || $sendAsEmail === 't' && $audienceId == 6 || $sendAsEmail === 't' && $audienceId == 7 )
      {
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
                ':message' => $notifyMsg
              )
            );
          }
        }
    }
    else {
      $db-commit();
    }

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

$app->post('/customAddCommunication', function () use($app) {
  // Add communication

  $allPostVars = json_decode($app->request()->getBody(),true);
  // var_dump($allPostVars);
  $subject =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
  $message =    ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
  $attachment = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
  // $attachment2 = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
  // $attachment = ( isset($allPostVars['post']['attachment']) ? 'TRUE': null);
  $audienceId = ( isset($allPostVars['post']['audience_id']) ? $allPostVars['post']['audience_id']: null);
  $comTypeId =  ( isset($allPostVars['post']['com_type_id']) ? $allPostVars['post']['com_type_id']: null);
  $studentId   =  ( isset($allPostVars['post']['student_id']) ? $allPostVars['post']['student_id']: null);
  $guardianId = ( isset($allPostVars['post']['guardian_id']) ? $allPostVars['post']['guardian_id']: null);
  $employeeId = ( isset($allPostVars['post']['emp_id']) ? $allPostVars['post']['emp_id']: null);
  $classId =    ( isset($allPostVars['post']['class_id']) ? $allPostVars['post']['class_id']: null);
  $sendAsEmail =  ( isset($allPostVars['post']['send_as_email']) ? $allPostVars['post']['send_as_email']: false);
  $sendAsSms =  ( isset($allPostVars['post']['send_as_sms']) ? $allPostVars['post']['send_as_sms']: false);
  $replyTo =    ( isset($allPostVars['post']['reply_to']) ? $allPostVars['post']['reply_to']: null);
  $postStatus = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
  $messageFrom =  ( isset($allPostVars['post']['message_from']) ? $allPostVars['post']['message_from']: null);
  $routeId =  ( isset($allPostVars['post']['transport_id']) ? $allPostVars['post']['transport_id']: null);
  $feeItem =  ( isset($allPostVars['post']['fee_item']) ? $allPostVars['post']['fee_item']: null);
  $sent =  ( isset($allPostVars['post']['sent']) ? $allPostVars['post']['sent']: false);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  // these are for use only when posting from the transport module
  $transportFilters = ( isset($allPostVars['post']['filters']) ? $allPostVars['post']['filters']: null);
  if($transportFilters !== null){
    $neighborhoodSelect =  ( isset($allPostVars['post']['filters']['neighborhoods']) ? $allPostVars['post']['filters']['neighborhoods']: null);
    $classCatSelect =  ( isset($allPostVars['post']['filters']['class_cat_id']) ? $allPostVars['post']['filters']['class_cat_id']: null);
    $tripIdSelect =  ( isset($allPostVars['post']['filters']['schoolbus_trip_id']) ? $allPostVars['post']['filters']['schoolbus_trip_id']: null);
    $busIdSelect =  ( isset($allPostVars['post']['filters']['bus_id']) ? $allPostVars['post']['filters']['bus_id']: null);
  }

  try
  {
    $db = getDB();
    $sth = $db->prepare("INSERT INTO app.communications(com_date, audience_id, com_type_id, subject, message, attachment, message_from, student_id, guardian_id, class_id, send_as_email, send_as_sms, created_by, reply_to, sent, post_status_id, route, activity)
               VALUES(now(), :audienceId, :comTypeId, :subject, :message, :attachment, :messageFrom, :studentId, :guardianId, :classId, :sendAsEmail, :sendAsSms, :userId, :replyTo, :sent, :postStatus, :route, :activity)");
    // var_dump($sth);
    if( $postStatus === 1 )
    {

      // get list of receipients
      $subdomain = getSubDomain();
      $schoolNameQry = $db->query("SELECT value FROM app.settings where name='School Name'");
      $schoolName = $schoolNameQry->fetch(PDO::FETCH_OBJ);
      $schoolName = $schoolName->value;

      $params = array();
      if( $sendAsEmail === 't' )
      {
        if( $audienceId === 1 )
        {
          // school wide
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT email FROM app.guardians WHERE active is true AND email is not null
                                          UNION
                                          SELECT email FROM app.employees WHERE active is true AND email is not null");
          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE active is true");
        }
        else if( $audienceId === 2 )
        {
          // class specific
          /*
          $className = $db->prepare("SELECT class_name
                                FROM app.classes
                              WHERE class_id = :classId");
          $className->execute(array(':classId' => $classId));
          $classNameResult = $className->fetch(PDO::FETCH_OBJ);
          $className = $classNameResult->class_name;
          */
          $className = $db->query("SELECT class_name FROM app.classes WHERE class_id = ".$classId);
          $classNameResult = $className->fetch(PDO::FETCH_OBJ);
          $className = $classNameResult->class_name;

          $notifyMsg = "News posted from " . $className. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT email FROM app.guardians
                                          INNER JOIN app.student_guardians
                                            INNER JOIN app.students
                                            ON student_guardians.student_id = students.student_id AND students.active is true
                                          ON guardians.guardian_id = student_guardians.guardian_id
                                          WHERE guardians.active is true
                                          AND current_class = :classId AND students.active IS TRUE");
          $params = array(':classId' => $classId);

          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = :classId
                                        AND active is true");

        }
        else if( $audienceId === 3 )
        {
          // all staff
          $pullRecipients = $db->prepare("SELECT email FROM app.employees WHERE active is true");
        }
        else if( $audienceId === 4 )
        {
          // all teachers
          $pullRecipients = $db->prepare("SELECT email FROM app.employees INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id WHERE employees.active is true AND LOWER(employee_cats.emp_cat_name) = LOWER('TEACHING')");
        }
        else if( $audienceId === 5 )
        {
          // specific parent
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT email FROM app.guardians WHERE guardian_id = :guardianId");
          $params = array(':guardianId' => $guardianId);

          $studentsToNotify = $db->prepare("SELECT students.student_id
                                        FROM app.students
                                        INNER JOIN app.student_guardians
                                        ON students.student_id = student_guardians.student_id
                                        WHERE guardian_id = :guardianId
                                        ");
        }
        else if( $audienceId === 6 )
        {
          // recipients by transport routes
          $pullRecipients = $db->prepare("SELECT guardians.email
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.transport_routes ON students.transport_route_id = transport_routes.transport_id
                                          INNER JOIN app.communications ON transport_routes.transport_id = communications.route
                                          WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");

          $notifyMsg = "Transport Route news posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT students.student_id FROM app.students
                                            INNER JOIN app.transport_routes ON students.transport_route_id = transport_routes.transport_id
                                            INNER JOIN app.communications ON transport_routes.transport_id = communications.route
                                            WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 7 )
        {
          // recipients by activity
          $pullRecipients = $db->prepare("SELECT guardians.email
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.student_fee_items ON students.student_id = student_fee_items.student_id
                                          INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                                          INNER JOIN app.communications ON fee_items.fee_item = communications.activity
                                          WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT students.student_id FROM app.students
                                            INNER JOIN app.student_fee_items ON students.student_id = student_fee_items.student_id
                                            INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                                            INNER JOIN app.communications ON fee_items.fee_item = communications.activity
                                            WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 14 )
        {
          // recipients by student neighborhood
          $pullRecipients = $db->prepare("SELECT guardians.email
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT students.student_id FROM app.students
                                            WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'");
        }
        else if( $audienceId === 15 )
        {
          // recipients by student schoolbus trip
          $pullRecipients = $db->prepare("SELECT email FROM (
                                            SELECT guardians.email, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT student_id FROM (
                                            SELECT students.student_id, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.students
                                            WHERE students.active = TRUE
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");
        }
        else if( $audienceId === 16 )
        {
          // recipients by students in neighborhood in schoolbus trip
          $pullRecipients = $db->prepare("SELECT email FROM (
                                            SELECT guardians.email, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT student_id FROM (
                                            SELECT students.student_id, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.students
                                            WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");
        }
        else if( $audienceId === 17 )
        {
          // recipients by class students in neighborhood
          $pullRecipients = $db->prepare("SELECT guardians.email
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          WHERE students.active = TRUE
                                          AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                          AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT students.student_id
                                          FROM app.students
                                          WHERE students.active = TRUE
                                          AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                          AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)");
        }
        else if( $audienceId === 18 )
        {
          // recipients by class students in schoolbus trip
          $pullRecipients = $db->prepare("SELECT email FROM (
                                            SELECT guardians.email, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE
                                            AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT student_id FROM (
                                            SELECT students.student_id, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.students
                                            WHERE students.active = TRUE
                                            AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");
        }
        else if( $audienceId === 19 )
        {
          // recipients by class students in neighborhood in schoolbus trip
          $pullRecipients = $db->prepare("SELECT email FROM (
                                            SELECT guardians.email, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                            AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;

          $studentsToNotify = $db->prepare("SELECT student_id FROM (
                                            SELECT students.student_id, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.students
                                            WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                            AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");
        }

        $insertEmail = $db->prepare("INSERT INTO app.communication_emails(com_id, email_address)
                                     VALUES(CURRVAL('app.communications_com_id_seq'), :email)");

      }
      else if( $sendAsSms === 't' )
      {
        // TO DO how do we know their telephone number is their mobile device and they want to receive SMS...
        if( $audienceId === 1 )
        {
          // school wide
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT DISTINCT ON (guardians.first_name, guardians.last_name, telephone) guardians.first_name, guardians.last_name, telephone FROM app.guardians
                                            INNER JOIN app.student_guardians USING (guardian_id)
                                            INNER JOIN app.students USING (student_id)
                                            WHERE guardians.active IS TRUE AND students.active IS TRUE AND telephone IS NOT null
                                          /*UNION
                                          SELECT first_name, last_name, telephone FROM app.employees WHERE active is true AND telephone is not null*/");
          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE active is true");
        }
        else if( $audienceId === 2 )
        {
          // class specific
          $className = $db->prepare("SELECT class_name
                                FROM app.classes
                              WHERE class_id = :classId");
          $className->execute(array(':classId' => $classId));
          $classNameResult = $className->fetch(PDO::FETCH_OBJ);
          $className = $classNameResult->class_name;

          $notifyMsg = "News posted from " . $className. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT guardians.telephone, guardians.first_name, guardians.last_name FROM app.guardians
                                          INNER JOIN app.student_guardians
                                            INNER JOIN app.students
                                            ON student_guardians.student_id = students.student_id AND students.active is true
                                          ON guardians.guardian_id = student_guardians.guardian_id
                                          WHERE guardians.active is true
                                          AND current_class = :classId");
          $params = array(':classId' => $classId);

          $studentsToNotify = $db->prepare("SELECT student_id
                                        FROM app.students
                                        WHERE current_class = :classId
                                        AND active is true");
        }
        else if( $audienceId === 3 )
        {
          // all staff
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM app.employees WHERE active is true");
        }
        else if( $audienceId === 4 )
        {
          // all teachers
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM app.employees INNER JOIN app.employee_cats ON employees.emp_cat_id = employee_cats.emp_cat_id WHERE employees.active is true AND LOWER(employee_cats.emp_cat_name) = LOWER('TEACHING')");
          //$params = array($_POST["attachment"]);
        }
        else if( $audienceId === 5 )
        {
          // specific parent
          $notifyMsg = "News posted from " . $schoolName. ": " . $subject;
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM app.guardians WHERE guardian_id = :guardianId");
          $params = array(':guardianId' => $guardianId);

        }
        else if( $audienceId === 6 )
        {
          // recipients by transport routes
          $pullRecipients = $db->prepare("SELECT guardians.first_name, guardians.last_name, guardians.telephone
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.transport_routes ON students.transport_route_id = transport_routes.transport_id
                                          INNER JOIN app.communications ON transport_routes.transport_id = communications.route
                                          WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");
        }
        else if( $audienceId === 7 )
        {


          // recipients by activity
          $pullRecipients = $db->prepare("SELECT guardians.first_name, guardians.last_name, guardians.telephone
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          INNER JOIN app.student_fee_items ON students.student_id = student_fee_items.student_id
                                          INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                                          INNER JOIN app.communications ON fee_items.fee_item = communications.activity
                                          WHERE students.active = TRUE AND communications.com_id =(SELECT MAX(com_id) FROM app.communications)");


        }
        else if( $audienceId === 8 )
        {
          // recipients by employee department (drivers)
          $pullRecipients = $db->prepare("SELECT employees.first_name, employees.last_name, employees.telephone
                                          FROM app.employees
                                          INNER JOIN app.employee_cats USING (emp_cat_id)
                                          WHERE employees.active is true AND LOWER(employee_cats.emp_cat_name) = LOWER('Drivers')");


        }
        else if( $audienceId === 9 )
        {
          // specific employee
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM app.employees WHERE emp_id = :employeeId");
          $params = array(':employeeId' => $employeeId);

        }
        else if( $audienceId === 14 )
        {
          // students in neighborhood
          $pullRecipients = $db->prepare("SELECT guardians.first_name, guardians.last_name, guardians.telephone
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          WHERE students.active IS TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'");

        }
        else if( $audienceId === 15 )
        {
          // students in schoolbus trip
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM (
                                            SELECT guardians.first_name, guardians.last_name, guardians.telephone, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

        }
        else if( $audienceId === 16 )
        {
          // students in neighborhood in trip
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM (
                                            SELECT guardians.first_name, guardians.last_name, guardians.telephone, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

        }
        else if( $audienceId === 17 )
        {
          // class students in neighborhood
          $pullRecipients = $db->prepare("SELECT guardians.first_name, guardians.last_name, guardians.telephone
                                          FROM app.guardians
                                          INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                          INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                          WHERE students.active = TRUE
                                          AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                          AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)");

        }
        else if( $audienceId === 18 )
        {
          // class students in schoolbus trip
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM (
                                            SELECT guardians.first_name, guardians.last_name, guardians.telephone, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE
                                            AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

        }
        else if( $audienceId === 19 )
        {
          // class students in neighborhood in schoolbus trip
          $pullRecipients = $db->prepare("SELECT first_name, last_name, telephone FROM (
                                            SELECT guardians.first_name, guardians.last_name, guardians.telephone, UNNEST(string_to_array(trip_ids,','))::int AS stdnt_trip
                                            FROM app.guardians
                                            INNER JOIN app.student_guardians ON guardians.guardian_id = student_guardians.guardian_id
                                            INNER JOIN app.students ON student_guardians.student_id = students.student_id
                                            WHERE students.active = TRUE AND students.destination ILIKE '%' || '$neighborhoodSelect' || '%'
                                            AND students.current_class IN (SELECT class_id FROM app.classes WHERE class_cat_id = $classCatSelect)
                                          )one
                                          INNER JOIN app.schoolbus_bus_trips sbt ON one.stdnt_trip = sbt.bus_trip_id
                                          WHERE sbt.bus_id = $busIdSelect AND sbt.bus_trip_id = $tripIdSelect");

        }

        $insertSMS = $db->prepare("INSERT INTO app.communication_sms(com_id, sim_number, first_name, last_name) VALUES(CURRVAL('app.communications_com_id_seq'), :mobileNumber, :firstName, :lastName)");
      }

    }



    $db->beginTransaction();
    $sth->execute( array(':audienceId' => $audienceId, ':comTypeId' => $comTypeId, ':subject' => $subject, ':message' => $message,
            ':attachment' => $attachment, ':userId' => $userId, ':studentId' => $studentId, ':guardianId' => $guardianId,
            ':classId' => $classId, ':sendAsEmail' => $sendAsEmail, ':sendAsSms' => $sendAsSms, ':replyTo' => $replyTo,
            ':messageFrom' => $messageFrom, ':postStatus' => $postStatus, ':route' => $routeId, ':activity' => $feeItem, ':sent' => $sent) );

    // if published, make entries into tables for email/sms service

    if( $postStatus === 1 )
    {

      $pullRecipients->execute($params);
      $results = $pullRecipients->fetchAll(PDO::FETCH_OBJ);
      $recipientsResults = $results;

      foreach($results as $result){
         if( $sendAsEmail === 't' )
         {
            $insertEmail->execute( array(':email' => $result->email) );
         }
         else if( $sendAsSms === 't' )
         {
           $insertSMS->execute( array(':mobileNumber' => $result->telephone, ':firstName' => $result->first_name, ':lastName' => $result->last_name) );
         }
      }

      // get the device ids to send a notification
      if( $sendAsEmail === 't' && $audienceId == 1 || $sendAsEmail === 't' && $audienceId == 2 || $sendAsEmail === 't' && $audienceId == 5 || $sendAsEmail === 't' && $audienceId == 6 || $sendAsEmail === 't' && $audienceId == 7 )
      {
          $studentsToNotify->execute($params);
          $results = $studentsToNotify->fetchAll(PDO::FETCH_OBJ);
          $studentIds = array();
          foreach($results as $result) {
            $studentIds[] = $result->student_id;
          }
          $studentIdStr = '{' . implode(',', $studentIds) . '}';
      }

      $db->commit();
      $db = null;

      //  was published, need to add entry for notifications
      $db = getMISDB();

      // get all device ids
      if( $sendAsEmail === 't' )
      {
          $getDeviceIds = $db->prepare("SELECT device_user_id
                                        FROM parents
                                        INNER JOIN parent_students
                                        ON parents.parent_id = parent_students.parent_id
                                        WHERE subdomain = :subdomain
                                        AND student_id = any(:studentIds)");
          $getDeviceIds->execute( array(':studentIds' => $studentIdStr, ':subdomain' => $subdomain) );
          $results = $getDeviceIds->fetchAll(PDO::FETCH_OBJ);
          $returnDevIds = $results;

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
                ':message' => $notifyMsg
              )
            );
          }
        }

    }
    else {
      $db-commit();
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1, "email_recipients" => $recipientsResults, "device_ids" => $returnDevIds, "execute_params" => $params));
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
      $sth = $db->prepare("SELECT communications.com_id as post_id, com_date, communications.creation_date, communications.com_type_id, com_type, subject, message, send_as_email, send_as_sms,
                employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                communications.audience_id, audience, communications.attachment, reply_to,
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
            group by communications.com_id,  com_date, communications.creation_date, communications.com_type_id, com_type, subject, message, send_as_email, send_as_sms, posted_by, communications.audience_id, audience, reply_to, student_name,
            parent_full_name, communications.guardian_id, communications.student_id, class_name, communications.class_id, communications.post_status_id, post_status, sent, sent_date, message_from

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

$app->get('/getRoutes/:post_id', function ($postId) {
  // Get Routes

$app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
      $sth = $db->prepare("SELECT transport_routes.route FROM app.transport_routes WHERE transport_id = :postId");
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

// $app->get('/getActivitiesList/:post_id', function ($postId) {
//     //Show fee items
//
//   $app = \Slim\Slim::getInstance();
//
//     try
//     {
//         $db = getDB();
//         $sth = $db->prepare("SELECT fee_items.fee_item FROM app.fee_items WHERE fee_item_id = :postId");
//         $sth->execute( array(':postId' => $postId) );
//         $results = $sth->fetch(PDO::FETCH_OBJ);
//
//         if($results) {
//             $app->response->setStatus(200);
//             $app->response()->headers->set('Content-Type', 'application/json');
//             echo json_encode(array('response' => 'success', 'data' => $results ));
//             $db = null;
//         } else {
//            $app->response->setStatus(200);
//             $app->response()->headers->set('Content-Type', 'application/json');
//             echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
//             $db = null;
//         }
//
//     } catch(PDOException $e) {
//       $app->response()->setStatus(200);
//   $app->response()->headers->set('Content-Type', 'application/json');
//       echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
//     }
//
// });

$app->get('/getActivitiesList(/:postId)', function ($postId = TRUE) {
    //Show fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT fee_item
              FROM app.fee_items
              WHERE active = :postId
              AND optional is TRUE");
        $sth->execute( array(':postId' => $postId) );
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

$app->put('/updateCommunication', function () use($app) {
  // Update communication

  $allPostVars = json_decode($app->request()->getBody(),true);
  $comId =    ( isset($allPostVars['post']['post_id']) ? $allPostVars['post']['post_id']: null);
  $subject =    ( isset($allPostVars['post']['title']) ? $allPostVars['post']['title']: null);
  $message =    ( isset($allPostVars['post']['body']) ? $allPostVars['post']['body']: null);
  $postStatusId = ( isset($allPostVars['post']['post_status_id']) ? $allPostVars['post']['post_status_id']: null);
  $attachment = ( isset($allPostVars['post']['attachment']) ? $allPostVars['post']['attachment']: null);
  $replyTo =    ( isset($allPostVars['post']['reply_to']) ? $allPostVars['post']['reply_to']: null);
  $messageFrom =  ( isset($allPostVars['post']['message_from']) ? $allPostVars['post']['message_from']: null);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

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

     if( $postStatusId === 1 )
     {
        // communication was published, need to add entry for notifications

     }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;


  }
  catch(PDOException $e) {
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

// this should be a get, but since there is no https.... i'm using a post
$app->post('/emailsToSend/', function () use($app) {
$allPostVars = json_decode($app->request()->getBody(),true);
$token = ( isset($allPostVars['token']) ? $allPostVars['token']: null);

try{
  require('../lib/token.php');
  if( $token === $_token )
  {
    $db = getMISDB();

    // get all clients
    $sth = $db->query("SELECT subdomain FROM clients");
    $results = $sth->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    $allEmails = array();
    foreach( $results as $result )
    {
      // loop through clients and pull any emails that need to be sent
      $db = setDBConnection($result->subdomain);
      $getEmails = $db->query("SELECT '{$result->subdomain}' as school, email_id, email_address, subject, message, attachment, reply_to
                              FROM app.communication_emails
                              INNER JOIN app.communications USING (com_id)
                              WHERE forwarded is false;");
      $results = $getEmails->fetch(PDO::FETCH_OBJ);
      if( $results ) $allEmails[] = $results;
      $db = null;
    }

    if( count($allEmails) > 0 ) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $allEmails ));
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
    }
  }
  else
  {
    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
}
catch(PDOException $e) {
  $app->response()->setStatus(200);
  $app->response()->headers->set('Content-Type', 'application/json');
  echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}
});

$app->put('/emailSent', function () use($app) {

$allPostVars = json_decode($app->request()->getBody(),true);
$school = ( isset($allPostVars['school']) ? $allPostVars['school']: null);
$emailIds = ( isset($allPostVars['email_ids']) ? $allPostVars['email_ids']: null);
$dateSent = ( isset($allPostVars['send_date']) ? $allPostVars['send_date']: null);

try{
  $db = setDBConnection($school);

  $updateEmails = $db->prepare("UPDATE app.communication_emails
                                SET send_date = :dateSent,
                                    forwarded = true
                                WHERE email_id in (:emailIds)");
  $db->execute(array(':dateSent' => $dateSent, ':emailIds' => $emailIds));

  $app->response->setStatus(200);
  $app->response()->headers->set('Content-Type', 'application/json');
  echo json_encode(array("response" => "success", "code" => 1));
  $db = null;
}
catch(PDOException $e) {
  $app->response()->setStatus(200);
  $app->response()->headers->set('Content-Type', 'application/json');
  echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->post('/addFeedback', function () use($app) {
  // Add communication



  $allPostVars = json_decode($app->request()->getBody(),true);


  $subject =    ( isset($allPostVars['post']['subject']) ? $allPostVars['post']['subject']: null);
  $message =    ( isset($allPostVars['post']['message']) ? $allPostVars['post']['message']: null);
  $messageFrom = ( isset($allPostVars['post']['message_from']) ? $allPostVars['post']['message_from']: null);
  $studentId   =  ( isset($allPostVars['post']['student_id']) ? $allPostVars['post']['student_id']: null);
  $guardianId = ( isset($allPostVars['post']['guardian_id']) ? $allPostVars['post']['guardian_id']: null);
  $classId =    ( isset($allPostVars['post']['class_id']) ? $allPostVars['post']['class_id']: null);

  try
  {
    $db = getDB();
    $sth = $db->prepare("INSERT INTO app.communication_feedback(subject, message, message_from, student_id, guardian_id, class_id)
               VALUES(:subject, :message, :messageFrom, :studentId, :guardianId, :classId)");

    $db->beginTransaction();
    $sth->execute( array(':subject' => $subject, ':message' => $message, ':messageFrom' => $messageFrom,
                          ':studentId' => $studentId, ':guardianId' => $guardianId, ':classId' => $classId) );
    $db->commit();

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

$app->get('/getFeedback/:post_id', function ($postId) {
  // Get communication

$app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
      $sth = $db->prepare("SELECT cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                cf.message_from as posted_by, cf.guardian_id, cf.student_id,
                s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                cf.message_from as parent_full_name,
                c.class_name, cf.opened
            FROM app.communication_feedback cf
            LEFT JOIN app.students s USING (student_id)
            LEFT JOIN app.guardians g USING (guardian_id)
            LEFT JOIN app.classes c USING (class_id)
            WHERE cf.com_feedback_id = :postId
            GROUP BY cf.com_feedback_id, subject, message, student_name, parent_full_name, class_name, opened
            ORDER BY post_id DESC
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

$app->get('/getAllFeedback', function () {
// Get all communications for current school year

$app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();

      $sth = $db->prepare( "SELECT cf.com_feedback_id as post_id, cf.creation_date as sent_date, cf.subject, cf.message,
                cf.message_from as posted_by, cf.guardian_id, cf.student_id,
                s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                cf.message_from as parent_full_name,
                c.class_name, cf.opened
            FROM app.communication_feedback cf
            LEFT JOIN app.students s USING (student_id)
            LEFT JOIN app.guardians g USING (guardian_id)
            LEFT JOIN app.classes c USING (class_id)
            GROUP BY cf.com_feedback_id, subject, message, student_name, parent_full_name, class_name, opened
            ORDER BY post_id DESC" );

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

$app->get('/getFeedbackUnopenedCount', function () {
  // Get blog post types

$app = \Slim\Slim::getInstance();

  try
  {
        $db = getDB();
        $sth = $db->prepare("SELECT COUNT(*) FROM app.communication_feedback WHERE opened IS FALSE");
        $sth->execute();
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

$app->put('/updateOpenedFeedbackMessage', function () use($app) {
  // Update feedback communication

    $allPostVars = json_decode($app->request()->getBody(),true);
    $postId =     ( isset($allPostVars['post_id']) ? $allPostVars['post_id']: null);

  try
  {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.communication_feedback SET opened = TRUE WHERE com_feedback_id = :postId");

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

$app->get('/getUnPublishedMsgCount', function () {
  // Get blog post types

$app = \Slim\Slim::getInstance();

  try
  {
        $db = getDB();
        $sth = $db->prepare("SELECT COUNT(*) FROM app.communications WHERE sent IS FALSE");
        $sth->execute();
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

$app->put('/publishMessage', function () use($app) {
  // Update feedback communication

    $allPostVars = json_decode($app->request()->getBody(),true);
    $postId =     ( isset($allPostVars['post_id']) ? $allPostVars['post_id']: null);

  try
  {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.communications SET sent = TRUE, modified_date = now() WHERE com_id = :postId");

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

$app->put('/batchPublishMessages', function () use($app) {
  // Update feedback communication

    $allPostVars = json_decode($app->request()->getBody(),true);
    $postId =     ( isset($allPostVars['post_id']) ? $allPostVars['post_id']: null);

  try
  {
        $db = getDB();
        $singlePostId = '{' . implode(',', $postId) . '}';
        $sth = $db->prepare("UPDATE app.communications SET sent = TRUE, modified_date = now() WHERE com_id = any(:singlePostId)");

        $sth->execute( array(':singlePostId' => $singlePostId) );

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

$app->put('/unPublishMessage', function () use($app) {
  // Update feedback communication

    $allPostVars = json_decode($app->request()->getBody(),true);
    $postId =     ( isset($allPostVars['post_id']) ? $allPostVars['post_id']: null);

  try
  {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.communications SET sent = FALSE, modified_date = now() WHERE com_id = :postId");

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

$app->get('/getStudentsWithFeeBal', function () {
  // Get blog post types

$app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
      $sth = $db->prepare("SELECT student_id, student_name, array_agg(parents) AS parents, fee_item,
                          (SELECT SUM(ib.total_due) AS total_due FROM app.invoice_balances2 ib WHERE ib.student_id = r.student_id GROUP BY ib.student_id) AS total_due,
                          (SELECT SUM(ib.total_paid) AS total_paid FROM app.invoice_balances2 ib WHERE ib.student_id = r.student_id GROUP BY ib.student_id) AS total_paid,
                          (SELECT SUM(ib.balance) AS balance FROM app.invoice_balances2 ib WHERE ib.student_id = r.student_id GROUP BY ib.student_id) AS balance
                          FROM (
                          	SELECT student_id, student_name, '{\"name\":\"' || parent_full_name || '\",\"phone\":\"' || telephone || '\",\"id\":' || guardian_id || '}' AS parents, fee_item, total_due, total_paid, balance
                          	FROM
                          	(
                          		SELECT DISTINCT(guardian_id) guardian_id, parent_full_name, telephone, student_id, student_name, array_to_string(array_agg(fee_item ORDER BY fee_item ASC),',') AS fee_item, payment_method, sum(invoice_total) AS total_due, sum(total_paid) AS total_paid, sum(total_paid) - sum(invoice_total) AS balance FROM (
                          			SELECT invoices.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                          				g.guardian_id, g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name AS parent_full_name, g.telephone,
                          				invoices.inv_id,
                          				invoice_line_items.amount as invoice_total, fee_item, student_fee_items.payment_method, inv_item_id,
                          				(SELECT COALESCE(sum(payment_inv_items.amount), 0)
                          				FROM app.payment_inv_items
                          				INNER JOIN app.payments
                          				ON payment_inv_items.payment_id = payments.payment_id AND reversed is false
                          				WHERE inv_item_id = invoice_line_items.inv_item_id) as total_paid
                          			FROM app.invoices
                          			INNER JOIN app.students USING (student_id)
                          			INNER JOIN app.student_guardians sg USING (student_id)
                          			INNER JOIN app.guardians g USING (guardian_id)
                          			INNER JOIN app.invoice_line_items
                          			INNER JOIN app.student_fee_items
                          			INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                          						ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id AND student_fee_items.active = true
                          						ON invoices.inv_id = invoice_line_items.inv_id
                          			WHERE invoices.canceled = false AND students.active IS TRUE
                          			ORDER BY student_name ASC
                          		)p
                          		GROUP BY guardian_id, parent_full_name, telephone, student_id, student_name, payment_method
                          		ORDER BY student_name ASC
                          	) q
                          )r
                          WHERE balance < 0
                          GROUP BY student_id, student_name, fee_item");
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

$app->get('/getCommunicationForSms/:post_id', function ($postId) {
  // Get communication

$app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
      $sth = $db->prepare("SELECT communication_sms.com_id, communication_sms.creation_date as message_date, communications.message as message_text,
                                employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as message_by, communication_sms.first_name ||' ' || communication_sms.last_name AS recipient_name,
                                communication_sms.sim_number AS phone_number
                            FROM app.communication_sms
                            INNER JOIN app.communications ON communication_sms.com_id = communications.com_id
                            INNER JOIN app.employees ON communications.message_from = employees.emp_id
                            WHERE communication_sms.com_id = :postId");
  $sth->execute( array(':postId' => $postId) );
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
