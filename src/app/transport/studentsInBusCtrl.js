'use strict';

angular.module('eduwebApp').
controller('studentsInBusCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window){

    $scope.bus = data;
    
	var school = window.location.host.split('.')[0];
	$scope.showLoader = false;
	$scope.showPreassigned = true;

	$scope.isTeacher = ($rootScope.currentUser.user_type == 'TEACHER' ? true : false);
    $scope.showTable = false;

	var initializeController = function()
	{
	    // fetch students in the bus
	    apiService.getStudentsInBus($scope.bus.bus_id, function(response,status)
        {
            var result = angular.fromJson(response);
            if( result.response == 'success')
        	{
        		$scope.studentsInBus = ( result.nodata ? {} : result.data );
        		console.log("Students in the bus",$scope.studentsInBus);
        		$scope.showTable = true;
        	}
        	else
        	{
        		$scope.error = true;
        		$scope.errMsg = result.data;
        	}
        
        }, apiError);

	}
	setTimeout(initializeController,100);

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel


	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
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
