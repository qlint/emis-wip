'use strict';

angular.module('eduwebApp').
controller('dashboardCtrl', ['$scope', '$rootScope', 'apiService',
function($scope, $rootScope, apiService){

	
	$scope.initializeController = function () 
	{
		
	}
	
	$scope.initializeController();
	
	
	
	
	$scope.$on('$destroy', function() {
		if($scope.journeysGrid) $scope.journeysGrid.destroy();
		$rootScope.isModal = false;
    });
	

} ]);