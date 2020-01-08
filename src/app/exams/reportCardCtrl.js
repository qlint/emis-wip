'use strict';

angular.module('eduwebApp').
controller('reportCardCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window, $parse){
	// console.log(data);
	// console.log($scope);
	$rootScope.isPrinting = false;
	$scope.student = data.student || undefined;
	$scope.reportCardType = ($scope.student !== undefined ? $scope.student.report_card_type : undefined);
	$scope.showSelect = ( $scope.student === undefined ? true : false );
	$scope.classes = data.classes || [];
	$scope.terms = data.terms || [];
	$scope.filters = data.filters || [];
	$scope.adding = data.adding;
	$scope.thestudent = {};
	$scope.examTypes = {};
	$scope.specialExamTypes = {};
	$scope.comments = {};
	$scope.principal_comment = {};
	$scope.entity_id = ($scope.student.entity_id == null || $scope.student.entity_id == undefined ? null : $scope.student.entity_id);
	$scope.parentPortalAcitve = ( $rootScope.currentUser.settings['Parent Portal'] && $rootScope.currentUser.settings['Parent Portal'] == 'Yes' ? true : false);
	$scope.schoolName = window.location.host.split('.')[0];
	$scope.wantStreamPos = ( window.location.host.split('.')[0] == 'kingsinternational' || window.location.host.split('.')[0] == 'lasalle' ? true : false );
	$scope.wantAutomatedComments = ( window.location.host.split('.')[0] == 'thomasburke' ? true : false );
	$scope.interchangeLabels = ( window.location.host.split('.')[0] == 'thomasburke' ? true : false );
	$scope.isSpecialExam = (window.location.host.split('.')[0] == 'mico' ? true : false);
	$scope.noGeneralComments = ( window.location.host.split('.')[0] == 'kingsinternational' || window.location.host.split('.')[0] == 'thomasburke' ? true : false );
	$scope.calculationMode = $rootScope.currentUser.settings["Exam Calculation"];
	// console.log($rootScope.currentUser.settings["Exam Calculation"]);
	$scope.canPrint = false;

	$scope.report = {};
	$scope.report.published = false;

	$scope.showReportCard = false;

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );
	$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' || $rootScope.currentUser.user_type == 'ADMIN' || $rootScope.currentUser.user_type == 'FINANCE_CONTROLLED' ? true : false );
	//$scope.noRanking = ( window.location.host.split('.')[0] == 'lasalle' ? true : false );
	if( data.filters.class.class_cat_id == 21 || data.filters.class.class_cat_id == 5 || data.filters.class.class_cat_id == 6 || data.filters.class.class_cat_id == 7 || data.filters.class.class_cat_id == 8 || data.filters.class.class_cat_id == 9 ){
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

	$scope.chart_path = "";

	var initializeController = function()
	{
	    // get lower school grading
	    if($scope.schoolName == "lasalle" && $scope.entity_id <= 6){
	        getGrading2();
	    }

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
				}

			}, apiError);

			apiService.getSpecialExamTypes($scope.filters.class.class_cat_id, function(response){

				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
				    $scope.rawExamTypes2 = result.data;
					if( $scope.reportCardType === undefined ) $scope.reportCardType = $scope.filters.class.report_card_type; // set the report card type if not passed in

					initReportCard();
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

			apiService.getSpecialExamTypes($scope.filters.class.class_cat_id, function(response){

				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
				    $scope.rawExamTypes2 = result.data;
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

		// if no student was passed in, get list of students for select dropdown
		if( $scope.student === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				var params = $rootScope.currentUser.emp_id + '/' + true;
				apiService.getTeacherStudents(params, loadStudents, apiError);
			}
			else
			{
				apiService.getAllStudents(true, loadStudents, apiError);
			}
		}


	}
	$timeout(initializeController,1);

	var initReportCard = function()
	{
		if( data.reportData !== undefined )
		{
		    // console.log(data.reportData);
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
			$scope.updateReportTermId = data.term_id;
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
			$scope.graphPoints = data.graphPoints;

			if($scope.isSpecialExam == true){
			    $scope.specialSubjectOverall = data.specialSubjectOverall;
			    $scope.specialSubjectOverallBySum = data.specialSubjectOverallBySum;
			    $scope.specialSubjectOverallByAvg = data.specialSubjectOverallByAvg;
			    $scope.specialCurrentClassPosition = data.specialCurrentClassPosition;
			}

			$scope.savedReport = true;
			$scope.canPrint = true;
			$scope.canDelete = ( $scope.isTeacher ? false : true);
			$scope.filters = data.filters;
			$scope.isClassTeacher = ( $scope.student.class_teacher_id == $rootScope.currentUser.emp_id ? true : false);
			$scope.isSchool = ( window.location.host.split('.')[0] == "kingsinternational" || window.location.host.split('.')[0] == "thomasburke" ? true : false);
			$scope.hideStudentImg = ( window.location.host.split('.')[0] == "thomasburke" ? true : false);
			$scope.hideLogo = ( window.location.host.split('.')[0] == "thomasburke" ? true : false);

			// fetch the report cards subjects based on user type
			getExamMarksforReportCard();
			if($scope.isSpecialExam == true){ getSpecialExamMarksforReportCard(); }
			getStreamPosition();

		}
	}

	// playgroup text area expand - start
	$scope.resizeTextarea = function resizeTextarea(el) {
	    var textAreaElement = "pgSubj" + el.item.subject_id;
        var a = document.getElementById(textAreaElement);
        a.style.height = 'auto';
        a.style.height = a.scrollHeight+'px';
    }
	// playgroup text area expand - end

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

	var getGrading2 = function()
	{

		apiService.getGrading2({}, function(response,status,params){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
			    $scope.grades2 = ( result.nodata ? [] : result.data );

				$scope.grades2 = $scope.grades2.map(function(item2){
					item2.mark_range = item2.min_mark + ' - ' + item2.max_mark;
					return item2;
				});

			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}

		}, apiError);
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
		}, apiError);
	});

	$scope.clearSelect = function($event)
	{
		$event.stopPropagation();
		$scope.thestudent.selected = undefined;
		$scope.showReportCard = false;
		$scope.report = {};
		$scope.overall = {};
		$scope.overallLastTerm = {};
		$scope.graphPoints = {};
		$scope.streamRankLastTerm = {};
		//$scope.examTypes = {};
		$scope.reportData = undefined;
	};

	$scope.getProgressReport = function(recreate)
	{
		$scope.showReportCard = false;
		$scope.report = {};
		$scope.overall = {};
		$scope.overallLastTerm = {};
		$scope.graphPoints = {};
		$scope.streamRankLastTerm = {};
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

			// var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.updateReportTermId;
			if( $scope.isTeacher && !$scope.isClassTeacher ){ params += '/' + $rootScope.currentUser.emp_id };
			// apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);

			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 6 || $scope.entity_id == undefined || $scope.entity_id == null) ){
				// console.log("Lsl. Lower Sch.");
			    apiService.getLowerSchoolExamMarksforReportCard(params, loadExamMarks, apiError);
			}else{
				// console.log("Upper Sch.");
				// console.log(params);
				apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
				if($scope.isSpecialExam == true){ apiService.getSpecialExamMarksforReportCard(params, loadSpecialExamMarks, apiError); }
			}
		}
		else
		{
			getExamMarksforReportCard();
			if($scope.isSpecialExam == true){ getSpecialExamMarksforReportCard(); }
			//var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id
			//apiService.getStudentReportCard(params,loadReportCard, apiError);
		}

	}

	var getStreamPosition = function()
	{
	    if($scope.entity_id >= 7){
    		var params = $scope.student.student_id + '/' +  $scope.report.term_id;
    		apiService.getStreamPosition(params, loadStreamPOsition, apiError);
	    }
	}

	var getExamMarksforReportCard = function()
	{
		if( $scope.reportCardType == 'Standard' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			// apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);

			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 6) ){
				// console.log("Lsl. Lower Sch.");
			    apiService.getLowerSchoolExamMarksforReportCard(params, loadExamMarks, apiError);
			}else{
				// console.log("Upper Sch.");
				apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
			}
		}
		else if( $scope.reportCardType == 'Standard-v.2' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			// apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);

			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 6 || $scope.entity_id == undefined || $scope.entity_id == null) ){
				// console.log("Lsl. Lower Sch.");
			    apiService.getLowerSchoolExamMarksforReportCard(params, loadExamMarks, apiError);
			}else{
				// console.log("Upper Sch.");
				apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
			}
		}
		else if( $scope.reportCardType == 'Standard-v.3' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			// apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);

			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 6 || $scope.entity_id == undefined || $scope.entity_id == null) ){
				// console.log("Lsl. Lower Sch.");
			    apiService.getLowerSchoolExamMarksforReportCard(params, loadExamMarks, apiError);
			}else{
				// console.log("Upper Sch.");
				apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
			}
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

	var getSpecialExamMarksforReportCard = function()
	{
		if( $scope.reportCardType == 'Standard' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getSpecialExamMarksforReportCard(params, loadSpecialExamMarks, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.2' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getSpecialExamMarksforReportCard(params, loadSpecialExamMarks, apiError);
		}
		else if( $scope.reportCardType == 'Standard-v.3' )
		{
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			if( $scope.isTeacher && !$scope.isClassTeacher ) params += '/' + $rootScope.currentUser.emp_id;
			apiService.getSpecialExamMarksforReportCard(params, loadSpecialExamMarks, apiError);
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

				// this if logic is simply to enable us indent subjects appropriately on the report card by adding some object keys
				if($scope.reportCardType == 'Playgroup' && $scope.schoolName == 'lasalle'){

				    var parentSubjectIds = [];

				    for (var s = 0; s < $scope.reportData.subjects.length; s++) {
				        $scope.reportData.subjects[s].child = ( $scope.reportData.subjects[s].parent_subject_id != null ? 'first' : 'parent');
				        $scope.reportData.subjects[s].indent = ( $scope.reportData.subjects[s].parent_subject_id != null ? '30' : '0');

				        if($scope.reportData.subjects[s].child == 'first'){ parentSubjectIds.push($scope.reportData.subjects[s].subject_id); }
                    }

                    for (var t = 0; t < $scope.reportData.subjects.length; t++) {
                            if( $scope.reportData.subjects[t].child == 'first' ){
                                $scope.reportData.subjects[t].child = (parentSubjectIds.indexOf($scope.reportData.subjects[t].parent_subject_id) == -1 ? 'first' : 'second');
                                $scope.reportData.subjects[t].indent = (parentSubjectIds.indexOf($scope.reportData.subjects[t].parent_subject_id) == -1 ? '30' : '60');
                            }
                    }

				}
				// end of indentation logic

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
						// console.log($scope.savedReportData);
						var orgData = $scope.savedReportData.subjects.filter(function(newItem){
							if( newItem.subject_name == item.subject_name ) return newItem;
						})[0];
						if( orgData !== undefined )
						{
							if( $scope.reportCardType == 'Kindergarten'){
							    item.remarks = angular.copy(orgData.remarks);
							}else if( $scope.reportCardType == 'Playgroup'){
							    item.remarks = angular.copy(orgData.remarks);
							    item.indent = angular.copy(orgData.indent);
							}else{
							    item.skill_level = angular.copy(orgData.skill_level);
							}
						}
					});

				}

				if( $scope.reportCardType == 'Playgroup'){
					$scope.reportData.subjects = $scope.savedReportData.subjects;
				}

			}

		}


	}

	var loadStreamPOsition = function(response, status)
	{
		var result = angular.fromJson(response);
		// $scope.streamRank = result.data.streamRank;

		if(result.data.streamRank.length != 0){
    		$scope.streamRankPosition = result.data.streamRank[0].position;
    		$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;
		}

        if(result.data.streamRankLastTerm.length != 0){
    		$scope.streamRankLastTerm = result.data.streamRankLastTerm;
    		$scope.streamRankPositionLastTerm = result.data.streamRankLastTerm[0].position;
    		$scope.streamRankOutOfLastTerm = result.data.streamRankLastTerm[0].position_out_of;
        }

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
				// console.log(result.data);
				buildReportBody(result.data);
				// $( "#remotegraph" ).load( "/studentgraph.html div#remotegraph" );

				/* look for saved report card data, if it was not passed in  */
				if( $scope.savedReportData === undefined )
				{
					var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id
					apiService.getStudentReportCard(params,loadReportCard, apiError);
				}
				else
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
				}

			}
		}
	}

	var loadSpecialExamMarks = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			if( result.specialDetails === false )
			{
				$scope.error = true;
				$scope.errMsg = "No data found.";
			}
			else
			{

				$scope.showReportCard = true;
				// console.log("Special report data",result.data);
				buildSpecialReportBody(result.data);
				// $( "#remotegraph" ).load( "/studentgraph.html div#remotegraph" );

				/* look for saved report card data, if it was not passed in  */
				if( $scope.savedReportData === undefined )
				{
					var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id
					apiService.getStudentReportCard(params,loadReportCard, apiError);
				}
				else
				{
					diffExamMarks($scope.latestExamMarks, $scope.savedReportData);
				}

			}
		}
	}

	var buildReportBody = function(data)
	{
		// console.log(data);
		$scope.examMarks = data.details;
		// if($scope.schoolName == "kingsinternational" || $scope.schoolName == "thomasburke"){
		if($scope.calculationMode == "Average" || $scope.calculationMode == ""){
		    $scope.overallSubjectMarks = data.subjectOverallByAvg;
		    $scope.overall = data.overallByAverage;
			$scope.overallLastTerm = data.overallLastTermByAverage;
			// console.log("Assigning by average",$scope.overallLastTerm);
			// console.log($scope.overall);
			// $scope.comments.teacher_name = (window.location.host.split('.')[0] == 'thomasburke' ? $scope.comments.teacher_name.split(' ')[0] : $scope.comments.teacher_name);
			$scope.comments.teacher_name = $scope.comments.teacher_name;
			// $scope.comments.principle_comments = $scope.overall.principal_comment;
		}else if($scope.calculationMode == "Last Exam"){
			// console.log("Assigning by last exam");
		    $scope.overallSubjectMarks = data.subjectOverall;
    		$scope.overall = data.overall;
			$scope.overallLastTerm = data.overallLastTerm;
		}
		$scope.graphPoints = data.graphPoints;

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



		if($scope.chart_path == "")
		{
			if ($scope.zoomed) $scope.ctx = document.getElementById("zoomedLine1").getContext("2d");
			else $scope.ctx = document.getElementById("line1").getContext("2d");

			initChart1($scope.ctx, lineChartData);
			$timeout(callAtTimeout, 2000);
			$scope.efficiencyLoading = false;
		}






















		/* remove any exam types that have not been used for this report card */

		// get exam types

			apiService.getExamTypes($scope.filters.class.class_cat_id, function(response){

				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
				    $rootScope.rawExamTypes = result.data;
				}

        		$scope.examTypes = $scope.rawExamTypes.filter(function(item){
        			var found = $scope.examMarks.filter(function(item2){
        				if( item.exam_type == item2.exam_type ) return item2;
        			})[0];
        			if( found !== undefined ) return item;
        		});

			}, apiError);

