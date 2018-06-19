'use strict';

angular.module('eduwebApp').
controller('printAddExamMarksCtrl', ['$scope', '$rootScope',
function($scope, $rootScope ){


	var initializeController = function()
	{
		var data = window.printCriteria;
		console.log(data);
		var criteria =  angular.fromJson(data.criteria);
		$scope.student = angular.fromJson(data.students);
		$scope.classes = angular.fromJson(data.classes);
		$scope.terms = angular.fromJson(data.terms);
		$scope.examTypes = angular.fromJson(data.examTypes);
		$scope.examMarks = angular.fromJson(data.examMarks);
		$scope.subjects = angular.fromJson(data.subjects);
		$scope.students = angular.fromJson(data.students);

		setTimeout( function(){
			window.print();

			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 100);
	}
	setTimeout(initializeController,1);

	$scope.displayMeanScore = function(key)
	{
		return Math.round($scope.totalMarks[key]/$scope.totalStudents,1) || '-' ;
	}


} ]);
