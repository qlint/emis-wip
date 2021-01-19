'use strict';

angular.module('eduwebApp')
.controller('landingCtrl', [ '$scope', '$rootScope', '$state', '$window', 'Auth', 'apiService',
function($scope, $rootScope, $state, $window, Auth, apiService, token ) {
	$scope.credentials = {};
	$scope.loginForm = {};
	$scope.error = false;
	$scope.loggingIn = false;

	var initializeController = function ()
	{
	}
	//setTimeout(initializeController,10);

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