// 		console.log($scope.rawExamTypes);
// 		$scope.examTypes = $scope.rawExamTypes.filter(function(item){
// 			var found = $scope.examMarks.filter(function(item2){
// 				if( item.exam_type == item2.exam_type ) return item2;
// 			})[0];
// 			if( found !== undefined ) return item;
// 		});

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
					//item.position = overall.rank;
					var subjNm = item.subject_name;
					if( subjNm == "Kiswahili" || subjNm == "KISWAHILI" ){
						item.comment = overall.kiswahili_comment;
					}else{
						item.comment = overall.comment;
						if( $scope.schoolName == 'thomasburke' || $scope.schoolName == 'kingsinternational' ){ item.remarks = overall.comment; }
					}

					// automated comments
					// item.comment = overall.kiswahili_comment;

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
				if( $scope.schoolName != 'thomasburke' || $scope.schoolName == 'kingsinternational' ){ if( orgData !== undefined ) 	item.remarks = angular.copy(orgData.remarks); }

				// automated comments
				if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 6 || $scope.entity_id == undefined || $scope.entity_id == null) ){
				    var overall = $scope.overallSubjectMarks.filter(function(item2){
    					if( item.subject_name == item2.subject_name ) return item2;
    				})[0];
    				// console.log(overall);
    				if( overall ) 	item.remarks = overall.kiswahili_comment;
				}
				if( $scope.schoolName == 'thomasburke' || $scope.schoolName == 'kingsinternational' ){
				    var overall = $scope.overallSubjectMarks.filter(function(item2){
    					if( item.subject_name == item2.subject_name ) return item2;
    				})[0];

    				if(item.subject_name == "Kiswahili" || item.subject_name == "KISWAHILI" || item.subject_name == "KIS" || item.subject_name == "KISW"){
    					if( overall ) 	item.remarks = overall.kiswahili_comment;
    				}else{
    					if( overall ) 	item.remarks = overall.comment;
    				}
				}
				// end automated comments

			});
		}

	}

	var buildSpecialReportBody = function(data)
	{
		// console.log(data);
		$scope.specialExamMarks = data.specialDetails;
		// if($scope.schoolName == "kingsinternational" || $scope.schoolName == "thomasburke"){
		if($scope.calculationMode == "Average" || $scope.calculationMode == ""){
		    $scope.specialOverallSubjectMarks = data.specialSubjectOverallByAvg;
    		$scope.specialOoverall = data.specialOverallByAverage;
		}else{
		    $scope.specialOverallSubjectMarks = data.specialSubjectOverall;
    		$scope.specialOverall = data.specialOverall;
		}
		$scope.specialOverallLastTerm = data.specialOverallLastTerm;
		$scope.specialGraphPoints = data.specialGraphPoints;

		/* remove any exam types that have not been used for this report card */

		// get exam types

			apiService.getSpecialExamTypes($scope.filters.class.class_cat_id, function(response){

				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
				    $rootScope.rawExamTypes2 = result.data;
				}
        		$scope.specialExamTypes = $scope.rawExamTypes2.filter(function(item){
        			var found = $scope.specialExamMarks.filter(function(item2){
        				if( item.exam_type == item2.exam_type ) return item2;
        			})[0];
        			if( found !== undefined ) return item;
        		});

			}, apiError);

		/* group the results by subject */
		$scope.specialReportData = {};
		$scope.specialReportData.subjects = specialGroupExamMarks( $scope.specialExamMarks );
		$scope.specialLatestExamMarks = angular.copy($scope.specialReportData);

		// set overall
		var total_marks = 0;
		var total_grade_weight = 0;

		angular.forEach( $scope.specialReportData.subjects, function(item,key){
			if( item.use_for_grading )
			{

				var overall = $scope.specialOverallSubjectMarks.filter(function(item2){
					if( item.subject_name == item2.subject_name ) return item2;
				})[0];

				if( overall )
				{
					item.overall_mark = overall.percentage;
					item.overall_grade = overall.grade;
					//item.position = overall.rank;
					var subjNm = item.subject_name;
					if( subjNm == "Kiswahili" || subjNm == "KISWAHILI" || subjNm == "KISW" || subjNm == "KIS" ){
						item.comment = overall.kiswahili_comment;
					}else{
						item.comment = overall.comment;
						if( $scope.schoolName == 'thomasburke' || $scope.schoolName == 'kingsinternational' ){ item.remarks = overall.comment; }
					}

					// automated comments
					// item.comment = overall.kiswahili_comment;

					total_marks += parseInt(overall.total_mark);
					total_grade_weight += parseInt(overall.total_grade_weight);
				}

				var graphdata = $scope.specialGraphPoints.filter(function(item2){
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
		$scope.specialTotals = {};
		angular.forEach( $scope.specialReportData.subjects, function(item,key)
		{
			if( item.use_for_grading )
			{
				angular.forEach( item.marks, function(item2,key)
				{
					if( $scope.specialTotals[key] === undefined ) $scope.specialTotals[key] = {total_mark:0, total_grade_weight:0};
					if( item.parent_subject_name == null ) $scope.specialTotals[key].total_mark += item2.mark;
					if( item.parent_subject_name == null ) $scope.specialTotals[key].total_grade_weight += item2.grade_weight;
				});
			}
		});

		if( $scope.originalData !== undefined )
		{
			// recreated a report, carry over the comments
			$scope.comments = angular.copy($scope.originalData.comments) || {};

			angular.forEach( $scope.specialReportData.subjects, function(item,key){

				// get matching element of currentReportData
				var orgData = $scope.originalData.subjects.filter(function(newItem){
					if( newItem.subject_name == item.subject_name ) return newItem;
				})[0];

				// end automated comments

			});
		}

	}

	function callAtTimeout() {

	$scope.chart_path = getChartPath();

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

	var specialGroupExamMarks = function(data)
	{
		/* group the results by subject */
		var specialReportData = {};
		specialReportData.subjects = [];
		var lastSubject = '',
			marks = {},
			i = 0;
		angular.forEach(data, function(item,key){

			if( item.subject_name != lastSubject )
			{
				// changing to new subject, store the marks
				if( i > 0 ) specialReportData.subjects[(i-1)].marks = marks;

				specialReportData.subjects.push(
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
		if( specialReportData.subjects[(i-1)] !== undefined ) specialReportData.subjects[(i-1)].marks = marks;
		return specialReportData.subjects;
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
				// console.log("original data 2 :::");
				// console.log($scope.originalData);

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

	var diffExamMarks = function(currentExamMarks,savedReportData)
	{
		//var currentExamMarks = result.data.details;
		//var currentOverall = result.data.overall; // overall is now always coming from the latest exam marks

		if($scope.isSpecialExam == true){
			$scope.specialCurrentReportData = {};
			$scope.specialCurrentReportData.subjects = specialGroupExamMarks( currentExamMarks );
		}else{
			$scope.currentReportData = {};
			$scope.currentReportData.subjects = groupExamMarks( currentExamMarks );
		}

		/* compare to stored report that is currently loaded */
		$scope.differences = [];
		angular.forEach( savedReportData.subjects, function(item,key){

			/* get matching element of currentReportData */
			if($scope.isSpecialExam == true){
				var curData = $scope.specialCurrentReportData.subjects.filter(function(newItem){
					if( newItem.subject_name == item.subject_name ) return newItem;
				})[0];
			}else{
				var curData = $scope.currentReportData.subjects.filter(function(newItem){
					if( newItem.subject_name == item.subject_name ) return newItem;
				})[0];
			}

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
				change : 'Position Out Of has changed from ' + $scope.overall.position_out_of + ' to ' + currentOverall.position_out_of
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
			if($scope.isSpecialExam == true){
				$scope.specialReportData.subjects = $scope.specialReportData.subjects.filter(function(item){
					if( item.teacher_id == $rootScope.currentUser.emp_id) return item;
				});
			}else{
				$scope.reportData.subjects = $scope.reportData.subjects.filter(function(item){
					if( item.teacher_id == $rootScope.currentUser.emp_id) return item;
				});
			}
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
			streamRankPosition: $scope.streamRankPosition,
			streamRankOutOf: $scope.streamRankOutOf,
			streamRankPositionLastTerm: $scope.streamRankPositionLastTerm,
			streamRankOutOfLastTerm: $scope.streamRankOutOfLastTerm,
			overallLastTerm: $scope.overallLastTerm,
			examTypes: $scope.examTypes,
			// reportData: $scope.reportData,
			grades2: $scope.grades2,
			reportData: ( $scope.reportCardType == 'Playgroup' ? $scope.savedReportData : $scope.reportData ),
			totals: $scope.totals,
			comments: $scope.comments,
			nextTermStartDate: $scope.nextTermStartDate,
			currentTermEndDate: $scope.currentTermEndDate,
			report_card_type: $scope.reportCardType,
			chart_path: $scope.chart_path,
			subj_name: $scope.subj_name,
			rmks: $scope.rmks,
			noRanking: $scope.noRanking,
			isClassTeacher: $scope.isClassTeacher
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
						$scope.streamPosition = {};
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
		// console.log($scope.reportData);
    	$scope.reportData.position = $scope.overall;
		$scope.reportData.stream_position = $scope.streamPosition;
		$scope.reportData.position_last_term = $scope.overallLastTerm;
		$scope.reportData.totals = $scope.totals;
		$scope.reportData.comments = $scope.comments;
		if($scope.isSpecialExam == true){
			$scope.specialReportData.position = $scope.specialOverall;
			$scope.specialReportData.stream_position = $scope.specialStreamPosition;
			$scope.specialReportData.position_last_term = $scope.specialOverallLastTerm;
			$scope.specialReportData.totals = $scope.specialTotals;
			// $scope.specialReportData.comments = $scope.specialComments;
		}
		$scope.reportData.nextTerm = $scope.nextTermStartDate;
		$scope.reportData.closingDate = $scope.currentTermEndDate;

		/* if subject teacher, and there is an existing report card, need to update only the subject they are associated with */
		if( $scope.isTeacher && !$scope.isClassTeacher && $scope.originalData !== undefined )
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

		var data = {
			user_id: $rootScope.currentUser.user_id,
			student_id: $scope.student.student_id,
			// term_id: $scope.report.term_id,
			term_id : ($scope.updateReportTermId == undefined ? $scope.filters.term_id : $scope.updateReportTermId),
			class_id : $scope.report.class_id,
			report_card_type : $scope.reportCardType,
			teacher_id : $scope.report.teacher_id,
			// report_data : JSON.stringify(reportData),
			report_data : JSON.stringify($scope.reportData),
			published: $scope.report.published || 'f'
		}
		// console.log("Updata :::",data);
		apiService.addReportCard(data,createCompleted,apiError);

		// GENERATE PDF AND SAVE TO SERVER

		let element = $("#showReportCard"); // the element we want to convert to pdf
		/*
		var blob = new Blob([element], {
			"type": "text/html"
		});
		var reader = new FileReader();
		reader.onload = function (evt) {
			if (evt.target.readyState === 2) {
				console.log(
							// full data-uri
							evt.target.result
							// base64 portion of data-uri
						, evt.target.result.slice(22, evt.target.result.length));
				// window.open(evt.target.result)
			};
		};
		reader.readAsDataURL(blob);
		*/

		// load html2canvas script and execute code on success
		$.getScript('/components/html2pdf.bundle2.js', function()
		{
			// first hide all elements we don't want on print
			var myList = document.getElementsByClassName("hideForPrint");

			for(let i=0;i < myList.length; i++){
				myList[i].style.display="none";
			}

			// get screenshot
			var reportCardElement = $("#showReportCard"); // the element we want to convert to pdf
			var opt = {
				margin:       0.5,
				filename:     $scope.schoolName + '_' + $scope.student.student_id + '_termid_' + $scope.report.term_id + '_reportcard.pdf',
				image:        { type: 'jpeg', quality: 0.98 },
				html2canvas:  { scale: 2 },
				jsPDF:        { unit: 'in', format: 'A4', orientation: 'portrait' }
			  };
			var reportCardPdf = html2pdf(document.getElementById('showReportCard'),opt);
			console.log(Promise.resolve(reportCardPdf));
			setTimeout(function(){
				var prmse = Promise.resolve(reportCardPdf);
				prmse.then(function(val) {
						// POST the file
						var formData = new FormData();
						formData.append('files[]', val);

						fetch('srvScripts/handle_file_upload.php', {
								method: 'POST',
								body: formData,
						}).then(response => {
								console.log("resp frm handler",response);
						});
						// end POST
				});
			}, 5000);

		});


	}

	var createCompleted = function ( response, status, params )
	{
        // console.log(response);
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
