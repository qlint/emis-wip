'use strict';

angular.module('eduwebApp').
controller('datesFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.edit = ( data !== undefined ? true : false );
	$scope.date = ( data !== undefined ? data : {} );
		
	$scope.initializeController = function()
	{
	
		if( !$scope.edit )
		{
			var currentDate = moment().format('YYYY-MM-DD');
			$scope.start_date = {startDate: currentDate};
			$scope.end_date = {startDate: currentDate};
		}		
		else
		{
			$scope.start_date = {startDate: $scope.date.start_date};
			$scope.end_date = {startDate: $scope.date.end_date};
		}
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.save = function(form)
	{
		if ( !form.$invalid ) 
		{
			var data = $scope.date;
			data.start_date = moment($scope.start_date.startDate).format('YYYY-MM-DD');
			data.end_date = moment($scope.end_date.startDate).format('YYYY-MM-DD');

			
			if( $scope.edit )
			{
				apiService.updateTerm(data,createCompleted,apiError);
			}
			else
			{
				apiService.addTerm(data,createCompleted,apiError);
			}
			
			
		}
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Term was updated.' : 'Term was added.');
			$rootScope.$emit('termAdded', {'msg' : msg, 'clear' : true});
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
	
	
} ]);