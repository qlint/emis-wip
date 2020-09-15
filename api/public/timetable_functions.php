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
			$subjectId = ( isset($timetable['subject_id']) ? $timetable['subject_id']: null);
			$subjectName = ( isset($timetable['subject_name']) ? $timetable['subject_name']: null);
			$termId = ( isset($timetable['term_id']) ? $timetable['term_id']: null);
			$year = ( isset($timetable['year']) ? $timetable['year']: null);

			$addTimetable = $db->prepare("INSERT INTO app.class_timetables(class_id, term_id, subject_name, year, month,
            day, start_hour, start_minutes, end_hour, end_minutes, color, subject_id)
								VALUES(:classId, :termId, :subjectName, :year, :month, :day, :startHour, :startMinutes, :endHour, :endMinutes, :color, :subjectId)");

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
											':color' => $color,
											':subjectId' => $subjectId ) );
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
			$subjectId = ( isset($timetable['subject_id']) ? $timetable['subject_id']: null);
			$subjectName = ( isset($timetable['subject_name']) ? $timetable['subject_name']: null);
			$termId = ( isset($timetable['term_id']) ? $timetable['term_id']: null);
			$year = ( isset($timetable['year']) ? $timetable['year']: null);

			$addTimetable = $db->prepare("INSERT INTO app.teacher_timetables(teacher_id, class_id, term_id, subject_name, year, month,
            day, start_hour, start_minutes, end_hour, end_minutes, color, subject_id)
								VALUES(:teacherId, :classId, :termId, :subjectName, :year, :month, :day, :startHour, :startMinutes, :endHour, :endMinutes, :color, :subjectId)");

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
											':color' => $color,
											':subjectId' => $subjectId ) );
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

$app->get('/getStudentTimetable/:studentId', function ($studentId) {
  //Show student timetable

$app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
      $sth = $db->prepare("SELECT student_name, class_id, class_name, term_id, term_name, year,
                            array_to_json(array_agg(day_lessons)) AS timetable
                          FROM (
                            SELECT student_name, class_id, class_name, term_id, term_name, year,
                              '{\"' || day || '\":' || day_details ||'}' AS day_lessons
                            FROM (
                              SELECT student_name, class_id, class_name, term_id, term_name, year, day,
                                array_to_json(array_agg(subject_details)) AS day_details
                              FROM (
                                SELECT s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                                  ct.class_id, c.class_name, ct.term_id, t.term_name, ct.year, ct.day, row_to_json(a) AS subject_details
                                FROM (
                                  SELECT class_timetable_id, ctt.day, ctt.subject_id, ctt.subject_name, ctt.start_hour, ctt.start_minutes, ctt.end_hour, ctt.end_minutes,
                                    e.emp_id AS teacher_id, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name AS teacher_name
                                  FROM app.class_timetables ctt
                                  INNER JOIN app.subjects s USING (subject_id)
                                  LEFT JOIN app.employees e ON s.teacher_id = e.emp_id
                                  WHERE ctt.class_id = (SELECT current_class FROM app.students WHERE student_id = :studentId)
                                )a
                                INNER JOIN app.class_timetables ct USING (class_timetable_id)
                                INNER JOIN app.classes c USING (class_id)
                                INNER JOIN app.terms t USING (term_id)
                                INNER JOIN app.students s ON c.class_id = s.current_class
                                WHERE s.student_id = :studentId
                              )b
                              GROUP BY student_name, class_id, class_name, term_id, term_name, year, day
                            )c
                          )d
                          GROUP BY student_name, class_id, class_name, term_id, term_name, year");
      $sth->execute(array(':studentId' => $studentId));
      $timetable = $sth->fetch(PDO::FETCH_OBJ);

      if($timetable) {
					$timetable->timetable = json_decode($timetable->timetable);
					for ($i=0; $i < count($timetable->timetable); $i++) {
						$timetable->timetable[$i] = json_decode($timetable->timetable[$i]);
					}
          $app->response->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo json_encode(array('response' => 'success', 'data' => $timetable ));
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

$app->get('/getTeacherTimetable/:teacherId', function ($teacherId) {
  //Show teacher timetable

$app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
      $sth = $db->prepare("SELECT class_id, class_name, term_id, term_name, year,
                              array_to_json(array_agg(day_lessons)) AS timetable
                            FROM (
                              SELECT class_id, class_name, term_id, term_name, year,
                                '{\"' || day || '\":' || day_details ||'}' AS day_lessons
                              FROM (
                                SELECT class_id, class_name, term_id, term_name, year, day,
                                  array_to_json(array_agg(class_details)) AS day_details
                                FROM (
                                  SELECT tt.class_id, c.class_name, tt.term_id, t.term_name, tt.year, tt.day,
                                    row_to_json(a) AS class_details
                                  FROM (
                                    SELECT teacher_timetable_id, tt.day, tt.subject_id, tt.subject_name, tt.start_hour, tt.start_minutes, tt.end_hour, tt.end_minutes,
                                      class_name
                                    FROM app.teacher_timetables tt
                                    --INNER JOIN app.subjects s USING (subject_id)
                                    INNER JOIN app.classes c USING (class_id)
                                    WHERE tt.teacher_id = :teacherId
                                  )a
                                  INNER JOIN app.teacher_timetables tt USING (teacher_timetable_id)
                                  INNER JOIN app.classes c USING (class_id)
                                  INNER JOIN app.terms t USING (term_id)
                                  --INNER JOIN app.students s ON c.class_id = s.current_class
                                  WHERE tt.teacher_id = :teacherId
                                )b
                                GROUP BY class_id, class_name, term_id, term_name, year, day
                              )c
                            )d
                            GROUP BY class_id, class_name, term_id, term_name, year");
      $sth->execute(array(':teacherId' => $teacherId));
      $timetable = $sth->fetch(PDO::FETCH_OBJ);

      if($timetable) {
					$timetable->timetable = json_decode($timetable->timetable);
					for ($i=0; $i < count($timetable->timetable); $i++) {
	          $timetable->timetable[$i] = json_decode($timetable->timetable[$i]);
	        }
          $app->response->setStatus(200);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo json_encode(array('response' => 'success', 'data' => $timetable ));
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

?>
