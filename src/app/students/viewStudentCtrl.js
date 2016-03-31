'use strict';

angular.module('eduwebApp').
controller('viewStudentCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, data){
	
	$rootScope.modalLoading = false;
	$scope.tabs = ['Details','Family','Medical History','Fees','Report Cards','Exams','News'];
	$scope.currentTab = 'Details';
	$scope.currentStep = 0;
	$scope.firstStep = true;
	$scope.hasChanges = false;
	
	$scope.edit = ($rootScope.permissions.students.edit ? true : false );
	//$scope.edit = false;
	
	var originalData = angular.copy(data);
	$scope.student = angular.copy(data);
	
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
		

		
	}
	$scope.initializeController();
	
	
	$scope.getStep = function(direction)
	{
		$scope.currentStep = (direction == 'next' ? ($scope.currentStep+1): ($scope.currentStep-1));
		$scope.getTabContent($scope.tabs[$scope.currentStep]);
	}

	$scope.getTabContent = function(tab)
	{
	
		if( !$scope.studentForm.$pristine )
		{
			var dlg = $dialogs.confirm('Changes Where Made','You have made changes to the data on this page. Did you want to save these changes?', {size:'sm'});
			
			dlg.result.then(function(btn){
				 // save the form
				 save(tab);
				 
			},function(btn){
				// revert the changes and move on
				$scope.student = angular.copy(originalData);	
				goToTab(tab);
			});
		}
		else
		{
			goToTab(tab);
		}
		
	}
	
	var goToTab = function(tab)
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

		// update class fields for student
		$scope.student.class_id = $scope.student.current_class.class_id;
		$scope.student.class_name = $scope.student.current_class.class_name;
		$scope.student.class_cat_id = $scope.student.current_class.class_cat_id;
		$scope.feeItems = filterFeeItems();		
	});
	
	$scope.$watch('uploader.queue[0]', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		$scope.studentForm.$setDirty();
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
	
	$scope.addGuardian = function()
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
	
	$scope.editGuardian = function(item)
	{
		// show small dialog with edit form
		var data = {
			student_id: $scope.student.student_id,
			guardian: item,
			action: 'edit'
		};
		var dlg = $dialogs.create('addParent.html','addParentCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(guardian){
			
			// find guardian and update
			angular.forEach( $scope.student.guardians, function(item,key){
				if( item.guardian_id == guardian.guardian_id) $scope.student.guardians[key] = guardian;
			});
			
		},function(){
			
		});
		
	}
	
	$scope.deleteGuardian = function(item,index)
	{

		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete <b>' + item.parent_full_name + '</b> as a parent/guardian? <br><br><b><i>(THIS CAN NOT BE UNDONE)</i></b>',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deleteGuardian(item.guardian_id, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					// remove row
					$scope.student.guardians.splice(params.index,1);
				
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, createError,{index:index});

		});
		
	}
	
	$scope.sendMessage = function(item)
	{
		// show small dialog with message form
	}
	
	$scope.addMedicalHistory = function()
	{
		// show small dialog with add form
		var data = {
			student_id: $scope.student.student_id,
			medicalHistory: $scope.student.medical_history,
			action: 'add'
		};
		var dlg = $dialogs.create('addMedicalHistory.html','addMedicalHistoryCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(medicalHistory){
			
			angular.forEach(medicalHistory, function(item,key){
				$scope.student.medical_history.push(item);
			});
			
		},function(){
			
		});
	}
	
	$scope.editMedical = function(item)
	{
		// show small dialog with add form
		var data = {
			student_id: $scope.student.student_id,
			medicalCondition: item,
			action: 'edit'
		};
		var dlg = $dialogs.create('updateMedicalCondition.html','updateMedicalConditionCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(medicalCondition){
			
			// find medical condition and update
			angular.forEach( $scope.student.medical_history, function(item,key){
				if( item.medical_id == medicalCondition.medical_id ) $scope.student.medical_history[key] = medicalCondition;
			});
			
		},function(){
			
		});
	}
	
	$scope.deleteMedical = function(item,index)
	{
		// show small dialog with add form
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete <b>' + item.illness_condition + '</b> as a medical condition for this student? <br><br><b><i>(THIS CAN NOT BE UNDONE)</i></b>',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deleteMedicalCondition(item.medical_id, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					// remove row
					$scope.student.medical_history.splice(params.index,1);
	
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, createError,{index:index});

		});
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
	

	$scope.save = function(tab)
	{
		// going to only send data that is on the current tab
		
		if( $scope.currentTab == 'Details' )
		{
			if( uploader.queue[0] !== undefined )
			{
				// need a unique filename
				uploader.queue[0].file.name = $scope.student.student_id + "_" + uploader.queue[0].file.name;
				uploader.uploadAll();
			}
			
			//postData.feeItems = $scope.feeItemSelection;
			
			var postData = {
				student_id : $scope.student.student_id,
				user_id : $rootScope.currentUser.user_id,
				details : {
					student_category : $scope.student.student_category,
					first_name : $scope.student.first_name,
					middle_name : $scope.student.middle_name,
					last_name : $scope.student.last_name,
					gender : $scope.student.gender,
					dob: $scope.student.dob,
					nationality : $scope.student.nationality,
					current_class : $scope.student.class_id,
					update_class : ( originalData.class_id != $scope.student.class_id ? true : false),
					previous_class :  originalData.class_id,
					student_image : ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null),
					active : ( $scope.student.active ? 't' : 'f' )
				}
			}
		}
		else if ( $scope.currentTab == 'Family' )
		{
			var postData = {
				student_id : $scope.student.student_id,
				user_id : $rootScope.currentUser.user_id,
				family : {
					marial_status_parents : $scope.student.marial_status_parents,
					adopted : ( $scope.student.adopted ? 't' : 'f' ),
					adopted_age : $scope.student.adopted_age,
					marital_separation_age : $scope.student.marital_separation_age,
					adoption_aware : ( $scope.student.adoption_aware ? 't' : 'f'),
					emergency_name: $scope.student.emergency_name,
					emergency_relationship : $scope.student.emergency_relationship,
					emergency_telephone : $scope.student.emergency_telephone,
					pick_up_drop_off_individual : $scope.student.pick_up_drop_off_individual
				}
			}
		}
		else if ( $scope.currentTab == 'Medical History' )
		{

			var postData = {
				student_id : $scope.student.student_id,
				user_id : $rootScope.currentUser.user_id,
				medical : {
					has_medical_conditions : ($scope.student.has_medical_conditions || $scope.student.other_medical_conditions ? 't' : 'f' ),
					hospitalized: ( $scope.student.hospitalized ? 't' : 'f' ),
					hospitalized_description: ( $scope.student.hospitalized ? $scope.student.hospitalized_description : null),
					current_medical_treatment: ( $scope.student.current_medical_treatment ? 't' : 'f' ),
					current_medical_treatment_description: ( $scope.student.current_medical_treatment ? $scope.student.current_medical_treatment_description : null),
					other_medical_conditions: ($scope.student.other_medical_conditions ? 't' : 'f'),
					other_medical_conditions_description: ($scope.student.other_medical_conditions ? $scope.student.other_medical_conditions_description : null);
				}
			}

		}
		console.log(postData);
		apiService.updateStudent(postData, createCompleted, createError, {tab:tab});
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( uploader.queue[0] !== undefined )
			{
				$scope.student.student_image = uploader.queue[0].file.name;
			}
			
			// saved, update the originalData
			originalData = angular.copy($scope.student);
			$scope.studentForm.$setPristine();
			
			// if moving tabs, continue
			if( params.tab !== undefined) goToTab(params.tab);

			// refresh the main student list
			$rootScope.$emit('studentAdded', {'msg' : 'Student was updated.', 'clear' : true});
			
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
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
		
		
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$scope.guardian.guardian_id = result.data;
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
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button ng-show="!edit" type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
				'<button ng-show="edit" type="button" class="btn btn-primary" ng-click="update()">Update</button>' +
			'</div>'
		);
}])
.controller('addMedicalHistoryCtrl',function($scope,$rootScope,$uibModalInstance,apiService,data){
		
		//-- Variables --//
		
		$scope.fullMedicalHistory =  data.medicalHistory || {};
		
		var medicalConditions = $rootScope.currentUser.settings['Medical Conditions'];
		medicalConditions = medicalConditions.split(',');
		
		// build array of just the medical condition names for the student
		var medicalHistory = $scope.fullMedicalHistory.reduce(function(sum,item){
			sum.push(item.illness_condition);
			return sum;
		}, []);
		
		// map medicalConditions to an object to hold user entry fields
		// remove medical conditions that are already set for the student
		$scope.medicalConditions = medicalConditions.reduce(function(sum,item){
			if( medicalHistory.indexOf(item) === -1 )
			{
				sum.push({
					'illness_condition' : item,
					'age': '',
					'comments' :''
				});
			}
			return sum;
		}, []);


		//-- Methods --//
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			//console.log($scope.guardian);
			var postData = {
				student_id: data.student_id,
				medicalConditions: $scope.conditionSelection,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.postMedicalConditions(postData, createCompleted, createError);
			
			
		}; // end save
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				// loop through results and add medical_id to each condition
				angular.forEach( result.data, function(item,key){
					$scope.conditionSelection[key].medical_id = item.medical_id;
					$scope.conditionSelection[key].date_medical_added = item.date_medical_added;
				});
				
				$uibModalInstance.close($scope.conditionSelection);
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
		
		$scope.conditionSelection = [];
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
	
	}) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addMedicalHistory.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Medical History</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="cargoDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<table class="display dataTable">' +
					'<thead>' +
						'<tr>' +
							'<th>Illness/Condition</th>' +
							'<th>Age</th>' +
							'<th>Explain</th>' +
						'</tr>' +
					'</thead>' +
					'<tbody>' +
						'<tr ng-class-odd="\'odd\'" ng-class-even="\'even\'" ng-repeat="item in medicalConditions track by $index">' +
							'<td width="150">' +
								'<label class="checkbox-inline">' +
								  '<input ' +
									'type="checkbox" ' +
									'name="conditions[]" ' +
									'value="{{item.illness_condition}}" ' +
									'ng-checked="conditionSelection.indexOf(item) > -1" ' +
									'ng-click="toggleMedicalCondition(item)" ' +
								  '> {{item.illness_condition}}' +
								'</label>' +
							'</td>' +
							'<td width="80">' +
								'<input type="text" class="form-control" name="medical_condition_age[]" ng-model="item.age" >' +
							'</td>' +
							'<td>' +
								'<input type="text" class="form-control" name="medical_condition_comments[]" ng-model="item.comments" >' +
							'</td>' +
						'</tr>' +
					'</tbody>' +
				'</table>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button ng-show="!edit" type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
			'</div>'
		);
}])
.controller('updateMedicalConditionCtrl',function($scope,$rootScope,$uibModalInstance,apiService,data){
		
		//-- Variables --//
		
		$scope.medicalCondition =  data.medicalCondition || {};
		
		
		//-- Methods --//
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			//console.log($scope.guardian);
			var postData = {
				student_id: data.student_id,
				medicalCondition: $scope.medicalCondition,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.updateMedicalConditions(postData, createCompleted, createError);
			
			
		}; // end save
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				// loop through results and add medical_id to each condition				
				$uibModalInstance.close($scope.medicalCondition);
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
  		$templateCache.put('updateMedicalCondition.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Update Medical Condition : {{medicalCondition.illness_condition}}</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="cargoDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div class="row">' +
						'<div class="col-sm-12">' +
							'<div ng-show="error" class="alert alert-danger">' +
								'{{errMsg}}'+
							'</div>' +
							'<!-- Age -->' +
							'<div class="form-group">' +
								'<div class="col-sm-12"><label for="age">Age</label></div>' +
								'<div class="col-sm-3"><input type="text" name="age" ng-model="medicalCondition.age" class="form-control"  ></div>' +
							'</div>' +
							'<!-- Explain -->' +
							'<div class="form-group">' +
								'<div class="col-sm-12"><label for="comments">Explain</label></div>' +
								'<div class="col-sm-12"><textarea name="comments" rows="5" ng-model="medicalCondition.comments" class="form-control"></textarea></div>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button ng-show="!edit" type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
			'</div>'
		);
}]);


