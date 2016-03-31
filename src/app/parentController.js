'use strict';

angular.module('eduwebApp').
controller('ParentController', ['$scope', '$rootScope', '$uibModal', 'dialogs', 'Auth', 'AUTH_EVENTS','USER_ROLES','$filter','$state','apiService',
function($scope, $rootScope, $uibModal, $dialogs, Auth, AUTH_EVENTS, USER_ROLES, $filter,$state,apiService){
	// this is the parent controller for all controllers.
	// Manages auth login functions and each controller
	// inherits from this controller	

	
	$scope.modalShown = false;
	$rootScope.updatePwd = false;
	
	var showLoginDialog = function(args) {
		//console.log(args);
		if(!$scope.modalShown){
			$scope.modalShown = true;
			var modalInstance = $uibModal.open({
				templateUrl : 'app/login.html',
				controller : "LoginCtrl",
				backdrop : 'static',
				resolve: {
				 token: function () {
				   return args.token;
				 }
			   }
			});

			modalInstance.result.then(function() {
				$scope.modalShown = false;
			  }, function() {
				$scope.modalShown = false;
			  })['finally'](function(){
				$scope.modalInstance = undefined  // <--- This fixes
			  });
			
		}
	};
	
	var setCurrentUser = function(){
		//$scope.currentUser = $rootScope.currentUser;
		$rootScope.permissions = [];

		switch( $rootScope.currentUser.user_type ){
			case "SYS_ADMIN":
				$rootScope.permissions = {
					'dashboard':{
						'view': true,
					},
					'students':{
						'view': true,
						'add': true,
						'edit': true,
						'import': true
					},
					'staff':{
						'view': true,
						'add': true,
						'edit': true,
						'import': true
					},
					'fees':{
						'dashboard': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'opening_balances': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'payments_received': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'fee_structure': {
							'view': true,
							'add': true,
							'edit': true,
						},
					},
					'school':{
						'departments': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'classes': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'subjects': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'grading': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'school_dates': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'school_settings': {
							'view': true,
							'add': true,
							'edit': true,
						},
					},
					'exams':{
						'view': true,
						'add': true,
						'edit': true,
						'import': true
					},
					'news':{
						'view': true,
						'add': true,
						'edit': true,
					}
					
				};
				break;
			
			default:
				$rootScope.permissions = {
					dashboard:{
						view: true,
					}
				};
		}
		
		$scope.navItems = [];
		$scope.subOptions = [];
		var i = 0,
		    j = 0;

		angular.forEach( $rootScope.permissions, function(permission, sectionName){
			// if no view permission, likely an object of arrays, dig deeper

			if( permission.view === undefined )
			{
				var navItem = {};
				var subnavItem = {};
				angular.forEach( permission, function(permission2, subSectionName){
				/*
					if( permission2.options )
					{
						if( i == 0 ) navItem = {id: sectionName + "/" + subSectionName + "/" + permission2.options[0].name, label: $filter('titlecase')(sectionName.split("_").join(" ")), section: sectionName, subnav: [], icon: icons[sectionName]};
					
						navItem.subnav.push({id: sectionName + "/" + subSectionName + "/" + permission2.options[0].name, label: $filter('titlecase')(subSectionName.split("_").join(" ")), section: sectionName + '/' + subSectionName, subSection: subSectionName, options:[]});
						angular.forEach( permission2.options, function(option){
							navItem.subnav[i].options.push({id: sectionName + "/" + subSectionName + "/" + option.name, label: $filter('titlecase')((option.name).split("_").join(" ")), filters: option.filters});	
						});
					}
					else
					{
				*/
						if( i == 0 ) navItem = {id: sectionName + "/" + subSectionName, label: $filter('titlecase')(sectionName.split("_").join(" ")), section: sectionName, subnav: []}; //, icon: icons[sectionName]};
					
						navItem.subnav.push({id: sectionName + "/" + subSectionName, label: $filter('titlecase')(subSectionName.split("_").join(" ")), section: sectionName + '/' + subSectionName, subSection: subSectionName}); //, filters:permission2.filters});
				/*	
					}
				*/
					i++;

					
				});

				$scope.navItems.push(navItem);
				
			}
			else
			{
				if( permission.view )
				{
						$scope.navItems.push({id: sectionName, label: $filter('titlecase')(sectionName.split("_").join(" ")), section: sectionName}); //, icon: icons[sectionName]});	
				}
			}
			
			i = 0;
		});

		$rootScope.navItems = $scope.navItems;
		
		var section = $rootScope.currentPage;
		section = section.split('/');
		var page = section[0];
		var params = section[1];
		//var options = section[2];

		angular.forEach( $rootScope.navItems, function( item, key) {
			var section = item.section;
			if( section.toUpperCase() == page.toUpperCase() )
			{
				$rootScope.mainSubNavItems = item.subnav;
			}
		});
		/*
		if( params !== undefined )
		{
			angular.forEach( $rootScope.mainSubNavItems, function( item, key) {
				var subSection = item.subSection;

				if( subSection.toUpperCase() == params.toUpperCase() )
				{
					//console.log(item);
					if( item.filters )
					{
						$rootScope.pageFilters = item.filters;
					}
					else if( item.options )
					{
						$rootScope.pageOptions = item.options;
						
						angular.forEach( $rootScope.pageOptions, function( item, key) {
							if( item.id == $rootScope.currentPage )
							{
								$rootScope.pageFilters = item.filters;
							}
						});
						if( $rootScope.pageFilters === undefined ) $rootScope.pageFilters = $rootScope.pageOptions[0].filters;
					}
				}
			});
		}
		*/
		
	}
	
	var showNotAuthorized = function()
	{
		alert("Not Authorized");
	}
	
	var showLoginError = function (args) 
	{
		$rootScope.$broadcast('displayLoginError', args);
	}
	
	var showUpdatePwdForm = function()
	{
		$rootScope.$broadcast('displayLoginError');
		$rootScope.updatePwd = true;
		var dlg = $dialogs.create('updatePwd.html','updatePwdCtrl',{user:$rootScope.currentUser},{size: 'md',backdrop:'static'});
		dlg.result.then(function(result){
			// if success, show the login box again and have them login
			// show message
			$rootScope.$broadcast('pwdUpdatedMsg');
			
			
		},function(){
			
		});
	}
	
	var goHome = function()
	{
		$rootScope.loggedIn = false;
		$state.go('index');
	}
	
	//$scope.currentUser = null;
	$scope.userRoles = USER_ROLES;
	$scope.isAuthorized = Auth.isAuthorized;

	//listen to events of unsuccessful logins, to run the login dialog
	$rootScope.$on(AUTH_EVENTS.notAuthorized, showLoginDialog);
	$rootScope.$on(AUTH_EVENTS.notAuthenticated, showLoginDialog);
	$rootScope.$on(AUTH_EVENTS.sessionTimeout, showLoginDialog);
	$rootScope.$on(AUTH_EVENTS.logoutSuccess, goHome);
	$rootScope.$on(AUTH_EVENTS.loginSuccess, setCurrentUser);
	$rootScope.$on(AUTH_EVENTS.loginFailed, function(event,args){showLoginError(args); });
	$rootScope.$on(AUTH_EVENTS.updatePwd, showUpdatePwdForm);
	
	$scope.openModal = function (section, view, size, item) 
	{
		
		if( $('#filterLinks').hasClass('in') )
		{
			$('#subnav').trigger('click');
		}
			
		if( !$scope.modalShown )
		{
			$scope.modalShown = true;
			var controller = view + 'Ctrl'; 
			if (size === undefined ) size = 'lg';
			var dlg = $dialogs.create(
				'app/' + section + '/' + view + '.html',
				controller,
				item,
				{
					keyboard: true,
					backdrop: 'static',
					size: size,
				}
			);
			dlg.result.then(function(data){
			  // save
			  $scope.modalShown = false;
			  $rootScope.isSearchModal = false;
			  $rootScope.printModal = false;
			},function(){
			  // cancel, close, no save
			  $scope.modalShown = false;
			  $rootScope.isSearchModal = false;
			  $rootScope.printModal = false;
			});
			$rootScope.theModal = dlg;
		}
		
	};
	

	$rootScope.chartColors = ['rgba(151,187,205,1)','rgba(220,220,220,1)','rgba(247,70,74,1)','rgba(70,191,189,1)','rgba(253,180,92,1)','rgba(148,159,177,1)','rgba(77,83,96,1)','rgba(181,221,56,1)','rgba(218,150,240,1)'];


} ])
