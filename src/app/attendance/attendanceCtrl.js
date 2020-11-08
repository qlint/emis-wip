'use strict';

angular.module('eduwebApp').
controller('attendanceCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','FileUploader','dialogs',
function($scope, $rootScope, apiService, $timeout, $window, $filter, FileUploader, $dialogs){

	var initializeController = function ()
	{
		//
	}
	$timeout(initializeController,1);

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		var msg = ( result.data.indexOf('"U_active_emp_cat"') > -1 ? 'The Employee Category name you entered already exists.' : result.data);
		$scope.errMsg = msg;
	}

} ]);
