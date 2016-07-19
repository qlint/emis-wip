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
			{ name: 'Date', field: 'creation_date', type:'date', cellFilter:'date', enableColumnMenu: false, sort: {direction:'asc'}, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.creation_date|date}}</div>'},
			{ name: 'Type', field: 'com_type', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.com_type}}</div>'},
			{ name: 'Recipient', field: 'audience', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.audience}}</div>'},
			{ name: 'Subject', field: 'subject', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.subject}}</div>'},
			{ name: 'Message', field: 'message', enableColumnMenu: false, width:'40%', cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewEmail(row.entity)" ng-bind-html="row.entity.message"></div>'},
			{ name: 'Status', field: 'post_status', width:75, enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.post_status}}</div>'},
			{ name: 'View', field: '', cellClass:'center', width:40, headerCellClass:'center', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.preview(row.entity)"><i class="fa fa-eye"></i></div>'},
			
		],
		exporterCsvFilename: 'school-departments.csv',
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
			else initDataGrid($scope.emails);
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
		initDataGrid($scope.emails);
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
		  [ 'com_type', 'audience', 'subject', 'message', 'post_status' ].forEach(function( field ){
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
		$rootScope.isModal = false;
    });

} ]);