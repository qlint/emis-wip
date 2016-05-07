'use strict';

angular.module('eduwebApp').
controller('reportCardCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window){

	$rootScope.isPrinting = false;
	$scope.student = data.student;
	$scope.classes = data.classes || [];
	$scope.terms = data.terms || [];
	$scope.filters = data.filters || [];
	$scope.adding = data.adding;

	
	$scope.canPrint = false;
	
	$scope.report = {};
	
	$scope.showReportCard = false;
	
	var initializeController = function()
	{
		
		if( data.reportData !== undefined )
		{
			console.log('here');
			// passing in a report to view, load it
			
			$scope.reportData = angular.fromJson(data.reportData);
			$scope.originalData = angular.copy($scope.reportData);
			
			$scope.report.class_name = data.class_name;
			$scope.report.term = data.term_name;
			$scope.report.year = data.year;
			
			$scope.currentFilters = {};
			$scope.currentFilters.term = {};
			$scope.currentFilters.class = {};
			$scope.currentFilters.term.term_id = data.term_id;
			$scope.currentFilters.class.class_id = data.class_id;
	
			$scope.setReportCardData();
		}
		else
		{
			apiService.getNextTerm({}, function(response,status){
				var result = angular.fromJson(response);				
				if( result.response == 'success')
				{ 
					$scope.nextTermStartDate = result.data.start_date;
				}
			}, apiError);
		}
		
		// get exam types
		var params = data.class_id || $scope.filters.class.class_id;
		apiService.getClassExams(params, function(response){
			var result = angular.fromJson(response);				
			if( result.response == 'success')
			{ 
				var examTypes = result.data;	
				
				// get unique exam types
				$scope.examTypes = examTypes.reduce(function(sum,item){
					if( sum.indexOf(item.exam_type) === -1 ) sum.push(item.exam_type);
					return sum;
				}, []);
			}
			
		}, function(){});
		
		/*
		// get grading
		apiService.getGrading({}, function(response,status,params){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.gradeLevels = ( result.nodata ? [] : result.data );
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, apiError);
		*/
	
		
	}
	$timeout(initializeController,1);
	
	$scope.$watch('filters.term',function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		$scope.filters.term_id = newVal.term_id;
	});
	
	
	$scope.getProgressReport = function()
	{
		$scope.currentFilters = angular.copy($scope.filters);
		$scope.report.class_name = $scope.currentFilters.class_name;
		$scope.report.term = $scope.currentFilters.term.term_name;
		$scope.report.year = $scope.currentFilters.term.year;
		
		// check to see if there is already a report card with this criteria
		var params = $scope.student.student_id + '/' + $scope.filters.class.class_id + '/' + $scope.filters.term_id
		apiService.getStudentReportCard(params,loadReportCard, apiError);
		
	}	
	
	var loadReportCard = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			// check if a report was found for the selected criteria
			// if not, grab exam marks and build
			if( result.nodata === undefined )
			{
				$scope.canPrint = true;
				$scope.showReportCard = true;
		
				$scope.report = result.data;
				$scope.reportData = ( result.data.report_data !== null ? angular.fromJson(result.data.report_data) : []);
				$scope.originalData = angular.copy($scope.report);
				
				$scope.report.class_name = $scope.report.class_name;
				$scope.report.term = $scope.report.term_name;
				$scope.report.year = $scope.report.year;
		
				$scope.setReportCardData();
			}			
			else
			{
				// get exam marks
				var params = $scope.student.student_id + '/' + $scope.filters.class.class_id + '/' + $scope.filters.term_id
				apiService.getExamMarksforReportCard(params, loadExamMarks, apiError);
			}
		}
	}
	
	$scope.setReportCardData = function(data)
	{		
		$scope.canPrint = true;
		$scope.showReportCard = true;
			
		$scope.overall = $scope.reportData.position;
		$scope.overallLastTerm = $scope.reportData.position_last_term;
		
		$scope.total_overall_mark = $scope.reportData.total_overall_mark;
		$scope.totals = $scope.reportData.totals;
		
		$scope.comments = $scope.reportData.comments;
		$scope.nextTermStartDate = $scope.reportData.nextTerm;
		
		console.log($scope.reportData);
	}
	
	var loadExamMarks = function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			if( result.details === false )
			{
				
			}
			else
			{
				$scope.showReportCard = true;
				$scope.examMarks = result.data.details;
				$scope.overallSubjectMarks = result.data.subjectOverall;
				$scope.overall = result.data.overall;
				$scope.overallLastTerm = result.data.overallLastTerm;
				
				if( $scope.reportData !== undefined )
				{
					// there is already a report generated, 
					// will need to check if the marks set in database differ from what is stored on the report card
					// if no, display a message and allow them to update report card
				}
				else
				{
					$scope.report.class_name = $scope.currentFilters.class.class_name;
					$scope.report.term = $scope.currentFilters.term.term_name;
					$scope.report.year = $scope.currentFilters.term.year;
					
					$scope.reportData = {};
									
					// group by subject
					$scope.reportData.subjects = [];
					var lastSubject = '';
					var marks = {};
					var i = 0;
					angular.forEach($scope.examMarks, function(item,key){
						
						if( item.subject_name != lastSubject )
						{
							// changing to new subject, store the marks
							if( i > 0 ) $scope.reportData.subjects[(i-1)].marks = marks;
							
							$scope.reportData.subjects.push(
								{
									subject_name: item.subject_name
								}
							);
							
							marks = {};
							i++;
						}
						marks[item.exam_type] = {
							mark: item.mark,
							grade_weight: item.grade_weight,
							position: item.rank,
							grade: item.grade
						}
						
						lastSubject = item.subject_name;
						
					});
					$scope.reportData.subjects[(i-1)].marks = marks;
					console.log($scope.reportData);
					
					
					// set overall
					var total_marks = 0;
					var total_grade_weight = 0;
					$scope.total_overall_mark = 0;
					angular.forEach( $scope.reportData.subjects, function(item,key){

						var overall = $scope.overallSubjectMarks.filter(function(item2){
							if( item.subject_name == item2.subject_name ) return item2;
						})[0];
						
						item.overall_mark = overall.percentage;
						item.overall_grade = overall.grade;
						item.position = overall.rank;
						
						total_marks += parseInt(overall.total_mark);
						total_grade_weight += parseInt(overall.total_grade_weight);
						
						return item;
					});
					console.log(total_marks);
					console.log(total_grade_weight);
					
					$scope.total_overall_mark += Math.round((total_marks/total_grade_weight)*100);
					
					
					// set totals
					$scope.totals = {};
					angular.forEach( $scope.reportData.subjects, function(item,key)
					{						
						angular.forEach( item.marks, function(item,key)
						{			
							if( $scope.totals[key] === undefined ) $scope.totals[key] = {total_mark:0, total_grade_weight:0};
							$scope.totals[key].total_mark += item.mark;
							$scope.totals[key].total_grade_weight += item.grade_weight;
						});						
					});
					console.log($scope.totals);
					
					/*
					// add up overall totals, get average per subject
					var sumMarks = 0;
					var sumGradeWeight = 0;
					$scope.examMarks.subjects = $scope.examMarks.subjects.map(function(item){

						sumMarks = Object.keys(item.marks).reduce(function(sum,key){
							sum = sum + parseInt(item.marks[key].mark);
							return sum;
						}, 0);
						
						sumGradeWeight = Object.keys(item.marks).reduce(function(sum,key){
							sum = sum + parseInt(item.marks[key].grade_weight);
							return sum;
						}, 0);
						
						item.overall_mark = Math.round((sumMarks/sumGradeWeight)*100);
						
						// get grade for overall mark
						var level = $scope.gradeLevels.filter(function(level){
							if( item.overall_mark >= level.min_mark && item.overall_mark <= level.max_mark ) return level;
						})[0];
						console.log(level);
						item.overall_grade = level.grade;
						
						return item;
					});
					*/
				
				}
			}
		}
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
			overallLastTerm: $scope.overallLastTerm,
			examTypes: $scope.examTypes,
			reportData: $scope.reportData,
			totals: $scope.totals,
			comments: $scope.comments,
			nextTermStartDate: $scope.nextTermStartDate
		}

		var domain = window.location.host;
		var newWindowRef = window.open('http://' + domain + '/#/exams/report_card/print');
		newWindowRef.printCriteria = criteria;
	}
	
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	
	$scope.save = function()
	{
		$scope.reportData.position = $scope.overall;
		$scope.reportData.position_last_term = $scope.overallLastTerm;
		$scope.reportData.totals = $scope.totals;
		$scope.reportData.total_overall_mark = $scope.total_overall_mark;
		$scope.reportData.comments = $scope.comments;
		$scope.reportData.nextTerm = $scope.nextTermStartDate;
		
		var data = {
			user_id: $rootScope.currentUser.user_id,
			student_id: $scope.student.student_id,
			term_id : $scope.currentFilters.term.term_id,
			class_id : $scope.currentFilters.class.class_id,
			report_data : JSON.stringify($scope.reportData)
		}
		console.log(data);
		apiService.addReportCard(data,createCompleted,apiError);			
		
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
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