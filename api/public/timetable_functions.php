<?php
$app->post('/addClassTimetable', function () use($app) {
	// Add class timetable
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$timetables =	( isset($allPostVars['time_tables']) ? $allPostVars['time_tables']: null);
	
	try 
	{
		$db = getDB();
		
		$db->beginTransaction();
		
		foreach($timetables as $timetable)
		{
			$classId = ( isset($timetable['class_id']) ? $timetable['class_id']: null);				
			$color = ( isset($timetable['color']) ? $timetable['color']: null);
			$day = ( isset($timetable['day']) ? $timetable['day']: null);
			$endHour = ( isset($timetable['end_hour']) ? $timetable['end_hour']: null);
			$endMinutes = ( isset($timetable['end_minutes']) ? $timetable['end_minutes']: null);
			$month = ( isset($timetable['month']) ? $timetable['month']: null);
			$startHour = ( isset($timetable['start_hour']) ? $timetable['start_hour']: null);
			$startMinutes = ( isset($timetable['start_minutes']) ? $timetable['start_minutes']: null);
			$subjectName = ( isset($timetable['subject_name']) ? $timetable['subject_name']: null);
			$termId = ( isset($timetable['term_id']) ? $timetable['term_id']: null);
			$year = ( isset($timetable['year']) ? $timetable['year']: null);
				
			$addTimetable = $db->prepare("INSERT INTO app.class_timetables(class_id, term_id, subject_name, year, month, 
            day, start_hour, start_minutes, end_hour, end_minutes, color) 
								VALUES(:classId, :termId, :subjectName, :year, :month, :day, :startHour, :startMinutes, :endHour, :endMinutes, :color)");

			$addTimetable->execute( array(':classId' => $classId, 
											':termId' => $termId, 
											':subjectName' => $subjectName, 
											':year' => $year, 
											':month' => $month,
											':day' => $day,
											':startHour' => $startHour,
											':startMinutes' => $startMinutes,
											':endHour' => $endHour,
											':endMinutes' => $endMinutes,
											':color' => $color ) );
		}
		
		$db->commit();
		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "code" => 1, "message" => "Class timetable saved successfully."));
		$db = null;
	} catch(PDOException $e) {
		$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	}

});

$app->get('/fetchClassTimetable/:classId/:termId', function ($classId, $termId) {
	// Return students school bus history
  
	$app = \Slim\Slim::getInstance();
  
	try
	{
	  $db = getDB();
  
	  // get credits
  
	  $sth = $db->prepare("SELECT c.class_name, t.term_name, ct.* FROM app.class_timetables ct
						  INNER JOIN app.classes c USING (class_id)
						  INNER JOIN app.terms t USING (term_id)
						  WHERE ct.class_id = :classId AND ct.term_id = :termId");
	  $sth->execute( array(':classId' => $classId, ':termId' => $termId));
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

$app->post('/addTeacherTimetable', function () use($app) {
	// Add class timetable
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$timetables =	( isset($allPostVars['time_tables']) ? $allPostVars['time_tables']: null);
	
	try 
	{
		$db = getDB();
		
		$db->beginTransaction();
		
		foreach($timetables as $timetable)
		{
			$teacherId = ( isset($timetable['teacher_id']) ? $timetable['teacher_id']: null);
			$classId = ( isset($timetable['class_id']) ? $timetable['class_id']: null);				
			$color = ( isset($timetable['color']) ? $timetable['color']: null);
			$day = ( isset($timetable['day']) ? $timetable['day']: null);
			$endHour = ( isset($timetable['end_hour']) ? $timetable['end_hour']: null);
			$endMinutes = ( isset($timetable['end_minutes']) ? $timetable['end_minutes']: null);
			$month = ( isset($timetable['month']) ? $timetable['month']: null);
			$startHour = ( isset($timetable['start_hour']) ? $timetable['start_hour']: null);
			$startMinutes = ( isset($timetable['start_minutes']) ? $timetable['start_minutes']: null);
			$subjectName = ( isset($timetable['subject_name']) ? $timetable['subject_name']: null);
			$termId = ( isset($timetable['term_id']) ? $timetable['term_id']: null);
			$year = ( isset($timetable['year']) ? $timetable['year']: null);
				
			$addTimetable = $db->prepare("INSERT INTO app.teacher_timetables(teacher_id, class_id, term_id, subject_name, year, month, 
            day, start_hour, start_minutes, end_hour, end_minutes, color) 
								VALUES(:teacherId, :classId, :termId, :subjectName, :year, :month, :day, :startHour, :startMinutes, :endHour, :endMinutes, :color)");

			$addTimetable->execute( array(':teacherId' => $teacherId,
											':classId' => $classId, 
											':termId' => $termId, 
											':subjectName' => $subjectName, 
											':year' => $year, 
											':month' => $month,
											':day' => $day,
											':startHour' => $startHour,
											':startMinutes' => $startMinutes,
											':endHour' => $endHour,
											':endMinutes' => $endMinutes,
											':color' => $color ) );
		}
		
		$db->commit();
		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "code" => 1, "message" => "Teacher timetable saved successfully."));
		$db = null;
	} catch(PDOException $e) {
		$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	}

});

$app->get('/fetchTeacherTimetable/:teacherId/:termId', function ($teacherId, $termId) {
	// Return students school bus history
  
	$app = \Slim\Slim::getInstance();
  
	try
	{
	  $db = getDB();
  
	  // get credits
  
	  $sth = $db->prepare("SELECT c.class_name, t.term_name, tt.* FROM app.teacher_timetables tt
						  INNER JOIN app.classes c USING (class_id)
						  INNER JOIN app.terms t USING (term_id)
						  WHERE tt.teacher_id = :teacherId AND tt.term_id = :termId");
	  $sth->execute( array(':teacherId' => $teacherId, ':termId' => $termId));
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
