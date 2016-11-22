'use strict';

angular.module('eduwebApp').
controller('listPostsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','$state',
function($scope, $rootScope, apiService, $timeout, $window, $filter, $state){

	$scope.filters = {};
	$scope.filters.post_status_id = 'All';
	$scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
	$scope.filterClass = ( $state.params.class_id !== '' ? true : false );	
	$scope.alert = {};
	$scope.loading = true;
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';
	
	var rowTemplate = function() 
	{
		return '<div class="clickable">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Title', field: 'title', enableColumnMenu: false, sort: {direction:'asc'}, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPost(row.entity)">{{row.entity.title}}</div>'},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPost(row.entity)">{{row.entity.class_name}}</div>'},
			{ name: 'Date', field: 'creation_date', type: 'date', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPost(row.entity)">{{row.entity.creation_date}}</div>'},
			{ name: 'Status', field: 'post_status', width:75, enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPost(row.entity)">{{row.entity.post_status}}</div>'},
			{ name: 'View', field: '', cellClass:'center', width:40, headerCellClass:'center', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.preview(row.entity)"><i class="fa fa-eye"></i></div>'},
			
		],
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};
	
	var initializeController = function () 
	{		
		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);
		
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
		
		// get all the teachers classes
		if( $rootScope.classes === undefined )
		{
			getClasses();	
		}
		else
		{
			$scope.classes = $rootScope.classes;
			if( $scope.filters.class_id === null ) $scope.filters.class_id = $scope.classes[0].class_id;
			getPosts( angular.copy($scope.filters) );
		}
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
					getPosts( angular.copy($scope.filters) );
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
	
	var getPosts = function(filters)
	{
		
		var params = filters.class_id + '/' + (filters.post_status_id || 'All');
		apiService.getClassPosts(params, function(response,status){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.posts = ( result.nodata ? [] : result.data );

				$scope.posts = $scope.posts.map(function(item){
					item.creation_date =  moment(item.creation_date).format('MMM Do YYYY, h:mm a');
					return item;
				});
				initDataGrid($scope.posts);
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
		getPosts( angular.copy($scope.filters) );		
	}
		
	var initDataGrid = function(data) 
	{
		$scope.gridOptions.data = data;
		$scope.loading = false;
		$rootScope.loading = false;
		
	}
	
	$scope.filterDataTable = function() 
	{
		$scope.gridApi.grid.refresh();
	};
	
	$scope.clearFilterDataTable = function() 
	{
		$scope.gridFilter.filterValue = '';
		$scope.gridApi.grid.refresh();
	};
	
	$scope.singleFilter = function( renderableRows )
	{
		var matcher = new RegExp($scope.gridFilter.filterValue, 'i');
		renderableRows.forEach( function( row ) {
		  var match = false;
		  [ 'class_name', 'title', 'post_status' ].forEach(function( field ){
			if ( row.entity[field].match(matcher) ){
			  match = true;
			}
		  });
		  if ( !match ){
			row.visible = false;
		  }
		});
		return renderableRows;
	};
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	$scope.preview = function(post)
	{
		var data = {
			type: 'post',
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
		$state.go('communications/add_post', { post_type:'communication'});
	}
	
	
	$scope.viewPost = function(item)
	{
	/*
		var selectedClass = $scope.classes.filter(function(item){
			if( item.class_id == $scope.filters.class_id ) return item;
		})[0];
		*/
		$state.go('communications/edit_post', {post: item, post_id: item.post_id, post_type: 'post'});
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
		getPosts( angular.copy($scope.filters)  );
	}
	
	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });

} ]);