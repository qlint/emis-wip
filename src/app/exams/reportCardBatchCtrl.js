'use strict';

angular.module('eduwebApp').
controller('reportCardBatchCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window, $parse){
	console.log("PASSED DATA > ",data);
	$scope.filters = data.filters;
	// console.log("Filters > ",$scope.filters);
	$scope.classRptCds = data.report.report_cards;
	$scope.reportCardType = ($scope.classRptCds[0].report_card.report_card_type == null || $scope.classRptCds[0].report_card.report_card_type == undefined ? 'Standard' : $scope.classRptCds[0].report_card.report_card_type);
	console.log("Report card type = " + $scope.reportCardType);
	console.log("Raw report cards >",$scope.classRptCds);
	$scope.removeExamsNotDone = function (objArr){
		for(let b = 0;b < objArr.length;b++){
			let exType = objArr[b];
			// console.log("Looping each exam type > ",exType);
			let subsLength = exType.exam_marks.length;
			// console.log("Exam type subject length = " + subsLength);
			let subsCheck = 0;
			for(let a=0;a < subsLength;a++){
				// console.log("Checking mark in > ",exType.exam_marks[a].mark);
				if(exType.exam_marks[a].mark == null){
					subsCheck = subsCheck + 1;
					// console.log("Null detected, subsCheck is now " + subsCheck + " ON > ",exType.exam_marks[a]);
				}
			}
			// console.log("Past loop, final subsCheck = " + subsCheck + " and subsLength = " + subsLength);
			if(subsCheck == (subsLength)){
				objArr.splice(b, 1);
				b--;
			}
		}
		// console.log("Final Object Array > ",objArr);
		return objArr;
	}

	function dateConverter(str){
		let strArr = str.split('-');
		let year = strArr[0];
		let month = parseInt(strArr[1]);
		let day = parseInt(strArr[2]);
		let months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
		let postFix = '';
		if(day == 1 || day == 21 || day == 31){
			postFix = 'st';
		}else if(day == 2 || day == 22){
			postFix = 'nd';
		}else if(day == 3 || day == 23){
			postFix = 'rd';
		}else{
			postFix = 'th';
		}
		let theDate = day + postFix + ' ' + months[month] + ', ' + year;
		// console.log(theDate);
		return theDate;
	}

	$scope.removeNullTotals = function(objArr){
		for(let b = 0;b < objArr.length;b++){
			let totExamType = objArr[b];
			// console.log(totExamType.total);
			if(totExamType.total == null){
				objArr.splice(b, 1);
				b--
			}
		}
		// console.log("Final Totals > ",objArr);
		return objArr;
	}

	$scope.classRptCds.forEach((item, i) => {
		// remove exam types not done
		if(item.report_card.exam_marks != null){
			item.report_card.exam_marks = $scope.removeExamsNotDone(item.report_card.exam_marks);
		}
		// remove totals of exam types not done
		if(item.report_card.totals[0].total_marks != null){
			item.report_card.totals[0].total_marks = $scope.removeNullTotals(item.report_card.totals[0].total_marks);
		}

		item.report_card.closing_date = (item.report_card.closing_date == null ? null : dateConverter(item.report_card.closing_date));
		item.report_card.next_term_begins = (item.report_card.next_term_begins == null ? null : dateConverter(item.report_card.next_term_begins));
		if(i == 10){ console.log("New Report Card Api",item.report_card); }
		if(item.report_card.subjects_column != null){
			for(let a=0;a < item.report_card.subjects_column.length;a++){
				let subj = item.report_card.subjects_column[a];
				for(let b=0;b < item.report_card.subject_overalls_column[0].subject_overalls.length;b++){
					let subjOvrl = item.report_card.subject_overalls_column[0].subject_overalls[b];

					if(subj.subject_id == subjOvrl.subject_id){
						subj.overall = subjOvrl;
						subjOvrl.sort_order = subj.sort_order;
					}
				}
				subj.exam_marks = [];
				item.report_card.exam_marks.forEach((item, i) => {
					let exam =  item.exam_marks;
					exam.forEach((cat, i) => {
						if(subj.subject_id == cat.subject_id){
							subj.exam_marks.push(cat);
						}
					});
				});

			}
			item.report_card.subjects_column.sort((a, b) => (a.sort_order > b.sort_order) ? 1 : -1);
			item.report_card.subject_overalls_column[0].subject_overalls.sort((a, b) => (a.sort_order > b.sort_order) ? 1 : -1);
			if($scope.schoolName == "lasalle" && $scope.entity_id <= 6){
				for(let x=0;x < item.report_card.subject_overalls_column[0].subject_overalls.length;x++){
					item.report_card.subject_overalls_column[0].subject_overalls[x].grade = item.report_card.subject_overalls_column[0].subject_overalls[x].grade2;
					item.report_card.subject_overalls_column[0].subject_overalls[x].comment = item.report_card.subject_overalls_column[0].subject_overalls[x].comment2;
				}
			}
		}

		if(item.report_card.calculation_mode == "Last Exam"){
			// console.log("By Last Exam");
			item.report_card.positions = item.report_card.positions_by_last_exams;
		}
		if(i == 10){ console.log("After Edit",item.report_card); }
	});
	// End Arr Modification
	console.log("Post report card edit >",$scope.classRptCds);

	// console.log($scope);
	$rootScope.isPrinting = false;
	$scope.showSelect = ( $scope.student === undefined ? true : false );
	// $scope.classes = data.classes || [];
	// $scope.terms = data.terms || [];
	// $scope.filters = data.filters || [];
	// $scope.adding = data.adding;
	$scope.thestudent = {};
	$scope.examTypes = {};
	$scope.specialExamTypes = {};
	$scope.comments = {};
	$scope.principal_comment = {};
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
	if( $scope.filters.class.class_cat_id == 21 || $scope.filters.class.class_cat_id == 5 || $scope.filters.class.class_cat_id == 6 || $scope.filters.class.class_cat_id == 7 || $scope.filters.class.class_cat_id == 8 || $scope.filters.class.class_cat_id == 9 ){
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
			$scope.rmks = "Remarks";
			$scope.subj_name = "Subject"
	}

	$scope.chart_path = "";
	$scope.showDownloadButton = false;

	function getReportCardData(){
		//
	}

	function getLiveReportCardData(studentId,classId,termId){
		console.log("Fetching live report card ...");
		var rptCardParams = studentId + '/' + classId + '/' + termId;
		console.log("Passed params for live report card > " + rptCardParams);
		// var rptCardParams = $scope.student.student_id + '/' + $scope.filters.class.class_id + '/' + data.term_id;
		var loadRptCd = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				if( result.nodata )
				{
					$scope.reportsNotFound = true;
					$scope.errMsg = "There was no report card data found for this student.";
				}
				else
				{
					$scope.reportCd = result.data;
					// remove exam types not done
					if($scope.reportCd.exam_marks != null){
						$scope.reportCd.exam_marks = $scope.removeExamsNotDone($scope.reportCd.exam_marks);
					}
					// remove totals of exam types not done
					if($scope.reportCd.totals[0].total_marks != null){
						$scope.reportCd.totals[0].total_marks = $scope.removeNullTotals($scope.reportCd.totals[0].total_marks);
					}

					$scope.reportCd.closing_date = ($scope.reportCd.closing_date == null ? null : dateConverter($scope.reportCd.closing_date));
					$scope.reportCd.next_term_begins = ($scope.reportCd.next_term_begins == null ? null : dateConverter($scope.reportCd.next_term_begins));
					console.log("New Report Card Api",$scope.reportCd);
					if($scope.reportCd.subjects_column != null){
					for(let a=0;a < $scope.reportCd.subjects_column.length;a++){
						let subj = $scope.reportCd.subjects_column[a];
						for(let b=0;b < $scope.reportCd.subject_overalls_column[0].subject_overalls.length;b++){
							let subjOvrl = $scope.reportCd.subject_overalls_column[0].subject_overalls[b];

							if(subj.subject_id == subjOvrl.subject_id){
								subj.overall = subjOvrl;
								subjOvrl.sort_order = subj.sort_order;
							}
						}
						subj.exam_marks = [];
						$scope.reportCd.exam_marks.forEach((item, i) => {
							let exam =  item.exam_marks;
							exam.forEach((cat, i) => {
								if(subj.subject_id == cat.subject_id){
									subj.exam_marks.push(cat);
								}
							});
						});

					}
				}
					// console.log("After Edit",$scope.reportCd);
					if($scope.reportCd.calculation_mode == "Last Exam"){
						console.log("By Last Exam");
						$scope.reportCd.positions = $scope.reportCd.positions_by_last_exams;
					}
					console.log("After Edit",$scope.reportCd);
				}
			}
			else
			{
				$scope.reportsNotFound = true;
				$scope.errMsg = result.data;
			}
		}
		console.log("Params > " + rptCardParams);
		apiService.getLiveReportCardData(rptCardParams, loadRptCd, apiError);
	}

	$scope.screenshotReportCard = function(){
		console.log("Screenshot");
		// GENERATE PDF AND SAVE TO SERVER
		$scope.classRptCds.forEach((student, y) => {
			let element = $("#doc_"+student.student_id);

			// load html2canvas script and execute code on success
			$.getScript('/components/html2canvas.min.js', function()
			{
				// first hide all elements we don't want on print
				var myList = document.getElementsByClassName("hideForPrint");

				for(let i=0;i < myList.length; i++){
					myList[i].style.display="none";
				}

				var reportCardElement = $("#doc_"+student.student_id)[0]; // global variable
				// var reportCardElement = document.getElementById('doc_' + student.student_id);
				var getCanvas; // global variable
				console.log(reportCardElement);
				html2canvas(reportCardElement).then(function(canvas) {
					getCanvas = canvas;
					console.log("Canvas >",canvas);
					var dataURL = canvas.toDataURL();
					var reportCardName = student.student_id + '_' + $scope.schoolName + '_' + student.report_card.student_name.split(' ').join('_') + '_' + student.report_card.term_id;
					console.log("Report card name >",reportCardName);
					// console.log("Base 64 img ::: ",dataURL);
					// POST the data
					$.ajax({
					 type: "POST",
					 url: "srvScripts/handle_file_upload.php",
					 data: {
							imgBase64: dataURL,
							fileName: reportCardName + ".png",
							student_id: student.student_id,
							term_id: student.report_card.term_id
					 }
				 }).done(function(o) {
					 console.log('Report card data has been saved',o);
					 if(o){
						 let savedReportCard = JSON.parse(o);
						 $scope.generatePdf(savedReportCard);
					 }
				 });
				});

				for(let i=0;i < myList.length; i++){
					myList[i].style.display="";
				}

			});
		});

		// let element = $("#showReportCard"); // the element we want to convert to pdf
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
	}

	var initializeController = function()
	{
		document.getElementsByClassName('modal-content')[0].setAttribute("id", "allCards");
		document.getElementById('allCards').style.width = '100%';
	    // get lower school grading
	    // if($scope.schoolName == "lasalle" && $scope.entity_id <= 6){
	    //     getGrading2();
	    // }

		if( $scope.reportCardType == 'Standard' )
		{
			// get exam types

			apiService.getExamTypes($scope.filters.class.class_cat_id, function(response){

				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
				    $scope.rawExamTypes = result.data;
						console.log("Raw Exam Types > ",$scope.rawExamTypes);
						$scope.examTypes = [];
						for(let r=0;r < $scope.rawExamTypes.length;r++){
							// loop through all students exams & get unique exam types
							$scope.classRptCds.forEach((student, i) => {
								// console.log("Student > ",student);
								student.report_card.exam_marks.forEach((exam, j) => {
									if(exam.exam_type_id == $scope.rawExamTypes[r].exam_type_id){
										$scope.examTypes.push($scope.rawExamTypes[r]);
										$scope.rawExamTypes.splice(r,1);
									}
								});

							});

						}
					initReportCard();
				}

			}, apiError);

			apiService.getSpecialExamTypes($scope.filters.class.class_cat_id, function(response){

				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes2 = result.data;
					initReportCard();
				}

			}, apiError);

			// chart
			$scope.classRptCds.forEach((student, i) => {
				let graphPoints = student.report_card.totals[0].total_marks;

				var performanceLabels = [];
				var performanceData = [];

				for(let x=0;x < graphPoints.length;x++){
					performanceLabels.push(graphPoints[x].exam_type);
					performanceData.push(graphPoints[x].total);
				};

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

				let ctx = document.getElementById("line1_"+student.student_id).getContext("2d");
				initChart1(ctx, lineChartData);
				$timeout(callAtTimeout, 2000);
				$scope.efficiencyLoading = false;
			});
			// end chart
		}
		else if( $scope.reportCardType == 'Standard-v.2' )
		{
			// get exam types
			apiService.getExamTypes($scope.filters.class.class_cat_id, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes = result.data;
					initReportCard();
				}

			}, apiError);

			apiService.getSpecialExamTypes($scope.filters.class.class_cat_id, function(response){

				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.rawExamTypes2 = result.data;
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
					initReportCard();
				}

			}, apiError);
		}
		else
		{
			initReportCard();
		}

		// give the report card time to load then execute
		// setTimeout(function(){ $scope.screenshotReportCard(); }, 7000);

	}
	$timeout(initializeController,1);

	var initReportCard = function()
	{
		//
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
		//
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
		$scope.isClassTeacher = ( $scope.student.class_teacher_id == $rootScope.currentUser.emp_id ? true : false);

	});

	$scope.$watch('filters.class', function(newVal,oldVal){
		if( newVal == oldVal ) return;

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

	$scope.conf = function(el){
		//
	}

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
			//
		}
		else
		{
			//
		}

	}

	var getStreamPosition = function()
	{
	    //
	}

	var getExamMarksforReportCard = function()
	{
		//
	}

	var getSpecialExamMarksforReportCard = function()
	{
		//
	}

	var loadSubjects = function(response, status)
	{
		//
	}

	var loadStreamPOsition = function(response, status)
	{
		//
	}

	var loadExamMarks = function(response, status)
	{
		//
	}

	var loadSpecialExamMarks = function(response, status)
	{
		//
	}

	var buildReportBody = function(data)
	{
		// console.log(data);
		$scope.examMarks = data.details;
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
						console.log("Exam Types > ",$scope.examTypes);

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

	}

	var buildSpecialReportBody = function(data)
	{
		//
	}

	function callAtTimeout() {

	$scope.chart_path = getChartPath();

}

	var groupExamMarks = function(data)
	{
		//
	}

	var specialGroupExamMarks = function(data)
	{
		//
	}

	var loadReportCard = function(response, status)
	{
		//
	}

	var diffExamMarks = function(currentExamMarks,savedReportData)
	{
		//
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
		console.log("Filtered Exam Types > ",$scope.examTypes);
	}

	$scope.recreateReport = function()
	{
		//
	}

	$scope.revertReport = function()
	{
		//
	}

	$scope.setReportCardData = function(data)
	{
		//
	}

	$scope.updateReport = function()
	{
		//
	}

	$scope.print = function()
	{
		var criteria = {
			student : $scope.student,
			reportCd: $scope.reportCd,
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

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

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
		console.log(result);
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	$scope.downloadReportCard = function(){
		console.log("Params > ",$scope.savedReportCard);
		console.log("Name Param > ",$scope.savedReportCard.file_name.split('.png')[0]);
		// post parameters that will convert image to pdf
		$.ajax({
		 type: "POST",
		 url: "srvScripts/download_report_card.php",
		 data: {
				file_name: $scope.savedReportCard.file_name,
				name: $scope.savedReportCard.file_name.split('.png')[0],
				file_path: $scope.savedReportCard.file_path,
				school_name: $scope.schoolName
		 }
	 }).done(function(r) {
		 console.log('PDF success',r);
		 let results = JSON.parse(r);
		 console.log(results);
		 let pdfFile = '/assets/reportcards/' + results.school_name + '/' + results.name + '.pdf';
		 let link = document.createElement("a"); //create 'a' element
		 link.setAttribute("href", pdfFile);
		 link.setAttribute("download", pdfFile);// replace "file" here too
		 link.click();
	 });
		// download pdf
	}

	$scope.generatePdf = function(savedReportCard){
		console.log("Params > ",savedReportCard);
		console.log("Name Param > ",savedReportCard.file_name.split('.png')[0]);
		// post parameters that will convert image to pdf
		$.ajax({
		 type: "POST",
		 url: "srvScripts/download_report_card.php",
		 data: {
				file_name: savedReportCard.file_name,
				name: savedReportCard.file_name.split('.png')[0],
				file_path: savedReportCard.file_path,
				school_name: $scope.schoolName
		 }
	 }).done(function(r) {
		 console.log('PDF success',r);
		 let results = JSON.parse(r);
		 console.log("PDF generated",results);
	 });
	}

} ]);
