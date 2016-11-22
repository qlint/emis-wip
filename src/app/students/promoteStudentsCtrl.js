'use strict';

angular.module('eduwebApp').
controller('promoteStudentsCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, data){

  $scope.selectedClass = data.selectedClass || undefined;
  $scope.selectedClassCat = data.selectedClassCat || undefined;
  $scope.students = data.students || undefined;
  $scope.classes = data.classes || undefined;
  $scope.showClassSelect = $scope.selectedClass === undefined ? true : false;
  $scope.toggleStatus = 'Select All';
  $scope.studentSelection = [];
  $scope.promote = [];

  var initializeController = function ()
  {
    if( !$scope.showClassSelect )
    {
      $scope.selectedStudents = $scope.students;
    }
  }
  initializeController();
  
  $scope.cancel = function()
  {
    $uibModalInstance.dismiss('canceled');
  }; // end cancel

  $scope.loadStudents = function ( theForm )
  {
    if( !theForm.$invalid )
    {
      $scope.selectedClass = $scope.promote.class_id;
      
      // get selected class
      var selectedClass = $scope.classes.filter(function(item){
        if( item.class_id == $scope.selectedClass ) return item;
      })[0];
      $scope.selectedClassCat = selectedClass.class_cat_id;
      
      
      //filter the students passed in
      $scope.selectedStudents = $scope.students.filter(function(student){
        if( student.class_id == $scope.selectedClass ) return student;
      });
      console.log($scope.selectedStudents);
    }
  }
  
  $scope.toggleAll = function ()
  {
    $scope.studentSelection = [];
    if( $scope.toggleStatus == 'Select All' )
    {
      $scope.selectedStudents.map(function(student){
        $scope.studentSelection.push(student.student_id);
      });
    }
    $scope.toggleStatus = $scope.toggleStatus == 'Select All' ? 'Deselect All' : 'Select All';
  }
  
  $scope.toggleStudent = function (studentId) 
  {
    var id = $scope.studentSelection.indexOf(studentId);

    // is currently selected
    if (id > -1) {
      $scope.studentSelection.splice(id, 1);
    }

    // is newly selected
    else {    
      $scope.studentSelection.push(studentId);
    }
  }
  
  $scope.save = function(form)
  {
    if ( !form.$invalid )
    {
      var data = {
        students: $scope.studentSelection,
        class_id: $scope.promote.to_class_id,
        previous_class_id: $scope.selectedClass,
        previous_class_cat_id: $scope.selectedClassCat,
        user_id: $rootScope.currentUser.user_id
      };

      apiService.promoteStudents(data,createCompleted,apiError);
    }
  }

  var createCompleted = function ( response, status, params )
  {
    var result = angular.fromJson( response );
    if( result.response == 'success' )
    {
      $uibModalInstance.close();
      var msg = 'Students were promoted.';
      $rootScope.$emit('studentsPromoted', {'msg' : msg, 'clear' : true});
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
    var msg = result.data;
    $scope.errMsg = msg;
  }

} ]);