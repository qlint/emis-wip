'use strict';

angular.module('eduwebApp').
controller('addStudentCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, data){
	
	$scope.tabs = ['Student Details','Parents','Medical History','Fee Items'];
	$scope.currentTab = 'Student Details';
	$scope.currentStep = 0;
	$scope.firstStep = true;
	
	$scope.student = {};
	var start_date = moment().format('YYYY-MM-DD HH:MM');
	$scope.student.admission_date = {startDate: start_date};
	$scope.student.student_category = 'Regular';
	$scope.student.nationality = 'Kenya';
	$scope.student.status = 'true';
	$scope.showSeparationAge = false;	
	
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
				$scope.feeItems = formatFeeItems(result.data.required_items);
				$scope.allFeeItems = $scope.feeItems;
				
				$scope.optFeeItems = formatFeeItems(result.data.optional_items);
				$scope.allOptFeeItems = $scope.optFeeItems;
			}
			
		}, function(){});
		
		
	}
	$scope.initializeController();
	
	var formatFeeItems = function(feeItems)
	{
		// convert the classCatsRestriction to array for future filtering
		return feeItems.map(function(item){
			// format the class restrictions into any array
			if( item.class_cats_restriction !== null )
			{
				var classCatsRestriction = (item.class_cats_restriction).slice(1, -1);
				item.class_cats_restriction = classCatsRestriction.split(',');
			}
			item.amount = undefined;
			item.payment_method = undefined;
			
			return item;
		});
	}
	
	
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

	$scope.toggleFeeItem = function(item) 
	{
		var selectionObj = ( type ==  'optional'  ? $scope.optFeeItemSelection : $scope.feeItemSelection);
		var id = selectionObj.indexOf(item);

		// is currently selected
		if (id > -1) {
			selectionObj.splice(id, 1);
			
			// clear out fields
			item.amount = undefined;
			item.payment_method = undefined;
		}

		// is newly selected
		else {
		
			// set value and payment method
			item.amount = item.default_amount;
			item.payment_method = $scope.student.payment_method;
		
			selectionObj.push(item);
			
			
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
		
		apiService.postStudent(postData, createCompleted, createError);
	}
	
	var createCompleted = function ( response, status ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			uploader.uploadAll();
			$modalInstance.close();
			$rootScope.$emit('studentAdded', {'msg' : 'Student was created.', 'clear' : true});
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
			//$rootScope.$emit('jobError', {'msg' : result.data });
		}
	}
	
	var createError = function () 
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
	
} ]);