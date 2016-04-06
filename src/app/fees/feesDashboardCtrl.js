'use strict';

angular.module('eduwebApp').
controller('feesDashboardCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

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
			$scope.payments = result.data;
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
	
	
} ]);