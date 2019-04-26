'use strict';

angular.module('eduwebApp').
controller('gradingFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.edit = ( data !== undefined ? true : false );
	$scope.grading = ( data !== undefined ? data : {} );
	
	console.log("Reading from grading test :::");
	console.log($rootScope.gradeAddingSelector);
	
	// we use these to show / hide elements as needed
	if ( window.location.host.split('.')[0] == "lasalle" && $rootScope.gradeAddingSelector == "lower" ){
	    $scope.isLowerSchool = true;
	}else{
	    $scope.isLowerSchool = false;
	}
		
	$scope.initializeController = function()
	{

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
			var data = $scope.grading;
			console.log("Saving ::::");
			console.log($scope.grading);
			
			// need to add grade2 to the object
            if( $scope.isLowerSchool == true ){
                data.grade2 = data.grade;
                delete data.grade;
                console.log(data);
            }
			
			if( $scope.edit )
			{   
			    if( $scope.isLowerSchool == true ){
			        console.log("Updating lower school grading.");
			        console.log(data);
			        apiService.updateGrading2(data,createCompleted,apiError);
			    }else{
			        console.log("Updating upper school grading.");
			        console.log(data);
				    apiService.updateGrading(data,createCompleted,apiError);
			    }
			}
			else
			{   
			    if( $scope.isLowerSchool == true ){
			        console.log("Adding lower school grading.");
			        console.log(data);
				    apiService.addGrading2(data,createCompleted,apiError);
			    }else{
			        console.log("Adding upper school grading.");
			        apiService.addGrading(data,createCompleted,apiError);
			    }
			}
			
			
		}
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Grading was updated.' : 'Grading was added.');
			$rootScope.$emit('gradingAdded', {'msg' : msg, 'clear' : true});
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
	
	
} ]);