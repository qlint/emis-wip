'use strict';

angular.module('eduwebApp').
controller('reportCardCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window, $parse){
	console.log("PASSED DATA > ",data);
	if(data.cbc_mode == undefined){
		$scope.isCbcMode = (data.filters.class.report_card_type == 'CBC' ? true : false);
	}else{
		$scope.isCbcMode = data.cbc_mode;
	}
	console.log('cbc mode >',$scope.isCbcMode);
	if($scope.isCbcMode == true){
		$scope.cbcModeExam = data.exam_type;
		$scope.cbcExamTypeId = data.exam_type_id;
	}

	$scope.cbcClassExamTypes = [];

	$scope.getClassExamTypes = function(cat){
		console.log("Fetching class exam types",cat);
		apiService.getExamTypes(cat, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success'){
				console.log("Exam types found > ",result.data);
				$scope.cbcClassExamTypes = result.data;
			}
		}, apiError);
	}

	$scope.getClassCat = function(class_id){
		if(class_id){
			console.log('Received id = '+class_id);
			console.log($scope.classes);
			var selectedClass = $scope.classes.filter(function (el) {
			  return el.class_id == class_id;
			});
			console.log('Selected Class >',selectedClass);
			$scope.getClassExamTypes(selectedClass.class_cat_id);
		}
	}

	if($scope.isCbcMode == true){
		$scope.getClassExamTypes(data.filters.class_cat_id);
	}
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
	if($scope.student){ $scope.entity_id = ($scope.student.entity_id == null || $scope.student.entity_id == undefined ? null : $scope.student.entity_id); }
	console.log("Entity :",$scope.entity_id);
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
	if( $scope.entity_id < 9 ){
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
		console.log("Final Object Array > ",objArr);
		return objArr;
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
		console.log("Final Totals > ",objArr);
		return objArr;
	}

	function getReportCardData(){
		if($scope.student){
			var rptCardParams = $scope.student.student_id + '/' + $scope.filters.class.class_id + '/' + (data.term_id == null || data.term_id == undefined ? $scope.filters.term_id : data.term_id);
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
						/* use cbc report card for grade 1 to 5 */
						if($scope.schoolName == "lasalle" && $scope.reportCd.entity_id <= 9){
							// console.log("Entity >",$scope.reportCd.entity_id);
				        getGrading2();
				    }
						// console.log("Filtered Exam Types > ",$scope.examTypes);
						if($scope.reportCd.report_card_type != null){$scope.reportCardType = ($scope.reportCd.report_card_type == null || $scope.reportCd.report_card_type == undefined ? 'Standard' : $scope.reportCd.report_card_type);}
						// remove exam types not done
						if($scope.reportCd.exam_marks != null){
							$scope.reportCd.exam_marks = $scope.removeExamsNotDone($scope.reportCd.exam_marks);
						}
						// remove totals of exam types not done
						if($scope.reportCd.totals[0].total_marks != null){
							$scope.reportCd.totals[0].total_marks = $scope.removeNullTotals($scope.reportCd.totals[0].total_marks);
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
							let theDate = day + postFix + ' ' + months[month - 1] + ', ' + year;
							// console.log(theDate);
							return theDate;
						}
						console.log("Unconverted date >",$scope.reportCd.closing_date);
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
									if(subj.subject_name.toLowerCase() == cat.subject_name.toLowerCase()){
										// console.log("Pushing to subjects_column >",cat);
										subj.exam_marks.push(cat);
									}
								});
							});

						}
						$scope.reportCd.subjects_column.sort((a, b) => (a.sort_order > b.sort_order) ? 1 : -1);
						$scope.reportCd.subjects_column.forEach((item, i) => {
							item.exam_marks.sort((a, b) => (a.exam_sort > b.exam_sort) ? 1 : -1);
						});
						$scope.reportCd.totals[0].total_marks.sort((a, b) => (a.exam_sort > b.exam_sort) ? 1 : -1);

						console.log('SORTED > ',$scope.reportCd.totals[0]);
						$scope.reportCd.subject_overalls_column[0].subject_overalls.sort((a, b) => (a.sort_order > b.sort_order) ? 1 : -1);
						if($scope.schoolName == "lasalle" && $scope.entity_id <= 7){
							for(let x=0;x < $scope.reportCd.subject_overalls_column[0].subject_overalls.length;x++){
								$scope.reportCd.subject_overalls_column[0].subject_overalls[x].grade = $scope.reportCd.subject_overalls_column[0].subject_overalls[x].grade2;
								$scope.reportCd.subject_overalls_column[0].subject_overalls[x].comment = $scope.reportCd.subject_overalls_column[0].subject_overalls[x].comment2;
							}
						}
						console.log($scope);
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
			apiService.getReportCardData(rptCardParams, loadRptCd, apiError);
		}
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
						console.log(theDate);
						return theDate;
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

		if( $scope.schoolName == 'lasalle' && $scope.filters.class.report_card_type == 'CBC' ){
			console.log("Lower Sch.");
			var params = '';
			if( $scope.isTeacher && !$scope.isClassTeacher ) params = $scope.filters.class.class_cat_id + '/true/' + $rootScope.currentUser.emp_id;
			else  params = $scope.filters.class.class_cat_id + '/true/0';
			// console.log("ReportCd Obj",$scope.reportCd);
			$scope.reportCd = {};
			$scope.reportCd.playgroup_report_card = [];
			apiService.getAllSubjects(params,
																function(response, status){
																	var result = angular.fromJson(response);
																	console.log("Lower school result >",result);
																	if( result.response == 'success'){
																		for (var i = 0; i < result.data.length; i++) {
																			let subjObj = {};
																			subjObj.active = result.data[i].active;
																			subjObj.class_cat_id = result.data[i].class_cat_id;
																			subjObj.class_cat_name = result.data[i].class_cat_name;
																			subjObj.has_children = result.data[i].has_children;
																			subjObj.parent_subject_id = result.data[i].parent_subject_id;
																			subjObj.parent_subject_name = result.data[i].parent_subject_name;
																			subjObj.sort_order = result.data[i].sort_order;
																			subjObj.subject_id = result.data[i].subject_id;
																			subjObj.subject_name = result.data[i].subject_name;
																			subjObj.remarks = null;
																			subjObj.skill_level = null;
																			subjObj.use_for_grading = result.data[i].use_for_grading;
																			subjObj.teacher_id = result.data[i].teacher_id;
																			subjObj.teacher_name = result.data[i].teacher_name;
																			$scope.reportCd.playgroup_report_card.push(subjObj);
																		}
																		console.log("ReportCd Obj",$scope.reportCd);
																	}
																},
																apiError);
		}else{
			console.log("Params 1 > " + rptCardParams);
			apiService.getLiveReportCardData(rptCardParams, loadRptCd, apiError);
		}

		console.log("Params 2 > " + rptCardParams);
		if($scope.isCbcMode == true){
			console.log('Using live cbc');
			apiService.getLiveCbcReportCardData(rptCardParams, loadRptCd, apiError);
		}else{
			console.log('Using regular cbc');
			apiService.getLiveReportCardData(rptCardParams, loadRptCd, apiError);
		}

		/*
		var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.updateReportTermId;
		if( $scope.isTeacher && !$scope.isClassTeacher ){ params += '/' + $rootScope.currentUser.emp_id };
		// apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);

		if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7 || $scope.entity_id == undefined || $scope.entity_id == null) ){
			// console.log("Lsl. Lower Sch.");
				apiService.getLowerSchoolExamMarksforReportCard(params, loadExamMarks, apiError);
		}else{
			// console.log("Upper Sch.");
			// console.log(params);
			apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
			if($scope.isSpecialExam == true){ apiService.getSpecialExamMarksforReportCard(params, loadSpecialExamMarks, apiError); }
		}
		*/
	}

	$scope.screenshotReportCard = function(){
		console.log("Screenshot");
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
		$.getScript('/components/html2canvas.min.js', function()
		{
			// first hide all elements we don't want on print
			var myList = document.getElementsByClassName("hideForPrint");

			for(let i=0;i < myList.length; i++){
				myList[i].style.display="none";
			}

			var reportCardElement = document.getElementById("showReportCard"); // global variable
			var getCanvas; // global variable
			/*
			html2canvas(reportCardElement, {
			onrendered: function (canvas) {
						 // $("#previewImage").append(canvas);
						 getCanvas = canvas;
						 var dataURL = canvas.toDataURL();
						 console.log("Base 64 img ::: ",dataURL);
						 console.log($scope);
						 // POST the data
						 $.ajax({
						  type: "POST",
						  url: "srvScripts/handle_file_upload.php",
						  data: {
						     imgBase64: dataURL,
								 fileName: "sample.jpg"
						  }
						}).done(function(o) {
						  console.log('saved',o);
						});

					}
			});
			*/
			html2canvas(reportCardElement).then(function(canvas) {
				getCanvas = canvas;
				var dataURL = canvas.toDataURL();
				var reportCardName = $scope.student.student_id + '_' + $scope.schoolName + '_' + $scope.student.student_name.split(' ').join('_') + '_' + ($scope.report.term_id ? $scope.report.term_id : $scope.filters.term_id)
				// console.log("File Name >",reportCardName + ".png");
				// console.log("Base 64 img ::: ",dataURL);
				// POST the data
				$.ajax({
				 type: "POST",
				 url: "srvScripts/handle_file_upload.php",
				 data: {
						imgBase64: dataURL,
						fileName: reportCardName + ".png",
						student_id: $scope.student.student_id,
						term_id: ($scope.report.term_id ? $scope.report.term_id : $scope.filters.term_id)
				 }
			 }).done(function(o) {
				 console.log('Report card data has been saved',o);
				 if(o){
					 $scope.savedReportCard = JSON.parse(o);
					 $scope.showDownloadButton = true;
					 document.getElementById('dnld').classList.remove("ng-hide");
					 console.log("Show download button? " + $scope.showDownloadButton);
					 $scope.generatePdf();
				 }
			 });
			});

			for(let i=0;i < myList.length; i++){
				myList[i].style.display="";
			}

		});
	}

	var initializeController = function()
	{

		if(data.generateOrFetch == 'generate'){
			getLiveReportCardData($scope.student.student_id,$scope.filters.class.class_id,(data.term_id == null || data.term_id == undefined ? $scope.filters.term_id : data.term_id));
		}else{
			getReportCardData();
		}
		// get lower school grading
		if($scope.schoolName == "lasalle" && $scope.entity_id <= 9){
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

		// give the report card time to load then execute
		setTimeout(function(){ $scope.screenshotReportCard(); }, 7000);

	}
	$timeout(initializeController,1);

	var initReportCard = function()
	{
		if( data.reportData !== undefined )
		{
		  // console.log('data.reportData > ',data.reportData);
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

	$scope.conf = function(el){
		// console.log("TERM CHANGE DETECTED > ",el);
		$scope.filters.term_id = el.filters.term.term_id;
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
		// console.log($scope);

		if($scope.isCbcMode == true){
			// console.log($scope.filters);
			var selectedExamType = $scope.cbcClassExamTypes.filter(function (el) {
			  return el.exam_type_id == $scope.filters.exam_type_id.exam_type_id;
			});
			// console.log('Selected exam type >',selectedExamType);
			$scope.cbcModeExam = selectedExamType[0].exam_type;
			console.log('CBC Exam Type >',$scope.cbcModeExam);
		}

		// "real time"
		var loadRptCd = function(response,status)
		{
			var result = angular.fromJson( response );
			console.log("Generating report card with live data",result);
			if( result.response == 'success' )
			{
				if( result.nodata )
				{
					console.log("No live data for the selected term");
					$scope.reportsNotFound = true;
					$scope.errMsg = "There was no report card data found for this student.";
				}
				else
				{
					$scope.reportCd = result.data;
					if($scope.schoolName == "lasalle" && $scope.reportCd.entity_id <= 9){
						console.log("Entity >",$scope.reportCd.entity_id);
			        getGrading2();
			    }
					console.log("Live data found. Raw data > ",$scope.reportCd);
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
						let theDate = day + postFix + ', ' + months[month] + ' ' + year;
						console.log(theDate);
						return theDate;
					}
					$scope.reportCd.closing_date = ($scope.reportCd.closing_date == null ? null : dateConverter($scope.reportCd.closing_date));
					$scope.reportCd.next_term_begins = ($scope.reportCd.next_term_begins == null ? null : dateConverter($scope.reportCd.next_term_begins));
					console.log("New Report Card Api",$scope.reportCd);
					for(let a=0;a < $scope.reportCd.subjects_column.length;a++){
						let subj = $scope.reportCd.subjects_column[a];
						for(let b=0;b < $scope.reportCd.subject_overalls_column[0].subject_overalls.length;b++){
							let subjOvrl = $scope.reportCd.subject_overalls_column[0].subject_overalls[b];

							if(subj.subject_id == subjOvrl.subject_id){
								subj.overall = subjOvrl;
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
		var rptCardParams = $scope.student.student_id + '/' + $scope.filters.class.class_id + '/' + $scope.filters.term.term_id;
		console.log("Params for live data : " + rptCardParams);
		console.log(data);
		if(data.generateOrFetch == 'generate'){
			console.log('Generating a new one');
			if($scope.isCbcMode == true){
				console.log('Using live cbc');
				apiService.getLiveCbcReportCardData(rptCardParams, loadRptCd, apiError);
			}else{
				console.log('Using regular live');
				apiService.getLiveReportCardData(rptCardParams, loadRptCd, apiError);
			}
		}else{
			console.log('Fetching existing one');
			apiService.getReportCardData(rptCardParams, loadRptCd, apiError);
		}

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
			console.log('RECREATING');
			/* user has requested to recreate an existing report card, fetch student exam marks and build report */
			$scope.savedReport = false;
			$scope.recreated = true;

			// var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
			var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.updateReportTermId;
			if( $scope.isTeacher && !$scope.isClassTeacher ){ params += '/' + $rootScope.currentUser.emp_id };
			// apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);

			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7 || $scope.entity_id == undefined || $scope.entity_id == null) ){
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

			// if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7) ){
			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7) ){
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

			// if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7 || $scope.entity_id == undefined || $scope.entity_id == null) ){
			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7 || $scope.entity_id == undefined || $scope.entity_id == null) ){
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

			// if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7 || $scope.entity_id == undefined || $scope.entity_id == null) ){
			if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7 || $scope.entity_id == undefined || $scope.entity_id == null) ){
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
			// apiService.getAllSubjects(params, loadSubjects, apiError);
			apiService.getAllSubjectsInExamTypes(params, loadSubjects, apiError);
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
				console.log("Fetched subjects",$scope.reportData.subjects);

				// this if logic is simply to enable us indent subjects appropriately on the report card by adding some object keys
				if($scope.reportCardType == 'Playgroup' && $scope.schoolName == 'lasalle' || $scope.reportCardType == 'CBC' && $scope.schoolName == 'lasalle'){

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
				console.log('$scope.savedReportData >',$scope.savedReportData);
				if( $scope.savedReportData === undefined )
				{
					var params = $scope.student.student_id + '/' + $scope.report.class_id + '/' + $scope.report.term_id;
					if($scope.isCbcMode == true){
						apiService.getStudentCbcReportCard(params,loadReportCard, apiError);
					}else{
						apiService.getStudentReportCard(params,loadReportCard, apiError);
					}

					getLiveReportCardData($scope.student.student_id,$scope.filters.class.class_id,$scope.filters.term_id);
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
							}else if( $scope.reportCardType == 'Playgroup' || $scope.reportCardType == 'CBC'){
							    item.remarks = angular.copy(orgData.remarks);
							    item.indent = angular.copy(orgData.indent);
									item.skill_level = angular.copy(orgData.skill_level);
							}else{
							    item.skill_level = angular.copy(orgData.skill_level);
							}
						}
					});

				}

				if( $scope.reportCardType == 'Playgroup' || $scope.reportCardType == 'CBC'){
					if($scope.savedReportData){
						$scope.reportData.subjects = $scope.savedReportData.subjects;
					}
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
					getLiveReportCardData($scope.student.student_id,$scope.filters.class.class_id,$scope.filters.term_id);
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
					getLiveReportCardData($scope.student.student_id,$scope.filters.class.class_id,$scope.filters.term_id);
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
		// console.log(data.details);
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
		// $scope.graphPoints = data.graphPoints;
		$scope.graphPoints = $scope.reportCd.totals[0].total_marks;

		var performanceLabels = [];
		var performanceData = [];

		angular.forEach($scope.graphPoints, function (item, key) {
		    performanceLabels.push(item.exam_type);
		    performanceData.push(item.total);
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
						console.log($rootScope.rawExamTypes);
				}

        		$scope.examTypes = $scope.rawExamTypes.filter(function(item){
        			var found = $scope.reportCd.exam_marks.filter(function(item2){
        				if( item.exam_type_id == item2.exam_type_id ) return item2;
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
				if( $scope.schoolName == 'lasalle' && ($scope.entity_id <= 7 || $scope.entity_id == undefined || $scope.entity_id == null) ){
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
		console.log("Filtered Exam Types > ",$scope.examTypes);
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
			reportData: ( $scope.reportCardType == 'Playgroup' || $scope.reportCardType == 'CBC' ? $scope.savedReportData : $scope.reportData ),
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
		console.log("Add report card data :::",data);
		if(data.report_card_type == 'CBC'){
			console.log('cbc et id',$scope.cbcExamTypeId);
			data.exam_type_id = $scope.cbcExamTypeId;
			console.log('PAYLOAD > ',data);
			apiService.addCbcReportCard(data,createCompleted,apiError);
		}else{
			apiService.addReportCard(data,createCompleted,apiError);
		}

		// give the report card time to load then execute
		setTimeout(function(){ $scope.screenshotReportCard(); }, 2000);

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

	$scope.generatePdf = function(){
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
		 console.log("PDF generated",results);
	 });
	}

} ]);
