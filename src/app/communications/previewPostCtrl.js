'use strict';

angular.module('eduwebApp').
controller('previewPostCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'data', 'apiService',
function($scope, $rootScope, $uibModalInstance, data, apiService){

	$scope.type = data.type;
	$scope.post = angular.copy(data.post);
	$scope.enUnPub = undefined;
	console.log($scope.post);

	$scope.post.rawAttachment = data.post.attachment;
	$scope.post.attachment = ($scope.post.rawAttachment != null ? $scope.post.attachment.split(',') : null);

	$scope.showAttachment = ($scope.post.rawAttachment == null ? false : true);
	console.log("Is there an attachment? " + $scope.showAttachment);

	//this block allows a sys_admin to 'publish' messages posted by other users
	//BEGIN

	//enable or disable publishing
	$scope.enPub = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' || $rootScope.currentUser.user_type == 'PRINCIPAL' || $rootScope.currentUser.user_type == 'FINANCE_CONTROLLED' ? true : false );

	if( $rootScope.currentUser.user_type == 'SYS_ADMIN' || $rootScope.currentUser.user_type == 'FINANCE_CONTROLLED' || $rootScope.currentUser.user_type == 'PRINCIPAL' ){
	    //enable an unpublish button
	    $scope.enUnPub = ( $scope.post.sent == true ? true : false );

	    //this message's id
	    var msgId = {
			post_id: $scope.post.post_id
		}

	    //the actual publishing
	    $scope.pubPost = function()
    	{
    	    console.log("Inside publish post");
    	    // POST SMS - START
                $scope.unpublishedSms = false;
        		// check if this message is an unpublished sms
        		if( $scope.post.send_as_sms == true && $scope.post.sent == false )
            	{
            	    $scope.unpublishedSms = true;
            	}

            	if($scope.unpublishedSms == true){
								// post the message
								$rootScope.postTxt($scope.post.post_id);
										/*
                    $.ajax({
                        type: "POST",
                        url: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/srvScripts/postSms.php",
                        data: { src: $scope.post.post_id, school: window.location.host.split('.')[0] },
                        success: function (data, status, jqXHR) {
                            console.log(data,status,jqXHR);
														location.reload();
                        },
                        error: function (xhr) {
                            console.log("Error. Data not posted.");
                        }
                    });
										*/
            	}
            	// POST SMS - END

    		apiService.publishMessage(msgId,function(response){
    			var result = angular.fromJson( response );
    			if( result.response == 'success' )
    			{
    				alert("Success. Message is now published and visible to the recipients.");
    				$uibModalInstance.close();
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
    	}

	    //unpublish emails (and sms) from app
	    $scope.unPubPost = function()
    	{
    		apiService.unPublishMessage(msgId,function(response){
    			var result = angular.fromJson( response );
    			if( result.response == 'success' )
    			{
    				alert("Success. Message is now nolonger published, hence not visible to the recipients.");
    				$uibModalInstance.close();
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
    	}
	}

	//END

	if( $scope.post.details === undefined ){
	    $scope.post.details = data.post;
	}

	var showName = $scope.post.details.audience;
	localStorage.setItem("theParentName", attachments);
	var exportParentName = localStorage.setItem("theParentName", showName);
	var returnParentName = localStorage.getItem("theParentName");
	console.log($scope.post.details);

	var attachments = data.post.attachment;

    $scope.attachments = ($scope.post.rawAttachment != null ? $scope.post.details.attachment.split(',') : null);
    // console.log($scope.attachments);

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}



} ]);
