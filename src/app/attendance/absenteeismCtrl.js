'use strict';

angular.module('eduwebApp').
controller('absenteeismCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter',
function($scope, $rootScope, apiService, $timeout, $window, $filter){

	$scope.filters= {};
	$scope.alert = {};
	$scope.loading = true;

	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';

	var rowTemplate = function()
	{
		return '<div class="clickable" ng-click="grid.appScope.viewAbsenteeism(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}

	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Student', field: 'student_name', enableColumnMenu: false},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false},
			{ name: 'Start Date', field: 'start_date', type:'date', cellFilter:'date', enableColumnMenu: false,sort: {direction:'asc'}},
			{ name: 'End Date', field: 'end_date', type:'date', cellFilter:'date', enableColumnMenu: false,},
			{ name: 'Reason', field: 'reason', enableColumnMenu: false},
			{ name: 'Days', field: 'days_absent', enableColumnMenu: false},
		],
		exporterCsvFilename: 'absenteeism.csv',
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
		$scope.classes = [];
		apiService.getAllClasses({},
								function(response){
									var result = angular.fromJson(response);
									if( result.response == 'success'){ $scope.classes = result.data || []; }else{ console.log("No class data found"); return []; }
								},
								function(){ console.log("Error fetching data"); }
		);

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);

		getAbsenteeism(null);
	}
	$timeout(initializeController,1);

	var getAbsenteeism = function(classId)
	{
		// a null classId will return absenteeism for all classes
		classId = (classId == null || classId == '' ? undefined : classId);
		apiService.getAbsenteeism(classId, function(response,status,params){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.absentees = ( result.nodata ? [] : result.data );
				console.log($scope.absentees);
				initDataGrid($scope.absentees);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}

		}, apiError);
	}

	$scope.loadFilter = function()
	{
		$scope.loading = true;
		// getTerms($scope.filters.year);
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
		  [ 'student_name' ].forEach(function( field ){
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


	$scope.addAbsenteeism = function()
	{
		$scope.openModal('school', 'datesForm', 'md');
	}

	$scope.viewAbsenteeism = function(item)
	{
		$scope.openModal('school', 'datesForm', 'md',item);
	}

	$scope.exportItems = function()
	{
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
	}


	$scope.$on('refreshDates', function(event, args) {

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
		// getTerms($scope.filters.year);
	}

	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });

} ]);
