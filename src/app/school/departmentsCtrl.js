'use strict';

angular.module('eduwebApp').
controller('departmentsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter',
function($scope, $rootScope, apiService, $timeout, $window, $filter){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.alert = {};
	$scope.loading = true;
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';
	
	var rowTemplate = function() 
	{
		return '<div class="clickable" ng-click="grid.appScope.viewDepartment(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Category', field: 'category', enableColumnMenu: false, sort: {direction:'asc'},},
			{ name: 'Department Name', field: 'dept_name', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'school-departments.csv',
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
		getDepartments();
		
		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);
		
	}
	$timeout(initializeController,1);

	var getDepartments = function()
	{	

		apiService.getDepts($scope.filters.status, function(response,status,params){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.departments = ( result.nodata ? [] : result.data );	

				// update the rootScope variable
				$rootScope.allDepts = $scope.departments;
				
				initDataGrid($scope.departments);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, apiError);
	}
		
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
		  [ 'category', 'dept_name' ].forEach(function( field ){
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
	
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		getDepartments();		
	}
	
	$scope.addDept = function()
	{
		$scope.openModal('school', 'departmentForm', 'md');
	}
	
	$scope.viewDepartment = function(item)
	{
		var data = {
			department: item
		};
		$scope.openModal('school', 'departmentForm', 'md',data);
	}
	
	$scope.exportItems = function()
	{
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
	}
	

	$scope.$on('refreshDepartments', function(event, args) {

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
		getDepartments();
	}
	
	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });

} ]);