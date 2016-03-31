'use strict';

angular.module('eduwebApp').
controller('viewStudentCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, data){
	
	$rootScope.modalLoading = false;
	$scope.tabs = ['Details','Family','Medical History','Fees','Report Cards','Exams','News'];
	$scope.currentTab = 'Details';
	$scope.currentStep = 0;
	$scope.firstStep = true;
	
	$scope.edit = ($rootScope.permissions.students.edit ? true : false );
	//$scope.edit = false;
	$scope.student = data;
	
	$scope.feeItemSelection = [];
	$scope.conditionSelection = [];
	
	$scope.initializeController = function()
	{
		var studentCats = $rootScope.currentUser.settings['Student Categories'];
		$scope.studentCats = studentCats.split(',');	
		
		var paymentOptions = $rootScope.currentUser.settings['Payment Options'];
		$scope.paymentOptions = paymentOptions.split(',');	
		
		var relationships = $rootScope.currentUser.settings['Guardian Relationships'];
		$scope.relationships = relationships.split(',');	
		
		var maritalStatuses = $rootScope.currentUser.settings['Marital Statuses'];
		$scope.maritalStatuses = maritalStatuses.split(',');
		
		var titles = $rootScope.currentUser.settings['Titles'];
		$scope.titles = titles.split(',');
		
		var medicalConditions = $rootScope.currentUser.settings['Medical Conditions'];
		medicalConditions = medicalConditions.split(',');
		
		// map medicalConditions to an object to hold user entry fields
		$scope.medicalConditions = medicalConditions.reduce(function(sum,item){
			sum.push({
				'medical_condition' : item,
				'age': '',
				'comments' :''
			});
			return sum;
		}, []);
		
		
		// get fee items
		apiService.getFeeItems({}, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				$scope.feeItems = result.data.map(function(item){
					// format the class restrictions into any array
					if( item.class_cats_restriction !== null )
					{
						var classCatsRestriction = (item.class_cats_restriction).slice(1, -1);
						item.class_cats_restriction = classCatsRestriction.split(',');
					}
					return item;
				});
				$scope.allFeeItems = $scope.feeItems;
			}
			
		}, function(){});
		
		
		
		console.log($scope.student);
		
	}
	$scope.initializeController();
	
	
	$scope.getStep = function(direction)
	{
		$scope.currentStep = (direction == 'next' ? ($scope.currentStep+1): ($scope.currentStep-1));
		$scope.getTabContent($scope.tabs[$scope.currentStep]);
	}

	$scope.getTabContent = function(tab)
	{
		$scope.currentTab = tab;
		$scope.currentStep = $scope.tabs.indexOf(tab);
		$scope.lastStep = false;
		$scope.firstStep = false;
		if( $scope.currentTab == $scope.tabs[0] ) $scope.firstStep = true;
		else if( $scope.currentTab == $scope.tabs[ $scope.tabs.length - 1 ] ) $scope.lastStep = true;
	}
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.$watch('student.payment_method', function(newVal, oldVal){
		if( newVal == oldVal) return;
		
		// want to set all selected fee item payment methods to this value
		
		
	});
	
	$scope.$watch('student.new_student', function(newVal, oldVal){
		if( newVal == oldVal ) return;
		$scope.feeItems = filterFeeItems();		
	});
	
	$scope.$watch('student.current_class', function(newVal, oldVal){
		if( newVal == oldVal) return;
		console.log(newVal);
		$scope.feeItems = filterFeeItems();		
	});
	
	var filterFeeItems = function()
	{
		var feeItems = [];
		if( $scope.student.new_student == 'true' )
		{
			// all fees apply to new students
			feeItems = $scope.allFeeItems;
		}
		else
		{
			// remove new student fees
			feeItems = $scope.allFeeItems.filter(function(item){
				if( !item.new_student_only ) return item;
			});
		}
		
		// now filter by selected class
		if( $scope.student.current_class !== undefined )
		{
			feeItems = feeItems.filter(function(item){
				if( item.class_cats_restriction === null ) return item;
				else if( item.class_cats_restriction.indexOf(($scope.student.current_class.class_cat_id).toString()) > -1 ) return item;
			});
		}
		console.log(feeItems);
		return feeItems;
	}
	
	$scope.addParent = function()
	{
		// show small dialog with add form
		var data = {student_id: $scope.student.student_id};
		var dlg = $dialogs.create('addParent.html','addParentCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(parent){
			
			console.log(parent);
			$scope.student.guardians.push(parent);
			
		},function(){
			
		});
	}
	
	$scope.editParent = function(item)
	{
		// show small dialog with edit form
		var data = {
			student_id: $scope.student.student_id,
			guardian: item,
			action: 'edit'
		};
		var dlg = $dialogs.create('addParent.html','addParentCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(guardian){
			
			console.log(guardian);
			// find guardian and update
			angular.forEach( $scope.student.guardians, function(item,key){
				if( item.guardian_id == guardian.guardian_id) $scope.student.guardians[key] = guardian;
			});
			console.log($scope.student.guardians);
			
		},function(){
			
		});
		
	}
	
	$scope.sendMessage = function(item)
	{
		// show small dialog with message form
	}
	
	$scope.addMedicalHistory = function()
	{
		// show small dialog with add form
	}
	
	$scope.editMedical = function()
	{
		// show small dialog with add form
	}
	
	$scope.toggleFeeItem = function(item) 
	{
	
		var id = $scope.feeItemSelection.indexOf(item);

		// is currently selected
		if (id > -1) {
			$scope.feeItemSelection.splice(id, 1);
			
			// clear out fields
			item.amount = undefined;
			item.payment_method = undefined;
		}

		// is newly selected
		else {
		
			// set value and payment method
			item.amount = item.default_amount;
			item.payment_method = $scope.student.payment_method;
		
			$scope.feeItemSelection.push(item);
			
			
		}
	};
	
	$scope.toggleMedicalCondition = function(item) 
	{
	
		var id = $scope.conditionSelection.indexOf(item);

		// is currently selected
		if (id > -1) {
			$scope.conditionSelection.splice(id, 1);
		}

		// is newly selected
		else {
			$scope.conditionSelection.push(item);
		}
	};
	
	$scope.save = function()
	{
	
		if( uploader.queue[0] !== undefined ){
			$scope.student.student_image = uploader.queue[0].file.name;
		}
		
		$scope.student.has_medical_conditions = ( $scope.conditionSelection.length > 0 || $scope.student.other_medical_conditions ? true : false );

		var postData = angular.copy($scope.student);
		postData.admission_date = $scope.student.admission_date.startDate;
		postData.current_class = $scope.student.current_class.class_id;		
		postData.medicalConditions = $scope.conditionSelection;
		postData.feeItems = $scope.feeItemSelection;
		postData.user_id = $rootScope.currentUser.user_id;
		console.log(postData);
		
		apiService.putStudent(postData, createCompleted, createError);
	}
	
	var createCompleted = function ( response, status ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			uploader.uploadAll();
			$uibModalInstance.close();
			$rootScope.$emit('studentAdded', {'msg' : 'Student was updated.', 'clear' : true});
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
			//$rootScope.$emit('jobError', {'msg' : result.data });
		}
	}
	
	var createError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'students'
			}]
    });
	
} ])
.controller('addParentCtrl',function($scope,$rootScope,$uibModalInstance,apiService,data){
		//-- Variables --//
		$scope.guardian =  data.guardian || {};
		$scope.edit = ( data.action == 'edit' ? true : false );
		
		var relationships = $rootScope.currentUser.settings['Guardian Relationships'];
		$scope.relationships = relationships.split(',');
		
		var maritalStatuses = $rootScope.currentUser.settings['Marital Statuses'];
		$scope.maritalStatuses = maritalStatuses.split(',');
		
		var titles = $rootScope.currentUser.settings['Titles'];
		$scope.titles = titles.split(',');

		//-- Methods --//
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			//console.log($scope.guardian);
			var postData = {
				student_id: data.student_id,
				guardian: $scope.guardian,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.postGuardian(postData, createCompleted, createError);
			
			
		}; // end save
		
		$scope.update = function()
		{
			//console.log($scope.guardian);
			var postData = {
				guardian: $scope.guardian,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.updateGuardian(postData, createCompleted, createError);
			
			
		}; // end update
		
		$scope.deleteGuardian = function()
		{
			var postData = {
				guardian_id: $scope.guardian.guardian_id,
				active: false,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.deleteGuardian(postData, createCompleted, createError);
		}
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$scope.guardian.parent_full_name = $scope.guardian.first_name + ' ' + $scope.guardian.middle_name + ' ' + $scope.guardian.last_name;
				$uibModalInstance.close($scope.guardian);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		}
		
		
		var createError = function(response,status)
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
  		$templateCache.put('addParent.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Parent/Guardian</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="cargoDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- last name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.last_name.$invalid && (studentForm.last_name.$touched || studentForm.$submitted) }">' +
						'<label for="last_name" class="col-sm-3 control-label">Last Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="last_name" ng-model="guardian.last_name" class="form-control"  >' +
							'<p ng-show="studentForm.last_name.$invalid && (studentForm.last_name.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Last Name is required.</p>' +
						'</div>' +
					'</div>' +
					'<!-- first name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.first_name.$invalid && (studentForm.first_name.$touched || studentForm.$submitted) }">		' +
						'<label for="first_name" class="col-sm-3 control-label">First Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="first_name" ng-model="guardian.first_name" class="form-control"  >	' +
							'<p ng-show="studentForm.first_name.$invalid && (studentForm.first_name.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> First Name is required.</p>' +
						'</div>	' +
					'</div>' +
					'<!-- middle name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.middle_name.$invalid && (studentForm.middle_name.$touched || studentForm.$submitted) }">	' +	
						'<label for="middle_name" class="col-sm-3 control-label">Middle Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="middle_name" ng-model="guardian.middle_name" class="form-control"  >	' +
							'<p ng-show="studentForm.middle_name.$invalid && (studentForm.middle_name.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Middle Name is required.</p>' +
						'</div>	' +
					'</div>' +
					'<!-- name title -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.title.$invalid && (studentForm.title.$touched || studentForm.$submitted) }">	' +	
						'<label for="title" class="col-sm-3 control-label">Title</label>' +
						'<div class="col-sm-9">' +
							'<select name="title" ng-model="guardian.title" class="form-control">' +
								'<option value="{{title}}" ng-repeat="title in titles">{{title}}</option>' +
							'</select>' +
							'<p ng-show="studentForm.title.$invalid && (studentForm.title.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Title is required.</p>' +
						'</div>	' +
					'</div>	' +
					'<!-- id number -->' +
					'<div class="form-group"> <!-- ng-class="{ \'has-error\' : studentForm.id_number.$invalid && (studentForm.id_number.$touched || studentForm.$submitted) }">-->' +
						'<label for="id_number" class="col-sm-3 control-label">ID Number</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="id_number" ng-model="guardian.id_number" class="form-control"  >	' +
							'<!--p ng-show="studentForm.id_number.$invalid && (studentForm.id_number.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> ID Number is required.</p-->' +
						'</div>' +
					'</div>' +
					'<!-- address -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.address.$invalid && (studentForm.address.$touched || studentForm.$submitted) }">' +
						'<label for="address" class="col-sm-3 control-label">Address</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="address" ng-model="guardian.address" class="form-control"  >	' +
							'<p ng-show="studentForm.address.$invalid && (studentForm.address.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Address is required.</p>' +
						'</div>' +
					'</div>' +
					
					'<!-- phone number -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.telephone.$invalid && (studentForm.telephone.$touched || studentForm.$submitted) }">	' +	
						'<label for="telephone" class="col-sm-3 control-label">Telephone</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="telephone" ng-model="guardian.telephone" class="form-control"  >	' +
							'<p ng-show="studentForm.telephone.$invalid && (studentForm.telephone.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Telephone number is required.</p>' +
						'</div>	' +
					'</div>' +
					'<!-- email -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.email.$invalid && (studentForm.email.$touched || studentForm.$submitted) }">	' +	
						'<label for="email" class="col-sm-3 control-label">Email</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="email" ng-model="guardian.email" class="form-control"  >	' +
							'<p ng-show="studentForm.email.$invalid && (studentForm.email.$touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Email is required.</p>' +
						'</div>	' +
					'</div>' +
					'<!-- occupation -->' +
					'<div class="form-group">	' +	
						'<label for="occupation" class="col-sm-3 control-label">Occupation</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="occupation" ng-model="guardian.occupation" class="form-control"  >	' +
						'</div>	' +
					'</div>' +
					'<!-- employer -->' +
					'<div class="form-group">	' +	
						'<label for="employer" class="col-sm-3 control-label">Employer</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="employer" ng-model="guardian.employer" class="form-control"  >	' +
						'</div>	' +
					'</div>' +
					'<!-- employer address -->' +
					'<div class="form-group">	' +	
						'<label for="employer_address" class="col-sm-3 control-label">Employer Address</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="employer_address" ng-model="guardian.employer_address" class="form-control"  >	' +
						'</div>	' +
					'</div>' +
					'<!-- phone number -->' +
					'<div class="form-group">	' +	
						'<label for="work_phone" class="col-sm-3 control-label">Work Phone</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="work_phone" ng-model="guardian.work_phone" class="form-control"  >	' +
						'</div>	' +
					'</div>' +
					'<!-- work_email -->' +
					'<div class="form-group">' +
						'<label for="work_email" class="col-sm-3 control-label">Work Email</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="work_email" ng-model="guardian.work_email" class="form-control"  >	' +
						'</div>	' +
					'</div>' +
					'<!-- relationship -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.relationship.$invalid && (studentForm.relationship.$touched || studentForm.$submitted) }">		' +
						'<label for="relationship" class="col-sm-3 control-label">Relationship</label>' +
						'<div class="col-sm-9">' +
							'<select name="relationship" ng-model="guardian.relationship" class="form-control">' +
								'<option value="">--select relationship--</option>' +
								'<option value="{{item}}" ng-repeat="item in relationships">{{item}}</option>' +
							'</select>' +
							'<p ng-show="studentForm.relationship.$invalid && (studentForm.relationship.touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Relationship is required.</p>' +
						'</div>	' +
					'</div>' +
					'<!-- marital status -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : studentForm.marital_status.$invalid && (studentForm.marital_status.$touched || studentForm.$submitted) }">		' +
						'<label for="marital_status" class="col-sm-3 control-label">Marital Status</label>' +
						'<div class="col-sm-9">' +
							'<select name="marital_status" ng-model="guardian.marital_status" class="form-control">' +
								'<option value="">--select one--</option>' +
								'<option value="{{item}}" ng-repeat="item in maritalStatuses">{{item}}</option>' +
							'</select>' +
							'<p ng-show="studentForm.marital_status.$invalid && (studentForm.marital_status.touched || studentForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Marital Status is required.</p>' +
						'</div>	' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<div class="pull-left" ng-show="edit"><button type="button" class="btn btn-danger" ng-click="deleteGuardian()">Delete</button></div>' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button ng-show="!edit" type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
				'<button ng-show="edit" type="button" class="btn btn-primary" ng-click="update()">Update</button>' +
			'</div>'
		);
}]);


