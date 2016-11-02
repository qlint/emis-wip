'use strict';

angular.module('eduwebApp').
controller('feesDashboardCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	//var start_date = moment().subtract(30, 'days').format('YYYY-MM-DD');
	//var end_date = moment().add(1,'day').format('YYYY-MM-DD');
	//$scope.date = {startDate: start_date, endDate: end_date};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.paymentsLoading = true;
	$scope.invoicesLoading = true;
	$scope.pastDueLoading = true;
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue1 = '';
	$scope.gridFilter.filterValue2 = '';
	$scope.gridFilter.filterValue3 = '';
	
	var rowTemplate1 = function() 
	{
		return '<div class="clickable" ng-click="grid.appScope.viewPayment(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	var rowTemplate2 = function() 
	{
		return '<div class="clickable" ng-click="grid.appScope.viewStudent(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}

	var names = ['Amt ( ' + $scope.currency + ' )', 'Balance ( ' + $scope.currency + ' )'];
	$scope.gridOptions1 = {
		enableSorting: true,
		rowTemplate: rowTemplate1(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Student', field: 'student_name', enableColumnMenu: false, sort: {direction: 'asc'} },
			{ name: names[0], field: 'amount', cellFilter:'currency:""', enableColumnMenu: false },
			{ name: 'Date', field: 'payment_date', type:'date', cellFilter:'date', enableColumnMenu: false },
		],
		onRegisterApi: function(gridApi){
		  $scope.gridApi1 = gridApi;
		  $scope.gridApi1.grid.registerRowsProcessor( $scope.singleFilter1, 200 );
		}
	};
	
	$scope.gridOptions2 = {
		enableSorting: true,
		rowTemplate: rowTemplate2(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Student', field: 'student_name', enableColumnMenu: false, sort: {direction: 'asc'} },
			{ name: names[1], field: 'balance', cellFilter:'numeric', enableColumnMenu: false },
			{ name: 'Due Date', field: 'due_date', type:'date', cellFilter:'date', enableColumnMenu: false },
		],
		onRegisterApi: function(gridApi){
		  $scope.gridApi2 = gridApi;
		  $scope.gridApi2.grid.registerRowsProcessor( $scope.singleFilter2, 200 );
		}
	};
	
	$scope.gridOptions3 = {
		enableSorting: true,
		rowTemplate: rowTemplate2(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Student', field: 'student_name', enableColumnMenu: false, sort: {direction: 'asc'} },
			{ name: names[1], field: 'balance', cellFilter:'numeric', enableColumnMenu: false },
			{ name: 'Due Date', field: 'due_date', type:'date', cellFilter:'date', enableColumnMenu: false },
		],
		onRegisterApi: function(gridApi){
		  $scope.gridApi3 = gridApi;
		  $scope.gridApi3.grid.registerRowsProcessor( $scope.singleFilter3, 200 );
		}
	};
	
	var initializeController = function () 
	{
		/*
		// get current term
		apiService.getCurrentTerm({},function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success') 
			{
				$scope.currentTerm = result.data;
				$scope.currentTermTitle = $scope.currentTerm.term_name + ' ' + $scope.currentTerm.year;
				//var end_date = moment().add(1,'day').format('YYYY-MM-DD');
				$scope.date = {startDate: $scope.currentTerm.start_date, endDate: $scope.currentTerm.end_date};
				getPaymentsReceived($scope.currentTerm.start_date, $scope.currentTerm.end_date);
				
			}
		},apiError);
		*/
		
		// get terms
		var year = moment().format('YYYY');
		apiService.getTerms(undefined, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{ 
				$rootScope.terms = result.data;
				$rootScope.setTermRanges(result.data);
				
				$scope.currentTerm = result.data.filter(function(item){
					if( item.current_term ) return item;
				})[0];
				$scope.currentTermTitle = $scope.currentTerm.term_name + ' ' + $scope.currentTerm.year;
				$scope.date = {startDate: $scope.currentTerm.start_date, endDate: $scope.currentTerm.end_date};
				getPaymentsReceived($scope.currentTerm.start_date, $scope.currentTerm.end_date);
			}
		}, function(){});
		
		// get payments due this month
		var start_date = moment().startOf('month').format('YYYY-MM-DD');
		var end_date = moment().endOf('month').format('YYYY-MM-DD');
		getPaymentsDue(start_date, end_date);
		
		getOverDuePayments();
		getTotalsForTerm();
	}
	$timeout(initializeController,1);
	
	var getPaymentsReceived = function(startDate, endDate)
	{
		// get payments received for current term, that has not been reversed
		var request = startDate + "/" + endDate + "/false";
		apiService.getPaymentsReceived(request, loadPaymentsReceived, apiError);
	}
	
	var loadPaymentsReceived = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success') 
		{
			$scope.paymentsReceived = result.data;
			$scope.paymentsReceivedTotal = 0;
			$scope.paymentsLoading = false;
			
			if( result.data  instanceof Array )
			{
				$scope.paymentsReceivedTotal = result.data.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.amount));
				},0);
			}
			
			$scope.gridOptions1.data = $scope.paymentsReceived;

			
			/*
			setTimeout(function(){
				var settings = {
					table: 'paymentsReceivedTable',
					sortOrder: [1,'desc'],
					noResultsTxt: "No payments received were found for selected date range."
				}
				initDataGrid(settings);
				
			},100
			*/
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
		}
	}
	
	var getPaymentsDue = function(startDate, endDate)
	{
		// get payments received for curren term
		var request = startDate + "/" + endDate;
		apiService.getPaymentsDue(request, loadPaymentsDue, apiError);
	}
	
	var loadPaymentsDue = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success') 
		{
			$scope.paymentsDue = result.data;
			$scope.paymentsDueTotal = 0; 
			$scope.invoicesLoading = false;
			
			if( result.data  instanceof Array )
			{
				$scope.paymentsDueTotal = result.data.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.balance));
				},0);
			}
			
			$scope.gridOptions2.data = $scope.paymentsDue;
			/*
			setTimeout(function(){
				var settings = {
					table: 'paymentsDueTable',
					sortOrder: [1,'desc'],
					noResultsTxt: "No unpaid invoices were found for this month."
				}
				initDataGrid(settings);
				
			},100);
			*/
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
		}
	}
	
	var getOverDuePayments = function()
	{
		apiService.getPaymentsPastDue({}, loadPaymentsPastDue, apiError);
	}
	
	var loadPaymentsPastDue = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success') 
		{
			$scope.paymentsPastDue = result.data;
			$scope.paymentsPastDueTotal = 0; 
			$scope.pastDueLoading = false;
			
			if( result.data instanceof Array )
			{
				$scope.paymentsPastDueTotal = result.data.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.balance));
				},0);
			}
			
			$scope.gridOptions3.data = $scope.paymentsPastDue;
			
			/*
			setTimeout(function(){
				var settings = {
					table: 'paymentsPastDueTable',
					sortOrder: [1,'desc'],
					noResultsTxt: "No past invoices were found."
				}
				initDataGrid(settings);
				
			},100);
			*/
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
		}
	}
	
	var getTotalsForTerm = function()
	{
		apiService.getTotalsForTerm({}, loadTotals, apiError);
	}
	
	var loadTotals = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success') 
		{
			$scope.totals = result.data;
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
		}
	}
	
	$scope.filterDataTable = function(grid) 
	{
		switch(grid){
			case "1":
				$scope.gridApi1.grid.refresh();
				break;
			case "2":
				$scope.gridApi2.grid.refresh();
				break;
			case "3":
				$scope.gridApi3.grid.refresh();
				break;
		}
	};
	
	$scope.clearFilterDataTable = function(grid) 
	{
		switch(grid){
			case "1":
				$scope.gridFilter.filterValue1 = '';
				$scope.gridApi1.grid.refresh();
				break;
			case "2":
				$scope.gridFilter.filterValue2 = '';
				$scope.gridApi2.grid.refresh();
				break;
			case "3":
				$scope.gridFilter.filterValue3 = '';
				$scope.gridApi3.grid.refresh();
				break;
		}
		
		
	};
	
	$scope.singleFilter1 = function( renderableRows )
	{
		var matcher = new RegExp($scope.gridFilter.filterValue1, 'i');
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
	}
	$scope.singleFilter2 = function( renderableRows )
	{
		var matcher = new RegExp($scope.gridFilter.filterValue2, 'i');
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
	}
	$scope.singleFilter3 = function( renderableRows )
	{
		var matcher = new RegExp($scope.gridFilter.filterValue3, 'i');
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
	}
	
	
	$scope.viewPayment = function(item)
	{
		$scope.openModal('fees', 'paymentDetails', 'lg', item);
	}
	
	$scope.viewStudent = function(student)
	{
		var data = {
			student: student
		}
		$scope.openModal('students', 'viewStudent', 'lg',data);
	}	
	
	
	$scope.addPayment = function()
	{
		$scope.openModal('fees', 'paymentForm', 'lg',{});
	}
	
	$scope.adjustPayment = function()
	{
		$scope.openModal('fees', 'paymentDetails', 'lg',{});
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
} ]);