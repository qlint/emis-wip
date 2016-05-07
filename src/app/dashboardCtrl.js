'use strict';

angular.module('eduwebApp').
controller('dashboardCtrl', ['$scope', '$rootScope', 'apiService',
function($scope, $rootScope, apiService){


	var getStudentCount = function()
	{
		apiService.getClassCatsSummary(status, function(response){
			
			var result = angular.fromJson(response);			
			
			if( result.response == 'success')
			{
				$scope.classCats = result.data;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, function(){});
	}
	
	var getStaffCount = function()
	{
		apiService.getDeptSummary(status, function(response){
			
			var result = angular.fromJson(response);			
			
			if( result.response == 'success')
			{
				$scope.deptCats = result.data;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, function(){});
	}
	
	var getFeeSummary = function()
	{
		// get current term
		apiService.getCurrentTerm({},function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success') 
			{
				$scope.currentTerm = result.data;
				$scope.currentTermTitle = $scope.currentTerm.term_name + ' ' + $scope.currentTerm.year;
				var end_date = moment().add(1,'day').format('YYYY-MM-DD');
				$scope.date = {startDate: $scope.currentTerm.start_date, endDate: end_date};
				getPaymentsReceived($scope.currentTerm.start_date, end_date);
				
			}
		},function(){});
		
		// get payments due this month
		var start_date = moment().startOf('month').format('YYYY-MM-DD');
		var end_date = moment().endOf('month').format('YYYY-MM-DD');
		getPaymentsDue(start_date, end_date);
		
		getOverDuePayments();
	}
	
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
			$scope.numPaymentsReceived = ( result.nodata !== undefined ? 0 : result.data.length);			
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
			$scope.numPaymentsDue = ( result.nodata !== undefined ? 0 : result.data.length);			
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
			$scope.numPaymentsPastDue = ( result.nodata !== undefined ? 0 : result.data.length);
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
		}
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	var initializeController = function () 
	{
		getStudentCount();
		getStaffCount();
		getFeeSummary();
	}
	
	setTimeout(initializeController(),10);
	
	
	$scope.$on('$destroy', function() {
		if($scope.journeysGrid) $scope.journeysGrid.destroy();
		$rootScope.isModal = false;
    });
	

} ]);