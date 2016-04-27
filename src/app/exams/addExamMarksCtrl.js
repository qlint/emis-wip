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
	
	
	$scope.$watch('filter.class',function(newVal,oldVal){
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
	
	$scope.getStudentExams = function()
	{
		$scope.examMarks = {};
		$scope.marksNotFound = false;
		
		if( $scope.dataGrid !== undefined )
		{
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;
		}

		var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
		apiService.getAllStudentExamMarks(request, loadMarks, apiError);
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
				$scope.errMsg = "There are currently no exam marks entered for this search criteria.";
			}
			else
			{
				$scope.examMarks = result.data;
				$scope.currentFilters = angular.copy($scope.filters);
				
				$scope.tableHeader = [];
				
				angular.forEach($scope.examMarks[0], function(value,key){
					if( ignoreCols.indexOf(key) === -1 )
					{
						var colRow = key.replace(/, /g , " / ").replace(/["']/g, "");
						
						$scope.tableHeader.push({
							title: colRow,
							key: key
						});
					}
				});

				console.log($scope.examMarks);
				
				
				$timeout(initDataGrid,10);
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
			console.log(filterFormWidth);
			$('#resultsTable_filter').css('left',filterFormWidth+45);
		}
		
		$window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				console.log('here');
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				console.log(filterFormWidth);
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
		
	}
	
	
	$scope.save = function(form)
	{

		if ( !form.$invalid ) 
		{
			console.log($scope.examMarks);
			var examMarks = [];
			angular.forEach($scope.examMarks, function(item,index){
				
				var exam = undefined;
				// need to get class_sub_exam_id
				angular.forEach(item, function(value,key){
					
					if( ignoreCols.indexOf(key) === -1 )
					{
						console.log(key);
						
						exam = $scope.classSubjectExams.filter(function(a){
							if( "'" + a.subject_name + "', '" + a.grade_weight + "'" == key ) return a;
						})[0];
					
						if( exam !== undefined )
						{
							examMarks.push({
								student_id : item.student_id,
								class_sub_exam_id: exam.class_sub_exam_id,
								term_id: $scope.currentFilters.term_id,
								mark: value
							});
						}
					}
					
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