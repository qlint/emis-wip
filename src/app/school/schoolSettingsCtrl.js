'use strict';

angular.module('eduwebApp').
controller('schoolSettingsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','FileUploader','dialogs',
function($scope, $rootScope, apiService, $timeout, $window, $filter, FileUploader, $dialogs){


	$scope.alert = {};

	var initializeController = function () 
	{
		//var deptCats = $rootScope.currentUser.settings['Department Categories'];
		//$scope.deptCats = deptCats.split(',');
		
		$scope.schoolTypes = ['Private School','Public School'];
		$scope.curriculums = ['8-4-4'];
		$scope.currencies = ['Ksh'];
		$scope.schoolLevels = ['Primary','Secondary'];
		
		
		if( $rootScope.currentUser.settings['School Name'] === undefined ) $scope.initialSetup = true;

		setSettings();
		
	}
	$timeout(initializeController,1);
	
	var getSettings = function()
	{
		// update the users settings
		apiService.getSettings({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var settings = result.data.reduce(function ( total, current ) { 
					total[ current.name ] = current.value;
					return total;
				}, {});
				
				$rootScope.$emit('setSettings', settings);
				setSettings();
			}		
			
		},apiError);
	}
	
	var setSettings = function()
	{
		$scope.settings	= {
			'Address 1' : angular.copy($rootScope.currentUser.settings['Address 1']),
			'Address 2' : angular.copy($rootScope.currentUser.settings['Address 2']),
			'Country' : angular.copy($rootScope.currentUser.settings['Country']),
			'Curriculum' : angular.copy($rootScope.currentUser.settings['Curriculum']),
			'Email Address' : angular.copy($rootScope.currentUser.settings['Email Address']),
			'Email From' : angular.copy($rootScope.currentUser.settings['Email From']),
			'Phone Number' : angular.copy($rootScope.currentUser.settings['Phone Number']),
			'School Name' : angular.copy($rootScope.currentUser.settings['School Name']),
			'School Type' : angular.copy($rootScope.currentUser.settings['School Type']	),
			'School Level' : angular.copy($rootScope.currentUser.settings['School Level']	),
			'logo' : angular.copy($rootScope.currentUser.settings['logo']	),
			'Currency' : angular.copy($rootScope.currentUser.settings['Currency']	),
		}
	}
	
	$scope.$watch('uploader.queue[0]', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		$scope.schoolForm.$setDirty();
	});
	
	$scope.save = function(theForm)
	{
		$scope.error = false;
		$scope.errMsg = '';
		
		if( !theForm.$invalid )
		{
			// do logo upload
			if( uploader.queue[0] !== undefined )
			{
				uploader.queue[0].file.name = uploader.queue[0].file.name;
				uploader.uploadAll();
			}
			
			var settings = [];
			angular.forEach( $scope.settings, function(item,key){
				
				if( uploader.queue[0] !== undefined && key == 'logo' )
				{
					settings.push({
						name: 'logo',
						value: 'assets/schools/' + uploader.queue[0].file.name,
						append: false
					})
				}
				else
				{
					settings.push({
						name: key,
						value: item,
						append: false
					})
				}
			});
			
			var postData = {
				settings: settings
			}
			$scope.saving = true;
			apiService.updateSettings(postData, createCompleted, apiError);
		}
	}
	
	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$scope.initialSetup = false;
			getSettings();
			$scope.schoolForm.$setPristine();
			$scope.saving = false;
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.addEmpCat = function()
	{
		var dlg = $dialogs.create('addEmpCategory.html','addEmpCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.empCats = undefined;
			$rootScope.getEmpCats();		
		},function(){
			
		});
	}
	
	$scope.editEmpCat = function(item)
	{
		var dlg = $dialogs.create('addEmpCategory.html','addEmpCategoryCtrl',{item:item},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.empCats = undefined;
			$rootScope.getEmpCats();		
		},function(){
			
		});
	}
	
	$scope.removeEmpCat = function(item)
	{
		var dlg = $dialogs.confirm('Delete Employee Category','You are deleting employee category <strong>' + item.emp_cat_name + '</strong>, do you wish to continue?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				emp_cat_id: item.emp_cat_id,
				status: 'f'
			}
			apiService.setEmployeeCatStatus(data, function(response, status){
				var result = angular.fromJson(response);
			
				if( result.response == 'success')
				{	
					$rootScope.empCats = undefined;
					$rootScope.getEmpCats();
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, apiError);
		});
	}
	
	$scope.addClassCat = function()
	{
		var dlg = $dialogs.create('addClassCategory.html','addClassCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.classCats = undefined;
			$rootScope.getClassCats();					
		},function(){
			
		});
	}
	
	$scope.editClassCat = function(item)
	{
		var dlg = $dialogs.create('addClassCategory.html','addClassCategoryCtrl',{item:item},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.classCats = undefined;
			$rootScope.getClassCats();					
		},function(){
			
		});
	}
	
	$scope.removeClassCat = function(item)
	{
		console.log(item);
		var dlg = $dialogs.confirm('Delete Class Category','You are deleting class category <strong>' + item.class_cat_name + '</strong>, do you wish to continue?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				class_cat_id: item.class_cat_id,
				status: 'f'
			}
			apiService.setClassCatStatus(data, function(response, status){
				var result = angular.fromJson(response);
			
				if( result.response == 'success')
				{	
					$rootScope.classCats = undefined;
					$rootScope.getClassCats();
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, apiError);
		});
	}		

	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'schools'
			}]
    });
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });

} ])
.controller('addEmpCategoryCtrl',[ '$scope','$rootScope','$uibModalInstance','apiService','dialogs','data',
function($scope,$rootScope,$uibModalInstance,apiService,$dialogs,data){
		
		$scope.edit = (data.item !== undefined ? true : false);
		$scope.empCat = data.item || {};
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			if( $scope.edit )
			{
				var dlg = $dialogs.confirm('Update Employee Category','Are you sure you want to update this employee category? It will also update all employees that are associated with this category.', {size:'sm'});
				dlg.result.then(function(btn){
					var data = {
						emp_cat_id : $scope.empCat.emp_cat_id,
						emp_cat_name : $scope.empCat.emp_cat_name,
						user_id: $rootScope.currentUser.user_id
					}
					apiService.updateEmployeeCat(data, createCompleted, apiError);
				});
			}
			else
			{
				var data = {
					emp_cat_name : $scope.empCat.emp_cat_name,
					user_id: $rootScope.currentUser.user_id
				}
				apiService.addEmployeeCat(data, createCompleted, apiError);
			}
			
		}; // end save
		
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$uibModalInstance.close(result.data);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		}
		
		
		var apiError = function(response,status)
		{
			var result = angular.fromJson( response );
			$scope.error = true;
			$scope.errMsg = result.data;
		}
		
		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.save();
		};
		

	
	
	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addEmpCategory.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> {{ (edit ? \'Update\' : \'Add\') }} Employee Category</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="catDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- emp_cat_name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : catDialog.emp_cat_name.$invalid && (catDialog.emp_cat_name.$touched || catDialog.$submitted) }">' +
						'<label for="emp_cat_name" class="col-sm-3 control-label">Employee Category Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="emp_cat_name" ng-model="empCat.emp_cat_name" class="form-control" required >' +
							'<p ng-show="catDialog.emp_cat_name.$invalid && (catDialog.emp_cat_name.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Employee Category Name is required.</p>' +
						'</div>' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="button" class="btn btn-primary" ng-click="save()">{{ (edit ? \'Update\' : \'Save\') }}</button>' +
			'</div>'
		);
}]);