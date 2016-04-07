'use strict';

angular.module('eduwebApp').
controller('feesDashboardCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	var start_date = moment().subtract(30, 'days').format('YYYY-MM-DD HH:MM');
	var end_date = moment().format('YYYY-MM-DD HH:MM');
	$scope.date = {startDate: start_date, endDate: end_date};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	

	var initializeController = function () 
	{
		// get current term
		apiService.getCurrentTerm({},function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success') 
			{
				$scope.currentTerm = result.data;
				$scope.currentTermTitle = $scope.currentTerm.term_name + ' ' + $scope.currentTerm.year;
				
				getPaymentsReceived($scope.currentTerm.start_date, $scope.currentTerm.end_date);
				
			}
		},function(){});
		
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
		// get payments received for curren term
		var request = startDate + "/" + endDate;
		apiService.getPaymentsReceived(request, loadPaymentsReceived, apiError);
	}
	
	var loadPaymentsReceived = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success') 
		{
			$scope.paymentsReceived = result.data;
			$scope.paymentsReceivedTotal = 0;
			
			if( result.data  instanceof Array )
			{
				$scope.paymentsReceivedTotal = result.data.reduce(function(sum,item){
					return sum = (parseInt(sum) + parseInt(item.amount));
				},0);
			}
			
			setTimeout(function(){
				var settings = {
					table: 'paymentsReceivedTable',
					sortOrder: [1,'desc'],
					noResultsTxt: "No payments received were found for selected date range."
				}
				initDataGrid(settings);
				
			},100);
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
			
			if( result.data  instanceof Array )
			{
				$scope.paymentsDueTotal = result.data.reduce(function(sum,item){
					return sum = (parseInt(sum) + parseInt(item.amount));
				},0);
			}
			
			
			setTimeout(function(){
				var settings = {
					table: 'paymentsDueTable',
					sortOrder: [1,'desc'],
					noResultsTxt: "No unpaid invoices were found for this month."
				}
				initDataGrid(settings);
				
			},100);
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
			
			if( result.data  instanceof Array )
			{
				$scope.paymentsPastDueTotal = result.data.reduce(function(sum,item){
					return sum = (parseInt(sum) + parseInt(item.amount));
				},0);
			}
			
			
			setTimeout(function(){
				var settings = {
					table: 'paymentsPastDueTable',
					sortOrder: [1,'desc'],
					noResultsTxt: "No past invoices were found."
				}
				initDataGrid(settings);
				
			},100);
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
	
	
	var initDataGrid = function(settings)
	{
	
		var tableElement = $('#' + settings.table);
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
				order: settings.sortOrder,
				filter: true,
				info: false,
				sorting:[],
				scrollY:'200px',
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
					search: "",
					searchPlaceholder: "Filter",
					lengthMenu: "Display _MENU_",
					emptyTable: settings.noResultsTxt
				},
			} );
	}
	
	
	$scope.viewPayment = function(item)
	{
		$rootScope.modalLoading = true;
		apiService.getPaymentDetails(item.payment_id, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				$scope.openModal('fees', 'paymentForm', 'lg', result.data);
			}
		});
	}
	
	$scope.viewStudent = function(item)
	{
		$rootScope.modalLoading = true;
		apiService.getStudentDetails(student.student_id, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				var student = formatStudentData([result.data]);
				
				// set current class to full class object
				var currentClass = $rootScope.allClasses.filter(function(item){
					if( item.class_id == student[0].class_id ) return item;
				});
				
				student[0].current_class = currentClass[0];

				$scope.openModal('students', 'viewStudent', 'lg',student[0]);
			}
		});
	}	
	
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
} ]);