'use strict';

angular.module('eduwebApp').
controller('openingBalancesCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;	
	$rootScope.modalLoading = false;
	$scope.alert = null;
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.totals = {};
	$scope.loading = true;
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';
	
	$scope.years = [];
	var currentYear = moment().format('YYYY');
	var startYear = ( $rootScope.currentUser.settings['Initial Year'] !== undefined ? $rootScope.currentUser.settings['Initial Year'] : '2014');
	var diff = currentYear - startYear;
	for(var i=startYear; i<=currentYear; i++)
	{
		$scope.years.push(i);
	}
	$scope.filters.year = currentYear;
	var lastQueriedYear = currentYear;
	var requery = false;
	
	var rowTemplate = function() 
	{
		return '<div class="clickable" ng-class="{\'alert-danger\': row.entity.days_overdue > 0}" ng-click="grid.appScope.viewStudent(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	var names = ['Opening Balance ( ' + $scope.currency + ' )', 'Payments Received ( ' + $scope.currency + ' )', 'Balance ( ' + $scope.currency + ' )'];
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Name', field: 'student_name', enableColumnMenu: false,},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false,},
			{ name: names[0], field: 'total_due', enableColumnMenu: false, type:'number', cellTemplate:'<div class="ui-grid-cell-contents">{{row.entity.total_due|currency:""}}</div>'},
			{ name: names[1], field: 'total_paid', enableColumnMenu: false, type:'number', cellTemplate:'<div class="ui-grid-cell-contents">{{row.entity.total_paid|currency:""}}</div>'},
			{ name: names[2], field: 'balance', enableColumnMenu: false, type:'number', sort: {direction: 'asc', priority: 1}, cellTemplate:'<div class="ui-grid-cell-contents">{{row.entity.balance|numeric}}</div>'},
			{ name: 'Last Payment', field: 'last_payment', enableColumnMenu: false},
			{ name: 'Next Payment Due', field: 'next_payment', enableColumnMenu: false},
		],
		exporterCsvFilename: 'opening-balances.csv',
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
		// get classes
		if( $rootScope.allClasses === undefined )
		{
			apiService.getAllClasses({}, function(response){
				var result = angular.fromJson(response);
				
				// store these as they do not change often
				if( result.response == 'success') 
				{
					//$rootScope.allClasses = ;
					$scope.classes = result.data;
				}
				
			}, function(){});
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
		}
		
		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);
		
		getStudentBalances('true',false);
	}
	$timeout(initializeController,1);
	
	var getStudentBalances = function(status, filtering)
	{		
		
		var year = angular.copy($scope.filters.year);
		var request =  year + '/' + status;
		apiService.getStudentBalances(request, function(response,status,params){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{	
				if(result.nodata !== undefined )
				{
					$scope.students = [];
				}
				else
				{
					lastQueriedYear = params.year;
					var formatedResults = result.data;
						
					if( filtering )
					{
						$scope.formerStudents = formatedResults
						filterResults();
					}
					else
					{
						$scope.allStudents = formatedResults;
						$scope.students = formatedResults;
					}

				}
				initDataGrid($scope.students);
				
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, function(){}, {year:year});
	}
	
	var calcTotals = function()
	{
		$scope.totals.total_due = $scope.students.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.total_due));
		},0);
		
		$scope.totals.total_paid = $scope.students.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.total_paid));
		},0);
		
		$scope.totals.total_balance = $scope.students.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.balance));
		},0);
	}
	
	var initDataGrid = function(data) 
	{
		// updating datagrid, also update totals
		calcTotals();
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
		  [ 'student_name', 'class_name' ].forEach(function( field ){
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
		}
	});
	
	$scope.$watch('filters.year', function(newVal,oldVal){
		if(newVal == oldVal) return;
		if( newVal !== lastQueriedYear ) requery = true;
		else requery = false;
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
			getStudentBalances('false', true);			
		}
		else if( requery )
		{
			// need to get fresh data, most likely because the user selected a new year
			getStudentBalances(currentStatus, true);		
		}
		else
		{
			// otherwise we have all we need, just filter it down 
			filterResults();
		}
		
		// store the current status filter
		currentStatus = $scope.filters.status;
		
	}
	
	var filterResults = function()
	{		
		// filter by class category
		// allStudents holds current students, formerStudents, the former...
		var filteredResults = ( $scope.filters.status == 'false' ? $scope.formerStudents : $scope.allStudents);
		
		
		if( $scope.filters.class_cat_id !== undefined && $scope.filters.class_cat_id !== null && $scope.filters.class_cat_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_cat_id.toString() == $scope.filters.class_cat_id.toString()  ) sum.push(item);
			  return sum;
			}, []);
		}
		
		if( $scope.filters.class_id !== undefined && $scope.filters.class_id !== null  && $scope.filters.class_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_id.toString() == $scope.filters.class_id.toString()  ) sum.push(item);
			  return sum;
			}, []);
		}
		
		$scope.students = filteredResults;
		initDataGrid($scope.students);
	}
	
	$scope.addPayment = function()
	{
		$scope.openModal('fees', 'paymentForm', 'lg',{});
	}
	
	$scope.adjustPayment = function()
	{
		$scope.openModal('fees', 'editPaymentForm', 'lg',{});
	}
	
	$scope.viewStudent = function(student)
	{
		$scope.openModal('students', 'viewStudent', 'lg',student);
	}
	
	$scope.exportBalances = function()
	{
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
	}
	
	$scope.$on('refreshBalances', function(event, args) {

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
		getStudentBalances(currentStatus,isFiltered);
	}
	
	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });

} ]);