'use strict';

angular.module('eduwebApp').
controller('blogCtrl', ['$scope', '$rootScope', 'apiService', '$state',
function($scope, $rootScope, apiService, $state){
	
	$scope.blogTitle = '';
	$scope.week = moment().startOf('isoweek').format('MMM Do') + ' - ' + moment().endOf('isoweek').format('MMM Do');
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	var initializeController = function () 
	{
		// get active student details
		$scope.student = $rootScope.currentUser.students.filter(function(item){
			if( item.school == $state.params.school && item.student_id == $state.params.student_id ) return item;
		})[0];

		
		// get blog		
		apiService.getBlog($rootScope.activeStudent, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{
				if( result.nodata !== undefined )
				{
					$scope.noPosts = true;
					$scope.blogTitle = $scope.student.class_name + ' Blog';
				}
				else
				{
					$scope.noPosts = false;
					$scope.posts = result.data;
					$scope.blogTitle = $scope.posts[0].blog_name;
					
				}
			}
		}, apiError);
		
		// get homework for current week
		apiService.getHomework($rootScope.activeStudent, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{
				if( result.nodata !== undefined )
				{
					$scope.noHomework = true;
				}
				else
				{
					$scope.noHomework = false;
					$scope.homework = result.data.map(function(item){
						item.date_day = moment(item.homework_date).format('D');
						item.date_dow = moment(item.homework_date).format('dddd');
						return item;
					});
				}
			}
		}, apiError);
	}
	
	setTimeout(initializeController(),10);
	
	
	$scope.$on('$destroy', function() {
		if($scope.datagrid) $scope.datagrid.destroy();
		$rootScope.isModal = false;
    });
	

} ]);