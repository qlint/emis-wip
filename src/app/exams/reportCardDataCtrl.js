'use strict';

angular.module('eduwebApp').
controller('reportCardDataCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window','$parse', '$compile',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, Bulkdata, $timeout, $window, $parse, $compile){

	var newBulkData = Bulkdata.filter(function(e){ return e === 0 || e }); //removes undefined values from array
	console.log(newBulkData);

	$scope.studentReports =[]
	$scope.studentReports = newBulkData;
	$scope.data = newBulkData[0];
	if( $scope.data == undefined || $scope.data == null){
				// we open a modal window to inform the user of a problem
				// instanciate new modal
				var modal = new tingle.modal({
				    footer: true,
				    stickyFooter: false,
				    closeMethods: ['overlay', 'button', 'escape'],
				    closeLabel: "Close",
				    cssClass: ['custom-class-1', 'custom-class-2'],
				    onOpen: function() {
				        console.log('modal open');
				    },
				    onClose: function() {
				        console.log('modal closed');
				    },
				    beforeClose: function() {
				        // here's goes some logic
				        // e.g. save content before closing the modal
				        return true; // close the modal
				        return false; // nothing happens
				    }
				});

				// set modal content
				modal.setContent('<h1>Oops!</h1><br><h4>There seems to be a problem with the class you selected.</h4><br><h4>The system compiles already generated report cards. Please make sure there are generated report cards in this class.</h4>');

				// add a button
				modal.addFooterBtn('Got It', 'tingle-btn tingle-btn--primary', function() {
				    // here goes some logic
				    modal.close();
				});

				// add another button
				// modal.addFooterBtn('Dangerous action !', 'tingle-btn tingle-btn--danger', function() {
				//     // here goes some logic
				//     modal.close();
				// });

				// open the modal
				modal.open();
	}
	console.log($scope.data);
	$rootScope.isPrinting = false;
	$scope.student = $scope.data.student || undefined;
	$scope.reportCardType = ($scope.student !== undefined ? $scope.student.report_card_type : undefined);
	$scope.showSelect = ( $scope.student === undefined ? true : false );
	$scope.classes = $scope.data.classes || [];
	$scope.terms = $scope.data.terms || [];
	$scope.filters = $scope.data.filters || [];
	$scope.adding = $scope.data.adding;
	$scope.thestudent = {};
	$scope.examTypes = {};
	$scope.comments = {};
	$scope.principal_comment = {};
	$scope.parentPortalAcitve = ( $rootScope.currentUser.settings['Parent Portal'] && $rootScope.currentUser.settings['Parent Portal'] == 'Yes' ? true : false);
	$scope.entity_id = $scope.data.entity_id;
	$scope.canPrint = false;
	$scope.isSchool = ( window.location.host.split('.')[0] == "newlightgirls" ? true : false);
	$scope.isStudentImage = ( window.location.host.split('.')[0] == "rongaiboys" ? true : false);
	// console.log($scope.isSchool);

	$scope.report = {};
	$scope.report.published = false;

	//$scope.showReportCard = false;

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );
	$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );

	$scope.no_img_found = "assets/x.png";
	$scope.motto = "";

	var cnt=0;








	var initializeController = function(studentData, student_id)
	{
		 // appendDiv(ids);


		if( $scope.studentReports[student_id].report_card_type === 'Standard' )
		{
			// get exam types
			apiService.getExamTypes(studentData.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.studentReports[student_id].rawExamTypes = result.data;
					if( studentData.reportCardType === undefined ) $scope.studentReports[student_id].reportCardType = studentData.report_card_type; // set the report card type if not passed in

					initReportCard(studentData, student_id);
				}

			}, apiError);
		}
		else if( $scope.studentReports[student_id].report_card_type === 'Standard-v.2' )
		{
			// get exam types
			apiService.getExamTypes(studentData.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.studentReports[student_id].rawExamTypes = result.data;
					if( studentData.reportCardType === undefined ) $scope.studentReports[student_id].reportCardType = studentData.report_card_type; // set the report card type if not passed in
					initReportCard(studentData, student_id);
				}

			}, apiError);
		}
		else if( $scope.studentReports[student_id].report_card_type === 'Standard-v.3' )
		{
			// get exam types
			apiService.getExamTypes(studentData.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.studentReports[student_id].rawExamTypes = result.data;
					if( studentData.reportCardType === undefined ) $scope.studentReports[student_id].reportCardType = studentData.report_card_type; // set the report card type if not passed in
					initReportCard(studentData, student_id);
				}

			}, apiError);
		}
		else
		{
			initReportCard(studentData, student_id);
		}

		// if no student was passed in, get list of students for select dropdown

		//SO: Not  necessary
		/* if( $scope.student === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				var params = $rootScope.currentUser.emp_id + '/' + true;
				apiService.getTeacherStudents(params, loadStudents, apiError);
				// apiService.getStreamPosition(params, loadStudents, apiError);
			}
			else
			{
				apiService.getAllStudents(true, loadStudents, apiError);
				// apiService.getStreamPosition(params, loadStudents, apiError);
			}
		} */


	}
	//$timeout(initializeController,1);


	var initReportCard = function(studentData, student_id)
	{

		if( studentData.reportData !== undefined )
		{
			/* passing in a report to view, load it */
			$scope.studentReports[student_id].savedReportData = angular.fromJson(studentData.reportData);
			$scope.studentReports[student_id].originalData = angular.copy(studentData.savedReportData);
			$scope.studentReports[student_id].report = {};
			$scope.studentReports[student_id].report.report_card_id = studentData.report_card_id;
			$scope.studentReports[student_id].report.class_name = studentData.class_name;
			$scope.studentReports[student_id].report.class_id = studentData.class_id;

			var termName = studentData.term_name;
			// we only want the number
			termName = termName.split(' ');
			$scope.studentReports[student_id].report.term = termName[1];

			$scope.studentReports[student_id].report.term_id = studentData.term_id;
			$scope.studentReports[student_id].report.year = studentData.year;
			$scope.studentReports[student_id].report.published = studentData.published;
			$scope.studentReports[student_id].reportCardType = studentData.report_card_type;
			$scope.studentReports[student_id].report.teacher_id = studentData.teacher_id;
			$scope.studentReports[student_id].report.teacher_name = studentData.teacher_name;

			$scope.studentReports[student_id].comments = {};
			$scope.studentReports[student_id].comments.teacher_name = studentData.teacher_name;
			//$scope.report.head_teacher_name = data.head_teacher_name;
			$scope.studentReports[student_id].report.date = studentData.date;
			$scope.studentReports[student_id].nextTermStartDate = studentData.savedReportData.nextTerm;
			$scope.studentReports[student_id].currentTermEndDate = studentData.savedReportData.closingDate;
			$scope.studentReports[student_id].overallLastTerm = studentData.overallLastTerm;
			$scope.studentReports[student_id].overallLastTermByAverage = studentData.overallLastTermByAverage;
			$scope.studentReports[student_id].subjectOverall = studentData.subjectOverall;
			$scope.studentReports[student_id].subjectOverallBySum = studentData.subjectOverallBySum;
			$scope.studentReports[student_id].subjectOverallByAvg = studentData.subjectOverallByAvg;
			$scope.studentReports[student_id].graphPoints = studentData.graphPoints;
			$scope.studentReports[student_id].currentClassPosition = studentData.currentClassPosition;

			$scope.savedReport = true;
			$scope.canPrint = true;
			$scope.canDelete = ( $scope.isTeacher ? false : true);
			$scope.filters = studentData.filters;
			$scope.studentReports[student_id].isClassTeacher = ( studentData.student.class_teacher_id == $rootScope.currentUser.emp_id ? true : false);
			$scope.studentReports[student_id].isSchool = ( window.location.host.split('.')[0] == "newlightgirls" ? true : false);
			// console.log("school = "+ window.location.host.split('.')[0] + " and isSchool = " + $scope.isSchool);
			$scope.studentReports[student_id].isStudentImage = ( window.location.host.split('.')[0] == "rongaiboys" ? true : false);

			// fetch the report cards subjects based on user type

			getExamMarksforReportCard(angular.copy(studentData), student_id);
			// var getStreamParams = angular.copy(studentData);
			// var streamTerm = getStreamParams.term_id;
			// var streamEntity = getStreamParams.entity_id;
			// var streamStudent = getStreamParams.student.student_id;
			// var streamParam = streamStudent + '/' + streamEntity + '/' + streamTerm;
			// var getStreams = getStreamPosition(angular.copy(studentData), student_id);
			// console.log(streamParam);

		}
	}

	var loadStudents = function(response)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.students = ( result.nodata ? {} : $rootScope.formatStudentData(result.data) );
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}

	}

	$scope.$watch('thestudent.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		//$scope.showReportCard = false;
		$scope.student = $scope.thestudent.selected;
		$scope.reportCardType = $scope.student.report_card_type;

		$scope.isClassTeacher = ( $scope.student.class_teacher_id == $rootScope.currentUser.emp_id ? true : false);

	});

	$scope.$watch('filters.class', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.reportCardType = newVal.report_card_type;

		if( $scope.reportCardType == 'Standard' )
		{
			// get exam types
			apiService.getExamTypes(newVal.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes = result.data;
				}

			}, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.2' )
		{
			// get exam types
			apiService.getExamTypes(newVal.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes = result.data;
				}

			}, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.3' )
		{
			// get exam types
			apiService.getExamTypes(newVal.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes = result.data;
				}

			}, apiError);
		}
	});

	$scope.$watch('filters.term', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.currentTermEndDate = newVal.end_date;

		/* get the next term based on the selected term for report card */
		apiService.getNextTerm(newVal.end_date, function(response,status){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.nextTermStartDate = ( result.nodata !== undefined ? '' : result.data.start_date);
			}
		}, apiError);
	});

	$scope.clearSelect = function($event)
	{
		$event.stopPropagation();
		$scope.thestudent.selected = undefined;
		//$scope.showReportCard = false;
		$scope.report = {};
		$scope.overall = {};
		$scope.overallByAverage = {};
		$scope.overallLastTerm = {};
		$scope.graphPoints = {};
		$scope.currentClassPosition = {};
		// $scope.streamPosition = {};
		//$scope.examTypes = {};
		$scope.reportData = undefined;
	};

	$scope.getProgressReport = function(recreate)
	{
		//$scope.showReportCard = false;
		$scope.report = {};
		$scope.overall = {};
		$scope.overallByAverage = {};
		$scope.overallLastTerm = {};
		$scope.graphPoints = {};
		$scope.currentClassPosition = {};
		// $scope.streamPosition = {};
		$scope.reportData = undefined;
		$scope.comments = {};
		$scope.recreated = false;
		$scope.savedReport = false;

		$scope.currentFilters = angular.copy($scope.filters);
		$scope.report.class_name = $scope.currentFilters.class.class_name;
		$scope.report.class_id = $scope.currentFilters.class.class_id;

		var termName = $scope.currentFilters.term.term_name;
		// we only want the number
		termName = termName.split(' ');
		$scope.report.term = termName[1];

		$scope.report.term_id = $scope.currentFilters.term.term_id;
		$scope.report.year = $scope.currentFilters.term.year;
		$scope.report.published = false;
		$scope.report.teacher_id = $scope.currentFilters.class.teacher_id;
		$scope.report.teacher_name = $scope.currentFilters.class.teacher_name;
		$scope.comments.teacher_name = $scope.currentFilters.class.teacher_name;

		// check to see if there is already a report card with this criteria
		if( recreate )
		{
			/* user has requested to recreate an existing report card, fetch student exam marks and build report */
			$scope.savedReport = false;
			$scope.recreated = true;

			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
			apiService.getStudentReportCard(params,loadReportCard, apiError);
		}
		else
		{
			getExamMarksforReportCard();
			getStudentReportCard();
			//var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			//apiService.getStudentReportCard(params,loadReportCard, apiError);
		}

	}

	var getStudentReportCard = function()
	{
		var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
		apiService.getStudentReportCard(params, loadExamMarks, apiError);
	}

	var getExamMarksforReportCard = function(studentData, student_id)
	{

		if( studentData.report_card_type == 'Standard' )
		{
			var params = studentData.student.student_id + '/' + studentData.report.class_id + '/' + studentData.report.term_id;
			if( $scope.isTeacher && !$scope.studentReports[student_id].isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			//apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);


			apiService.getExamMarksforReportCard(params, function(response){

					var result = angular.fromJson(response);
						if( result.response == 'success')
						{
							if( result.details === false )
							{
								$scope.error = true;
								$scope.errMsg = "No data found.";
							}
							else
							{


								$scope.showReportCard = true;
								buildReportBody(result.data, studentData, student_id);
								// $( "#remotegraph" ).load( "/studentgraph.html div#remotegraph" );

								/* look for saved report card data, if it was not passed in  */

								if( studentData.savedReportData === undefined )
								{
									var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
									apiService.getStudentReportCard(params,loadReportCard, apiError);
								}
								else
								{
									diffExamMarks(studentData.latestExamMarks, studentData.savedReportData, studentData, student_id);
								}

							}
						}


					}, apiError);

		}
		else if( $scope.reportCardType == 'Standard-v.2' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			//apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
					apiService.getExamMarksforReportCard(params, function(response){

					var result = angular.fromJson(response);
						if( result.response == 'success')
						{
							if( result.details === false )
							{
								$scope.error = true;
								$scope.errMsg = "No data found.";
							}
							else
							{

								$scope.showReportCard = true;
								buildReportBody(result.data, studentData, student_id);
								// $( "#remotegraph" ).load( "/studentgraph.html div#remotegraph" );

								/* look for saved report card data, if it was not passed in  */
								if( studentData.savedReportData === undefined )
								{
									var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
									apiService.getStudentReportCard(params,loadReportCard, apiError);
								}
								else
								{
									diffExamMarks(studentData.latestExamMarks, studentData.savedReportData, studentData, student_id);
								}

							}
						}


					}, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.3' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			//apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
					apiService.getExamMarksforReportCard(params, function(response){

					var result = angular.fromJson(response);
						if( result.response == 'success')
						{
							if( result.details === false )
							{
								$scope.error = true;
								$scope.errMsg = "No data found.";
							}
							else
							{

								$scope.showReportCard = true;

								buildReportBody(result.data, studentData, student_id);
								// $( "#remotegraph" ).load( "/studentgraph.html div#remotegraph" );

								/* look for saved report card data, if it was not passed in  */
								if( studentData.savedReportData === undefined )
								{
									var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
									apiService.getStudentReportCard(params,loadReportCard, apiError);
								}
								else
								{
									diffExamMarks(studentData.latestExamMarks, studentData.savedReportData, studentData, student_id);
								}

							}
						}


					}, apiError);
		}
		else
		{
			// Kindergarten and Playgroup, get subjects
			// set date to now
			$scope.report.date = moment().format('YYYY-MM-DD');

			var params = '';
			if( $scope.isTeacher && !$scope.isClassTeacher ) params = $scope.filters.class.class_cat_id + '/true/' + $rootScope.currentUser.emp_id;
			else  params = $scope.filters.class.class_cat_id + '/true/0';
			apiService.getAllSubjects(params, loadSubjects, apiError);
		}
	}

	var loadSubjects = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			if( result.nodata !== undefined )
			{
				$scope.error = true;
				$scope.errMsg = "There are no subjects assigned to this students class.";
			}
			else
			{

				$scope.showReportCard = true;
				$scope.reportData = {};
				$scope.reportData.subjects = result.data;

				/* look for saved report card data, if it was not passed in  */
				if( $scope.savedReportData === undefined )
				{
					var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
					apiService.getStudentReportCard(params,loadReportCard, apiError);
				}
				else
				{

					// recreated a report, carry over the comments
					$scope.comments = angular.copy($scope.savedReportData.comments) || {};

					angular.forEach( $scope.reportData.subjects, function(item,key){

						// get matching element of currentReportData
						var orgData = $scope.savedReportData.subjects.filter(function(newItem){
							if( newItem.subject_name == item.subject_name ) return newItem;
						})[0];
						if( orgData !== undefined )
						{
							if( $scope.reportCardType == 'Kindergarten') item.remarks = angular.copy(orgData.remarks);
							else item.skill_level = angular.copy(orgData.skill_level);
						}
					});

				}

			}

		}


	}

	var loadStreamPOsition = function(response, status)
	{
		var result = angular.fromJson(response);

		var school = window.location.host.split('.')[0];

		if (school == "karemeno" || school == "rongaiboys"){
			$scope.streamRankPosition = result.data.streamRankByMarks[0].position;
			$scope.streamRankOutOf = result.data.streamRankByMarks[0].position_out_of;

			$scope.streamRankLastTerm = result.data.streamRankLastTermByMarks;
			$scope.streamRankPositionLastTerm = result.data.streamRankLastTermByMarks[0].position;
			$scope.streamRankOutOfLastTerm = result.data.streamRankLastTermByMarks[0].position_out_of;
			console.log("Stream by marks");
		}else if (school == "newlightgirls"){
			$scope.streamRankPosition = result.data.streamRank[0].position;
			$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;

			$scope.streamRankLastTerm = result.data.streamRankLastTerm;
			$scope.streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
			$scope.streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of;
			console.log("Stream by points");
		}
		// $scope.streamRankPosition = result.data.streamRank[0].position;
		// $scope.streamRankOutOf = result.data.streamRank[0].position_out_of;
		//
		// $scope.streamRankLastTerm = result.data.streamRankLastTerm;
		// $scope.streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
		// $scope.streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of;

		console.log("Stream last term = (" + $scope.streamRankPositionLastTerm + "/" + $scope.streamRankOutOfLastTerm + ")");

			localStorage.setItem('printStreamRank', $scope.streamRankPosition);
			var getPrintRank = localStorage.getItem("printStreamRank");
			localStorage.setItem('printStreamRankOutOf', $scope.streamRankOutOf);
			var getStreamRankOutOf = localStorage.getItem("printStreamRankOutOf");

	}

	var loadExamMarks = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			if( result.details === false )
			{
				$scope.error = true;
				$scope.errMsg = "No data found.";
			}
			else
			{
				$scope.showReportCard = true;
				buildReportBody(result.data);
				// $( "#remotegraph" ).load( "/studentgraph.html div#remotegraph" );

				/* look for saved report card data, if it was not passed in  */
				if( $scope.savedReportData === undefined )
				{
					var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
					apiService.getStudentReportCard(params,loadReportCard, apiError);
				}
				else
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
				}

			}
		}
	}

	var buildReportBody = function(data, studentData, student_id)
	{

	// console.log(student_id);
		// $scope.studentReports[student_id].chart_path = "";
		var params2 = $scope.studentReports[student_id].student.student_id + '/' + $scope.filters.class.class_id + '/' + $scope.filters.term.term_id;
		// console.log(params2);
		var theChartPath = function(response, status)
		{
			// console.log("Loading chart...");
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.fetchedJson = JSON.parse(result.data.json_data);
				$scope.studentReports[student_id].chart_path = $scope.fetchedJson.chart;
				$scope.chart_path = $scope.studentReports[student_id].chart_path;
				// if( $scope.studentReports[student_id].chart_path == null ){ $scope.studentReports[student_id].chart_path = "assets/x.png";}
				// console.log($scope.chart_path);

				if( result.nodata === undefined )
				{
					/* existing report card was found, load the data */
					$scope.report = result.data;
					// console.log("fetch chart");
					// console.log(result.data);
				}
				else
				{ console.log("no data"); }
			}
		}
		apiService.getStudentReportCard(params2,theChartPath, apiError);
		// console.log($scope.chart_path);
		$scope.studentReports[student_id].chart_path = $scope.chart_path;

		var getStreamPosition = function(studentData, student_id)
		{
			var strmParams = studentData.student.student_id + '/' + studentData.entity_id + '/' +  studentData.report.term_id;

			//apiService.getStreamPosition(params, loadStreamPOsition, apiError);


				apiService.getStreamPosition(strmParams, function(response){

						var result = angular.fromJson(response);

						var school = window.location.host.split('.')[0];

						if (school == "karemeno" || school == "rongaiboys"){
							$scope.streamRankPosition = result.data.streamRankByMarks[0].position;
							$scope.streamRankOutOf = result.data.streamRankByMarks[0].position_out_of;

							$scope.streamRankLastTerm = result.data.streamRankLastTermByMarks;
							$scope.streamRankPositionLastTerm = result.data.streamRankLastTermByMarks[0].position;
							$scope.streamRankOutOfLastTerm = result.data.streamRankLastTermByMarks[0].position_out_of;
							console.log("Stream by marks");
						}else if (school == "newlightgirls"){
							$scope.streamRankPosition = result.data.streamRank[0].position;
							$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;

							$scope.streamRankLastTerm = result.data.streamRankLastTerm;
							$scope.streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
							$scope.streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of;
							console.log("Stream by points");
						}

					console.log("Stream last term = (" + $scope.streamRankPositionLastTerm + "/" + $scope.streamRankOutOfLastTerm + ")");

					var school = window.location.host.split('.')[0];

					$scope.studentReports[student_id].examMarks = data.details;
					// $scope.overallSubjectMarks = data.subjectOverall;
					// $scope.overall = data.overall;
					if (school == "karemeno" || school == "rongaiboys"){
						$scope.studentReports[student_id].overall = data.overallByAverage;
						$scope.studentReports[student_id].overallLastTerm = data.overallLastTermByAverage;
						$scope.studentReports[student_id].thisTermMarks = data.overallByAverage.current_term_marks;
						$scope.studentReports[student_id].streamRankPosition = result.data.streamRankByMarks[0].position;
						$scope.studentReports[student_id].streamRankOutOf = result.data.streamRankByMarks[0].position_out_of;
						$scope.studentReports[student_id].streamRankPositionLastTerm = result.data.streamRankLastTermByMarks[0].position;
						$scope.studentReports[student_id].streamRankOutOfLastTerm = result.data.streamRankLastTermByMarks[0].position_out_of
						console.log("K & R");
					}else if (school == "newlightgirls"){
						$scope.studentReports[student_id].overall = data.overall;
						$scope.studentReports[student_id].overallLastTerm = data.overallLastTerm;
						$scope.studentReports[student_id].thisTermMarks = data.overall.current_term_marks;
						$scope.studentReports[student_id].streamRankPosition = result.data.streamRank[0].position;
						$scope.studentReports[student_id].streamRankOutOf = result.data.streamRank[0].position_out_of;
						$scope.studentReports[student_id].streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
						$scope.studentReports[student_id].streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of
						console.log("NLT");
						// console.log($scope.overall);
					}
					// $scope.overallLastTerm = data.overallLastTerm;
					$scope.studentReports[student_id].graphPoints = data.graphPoints;
					$scope.studentReports[student_id].currentClassPosition = data.currentClassPosition[0];
					// $scope.studentReports[student_id].streamRankPosition = result.data.streamRank[0].position;
					// $scope.studentReports[student_id].streamRankOutOf = result.data.streamRank[0].position_out_of;
					// $scope.studentReports[student_id].streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
					// $scope.studentReports[student_id].streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of
					console.log("Stream Pos = " + $scope.studentReports[student_id].streamRankPosition + '/' + $scope.studentReports[student_id].streamRankOutOf + " for student " + $scope.studentReports[student_id]);
					if (school == "karemeno"){
						$scope.studentReports[student_id].overallSubjectMarks = data.subjectOverallBySum;
						console.log("K calc");
					}else if (school == "rongaiboys"){
						$scope.studentReports[student_id].overallSubjectMarks = data.subjectOverallByAvg;
						console.log("R calc");
					}else{
						$scope.studentReports[student_id].overallSubjectMarks = data.subjectOverall;
						console.log("N calc");
					}
					// $scope.thisTermMarks = data.overall.current_term_marks;
					$scope.studentReports[student_id].thisTermMarksOutOf = data.overall.current_term_marks_out_of;
					$scope.studentReports[student_id].thisTermGrade = data.overall.grade;
					$scope.studentReports[student_id].thisTermPercentage = data.overall.percentage;
					$scope.studentReports[student_id].latestExamType = data.latestExamType[0];
					$scope.studentReports[student_id].isLastExamDoneEndTerm = $scope.studentReports[student_id].latestExamType.is_last_exam;
					console.log("Has ET been done? " + $scope.studentReports[student_id].isLastExamDoneEndTerm);
					$scope.studentReports[student_id].isHideTotColumn = false;
					if ( $scope.studentReports[student_id].isLastExamDoneEndTerm == false || $scope.studentReports[student_id].isLastExamDoneEndTerm == undefined || $scope.studentReports[student_id].isLastExamDoneEndTerm == null ){
						setTimeout(function(){
							$scope.studentReports[student_id].isHideTotColumn = true;
							$scope.isSchool = false;
							// $('#colsHidden').attr('colspan',2);
							// document.getElementById("colsHidden").colSpan = "2";
							var y = document.getElementsByClassName("colsHidden");
							var j;
							console.log("There are (" + y.length + ") elements with the class (colsHidden)");
							for (j = 0; j < y.length; j++) {
							    y[j].colSpan = "2";
							}
							var x = document.getElementsByClassName("hideColTillEt");
							var i;
							console.log("There are (" + x.length + ") with the class (hideColTillEt)");
							for (i = 0; i < x.length; i++) {
							    x[i].style.display = 'none';
							}
						},30000);

					}
					console.log("Show columns? " + $scope.isSchool);

					// console.log("subject overalls variable ::>");
					// console.log($scope.overallSubjectMarks);

						if (school == "karemeno" && $scope.motto == ""){

								var motto = "LIVE JESUS IN OUR HEARTS.......... FOREVER";
								var h4Element = document.createElement('h4');
								//document.getElementById('motto').appendChild(h4Element);
								$scope.studentReports[student_id].motto = "LIVE JESUS IN OUR HEARTS.......... FOREVER";

						}else{
							var motto = "";
							var h4Element = document.createElement('h4');
							//document.getElementById('motto').appendChild(h4Element);
							$scope.motto = "";
						}

					$scope.studentReports[student_id].performanceLabels = [];
					$scope.studentReports[student_id].performanceData = [];

					angular.forEach($scope.studentReports[student_id].graphPoints, function (item, key) {
					    $scope.studentReports[student_id].performanceLabels.push(item.exam_type);
					    $scope.studentReports[student_id].performanceData.push(item.average_grade);
					});

					var lineChartData = {
					    labels: $scope.studentReports[student_id].performanceLabels,
					    datasets: [{
					        label: "performance",
					        data: $scope.studentReports[student_id].performanceData,


					        borderWidth: 2,
					        pointBorderColor: '#ffffff',
					        pointBackgroundColor: '#2D4E5E',
					        pointBorderWidth: 1,
					        radius: 4
					    }]
					};

					// console.log($scope.studentReports);
					cnt++;

					if(cnt === ids.length)
					{

					//Print

					$uibModalInstance.close($scope.studentReports);
						//$scope.openModal('exams', 'reportCardBulkPrint', 'lg', $scope.studentReports);

					}
					/* if($scope.studentReports[student_id].chart_path == "")
					{
					if ($scope.zoomed) $scope.ctx = document.getElementById("zoomedLine1").getContext("2d");
						else $scope.ctx = document.getElementById(student_id).getContext("2d");


						initChart1($scope.ctx, lineChartData, $scope.studentReports[student_id]);
						//$timeout(callAtTimeout, 2000);
						$timeout(callAtTimeout, 2000, true, student_id);
						$scope.efficiencyLoading = false;
					} */

					/* remove any exam types that have not been used for this report card */
					$scope.studentReports[student_id].examTypes = $scope.studentReports[student_id].rawExamTypes.filter(function(item){
						var found = $scope.studentReports[student_id].examMarks.filter(function(item2){
							if( item.exam_type == item2.exam_type ) return item2;
						})[0];
						if( found !== undefined ) return item;
					});

					/* group the results by subject */
					$scope.studentReports[student_id].reportData = {};
					$scope.studentReports[student_id].reportData.subjects = groupExamMarks( $scope.studentReports[student_id].examMarks );
					$scope.studentReports[student_id].latestExamMarks = angular.copy($scope.studentReports[student_id].reportData);

					// set overall
					var total_marks = 0;
					var total_grade_weight = 0;

					angular.forEach( $scope.studentReports[student_id].reportData.subjects, function(item,key){
						if( item.use_for_grading )
						{

							var overall = $scope.studentReports[student_id].overallSubjectMarks.filter(function(item2){
								if( item.subject_name == item2.subject_name ) return item2;
							})[0];

							if( overall )
							{
								item.overall_mark = overall.percentage;
								item.overall_grade = overall.grade;
								item.tot30 = overall.tot30;
								item.tot70 = overall.tot70;
								item.position = overall.rank;
								var subjNm = item.subject_name;
								if( subjNm == "Kiswahili" ){
									item.comment = overall.kiswahili_comment;
								}else{
									item.comment = overall.comment;
								}
								// item.comment = overall.comment;
								// console.log("Endterm percentage :: >>");
								// console.log(overall.percentage);

								total_marks += parseInt(overall.total_mark);
								total_grade_weight += parseInt(overall.total_grade_weight);
							}

							var graphdata = $scope.studentReports[student_id].graphPoints.filter(function(item2){
								if( item.average_grade == item2.average_grade ) return item2;
							})[0];
							if( graphdata )
							{
								item.term_grade = graphdata.average_grade;
								item.which_term = graphdata.exam_type;
							}

						}

					});


					// set totals, only add up parent subjects
					$scope.studentReports[student_id].totals = {};
					angular.forEach( $scope.studentReports[student_id].reportData.subjects, function(item,key)
					{
						if( item.use_for_grading )
						{
							angular.forEach( item.marks, function(item2,key)
							{
								if( $scope.studentReports[student_id].totals[key] === undefined ) $scope.studentReports[student_id].totals[key] = {total_mark:0, total_grade_weight:0};
								if( item.parent_subject_name == null ) $scope.studentReports[student_id].totals[key].total_mark += item2.mark;
								if( item.parent_subject_name == null ) $scope.studentReports[student_id].totals[key].total_grade_weight += item2.grade_weight;
							});
						}
					});


					if( $scope.studentReports[student_id].originalData !== undefined )
					{
						// recreated a report, carry over the comments
						$scope.studentReports[student_id].comments = angular.copy(studentData.originalData.comments) || {};

						angular.forEach( $scope.studentReports[student_id].reportData.subjects, function(item,key){

							// get matching element of currentReportData
							var orgData = $scope.studentReports[student_id].originalData.subjects.filter(function(newItem){
								if( newItem.subject_name == item.subject_name ) return newItem;
							})[0];
							var overall = $scope.studentReports[student_id].overallSubjectMarks.filter(function(item2){
								if( item.subject_name == item2.subject_name ) return item2;
							})[0];
							if(item.subject_name == "Kiswahili"){
								if( overall ) 	item.remarks = overall.kiswahili_comment;
							}else{
								if( overall ) 	item.remarks = overall.comment;
							}
							// if( overall ) 	item.remarks = overall.comment;
						});
					}

						}, apiError);

		}
		getStreamPosition(angular.copy(studentData), student_id);
		// console.log("outside api pos = " + $scope.streamRankPosition + '/' + $scope.streamRankOutOf);

		// var school = window.location.host.split('.')[0];
		//
		// $scope.studentReports[student_id].examMarks = data.details;
		// // $scope.overallSubjectMarks = data.subjectOverall;
		// // $scope.overall = data.overall;
		// if (school == "karemeno" || school == "rongaiboys"){
		// 	$scope.studentReports[student_id].overall = data.overallByAverage;
		// 	$scope.studentReports[student_id].overallLastTerm = data.overallLastTermByAverage;
		// 	$scope.studentReports[student_id].thisTermMarks = data.overallByAverage.current_term_marks;
		// 	console.log("K & R");
		// }else if (school == "newlightgirls"){
		// 	$scope.studentReports[student_id].overall = data.overall;
		// 	$scope.studentReports[student_id].overallLastTerm = data.overallLastTerm;
		// 	$scope.studentReports[student_id].thisTermMarks = data.overall.current_term_marks;
		// 	console.log("NLT");
		// 	// console.log($scope.overall);
		// }
		// // $scope.overallLastTerm = data.overallLastTerm;
		// $scope.studentReports[student_id].graphPoints = data.graphPoints;
		// $scope.studentReports[student_id].currentClassPosition = data.currentClassPosition[0];
		// $scope.studentReports[student_id].streamRankPosition = studentData.streamRankPosition;
		// $scope.studentReports[student_id].streamRankOutOf = studentData.streamRankOutOf;
		// console.log("Stream Pos = " + $scope.studentReports[student_id].streamRankPosition + '/' + $scope.studentReports[student_id].streamRankOutOf);
		// if (school == "karemeno"){
		// 	$scope.studentReports[student_id].overallSubjectMarks = data.subjectOverallBySum;
		// 	console.log("K calc");
		// }else if (school == "rongaiboys"){
		// 	$scope.studentReports[student_id].overallSubjectMarks = data.subjectOverallByAvg;
		// 	console.log("R calc");
		// }else{
		// 	$scope.studentReports[student_id].overallSubjectMarks = data.subjectOverall;
		// 	console.log("N calc");
		// }
		// // $scope.thisTermMarks = data.overall.current_term_marks;
		// $scope.studentReports[student_id].thisTermMarksOutOf = data.overall.current_term_marks_out_of;
		// $scope.studentReports[student_id].thisTermGrade = data.overall.grade;
		// $scope.studentReports[student_id].thisTermPercentage = data.overall.percentage;
		//
		// // console.log("subject overalls variable ::>");
		// // console.log($scope.overallSubjectMarks);
		//
		// 	if (school == "karemeno" && $scope.motto == ""){
		//
		// 			var motto = "LIVE JESUS IN OUR HEARTS.......... FOREVER";
		// 			var h4Element = document.createElement('h4');
		// 			//document.getElementById('motto').appendChild(h4Element);
		// 			$scope.studentReports[student_id].motto = "LIVE JESUS IN OUR HEARTS.......... FOREVER";
		//
		// 	}else{
		// 		var motto = "";
		// 		var h4Element = document.createElement('h4');
		// 		//document.getElementById('motto').appendChild(h4Element);
		// 		$scope.motto = "";
		// 	}
		//
		// $scope.studentReports[student_id].performanceLabels = [];
		// $scope.studentReports[student_id].performanceData = [];
		//
		// angular.forEach($scope.studentReports[student_id].graphPoints, function (item, key) {
		//     $scope.studentReports[student_id].performanceLabels.push(item.exam_type);
		//     $scope.studentReports[student_id].performanceData.push(item.average_grade);
		// });
		//
		// var lineChartData = {
		//     labels: $scope.studentReports[student_id].performanceLabels,
		//     datasets: [{
		//         label: "performance",
		//         data: $scope.studentReports[student_id].performanceData,
		//
		//
		//         borderWidth: 2,
		//         pointBorderColor: '#ffffff',
		//         pointBackgroundColor: '#2D4E5E',
		//         pointBorderWidth: 1,
		//         radius: 4
		//     }]
		// };
		//
		// // console.log($scope.studentReports);
		// cnt++;
		//
		// if(cnt === ids.length)
		// {
		//
		// //Print
		//
		// $uibModalInstance.close($scope.studentReports);
		// 	//$scope.openModal('exams', 'reportCardBulkPrint', 'lg', $scope.studentReports);
		//
		// }
		// /* if($scope.studentReports[student_id].chart_path == "")
		// {
		// if ($scope.zoomed) $scope.ctx = document.getElementById("zoomedLine1").getContext("2d");
		// 	else $scope.ctx = document.getElementById(student_id).getContext("2d");
		//
		//
		// 	initChart1($scope.ctx, lineChartData, $scope.studentReports[student_id]);
		// 	//$timeout(callAtTimeout, 2000);
		// 	$timeout(callAtTimeout, 2000, true, student_id);
		// 	$scope.efficiencyLoading = false;
		// } */
		//
		// /* remove any exam types that have not been used for this report card */
		// $scope.studentReports[student_id].examTypes = $scope.studentReports[student_id].rawExamTypes.filter(function(item){
		// 	var found = $scope.studentReports[student_id].examMarks.filter(function(item2){
		// 		if( item.exam_type == item2.exam_type ) return item2;
		// 	})[0];
		// 	if( found !== undefined ) return item;
		// });
		//
		// /* group the results by subject */
		// $scope.studentReports[student_id].reportData = {};
		// $scope.studentReports[student_id].reportData.subjects = groupExamMarks( $scope.studentReports[student_id].examMarks );
		// $scope.studentReports[student_id].latestExamMarks = angular.copy($scope.studentReports[student_id].reportData);
		//
		// // set overall
		// var total_marks = 0;
		// var total_grade_weight = 0;
		//
		// angular.forEach( $scope.studentReports[student_id].reportData.subjects, function(item,key){
		// 	if( item.use_for_grading )
		// 	{
		//
		// 		var overall = $scope.studentReports[student_id].overallSubjectMarks.filter(function(item2){
		// 			if( item.subject_name == item2.subject_name ) return item2;
		// 		})[0];
		//
		// 		if( overall )
		// 		{
		// 			item.overall_mark = overall.percentage;
		// 			item.overall_grade = overall.grade;
		// 			item.tot30 = overall.tot30;
		// 			item.tot70 = overall.tot70;
		// 			item.position = overall.rank;
		// 			var subjNm = item.subject_name;
		// 			if( subjNm == "Kiswahili" ){
		// 				item.comment = overall.kiswahili_comment;
		// 			}else{
		// 				item.comment = overall.comment;
		// 			}
		// 			// item.comment = overall.comment;
		// 			// console.log("Endterm percentage :: >>");
		// 			// console.log(overall.percentage);
		//
		// 			total_marks += parseInt(overall.total_mark);
		// 			total_grade_weight += parseInt(overall.total_grade_weight);
		// 		}
		//
		// 		var graphdata = $scope.studentReports[student_id].graphPoints.filter(function(item2){
		// 			if( item.average_grade == item2.average_grade ) return item2;
		// 		})[0];
		// 		if( graphdata )
		// 		{
		// 			item.term_grade = graphdata.average_grade;
		// 			item.which_term = graphdata.exam_type;
		// 		}
		//
		// 	}
		//
		// });
		//
		//
		// // set totals, only add up parent subjects
		// $scope.studentReports[student_id].totals = {};
		// angular.forEach( $scope.studentReports[student_id].reportData.subjects, function(item,key)
		// {
		// 	if( item.use_for_grading )
		// 	{
		// 		angular.forEach( item.marks, function(item2,key)
		// 		{
		// 			if( $scope.studentReports[student_id].totals[key] === undefined ) $scope.studentReports[student_id].totals[key] = {total_mark:0, total_grade_weight:0};
		// 			if( item.parent_subject_name == null ) $scope.studentReports[student_id].totals[key].total_mark += item2.mark;
		// 			if( item.parent_subject_name == null ) $scope.studentReports[student_id].totals[key].total_grade_weight += item2.grade_weight;
		// 		});
		// 	}
		// });
		//
		//
		// if( $scope.studentReports[student_id].originalData !== undefined )
		// {
		// 	// recreated a report, carry over the comments
		// 	$scope.studentReports[student_id].comments = angular.copy(studentData.originalData.comments) || {};
		//
		// 	angular.forEach( $scope.studentReports[student_id].reportData.subjects, function(item,key){
		//
		// 		// get matching element of currentReportData
		// 		var orgData = $scope.studentReports[student_id].originalData.subjects.filter(function(newItem){
		// 			if( newItem.subject_name == item.subject_name ) return newItem;
		// 		})[0];
		// 		var overall = $scope.studentReports[student_id].overallSubjectMarks.filter(function(item2){
		// 			if( item.subject_name == item2.subject_name ) return item2;
		// 		})[0];
		// 		if(item.subject_name == "Kiswahili"){
		// 			if( overall ) 	item.remarks = overall.kiswahili_comment;
		// 		}else{
		// 			if( overall ) 	item.remarks = overall.comment;
		// 		}
		// 		// if( overall ) 	item.remarks = overall.comment;
		// 	});
		// }

	}
	// console.log(data.graphPoints);

	function callAtTimeout(student_id) {


	$scope.studentReports[student_id].chart_path = $scope.chart_path;

	// console.log($scope.studentReports[student_id]);
	// console.log("time out func");

}

	var groupExamMarks = function(data)
	{
		/* group the results by subject */
		var reportData = {};
		reportData.subjects = [];
		var lastSubject = '',
			marks = {},
			i = 0;
		angular.forEach(data, function(item,key){

			if( item.subject_name != lastSubject )
			{
				// changing to new subject, store the marks
				if( i > 0 ) reportData.subjects[(i-1)].marks = marks;

				reportData.subjects.push(
					{
						subject_name: item.subject_name,
						parent_subject_name: item.parent_subject_name,
						teacher_id: item.teacher_id,
						initials: item.initials,
						use_for_grading: item.use_for_grading
					}
				);

				marks = {};
				i++;
			}


			marks[item.exam_type] = {
				mark: item.mark,
				grade_weight: item.grade_weight,
				//position: item.rank,
				grade: item.grade
			}


			lastSubject = item.subject_name;

		});
		if( reportData.subjects[(i-1)] !== undefined ) reportData.subjects[(i-1)].marks = marks;
		return reportData.subjects;
	}

	var loadReportCard = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{

			if( result.nodata === undefined )
			{
				/* existing report card was found, load the data */
				$scope.canPrint = true;
				$scope.showReportCard = true;
				$scope.savedReport = true;
				$scope.canDelete = ( $scope.isTeacher ? false : true);

				$scope.report = result.data;
				var termName = $scope.report.term_name;
				// we only want the number
				termName = termName.split(' ');
				$scope.report.term = termName[1];
				$scope.savedReportData = ( result.data.report_data !== null ? angular.fromJson(result.data.report_data) : []);
				$scope.originalData = angular.copy($scope.savedReportData);

				$scope.setReportCardData($scope.savedReportData);
				//filterExamTypes();


				/* look for adjustments to exam marks */

				if( $scope.reportCardType == 'Standard' )
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
					//var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
					//if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
					//apiService.getExamMarksforReportCard(params, diffExamMarks, apiError);
				}
				else if( $scope.reportCardType == 'Standard-v.2' )
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
					//var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
					//if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
					//apiService.getExamMarksforReportCard(params, diffExamMarks, apiError);
				}
				else if( $scope.reportCardType == 'Standard-v.3' )
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
					//var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
					//if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
					//apiService.getExamMarksforReportCard(params, diffExamMarks, apiError);
				}

			}
			else
			{
				/* no existing report card, go get the students exam marks and build */
				$scope.savedReport = false;
			}
		}
	}

	var diffExamMarks = function(currentExamMarks,savedReportData, studentData, student_id)
	{
		//var currentExamMarks = result.data.details;
		//var currentOverall = result.data.overall; // overall is now always coming from the latest exam marks

		studentData.currentReportData = {};
		studentData.currentReportData.subjects = groupExamMarks( currentExamMarks );

		/* compare to stored report that is currently loaded */
		studentData.differences = [];
		angular.forEach( savedReportData.subjects, function(item,key){

			/* get matching element of currentReportData */
			var curData = studentData.currentReportData.subjects.filter(function(newItem){
				if( newItem.subject_name == item.subject_name ) return newItem;
			})[0];

			if( curData !== undefined )
			{
				angular.forEach( item.marks, function(mark,examType){

					if( curData.marks[examType].mark != mark.mark )
					{
						studentData.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Mark has changed from ' + mark.mark + ' to ' + curData.marks[examType].mark
						});
					}
					if( curData.marks[examType].position != mark.position )
					{
						studentData.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Position has changed from ' + mark.position + ' to ' + curData.marks[examType].position
						});
					}
					if( curData.marks[examType].grade_weight != mark.grade_weight )
					{
						studentData.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Grade Weight has changed from ' + mark.grade_weight + ' to ' + curData.marks[examType].grade_weight
						});
					}

				});
			}
		});

		/* compare overall standing
		if( currentOverall.rank != $scope.overall.rank )
		{
			$scope.differences.push({
				change : 'Students rank has changed from ' + $scope.overall.rank + ' to ' + currentOverall.rank
			});
		}
		if( currentOverall.position_out_of != $scope.overall.position_out_of )
		{
			$scope.differences.push({
				change : 'Position Out Of has changed from ' + $scope.currentClassPosition.position + ' to ' + currentOverall.position_out_of
			});
		}
		 */


	}

	var filterExamTypes = function()
	{
		/* get a list of the exam types used on report card */
		var examTypesUsed = [];
		angular.forEach($scope.reportData.subjects, function(item,key){
			angular.forEach(item.marks, function(data,examType){
				if( examTypesUsed.indexOf(examType) === -1 ) examTypesUsed.push(examType);
			})
		});

		/* remove any exam types that have not been used for this report card */
		$scope.examTypes = $scope.rawExamTypes.filter(function(item){
			var found = (examTypesUsed.indexOf(item.exam_type) > -1 ? true : false);
			if( found ) return item;
		});
	}

	$scope.recreateReport = function()
	{
		/* force new report to be generated */
		$scope.canPrint = false;
		$scope.modified = true;
		$scope.getProgressReport(true);
	}

	$scope.revertReport = function()
	{
		$scope.canPrint = true;
		$scope.modified = false;
		$scope.recreated = false;
		$scope.savedReport = true;
		$scope.getProgressReport();
	}

	$scope.setReportCardData = function(data)
	{

		$scope.canPrint = true;
		$scope.showReportCard = true;

		//$scope.overall = angular.copy(data.position);
		//$scope.overallLastTerm = angular.copy(data.position_last_term);
		//$scope.total_overall_mark = angular.copy( $scope.reportData.total_overall_mark);
		//$scope.totals = angular.copy(data.totals);

		$scope.comments = angular.copy(data.comments) || {};

		$scope.nextTermStartDate = angular.copy(data.nextTerm);
		$scope.currentTermEndDate = angular.copy(data.closingDate);

		/* if this is a teacher and not the class teacher, only display their subjects */
		if( $scope.isTeacher && !$scope.isClassTeacher )
		{
			// filter $scope.reportData to teacher subjects
			$scope.reportData.subjects = $scope.reportData.subjects.filter(function(item){
				if( item.teacher_id == $rootScope.currentUser.emp_id) return item;
			});
		}

		/* merge saved data into report */
		$scope.reportData.subjects = $scope.reportData.subjects.map(function(item){

			/* if the saved data has the same subject, set its data in report data */
			var savedItem = data.subjects.filter(function(item2){
				if( item.subject_name == item2.subject_name ) return item2;
			})[0];

			if( savedItem !== undefined )
			{
				item = savedItem;
				item.updated = true;
			}

			return item;

		});

	}

	$scope.updateReport = function()
	{
		$scope.canPrint = false;
		$scope.modified = true;
	}

	$scope.print = function()
	{
		var criteria = {
			student : $scope.student,
			report: $scope.report,
			overall: $scope.overall,
			graphPoints: $scope.graphPoints,
			currentClassPosition: $scope.currentClassPosition,
			streamRankPosition: $scope.streamRankPosition,
			streamRankOutOf: $scope.streamRankOutOf,
			// streamPosition: $scope.streamPosition,
			overallLastTerm: $scope.overallLastTerm,
			examTypes: $scope.examTypes,
			reportData: $scope.reportData,
			totals: $scope.totals,
			comments: $scope.comments,
			nextTermStartDate: $scope.nextTermStartDate,
			currentTermEndDate: $scope.currentTermEndDate,
			report_card_type: $scope.reportCardType,
			chart_path: $scope.chart_path,
			motto: $scope.motto,
			overallSubjectMarks: $scope.overallSubjectMarks,
			thisTermMarks: $scope.thisTermMarks,
			thisTermMarksOutOf: $scope.thisTermMarksOutOf,
			thisTermGrade: $scope.thisTermGrade,
			thisTermPercentage: $scope.thisTermPercentage
		}

		var domain = window.location.host;
		var newWindowRef = window.open('https://' + domain + '/#/exams/report_card/print');
		newWindowRef.printCriteria = criteria;
	}


	$scope.deleteReportCard = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete this report card? <br><br><b><i>(THIS CAN NOT BE UNDONE)</i></b>',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deleteReportCard($scope.report.report_card_id, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					if( $scope.adding )
					{
						//$scope.showReportCard = false;
						$scope.report = {};
						$scope.overall = {};
						$scope.overallLastTerm = {};
						$scope.graphPoints = {};
						$scope.currentClassPosition = {};
						// $scope.streamPosition = {};
						$scope.reportData = undefined;
						$scope.comments = {};
						$scope.recreated = false;
						$scope.canDelete = false;
					}
					else
					{
						$uibModalInstance.dismiss('canceled');
					}
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}

			}, apiError);

		});
	}

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	$scope.save = function()
	{

		$scope.reportData.position = $scope.overall;
		// $scope.reportData.stream_position = $scope.streamPosition;
		$scope.reportData.position_last_term = $scope.overallLastTerm;
		$scope.reportData.totals = $scope.totals;
		$scope.reportData.comments = $scope.comments;
		$scope.reportData.nextTerm = $scope.nextTermStartDate;
		$scope.reportData.closingDate = $scope.currentTermEndDate;

		/* if subject teacher, and there is an existing report card, need to update only the subject they are associated with */
		if( $scope.isTeacher && !$scope.isClassTeacher )
		{
			var reportData = angular.copy($scope.reportData);
			var addIt = false;
			angular.forEach( $scope.originalData.subjects, function(item,key){
				/* if saved data had subject data that this user could not view, add it back in, then save */
				angular.forEach( reportData.subjects, function(item2,key){
					addIt = ( item.subject_name == item2.subject_name ? false : true );
				});
				if( addIt )
				{
					reportData.subjects.push(item);
				}
			});

		}
		else
		{
			var reportData = angular.copy($scope.reportData);
		}

		var dataForPdf = {
			// school: {
			// 	school_name: $rootScope.currentUser.settings['School Name'],
			// 	school_address: $rootScope.currentUser.settings['Address 1'],
			// 	school_address2: $rootScope.currentUser.settings['Address 2'],
			// 	contact: $rootScope.currentUser.settings['Phone Number 2'],
			// 	contact2: $rootScope.currentUser.settings['Phone Number'],
			// 	email: $rootScope.currentUser.settings['Email Address'],
			// 	letterhead: "assets/schools/" + $rootScope.currentUser.settings['Letterhead']
			// },
			header: {
				user_id: $rootScope.currentUser.user_id,
				student_id: $scope.student.student_id,
				student_name: $scope.student.student_name,
				student_img: "assets/students/" + $scope.student.student_image,
				kcpe_marks: $scope.student.kcpe_marks,
				school_house: $scope.student.school_house,
				stream_pos: $scope.streamRankPosition,
				stream_out_of: $scope.streamRankOutOf,
				term_id : $scope.report.term_id,
				class_id : $scope.report.class_id,
				report_card_type : $scope.reportCardType,
				teacher_id : $scope.report.teacher_id
			},
			report_data : reportData,
			chart: $scope.chart_path
		}

		var data = {
			user_id: $rootScope.currentUser.user_id,
			student_id: $scope.student.student_id,
			term_id : $scope.report.term_id,
			class_id : $scope.report.class_id,
			report_card_type : $scope.reportCardType,
			teacher_id : $scope.report.teacher_id,
			report_data : JSON.stringify(reportData),
			json_data : JSON.stringify(dataForPdf),
			published: $scope.report.published || 'f'
		}

		apiService.addReportCard(data,createCompleted,apiError);

	}

	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			/* update the original data with saved data */
			$scope.originalData = angular.copy($scope.reportData);
			$scope.canPrint = true;
			$scope.saved = true;
			$scope.modified = false;
			//$uibModalInstance.close();
			var msg = ($scope.edit ? 'Report Card was updated.' : 'Report Card was added.');
			$rootScope.$emit('reportCardAdded', {'msg' : msg, 'clear' : true});
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}


		var chartsCanvas = "<div>";
		var ids = [];
		angular.forEach($scope.studentReports, function(item,key)
		{
				if(item !== undefined)
				{
					chartsCanvas = chartsCanvas + "<canvas id='" + key + "' class='chart chart-line efficiency-chart'></canvas>";
					ids.push(key);
				}

		});

		//appendDiv(ids);

		chartsCanvas = chartsCanvas + "</div>";




		//get student Information


	//initializeController($scope.studentReports[302], 302)
	angular.forEach($scope.studentReports, function(item,key)
		{

			if(item !== undefined)
			{
				//initializeController(item, key)

				$timeout(initializeController, 2000, true, item, key);
			}



		});




} ]);
