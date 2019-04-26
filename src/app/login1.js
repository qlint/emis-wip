'use strict';

angular.module('eduwebApp')
.controller('LoginCtrl', [ '$scope', '$rootScope', '$state', '$uibModalInstance' , '$window', 'Auth', 'apiService',
function($scope, $rootScope, $state, $uibModalInstance, $window, Auth, apiService, token ) {
	$scope.credentials = {};
	$scope.loginForm = {};
	$scope.error = false;
	$scope.loggingIn = false;

	var initializeController = function ()
	{
	}
	//setTimeout(initializeController,10);

	$scope.hitEnter = function(evt)
	{
		$scope.submit();
	}; // end hitEnter

	//when the form is submitted
	$scope.submit = function()
	{
		$scope.submitted = true;
		$scope.loggingIn = true;

		if (!$scope.loginForm.$invalid) {
			$scope.login($scope.credentials);
		} else {
			$scope.error = true;
			$scope.errorMsg = "You must enter your login credentials below.";
			$scope.loggingIn = false;
			return;
		}
	};

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel


	//Performs the login function, by sending a request to the server with the Auth service
	$scope.login = function(credentials)
	{
		$scope.error = false;

		Auth.login(credentials, function(user) {
			//success function

			$scope.loggingIn = false;

			setTimeout(
				function(){
					$uibModalInstance.dismiss('cancel');
				}
			, 100);

			if( $rootScope.currentUser.user_type == 'PARENT' ) $state.go('portal_dashboard');
			else $state.go('dashboard');


		}, function(err) {
			$scope.credentials.user_pwd = '';
			$scope.error = true;
			$scope.loggingIn = false;
		});


	};

	$rootScope.$on('displayLoginError', function(event, args) {
		$scope.errorMsg = args.errorMsg;
		$scope.credentials.user_pwd = '';
		$scope.error = true;
		$scope.loggingIn = false;
	});

	$rootScope.$on('pwdUpdatedMsg', function(event, args) {
		$scope.credentials.user_pwd = '';
		$scope.submitted = false;
		$scope.error = false;
		$scope.pwdupdated = true;
		$scope.loggingIn = false;
		$scope.loginForm.user_pwd.$dirty = false;
		$scope.loginForm.user_pwd.$invalid = false;
		$('input[name=user_pwd]').focus();
	});



	// if a session exists for current user (page was refreshed)
	// log him in again
	/***** REMOVE ME : TEST *****
	var loginData = {"response":"success","data":{"user_name":"sealer","user_pwd":"123","user_type":"SEALER","user_id":3}};
	$window.sessionStorage["userInfo"] = JSON.stringify(loginData.data);
	****************************/
	if( $window.sessionStorage["userInfo"] )
	{
		var credentials = JSON.parse($window.sessionStorage["userInfo"]);
		$scope.login(credentials);
	}

} ])
.controller('updatePwdCtrl',[ '$scope','$uibModalInstance','data','$timeout','apiService',
 function($scope,$uibModalInstance,data,$timeout,apiService){
		//-- Variables --//
		$scope.user = data.user
		$scope.user.old_pwd = $scope.user.user_pwd;
		$scope.user.user_pwd = '';

		//-- Methods --//

		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel

		$scope.updatePwd = function(form){

			if( $scope.user.old_pwd == $scope.user.user_pwd )
			{
				form.user_pwd.$error.pwdnotnew = true;
			}
			else
			{
				// make API call to save password
				/*
				http://41.72.203.166/cargoview_dev/user_mngmt_api?request={"api_action":"update_pwd","user_name":"tom","new_user_pwd":"tom_kioko","user_id":1}
				*/
				var user_data = {
					"api_action": "update_pwd",
					"user_name" : $scope.user.user_name,
					"new_user_pwd" : $scope.user.user_pwd,
					"user_id" : $scope.user.user_id
				};

				var updateSuccess = function ( response, status, params )
				{

					var result = angular.fromJson( response );
					if( result.response == 'success' )
					{
						console.log("Password Updated Successfully");
					}
					else
					{
						console.log("Error. Pass not updated");
						$scope.error = true;
						$scope.errMsg = result.data;
					}
				}

				var apiError = function (response, status)
				{
					console.log("There is a problem somewhere");
				}

				apiService.postUserRequest(user_data,updateSuccess,apiError);

				// apiService.postUserRequest(angular.toJson(user_data), function (response, status) {
				// 	// back to login form
				// 	$uibModalInstance.close($scope.user);
				// },
				// function (response, status)
				// 	{
				// 		console.log(angular.fromJson( response ));
				// 		var result = angular.fromJson( response );
				// 		$scope.error = true;
				// 		$scope.notificationMsg = result.data;
				// 	});


			}

		}; // end save

		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.updatePwd();
		};

	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('updatePwd.html','<div class="modal-header modal-warning dialog-header-form">' +
		'<h4 class="modal-title"><span class="fa fa-exclamation-triangle"></span> Change Password</h4>' +
		'</div><div class="modal-body">' +
		'<ng-form name="updatePwdDialog" novalidate role="form" class="form-horizontal" method="post">' +
		'<div class="notification alert alert-danger" ng-show="error">{{notificationMsg}}</div>' +
		'<p>Your password has expired, please create a new password below.</p>' +
		'<div>' +
		  '<label for="user_pwd">Password</label>' +
		  '<div ng-class="{ \'has-error\' : updatePwdDialog.user_pwd.$dirty && updatePwdDialog.user_pwd.$error.pwdnotnew && (updatePwdDialog.user_pwd.$touched || updatePwdDialog.$submitted) }">' +
			'<input type="password" class="form-control immediate-help" name="user_pwd" ng-model="user.user_pwd" password-validate="{{user.old_pwd}}" required id="inputPassword">' +
			'<div class="input-help">' +
				'<h4>Password must meet the following requirements:</h4>' +
				'<ul>' +
				  '<li ng-class="pwdHasLetter">At least <strong>one letter</strong></li>' +
				  '<li ng-class="pwdHasNumber">At least <strong>one number</strong></li>' +
				 ' <li ng-class="pwdValidLength">At least <strong>8 characters long</strong></li>' +
				'</ul>' +
			 '</div>' +
			 '<p ng-show="updatePwdDialog.user_pwd.$dirty && updatePwdDialog.user_pwd.$error.pwdnotnew && (updatePwdDialog.user_pwd.$touched || updatePwdDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> You must enter a new password.</p>' +
		  '</div>' +
		'</div>' +
		'<div ng-class="{ \'has-error\' : updatePwdDialog.verify_password.$dirty && updatePwdDialog.verify_password.$error.pwmatch && (updatePwdDialog.verify_password.$touched || updatePwdDialog.$submitted) }">' +
		 '<label for="verify_password">Verify Password</label>'+
		 '<div>'+
			'<input type="password" class="form-control" name="verify_password" ng-model="user.verify_password" pw-check="inputPassword" required>'+
			'<p ng-show="updatePwdDialog.verify_password.$dirty && updatePwdDialog.verify_password.$error.pwmatch && (updatePwdDialog.verify_password.$touched || updatePwdDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Password does not match.</span>'+
		 '</div>' +
		'</div>'+
		'</ng-form></div>' +
		'<div class="modal-footer">' +
		 '<div class="pull-left alert alert-danger col-sm-8" ng-show="error">{{msg}}</div>' +
		 '<button type="button" class="btn btn-default" ng-click="cancel()">Cancel</button>' +
		 '<button type="button" class="btn btn-primary" ng-click="updatePwd(updatePwdDialog)">Update</button>' +
		'</div>');
}]);
