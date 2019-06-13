'use strict';

angular.module('eduwebApp').
controller('previewFeedbackCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, data){

	$scope.type = data.type;
	$scope.post = angular.copy(data.post);

    console.log($scope.post);
    
    var loadEmployees = function(response)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.employees = ( result.nodata ? {} : result.data );
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}

	}
	
    apiService.getAllEmployees(true, loadEmployees, apiError);
    
	if( $scope.post.details === undefined ) $scope.post.details = data.post;
    
    // hide the reply section until button is clicked
	$scope.replySection = false;
	$scope.reply = function() 
	{
	    $scope.replySection = true;
	}
	
	$scope.postReply = function()
	{
		// acquire the form values
	    var theSubject = $('#subject').val();
	    var theMessage = $('#message').val();
	    var msgFrom = $('#message_from').val();
	    
	    // push the values to an object
	    var fdbackObj = {};
		
		fdbackObj.title = theSubject;
		fdbackObj.body = theMessage;
		fdbackObj.message_from = parseInt(msgFrom);
		
        // add non-form properties to the object
        fdbackObj.guardian_id = $scope.post.guardian_id;
        fdbackObj.student_id = $scope.post.student_id;
        fdbackObj.audience_id = 5;
        fdbackObj.com_type_id = 1;
        fdbackObj.post_status_id = 1;
        fdbackObj.send_as_email = 't';
        fdbackObj.send_as_sms = 'f';
        fdbackObj.reply_to = "Mobile App";
        console.log("Feedback object",fdbackObj);
        console.log("Scope",$scope);
        // console.log("Root scope",$rootScope);
        
        var data = {
						user_id: $rootScope.currentUser.user_id,
						post: fdbackObj
					}
		console.log("Data Post",data);
        apiService.addCommunication(data,createCompleted,apiError);
	};

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel
	
	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			alert("Reply sent successfully to " + $scope.post.parent_full_name);
            $uibModalInstance.close();
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
		console.log("ERROR ENCOUNTERED",result);
		$scope.error = true;
		$scope.errMsg = ( result.data == null | result.data == undefined ? response : result.data );
		$scope.loadingPost = false;
		$scope.saving = false;
	}

} ]);
