'use strict';

angular.module('eduwebApp').
controller('paymentFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.student = (data.student !== undefined ? data.student : null);
	

	$scope.initializeController = function()
	{
	
	}
	
	
} ]);