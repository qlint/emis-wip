'use strict';

angular.module('eduwebApp').
controller('listHomeworkCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','$state',
function($scope, $rootScope, apiService, $timeout, $window, $filter, $state){
	
	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.post_status_id = null;
	$scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
	$scope.filters.subject_id = ( $state.params.subject_id !== '' ? $state.params.subject_id : null );
	$scope.filterClass = ( $state.params.class_id !== '' ? true : false );	
	$scope.alert = {};
	
	var initializeController = function () 
	{		
		/* get post statuses if not set */
		if( $rootScope.postStatuses === undefined )
		{
			apiService.getBlogPostStatuses({}, function(response){
				var result = angular.fromJson(response);
				
				// store these as they do not change often
				if( result.response == 'success') 
				{
					$scope.postStatuses = result.data;
					$rootScope.postStatuses = $scope.postStatuses;
				}		
				
			}, apiError);
		}
		else
		{
			$scope.postStatuses = $rootScope.postStatuses;
		}
		
		/* get all the teachers class subjects */
		getClassSubjects();		
	}
	$timeout(initializeController,1);
	
	$scope.$watch('filters.class_id', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		/* filter the subjects based on selected class */
		$scope.classSubjects = $scope.allClassSubjects.reduce(function(sum,item){
			if( item.class_id == newVal ) sum.push(item);
			return sum;
		},[]);
		$timeout(setSearchBoxPosition,10);
		
	});

	var getClassSubjects = function()
	{	
		var params = $rootScope.currentUser.emp_id;
		apiService.getTeacherClassSubjects(params, function(response,status){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.allClassSubjects = ( result.nodata ? [] : result.data );	
				if( $scope.allClassSubjects.length > 0 )
				{				
					/* build array of unique classes for classes drop down */
					$scope.classes = $scope.allClassSubjects.reduce(function(sum,item){
						var classObj = {
							class_id: item.class_id,
							class_name: item.class_name
						};
						if( !containsClassId(classObj, sum) ) sum.push(classObj);
						return sum;
					}, []);

					
					//if( $scope.filters.class_id === null ) $scope.filters.class_id = $scope.classes[0].class_id;
					if( $scope.filters.subject_id !== null )
					{
						/* set class subjects */
						$scope.classSubjects = $scope.allClassSubjects.reduce(function(sum,item){
							if( item.class_id == $scope.filters.class_id ) sum.push(item);
							return sum;
						},[]);
		
						/* set selected class subject */
						var activeClassSubject = $scope.allClassSubjects.filter(function(item){
							if( item.class_id == $scope.filters.class_id && item.subject_id == $scope.filters.subject_id) return item;
						})[0];
						
						$scope.filters.class_subject_id = activeClassSubject.class_subject_id;
					}

					getHomework( angular.copy($scope.filters) );
				}
				else
				{
					$scope.noHomework = true;
				}

			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, apiError);

		
	}
	
	var containsClassId = function(obj, list) {
		var i;
		for (i = 0; i < list.length; i++) {
			if (list[i].class_id == obj.class_id) {
				return true;
			}
		}

		return false;
	}
	
	var getHomework = function(filters)
	{
		if( $scope.dataGrid !== undefined )
		{	
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();				
		}	

		var params = (filters.post_status_id || 'All') + '/' + (filters.class_subject_id || 'All') + '/' + (filters.class_id || 'All');
		apiService.getHomeworkPosts(params, function(response,status){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.homework = ( result.nodata ? [] : result.data );	

				$scope.homework = $scope.homework.map(function(item){
					item.assigned_date2 = moment(item.assigned_date).format('MMM Do YYYY, h:mm a');
					item.due_date2 = moment(item.due_date).format('MMM Do YYYY, h:mm a');
					return item;
				});
				$timeout(initDataGrid,10);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			$scope.loading = false;
		}, apiError);
	}
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		getHomework( angular.copy($scope.filters) );		
	}
		
	var initDataGrid = function() 
	{
	
		var tableElement = $('#resultsTable');
		$scope.dataGrid = tableElement.DataTable( {
				responsive: {
					details: {
						type: 'column'
					}
				},
				columnDefs: [ {
					className: 'control',
					orderable: false,
					targets:   0
				} ],
				paging: false,
				destroy:true,
				order: [1,'asc'],
				filter: true,
				info: false,
				sorting:[],
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
						search: "Search Results<br>",
						searchPlaceholder: "Filter",
						lengthMenu: "Display _MENU_",
						emptyTable: "No homework posts found."
				},
			} );
			
		
		var headerHeight = $('.navbar-fixed-top').height();
		//var subHeaderHeight = $('.subnavbar-container.fixed').height();
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 13 );
		new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );
	
		
		// position search box
		setSearchBoxPosition();
		
		if( initialLoad ) setResizeEvent();
		
	}
	
	var setSearchBoxPosition = function()
	{
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+45);
		}
	}
	
	var setResizeEvent = function()
	{
		 initialLoad = false;

		 $window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	$scope.preview = function(post)
	{
		var data = {
			type: 'homework',
			post: post
		}
		$scope.openModal('communications', 'previewPost', 'md', data);
	}
	
	$scope.addPost = function()
	{		
		$state.go('communications/add_post', {class_subject_id: $scope.filters.class_subject_id, post_type:'post'});
	}
	
	$scope.addHomework = function()
	{		
		$state.go('communications/add_post', {class_subject_id: $scope.filters.class_subject_id, post_type:'homework'});
	}
	
	$scope.addEmail = function()
	{		
		$state.go('communications/add_post', { post_type:'communication'});
	}
	
	
	$scope.viewPost = function(item)
	{
		$state.go('communications/edit_post', {post: item, post_id: item.homework_id, post_type: 'homework'});
	}
	
	$scope.$on('refreshPosts', function(event, args) {

		$scope.loading = true;
		$rootScope.loading = true;
		
		if( args !== undefined )
		{
			$scope.updated = true;
			$scope.notificationMsg = args.msg;
		}
		$scope.refresh();
		
		// wait a bit, then turn off the alert
		$timeout(function() { $scope.alert.expired = true;  }, 2000);
		$timeout(function() { 
			$scope.updated = false;
			$scope.notificationMsg = ''; 
			$scope.alert.expired = false;
		}, 3000);
	});
	
	$scope.refresh = function () 
	{
		$scope.loading = true;
		$rootScope.loading = true;
		getHomework( angular.copy($scope.filters)  );
	}
	
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