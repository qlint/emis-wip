'use strict';

angular.module('eduwebApp').
controller('previewPostCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'data', 'apiService',
function($scope, $rootScope, $uibModalInstance, data, apiService){

	$scope.type = data.type;
	$scope.post = angular.copy(data.post);
	$scope.enUnPub = undefined;
	console.log($scope.post);

	// for (var i = 0; i < data.post.attachment.length; i++){
		$scope.post.attachment = data.post.attachment;
	// }
	
	//this block allows a sys_admin to 'publish' messages posted by other users
	//BEGIN
	
	//enable or disable publishing
	$scope.enPub = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );
	
	if( $rootScope.currentUser.user_type == 'SYS_ADMIN' ){
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
                                        "phone_number": $scope.smsData[v].phone_number,
                                        "recipient_name": $scope.smsData[v].recipient_name
                                    });
                                }
                                
                                console.log("Our built message :::",buildSmsToPost);
                                /*
                                  We need to determine the number of keys/properties (recipients) in the object
                                  -this is because the sms api for some reason won't post messages with over 99 recipients, else
                                  we'd just post the object as it is at this point
                                */
                                var recipientLength = Object.keys(buildSmsToPost.message_recipients).length;
                                console.log("The message has (" + recipientLength + ") keys.");
                                
                                /*
                                   Create a variable to divide the above object to a predetermined number less than 100
                                   in our case we'll use a safe number of 80 so that for messages with many recipients
                                   the messages will be sent to 80 at a time until it's over
                                */
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
                                    console.log(newMessage);
                                    
                                  // Post the message
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
                                              console.log("Success Func. Msg Sent");
                                              console.log(data);
                                              console.log(status);
                                              console.log(jqXHR);
                                              //alert("success..." + data);
                                              //alert("Success. Message sent.");
                                          },
                                          error: function (xhr) {
                                              console.log("Error Func. Probably a false positive");
                                              console.log("Batch number " + i);
                                              console.log(xhr);
                                              // Do not alert() an error message to the user as often times the api
                                              // may delay with a response therefore output an error. This is a false negative
                                              // since the messages are already successfully sent.
                                              // alert("Success. Message Sent.");
                                          }
                                  });
                                  
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
	
	if( $scope.post.details === undefined ) $scope.post.details = data.post;

	var showName = $scope.post.details.audience;
	localStorage.setItem("theParentName", attachments);
	var exportParentName = localStorage.setItem("theParentName", showName);
	var returnParentName = localStorage.getItem("theParentName");
	console.log($scope.post.details);

	var attachments = data.post.attachment;
	localStorage.setItem("attachmentsList", attachments);
	var testing12 = localStorage.setItem("attachmentsList", attachments);
	var returntestresults = localStorage.getItem("attachmentsList");
	// console.log("Testing 1-2 || " + returntestresults);
	// console.log(attachments);

    if($scope.attachments !== undefined && $scope.attachments.length > 0){
	    $scope.attachments = attachments.split(',');
    }

	// console.log($scope.attachments[0]);

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
