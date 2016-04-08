'use strict';

// Configure the main application module.
var eduwebApp = angular.module('eduwebApp', ['ui.router', 'ui.bootstrap', 'dialogs.main', 'daterangepicker', 'ui.select', 'angularFileUpload'])
/*Constants regarding user login defined here*/
.constant('USER_ROLES', {
	all : '*',
	admin : 'ADMIN',
	parent : 'PARENT',
	staff : 'STAFF',
	teacher : 'TEACHER',
	sys_admin : 'SYS_ADMIN'
}).constant('AUTH_EVENTS', {
	loginSuccess : 'auth-login-success',
	loginFailed : 'auth-login-failed',
	logoutSuccess : 'auth-logout-success',
	sessionTimeout : 'auth-session-timeout',
	notAuthenticated : 'auth-not-authenticated',
	notAuthorized : 'auth-not-authorized',
	updatePwd: 'update-pwd'
})
/* Adding the auth interceptor here, to check every $http request*/
.config(function ($httpProvider) {
  $httpProvider.interceptors.push([
    '$injector',
    function ($injector) {
      return $injector.get('AuthInterceptor');
    }
  ]);
})
eduwebApp.filter('numeric', function($filter) {
    return function (value) {
        if (value < 0) {
			value =  Math.abs(value);
			value = $filter('currency')(value, "");
            value = '(' +value + ')';
			return value;
        }
        else{
			value = $filter('currency')(value, "");
			return value;
		}
    };
});
eduwebApp.filter('titlecase', function() {
    return function(s) {
        s = ( s === undefined || s === null ) ? '' : s;
        return s.toString().toLowerCase().replace( /\b([a-z])/g, function(ch) {
            return ch.toUpperCase();
        });
    };
})

eduwebApp.filter('arrayToList', function(){
	return function(arr) {
		if( arr instanceof Array ) return arr.join(', ');
		else return arr;
	}
});

eduwebApp.filter('makePositive', function() {
    return function(num) { return Math.abs(num); }
});



