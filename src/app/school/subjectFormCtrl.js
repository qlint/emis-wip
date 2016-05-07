'use strict';

angular.module('eduwebApp').
controller('subjectFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){
	
	$scope.subject = ( data !== undefined ? data : {} );
	$scope.edit = (  data !== undefined && data.subject_id !== undefined ? true : false );
	
	//console.log(data);
	
	$scope.initializeController = function()
	{
		apiService.getAllTeachers(true,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success') $scope.teachers = result.data;
		},apiError);
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
			var data = $scope.subject;
			data.user_id = $rootScope.currentUser.user_id;
			//console.log(data);
			
			if( $scope.edit )
			{
				apiService.updateSubject(data,createCompleted,apiError);
			}
			else
			{
				apiService.addSubject(data,createCompleted,apiError);
			}
			
			
		}
	}
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Subject was updated.' : 'Subject was added.');
			$rootScope.$emit('subjectAdded', {'msg' : msg, 'clear' : true});
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
	
	$scope.deleteSubject = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to mark this subject as deleted? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				subject_id: $scope.subject.subject_id,
				status: 'f'
			}
			apiService.setSubjectStatus(data,createCompleted,apiError);

		});
		
	}
	
	$scope.activateSubject = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to re-activate this subject? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				subject_id: $scope.subject.subject_id,
				status: 't'
			}
			apiService.setSubjectStatus(data,createCompleted,apiError);

		});
		
	}
	
	$scope.addClassCat = function()
	{		
		// show small dialog with add form
		var dlg = $dialogs.create('addClassCategory.html','addClassCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			
			$rootScope.classCats.push(category);
					
		},function(){
			
		});
	}
	
	
	
} ])
.controller('addClassCategoryCtrl',function($scope,$rootScope,$uibModalInstance,apiService,data){
		
		$scope.classCat = {};
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			var data = {
				class_cat_name : $scope.classCat.class_cat_name
			}
			apiService.addClassCat(data, createCompleted, apiError);
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
		

	
	
	}) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addClassCategory.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Class Category</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="catDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- class_cat_name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : catDialog.class_cat_name.$invalid && (catDialog.class_cat_name.$touched || catDialog.$submitted) }">' +
						'<label for="class_cat_name" class="col-sm-3 control-label">Class Category Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="name" ng-model="classCat.class_cat_name" class="form-control"  >' +
							'<p ng-show="catDialog.class_cat_name.$invalid && (catDialog.class_cat_name.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Class Category Name is required.</p>' +
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