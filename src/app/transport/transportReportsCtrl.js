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

	$scope.reportTypes = [
		'All Students With Transport',
		'All Students In A Trip',
		'All Students Who Use A Bus'
	];
	$scope.filters.report_type = 'All Students With Transport';

	$scope.preLoadMessageH1 = "MAKE A SELECTION ABOVE TO LOAD A REPORT";
	$scope.preLoadMessageH3 = "Supported reports are in tables, charts and graphs";

	$scope.initialReportLoad = true; // initial items to show before any report is loaded
	$scope.showReport = false; // hide the reports div until needed
	$scope.overallBalancesAnalysisTable = false; // show the table for overall balances
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
								console.log($scope.reportData);
        			}
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
				apiService.getAllStudentsWithTransport({}, fetchAllStudentsWithTransp, apiError);


    	}

	var initializeController = function ()
	{

		if($scope.filters.report_type == 'All Students With Transport'){ $scope.fetchAllStudentsWithTransport(); }

	}
	$timeout(initializeController,1);

	$scope.$watch('filters.analysis',function(newVal,oldVal){
		if( newVal == oldVal ) return;
	});

	$scope.loadSelection = function()
	{
	    if($scope.filters.analysis == "overall_balances"){
			$scope.getOverallBalances();
		}else if($scope.filters.analysis == "student_fee_items"){
			$scope.getStudentPayments();
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

	$scope.printReport = function()
	{
		var selectedTerm = $scope.terms.filter(function(item){
			if( item.term_id == $scope.filters.term_id ) return item;
		})[0];
		var selectedExam =  $scope.examTypes.filter(function(item){
			if( item.exam_type_id == $scope.filters.exam_type_id ) return item;
		})[0];

		var data = {
			criteria: {
				class_name: $scope.selectedClass,
				term: selectedTerm.term_name,
				exam_type: selectedExam.exam_type
			},
			tableHeader: $scope.tableHeader,
			examMarks: $scope.examMarks,
			totalMarks: $scope.totalMarks
		}
		var domain = window.location.host;
		var newWindowRef = window.open('http://' + domain + '/#/exams/analysis/print');
		newWindowRef.printCriteria = data;
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
