'use strict';

angular.module('eduwebApp').
controller('postFormCtrl', ['$scope', '$rootScope', 'apiService', 'dialogs', 'FileUploader','$timeout','$state',
function($scope, $rootScope, apiService, $dialogs, FileUploader, $timeout, $state){
	
	console.log($state.params);
	$scope.edit = ( $state.params.action !== undefined && $state.params.action == 'edit' ? true : false );	
	$scope.post = ( $state.params.post !== undefined ? $state.params.post : {} );
	$scope.filters = {};
	$scope.loadingPost = true;
	$scope.alert = {};
	$scope.editingBlogName = false;
		
	var initializeController = function()
	{		
		if( $rootScope.classes !== undefined )
		{	
			$scope.classes = $rootScope.classes;
			if( $state.params.class_id !== null )
			{
				console.log('here with class id ' + $state.params.class_id );
				console.log($scope.classes);
				$scope.filters.class = $scope.classes.filter(function(item){
					if( item.class_id == $state.params.class_id ) return item;
				})[0];
				console.log($scope.filters);
				$scope.setClass();
			}
			else
			{
				$scope.filters.class = $scope.classes[0];
			}
			
			$scope.loadingPost = false;
		}
		else
		{
			var params = $rootScope.currentUser.emp_id + '/true';
			apiService.getTeacherClasses(params, function(response,status){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{	
					$rootScope.classes = $scope.classes = ( result.nodata ? [] : result.data );	
					if( $scope.classes.length > 0 ) 
					{
						if( $state.params.class_id !== null )
						{
							console.log('here with class id ' + $state.params.class_id );
							console.log($scope.classes);
							$scope.filters.class = $scope.classes.filter(function(item){
								if( item.class_id == $state.params.class_id ) return item;
							})[0];
							console.log($scope.filters);
							$scope.setClass();
						}
						else
						{
							$scope.filters.class = $scope.classes[0];
						}
					}
					else $scope.noClasses = true;
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				$scope.loadingPost = false;
				
			}, apiError);
		}
		
		if( $state.params.post === null && $state.params.post_id !== undefined )
		{
			apiService.getPost($state.params.post_id, function(response, status){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{	
					if( result.nodata )
					{
						$scope.post = {};
						$scope.notFound = true;
						$scope.loadingPost = false;
					}
					else
					{
						$scope.post = result.data;	
						$scope.classSelected = true;
						$scope.selectedClass = {
							blog_name: $scope.post.blog_name,
							class_id: $scope.post.class_id,
							class_name: $scope.post.class_name,
							blog_id: $scope.post.blog_id
						}
						$scope.setupBlog = ( $scope.selectedClass.blog_id === null ? true : false );
						$scope.loadingPost = false;
						console.log($scope.post);
					}
					
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
			}, apiError);
		}
		else if( $scope.post.post_id !== undefined )
		{
			$scope.selectedClass = $state.params.selectedClass;
			$scope.classSelected = true;
			$scope.setupBlog = ( $scope.selectedClass.blog_id === null ? true : false );
			$scope.loadingPost = false;
		}

	}
	$timeout(initializeController,100);
	
	$scope.cancel = function()
	{
		$state.go('manage_blog', {class_id: $scope.selectedClass.class_id });
	}; // end cancel
	
	$scope.setClass = function()
	{
		$scope.classSelected = true;
		$scope.selectedClass = angular.copy($scope.filters.class);
		$scope.setupBlog = ( $scope.selectedClass.blog_id === null ? true : false );
		if( $scope.setupBlog )
		{
			$scope.blog = {};
		}
		console.log($scope.selectedClass);
	}
	
	$scope.updateBlogName = function()
	{
		$scope.editingBlogName = true;
		if( $scope.blog === undefined ) $scope.blog = {};
		$scope.blog.blog_name = angular.copy($scope.selectedClass.blog_name);
	}
	
	$scope.addPost = function()
	{		
		$state.go('add_post', {class_id: $scope.selectedClass.class_id});
	}
	
	$scope.saveBlogName = function()
	{
		var data = {
			user_id: $scope.currentUser.user_id,
			blog_id: $scope.post.blog_id,
			blog_name: $scope.blog.blog_name 
		}

		apiService.updateBlog(data,function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$scope.editingBlogName = false;
				$scope.selectedClass.blog_name = angular.copy($scope.blog.blog_name);
			}
		},apiError);
	}
	
	$scope.preview = function()
	{
		$scope.openModal('blog', 'previewPost', 'md', $scope.post);
	}
	
	$scope.saveDraft = function(form)
	{
		$scope.post.post_status_id = 2; // TO DO: fix this 
		$scope.save(form);
	}
	
	$scope.publish = function(form)
	{
		$scope.post.post_status_id = 1; // TO DO: fix this 
		if( $scope.edit ) $scope.updatePost(form);
		else $scope.save(form);
	}
	
	$scope.updatePost = function(form)
	{
		$scope.saving = true;
		if( uploader.queue[0] !== undefined )
			{
				// need a unique filename
				uploader.queue[0].file.name =  moment() + '_' + uploader.queue[0].file.name;
				uploader.uploadAll();
				
				$scope.post.feature_image = ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null);
			}

			console.log($scope.post);
			var data = {
				user_id: $scope.currentUser.user_id,
				post: $scope.post
			}

			apiService.updatePost(data,createCompleted,apiError);

	}
	
	$scope.deletePost = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete this post? <b>This can not be undone</b>.',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deletePost($scope.post.post_id,createCompleted,apiError);
		});
		
	}
	
	$scope.save = function(form)
	{
		//console.log(form);
		$scope.error = false;
		$scope.errMsg = '';
		if ( !form.$invalid ) 
		{
			$scope.saving = true;
			if( $scope.setupBlog )
			{
				
				var data = {
					teacher_id: $scope.currentUser.emp_id,
					blog_name: $scope.blog.blog_name,
					class_id: $scope.selectedClass.class_id
				}
				apiService.addBlog(data,function(response, status){
					var result = angular.fromJson( response );
					if( result.response == 'success' )
					{
						$scope.setupBlog = false;
						$scope.post.blog_id = result.data;
						$scope.selectedClass.blog_id = result.data;
						$scope.selectedClass.blog_name = $scope.blog.blog_name;
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
					$scope.saving = false;
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

				var data = {
					user_id: $scope.currentUser.user_id,
					blog_id: $scope.selectedClass.blog_id,
					post: $scope.post
				}

				apiService.addPost(data,createCompleted,apiError);
								
			}
		}
	}
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'posts'
			}]
    });
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$scope.updated = true;
			//$scope.notificationMsg = args.msg;
			
			// wait a bit, then turn off the alert
			$timeout(function() { $scope.alert.expired = true;  }, 1000);
			$timeout(function() { 
				$scope.updated = false;
				$scope.notificationMsg = ''; 
				$scope.alert.expired = false;
				$state.go('manage_blog', {class_id: $scope.selectedClass.class_id});
			}, 1500);
			
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
		$scope.loadingPost = false;
		$scope.saving = false;
	}
	
	
} ]);