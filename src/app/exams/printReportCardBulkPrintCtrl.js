'use strict';

angular.module('eduwebApp').
controller('printReportCardBulkPrintCtrl', ['$scope', '$rootScope',
function($scope, $rootScope){

	var initializeController = function()
	{

		var loadStreamPOsition = function(response, status)
		{
			var result = angular.fromJson(response);
			console.log("streamPosition - >");
			console.log(response);
			$scope.streamRankPosition = result.data.streamRank[0].position;
			$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;

				console.log($scope.streamRankPosition);
				console.log($scope.streamRankOutOf);

		}

		$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );

		var data = window.printCriteria;
		var getPrintRank = localStorage.getItem("printStreamRank");
		var getStreamRankOutOf = localStorage.getItem("printStreamRankOutOf");
		var getClassPos = localStorage.getItem("printClassPos");
		$rootScope.isPrinting = true;
		$scope.showReportCard = true;
		$scope.AllData2.student = angular.fromJson(data.student);
		$scope.AllData2.report = angular.fromJson(data.report);
		$scope.AllData2.overall = angular.fromJson(data.overall);
		$scope.AllData2.overallLastTerm = angular.fromJson(data.overallLastTerm);
		$scope.AllData2.examTypes = angular.fromJson(data.examTypes);
		$scope.AllData2.reportData = angular.fromJson(data.reportData);
		$scope.AllData2.totals = angular.fromJson(data.totals);
		$scope.AllData2.comments = angular.fromJson(data.comments);
		$scope.AllData2.graphPoints = angular.fromJson(data.graphPoints);
		$scope.AllData2.nextTermStartDate = data.nextTermStartDate;
		$scope.AllData2.currentTermEndDate = data.currentTermEndDate;
		//$scope.total_overall_mark = data.total_overall_mark;
		$scope.AllData2.reportCardType = data.report_card_type;
		$scope.AllData2.chart_path = data.chart_path;
		$scope.AllData2.motto = data.motto;
		$scope.AllData2.currentClassPosition = data.currentClassPosition;
		$scope.AllData2.streamRankPosition = data.streamRankPosition;
		// $scope.AllData2.student.streamRankOutOf = data.streamRankOutOf; //we need this
		$scope.streamRankLastTerm = data.streamRankLastTerm,
		$scope.AllData2.overallSubjectMarks = angular.fromJson(data.subjectOverall);
		$scope.AllData2.thisTermMarks = data.thisTermMarks;
		$scope.AllData2.thisTermMarksOutOf = data.thisTermMarksOutOf;
		$scope.AllData2.thisTermGrade = data.thisTermGrade;
		$scope.AllData2.thisTermPercentage = data.thisTermPercentage;
		$scope.isSchool = ( window.location.host.split('.')[0] == "newlightgirls" ? true : false);
		$scope.isStudentImage = ( window.location.host.split('.')[0] == "rongaiboys" ? true : false);
		// $scope.currentClassPosition.position = getClassPos;
		// $scope.streamRankPosition = getPrintRank;
		// $scope.streamRankOutOf = getStreamRankOutOf;


		$scope.loading = false;

		setTimeout( function(){
			window.print();

			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 90000);
		}, 90000); //Giving it 90 seconds to load (might have to increase for larger data sets)

	}
	setTimeout(initializeController,1);

	$scope.$on('$destroy', function() {
		$rootScope.isPrinting = false;
    });



} ]);
