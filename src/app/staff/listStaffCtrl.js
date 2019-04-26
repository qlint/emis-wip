'use strict';

angular.module('eduwebApp').
controller('listStaffCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.employees = [];
	$scope.loading = true;
	
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filters.emp_cat_id = ( $state.params.category !== '' ? $state.params.category : null );
	$scope.filterEmpCat = ( $state.params.category !== '' ? true : false );
	$scope.filters.dept_id = ( $state.params.dept !== '' ? $state.params.dept : null );
	$scope.filterDept = ( $state.params.dept !== '' ? true : false );
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';
	
	/* get full employee cat record from state param */
	if( $state.params.category !== null )
	{
		$scope.filters.emp_cat = $rootScope.empCats.filter(function(item){
			if( item.emp_cat_id == $state.params.category ) return item;
		})[0];
	}
	
	$scope.alert = {};
	
	var rowTemplate = function() 
	{
		return '<div class="clickable" ng-click="grid.appScope.viewEmployee(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Name', field: 'employee_name', enableColumnMenu: false, sort: {direction:'asc'},},
			{ name: 'Category', field: 'emp_cat_name', enableColumnMenu: false,},
			{ name: 'Department', field: 'dept_name', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'staff.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};
	
	var getStaff = function()
	{		
		apiService.getAllEmployees(true, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{		
				$scope.allEmployees = (result.nondata !== undefined ? [] : result.data);	
				$scope.employees = $scope.allEmployees ;
				
				// if filters set, filter results
				if( $scope.currentFilters !== undefined || $scope.filterEmpCat || $scope.filterDept  )
				{
					filterResults(false);
				}
				else
				{
					initDataGrid($scope.employees);
				}
				
				
			}
			else
			{
				initDataGrid($scope.employees);
			}
			
		}, function(){});
	}
	
	var initializeController = function () 
	{
		// get staff
		$scope.departments = $rootScope.allDepts;
		getStaff()		

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);		
	}
	$timeout(initializeController,1000);
	
	$scope.$watch('filters.emp_cat', function(newVal,oldVal){
		if (oldVal == newVal) return;

		if( newVal === undefined || newVal === null || newVal == '' ) 	$scope.departments = $rootScope.allDepts;
		else
		{	
			// filter dept to only show those belonging to the selected category
			$scope.departments = $rootScope.allDepts.reduce(function(sum,item){
				if( item.category == newVal.emp_cat_name ) sum.push(item);
				return sum;
			}, []);
			$scope.filters.emp_cat_id = newVal.emp_cat_id;
			$timeout(setSearchBoxPosition,10);
		}
	});
	
	var initDataGrid = function(data) 
	{		
		$scope.gridOptions.data = data;
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
		  [ 'employee_name', 'emp_cat_name', 'dept_name' ].forEach(function( field ){
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
		
	$scope.filter = function()
	{
		$scope.currentFilters = angular.copy($scope.filters);
		console.log($scope.filters.status);
		
		apiService.getAllEmployees($scope.filters.status, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{		
				$scope.allEmployees = (result.nondata !== undefined ? [] : result.data);	
				$scope.employees = $scope.allEmployees ;
				
				// if filters set, filter results
				if( $scope.currentFilters !== undefined || $scope.filterEmpCat || $scope.filterDept  )
				{
					filterResults(false);
				}
				else
				{
					initDataGrid($scope.employees);
				}
				
				
			}
			else
			{
				initDataGrid($scope.employees);
			}
			
		}, function(){});
		
		filterResults(true);
	}
	
	var filterResults = function(clearTable)
	{
		$scope.loading = true;
		
		// filter by emp category
		var filteredResults = $scope.allEmployees;
		
		
		if( $scope.filters.emp_cat_id !== undefined && $scope.filters.emp_cat_id !== null && $scope.filters.emp_cat_id != ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.emp_cat_id.toString() == $scope.filters.emp_cat_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}

		
		if( $scope.filters.dept_id !== undefined && $scope.filters.dept_id !== null && $scope.filters.dept_id != '' )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.dept_id.toString() == $scope.filters.dept_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}
		
		$scope.employees = filteredResults;
		initDataGrid($scope.employees);
	}
	
	$scope.addEmployee = function()
	{
		$scope.openModal('staff', 'addEmployee', 'lg');
	}
	
	$scope.viewEmployee = function(item)
	{
		$scope.openModal('staff', 'viewEmployee', 'lg', item);
	}
	
	$scope.exportData = function()
	{
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
	}
	
	$scope.$on('refreshStaff', function(event, args) {

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
		getStaff();
	}
	
	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });
	

} ]);