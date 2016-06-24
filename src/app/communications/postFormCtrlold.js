'use strict';

angular.module('eduwebApp').
controller('postFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader','$timeout', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, $timeout, data){

	$scope.edit = ( data.edit !== undefined ? data.edit : false );
	$scope.classes = data.classes;
	$scope.selectedClass = data.selectedClass;
	
	$scope.post = ( data.post !== undefined ? data.post : {} );
		
	var initializeController = function()
	{
		$scope.bodyContent = 'test';
		/*
		if( !$scope.edit )
		{
			var currentDate = moment().format('YYYY-MM-DD');
			$scope.post.post_date = {startDate: currentDate};
		}		
		else
		{
			$scope.post.post_date = {startDate: $scope.post.start_date};
		}
		*/
	}
	$timeout(initializeController,100);
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.setClass = function()
	{
		$scope.classSelected = true;
		$scope.setupBlog = ( $scope.selectedClass.blog_id === null ? true : false );
	}
	
	$scope.preview = function()
	{
		
	}
	
	$scope.saveDraft = function(form)
	{
		$scope.post.post_status_id = 2; // TO DO: fix this 
		$scope.save(form);
	}
	
	$scope.publish = function(form)
	{
		$scope.post.post_status_id = 1; // TO DO: fix this 
		$scope.save(form);
	}
	
	
	$scope.save = function(form)
	{
		if ( !form.$invalid ) 
		{

			if( $scope.setupBlog )
			{
				apiService.addBlog(data,function(response, status){
					var result = angular.fromJson( response );
					if( result.response == 'success' )
					{
						$scope.setupBlog = false;
						$scope.post.blog_id = result.data;
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
				}, apiError);
			}
			else
			{
				if( uploader.queue[0] !== undefined )
				{
					// need a unique filename
					uploader.queue[0].file.name = moment() + '_' + uploader.queue[0].file.name;
					uploader.uploadAll();
					
					$scope.post.feature_image = ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null);
				}

				$scope.post.body = $scope.bodyContent;

				var data = {
					user_id: $scope.currentUser.user_id,
					blog_id: $scope.selectedClass.blog_id,
					post: $scope.post
				}
			//	data.start_date = moment($scope.start_date.startDate).format('YYYY-MM-DD');

				if( $scope.edit )
				{
					apiService.updatePost(data,createCompleted,apiError);
				}
				else
				{
					apiService.addPost(data,createCompleted,apiError);
				}				
			}
		}
	}
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'students'
			}]
    });
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Post was updated.' : 'Post was added.');
			$rootScope.$emit('postAdded', {'msg' : msg, 'clear' : true});
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