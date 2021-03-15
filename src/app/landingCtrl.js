'use strict';

angular.module('eduwebApp')
.controller('landingCtrl', [ '$scope', '$rootScope', '$state', '$window', 'Auth', 'apiService',
function($scope, $rootScope, $state, $window, Auth, apiService, token ) {
	$scope.credentials = {};
	$scope.loginForm = {};
	$scope.error = false;
	$scope.loggingIn = false;

	$scope.phoneInp = true;
	$scope.phoneWaiting = false;
	$scope.phoneCode = false;
	$scope.enteredPhone = null;
	$scope.errDesc = false;
	$scope.usrPhone = null;

	var initializeController = function ()
	{
		$scope.pwdDesc = "Did you forget your password? We could help you reset it. Enter the phone number known to your school below.";
		$(document).ready(function(){
		  $("#fgtPwdBtn").click(function(){
		    $("#forgotPwdModal").modal();
				$scope.phoneInp = true;
		  });
		});
		$scope.nxtStatus = "phone";
	}
	setTimeout(initializeController,10);

	// check if school has dual links
	apiService.checkMultiLinks(window.location.host.split('.')[0], function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.multiLink = result.data;
					if($scope.multiLink != undefined){
    					if($scope.multiLink[0].link_status == "multi-link"){
    					    $rootScope.showMultiLink = true;
    					    document.getElementById('multi-school').style.display = "block";
    					    document.getElementsByClassName('navbar-brand')[0].style.width = '100%';
    					    $rootScope.multiLink = $scope.multiLink[0].multi_link;
    					    $rootScope.multiLinkSchoolName = $scope.multiLink[0].school_name;
    					}
				    }
				}

			}, console.log("%cEduweb School Management Information System", "color: #00ff00; font-size:30px;"));

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

	}; // end cancel


	//Performs the login function, by sending a request to the server with the Auth service
	$scope.login = function(credentials)
	{
		$scope.error = false;

		Auth.login(credentials, function(user) {
			//success function

			$scope.loggingIn = false;
			$scope.loggedIn = true;

			// check to see if they have set up their school, if not take them to the settings page to get started
			if( $rootScope.currentUser.user_type == 'PARENT' ) $state.go('portal_dashboard');
			else
			{
				if( $rootScope.currentUser.settings['School Name'] === undefined )
				{
					$state.go('school/school_settings');
				}
				else
				{
					$state.go('dashboard');
				}
			}

		}, function(err) {
			$scope.credentials.user_pwd = '';
			$scope.error = true;
			$scope.loggingIn = false;
		});


	};

	$scope.fgtPwdModal= function(){
		document.getElementById("nextBtn").disabled = false;
		$scope.nxtStatus = "phone";
	}

	$scope.forgotPwdCode = function(){
		console.log($scope.nxtStatus);
		if($scope.nxtStatus == "phone"){
			let phoneEntry = document.getElementById('enteredPhone').value;
			if(phoneEntry == null || phoneEntry.length < 10){
				$scope.errDesc = true;
				$scope.nextErr = "Please enter a valid phone number to continue";
			}else{
				$scope.errDesc = false;
				console.log(phoneEntry);
				$scope.usrPhone = phoneEntry;
				$scope.phoneInp = false;
				$scope.phoneWaiting = true;
				$scope.pwdDesc = "Checking if the phone number you have entered is known to your school.";

				apiService.fgtPwd(phoneEntry, function(response){
					var result = angular.fromJson(response);
					//console.log(result.data);
					if( result.response == 'Success')
					{
						var pwdResp = result;
						console.log(pwdResp);
						$scope.pwdDesc = "Success! Enter the code that has been sent to your phone and click Next.";
						$scope.phoneWaiting = false;
						$scope.phoneCode = true;
						$scope.nxtStatus = "code";
						// $scope.usrPhone = pwdResp.phone;
						console.log("Entered Phone = " + $scope.usrPhone,pwdResp);
					}else{
						document.getElementById("nextBtn").disabled = true;
						$scope.errDesc = true;
						$scope.phoneWaiting = false;
						$scope.pwdDesc = "Oops!";
						$scope.nextErr = result.message + "\n\r Confirm the phone number and try again";
						console.log(result);
					}
				}, function(err){console.log("AN ERROR WAS ENCOUNTERED : ",err)});

			}
		}else if($scope.nxtStatus == "code"){
			let enteredCode = document.getElementById('enteredCode').value;
			let codeParam = $scope.usrPhone + '/' + enteredCode;
			console.log($scope,codeParam);

			apiService.confirmTemporaryPassword(codeParam, function(response){
				var result = angular.fromJson(response);
				//console.log(result.data);
				if( result.response == 'success' || result.response == 'Success')
				{
					var codeResp = result;
					$scope.pwdDesc = codeResp.message;
					document.getElementById("nextBtn").disabled = true;
					$scope.phoneCode = false;
					// $scope.nxtStatus = "code";
					// console.log(pwdResp);
				}else{
					console.log(result);
					// document.getElementById("nextBtn").disabled = true;
					// $scope.errDesc = true;
					// $scope.phoneWaiting = false;
					// $scope.pwdDesc = "Oops!";
					// $scope.nextErr = result.message + "\n\r Confirm the phone number and try again";
					// console.log(result);
				}
			}, function(err){console.log("AN ERROR WAS ENCOUNTERED : ",err)});
		}else if($scope.nxtStatus == "started"){
			let phone = document.getElementById('enteredPhone').value;
			let code = document.getElementById('enteredCode').value;
			let param = phone + '/' + code;
			apiService.confirmTemporaryPassword(param, function(response){
				var result = angular.fromJson(response);
				//console.log(result.data);
				if( result.response == 'success' || result.response == 'Success')
				{
					var codeResp = result;
					$scope.pwdDesc = codeResp.message;
					document.getElementById("nextBtn").disabled = true;
					$scope.phoneCode = false;
					// $scope.nxtStatus = "code";
					// console.log(pwdResp);
				}else{
					console.log(result);
					// document.getElementById("nextBtn").disabled = true;
					// $scope.errDesc = true;
					// $scope.phoneWaiting = false;
					// $scope.pwdDesc = "Oops!";
					// $scope.nextErr = result.message + "\n\r Confirm the phone number and try again";
					// console.log(result);
				}
			}, function(err){console.log("AN ERROR WAS ENCOUNTERED : ",err)});
		}
	}

	$scope.haveCode = function(){
		$scope.pwdDesc = "If you already received an sms with a code, enter it here along side your phone number that received the sms, then click next.";
		$scope.phoneWaiting = false;
		$scope.phoneCode = true;
		$scope.nxtStatus = "started";
	}

	$scope.enteredCode = function(){
		$scope.phoneWaiting = false;
		$scope.phoneCode = true;
	}

	$scope.clrModal = function(){
		let phoneEntry = null;
		$scope.phoneInp = true;
		$scope.phoneWaiting = false;
		$scope.phoneCode = false;
		$scope.enteredPhone = null;
		$scope.errDesc = false;
		$scope.usrPhone = null;
		$scope.pwdDesc = "Did you forget your password? We could help you reset it. Enter the phone number known to your school below.";
		document.getElementById('enteredPhone').value = null;
	}

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

} ]);
