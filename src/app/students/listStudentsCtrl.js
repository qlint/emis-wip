'use strict';

angular.module('eduwebApp').
controller('listStudentsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.status = 'true';
	

	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;	
	$scope.loading = true;
	$rootScope.modalLoading = false;
	$scope.alert = {};
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';
	
	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false);
	
	var rowTemplate = function() 
	{
		return '<div class="clickable" ng-class="{\'alert-danger\': row.entity.balance > 0, \'alert-success\' : row.entity.balance == 0}" ng-click="grid.appScope.viewStudent(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Name', field: 'student_name', enableColumnMenu: false, sort: {direction:'asc'}},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false,},
			{ name: 'Admission Number', field: 'admission_number', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'students.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};
	
	var initializeController = function () 
	{
		// if user is a teacher, we only want to give them class categories and classes that they are associated with
		if ( $scope.isTeacher )
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
								//$rootScope.allClasses = result.data;
								$scope.classes = result.data;

								getStudents('true',false );
								
								if( $state.params.class_cat_id !== null )
								{
									$scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
									$scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
									$scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
									$scope.filterClass = ( $state.params.class_id !== '' ? true : false );
								}
								else
								{
									if( $rootScope.classCats.length == 1 ) $scope.filters.class_cat_id = $rootScope.classCats[0].class_cat_id;
								}
								
							}
							
						}, apiError);
					}
					else
					{
						$scope.classes = $rootScope.allClasses;
						getStudents('true',false);

						if( $state.params.class_cat_id !== null )
						{
							$scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
							$scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
							$scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
							$scope.filterClass = ( $state.params.class_id !== '' ? true : false );

						}
						else
						{
							if( $rootScope.classCats.length == 1 ) $scope.filters.class_cat_id = $rootScope.classCats[0].class_cat_id;
						}
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
					//	$rootScope.allClasses = ;
						$scope.classes = result.data;

						getStudents('true',false );
						
						$scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
						$scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
						$scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
						$scope.filterClass = ( $state.params.class_id !== '' ? true : false );
					}
					
				}, apiError);
			}
			else
			{
				$scope.classes = $rootScope.allClasses;
				
				$scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
				$scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
				$scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
				$scope.filterClass = ( $state.params.class_id !== '' ? true : false );

							
				getStudents('true',false);
			}
		}
		
		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);

	}
	$timeout(initializeController,1);
	
	var getStudents = function(status, filtering)
	{		
		if ( $scope.isTeacher )
		{
			var params = $rootScope.currentUser.emp_id + '/' + status;
			apiService.getTeacherStudents(params, loadStudents, apiError, {filtering:filtering});
		}
		else
		{
			apiService.getAllStudents(status, loadStudents, apiError, {filtering:filtering,status:status});
		}
		
	}
	
	var loadStudents = function(response,status, params)
	{
		var result = angular.fromJson(response);			
		
		if( result.response == 'success')
		{
	
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
				
				if( $scope.filterClassCat || $scope.filterClass ) filterStudents();
				initDataGrid($scope.students);
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
		
	var initDataGrid = function( data ) 
	{
	
		$scope.gridOptions.data = $scope.students;
		$scope.loading = false;
		$rootScope.loading = false;
		
	}
	
	$scope.filterDataTable = function() 
	{
		$scope.gridApi.grid.refresh();
	};
	
	$scope.clearFilterDataTable = function() 
	{
		$scope.gridFilter.filterValue = '';
		$scope.gridApi.grid.refresh();
	};
	
	$scope.singleFilter = function( renderableRows )
	{
		var matcher = new RegExp($scope.gridFilter.filterValue, 'i');
		renderableRows.forEach( function( row ) {
		  var match = false;
		  [ 'student_name', 'class_name', 'admission_number' ].forEach(function( field ){
			if ( row.entity[field].match(matcher) ){
			  match = true;
			}
		  });
		  if ( !match ){
			row.visible = false;
		  }
		});
		return renderableRows;
	};
	/*
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
	*/
	$scope.$watch('filters.class_cat_id', function(newVal,oldVal){
		if (oldVal == newVal) return;

		if( newVal === undefined || newVal === null || newVal == '' ) 	$scope.classes = $rootScope.allClasses;
		else
		{	
			// filter classes to only show those belonging to the selected class category
			$scope.classes = $rootScope.allClasses.reduce(function(sum,item){
				if( item.class_cat_id == newVal ) sum.push(item);
				return sum;
			}, []);
			//$timeout(setSearchBoxPosition,10);
			
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
		
		initDataGrid($scope.students);
		
	
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
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
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
		$rootScope.isModal = false;
    });
	

} ]);