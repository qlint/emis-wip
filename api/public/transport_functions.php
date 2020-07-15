<?php
$app->get('/getAllBuses/:status', function ($status) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

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

$app->get('/getAllAssignedBuses/:status', function ($status) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT buses.bus_id, bus_type, bus_registration, bus_description, bus_capacity, destinations, trip_name
                        FROM app.buses
                        LEFT JOIN app.schoolbus_bus_trips USING (bus_id)
                        LEFT JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                        WHERE buses.active = :status
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

$app->get('/getBusDestinations/:busId', function ($busId) {
  //Get the selected bus' destinations

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT destinations FROM app.buses WHERE bus_id = :busId");
    $sth->execute( array(':busId' => $busId) );

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

$app->get('/getStudentTransportDetails/:studentId', function ($studentId) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT DISTINCT ON (two.trip_id)two.trip_id, one.student_id, student_destination, bus_id, bus, destinations, bus_driver, bus_guide, driver_name, driver_telephone, guide_name,
	trip_name, fee_item, three.route, amount, parent_name, parent_telephone
FROM
			(
        SELECT raw1.*, trip_name
				FROM (
  					SELECT s.student_id, s.destination AS student_destination, b.bus_id, b.bus_type || ' - ' || b.bus_registration AS bus, b.destinations, b.bus_driver,
  						b.bus_guide, UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS trip_id,
  						e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as driver_name, e.telephone AS driver_telephone,
  						e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name,
  						g.first_name || ' ' || coalesce(g.middle_name,'') || ' ' || g.last_name as parent_name, g.telephone AS parent_telephone
  					FROM app.buses b
  					INNER JOIN app.students s ON b.destinations ILIKE '%' || s.destination || '%'
  					LEFT JOIN app.student_guardians sg USING (student_id)
  					LEFT JOIN app.guardians g USING (guardian_id)
  					LEFT JOIN app.employees e ON b.bus_driver = e.emp_id
  					LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
  					WHERE student_id = :studentId
  				)raw1
				INNER JOIN app.schoolbus_bus_trips sbt ON raw1.bus_id = sbt.bus_id AND raw1.trip_id = sbt.bus_trip_id
				INNER JOIN app.schoolbus_trips st ON st.schoolbus_trip_id = sbt.schoolbus_trip_id
                      )one
                        INNER JOIN
			                     (
                        SELECT student_id, UNNEST(string_to_array(trip_ids, ',')::int[]) AS trip_id
                        FROM app.students
                        WHERE student_id = :studentId)two
                        ON one.student_id = two.student_id AND one.trip_id = two.trip_id
                        INNER JOIN
                        (
                        SELECT sfi.student_id, sfi.fee_item_id, fi.fee_item, tr.route, fi.default_amount, sfi.amount
                  			FROM app.student_fee_items sfi
                  			INNER JOIN app.fee_items fi USING (fee_item_id)
                  			INNER JOIN app.students s USING (student_id)
                  			INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                  			WHERE student_id = :studentId
                  			AND fee_item = 'Transport')three
                                          ON two.student_id = three.student_id");
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
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentTripOptions/:studentId', function ($studentId) {
  //Show available student trip options

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT trip_id, trip_name, bus_type || ' ' || bus_registration AS trip_bus, destinations AS trip_destinations FROM (
                          	SELECT sbt.bus_trip_id AS trip_id, st.trip_name, b.bus_id, b.bus_type, b.bus_registration, b.destinations
                          	FROM app.schoolbus_trips st
                          	INNER JOIN app.schoolbus_bus_trips sbt USING (schoolbus_trip_id)
                          	INNER JOIN app.buses b USING (bus_id)
                          	INNER JOIN app.students s ON b.destinations ILIKE '%' || s.destination || '%'
                          	WHERE b.destinations IS NOT NULL
                          	AND s.student_id = :studentId
                          )a");
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
      $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
      echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getActiveRoutes/:status', function ($status) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

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

$app->get('/getAllDrivers', function () {
  //Show parents associated with teacher's students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

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

$app->get('/getAllEmployeesExceptDrivers', function () {
  //Show parents associated with teacher's students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

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

$app->get('/getAllBusesRoutesAndDrivers/:status', function ($status) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT bus_id, bus_type, bus_registration, bus_description, bus_capacity, destinations, trip_name, bus_driver,
                        	(case when driver_name is null then 'Unassigned' else driver_name end) as driver_name,
                        	bus_guide, (case when guide_name is null then 'Unassigned' else guide_name end) as guide_name
                        FROM(
                        	SELECT buses.bus_id, bus_type, bus_registration, bus_description, bus_capacity, destinations, trip_name, bus_driver, bus_guide,
                        		e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as driver_name,
                        		e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name
                        	FROM app.buses
                        	FULL OUTER JOIN app.employees e ON buses.bus_driver = e.emp_id
                        	FULL OUTER JOIN app.employees e2 ON buses.bus_guide = e2.emp_id
                        	FULL OUTER JOIN app.schoolbus_bus_trips USING (bus_id)
				FULL OUTER JOIN app.schoolbus_trips USING (schoolbus_trip_id)
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

$app->get('/getSchoolBusRouteSharing', function () {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT student_count, route_id, route, COUNT(route) AS buses_count, ARRAY_AGG(the_bus) AS buses FROM (
                        		SELECT student_count, route_id, route, '{\"bus_id\":' || bus_id ||',\"bus_type\":\"' || bus_type ||'\",\"bus_registration\":\"' || bus_registration || '\"}' AS the_bus FROM (
                        			SELECT bus_id, bus_type, bus_registration, route_id, route, COUNT(student_name) AS student_count
                                                FROM(
                                                	SELECT bus_id, bus_type, bus_registration, route_id, route,
                                                	s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name
                                                	FROM app.buses
                                                	INNER JOIN app.transport_routes ON buses.route_id = transport_routes.transport_id
                                                	INNER JOIN app.students s ON transport_routes.transport_id = s.transport_route_id
                                                	WHERE buses.active = TRUE AND s.active = TRUE
                                                	ORDER BY bus_id DESC
                                                )A
                                                GROUP BY bus_id, bus_type, bus_registration, route_id, route
                                                ORDER BY route ASC
                        		)B
                        	)C
                        	GROUP BY student_count, route_id, route
                          HAVING COUNT(route) > 1");
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

$app->get('/getStudentsInBus/:busId', function ($busId) {
  // Get all students in the given school bus

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT one.*, b.bus_id, b.bus_type || ' - ' || b.bus_registration AS bus, b.destinations, b.bus_driver,
                						b.bus_guide, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as driver_name,
                						e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name, st.trip_name,
                						st.class_cats
                					FROM (
                						SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name, class_name, current_class,
                                          				s.destination AS student_destination, UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS student_trips, tr.route
                                                                  FROM app.students s
                                                                  INNER JOIN app.classes c ON s.current_class = c.class_id
                                                                  INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                                                                  WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                					)one
                					INNER JOIN app.schoolbus_bus_trips sbt ON one.student_trips = sbt.bus_trip_id
                                                        INNER JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                                                        INNER JOIN app.buses b USING (bus_id)
                                                        LEFT JOIN app.employees e ON b.bus_driver = e.emp_id
                					LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
                					WHERE sbt.bus_id = :busId
                					ORDER BY class_name ASC, student_name ASC");
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

$app->get('/getStudentsInRoute/:routeId', function ($routeId) {
  // Get all students in the given school bus

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name, tr.route
                        FROM app.students s
                        INNER JOIN app.classes c ON s.current_class = c.class_id
                        INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                        WHERE s.transport_route_id = :routeId AND s.active IS TRUE");
    $sth->execute( array(':routeId' => $routeId) );
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

$app->get('/getBusesWithPickDropHistory', function () {
  // Return school buses that have been used to either pick or drop off students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT DISTINCT ON (sh.bus_id) sh.bus_id, sh.bus_type, sh.bus_registration, sh.route_id, tr.route
                        FROM app.schoolbus_history sh
                        LEFT JOIN app.transport_routes tr ON sh.route_id = tr.transport_id");
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

$app->get('/getBusPickUpDropOffHistory/:busId/:activity/:year/:month/:day', function ($busId, $activity, $year, $month, $day) {
  // Return students school bus history

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    // get credits

    $sth = $db->prepare("SELECT sh.bus_id, sh.bus_type, sh.bus_registration, sh.route_id, sh.bus_driver AS driver_id, sh.bus_guide AS guide_id, sh.gps, sh.gps_time,
                        	sh.activity, tr.route, e1.first_name || ' ' || coalesce(e1.middle_name,'') || ' ' || e1.last_name AS driver_name,
                        	e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name AS assistant_name,
                        	sh.student_id, sh.gps_order, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name
                        FROM app.schoolbus_history sh
                        INNER JOIN app.students s USING (student_id)
                        INNER JOIN app.classes c ON s.current_class = c.class_id
                        INNER JOIN app.transport_routes tr ON sh.route_id = tr.transport_id
                        LEFT JOIN app.employees e1 ON sh.bus_driver = e1.emp_id
                        LEFT JOIN app.employees e2 ON sh.bus_guide = e2.emp_id
                        WHERE sh.bus_id = :busId AND sh.activity = :activity AND date_part('year', sh.creation_date) = :year AND date_part('month', sh.creation_date) = :month AND date_part('day', sh.creation_date) = :day
                        ORDER BY sh.gps_time ASC");
    $sth->execute( array(':busId' => $busId, ':activity' => $activity, ':year' => $year, ':month' => $month, ':day' => $day));
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

$app->get('/getStudentAttendance/:studentId/:date', function ($studentId, $date) {
  // Return students arrears for before a given date

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT * FROM app.buses
                            WHERE student_id = :studentID
                            AND date <= :date");
    $sth->execute( array(':studentID' => $studentId, ':date' => $date));
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
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->post('/createSchoolBus', function () use($app) {
  // Add student
  $allPostVars = json_decode($app->request()->getBody(),true);

  $busType = ( isset($allPostVars['bus_type']) ? $allPostVars['bus_type']: null);
  $busRegistration = ( isset($allPostVars['bus_registration']) ? $allPostVars['bus_registration']: null);
  $busDescription = ( isset($allPostVars['bus_description']) ? $allPostVars['bus_description']: null);
  $busCapacity = ( isset($allPostVars['bus_capacity']) ? $allPostVars['bus_capacity']: null);

  try
  {
    $db = getDB();

    $busInsert = $db->prepare("INSERT INTO app.buses(bus_type, bus_registration, bus_description, bus_capacity)
            VALUES(:busType,:busRegistration,:busDescription,:busCapacity);");

    $db->beginTransaction();

    $busInsert->execute( array(':busType' => $busType,
              ':busRegistration' => $busRegistration,
              ':busDescription' => $busDescription,
              ':busCapacity' => $busCapacity
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

$app->delete('/deleteBus/:busId', function ($busId) {
  // delete guardian

  $app = \Slim\Slim::getInstance();

  try
  {
    // remove from client database
    $db = getDB();
    $sth = $db->prepare("DELETE FROM app.buses WHERE bus_id = :busId");
    $sth->execute( array(':busId' => $busId) );
    $db = null;

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
  } catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/assignBusToRoute', function () use($app) {
    // Update class status

	$allPostVars = json_decode($app->request()->getBody(),true);
	$busId =	( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);
  $destinations =	( isset($allPostVars['destinations']) ? $allPostVars['destinations']: null);

    try
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.buses
							SET destinations = :destinations,
								modified_date = now()
							WHERE bus_id = :busId
							");
        $sth->execute( array(':busId' => $busId, ':destinations' => $destinations	) );

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

$app->get('/getAllSchoolBusTrips', function () {
  // Return school buses that have been used to either pick or drop off students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT * FROM app.schoolbus_trips ORDER BY trip_name");
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

$app->get('/getSchoolBusTrips/:tripId', function ($tripId) {

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT st.schoolbus_trip_id, trip_name, bus_id, class_cats
                          FROM app.schoolbus_trips st
                          LEFT JOIN app.schoolbus_bus_trips USING (schoolbus_trip_id)
                          LEFT JOIN app.buses USING (bus_id)
                          WHERE st.schoolbus_trip_id = :tripId");
    $sth->execute(array(':tripId' => $tripId));
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

$app->get('/getBusTrips/:busId', function ($busId) {

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT sbt.bus_trip_id, st.schoolbus_trip_id, trip_name, bus_id, bus_type || ' - ' || bus_registration AS bus, st.class_cats
                          FROM app.schoolbus_trips st
                          INNER JOIN app.schoolbus_bus_trips sbt USING (schoolbus_trip_id)
                          INNER JOIN app.buses b USING (bus_id)
                          WHERE sbt.bus_id = :busId");
    $sth->execute(array(':busId' => $busId));
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

$app->post('/createSchoolBusTrip', function () use($app) {
  // Add student
  $allPostVars = json_decode($app->request()->getBody(),true);
  $tripName = ( isset($allPostVars['trip_name']) ? $allPostVars['trip_name']: null);
  $classCats = ( isset($allPostVars['class_cats']) ? $allPostVars['class_cats']: null);

  try
  {
    $db = getDB();

    $tripInsert = $db->prepare("INSERT INTO app.schoolbus_trips(trip_name,class_cats)
            VALUES(:tripName, :classCats);");

    $db->beginTransaction();

    $tripInsert->execute( array(':tripName' => $tripName, ':classCats' => $classCats) );

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

$app->post('/updateSchoolBusTrip', function () use($app) {
  // Add student
  $allPostVars = json_decode($app->request()->getBody(),true);
  $tripId = ( isset($allPostVars['trip_id']) ? $allPostVars['trip_id']: null);
  $busId = ( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);
  $delete = ( isset($allPostVars['delete']) ? $allPostVars['delete']: null);
  $deleteId = ( isset($allPostVars['bus_trip_id']) ? $allPostVars['bus_trip_id']: null);

  try
  {
    $db = getDB();

    if($delete === 'NO' || $delete === null){
      $busTripInsert = $db->prepare("INSERT INTO app.schoolbus_bus_trips(schoolbus_trip_id, bus_id)
                                  VALUES (:tripId, :busId);");

      $db->beginTransaction();
      $busTripInsert->execute( array(':busId' => $busId, ':tripId' => $tripId) );
      $db->commit();
    }elseif($delete === 'YES'){
      $busTripDelete = $db->prepare("DELETE FROM app.schoolbus_bus_trips
                                  WHERE bus_trip_id = :deleteId;");

      $db->beginTransaction();
      $busTripDelete->execute( array(':deleteId' => $deleteId) );
      $db->commit();
    }
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

$app->put('/assignPersonnelToBus', function () use($app) {
    // Update class status

	$allPostVars = json_decode($app->request()->getBody(),true);
	$busId =	( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);
	$busDriver =	( isset($allPostVars['bus_driver']) ? $allPostVars['bus_driver']: null);
	$busGuide =	( isset($allPostVars['bus_guide']) ? $allPostVars['bus_guide']: null);

    try
    {
        $db = getDB();
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

$app->get('/getDriverOrGuideRouteBusStudents/:empId', function ($empId) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

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

$app->post('/createSchoolBusHistory', function () use($app) {
  // Add student
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
    $db = getDB();

    $busInsert = $db->prepare("INSERT INTO app.schoolbus_history(bus_id, bus_type, bus_registration, route_id, bus_driver, bus_guide, gps, gps_time, gps_order, activity, student_id)
            VALUES(:busId,:busType,:busRegistration,:routeId,:busDriver,:busGuide,:gps,:gpsTime,:gpsOrder,:activity,:studentId);");

    $db->beginTransaction();

    $busInsert->execute( array(':busId' => $busId, ':busType' => $busType,
              ':busRegistration' => $busRegistration, ':routeId' => $routeId, ':busDriver' => $busDriver, ':busGuide' => $busGuide, ':gps' => $gps, ':gpsTime' => $gpsTime, ':gpsOrder' => $gpsOrder, ':activity' => $activity, ':studentId' => $studentId
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

$app->post('/assignStudentToBus', function () use($app) {
  // Assign student to bus
  $allPostVars = json_decode($app->request()->getBody(),true);

  $studentId =  ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $busId =     ( isset($allPostVars['bus_id']) ? $allPostVars['bus_id']: null);

  try
  {
    $db = getDB();

    $studentCheckQry = $db->query("SELECT (CASE
                                    		WHEN EXISTS (SELECT student_id FROM app.student_buses WHERE student_id = $studentId) THEN 'update'
                                    		ELSE 'insert'
                                    	END) AS status");
    $studentStatus = $studentCheckQry->fetch(PDO::FETCH_OBJ);
    $studentStatus = $studentStatus->status;

    if($studentStatus === "update"){
      $updateStudentInBus = $db->prepare("UPDATE app.student_buses SET bus_id = :busId
                                  WHERE student_id = :studentId;");
      $db->beginTransaction();
      $updateStudentInBus->execute( array(':studentId' => $studentId, ':busId' => $busId) );
      $db->commit();
    } else {
      $insertStudentToBus = $db->prepare("INSERT INTO app.student_buses(student_id,bus_id) VALUES(:studentId,:busId);");
      $db->beginTransaction();
      $insertStudentToBus->execute( array(':studentId' => $studentId, ':busId' => $busId) );
      $db->commit();
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1, "data" => "Assigned to bus successfully."));
    $db = null;
  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getAlreadyAssignedStudentsInBus', function () {
  // Return students arrears for before a given date

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT student_id, bus_id,
                          s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name,
                          b.bus_type, b.bus_registration
                          FROM app.student_buses
                          INNER JOIN app.students s USING (student_id)
                          INNER JOIN app.buses b USING (bus_id)");
    $sth->execute( array());
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

$app->get('/getTransportCards/:studentId', function ($studentId) {
    //Show transport cards

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        if($studentId === "0"){
          $sth = $db->prepare("SELECT student_id, admission_number, student_name, class_name, neighborhood, billed,
                              			array_agg('{\"trip_id\":' || student_trip_id || ',\"trip_name\":\"' || trip_name || '\",\"bus\":\"' || bus || '\",\"dscription\":\"' || bus_description || '\",\"driver_name\":\"' || driver_name || '\",\"driver_telephone\":\"' || driver_telephone || '\"}') AS trip_details
                              		FROM (
                              			SELECT two.*, st.trip_name
                              			FROM (
                              				SELECT one.*, b.bus_id, b.bus_registration AS bus, bus_description, b.bus_driver AS driver_id, b.bus_guide AS guide_id,
                              					e.first_name as driver_name, e.telephone AS driver_telephone,
                                					e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name
                              				FROM (
                              					SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                              						s.destination AS student_destination, s.admission_number, class_name, s.destination AS neighborhood, UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS student_trip_id,
                              						CASE WHEN s.transport_route_id IS NOT NULL THEN 'YES' ELSE 'NO' END AS billed
                                					FROM app.students s
                                					INNER JOIN app.classes c ON s.current_class = c.class_id
                                					WHERE s.active IS TRUE AND s.trip_ids IS NOT NULL AND s.transport_route_id IS NOT NULL
                                				)one
                                				INNER JOIN app.buses b ON b.destinations ILIKE '%' || one.neighborhood || '%'
                                				LEFT JOIN app.employees e ON b.bus_driver = e.emp_id
                                				LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
                                				ORDER BY student_id ASC
                                			)two
                                			INNER JOIN app.schoolbus_bus_trips sbt ON two.bus_id = sbt.bus_id AND two.student_trip_id = sbt.bus_trip_id
                                			INNER JOIN app.schoolbus_trips st ON sbt.schoolbus_trip_id = st.schoolbus_trip_id
                                			ORDER BY student_id ASC
                                		)three
                                		GROUP BY student_id, admission_number, student_name, class_name, neighborhood, billed
                                		ORDER BY class_name ASC, student_name ASC");
          $sth->execute();
          $results = $sth->fetchAll(PDO::FETCH_OBJ);
        }else{
          $sth = $db->prepare("		SELECT student_id, admission_number, student_name, class_name, neighborhood, billed,
                              			array_agg('{\"trip_id\":' || student_trip_id || ',\"trip_name\":\"' || trip_name || '\",\"bus\":\"' || bus || '\",\"dscription\":\"' || bus_description || '\",\"driver_name\":\"' || driver_name || '\",\"driver_telephone\":\"' || driver_telephone || '\"}') AS trip_details
                              		FROM (
                              			SELECT two.*, st.trip_name
                              			FROM (
                              				SELECT one.*, b.bus_id, b.bus_registration AS bus, bus_description, b.bus_driver AS driver_id, b.bus_guide AS guide_id,
                              					e.first_name as driver_name, e.telephone AS driver_telephone,
                                					e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name
                              				FROM (
                              					SELECT s.student_id, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name,
                              						s.destination AS student_destination, s.admission_number, class_name, s.destination AS neighborhood, UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS student_trip_id,
                              						CASE WHEN s.transport_route_id IS NOT NULL THEN 'YES' ELSE 'NO' END AS billed
                                					FROM app.students s
                                					INNER JOIN app.classes c ON s.current_class = c.class_id
                                					WHERE s.active IS TRUE AND s.trip_ids IS NOT NULL AND s.transport_route_id IS NOT NULL
                                				)one
                                				INNER JOIN app.buses b ON b.destinations ILIKE '%' || one.neighborhood || '%'
                                				LEFT JOIN app.employees e ON b.bus_driver = e.emp_id
                                				LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
                                				ORDER BY student_id ASC
                                			)two
                                			INNER JOIN app.schoolbus_bus_trips sbt ON two.bus_id = sbt.bus_id AND two.student_trip_id = sbt.bus_trip_id
                                			INNER JOIN app.schoolbus_trips st ON sbt.schoolbus_trip_id = st.schoolbus_trip_id
                                			ORDER BY student_id ASC
                                		)three
                                    WHERE student_id = :studentId
                                		GROUP BY student_id, admission_number, student_name, class_name, neighborhood, billed
                                		ORDER BY class_name ASC, student_name ASC");
          $sth->execute( array(':studentId' => $studentId) );
          $results = $sth->fetchAll(PDO::FETCH_OBJ);
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

/* Dashboard */

$app->get('/getStudentsBusUsage', function () {
  // Get bus usage by student count

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT bus_id, bus, COUNT(bus_id) AS students FROM (
          									SELECT one.*, b.bus_id, b.bus_type || ' - ' || b.bus_registration AS bus, b.destinations, b.bus_driver,
                          						b.bus_guide, e.first_name || ' ' || coalesce(e.middle_name,'') || ' ' || e.last_name as driver_name,
                          						e2.first_name || ' ' || coalesce(e2.middle_name,'') || ' ' || e2.last_name as guide_name, st.trip_name,
                          						st.class_cats
                          					FROM (
                          						SELECT s.student_id, s.admission_number, s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name as student_name, class_name, current_class,
                                                    				s.destination AS student_destination, UNNEST(string_to_array(s.trip_ids, ',')::int[]) AS student_trips, tr.route
                                                                            FROM app.students s
                                                                            INNER JOIN app.classes c ON s.current_class = c.class_id
                                                                            INNER JOIN app.transport_routes tr ON s.transport_route_id = tr.transport_id
                                                                            WHERE s.active IS TRUE AND s.transport_route_id IS NOT NULL
                          					)one
                          					INNER JOIN app.schoolbus_bus_trips sbt ON one.student_trips = sbt.bus_trip_id
                                                                  INNER JOIN app.schoolbus_trips st USING (schoolbus_trip_id)
                                                                  INNER JOIN app.buses b USING (bus_id)
                                                                  LEFT JOIN app.employees e ON b.bus_driver = e.emp_id
                          					LEFT JOIN app.employees e2 ON b.bus_guide = e2.emp_id
                          					ORDER BY class_name ASC, student_name ASC
          								)two
          								GROUP BY bus_id, bus
          								ORDER BY students DESC");
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

$app->get('/getPopularDestinations', function () {
  // Get popular destinations by where students live

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

     $sth = $db->prepare("SELECT destination, COUNT(destination) AS student_count FROM (
                          	SELECT student_id, destination
                          	FROM app.students
                          	WHERE active IS TRUE AND destination IS NOT NULL
                          )a
                          GROUP BY destination
                          ORDER BY student_count DESC");
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

?>
