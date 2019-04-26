'use strict';

angular.module('eduwebApp').
controller('feedbackCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','$state',
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
	
	if( $( "li:contains('Feedback')" ) ){
	    // console.log("Feedback tab");
	    apiService.getFeedbackUnopenedCount({}, function(response){
				var result = angular.fromJson(response);
				// console.log(result);
				
				if( result.response == 'success')
				{
					// console.log(result.data);
					$( "li a:contains('Feedback')" ).append( "<span class='notifBox'>" + result.data.count + "</span>" );
				}
				
			}, apiError);
	}
	
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
			{ name: 'Date', field: 'sent_date', type:'date', enableColumnMenu: false, sort: {direction:'desc'}, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.opened == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.sent_date|date:"MMM d yyyy, h:mm a"}}</div>'},
			{ name: 'Parent', field: 'parent_full_name', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.opened == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.parent_full_name}}</div>'},
			{ name: 'Student(s)', field: 'student_name', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.opened == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.student_name}}</div>'},
			{ name: 'Subject', field: 'subject', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.opened == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.subject}}</div>'},
			{ name: 'Message', field: 'message', enableColumnMenu: false, width:'40%', cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.opened == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)" ng-bind-html="row.entity.message"></div>'},
			{ name: 'Opened', field: 'opened', width:75, enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.opened == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.opened}}</div>'},
			{ name: 'View', field: '', cellClass:'center', width:40, headerCellClass:'center', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.opened == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.preview(row.entity)"><i class="fa fa-eye"></i></div>'},
			
		],
		exporterCsvFilename: 'received-emails.csv',
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
		
		$scope.postStatuses = [
		    {"option":"read",
		      "id":true
		    },
		    {"option":"unread",
		      "id":false
		    }
		    ];

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
		
	    apiService.getAllFeedback({}, loadEmails, apiError, {filters:filters});
	}
	
	var loadEmails = function(response,status,params)
	{
		var result = angular.fromJson(response);
		
		if( result.response == 'success')
		{	
			$scope.emails = ( result.nodata ? [] : result.data );	
            
			$scope.emails = $scope.emails.map(function(item){
				// item.sent_date = new Date(item.creation_date);
				item.send_method = (item.send_as_sms ? 'sms' : 'email');
				item.message_truncated = ( item.message.length > 100 ? item.message.substring(0,100) + '...' : item.message );
				item.body = item.message;
				item.title = item.subject;
				return item;
			});
			$scope.allResults = $scope.emails;
			// console.log($scope.allResults);
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
		  // original -> 'com_type', 'audience', 'subject', 'message', 'post_status'
		  [ 'subject', 'message', 'message_from', 'sent_date', 'opened' ].forEach(function( field ){
			if ( String(row.entity[field]).match(matcher) ){
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
	    console.log("In preview function");
		var data = {
			type: 'communication',
			post: post
		}
		$scope.openModal('communications', 'previewFeedback', 'md', data);
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
    if( item.opened === false )
    {
        // feedback has never been opened, change 'opened' state to true
        console.log("Update and head to preview email",item);
        
        var feedbackId = {
			post_id: item.post_id
		}
		
		var classNameFetch = 'post' + item.post_id;

		apiService.updateOpenedFeedbackMessage(feedbackId,function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				console.log("Success. Message status changed.");
				if( $( "li:contains('Feedback')" ) ){
            	    // console.log("Feedback tab");
            	    apiService.getFeedbackUnopenedCount({}, function(response){
            				var result = angular.fromJson(response);
            				// console.log(result);
            				
            				if( result.response == 'success')
            				{
            					$( "li a:contains('Feedback')" ).html( "Feedback <span class='notifBox'>" + result.data.count + "</span>" );
            					var removeRedClass = document.getElementsByClassName(classNameFetch);
            					
                                for (var z = 0; z < removeRedClass.length; z++) {
                                  removeRedClass[z].className = removeRedClass[z].className.replace(/\bunreadMsg\b/g, "");
                                }
            				}
            				
            			}, apiError);
            	}
			}
		},apiError);
		
        console.log(item);
        $scope.preview(item);
    }
    else
    {
        // feedback has been opened before, no need to change 'opened' state
        $scope.preview(item);
    }
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