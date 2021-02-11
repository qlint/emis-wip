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
	$scope.subdomain = window.location.host.split('.')[0];

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );

	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';

	if( $( "li:contains('Feedback')" ) ){
	    // console.log("Feedback tab located");
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

	if( $rootScope.currentUser.user_type == 'SYS_ADMIN' || $rootScope.currentUser.user_type == 'FINANCE_CONTROLLED' ){
	    if( $( "li:contains('Send Email')" ) ){
    	    // console.log("Send Email tab located");
    	    apiService.getUnPublishedMsgCount({}, function(response){
    				var result = angular.fromJson(response);
    				// console.log(result);

    				if( result.response == 'success')
    				{
    					// console.log(result.data);
    					$( "li a:contains('Send Email')" ).append( "<span class='notifBox'>" + result.data.count + "</span>" );
    				}

    			}, apiError);
    	}
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
		    { name: 'Approve', field: 'approve', width:55,enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}"><input ng-disabled="{{row.entity.sent == false ? \'false\':\'true\'}}" value="{{row.entity.post_id}}" type="checkbox" class="approveMsgs" name="approveMsgs"></div>'},
		    // { name: 'Approve', field: 'approve', width:55,enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}"><input value="{{row.entity.post_id}}" type="checkbox" class="approveMsgs" name="approveMsgs"></div>'},
			{ name: 'Date', field: 'creation_date', type:'date', enableColumnMenu: false, sort: {direction:'desc'}, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.creation_date|date:"MMM d yyyy, h:mm a"}}</div>'},
			{ name: 'Type', field: 'com_type', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.com_type}}</div>'},
			{ name: 'Recipient', field: 'audience', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.audience}}</div>'},
			{ name: 'Subject', field: 'subject', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.subject}}</div>'},
			{ name: 'Message', field: 'message', enableColumnMenu: false, width:'40%', cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)" ng-bind-html="row.entity.message"></div>'},
			{ name: 'Status', field: 'post_status', width:75, enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.viewEmail(row.entity)">{{row.entity.post_status}}</div>'},
			{ name: 'View', field: '', cellClass:'center', width:40, headerCellClass:'center', enableColumnMenu: false, cellTemplate:'<div class="ui-grid-cell-contents post{{row.entity.post_id}} {{row.entity.sent == false ? \'unreadMsg\':\'\'}}" ng-click="grid.appScope.preview(row.entity)"><i class="fa fa-eye"></i></div>'},

		],
		exporterCsvFilename: 'school-emails.csv',
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
		// console.log($scope);

		setTimeout(function(){
    	    // show or hide the 'Approve messages button'
    	    var inpForApprv = document.getElementsByClassName('approveMsgs');

    	    $scope.inputsForApproval = [].slice.call(inpForApprv);

    	    $scope.approveBtn = false;
    	    for (var m = 0; m < $scope.inputsForApproval.length; m++){
    			if( $scope.inputsForApproval[m].disabled == false )
    			{
    				// if there's an enabled input - we show the 'Approve Msg' button
    				$scope.approveBtn = true;
    				var allowBtn = document.getElementById('approveSelectedMsgs');
    				var allowDeleteBtn = document.getElementById('deleteSelectedMsgs');
    				allowBtn.style.display = "";
    				allowDeleteBtn.style.display = "";
    			}
    		}

    	    // acquire values from the selected inputs

            $("#approveSelectedMsgs").click(function(){
                var approveList = [];
                $.each($("input[name='approveMsgs']:checked"), function(){
                    approveList.push($(this).val());
                });
                // console.log("Selected message id's are: " + approveList.join(", "));
                var msgId = { post_id: approveList }
                apiService.batchPublishMessages(msgId,function(response){
		        			var result = angular.fromJson( response );
		        			if( result.response == 'success' )
		        			{
		        				alert("Success. Messages are now published and visible to the recipients.");
		        				// $uibModalInstance.close();
		        				if( $( "li:contains('Send Email')" ) ){
											// console.log("Feedback tab");
											apiService.getUnPublishedMsgCount({}, function(response){
												var result = angular.fromJson(response);
												// console.log(result);
												if( result.response == 'success')
												{
													// console.log(result.data);
													$( "li a:contains('Send Email')" ).html( "Send Email <span class='notifBox'>" + result.data.count + "</span>" );
												}

											}, apiError);
										}
		        			}
								},apiError);

        		// POST SMS - START
						$scope.unpublishedSms = false;
        		// filter through all messages and put checked sms's (from approveList above) in an array to later send
        		var unpublishedSmsArr = [];
        		// we check through all messages if an unpublished sms exists
        		for (var q = 0; q < $scope.emails.length; q++){
            			if( $scope.emails[q].send_as_sms == true && $scope.emails[q].sent == false )
            			{
            			    for(var r = 0; r < approveList.length; r++){
            			        if($scope.emails[q].post_id == approveList[r]){
            			           // there exists an unpublished sms
                    				unpublishedSmsArr.push($scope.emails[q].post_id);
                    				$scope.unpublishedSms = true;
            			        }
            			    }
            			}
            	}

            	if($scope.unpublishedSms == true){
            	    console.log("Message id's for unpublished messages",unpublishedSmsArr);

            	    // we need to get the sms details for each item in 'unpublishedSmsArr'
            	    unpublishedSmsArr.forEach(function(eachMsgId) {
										// fetch sms post
										var params = eachMsgId;
										apiService.getSmsDetails(params, function(response,status){
											var result = angular.fromJson(response);
											if( result.response == 'success')
											{
												// initialize data variable
												var smsData = ( result.nodata ? [] : result.data );
												// Post the message
												if($scope.subdomain == 'appleton' || $scope.subdomain == 'appletonngong'){
													let data = {
															account_id: 1,
															messagetext: smsData[0].message_text,
															recipients: []
													}
													for(let x=0;x < smsData.length;x++){
															data.recipients.push("+254" + smsData[x].phone_number);
													}
													console.log(data);
													apiService.addCommViaAfricasTalking(data,function(response, status){
														var result = angular.fromJson( response );
														if( result.response == 'success' )
														{
															console.log("Message sent successfully.",result);
														}
														else
														{
															$scope.error = true;
															$scope.errMsg = result.data;
														}
													}, apiError);
												}else{
													// use new method to post sms
													// ie
												}
									}else{console.log("An error was encountered retrieving sms data",result.data);}

								}, apiError);
                    });

            	}
            	// POST SMS - END
							/*
            	$.ajax({
                    type: "POST",
                    url: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/srvScripts/postNotifications.php",
                    data: { school: window.location.host.split('.')[0] },
                    success: function (data, status, jqXHR) {
                        console.log("Notifications initiated.",data,status,jqXHR);
                    },
                    error: function (xhr) {
                        console.log("Error. Notifications could not be sent.");
                    }
                });
								*/

            });
        },5000);

        // delete message function
        $("#deleteSelectedMsgs").click(function(){
                var approveList = []; // messages to be deleted
                $.each($("input[name='approveMsgs']:checked"), function(){
                    approveList.push($(this).val());
                });

                approveList.forEach(function(msgId) {

                    apiService.deleteCommunication(msgId,function(response){
            			var result = angular.fromJson( response );
            			if( result.response == 'success' )
            			{
            				alert("Success. The selected message(s) were deleted.");

            			}
            		},apiError);

                });


            });

						/* MANUALLY SEND ALL NOTIFICATIONS BELOW */
						/*
						apiService.sendNotifications({}, function(response){
							var result = angular.fromJson(response);
							console.log(result);
							if( result.response == 'success')
							{
								console.log("Success. Mobile App Notifications Have Been Sent To Respective Parents!",result);
							}

						}, function(response){
							console.log("Notifications error:",response);
						});
						*/

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

	$scope.batchInvoiceBalDlg = function()
	{

		var data = {
			// classes: $scope.classes,
			// terms: $scope.terms
		}
		$scope.openModal('communications', 'batchInvoice', 'lg', data);
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
				item.creation_date = new Date(item.creation_date);
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
    if( item.post_status_id === 1 )
    {
        // communication has been published, can no longer edit
        $scope.preview(item);
    }
    else
    {
      $state.go('communications/edit_post', {post: item, post_id: item.post_id, post_type: 'communication'});
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
