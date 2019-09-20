'use strict';

angular.module('eduwebApp').
controller('transportReportsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse', '$location',
function($scope, $rootScope, apiService, $timeout, $window, $q, $parse, $location){

	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;
	$rootScope.modalLoading = false;
	$scope.alert = {};
	$scope.refreshing = false;
	$scope.activeChartTab = false; // hide charts

	$scope.needsClass = false;
	$scope.needsStream = false;
	$scope.needsZone = false;
	$scope.needsBus = false;
	$scope.needsTrip = false;

	$scope.reportTypes = {
		allStudents: ['All Students With Transport',
									'All Students Who Use A Bus',
									'All Students In A Trip',
									'All Students In Transport Zone',
									'All Students With Transport Balance',
									'All Students In A Trip Who Use A Bus'],
		classStudents:['Class Students With Transport',
									'Class Students Who Use A Bus',
									'Class Students In A Trip',
									'Class Students In Transport Zone',
									'Class Students With Transport Balance',
									'Class Students In A Trip Who Use A Bus']
	};

	$scope.filters.report_type = 'All Students With Transport';

	$scope.preLoadMessageH1 = "MAKE A SELECTION ABOVE TO LOAD A REPORT";
	$scope.preLoadMessageH3 = "Supported reports are in tables, charts and graphs";

	$scope.initialReportLoad = true; // initial items to show before any report is loaded
	$scope.showReport = false; // hide the reports div until needed
	$scope.allWithTranspTable = false;
	$scope.studentFeeItemsAnalysisTable = false; // show the table for student fee items

	$scope.fetchOvrlBalances = function()
    	{
    	    $scope.chartData = $scope.overallBalances;
        	var theLabels = [];
        	var theDue = [];
        	var thePaid = [];
        	var theBalance = [];
        	$scope.chartData.forEach(function(label) {
                theLabels.push(label.fee_item);
                theDue.push(label.total_due);
                thePaid.push(label.total_paid);
                theBalance.push(label.balance);
            });

        	var barChartData = {
            	labels: theLabels,
            	datasets: [{
            				label: 'Total Paid',
            				backgroundColor: '#17FF00',
            				stack: 'Stack 0',
            				data: thePaid
            			}, {
            				label: 'Balance',
            				backgroundColor: '#FF0000',
            				stack: 'Stack 0',
            				data: theBalance
            			}, {
            				label: 'Total Due',
            				backgroundColor: '#00D1FF',
            				stack: 'Stack 1',
            				data: theDue
            	}]

            };

            var ctx = document.getElementById('canvasReport').getContext('2d');
        			new Chart(ctx, {
        				type: 'bar',
        				data: barChartData,
        				options: {
        					title: {
        						display: true,
        						text: 'Overall School Balances Analysis'
        					},
        					tooltips: {
        						mode: 'index',
        						intersect: false
        					},
        					responsive: true,
        					scales: {
        						xAxes: [{
        							stacked: true,
        						}],
        						yAxes: [{
        							stacked: true
        						}]
        					}
        				}
        			});
    	}

	$scope.fetchAllStudentsWithTransport = function()
    {
    	    var fetchAllStudentsWithTransp = function(response,status){
    		    var result = angular.fromJson( response );
        		if( result.response == 'success' )
        		{
        			if( result.nodata )
        			{
        				$scope.errMsg = "No data found for this search criteria.";
        			}
        			else
        			{
        				$scope.reportData = result.data;
								$scope.studentsWithTranspCount = $scope.reportData.length;
								$scope.showReport = true;
								$scope.allWithTranspTable = true;
								$scope.allStudentsInBus = false;
								$scope.allStudentsInTrip = false;
								$scope.allStudentsInZone = false;
								$scope.allStudentsWithBalance = false;
								$scope.allStudentsInTripInBus = false;
								$scope.classStdTrans = false;
								$scope.classStudentsInBus = false;
								$scope.classStudentsInTrip = false;
								$scope.classStudentsInZone = false;
								$scope.classStudentsInTripInBus = false;
								$scope.initialReportLoad = false;
        			}
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
				apiService.getAllStudentsWithTransport({}, fetchAllStudentsWithTransp, apiError);
    }

    $scope.fetchClassStudentsWithTransport = function()
    {
            let classId = $scope.filters.class;
    	    var fetchClassStudentsWithTransp = function(response,status){
    		    var result = angular.fromJson( response );
        		if( result.response == 'success' )
        		{
        			if( result.nodata )
        			{
        				$scope.classStudentsWithTransp = [];
        			}
        			else
        			{
        				$scope.classStudentsWithTransp = result.data;
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = true;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
        			}
							$scope.classStdsWithTranspCount = $scope.classStudentsWithTransp.length;
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
			apiService.getClassStudentsWithTransp(classId, fetchClassStudentsWithTransp, apiError);
    }

	$scope.fetchAllStudentsInBus = function(){
		// get the bus id
		let busId = $scope.filters.bus;
		var loadStudentsInBus = function(response,status)
	    {
	        var result = angular.fromJson(response);
	    		if( result.response == 'success')
	    		{
	    			$scope.studentsInBus = ( result.nodata ? [] : result.data );
						$scope.studentsInBus.forEach(function(student) {
							student.route = student.route.split(' - ')[0];
						});
						$scope.studentsInBusCount = $scope.studentsInBus.length;
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = true;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
	    			console.log("Fetching students in the bus",$scope.studentsInBus);
	    		}
	    		else
	    		{
	    			$scope.error = true;
	    			$scope.errMsg = result.data;
	    		}

	    }
		apiService.getStudentsInBus(busId, loadStudentsInBus, apiError);

	}

	$scope.fetchAllStudentsInTrip = function(){
		// get the filter id
		console.log($scope.filters);
		let filterId = $scope.filters.trip;
		var loadStudentsInTrip = function(response,status)
	    {
	        var result = angular.fromJson(response);
	    		if( result.response == 'success')
	    		{
	    		    // setTimeout(function(){ $scope.studentsInTrip = ( result.nodata ? [] : result.data ); console.log($scope.studentsInTrip); }, 2000);
	    		    $scope.studentsInTrip = ( result.nodata ? [] : result.data );
							$scope.studentsInTrip.forEach(function(student) {
								student.route = student.route.split(' - ')[0];
							});
	    			$scope.studentsInTripCount = $scope.studentsInTrip.length;
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsInTrip = true;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
					if($scope.studentsInTrip.length == 0){
					    console.log("No data found for this selection");
					    $scope.studentsInTrip[0] = {student_name: 'No Records', class_name: 'No Records', student_destination: 'No Records', bus: 'No Records', driver_name: 'No Records', guide_name: 'No Records', trip_name: null}
					}
	    		}
	    		else
	    		{
	    			$scope.error = true;
	    			$scope.errMsg = result.data;
	    		}

	    }
		apiService.getAllStudentsInTrip(filterId, loadStudentsInTrip, apiError);

	}

	$scope.fetchAllStudentsInZone = function(){
		var loadStudentsInZone = function(response,status){
    		    var result = angular.fromJson( response );
        		if( result.response == 'success' )
        		{
        			if( result.nodata )
        			{
        				$scope.studentsInZone = [];
        				$scope.studentsInZone[0] = {student_name: 'No Records', class_name: 'No Records', student_destination: 'No Records', route: 'No Records', amount: 'No Records'};
        			}
        			else
        			{
        				$scope.studentsInZone = result.data;
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = true;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
        			}
							$scope.studentsInZoneCount = $scope.studentsInZone.length;
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
		apiService.getAllStudentsInTranspZone({}, loadStudentsInZone, apiError);

	}

	$scope.fetchAllStudentsWithBalance = function(){
		var loadStudentsWithBalance = function(response,status){
    		    var result = angular.fromJson( response );
        		if( result.response == 'success' )
        		{
        			if( result.nodata )
        			{
        					$scope.studentsWithBalance = [];
        					$scope.studentsWithBalance[0] = {student_name: 'No Records', class_name: 'No Records', destination: 'No Records', route: 'No Records', amount: 'No Records', payment: 'No Records', balance: 'No Records'};
        			}
        			else
        			{
        				$scope.studentsWithBalance = result.data;
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsWithBalance = true;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
        			}
								$scope.studentsWithBalanceCount = 	$scope.studentsWithBalance.length;
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
		apiService.getAllStudentsWithTranspBalance({}, loadStudentsWithBalance, apiError);

	}

	$scope.fetchClassStudentsInBus = function(){
		// get the bus & classs id
		let busId = $scope.filters.bus;
		let classCatId = $scope.filters.class;
		var loadClassStudentsInBus = function(response,status)
	    {
	        var result = angular.fromJson(response);
	    		if( result.response == 'success')
	    		{
	    			// $scope.classStudentsInSelectedBus = ( result.nodata ? [] : result.data );
	    			$scope.classStudentsInSelectedBus = ( result.data == undefined || result.data ==null ? [] : result.data );
						$scope.classStudentsInSelectedBus.forEach(function(student) {
							student.route = student.route.split(' - ')[0];
						});
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = true;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.initialReportLoad = false;
						$scope.classStudentsInTripInBus = false;
	    			if($scope.classStudentsInSelectedBus.length == 0){
	    			    $scope.classStudentsInBus = [{student_name: 'No Records', class_name: 'No Records', student_destination: 'No Records', trip_name: 'No Records', driver_name: 'No Records', guide_name: 'No Records'}];
	    			}
						$scope.classStudentsInSelectedBusCount = $scope.classStudentsInSelectedBus.length;
	    		}
	    		else
	    		{
	    			$scope.error = true;
	    			$scope.errMsg = result.data;
	    		}

	    }
		apiService.getClassStudentsInBus(busId + '/' + classCatId, loadClassStudentsInBus, apiError);

	}

	$scope.fetchClassStudentsInTrip = function(){
		// get the trip and class id
		let tripId = $scope.filters.trip;
		let classCatId = $scope.filters.class;
		var loadClassStudentsInTrip = function(response,status)
	    {
	        var result = angular.fromJson(response);
	    		if( result.response == 'success')
	    		{
	    		    $scope.classStudentsInSelectedTrip = ( result.nodata ? [] : result.data );
							$scope.classStudentsInSelectedTrip.forEach(function(student) {
								student.route = student.route.split(' - ')[0];
							});
	    			$scope.classStudentsInSelectedTripCount = $scope.classStudentsInSelectedTrip.length;
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = true;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
					if($scope.classStudentsInSelectedTrip.length == 0){
					    console.log("No data found for this selection");
					    $scope.classStudentsInSelectedTrip = [{student_name: 'No Records', class_name: 'No Records', student_destination: 'No Records', bus: 'No Records', driver_name: 'No Records', guide_name: 'No Records', trip_name: null}];
					}
	    		}
	    		else
	    		{
	    			$scope.error = true;
	    			$scope.errMsg = result.data;
	    		}

	    }
		apiService.getClassStudentsInTrip(tripId + '/' + classCatId, loadClassStudentsInTrip, apiError);

	}

	$scope.fetchClassStudentsInZone = function(){
	    // get the class id
	    let classCatId = $scope.filters.class;
		var loadClassStudentsInZone = function(response,status){
    		    var result = angular.fromJson( response );
        		if( result.response == 'success' )
        		{
        			if( result.nodata )
        			{
        				$scope.classStudentsInSelectedZone = [];
        				$scope.classStudentsInSelectedZone[0] = {student_name: 'No Records', class_name: 'No Records', student_destination: 'No Records', route: 'No Records', amount: 'No Records'};
        			}
        			else
        			{
        				$scope.classStudentsInSelectedZone = result.data;
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = true;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
        			}
							$scope.classStudentsInSelectedZoneCount = $scope.classStudentsInSelectedZone.length;
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
		apiService.getClassStudentsInTranspZone(classCatId, loadClassStudentsInZone, apiError);

	}

	$scope.fetchAllStudentsInTripInBus = function(){
	    let busId = $scope.filters.bus;
	    let tripId = $scope.filters.trip;
	    let thisParam = busId + '/' + tripId;

	    var fetchAllStudentsInBusInTrip = function(response,status){
    		    var result = angular.fromJson( response );
        		if( result.response == 'success' )
        		{
        			if( result.nodata )
        			{
        				$scope.classStudentsWithTransp = [];
        			}
        			else
        			{
        				$scope.allStudentsInBusInTrp = result.data;
								$scope.allStudentsInBusInTrp.forEach(function(student) {
								  student.route = student.route.split(' - ')[0];
								});
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = true;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = false;
						$scope.initialReportLoad = false;
        			}
							$scope.allStudentsInBusInTrpCount = $scope.allStudentsInBusInTrp.length;
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
			apiService.getAllStudentsInBusInTrip(thisParam, fetchAllStudentsInBusInTrip, apiError);
	}

	$scope.fetchClassStudentsInTripInBus = function(){
	    let classId = $scope.filters.class;
	    let busId = $scope.filters.bus;
	    let tripId = $scope.filters.trip;
	    let thisParam = classId + '/' + busId + '/' + tripId;

	    var fetchClassStudentsInBusInTrip = function(response,status){
    		    var result = angular.fromJson( response );
        		if( result.response == 'success' )
        		{
        			if( result.nodata )
        			{
        				$scope.classStudentsWithTransp = [];
        			}
        			else
        			{
        				$scope.classStudentsInBusInTrp = result.data;
								$scope.classStudentsInBusInTrp.forEach(function(student) {
									student.route = student.route.split(' - ')[0];
								});
						$scope.showReport = true;
						$scope.allWithTranspTable = false;
						$scope.allStudentsInBus = false;
						$scope.allStudentsInTrip = false;
						$scope.allStudentsInZone = false;
						$scope.allStudentsWithBalance = false;
						$scope.allStudentsInTripInBus = false;
						$scope.classStdTrans = false;
						$scope.classStudentsInBus = false;
						$scope.classStudentsInTrip = false;
						$scope.classStudentsInZone = false;
						$scope.classStudentsInTripInBus = true;
						$scope.initialReportLoad = false;
        			}
							$scope.classStudentsInBusInTrpCount = $scope.classStudentsInBusInTrp.length;
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
			apiService.getClassStudentsInBusInTrip(thisParam, fetchClassStudentsInBusInTrip, apiError);
	}

	var initializeController = function ()
	{
		apiService.getClassCats(undefined, function(response){
			var result = angular.fromJson(response);
			// store these as they do not change often
			if( result.response == 'success')	$scope.classCats = result.data;

		}, function(){});

		if($scope.filters.report_type == 'All Students With Transport'){ $scope.fetchAllStudentsWithTransport(); }

		// get all active buses
		var getBusesParam = true;
		apiService.getAllBuses(getBusesParam, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
					$scope.buses = ( result.nodata ? [] : result.data );

			}
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);

		apiService.getAllSchoolBusTrips({}, function(response){
          var result = angular.fromJson(response);
          if( result.response == 'success')
          {
            if( result.nodata !== undefined)
            {
              $scope.trips = [];
            }
            else
            {
              $scope.trips = result.data;
            }
          }

        }, function(){console.log("There was an error fetching the existing trips.")});

	}
	$timeout(initializeController,1);

	$scope.$watch('filters.analysis',function(newVal,oldVal){
		if( newVal == oldVal ) return;
	});

	$scope.trackReportType = function(el){
		let selectedReport = el.filters.report_type;
		console.log(selectedReport);

		if(selectedReport == 'All Students With Transport'){
			$scope.needsClass = false;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = false;
			$scope.needsTrip = false;
		}else if(selectedReport == 'All Students Who Use A Bus'){
			$scope.needsClass = false;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsTrip = false;
			$scope.needsBus = true;
		}else if(selectedReport == 'All Students In A Trip'){
		    $scope.needsClass = false;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = false;
			$scope.needsTrip = true;
		}else if(selectedReport == 'All Students In Transport Zone'){
			$scope.needsClass = false;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = false;
			$scope.needsTrip = false;
		}else if(selectedReport == 'All Students With Transport Balance'){
		    $scope.needsClass = false;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = false;
			$scope.needsTrip = false;
		}else if(selectedReport == 'Class Students With Transport'){
		    $scope.needsClass = true;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = false;
			$scope.needsTrip = false;
		}else if(selectedReport == 'Class Students Who Use A Bus'){
		    $scope.needsClass = true;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = true;
			$scope.needsTrip = false;
		}else if(selectedReport == 'Class Students In A Trip'){
		    $scope.needsClass = true;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = false;
			$scope.needsTrip = true;
		}else if(selectedReport == 'Class Students In Transport Zone'){
		    $scope.needsClass = true;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = false;
			$scope.needsTrip = false;
		}else if(selectedReport == 'All Students In A Trip Who Use A Bus'){
		    $scope.needsClass = false;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = true;
			$scope.needsTrip = true;
		}else if(selectedReport == 'Class Students In A Trip Who Use A Bus'){
		    $scope.needsClass = true;
			$scope.needsStream = false;
			$scope.needsZone = false;
			$scope.needsBus = true;
			$scope.needsTrip = true;
		}
	}

	$scope.loadSelection = function()
	{
	    if($scope.filters.report_type == 'All Students With Transport'){
			$scope.fetchAllStudentsWithTransport();
		}else if($scope.filters.report_type == 'All Students Who Use A Bus'){
			$scope.fetchAllStudentsInBus();
		}else if($scope.filters.report_type == 'All Students In A Trip'){
			$scope.fetchAllStudentsInTrip();
		}else if($scope.filters.report_type == 'All Students In Transport Zone'){
			$scope.fetchAllStudentsInZone();
		}else if($scope.filters.report_type == 'All Students With Transport Balance'){
		    $scope.fetchAllStudentsWithBalance();
		}else if($scope.filters.report_type == 'Class Students With Transport'){
		    $scope.fetchClassStudentsWithTransport();
		}else if($scope.filters.report_type == 'Class Students Who Use A Bus'){
		    $scope.fetchClassStudentsInBus();
		}else if($scope.filters.report_type == 'Class Students In A Trip'){
		    $scope.fetchClassStudentsInTrip();
		}else if($scope.filters.report_type == 'Class Students In Transport Zone'){
		    $scope.fetchClassStudentsInZone();
		}else if($scope.filters.report_type == 'All Students In A Trip Who Use A Bus'){
		    $scope.fetchAllStudentsInTripInBus();
		}else if($scope.filters.report_type == 'Class Students In A Trip Who Use A Bus'){
		    $scope.fetchClassStudentsInTripInBus();
		}else{
			// make a valid selection message
			$scope.preLoadMessageH1 = "";
			$scope.preLoadMessageH3 = "There seems to be a problem with the current selection.";
		}
	}

    $scope.getOverallBalances = function()
	{
	    $scope.showReport = true; // show the div
	    $scope.reportTitle = 'School Balances Analysis';

	    $scope.initialReportLoad = false; // initial items to show before any report is loaded

	    $scope.studentFeeItemsAnalysisTable = false; // hide the student fee items table if its already showing
	    $scope.overallBalancesAnalysisTable = true; // show the table for overall balances analysis

	    var request = $scope.filters.term_id;
    	apiService.getOverallFinancials(request, loadOverallBalances, apiError);
	}

	$scope.getStudentPayments = function()
	{
	    $scope.showReport = true; // show the div
	    $scope.reportTitle = 'Student Fee Items Analysis - % on Total Payments';

	    $scope.initialReportLoad = false; // initial items to show before any report is loaded

	    $scope.overallBalancesAnalysisTable = false; // hide the table for overall balances analysis if its already showing
	    $scope.studentFeeItemsAnalysisTable = true; // show the student fee items table

	    var request = $scope.filters.term_id;
		apiService.getOverallStudentFeePayments(request, loadStudentPayments, apiError);
	}

	var loadOverallBalances = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.errMsg = "No data found for this search criteria.";
			}
			else
			{
				$scope.overallBalances = result.data;
				$scope.chartDataGlbl = $scope.overallBalances;
			}
		}
		else
		{
			$scope.errMsg = result.data;
		}

	}

	$scope.gotoDiv1 = function(el) {

        var newHash = '1a';
        if ($location.hash() !== newHash) {
            $location.hash('1a');

            document.getElementById("2a").classList.remove("active");
	        document.getElementById("1a").classList.add("active");

        } else {
            // $anchorScroll();
        }

        $scope.overallBalancesAnalysisTable = ( $scope.returnToOverallBalancesAnalysis == true ? true : false);
        $scope.studentFeeItemsAnalysisTable = ( $scope.returnToStudentFeeItemsAnalysis == true ? true : false);

        $scope.activeChartTab = false; // hide charts
        document.getElementById("canvasReport").style.display = "none";

        if($scope.overallBalancesAnalysisTable == true){

            document.getElementById("overallBalancesAnalysisTableDiv").style.display = "block";
            $scope.getOverallBalances(); // refetch the data
            document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "none";
            $scope.studentFeeItemsAnalysisTable = false;

        }else if($scope.studentFeeItemsAnalysisTable == true){

            document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "block";
            $scope.getStudentPayments(); // refetch the data
            document.getElementById("overallBalancesAnalysisTableDiv").style.display = "none";
            $scope.overallBalancesAnalysisTable = false;

        }else{

            $scope.preLoadMessageH1 = "MAKE A SELECTION ABOVE TO LOAD A REPORT";
	        $scope.preLoadMessageH3 = "Supported reports are in tables, charts and graphs";

        	$scope.initialReportLoad = true; // initial items to show before any report is loaded
        	$scope.showReport = false; // hide the reports div until needed
        	$scope.overallBalancesAnalysisTable = false; // show the table for overall balances
        	$scope.studentFeeItemsAnalysisTable = false; // show the table for student fee items

        	$("#overallBalancesAnalysisTable").DataTable().destroy();
        	$("#studentFeeItemsAnalysisTable").DataTable().destroy();
        	initializeController();
        }

     };

     $scope.gotoDiv2 = function(el) {

        var newHash = '2a';
        if ($location.hash() !== newHash) {
            $location.hash('2a');

            // lets first load chart.js
            // $.getScript('/components/Chart2.8.min.js', function()
            $.getScript('/components/overviewFiles/js/apexcharts.js', function()
            {
                // script is now loaded and executed.
                document.getElementById("1a").classList.remove("active");
	            document.getElementById("2a").classList.add("active");

	            $scope.activeChartTab = true; // show charts

                if($scope.overallBalancesAnalysisTable == true){

                    // hide the active table to pave way for charts visibility
                    $scope.showReport = true; // show the div
        	        $scope.overallBalancesAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        $scope.studentFeeItemsAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        document.getElementById("overallBalancesAnalysisTableDiv").style.display = "none";
        	        document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "none";
        	        $scope.returnToOverallBalancesAnalysis = true;

        	        $scope.fetchOvrlBalances();

        	    }else if($scope.studentFeeItemsAnalysisTable == true){

        	        // hide the active table to pave way for charts visibility
        	        $scope.showReport = true; // show the div
        	        $scope.overallBalancesAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        $scope.studentFeeItemsAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        document.getElementById("overallBalancesAnalysisTableDiv").style.display = "none";
        	        document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "none";
        	        $scope.returnToStudentFeeItemsAnalysis = true;

        	        $scope.fetchBalances();

        	    }else{
        	        // no selection made yet
        	    }

            });

        } else {
            // $anchorScroll();
        }

     };

	var loadStudentPayments = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.errMsg = "No data found for this search criteria.";
			}
			else
			{
				$scope.studentFeeItems = result.data;
			}
		}
		else
		{
			$scope.errMsg = result.data;
		}

	}

	$scope.exportData = function(){

		if($scope.allWithTranspTable == true){
			var divToExport=document.getElementById("allWithTranspTableDiv");
			var rows = document.querySelectorAll('table.allWithTranspTableDiv tr[style="display: table-row;"]');
		}else if($scope.allStudentsInBus == true){
			var divToExport=document.getElementById("allStudentsInBusDiv");
			var rows = document.querySelectorAll('table.allStudentsInBusDiv tr[style="display: table-row;"]');
		}else if($scope.allStudentsInTrip == true){
			var divToExport=document.getElementById("allStudentsInTripDiv");
			var attr = $('table.allStudentsInTripDiv tr').attr('style');
			if(typeof attr !== typeof undefined && attr !== false){
				console.log("Table is unfiltered");
			}else{
				console.log("Table has been filtered");
			}

			var rows = document.querySelectorAll('table.allStudentsInTripDiv tr[style="display: table-row;"]');
			if(rows.length == 0){
				var rows = document.querySelectorAll('table.allStudentsInTripDiv tr');
			}
		}else if($scope.allStudentsInZone){
			var divToExport=document.getElementById("allStudentsInZoneDiv");
			var rows = document.querySelectorAll('table.allStudentsInZoneDiv tr[style="display: table-row;"]');
		}else if($scope.allStudentsWithBalance == true){
			var divToExport=document.getElementById("allStudentsWithBalanceDiv");
			var rows = document.querySelectorAll('table.allStudentsWithBalanceDiv tr[style="display: table-row;"]');
		}else if($scope.classStdTrans == true){
			var divToExport=document.getElementById("classStdTransDiv");
			var rows = document.querySelectorAll('table.classStdTransDiv tr[style="display: table-row;"]');
		}else if($scope.allStudentsInTripInBus == true){
			var divToExport=document.getElementById("allStudentsInTripInBusDiv");
			var rows = document.querySelectorAll('table.allStudentsInTripInBusDiv tr[style="display: table-row;"]');
		}else if($scope.classStudentsInBus == true){
			var divToExport=document.getElementById("classStudentsInBusDiv");
			var rows = document.querySelectorAll('table.classStudentsInBusDiv tr[style="display: table-row;"]');
		}else if($scope.classStudentsInTrip == true){
			var divToExport=document.getElementById("classStudentsInTripDiv");
			var rows = document.querySelectorAll('table.classStudentsInTripDiv tr[style="display: table-row;"]');
		}else if($scope.classStudentsInTripInBus == true){
			var divToExport=document.getElementById("classStudentsInTripInBusDiv");
			var rows = document.querySelectorAll('table.classStudentsInTripInBus tr[style="display: table-row;"]');
		}else if($scope.classStudentsInZone == true){
			var divToExport=document.getElementById("classStudentsInZoneDiv");
			var rows = document.querySelectorAll('table.classStudentsInZoneDiv tr[style="display: table-row;"]');
		}

		function downloadCSV(csv, filename) {
		    var csvFile;
		    var downloadLink;

		    // CSV file
		    csvFile = new Blob([csv], {type: "text/csv"});

		    // Download link
		    downloadLink = document.createElement("a");

		    // File name
		    downloadLink.download = filename;

		    // Create a link to the file
		    downloadLink.href = window.URL.createObjectURL(csvFile);

		    // Hide download link
		    downloadLink.style.display = "none";

		    // Add the link to DOM
		    document.body.appendChild(downloadLink);

		    // Click download link
		    downloadLink.click();
		}

		function exportTableToCSV(filename) {
		    var csv = [];
		    // var rows = document.querySelectorAll('table tr[style*="display: table-row;"]');
				console.log("rows data",typeof rows);
				var headerRow = divToExport.querySelectorAll('table thead tr')[0];
				var titles = headerRow.querySelectorAll("th");
				var titlesText = [];
				for(let x=0; x<titles.length;x++){
					titlesText.push(titles[x].innerText);
				}
				var titlesToCsv = titlesText.join(',');
				console.log(titlesToCsv);
				csv.push(titlesToCsv);

		    for (var i = 0; i < rows.length; i++) {
		        var row = [], cols = rows[i].querySelectorAll("td, th");

		        for (var j = 0; j < cols.length; j++)
		            row.push(cols[j].innerText);

		        csv.push(row.join(","));
		    }

		    // Download CSV file
		    downloadCSV(csv.join("\n"), filename);
		}

		exportTableToCSV($scope.filters.report_type + '.csv');

	}

	$scope.printReport = function()
	{
		function printData()
		{
			if($scope.allWithTranspTable == true){
			 var divToPrint=document.getElementById("allWithTranspTableDiv").firstElementChild;
		 }else if($scope.allStudentsInBus == true){
			 var divToPrint=document.getElementById("allStudentsInBusDiv").firstElementChild;
		 }else if($scope.allStudentsInTrip == true){
			 var divToPrint=document.getElementById("allStudentsInTripDiv").firstElementChild;
		 }else if($scope.allStudentsInZone){
			 var divToPrint=document.getElementById("allStudentsInZoneDiv").firstElementChild;
		 }else if($scope.allStudentsWithBalance == true){
			 var divToPrint=document.getElementById("allStudentsWithBalanceDiv").firstElementChild;
		 }else if($scope.classStdTrans == true){
			 var divToPrint=document.getElementById("classStdTransDiv").firstElementChild;
		 }else if($scope.allStudentsInTripInBus == true){
			 var divToPrint=document.getElementById("allStudentsInTripInBusDiv").firstElementChild;
		 }else if($scope.classStudentsInBus == true){
			 var divToPrint=document.getElementById("classStudentsInBusDiv").firstElementChild;
		 }else if($scope.classStudentsInTrip == true){
			 var divToPrint=document.getElementById("classStudentsInTripDiv").firstElementChild;
		 }else if($scope.classStudentsInTripInBus == true){
			 var divToPrint=document.getElementById("classStudentsInTripInBusDiv").firstElementChild;
		 }else if($scope.classStudentsInZone == true){
			 var divToPrint=document.getElementById("classStudentsInZoneDiv").firstElementChild;
		 }
		   var newWin= window.open("");
		   newWin.document.write(divToPrint.outerHTML);
			 newWin.document.write('<html><head><title>Print Report.</title><link rel="stylesheet" type="text/css" href="css/printReportsStyles.css"></head><body>');
			 // newWin.document.write($("#resultsTable").html());
			 setTimeout(function(){
				 newWin.print();
			   newWin.close();
			 }, 3000);
		   // newWin.print();
		   // newWin.close();
		}
		printData();
	}

	$("#search").keyup(function () {
        var value = this.value.toLowerCase().trim();

        $("table tr").each(function (index) {
            if (!index) return;
            $(this).find("td").each(function () {
                var id = $(this).text().toLowerCase().trim();
                var not_found = (id.indexOf(value) == -1);
                $(this).closest('tr').toggle(!not_found);
                return not_found;
            });
        });
    });

	$scope.refresh = function ()
	{
		$scope.loading = true;
		$scope.refreshing = true;
		$rootScope.loading = true;
		$scope.getStudentExams();
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	$scope.$on('$destroy', function() {
		if($scope.dataGrid){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.fixedHeader.destroy();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		$rootScope.isModal = false;
    });


} ]);
