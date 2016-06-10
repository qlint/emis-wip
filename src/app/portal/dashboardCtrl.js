'use strict';

angular.module('eduwebApp').
controller('parentsDashboardCtrl', ['$scope', '$rootScope', 'apiService',
function($scope, $rootScope, apiService){

	$scope.studentsLoading = true;
	$scope.noticesLoading = true;
	$scope.newsLoading = true;
	

	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	var initializeController = function () 
	{
		$scope.students = $rootScope.currentUser.students;

		//getNotices();
		//getNews();

		
	}
	
	setTimeout(initializeController(),10);
	
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.fixedHeader.destroy();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		$rootScope.isModal = false;
    });
	

} ]);