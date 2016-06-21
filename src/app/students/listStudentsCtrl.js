'use strict';

angular.module('eduwebApp').
controller('listStudentsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filters.class_cat_id = ( $state.params.filter !== '' ? $state.params.filter : null );
	$scope.filterClassCat = ( $state.params.filter !== '' ? true : false );
	//console.log($state.params.filter);
	//console.log($scope.filtering);
	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;	
	$rootScope.modalLoading = false;
	$scope.alert = {};
	
	
	var initializeController = function () 
	{
		// if user is a teacher, we only want to give them class categories and classes that they are associated with
		if ( $rootScope.currentUser.user_type == 'TEACHER' )
		{
			apiService.getClassCats($rootScope.currentUser.emp_id, function(response){
				var result = angular.fromJson(response);
				
				// store these as they do not change often
				if( result.response == 'success')
				{				
					$rootScope.classCats = result.data;
					
					// get classes
					if( $rootScope.allClasses === undefined )
					{
						apiService.getAllClasses({}, function(response){
							var result = angular.fromJson(response);
							
							// store these as they do not change often
							if( result.response == 'success') 
							{
								$rootScope.allClasses = result.data;
								$scope.classes = $rootScope.allClasses;
								getStudents('true',false );
								if( $rootScope.classCats.length == 1 ) $scope.filters.class_cat_id = $rootScope.classCats[0].class_cat_id;
							}
							
						}, apiError);
					}
					else
					{
						$scope.classes = $rootScope.allClasses;
						getStudents('true',false);
						if( $rootScope.classCats.length == 1 ) $scope.filters.class_cat_id = $rootScope.classCats[0].class_cat_id;
					}
				}
				
			}, apiError);
		}
		else
		{
			// get classes
			if( $rootScope.allClasses === undefined )
			{
				apiService.getAllClasses({}, function(response){
					var result = angular.fromJson(response);
					
					// store these as they do not change often
					if( result.response == 'success') 
					{
						$rootScope.allClasses = result.data;
						$scope.classes = $rootScope.allClasses;
						getStudents('true',false );
					}
					
				}, apiError);
			}
			else
			{
				$scope.classes = $rootScope.allClasses;
				getStudents('true',false);
			}
		}
		
		

	}
	$timeout(initializeController,1);
	
	var getStudents = function(status, filtering)
	{		
		if ( $rootScope.currentUser.user_type == 'TEACHER' )
		{
			var params = $rootScope.currentUser.emp_id + '/' + status;
			apiService.getTeacherStudents(params, loadStudents, apiError, {filtering:filtering});
		}
		else
		{
			console.log(filtering);
			apiService.getAllStudents(status, loadStudents, apiError, {filtering:filtering,status:status});
		}
		
	}
	
	var loadStudents = function(response,status, params)
	{
		var result = angular.fromJson(response);			
		
		if( result.response == 'success')
		{
			
			if( $scope.dataGrid !== undefined )
			{	
				//$scope.dataGrid.clear();
				$scope.dataGrid.destroy();				
			}
					
			if( result.nodata ) var formatedResults = [];
			else {
				// make adjustments to student data
				var formatedResults = $rootScope.formatStudentData(result.data);
			}
			
			if( params.status == 'false' )
			{
				$scope.formerStudents = formatedResults
				filterStudents(false);
			}
			else
			{
				$scope.allStudents = formatedResults;
				$scope.students = formatedResults;
				
				if( $scope.filterClassCat ) filterStudents(false);
				$timeout(initDataGrid,100);
			}		
			
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
	
	
		
	var initDataGrid = function() 
	{

		var tableElement = $('#resultsTable');
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
				order: [1,'asc'],
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
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 41 );
		
		$timeout(function(){
			console.log($scope.dataGrid);
			$scope.fixedHeader = new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );
		},1000);
		
		
		// position search box
		setSearchBoxPosition();
		
		if( initialLoad ) setResizeEvent();
		
		
	}
	
	var setSearchBoxPosition = function()
	{
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+45);
		}
	}
	
	var setResizeEvent = function()
	{
		 initialLoad = false;

		 $window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
	}
	
	$scope.$watch('filters.class_cat_id', function(newVal,oldVal){
		if (oldVal == newVal) return;
		
		if( newVal === undefined || newVal == '' ) 	$scope.classes = $rootScope.allClasses;
		else
		{	
			// filter classes to only show those belonging to the selected class category
			$scope.classes = $rootScope.allClasses.reduce(function(sum,item){
				if( item.class_cat_id == newVal ) sum.push(item);
				return sum;
			}, []);
			$timeout(setSearchBoxPosition,10);
			
		}
	});
		
	$scope.toggleFilter = function()
	{
		$scope.filterShowing = !$scope.filterShowing;
		
		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}
	
	$scope.toggleTools = function()
	{
		$scope.toolsShowing = !$scope.toolsShowing;
		
		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		isFiltered = true;
		
		// if user is filtering for former students and we have not previously pulled these, get them, then continue to filter
		if( $scope.filters.status == 'false' && $scope.formerStudents === undefined )
		{
			// we need to fetch inactive students first
			getStudents('false', true);			
		}
		else
		{
			filterStudents(true);
		}
		
		// store the current status filter
		currentStatus = $scope.filters.status;
		
	}
	
	var filterStudents = function(clearTable)
	{
		if( $scope.dataGrid !== undefined && clearTable )
		{
		//	$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		
		// filter by class category
		// allStudents holds current students, formerStudents, the former...
		var filteredResults = ( $scope.filters.status == 'false' ? $scope.formerStudents : $scope.allStudents);
		
		
		if( $scope.filters.class_cat_id !== undefined && $scope.filters.class_cat_id !== null && $scope.filters.class_cat_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_cat_id.toString() == $scope.filters.class_cat_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}

		if( $scope.filters.class_id !== undefined && $scope.filters.class_id !== null && $scope.filters.class_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_id.toString() == $scope.filters.class_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}
		
		$scope.students = filteredResults;
		console.log($scope.students);
		
		$timeout(initDataGrid,100);
		
	
	}
	
	$scope.addStudent = function()
	{
		$scope.openModal('students', 'addStudent', 'lg');
	}
	
	$scope.viewStudent = function(student)
	{
		$scope.openModal('students', 'viewStudent', 'lg',student);
	}
	
	$scope.importStudents = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.exportData = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.$on('refreshStudents', function(event, args) {

		$scope.loading = true;
		$rootScope.loading = true;
		
		if( args !== undefined )
		{
			$scope.updated = true;
			$scope.notificationMsg = args.msg;
		}
		$scope.refresh();
		
		// wait a bit, then turn off the alert
		$timeout(function() { $scope.alert.expired = true;  }, 2000);
		$timeout(function() { 
			$scope.updated = false;
			$scope.notificationMsg = ''; 
			$scope.alert.expired = false;
		}, 3000);
	});
	
	$scope.refresh = function () 
	{
		$scope.loading = true;
		$rootScope.loading = true;
					
		getStudents(currentStatus,isFiltered);
	}
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.fixedHeader.destroy();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		$rootScope.isModal = false;
    });
	

} ]);