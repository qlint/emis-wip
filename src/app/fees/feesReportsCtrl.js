'use strict';

angular.module('eduwebApp').
controller('feesReportsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse', '$location',
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
	//$scope.loading = true;

	$scope.preLoadMessageH1 = "MAKE A SELECTION ABOVE TO LOAD A REPORT";
	$scope.preLoadMessageH3 = "Supported reports are in tables, charts and graphs";

	$scope.initialReportLoad = true; // initial items to show before any report is loaded
	$scope.showReport = false; // hide the reports div until needed
	$scope.overallBalancesAnalysisTable = false; // show the table for overall balances
	$scope.studentFeeItemsAnalysisTable = false; // show the table for student fee items
	$scope.activeChartTab = false; // hide charts
	
	$scope.fetchBalances = function()
    	{   
    	    var fetchOverallBalances = function(response,status){
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
        				
        				        $scope.chartData = $scope.overallBalances;
                	            
                	            var theLabels = [];
        	                    var thePaid = [];
        	        
                    	        $scope.chartData.forEach(function(label) {
                                    theLabels.push(label.fee_item);
                                    thePaid.push(Number(label.total_paid));
                                });
            
                                // we want to iterate thru each item in the arr and create a nested arr where only the ith item is not 0
                                // eg arr [ [5,0,0,0],[0,7,0,0] ]
                                
                                var paidSummation = function(arr){
                                    return arr.reduce(function(a,b){
                                    return a + b
                                  }, 0);
                                }
                                
                                $scope.overallPaid = paidSummation(thePaid); // this is the total of all payments
                                
                                var paidLength = thePaid.length;
                                var newPaidVals = [];
                                
                                for (var v = 0; v < paidLength; v++) {
                                    
                                    var newArr = [];
                                    newArr.length = paidLength;
                                    var newArrLength = newArr.length;
                                    
                                    for(var w = 0; w < newArrLength; w++){
                                        
                                        if(newArr[w] == null || newArr[w] == undefined){
                                            newArr[w] = 0;
                                        }
                                        var fullPercentage = (thePaid[v] / $scope.overallPaid) * 100;
                                        var roundedPercentage = fullPercentage.toFixed(1);
                                        console.log(thePaid[v],"Overall = " + $scope.overallPaid,fullPercentage,roundedPercentage);
                                        newArr[v] = roundedPercentage;
                                    }
                                    newPaidVals.push(newArr);
                                }
                                console.log(newPaidVals);
                                
                                
                                function getRandomColor() {
                                    var letters = '0123456789ABCDEF';
                                    var color = '#';
                                    for (var i = 0; i < 6; i++) {
                                        color += letters[Math.floor(Math.random() * 16)];
                                    }
                                    myColors.push(color);
                                    return color;
                                }
                                
                                var myColors = [];
                                for(var y=0; y<newPaidVals.length; y++){
                                    getRandomColor();
                                }
                                
                    	        Chart.controllers.doughnut.prototype.calculateTotal = function() {
                                  return 100;
                                };
                                
                                var ctx = document.getElementById('canvasReport').getContext('2d');
                                
                                var myDataSets = [];
                                
                                newPaidVals.forEach(function(eachArrItem) {
                                    var oneDataSet = {
                                        data: eachArrItem,
                                        backgroundColor: myColors
                                    }
                                    myDataSets.push(oneDataSet);
                                });
                                
                                new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        datasets: myDataSets,
                                        labels: theLabels
                                    },
                                    options: {
                                        rotation: 1 * Math.PI,
                                        circumference: 2 * Math.PI,
                                        cutoutPercentage: 15,
                                        legend: {
                                            position: 'right'
                                        }
                                    }
                                });
                                document.getElementById("canvasReport").style.width = "65%"; // make the chart samller to fit on screen
                                document.getElementById("canvasReport").style.height = "auto"; // set the height to auto to maintain aspect ratio
                                
        			}
        		}
        		else
        		{
        			$scope.errMsg = result.data;
        		}
    		}
    	    
    	    var request = $scope.filters.term_id;
    		apiService.getOverallFinancials(request, fetchOverallBalances, apiError);
    		
    		
    	}

	var initializeController = function ()
	{
		// get classes
		var requests = [];

		var deferred = $q.defer();
		requests.push(deferred.promise);

		// get terms
		var deferred2 = $q.defer();
		requests.push(deferred2.promise);
		if( $rootScope.terms === undefined )
		{
			apiService.getTerms(undefined, function(response,status)
			{
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.terms = result.data;
					$rootScope.terms = result.data;

					var currentTerm = $scope.terms.filter(function(item){
						if( item.current_term ) return item;
					})[0];
					$scope.filters.term_id = currentTerm.term_id;
					deferred2.resolve();
				}
				else
				{
					deferred2.reject();
				}

			}, function(){deferred2.reject();});
		}
		else
		{
			$scope.terms = $rootScope.terms;
			var currentTerm = $scope.terms.filter(function(item){
				if( item.current_term ) return item;
			})[0];
			$scope.filters.term_id = currentTerm.term_id;
			deferred2.resolve();
		}

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
	    $scope.activeChartTab = false; // hide charts
	    
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
	    $scope.activeChartTab = false; // hide charts
	    
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

		function beautifyOverallBalancesAnalysisTable() {
			// data tables - prepare the table for presentation and download
		    var docName = "Overall School Balances Analysis";
	        var targetTable = document.getElementById('overallBalancesAnalysisTable').rows[0].cells.length;
	        var orderCol = targetTable - 1;

			$('#overallBalancesAnalysisTable').DataTable( {
	            fixedHeader: true,
	            dom: 'Bfrtip',
	            "columnDefs": [
	                {"className": "dt-center", "targets": "_all"}
	            ],
	            buttons: [
	                {
	                    extend: 'excelHtml5',
	                    title: docName
	                },
	                {
	                    extend: 'csvHtml5',
	                    title: docName
	                },
	                {
	                    extend: 'pdfHtml5',
	                    title: docName
	                }
	              ],
	              "order": [[orderCol,"asc"]],
	              "bStateSave": true
	      } );
	      // end data tables
		}
		setTimeout(beautifyOverallBalancesAnalysisTable, 3000);

	}

	$scope.gotoDiv1 = function(el) {
	    
	    console.log("First tab",el);
        var newHash = '1a';
        if ($location.hash() !== newHash) {
            $location.hash('1a');
            
            document.getElementById("2a").classList.remove("active");
	        document.getElementById("1a").classList.add("active");
	        
        } else {
            // $anchorScroll();
        }
        
        $scope.activeChartTab = false; // hide charts
        document.getElementById("canvasReport").style.display = "none";
        
        if($scope.overallBalancesAnalysisTable == true){
            
            document.getElementById("overallBalancesAnalysisTableDiv").style.display = "block";
            document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "none";
            $scope.studentFeeItemsAnalysisTable = false;
            
        }else if($scope.studentFeeItemsAnalysisTable == true){
            
            $scope.overallBalancesAnalysisTable = false;
            document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "block";
            document.getElementById("overallBalancesAnalysisTableDiv").style.display = "none";
            
        }else{
            
            $scope.preLoadMessageH1 = "MAKE A SELECTION ABOVE TO LOAD A REPORT";
	        $scope.preLoadMessageH3 = "Supported reports are in tables, charts and graphs";

        	$scope.initialReportLoad = true; // initial items to show before any report is loaded
        	$scope.showReport = false; // hide the reports div until needed
        	$scope.overallBalancesAnalysisTable = false; // show the table for overall balances
        	$scope.studentFeeItemsAnalysisTable = false; // show the table for student fee items
        	$scope.activeChartTab = false; // hide charts
        	
        	initializeController();
        }
        
     };

     $scope.gotoDiv2 = function(el) {
         
	    console.log("Second tab",el);
	    
        var newHash = '2a';
        if ($location.hash() !== newHash) {
            $location.hash('2a');
            
            // lets first load chart.js
            $.getScript('/components/Chart2.8.min.js', function()
            // $.getScript('/components/overviewFiles/js/apexcharts.js', function()
            {
                // script is now loaded and executed.
                document.getElementById("1a").classList.remove("active");
	            document.getElementById("2a").classList.add("active");

                if($scope.overallBalancesAnalysisTable == true){
                    
                    // hide the active table to pave way for charts visibility
                    $scope.showReport = true; // show the div
        	        $scope.overallBalancesAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        $scope.studentFeeItemsAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        document.getElementById("overallBalancesAnalysisTableDiv").style.display = "none";
        	        document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "none";
        	        $scope.activeChartTab = true; // show charts
        	        
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
        	        console.log("The labels are:",theLabels);
        	        
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
        			
        			// document.getElementById("canvasReport").classList.remove("ng-hide");
        	        
        	    }else if($scope.studentFeeItemsAnalysisTable == true){
        	        
        	        // hide the active table to pave way for charts visibility
        	        $scope.showReport = true; // show the div
        	        $scope.overallBalancesAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        $scope.studentFeeItemsAnalysisTable = false; // hide the active table to pave way for charts visibility
        	        document.getElementById("overallBalancesAnalysisTableDiv").style.display = "none";
        	        document.getElementById("studentFeeItemsAnalysisTableDiv").style.display = "none";
        	        $scope.activeChartTab = true; // show charts
        	        
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

		function beautifyStudentPaymentsAnalysisTable() {
			// data tables - prepare the table for presentation and download
		    var docName = "Student Fee Payments Analysis";
	        var targetTable = document.getElementById('studentFeeItemsAnalysisTable').rows[0].cells.length;
	        var orderCol = targetTable - 1;

			$('#studentFeeItemsAnalysisTable').DataTable( {
	            fixedHeader: true,
	            dom: 'Bfrtip',
	            "columnDefs": [
	                {"className": "dt-center", "targets": "_all"}
	            ],
	            buttons: [
	                {
	                    extend: 'excelHtml5',
	                    title: docName
	                },
	                {
	                    extend: 'csvHtml5',
	                    title: docName
	                },
	                {
	                    extend: 'pdfHtml5',
	                    title: docName
	                }
	              ],
	              "order": [[orderCol,"asc"]],
	              "bStateSave": true
	      } );
	      // end data tables
		}
		setTimeout(beautifyStudentPaymentsAnalysisTable, 3000);

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
