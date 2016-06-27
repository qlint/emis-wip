'use strict';

angular.module('eduwebApp').
controller('listEmailsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','$state',
function($scope, $rootScope, apiService, $timeout, $window, $filter, $state){

	$scope.filters = {};
	$scope.filters.audience_id = null;
	$scope.filters.com_type_id = null;
	$scope.filters.post_status_id = null;
	$scope.alert = {};
	$scope.loading = true;
	
	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );
	
	var initializeController = function () 
	{		
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

		if( $rootScope.comTypes === undefined )
		{
			apiService.getCommunicationOptions({}, function(response){
				var result = angular.fromJson(response);
				
				// store these as they do not change often
				if( result.response == 'success')
				{
					$rootScope.comTypes = $scope.comTypes = result.data.com_types;		
					$rootScope.comAudience = $scope.comAudience = result.data.audiences;	
				}
				
			}, apiError);
		}
		else
		{
			$scope.comTypes = $rootScope.comTypes;
			$scope.comAudience = $rootScope.comAudience;				
		}
		
		getCommunications();
		

	}
	$timeout(initializeController,1);

	var getClasses = function()
	{	
		var params = $rootScope.currentUser.emp_id + '/true';
		apiService.getTeacherClasses(params, function(response,status){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$rootScope.classes = $scope.classes = ( result.nodata ? [] : result.data );	
				if( $scope.classes.length > 0 )
				{				
					if( $scope.filters.class_id === null ) $scope.filters.class_id = $scope.classes[0].class_id;
					getCommunications( angular.copy($scope.filters) );
				}
				else
				{
					$scope.noClasses = true;
				}

			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, apiError);

		
	}
	
	var getCommunications = function(filters)
	{
		if( $scope.dataGrid !== undefined )
		{	
			$('.fixedHeader-floating').remove();
			//$scope.dataGrid.clear();
			$scope.dataGrid.destroy();				
		}	
		
		if( $scope.isTeacher )
		{
			var params = $rootScope.currentUser.emp_id;
			apiService.getTeacherCommunications(params, loadEmails, apiError, {filters:filters});
		}
		else
		{
			apiService.getSchoolCommunications({}, loadEmails, apiError, {filters:filters});
		}
	}
	
	var loadEmails = function(response,status,params)
	{
		var result = angular.fromJson(response);
		
		if( result.response == 'success')
		{	
			$scope.emails = ( result.nodata ? [] : result.data );	

			$scope.emails = $scope.emails.map(function(item){
				item.creation_date = moment(item.creation_date).format('MMM Do YYYY, h:mm a');
				item.send_method = (item.send_as_sms ? 'sms' : 'email');
				item.message_truncated = ( item.message.length > 100 ? item.message.substring(0,100) + '...' : item.message );
				item.body = item.message;
				item.title = item.subject;
				return item;
			});
			$scope.allResults = $scope.emails;
			if( params.filters ) $scope.loadFilter();
			else $timeout(initDataGrid,100);
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
		$scope.loading = false;
	}
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		if( $scope.dataGrid !== undefined ){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.destroy();
		}
		
		var filteredResults = $scope.allResults;
		
		/* filter audience if set */
		if( $scope.filters.audience_id !== null )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.audience_id.toString() == $scope.filters.audience_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}
		
		/* filter type if set */
		if( $scope.filters.com_type_id !== null  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.com_type_id.toString() == $scope.filters.com_type_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}
		
		/* filter status if set */
		if( $scope.filters.post_status_id !== null  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.post_status_id.toString() == $scope.filters.post_status_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}
		
		$scope.emails = filteredResults;
		$timeout(initDataGrid,100);
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
						emptyTable: "No posts found."
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
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+45);
		}
		
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
			type: 'communication',
			post: post
		}
		$scope.openModal('communications', 'previewPost', 'md', data);
	}
	
	$scope.addPost = function()
	{		
		$state.go('communications/add_post', {class_id: $scope.filters.class_id, post_type:'post'});
	}
	
	$scope.addHomework = function()
	{		
		$state.go('communications/add_post', {class_id: $scope.filters.class_id, post_type:'homework'});
	}
	
	$scope.addEmail = function()
	{		
		$state.go('communications/add_post', {post_type:'communication'});
	}
	
	$scope.viewEmail = function(item)
	{
		$state.go('communications/edit_post', {post: item, post_id: item.post_id, post_type: 'communication'});
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
		getCommunications( angular.copy($scope.filters)  );
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