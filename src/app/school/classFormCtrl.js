'use strict';

angular.module('eduwebApp').
controller('classFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.edit = ( data !== undefined ? true : false );
	$scope.theClass = ( data !== undefined ? data : {} );
	console.log(data);
	
	$scope.initializeController = function()
	{
		apiService.getAllTeachers(true,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success') $scope.teachers = result.data;
		},apiError);
		
		apiService.getExamTypes({}, function(response){
			var result = angular.fromJson(response);				
			if( result.response == 'success'){ $scope.examTypes = result.data;}			
		}, apiError);
		
	}
	$scope.initializeController();
	
	
	$scope.$watch('theClass.class_cat_id',function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		apiService.getSubjects(newVal,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success') $scope.subjects = result.data;
		}, apiError);
		
		
	});
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.save = function(form)
	{
		console.log(form);
		if ( !form.$invalid ) 
		{
			var data = $scope.theClass;
			data.user_id = $rootScope.currentUser.user_id;
			console.log(data);
			
			if( $scope.edit )
			{
				apiService.updateClass(data,createCompleted,apiError);
			}
			else
			{
				apiService.addClass(data,createCompleted,apiError);
			}
			
			
		}
	}
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Class was updated.' : 'Class was added.');
			$rootScope.$emit('classAdded', {'msg' : msg, 'clear' : true});
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
	
	$scope.deleteClass = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to mark this class as deleted? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				class_id: $scope.theClass.class_id,
				status: 'f'
			}
			apiService.setClassStatus(data,createCompleted,apiError);

		});
		
	}
	
	$scope.activateClass = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to re-activate this class? ',{size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id : $rootScope.currentUser.user_id,
				class_id: $scope.theClass.class_id,
				status: 't'
			}
			apiService.setClassStatus(data,createCompleted,apiError);

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
	
	$scope.addSubject = function()
	{		
		// show small dialog with add form
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/school/subjectForm.html','subjectFormCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(subject){
			
			$rootScope.subjects.push(subject);
					
		},function(){
			
		});
	}
	
	$scope.addExamType = function()
	{		
		// show small dialog with add form
		var dlg = $dialogs.create('addExamType.html','addExamTypeCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(examType){
			
			$rootScope.examTypes.push(examType);
					
		},function(){
			
		});
	}
	
	
} ])
.controller('addExamType',function($scope,$rootScope,$uibModalInstance,apiService){		
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			apiService.addExamType($scope.exam_type, createCompleted, apiError);
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
		

	
	
	}) // end controller
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addExamTypeCtrl.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Exam Type</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="catDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- exam type -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : catDialog.exam_type.$invalid && (catDialog.exam_type.$touched || catDialog.$submitted) }">' +
						'<label for="exam_type" class="col-sm-3 control-label">Class Category Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="name" ng-model="exam_type" class="form-control"  >' +
							'<p ng-show="catDialog.exam_type.$invalid && (catDialog.exam_type.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Exan Type is required.</p>' +
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