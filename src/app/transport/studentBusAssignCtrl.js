'use strict';

angular.module('eduwebApp').
controller('studentBusAssignCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window){

    $scope.route = data.buses.route;
    $scope.roueId = data.buses.route_id;
    $scope.buses = data.buses.buses;
	var school = window.location.host.split('.')[0];
	$scope.studentsToAssign = null;
	$scope.preAssignedStudents = null;
	$scope.showLoader = false;
	$scope.showPreassigned = true;

	$scope.isTeacher = ($rootScope.currentUser.user_type == 'TEACHER' ? true : false);
    $scope.showTable = false;

	var initializeController = function()
	{
	    // fetch students who have already been assigned
        apiService.getAlreadyAssignedStudentsInBus(true, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
			        var preAssignedStudents = ( result.nodata ? [] : result.data );
			        if(preAssignedStudents != null || preAssignedStudents != undefined){
    			        preAssignedStudents.forEach(function(eachPreAssignedStudent) {
                          eachPreAssignedStudent.preassigned = "Already assigned to " + eachPreAssignedStudent.bus_type + " - " + eachPreAssignedStudent.bus_registration; 
                        });
			        }
			        $scope.preAssignedStudents = preAssignedStudents;
			}
			else
			{
			        console.log("Problem fetching assigned students");
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);

        // fetch students in this route
        apiService.getStudentsInRoute($scope.roueId, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
					$scope.students = ( result.nodata ? [] : result.data );
					$scope.students.forEach(function(eachStudent) {
                      eachStudent.bus_options = $scope.buses;
                      // search for students with preassigned data
                      if($scope.preAssignedStudents != null || $scope.preAssignedStudents != undefined){
                          for(let k=0; k<$scope.preAssignedStudents.length; k++){
                              if(eachStudent.student_id == $scope.preAssignedStudents[k].student_id){
                                  eachStudent.preassigned = $scope.preAssignedStudents[k].preassigned;
                              }else{
                                  eachStudent.preassigned = null;
                              }
                          }
                      }
                    });
                    $scope.studentsToAssign = $scope.students;
					$scope.showTable = ($scope.studentsToAssign == null ? false : true);
			}
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);

	}
	setTimeout(initializeController,100);

	$scope.updateStudentBus = function (el)
	{
	    let elementId =  "status_student_" + el.item.student_id;
	    $scope.showPreassigned = false;
	    $('#'+elementId).attr('style', 'display: block !important');
	    let updateObj = el.item;
		// console.log(updateObj,el);
		let updateParam = updateObj.student_id + "/" + el.selectedBus;
        let postData = {
				student_id: updateObj.student_id,
				bus_id: el.selectedBus
			}
			apiService.assignStudentToBus(postData, function(response,status){
        var result = angular.fromJson(response);
        if( result.response == 'success')
        {
            document.getElementById(elementId).innerHTML = "Updated";
        }
        else
        {
            document.getElementById(elementId).innerHTML = "Error updating. Please try again.";
        }
        setTimeout(initializeController,3000);

  }, apiError);
	};

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel


	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
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
