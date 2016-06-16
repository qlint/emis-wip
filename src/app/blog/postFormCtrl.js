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
	
	$scope.post_type = ( $state.params.post_type !== undefined ? $state.params.post_type : 'post' );
	$scope.isPost = ($scope.post_type == 'post' ? true : false );
	$scope.isHomework = ($scope.post_type == 'homework' ? true : false );

	
	if( $scope.isHomework )
	{
		$scope.dates = {};
		$scope.dates.assigned_date = {startDate:moment().format('YYYY-MM-DD')};
		$scope.dates.due_date = {startDate:null};
	}
		
	var initializeController = function()
	{		
		
		/* post_id was passed, editing a post */
		/* if the post data was not sent, grab post data from post id */
		if( $state.params.post === null && $state.params.post_id !== undefined )
		{
			if( $scope.isHomework ) 
			{
				apiService.getHomeworkPost($state.params.post_id, function(response, status){
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
							$scope.dates.assigned_date = {startDate:moment($scope.post.assigned_date).format('YYYY-MM-DD')};
							$scope.dates.due_date = {startDate:moment($scope.post.due_date).format('YYYY-MM-DD')};
							$scope.classSelected = true;
							$scope.selectedClassSubject = {
								subject_name: $scope.post.subject_name,
								class_id: $scope.post.class_id,
								class_name: $scope.post.class_name,
								subject_id: $scope.post.subject_id,
								class_subject_id:  $scope.post.class_subject_id
							}
							$scope.setupBlog = false;
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
			else
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
		}
		else if( $scope.post.post_id !== undefined )
		{
			if( $scope.isHomework ) $scope.selectedClassSubject = $state.params.selectedClassSubject;
			else $scope.selectedClass = $state.params.selectedClass;
			$scope.classSelected = true;
			$scope.setupBlog = false;
			$scope.loadingPost = false;
			console.log($scope.post);
			$scope.dates.assigned_date = {startDate:moment($scope.post.assigned_date).format('YYYY-MM-DD')};
			$scope.dates.due_date = {startDate:moment($scope.post.due_date).format('YYYY-MM-DD')};
			console.log($scope.dates);
		}
		
		
		/* set data for drop down */
		if( $scope.isHomework )
		{
			/* get teachers associated class subjects */
			if( $rootScope.classSubjects !== undefined )
			{	
				$scope.classSubjects = $rootScope.classSubjects;
				setInitalClassSubject();					
				$scope.loadingPost = false;
			}
			else
			{
				apiService.getTeacherClassSubjects($rootScope.currentUser.emp_id, function(response,status){
					
					var result = angular.fromJson(response);
						
					if( result.response == 'success' )
					{	
						$rootScope.classSubjects = $scope.classSubjects = ( result.nodata ? [] : result.data );	
						console.log($rootScope.classSubjects);
						if( $scope.classSubjects.length > 0 ) 
						{
							setInitalClassSubject();
						}
						else $scope.noClassSubjects = true;
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
					$scope.loadingPost = false;
					
				}, apiError);
			}
		}
		else
		{
			/* if classes not yet set, get list of classes for drop down */
			if( $rootScope.classes !== undefined )
			{	
				$scope.classes = $rootScope.classes;
				/* if a class id was passed in, set this as the active filter */
				setInitalClass();
				
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
							setInitalClass();
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
	
		}
		
	}
	$timeout(initializeController,100);
	
	var setInitalClass = function()
	{
		/* if a class id was passed in, set this as the active filter */
		if( $state.params.class_id !== null )
		{
			$scope.filters.class = $scope.classes.filter(function(item){
				if( item.class_id == $state.params.class_id ) return item;
			})[0];
			$scope.setClass();
		}
		else
		{
			/* if not class passed in, set drop down to first class */
			$scope.filters.class = $scope.classes[0];
		}
					
	}
	
	var setInitalClassSubject = function()
	{
		/* if a class id was passed in, set this as the active filter */
		if( $state.params.class_subject_id !== null )
		{
			$scope.filters.subject = $scope.classSubjects.filter(function(item){
				if( item.class_subject_id == $state.params.class_subject_id ) return item;
			})[0];
			$scope.setClassSubject();
		}
		else
		{
			/* if not class passed in, set drop down to first class */
			$scope.filters.subject = $scope.classSubjects[0];
		}
					
	}
	
	
	$scope.cancel = function()
	{
		if( $scope.isHomework ) $state.go('manage_blog/homework', {class_subject_id: $scope.selectedClassSubject.class_subject_id });
		else  $state.go('manage_blog/posts', {class_id: $scope.selectedClass.class_id });
	}; // end cancel
	
	$scope.setClass = function()
	{
		if( $scope.isHomework )
		{
			$scope.setClassSubject();
		}
		else
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
	}
	
	$scope.setClassSubject = function()
	{
		$scope.classSelected = true;
		$scope.classSubjectSelected = true;
		$scope.selectedClassSubject = angular.copy($scope.filters.subject);
		console.log($scope.selectedClassSubject);
	}
	
	$scope.updateBlogName = function()
	{
		$scope.editingBlogName = true;
		if( $scope.blog === undefined ) $scope.blog = {};
		$scope.blog.blog_name = angular.copy($scope.selectedClass.blog_name);
	}
	
	$scope.addPost = function()
	{		
		$state.go('add_post', {class_id: $scope.selectedClass.class_id, post_type:'post'});
	}
	
	$scope.addHomework = function()
	{		
		$state.go('add_post', {class_subject_id: $scope.selectedClassSubject.class_subject_id, post_type:'homework'});
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
		var data = {
			type: $scope.post_type,
			post: $scope.post
		}
		$scope.openModal('blog', 'previewPost', 'md', data);
	}
	
	$scope.saveDraft = function(form)
	{
		$scope.postForm.$setSubmitted();
		$scope.post.post_status_id = 2; // TO DO: fix this 
		$scope.save(form);
	}
	
	$scope.publish = function(form)
	{
		$scope.postForm.$setSubmitted();
		$scope.post.post_status_id = 1; // TO DO: fix this 
		if( $scope.edit ) $scope.updatePost(form);
		else $scope.save(form);
	}
	
	$scope.updatePost = function(form)
	{
		$scope.postForm.$setSubmitted();
		$scope.saving = true;
		if( uploader.queue[0] !== undefined )
		{
			// need a unique filename
			uploader.queue[0].file.name =  moment() + '_' + uploader.queue[0].file.name;
			uploader.uploadAll();
			
			if( $scope.isHomework ) $scope.post.attachment = ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null);
			else $scope.post.feature_image = ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null);
		}

		console.log($scope.post);
		if( $scope.isHomework )
		{
			$scope.post.due_date = ( $scope.dates.due_date.startDate !== undefined ? moment($scope.dates.due_date.startDate).format('YYYY-MM-DD'): null);
			$scope.post.assigned_date = ( $scope.dates.assigned_date.startDate !== undefined ? moment($scope.dates.assigned_date.startDate).format('YYYY-MM-DD'): null);
			var data = {
				user_id: $scope.currentUser.user_id,
				post: $scope.post
			}

			apiService.updateHomework(data,createCompleted,apiError);
		}
		else
		{
			var data = {
				user_id: $scope.currentUser.user_id,
				post: $scope.post
			}

			apiService.updatePost(data,createCompleted,apiError);
		}

	}
	
	$scope.deletePost = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete this post? <b>This can not be undone</b>.',{size:'sm'});
		dlg.result.then(function(btn){
			if( $scope.isHomework)  apiService.deleteHomework($scope.post.post_id,createCompleted,apiError);
			else apiService.deletePost($scope.post.post_id,createCompleted,apiError);
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
					
					if( $scope.isHomework ) $scope.post.attachment = ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null);
					else $scope.post.feature_image = ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null);
				}
				console.log($scope.post);
				
				if( $scope.isHomework )
				{
					console.log($scope.due_date);
					console.log($scope.assigned_date);
					
					$scope.post.due_date = ( $scope.dates.due_date.startDate !== undefined ? moment($scope.dates.due_date.startDate).format('YYYY-MM-DD'): null);
					$scope.post.assigned_date = ( $scope.dates.assigned_date.startDate !== undefined ? moment($scope.dates.assigned_date.startDate).format('YYYY-MM-DD'): null);

					var data = {
						user_id: $scope.currentUser.user_id,
						class_subject_id: $scope.selectedClassSubject.class_subject_id,
						post: $scope.post
					}

					apiService.addHomework(data,createCompleted,apiError);
				}
				else
				{
					$scope.post.post_type_id = 1;
					var data = {
						user_id: $scope.currentUser.user_id,
						blog_id: $scope.selectedClass.blog_id,
						post: $scope.post
					}

					apiService.addPost(data,createCompleted,apiError);
				}
								
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
				if( $scope.isHomework ) $state.go('manage_blog/homework', {class_subject_id: $scope.selectedClassSubject.class_subject_id });
				else  $state.go('manage_blog/posts', {class_id: $scope.selectedClass.class_id });
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