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
	$scope.enPub = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' || $rootScope.currentUser.user_type == 'FINANCE_CONTROLLED' ? true : false );

	if( $rootScope.currentUser.user_type == 'SYS_ADMIN' || $rootScope.currentUser.user_type == 'FINANCE_CONTROLLED' ){
	    //enable an unpublish button
	    $scope.enUnPub = ( $scope.post.sent == true ? true : false );

	    //this message's id
	    var msgId = {
			post_id: $scope.post.post_id
		}

	    //the actual publishing
	    $scope.pubPost = function()
    	{
    	    // POST SMS - START
                $scope.unpublishedSms = false;
        		// check if this message is an unpublished sms
        		if( $scope.post.send_as_sms == true && $scope.post.sent == false )
            	{
            	    $scope.unpublishedSms = true;
            	}

            	if($scope.unpublishedSms == true){

            	    // post the message

                    $.ajax({
                        type: "POST",
                        url: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/postSms.php",
                        data: { src: $scope.post.post_id, school: window.location.host.split('.')[0] },
                        success: function (data, status, jqXHR) {
                            console.log("Data posted for processing.",data,status,jqXHR);
                        },
                        error: function (xhr) {
                            console.log("Error. Data not posted.");
                        }
                    });

                    // Below is the old method of doing it - note there are 2 xhr methods, use one or the other if reverting to it
                    /*
            	    apiService.getCommunicationForSms($scope.post.post_id, function(response, status){
        					var result = angular.fromJson(response);

        					if( result.response == 'success')
        					{

        						$scope.smsData = result.data;
        						console.log($scope.smsData);

        						// this is a delay function - we'll use it to pause & wait for an ajax response
                                function sleep(milliseconds) {
                                    var start = new Date().getTime();
                                    for (var i = 0; i < 1e7; i++) {
                                        if ((new Date().getTime() - start) > milliseconds){
                                            break;
                                        }
                                    }
                                 }
                                 // end delay function

        						var buildSmsToPost = {
                                    "message_by": $scope.smsData[0].message_by,
                                    "message_date": $scope.smsData[0].message_date,
                                    "message_recipients": [],
                                    "message_text": $scope.smsData[0].message_text,
                                    "subscriber_name": window.location.host.split('.')[0]
                                };

                                // insert recipients into 'message_recipients'
                                for (var v = 0; v < $scope.smsData.length; v++) {
                                    buildSmsToPost.message_recipients.push({
                                        "phone_number": "+254" + $scope.smsData[v].phone_number,
                                        "recipient_name": $scope.smsData[v].recipient_name
                                    });
                                }

                                console.log("Our built message :::",buildSmsToPost);

                                // We need to divide the recipients into groups of less than 99 recipients, messages to over 99 fail
                                var recipientLength = Object.keys(buildSmsToPost.message_recipients).length;
                                // console.log("The message has (" + recipientLength + ") keys.");

                                // In our case we will do groups of 80 recipients

                                var messageRep = buildSmsToPost.message_recipients.slice();
                                for(var i = 0; i < recipientLength; i+=80){
                                    buildSmsToPost.message_recipients = messageRep.slice(i, i+80);
                                    // console.log(buildSmsToPost.message_recipients);
                                    // We can now create a new message object using the smaller recipient groups
                                    var newMessage = {
                                      "message_by": buildSmsToPost.message_by,
                                      "message_date": buildSmsToPost.message_date,
                                      "message_recipients": buildSmsToPost.message_recipients,
                                      "message_text": buildSmsToPost.message_text,
                                      "subscriber_name": buildSmsToPost.subscriber_name
                                    };
                                    // console.log(newMessage);

                                  // jquery ajax method
                                  // var url = "http://41.72.203.166/sms_api_staging/api/sendBulkSms";
                                  var url = "https://sms_api.eduweb.co.ke/api/sendBulkSms";
                                  $.ajax({
                                          type: "POST",
                                          url: url,
                                          data: JSON.stringify(newMessage),
                                          contentType: "application/json; charset=utf-8",
                                          dataType: "json",
                                          processData: true,
                                          success: function (data, status, jqXHR) {
                                              console.log("Success Func. Msg Sent",data,status,jqXHR);
                                          },
                                          error: function (xhr) {
                                              console.log("Error Func. Probably a false positive");
                                              console.log(xhr);
                                              // Do not alert() an error message to the user as often times the api
                                              // may delay with a response therefore output an error. This is a false negative
                                              // since the messages are already successfully sent.
                                              // alert("Success. Message Sent.");
                                          }
                                  });

                                  // plain js xhr method
                                    var xhr = new XMLHttpRequest();
                                    xhr.open("POST", 'https://sms_api.eduweb.co.ke/api/sendBulkSms', true);
                                    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
                                    xhr.onreadystatechange = function() {
                                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                                                console.log("Request sent. Wait for response object ........");
                                                console.log(xhr);
                                        }
                                    }
                                    xhr.send(JSON.stringify(newMessage));

                                    // before continuing the loop we need to wait a bit - trying 1.5s
                                    console.log("Waiting 1.5s ...");
                                    sleep(1500);
                                }

        					}
        					else
        					{
        						$scope.error = true;
        						$scope.errMsg = result.data;
        					}
        				}, apiError);
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
    console.log($scope.attachments);

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
