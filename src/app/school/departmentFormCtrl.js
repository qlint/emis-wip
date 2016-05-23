'use strict';

angular.module('eduwebApp').
controller('departmentFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.edit = ( data !== undefined ? true : false );
	$scope.department = ( data !== undefined ? data : {} );
	//console.log(data);
	
	$scope.initializeController = function()
	{
	
		//var categories = $rootScope.currentUser.settings['Department Categories'];
		$scope.categories = $rootScope.empCats;
		
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.save = function(form)
	{
		//console.log(form);
		if ( !form.$invalid ) 
		{
			var data = $scope.department;
			data.user_id = $rootScope.currentUser.user_id;
			//console.log(data);
			
			if( $scope.edit )
			{
				apiService.updateDept(data,createCompleted,apiError);
			}
			else
			{
				apiService.addDept(data,createCompleted,apiError);
			}
			
			
		}
	}
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Department was updated.' : 'Department was added.');
			$rootScope.$emit('deptAdded', {'msg' : msg, 'clear' : true});
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
	
	$scope.deleteDept = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to mark this department as deleted? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				dept_id: $scope.department.dept_id,
				status: 'f'
			}
			apiService.setDeptStatus(data,createCompleted,apiError);

		});
	}
	
	$scope.activateDept = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to re-activate this department? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				dept_id: $scope.department.dept_id,
				status: 't'
			}
			apiService.setDeptStatus(data,createCompleted,apiError);

		});
	}
	
	$scope.addCat = function()
	{		
		// show small dialog with add form
		var dlg = $dialogs.create('addCategory.html','addCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			
			$scope.categories.push(category);
			
			// update the users settings
			apiService.getSettings({},function(response){
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					var settings = result.data.reduce(function ( total, current ) { 
						total[ current.name ] = current.value;
						return total;
					}, {});
					//console.log(settings);
					
					$rootScope.$emit('setSettings', settings);
					
				}		
				
			},apiError);
			
		},function(){
			
		});
	}
	
	
	
} ])
.controller('addCategoryCtrl',[ '$scope','$rootScope','$uibModalInstance','apiService','data',
function($scope,$rootScope,$uibModalInstance,apiService,data){
		
		$scope.category = {};
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			//console.log($scope.guardian);
			var postData = {
				name: 'Department Categories',
				value: $scope.category.name,
				append: true
			}
			apiService.updateSetting(postData, createCompleted, apiError);
			
			
		}; // end save
		
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$uibModalInstance.close($scope.category.name);
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
  		$templateCache.put('addCategory.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Category</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="catDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : catDialog.name.$invalid && (catDialog.name.$touched || catDialog.$submitted) }">' +
						'<label for="name" class="col-sm-3 control-label">Category Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="name" ng-model="category.name" class="form-control"  >' +
							'<p ng-show="catDialog.name.$invalid && (catDialog.name.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Category Name is required.</p>' +
						'</div>' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
			'</div>'
		);
}]);