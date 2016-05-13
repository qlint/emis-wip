'use strict';

angular.module('eduwebApp').
controller('addExamMarksCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window){

	var ignoreCols = ['student_id','student_name','sum','total'];
	
	var initializeController = function()
	{
		$scope.classes = data.classes;
		$scope.terms = data.terms;
		$scope.examTypes = data.examTypes;		
		$scope.filters = data.filters;

		
		getClassDetails($scope.filters.class_id);
		$scope.getStudentExams();
	}
	setTimeout(initializeController,1);
	
	
	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		$scope.filters.class_id = newVal.class_id;
		getClassDetails($scope.filters.class_id);
		
		apiService.getExamTypes(newVal.class_cat_id, function(response){
			var result = angular.fromJson(response);				
			if( result.response == 'success'){ $scope.examTypes = result.data;}			
		}, apiError);
	});
		
	var getClassDetails = function(classId)
	{
		apiService.getClassExams(classId,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.classSubjectExams = result.data;
			}
		}, apiError);
	}	
	
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.getStudentExams = function(theForm)
	{
		theForm.$submitted = true;
		if( !theForm.$invalid )
		{
			$scope.examMarks = {};
			$scope.marksNotFound = false;
			
			if( $scope.dataGrid !== undefined )
			{
				$scope.dataGrid.destroy();
				$scope.dataGrid = undefined;
			}
			
			var request = $scope.filters.class_id + '/' + $scope.filters.exam_type_id;
			apiService.getClassExams(request, function(response){
				$scope.loading = false;
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					if( result.nodata !== undefined)
					{
						$scope.marksNotFound = true;
						$scope.errMsg = "The selected exam is not set up for this class.";
					}
					else
					{
						$scope.subjects = result.data;
						
						var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
						apiService.getClassExamMarks(request, loadMarks, apiError);
					}
				}
			}, apiError)
		}	
	}
	
	var loadMarks = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{			
			if( result.nodata )
			{
				$scope.marksNotFound = true;
				$scope.errMsg = "There are no students in the selected classes.";
			}
			else
			{
				$scope.students = result.data;
				$scope.currentFilters = angular.copy($scope.filters);
				
				// loop through exam marks and build into
				// one object per student, with
				$scope.examMarks = [];
				var lastStudent = '';
				var marks = {};
				var i = 0;
				angular.forEach($scope.students, function(item,key){
					if( item.student_id != lastStudent )
					{
						// changing to new student, store the report
						if( i > 0 ) $scope.examMarks[(i-1)].marks = marks;
						
						$scope.examMarks.push(
							{
								student_name: item.student_name,
								student_id: item.student_id,								
								marks : {}
							}
						);
						
						marks = {};
						i++;

					}
					
					var thesubject = $scope.subjects.filter(function(subject){
						if ( subject.subject_name == item.subject_name ) return subject;
					})[0];
					console.log(thesubject);
					
					marks[thesubject.subject_name] = {
						mark: item.mark,
						class_sub_exam_id: item.class_sub_exam_id,
						grade_weight: item.grade_weight
					};
					
					lastStudent = item.student_id;
					
				});
				$scope.examMarks[(i-1)].marks = marks;
				console.log($scope.examMarks);
				
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}
	}
	
	var initDataGrid = function() 
	{
		var tableElement = $('#resultsTable2');
		$scope.dataGrid = tableElement.DataTable( {
				responsive: {
					details: {
						type: 'column'
					}
				},
				columnDefs: [ {
					className: 'control',
					orderable: false,
					targets:   0
				} ],
				paging: false,
				destroy:true,
				order: [2,'asc'],
				filter: true,
				info: false,
				sorting:[],
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
						search: "Search Results<br>",
						searchPlaceholder: "Filter",
						lengthMenu: "Display _MENU_",
						emptyTable: "No students found."
				},
			} );
			
		
		var headerHeight = $('.navbar-fixed-top').height();
		//var subHeaderHeight = $('.subnavbar-container.fixed').height();
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 13 );
		new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );
		
		
		// position search box
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			//console.log(filterFormWidth);
			$('#resultsTable_filter').css('left',filterFormWidth+45);
		}
		
		$window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				//console.log('here');
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				//console.log(filterFormWidth);
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
		
	}
	
	$scope.save = function(form)
	{

		if ( !form.$invalid ) 
		{
		
			var examMarks = [];
			angular.forEach($scope.examMarks, function(item,index){
				
				var exam = undefined;
				// need to get class_sub_exam_id
				angular.forEach(item.marks, function(mark,key){
					examMarks.push({
						student_id : item.student_id,
						class_sub_exam_id: mark.class_sub_exam_id,
						term_id: $scope.currentFilters.term_id,
						mark: mark.mark
					});
				});
			});
			
			
			var data = {
				user_id: $rootScope.currentUser.user_id,
				exam_marks: examMarks
			}
			console.log(data);
			apiService.addExamMarks(data,createCompleted,apiError);			
		}
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Exam mark was updated.' : 'Exam marks were added.');
			$rootScope.$emit('examMarksAdded', {'msg' : msg, 'clear' : true});
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