'use strict';

angular.module('eduwebApp').
controller('listUsersCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.users = [];
	$scope.loading = true;

	$scope.alert = {};
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';
	
	var rowTemplate = function() 
	{
		return '<div class="clickable" ng-click="grid.appScope.viewUser(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Name', field: 'user_name', enableColumnMenu: false, sort: {direction:'asc'},},
			{ name: 'Username', field: 'username', enableColumnMenu: false,},
			{ name: 'User Type', field: 'user_type', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'users.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};
	
	var getUsers = function()
	{
		apiService.getUsers($scope.filters.status, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{
				$scope.users = (result.nondata !== undefined ? [] : result.data);	
				initDataGrid($scope.users);
				
			}
			else
			{
			}
			
		}, function(){});
	}
	
	var initializeController = function () 
	{
		// get users
		getUsers()		

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);		
	}
	$timeout(initializeController,1000);
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		getUsers();		
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
		  [ 'user_name', 'username', 'user_type' ].forEach(function( field ){
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
	
	
	$scope.addUser = function()
	{
		$scope.openModal('users', 'userForm', 'md');
	}
	
	$scope.viewUser = function(item)
	{
		$scope.openModal('users', 'userForm', 'md', item);
	}
	
	$scope.exportData = function()
	{
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
	}
	
	$scope.$on('refreshUsers', function(event, args) {

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
		getUsers();
	}
	
	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });
	

} ]);