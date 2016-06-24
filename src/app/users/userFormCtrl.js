'use strict';

angular.module('eduwebApp').
controller('userFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.edit = ( data !== undefined ? true : false );
	$scope.user = data || {};
	
	$scope.initializeController = function()
	{
		
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
			var data = $scope.user;
			data.current_user_id = $rootScope.currentUser.user_id;
			
			if( $scope.edit )
			{
				apiService.updateUser(data,createCompleted,apiError);
			}
			else
			{
				apiService.addUser(data,createCompleted,apiError);
			}
			
			
		}
	}
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'User was updated.' : 'User was added.');
			$rootScope.$emit('userAdded', {'msg' : msg, 'clear' : true});
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
	
	$scope.deleteUser = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to mark this user as deleted? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				current_user_id : $rootScope.currentUser.user_id,
				user_id: $scope.user.user_id,
				status: 'f'
			}
			apiService.setUserStatus(data,createCompleted,apiError);

		});
	}
	
	$scope.activateUser = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to re-activate this user? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				current_user_id : $rootScope.currentUser.user_id,
				user_id: $scope.user.user_id,
				status: 't'
			}
			apiService.setUserStatus(data,createCompleted,apiError);

		});
	}
	
	
} ]);