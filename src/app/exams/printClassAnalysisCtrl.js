'use strict';

angular.module('eduwebApp').
controller('printClassAnalysisCtrl', ['$scope', '$rootScope',
function($scope, $rootScope ){


	var initializeController = function()
	{
		var data = window.printCriteria;
		console.log(data);
		$scope.hideImages = ( window.location.host.split('.')[0] == 'thomasburke' ? true : false );
		$scope.isPlain = ( window.location.host.split('.')[0] == 'thomasburke' ? true : false );
		var criteria =  angular.fromJson(data.criteria);
		$scope.title = criteria.class_name + ' ' + criteria.term + ' ' + criteria.exam_type;
		$scope.tableHeader = angular.fromJson(data.tableHeader);
		$scope.examMarks = angular.fromJson(data.examMarks);
		$scope.totalMarks = angular.fromJson(data.totalMarks);
		$scope.totGtot = angular.fromJson(data.totGtot);
		$scope.totAvg = angular.fromJson(data.totAvg);
		$scope.avgGtot = angular.fromJson(data.avgGtot);
		$scope.avgAvg = angular.fromJson(data.avgAvg);
		$scope.totalStudents = $scope.examMarks.length;
		for(let y=0; y < $scope.tableHeader.length; y++){
			var eachKey = $scope.tableHeader[y].key.split(',')[3].replace(/'/g,'');
			$scope.tableHeader[y].outOf = eachKey;
		}
		var allMeans = [];

		function sumTheObj( obj ) {
		  var sum = 0;
		  for( var el in obj ) {
		    if( obj.hasOwnProperty( el ) ) {
		      sum += parseFloat( obj[el] );
		    }
		  }
		  return sum;
		}

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
		if($scope.isPlain == true){
			// do not round off
			var theMean = $scope.totalMarks[key]/$scope.totalStudents;
			return theMean.toFixed(2) || '-' ;
		}else{
			// round off
			return Math.round($scope.totalMarks[key]/$scope.totalStudents,1) || '-' ;
		}
	}


} ]);
