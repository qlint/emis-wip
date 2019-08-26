'use strict';

angular.module('eduwebApp').
controller('reportCardBulkPrintCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window, $parse){
	// console.log(data);
	// console.log("School = " + window.location.host.split('.')[0]);
	$scope.AllData2 ={};

		angular.forEach(data, function(value, key) {
			$scope.AllData2[key] = value;
			//console.log(value.reportData)
		});
	$scope.AllData = []; // $scope.AllData2;

		angular.forEach(data, function(item,key)
		{
				if(item !== undefined)
				{
					$scope.AllData.push(item)
				}

		});
	console.log($scope.AllData2,$scope.AllData2);
	data = $scope.AllData2[0];
	$rootScope.isPrinting = false;
	$scope.student = null
	$scope.reportCardType = "Standard" ; //($scope.student !== undefined ? $scope.student.report_card_type : undefined);
	$scope.showSelect = ( $scope.student === undefined ? true : false );
	$scope.classes =  [];
	$scope.terms = [];
	$scope.filters =  [];
	$scope.adding = false
	$scope.thestudent = {};
	$scope.examTypes = {};
	$scope.comments = {};
	$scope.principal_comment = {};
	$scope.parentPortalAcitve = ( $rootScope.currentUser.settings['Parent Portal'] && $rootScope.currentUser.settings['Parent Portal'] == 'Yes' ? true : false);
	$scope.entity_id = null
	$scope.canPrint = false;
	$scope.isSchool = window.location.host.split('.')[0];
	$scope.schoolName = window.location.host.split('.')[0];
	$scope.wantStreamPos = ( window.location.host.split('.')[0] == 'kingsinternational' || window.location.host.split('.')[0] == 'lasalle' ? true : false );
	$scope.wantAutomatedComments = ( window.location.host.split('.')[0] == 'thomasburke' ? true : false );
	$scope.isSpecialExam = (window.location.host.split('.')[0] == 'mico' ? true : false);
	$scope.noGeneralComments = ( window.location.host.split('.')[0] == 'kingsinternational' || window.location.host.split('.')[0] == 'thomasburke' ? true : false );
	// console.log($scope.isSchool);

	$scope.report = {};
	$scope.report.published = false;

	$scope.showReportCard = false;

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );
	$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );

	$scope.chart_path = "";
	console.log($scope);
	if( $scope.AllData[0].filters.class.class_cat_id == 21 || $scope.AllData[0].filters.class.class_cat_id == 5 || $scope.AllData[0].filters.class.class_cat_id == 6 || $scope.AllData[0].filters.class.class_cat_id == 7 || $scope.AllData[0].filters.class.class_cat_id == 8 || $scope.AllData[0].filters.class.class_cat_id == 9 ){
	    $scope.noRanking = ( window.location.host.split('.')[0] == 'lasalle' ? true : false );
	    if( $scope.noRanking == true ){
    	    $scope.rmks = "Teachers Comments On Performance Of Curriculum Outcome";
    	    $scope.subj_name = "Learning Area";
	    }else{
	        $scope.rmks = "Remarks";
	        $scope.subj_name = "Subject"
	    }
	}else{
	    $scope.noRanking = false;
	}

	var initializeController = function()
	{

		if( $scope.reportCardType == 'Standard' )
		{
			// get exam types
			apiService.getExamTypes($scope.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes = result.data;
					if( $scope.reportCardType === undefined ) $scope.reportCardType = $scope.filters.class.report_card_type; // set the report card type if not passed in

					initReportCard();
					$scope.Done_getExamTypes = true;
				}

			}, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.2' )
		{
			// get exam types
			apiService.getExamTypes($scope.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes = result.data;
					if( $scope.reportCardType === undefined ) $scope.reportCardType = $scope.filters.class.report_card_type; // set the report card type if not passed in

					initReportCard();
				}

			}, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.3' )
		{
			// get exam types
			apiService.getExamTypes($scope.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes = result.data;
					if( $scope.reportCardType === undefined ) $scope.reportCardType = $scope.filters.class.report_card_type; // set the report card type if not passed in

					initReportCard();
				}

			}, apiError);
		}
		else
		{
			initReportCard();
		}

	}
	//$timeout(initializeController,1);

	var initReportCard = function()
	{
		console.log("Initializing report card",data);
		if( data.reportData !== undefined )
		{

			/* passing in a report to view, load it */
			$scope.savedReportData = angular.fromJson(data.reportData);
			$scope.originalData = angular.copy($scope.savedReportData);

			$scope.report.report_card_id = data.report_card_id;
			$scope.report.class_name = data.class_name;
			$scope.report.class_id = data.class_id;

			var termName = data.term_name;
			// we only want the number
			termName = termName.split(' ');
			$scope.report.term = termName[1];

			$scope.report.term_id = data.term_id;
			$scope.report.year = data.year;
			$scope.report.published = data.published;
			$scope.reportCardType = data.report_card_type;
			$scope.report.teacher_id = data.teacher_id;
			$scope.report.teacher_name = data.teacher_name;
			$scope.comments.teacher_name = data.teacher_name;
			//$scope.report.head_teacher_name = data.head_teacher_name;
			$scope.report.date = data.date;
			$scope.nextTermStartDate = $scope.savedReportData.nextTerm;
			$scope.currentTermEndDate = $scope.savedReportData.closingDate;
			$scope.overallLastTerm = data.overallLastTerm;
			$scope.subjectOverall = data.subjectOverall;
			$scope.subjectOverallBySum = data.subjectOverallBySum;
			$scope.subjectOverallByAvg = data.subjectOverallByAvg;
			$scope.graphPoints = data.graphPoints;
			$scope.currentClassPosition = data.currentClassPosition;

			$scope.savedReport = true;
			$scope.canPrint = true;
			$scope.canDelete = ( $scope.isTeacher ? false : true);
			$scope.filters = data.filters;
			$scope.isClassTeacher = ( $scope.student.class_teacher_id == $rootScope.currentUser.emp_id ? true : false);
			$scope.isSchool = ( window.location.host.split('.')[0] == "kingsinternational" || window.location.host.split('.')[0] == "thomasburke" ? true : false);
			// console.log("school = "+ window.location.host.split('.')[0] + " and isSchool = " + $scope.isSchool);
			console.log("data_key = " + $scope.data_key);
			$scope.AllData[$scope.data_key].nextTermStartDate = $scope.nextTermStartDate;
			$scope.AllData[$scope.data_key].currentTermEndDate = $scope.currentTermEndDate;
			$scope.AllData[$scope.data_key].report = $scope.report;

			// fetch the report cards subjects based on user type
			getExamMarksforReportCard();
			getStreamPosition();

			// console.log($scope);

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
		$scope.showReportCard = false;
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
			$scope.Done_getNextTerm =true;
		}, apiError);
	});

	$scope.clearSelect = function($event)
	{
		$event.stopPropagation();
		$scope.thestudent.selected = undefined;
		$scope.showReportCard = false;
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
		$scope.showReportCard = false;
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

	var getStreamPosition = function()
	{
		var params = $scope.student.student_id + '/' + $scope.entity_id + '/' +  $scope.report.term_id;

		apiService.getStreamPosition(params, loadStreamPOsition, apiError);
	}

	var getExamMarksforReportCard = function()
	{
		if( $scope.reportCardType == 'Standard' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.2' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.3' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
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

		$scope.Done_getAllSubjects =true;


	}

	var loadStreamPOsition = function(response, status)
	{
		var result = angular.fromJson(response);



		try{
			var school = window.location.host.split('.')[0];

				$scope.streamRankPosition = result.data.streamRank[0].position;
				$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;

				$scope.streamRankLastTerm = result.data.streamRankLastTerm;
				$scope.streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
				$scope.streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of;
				console.log("Stream ranking");

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

			var school = window.location.host.split('.')[0];

				$scope.streamRankPosition = result.data.streamRank[0].position;
				$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;

				$scope.streamRankLastTerm = result.data.streamRankLastTerm;
				$scope.streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
				$scope.streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of;
				console.log("Stream last term");

		$scope.AllData[$scope.data_key].streamRankOutOf = $scope.streamRankOutOf;
		$scope.AllData[$scope.data_key].streamRankOutOfLastTerm = $scope.streamRankOutOfLastTerm;
		}catch(e){}
		$scope.Done_getStreamPosition =true;

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

		$scope.Done_getExamMarksforReportCard = true;
	}

	var buildReportBody = function(data)
	{
		var student_id = data.details[0].student_id;
		console.log(student_id)
		console.log("vvvvvvv >> " + $scope.student.student_id)

		var school = window.location.host.split('.')[0];

		$scope.examMarks = data.details;
		// $scope.overallSubjectMarks = data.subjectOverall;
		// $scope.overall = data.overall;
		if (school == "kingsinternational" || school == "thomasburke"){
			$scope.overall = data.overallByAverage;
			$scope.thisTermMarks = data.overallByAverage.current_term_marks;
			console.log("overall data by avg");
		}else{
			$scope.overall = data.overall;
			$scope.thisTermMarks = data.overall.current_term_marks;
			console.log("overall data - norma");
			// console.log($scope.overall);
		}
		$scope.overallLastTerm = data.overallLastTerm;
		$scope.graphPoints = data.graphPoints;




		$scope.currentClassPosition = data.currentClassPosition[0];
		if (school == "kingsinternational" || school == "thomasburke"){
			$scope.overallSubjectMarks = data.subjectOverallByAvg;
			console.log("sbj ovrl by avg");
		}else{
			$scope.overallSubjectMarks = data.subjectOverall;
			console.log("sbj ovrl normal");
		}
		// $scope.thisTermMarks = data.overall.current_term_marks;
		$scope.thisTermMarksOutOf = data.overall.current_term_marks_out_of;
		$scope.thisTermGrade = data.overall.grade;
		$scope.thisTermPercentage = data.overall.percentage;
		$scope.latestExamType = data.latestExamType[0];

		// console.log("subject overalls variable ::>");
		// console.log($scope.overallSubjectMarks);

		$scope.AllData[student_id].overall = $scope.overall;
		$scope.AllData[student_id].overallLastTerm = $scope.overallLastTerm;
		$scope.AllData[student_id].thisTermMarks = $scope.thisTermMarks;
		$scope.AllData[student_id].thisTermMarksOutOf = $scope.thisTermMarksOutOf;
		$scope.AllData[student_id].thisTermGrade = $scope.thisTermGrade;
		$scope.AllData[student_id].thisTermPercentage = $scope.thisTermPercentage;
		$scope.AllData[student_id].terms = $scope.terms;

		var performanceLabels = [];
		var performanceData = [];

		angular.forEach($scope.graphPoints, function (item, key) {
		    performanceLabels.push(item.exam_type);
		    performanceData.push(item.average_grade);
		});



		var lineChartData = {
		    labels: performanceLabels,
		    datasets: [{
		        label: "performance",
		        data: performanceData,


		        borderWidth: 2,
		        pointBorderColor: '#ffffff',
		        pointBackgroundColor: '#2D4E5E',
		        pointBorderWidth: 1,
		        radius: 4
		    }]
		};



		if($scope.AllData[student_id].chart_path == "")
		{
			if ($scope.zoomed) $scope.ctx = document.getElementById("zoomedLine1").getContext("2d");
			else $scope.ctx = document.getElementById("line1").getContext("2d");

			initChart1($scope.ctx, lineChartData);
			$timeout(callAtTimeout, 1000, true, student_id);
			$scope.efficiencyLoading = false;

		}






















		/* remove any exam types that have not been used for this report card */
		$scope.examTypes = $scope.rawExamTypes.filter(function(item){
			var found = $scope.examMarks.filter(function(item2){
				if( item.exam_type == item2.exam_type ) return item2;
			})[0];
			if( found !== undefined ) return item;
		});

		$scope.AllData[student_id].examTypes = $scope.examTypes;



		/* group the results by subject */
		$scope.reportData = {};
		$scope.reportData.subjects = groupExamMarks( $scope.examMarks );
		$scope.latestExamMarks = angular.copy($scope.reportData);

		// set overall
		var total_marks = 0;
		var total_grade_weight = 0;

		angular.forEach( $scope.reportData.subjects, function(item,key){
			if( item.use_for_grading )
			{

				var overall = $scope.overallSubjectMarks.filter(function(item2){
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

				var graphdata = $scope.graphPoints.filter(function(item2){
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
		$scope.totals = {};
		angular.forEach( $scope.reportData.subjects, function(item,key)
		{
			if( item.use_for_grading )
			{
				angular.forEach( item.marks, function(item2,key)
				{
					if( $scope.totals[key] === undefined ) $scope.totals[key] = {total_mark:0, total_grade_weight:0};
					if( item.parent_subject_name == null ) $scope.totals[key].total_mark += item2.mark;
					if( item.parent_subject_name == null ) $scope.totals[key].total_grade_weight += item2.grade_weight;
				});
			}
		});


		if( $scope.originalData !== undefined )
		{
			// recreated a report, carry over the comments
			$scope.comments = angular.copy($scope.originalData.comments) || {};

			angular.forEach( $scope.reportData.subjects, function(item,key){

				// get matching element of currentReportData
				var orgData = $scope.originalData.subjects.filter(function(newItem){
					if( newItem.subject_name == item.subject_name ) return newItem;
				})[0];
				var overall = $scope.overallSubjectMarks.filter(function(item2){
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


			$scope.AllData[student_id].reportData = $scope.reportData;
			$scope.AllData[student_id].totals = $scope.totals;
			$scope.AllData[student_id].comments = $scope.comments;
			$scope.AllData[student_id].principal_comment = $scope.principal_comment;

			console.log("ALL DATA")
			console.log($scope.AllData)


	}
	// console.log(data.graphPoints);

	function callAtTimeout(param) {

	$scope.chart_path = getChartPath();
	console.log("GETTTING CHART" + param)
			$scope.AllData[param].chart_path = $scope.chart_path;
			$scope.Done_getChart_path = true;

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

		$scope.Done_getStudentReportCard =true

	}

	var diffExamMarks = function(currentExamMarks,savedReportData)
	{
		//var currentExamMarks = result.data.details;
		//var currentOverall = result.data.overall; // overall is now always coming from the latest exam marks

		$scope.currentReportData = {};
		$scope.currentReportData.subjects = groupExamMarks( currentExamMarks );

		/* compare to stored report that is currently loaded */
		$scope.differences = [];
		angular.forEach( savedReportData.subjects, function(item,key){

			/* get matching element of currentReportData */
			var curData = $scope.currentReportData.subjects.filter(function(newItem){
				if( newItem.subject_name == item.subject_name ) return newItem;
			})[0];

			if( curData !== undefined )
			{
				angular.forEach( item.marks, function(mark,examType){

					if( curData.marks[examType].mark != mark.mark )
					{
						$scope.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Mark has changed from ' + mark.mark + ' to ' + curData.marks[examType].mark
						});
					}
					if( curData.marks[examType].position != mark.position )
					{
						$scope.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Position has changed from ' + mark.position + ' to ' + curData.marks[examType].position
						});
					}
					if( curData.marks[examType].grade_weight != mark.grade_weight )
					{
						$scope.differences.push({
							subject_name: item.subject_name,
							exam_type: examType,
							change : 'Grade Weight has changed from ' + mark.grade_weight + ' to ' + curData.marks[examType].grade_weight
						});
					}

				});
			}
		});

		$scope.AllData[$scope.data_key].differences = $scope.differences;

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

		$scope.AllData[$scope.data_key].examTypes = $scope.examTypes;
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
			subj_name: $scope.subj_name,
			rmks: $scope.rmks,
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
						$scope.showReportCard = false;
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

} ]);
