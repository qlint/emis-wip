'use strict';

angular.module('eduwebApp').
controller('previewPostCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'data',
function($scope, $rootScope, $uibModalInstance, data){

	console.log(data);
	$scope.post = data;		
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	
	
} ]);