'use strict';

angular.module('eduwebApp').
controller('userFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.edit = ( data !== undefined ? true : false );
	$scope.user = data || {};
	$scope.enableSave = false;
	console.log($rootScope);

	$scope.checkSave = function(user){
		if(user.first_name == null || user.first_name == undefined || user.first_name == '' || user.first_name == ' '){ $scope.enableSave = false; }
		else if(user.last_name == null || user.last_name == undefined || user.last_name == '' || user.last_name == ' '){ $scope.enableSave = false; }
		else if(user.user_type == null || user.user_type == undefined || user.user_type == '' || user.user_type == ' '){ $scope.enableSave = false; }
		else if(user.phone == null || user.phone == undefined || user.phone == '' || user.phone == ' '){ $scope.enableSave = false; }
		else if(user.username == null || user.username == undefined || user.username == '' || user.username == ' '){ $scope.enableSave = false; }
		else if(user.password == null || user.password == undefined || user.password == '' || user.password == ' '){ $scope.enableSave = false; }
		else if(user.id_number == null || user.id_number == undefined || user.id_number == '' || user.id_number == ' '){ $scope.enableSave = false; }
		else if(user.gender == null || user.gender == undefined || user.gender == '' || user.gender == ' '){ $scope.enableSave = false; }
		else if(user.dob == null || user.dob == undefined || user.dob == '' || user.dob == ' '){ $scope.enableSave = false; }
		else if(user.emp_cat_id == null || user.emp_cat_id == undefined || user.emp_cat_id == '' || user.emp_cat_id == ' '){ $scope.enableSave = false; }
		else if(user.dept_id == null || user.dept_id == undefined || user.dept_id == '' || user.dept_id == ' '){ $scope.enableSave = false; }
		else{ $scope.enableSave = true; }
	}

	function getEmpCats(){
		$scope.empCats = $rootScope.empCats;
		$scope.empDepts0 = $rootScope.allDepts;
		console.log('Employee Cats >',$scope.empCats);
		console.log('Employee Depts >',$scope.empDepts0);
		$scope.empDepts = null;
	}

	$scope.getDepts = function(emp_cat_id){
		let category = $scope.empCats.filter(function (el) { return el.emp_cat_id == emp_cat_id});
		$scope.empDepts = $scope.empDepts0.filter(function (el) { return el.category == category[0].emp_cat_name});
	}

	$scope.initializeController = function()
	{
		getEmpCats();
	}
	$scope.initializeController();

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	$scope.save = function(form)
	{
		if ( !form.$invalid )
		{
			var data = $scope.user;
			data.current_user_id = $rootScope.currentUser.user_id;

			if( $scope.edit )
			{
				apiService.updateUser(data,createCompleted,apiError);
			}
			else
			{
				data.initials = data.first_name[0] + '.' + (data.middle_name && data.middle_name != ' ' ? data.middle_name[0] : '') + '.' + data.last_name[0];
				console.log('Posting Data >',data);
				apiService.addUser(data,createCompleted,apiError);
			}


		}
	}
	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		console.log('Result >',result);
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'User was updated.' : 'User was added.');
			$rootScope.$emit('userAdded', {'msg' : msg, 'clear' : true});
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
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	$scope.deleteUser = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to mark this user as deleted? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				current_user_id : $rootScope.currentUser.user_id,
				user_id: $scope.user.user_id,
				status: 'f'
			}
			apiService.setUserStatus(data,createCompleted,apiError);

		});
	}

	$scope.activateUser = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to re-activate this user? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				current_user_id : $rootScope.currentUser.user_id,
				user_id: $scope.user.user_id,
				status: 't'
			}
			apiService.setUserStatus(data,createCompleted,apiError);

		});
	}
	// make the modal wider to accomodate more inputs
	setTimeout(function(){ document.getElementsByClassName('modal-dialog')[0].style.width = '75vw'; }, 2000);

} ]);
