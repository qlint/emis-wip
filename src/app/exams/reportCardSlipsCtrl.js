'use strict';

angular.module('eduwebApp').
controller('reportCardSlipsCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window','$parse', '$compile',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window, $parse, $compile){

	console.log(data);
	$scope.studentsLength = data.length;
	$scope.switchToClass = ( window.location.host.split('.')[0] == 'thomasburke' ? 'Class' : 'Stream' );
	$scope.switchToStream = ( window.location.host.split('.')[0] == 'thomasburke' ? 'Stream' : 'Class' );
	data.forEach(function(stdnt) {
		stdnt.pos_out_of = $scope.studentsLength;
	});
	$scope.studentSlips = data;

	var initializeController = function()
	{

	}
	// initializeController();

	$scope.print = function()
	{
		//
	}

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

} ]);
