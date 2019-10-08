'use strict';

angular.module('eduwebApp').
controller('examsReportsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse', '$location',
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
	$scope.getReport = "examsTable";
	//$scope.loading = true;

	$scope.preLoadMessageH1 = "SELECT A CLASS FROM THE ABOVE FILTER TO LOAD A REPORT";
	$scope.preLoadMessageH3 = "Supported reports are in tables, charts and graphs";

	$scope.initialReportLoad = true; // initial items to show before any report is loaded
	$scope.showReport = false; // hide the reports div until needed
	$scope.classAnalysisTable = false; // show the table for class analysis
	$scope.streamAnalysisTable = false; // show the table for stream analysis
	$scope.improvementsAnalysisTable = false; // show the table for improvements analysis

	$scope.returnToClassAnalysis = false;
	$scope.returnToStreamAnalysis = false;
	$scope.returnToImprovementsAnalysis = false;

    $scope.makeClassPerformanceChart = function()
    {
        // console.log("Exam mars",$scope.examMarks);
        var rawStudentMarks = [];
        var highArrClone = [];
        var lowArrClone = [];
        $scope.examMarks.forEach(function(element) {

            delete element.gender;
            delete element.student_id;
            delete element.student_name;
            delete element.exam_type;
            delete element.rank;
            delete element.total;
            delete element.total_mark;

            Object.keys(element).forEach(function (item) {
            	var itemsArr = item.split(',');
                var newItem = itemsArr[2].trim().replace(/'/g, "");

                element[newItem] = element[item];
                delete element[item];
            });

            rawStudentMarks.push(element);
            highArrClone.push(element);
            lowArrClone.push(element);
        });

        var subjects = [];
        var highestMarks = [];
        var lowestMarks = [];
        var avgMarks = [];

        // this will populate our subjects[]
        function getSubjectLabels(){

            var theSubjects = rawStudentMarks[0];
            Object.keys(theSubjects).forEach(function (subjectNm) {
            	subjects.push(subjectNm);
            });

        }
        getSubjectLabels();
        console.log("Subject labels",subjects);

        // this will populate our avgMarks[]
        function getAvgMarks(){

            for(var k = 0; k < subjects.length; k++){

                $scope.summation = function(items, prop){
                    return items.reduce( function(a, b){
                        return a + b[prop];
                    }, 0);
                };

                var perSubjectTotal = $scope.summation(rawStudentMarks, subjects[k]);
                var perSubjectAvg = perSubjectTotal / rawStudentMarks.length;
                avgMarks.push(Number(perSubjectAvg.toFixed(1)));
            }

        }
        getAvgMarks();
        console.log("The average marks",avgMarks);

        // this will populate our highestMarks[]
        function getHighestMarks(){
            /*
            subjects.forEach(function(highestSubj) {
                var highestPerSubject = Math.max.apply(Math,highArrClone.map( function(o){console.log(o); return o[highestSubj];} ));

                highestMarks.push(highestPerSubject);
            });
            */
            for(var j = 0; j < subjects.length; j++){

                var highestPerSubject = Math.max.apply(Math,highArrClone.map( function(o){return o[subjects[j]];} ));

                highestMarks.push(highestPerSubject);
            }

        }
        getHighestMarks();
        console.log("The highest marks",highestMarks);

        // this will populate our lowestMarks[]
        function getLowestMarks(){

            for(var h = 0; h < subjects.length; h++){

                var lowestPerSubject = Math.min.apply(Math,lowArrClone.map( function(o){return o[subjects[h]];} ));

                lowestMarks.push(lowestPerSubject);
            }

        }
        getLowestMarks();
        console.log("The lowest marks",lowestMarks);

        // build the chart
        var options = {
            chart: {
                height: 350,
                type: 'line',
                shadow: {
                    enabled: true,
                    color: '#000',
                    top: 18,
                    left: 7,
                    blur: 10,
                    opacity: 1
                },
                toolbar: {
                    show: false
                }
            },
            colors: ['#00FF00', '#0000FF', '#FF0000'],
            dataLabels: {
                enabled: true,
            },
            stroke: {
                curve: 'smooth'
            },
            series: [{
                        name: "Highest",
                        data: highestMarks
                    },
                    {
                        name: "Average",
                        data: avgMarks
                    },
                    {
                        name: "Lowest",
                        data: lowestMarks
                    }
            ],
            title: {
                text: 'Average, Highest & Lowest Marks Per Subject',
                align: 'left'
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#878787', 'transparent'], // takes an array which will be repeated on columns
                    opacity: 0.5
                },
            },
            markers: {

                size: 6
            },
            xaxis: {
                categories: subjects,
                title: {
                    text: 'Subjects'
                }
            },
            yaxis: {
                title: {
                    text: 'Performance'
                },
                min: 0,
                max: 100
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        }

        var chart = new ApexCharts(
            document.querySelector("#chart"),
            options
        );

        chart.render();
    }

    $scope.makeStreamPerformanceChart = function()
    {

        // console.log("Exam mars",$scope.examMarks);
        var rawStreamStudentMarks = [];
        var highStreamArrClone = [];
        var lowStreamArrClone = [];
        $scope.examMarks.forEach(function(element) {

            delete element.gender;
            delete element.student_id;
            delete element.student_name;
            delete element.exam_type;
            delete element.rank;
            delete element.total;
            delete element.total_mark;

            Object.keys(element).forEach(function (item) {
            	var itemsArr = item.split(',');
                var newItem = itemsArr[2].trim().replace(/'/g, "");

                element[newItem] = element[item];
                delete element[item];
            });

            rawStreamStudentMarks.push(element);
            highStreamArrClone.push(element);
            lowStreamArrClone.push(element);
        });

        var subjects_stream = [];
        var highestMarks_stream = [];
        var lowestMarks_stream = [];
        var avgMarks_stream = [];

        // this will populate our subjects_stream[]
        function getSubjectLabels(){

            var theSubjects = rawStreamStudentMarks[0];
            Object.keys(theSubjects).forEach(function (subjectNm) {
            	subjects_stream.push(subjectNm);
            });

        }
        getSubjectLabels();
        console.log("Subject labels",subjects_stream);

        // this will populate our avgMarks_stream[]
        function getAvgMarks(){

            for(var k = 0; k < subjects_stream.length; k++){

                $scope.summation = function(items, prop){
                    return items.reduce( function(a, b){
                        return a + b[prop];
                    }, 0);
                };

                var perSubjectTotal = $scope.summation(rawStreamStudentMarks, subjects_stream[k]);
                var perSubjectAvg = perSubjectTotal / rawStreamStudentMarks.length;
                avgMarks_stream.push(Number(perSubjectAvg.toFixed(1)));
            }

        }
        getAvgMarks();
        console.log("The average marks",avgMarks_stream);

        // this will populate our highestMarks_stream[]
        function getHighestMarks(){
            /*
            subjects.forEach(function(highestSubj) {
                var highestPerSubject = Math.max.apply(Math,highArrClone.map( function(o){console.log(o); return o[highestSubj];} ));

                highestMarks.push(highestPerSubject);
            });
            */
            for(var j = 0; j < subjects_stream.length; j++){

                var highestPerSubject = Math.max.apply(Math,highStreamArrClone.map( function(o){return o[subjects_stream[j]];} ));

                highestMarks_stream.push(highestPerSubject);
            }

        }
        getHighestMarks();
        console.log("The highest marks",highestMarks_stream);

        // this will populate our lowestMarks_stream[]
        function getLowestMarks(){

            for(var h = 0; h < subjects_stream.length; h++){

                var lowestPerSubject = Math.min.apply(Math,lowStreamArrClone.map( function(o){return o[subjects_stream[h]];} ));

                lowestMarks_stream.push(lowestPerSubject);
            }

        }
        getLowestMarks();
        console.log("The lowest marks",lowestMarks_stream);

        // build the chart
        var options = {
            chart: {
                height: 350,
                type: 'line',
                shadow: {
                    enabled: true,
                    color: '#000',
                    top: 18,
                    left: 7,
                    blur: 10,
                    opacity: 1
                },
                toolbar: {
                    show: false
                }
            },
            colors: ['#00FF00', '#0000FF', '#FF0000'],
            dataLabels: {
                enabled: true,
            },
            stroke: {
                curve: 'smooth'
            },
            series: [{
                        name: "Highest",
                        data: highestMarks_stream
                    },
                    {
                        name: "Average",
                        data: avgMarks_stream
                    },
                    {
                        name: "Lowest",
                        data: lowestMarks_stream
                    }
            ],
            title: {
                text: 'Average, Highest & Lowest Marks Per Subject',
                align: 'left'
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#878787', 'transparent'], // takes an array which will be repeated on columns
                    opacity: 0.5
                },
            },
            markers: {

                size: 6
            },
            xaxis: {
                categories: subjects_stream,
                title: {
                    text: 'Subjects'
                }
            },
            yaxis: {
                title: {
                    text: 'Performance'
                },
                min: 0,
                max: 100
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        }

        var chart = new ApexCharts(
            document.querySelector("#chart"),
            options
        );

        chart.render();

    }

    $scope.makeClassMeanChart = function()
    {
        //
    }

    $scope.makeStreamMeanChart = function()
    {
        //
    }

    $scope.makeClassGradesChart = function()
    {
        //
    }

    $scope.makeStreamGradesChart = function()
    {
        //
    }

	var initializeController = function ()
	{
		// get classes
		var requests = [];

		var deferred = $q.defer();
		requests.push(deferred.promise);

		if( $rootScope.allClasses === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				apiService.getTeacherClasses($rootScope.currentUser.emp_id, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						deferred.resolve();
					}
					else
					{
						deferred.reject();
					}

				}, function(){deferred.reject();});
			}
			else
			{
				apiService.getAllClasses({}, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						$scope.filters.class_cat_id = ( $scope.classes[0] ? $scope.classes[0].class_cat_id : null);
						deferred.resolve();
					}
					else
					{
						deferred.reject();
					}

				}, function(){deferred.reject();});
			}
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
			$scope.filters.class = $scope.classes[0];
			$scope.filters.class_id = $scope.classes[0].class_id;
			$scope.filters.class_cat_id = $scope.classes[0].class_cat_id;
			deferred.resolve();
		}


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

	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.filters.class_id = newVal.class_id;
		$scope.selectedClass = newVal.class_name;

		apiService.getExamTypes(newVal.class_cat_id, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata ){
				$scope.examTypes = result.data;
				$scope.filters.exam_type_id = $scope.examTypes[0].exam_type_id;
			}
		}, apiError);


	});

	$scope.$watch('filters.analysis',function(newVal,oldVal){
		if( newVal == oldVal ) return;
	});

	$scope.loadSelection = function()
	{
		console.log($scope.filters.analysis + " analysis selected");
		if($scope.filters.analysis == "class_performace"){
			$scope.getStudentExams();
		}else if($scope.filters.analysis == "class_mean"){
			//
		}else if($scope.filters.analysis == "class_grades"){
			//
		}else if($scope.filters.analysis == "class_subjects"){
			//
		}else if($scope.filters.analysis == "stream_performace"){
			$scope.getStudentStreamExams();
		}else if($scope.filters.analysis == "stream_mean"){
			//
		}else if($scope.filters.analysis == "stream_grades"){
			//
		}else if($scope.filters.analysis == "stream_subjects"){
			//
		}else if($scope.filters.analysis == "improvement_report"){
			$scope.getExamImprovement();
		}else{
			// make a valid selection message
			$scope.preLoadMessageH1 = "";
			$scope.preLoadMessageH3 = "There seems to be a problem with the current selection.";
		}
	}

	$scope.getStudentExams = function()
	{
		$scope.examMarks = {};
		$scope.totalMarks = {};
		$scope.meanScores = {};
		$scope.tableHeader = [];
		$scope.marksNotFound = false;
		$scope.getReport = "";

		$scope.initialReportLoad = false; // initial items to show before any report is loaded
		$scope.showReport = true; // show the div with the analysis table

		$scope.reportTitle = 'Class Analysis For ' + $scope.filters.class.class_name;
		$scope.streamAnalysisTable = false;
		$scope.classAnalysisTable = true; // show the table for class analysis
		$scope.improvementsAnalysisTable = false;

		var request = $scope.filters.class_id + '/' + $scope.filters.term_id;

		apiService.getClassAnalysis(request, loadMarks, apiError);
	}

	$scope.getStudentStreamExams = function()
	{
		$scope.examMarks = {};
		$scope.totalMarks = {};
		$scope.meanScores = {};
		$scope.tableHeader = [];
		$scope.marksNotFound = false;
		$scope.getReport = "";

		$scope.initialReportLoad = false; // initial items to show before any report is loaded
		$scope.showReport = true; // show the div with the analysis table

		$scope.reportTitle = 'Stream Analysis For ' + $scope.filters.class.class_name;
		$scope.classAnalysisTable = false;
		$scope.streamAnalysisTable = true; // show the table for stream analysis
		$scope.improvementsAnalysisTable = false;

		var request = $scope.filters.class_id + '/' + $scope.filters.term_id;
		apiService.getStreamAnalysis(request, loadStreamMarks, apiError);
	}

	$scope.getExamImprovement = function()
	{
		$scope.examMarks = {};
		$scope.totalMarks = {};
		$scope.meanScores = {};
		$scope.tableHeader = [];
		$scope.marksNotFound = false;
		$scope.getReport = "";

		$scope.initialReportLoad = false; // initial items to show before any report is loaded
		$scope.showReport = true; // show the div with the analysis table
		// console.log($scope.filters);
		$scope.reportTitle = 'Deviations Analysis';
		$scope.classAnalysisTable = false;
		$scope.streamAnalysisTable = false;
		$scope.improvementsAnalysisTable = true; // show the table for improvement analysis

		var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
		apiService.getExamDeviations(request, loadImprovementResults, apiError);
	}

	var loadMarks = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.marksNotFound = true;
				$scope.errMsg = "There are currently no exam marks entered for this search criteria.";
			}
			else
			{

				if( $scope.dataGrid !== undefined )
				{
					$('.fixedHeader-floating').remove();
					$scope.dataGrid.clear();
					$scope.dataGrid.destroy();
				}

				$scope.examMarks = result.data;
				$scope.totalStudents = result.data.length;

				/* loop through the first exam mark result to build the table columns */
				$scope.tableHeader = [];
				var ignoreCols = ['gender','student_id','student_name','rank','exam_type'];
				var subjectsWeights = {};
				var subjectsObj = {};
				angular.forEach($scope.examMarks[0], function(value,key){
					if( ignoreCols.indexOf(key) === -1 )
					{
						// keys read like '7', 'C.R.E', '40', remove the ' and replace , with /
						/* need the sort order id on the front so it orders correctly, seems to go alphabetical regardless of sort order applied to query
						   in order to fix this, the first item needs to strip this off */

						var colRow = key.replace(/["']/g, "");
						var subjectDetails = colRow.split(', '),
							parentSubject = subjectDetails[1],
							subjectName = subjectDetails[2],
							gradeWeight = subjectDetails[3];

						/* each subject group needs to scored out of 100, if a subject does not have a parent, add 100 for grand total
						   if a subject has a parent, add 100 for each parent subject
						*/
						// also grouping to determine which subjects are parents but do not have children
						// needed to build table header, which happens next
						if( parentSubject == '' )
						{
							// no parent
							subjectsWeights[subjectName] = 100;
							if( subjectsObj[subjectName] === undefined )
							{
								subjectsObj[subjectName] = {
									isParent:true,
									subjectName:subjectName,
									children: []
								}
							}
						}
						else
						{
							// has parent, use parents subject name
							subjectsWeights[parentSubject] = 100;
							if( subjectsObj[parentSubject] === undefined )
							{
								subjectsObj[parentSubject] = {
									isParent:true,
									subjectName:parentSubject,
									children: []
								};
							}
							subjectsObj[parentSubject].children.push({
								subjectName:subjectName
							});
						}
					}
				});

				// build table header, use TOT for parents that have children
				// exception is Kiswahili, needs to be Juml (total in Kiswahili)
				angular.forEach($scope.examMarks[0], function(value,key){
					if( ignoreCols.indexOf(key) === -1 )
					{
						var colRow = key.replace(/["']/g, "");
						var subjectDetails = colRow.split(', '),
							parentSubject = subjectDetails[1],
							subjectName = subjectDetails[2];

						var hasChildren = ( parentSubject == '' && subjectsObj[subjectName].children.length > 0 ? true : false );

						$scope.tableHeader.push({
							title: (hasChildren ? ( subjectName == 'Kiswahili' ? 'Juml' : 'TOT') : ( key !== 'total_mark' ? formatTitle(subjectName) : 'MKS' ) ),
							key: key,
							isParent: (parentSubject == '' ? true : false)
						});
					}
				});


				/* sum up the total grade weight value */
				$scope.totalGradeWeight = 0;
				for (var key in subjectsWeights) {
					// skip loop if the property is from prototype
					if (!subjectsWeights.hasOwnProperty(key)) continue;

					//var value = subjectsWeights[key];
					$scope.totalGradeWeight += subjectsWeights[key];
				}

				/* loop through all exam mark results and calculate the students total score */
				/* only total up the parent subjects */
				// total up marks

				var total = 0;
				// need to total up each subject for total marks in footer
				$scope.totalMarks = {};
				$scope.grandTotal = 0;
				angular.forEach($scope.examMarks, function(item){
					var total = 0;
					angular.forEach(item, function(value,key){
						if( ignoreCols.indexOf(key) === -1 )
						{
							var colRow = key.replace(/["']/g, "");
							var subjectDetails = colRow.split(', '),
								parentSubject = subjectDetails[1];

							if( parentSubject == '' ) total += value;

							if( $scope.totalMarks[key] === undefined ) $scope.totalMarks[key] = 0;
							$scope.totalMarks[key] = $scope.totalMarks[key] + value;
						}
					});
					item.total = Math.round(total);
					$scope.grandTotal += item.total;
				});

				// $scope.getReport = "examsTable";
				// $timeout(initDataGrid,100);
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}

	}

	var loadStreamMarks = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.marksNotFound = true;
				$scope.errMsg = "There are currently no exam marks entered for this search criteria.";
			}
			else
			{

				if( $scope.dataGrid !== undefined )
				{
					$('.fixedHeader-floating').remove();
					$scope.dataGrid.clear();
					$scope.dataGrid.destroy();
				}

				$scope.examMarks = result.data;
				$scope.totalStudents = result.data.length;

				/* loop through the first exam mark result to build the table columns */
				$scope.tableHeader = [];
				var ignoreCols = ['gender','student_id','student_name','rank','exam_type'];
				var subjectsWeights = {};
				var subjectsObj = {};
				angular.forEach($scope.examMarks[0], function(value,key){
					if( ignoreCols.indexOf(key) === -1 )
					{
						// keys read like '7', 'C.R.E', '40', remove the ' and replace , with /
						/* need the sort order id on the front so it orders correctly, seems to go alphabetical regardless of sort order applied to query
						   in order to fix this, the first item needs to strip this off */

						var colRow = key.replace(/["']/g, "");
						var subjectDetails = colRow.split(', '),
							parentSubject = subjectDetails[1],
							subjectName = subjectDetails[2],
							gradeWeight = subjectDetails[3];

						/* each subject group needs to scored out of 100, if a subject does not have a parent, add 100 for grand total
						   if a subject has a parent, add 100 for each parent subject
						*/
						// also grouping to determine which subjects are parents but do not have children
						// needed to build table header, which happens next
						if( parentSubject == '' )
						{
							// no parent
							subjectsWeights[subjectName] = 100;
							if( subjectsObj[subjectName] === undefined )
							{
								subjectsObj[subjectName] = {
									isParent:true,
									subjectName:subjectName,
									children: []
								}
							}
						}
						else
						{
							// has parent, use parents subject name
							subjectsWeights[parentSubject] = 100;
							if( subjectsObj[parentSubject] === undefined )
							{
								subjectsObj[parentSubject] = {
									isParent:true,
									subjectName:parentSubject,
									children: []
								};
							}
							subjectsObj[parentSubject].children.push({
								subjectName:subjectName
							});
						}
					}
				});

				// build table header, use TOT for parents that have children
				// exception is Kiswahili, needs to be Juml (total in Kiswahili)
				angular.forEach($scope.examMarks[0], function(value,key){
					if( ignoreCols.indexOf(key) === -1 )
					{
						var colRow = key.replace(/["']/g, "");
						var subjectDetails = colRow.split(', '),
							parentSubject = subjectDetails[1],
							subjectName = subjectDetails[2];

						var hasChildren = ( parentSubject == '' && subjectsObj[subjectName].children.length > 0 ? true : false );

						$scope.tableHeader.push({
							title: (hasChildren ? ( subjectName == 'Kiswahili' ? 'Juml' : 'TOT') : ( key !== 'total_mark' ? formatTitle(subjectName) : 'MKS' ) ),
							key: key,
							isParent: (parentSubject == '' ? true : false)
						});
					}
				});


				/* sum up the total grade weight value */
				$scope.totalGradeWeight = 0;
				for (var key in subjectsWeights) {
					// skip loop if the property is from prototype
					if (!subjectsWeights.hasOwnProperty(key)) continue;

					//var value = subjectsWeights[key];
					$scope.totalGradeWeight += subjectsWeights[key];
				}

				/* loop through all exam mark results and calculate the students total score */
				/* only total up the parent subjects */
				// total up marks

				var total = 0;
				// need to total up each subject for total marks in footer
				$scope.totalMarks = {};
				$scope.grandTotal = 0;
				angular.forEach($scope.examMarks, function(item){
					var total = 0;
					angular.forEach(item, function(value,key){
						if( ignoreCols.indexOf(key) === -1 )
						{
							var colRow = key.replace(/["']/g, "");
							var subjectDetails = colRow.split(', '),
								parentSubject = subjectDetails[1];

							if( parentSubject == '' ) total += value;

							if( $scope.totalMarks[key] === undefined ) $scope.totalMarks[key] = 0;
							$scope.totalMarks[key] = $scope.totalMarks[key] + value;
						}
					});
					item.total = Math.round(total);
					$scope.grandTotal += item.total;
				});

				// $scope.getReport = "examsTable";
				// $timeout(initDataGrid,100);
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}

	}

	var loadImprovementResults = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.marksNotFound = true;
				$scope.errMsg = "There are currently no exam marks entered for this search criteria.";
			}
			else
			{

				if( $scope.dataGrid !== undefined )
				{
					$('.fixedHeader-floating').remove();
					$scope.dataGrid.clear();
					$scope.dataGrid.destroy();
				}

				$scope.examMarks = result.data;
				$scope.currentExamResults = $scope.examMarks.currentExamResults;
				$scope.previousExamResults = $scope.examMarks.previousExamResults;
				console.log($scope.examMarks);

				$scope.allStudentsCurrent = [];

				for(let x=0;x < $scope.currentExamResults.length;x++){
					var studentObjs = $scope.currentExamResults.filter(obj => {
						return obj.student_id === $scope.currentExamResults[x].student_id
					  });
					  // console.log(studentObjs);

					  var thisStudent = {};
					  var theSubjects = [];
					  for(let y=0;y < studentObjs.length;y++){
						  if(y==0){
							thisStudent = {
								gender: studentObjs[0].gender,
								student_name: studentObjs[0].student_name,
								class_id: studentObjs[0].class_id,
								exam_type: studentObjs[0].exam_type,
								admission_number: studentObjs[0].admission_number,
								class_name: studentObjs[0].class_name,
								student_id: studentObjs[0].student_id,
								subjects: []
							};
						  }
						  let eachSubject = {
											  subject_name:studentObjs[y].subject_name,
											  grade_weight:studentObjs[y].grade_weight,
											  mark:studentObjs[y].mark
										};
						thisStudent.subjects.push(eachSubject);
					  }
					  $scope.allStudentsCurrent[x] = thisStudent;
				}
				$scope.studentCurrentResults = Object.values($scope.allStudentsCurrent.reduce((acc,cur)=>Object.assign(acc,{[cur.student_id]:cur}),{}));
				// console.log("Current results",$scope.studentCurrentResults);

				$scope.allStudentsPrevious = [];

				for(let q=0;q < $scope.previousExamResults.length;q++){
					var studentObjs = $scope.previousExamResults.filter(obj => {
						return obj.student_id === $scope.previousExamResults[q].student_id
					  });
					  // console.log(studentObjs);

					  var thisStudent = {};
					  for(let r=0;r < studentObjs.length;r++){
						  if(r==0){
							thisStudent = {
								gender: studentObjs[0].gender,
								student_name: studentObjs[0].student_name,
								class_id: studentObjs[0].class_id,
								exam_type: studentObjs[0].exam_type,
								admission_number: studentObjs[0].admission_number,
								class_name: studentObjs[0].class_name,
								student_id: studentObjs[0].student_id,
								subjects: []
							};
						  }
						  let eachSubject = {
											  subject_name:studentObjs[r].subject_name,
											  grade_weight:studentObjs[r].grade_weight,
											  mark:studentObjs[r].mark
										};
						thisStudent.subjects.push(eachSubject);
					  }
					  $scope.allStudentsPrevious[q] = thisStudent;
				}
				$scope.studentPreviousResults = Object.values($scope.allStudentsPrevious.reduce((acc,cur)=>Object.assign(acc,{[cur.student_id]:cur}),{}));
				// console.log($scope.studentPreviousResults);

				for(let v=0;v < $scope.studentCurrentResults.length;v++){
					$scope.studentPreviousResults.forEach(function(previousExam) {
						if($scope.studentCurrentResults[v].student_id == previousExam.student_id){
							let currentResults = $scope.studentCurrentResults[v].subjects;
							let previousResults = previousExam.subjects;
							let deviationResults = [];
							let allSubjMarks = [];
							for(let x=0;x < currentResults.length;x++){
								if(currentResults[x].subject_name == previousResults[x].subject_name){
									let arrLength = currentResults.length -1;
									previousResults[x].mark = (previousResults[x].mark == undefined || previousResults[x].mark == null ? 0 : previousResults[x].mark);
									currentResults[x].mark = (currentResults[x].mark == undefined || currentResults[x].mark == null ? 0 : currentResults[x].mark);
									currentResults[x].mark = currentResults[x].mark - previousResults[x].mark;
									allSubjMarks.push(currentResults[x].mark);
									if(x == arrLength){
										let total = allSubjMarks.reduce(function(acc, val) { return acc + val; }, 0);
										// console.log("Last item i arr = " + x + " || ",total);
										$scope.studentCurrentResults[v].total = total;
										allSubjMarks = [];
									}
								}
							}
						}
					  });
				}
				console.log("Deviations",$scope.studentCurrentResults);
				// order from the most improved to least
				$scope.studentCurrentResults = $scope.studentCurrentResults.sort((a, b) => (a.total > b.total) ? -1 : 1);
				$scope.colsFromSubjects = $scope.studentCurrentResults[0].subjects;
				$scope.cols = [];
				$scope.colsFromSubjects.forEach(function(subject) {
					$scope.cols.push(subject.subject_name);
				});

			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.gotoDiv1 = function(el) {
	    console.log("First tab",el);
        var newHash = '1a';
        if ($location.hash() !== newHash) {
            $location.hash('1a');

            document.getElementById("2a").classList.remove("active");
	        document.getElementById("1a").classList.add("active");

        } else {
            //$anchorScroll();
        }

        $scope.classAnalysisTable = ( $scope.returnToClassAnalysis == true ? true : false);
		$scope.streamAnalysisTable = ( $scope.returnToStreamAnalysis == true ? true : false);
		$scope.improvementsAnalysisTable = ( $scope.returnToImprovementsAnalysis == true ? true : false);
        console.log("Can we return to the class analysis? " + $scope.classAnalysisTable);

        if($scope.classAnalysisTable == true){

	            document.getElementById("classAnalysisTableDiv").style.display = "block";
	            $scope.getStudentExams(); // refetch the data
				document.getElementById("streamAnalysisTableDiv").style.display = "none";
				document.getElementById("improvementsAnalysisTableDiv").style.display = "none";
				$scope.streamAnalysisTable = false;
				$scope.improvementsAnalysisTable = false;

	    }else if($scope.streamAnalysisTable == true){

	            document.getElementById("streamAnalysisTableDiv").style.display = "block";
	            $scope.getStudentStreamExams(); // refetch the data
				document.getElementById("classAnalysisTableDiv").style.display = "none";
				document.getElementById("improvementsAnalysisTableDiv").style.display = "none";
				$scope.classAnalysisTable = false;
				$scope.improvementsAnalysisTable = false;

	    }else if($scope.improvementsAnalysisTable == true){

			document.getElementById("improvementsAnalysisTableDiv").style.display = "block";
			$scope.getExamImprovement(); // refetch the data
			document.getElementById("streamAnalysisTableDiv").style.display = "none";
			document.getElementById("classAnalysisTableDiv").style.display = "none";
			$scope.classAnalysisTable = false;
			$scope.streamAnalysisTable = false;

		}else{
        	   // no selection made yet
        	   $scope.preLoadMessageH1 = "MAKE A SELECTION ABOVE TO LOAD A REPORT";
	           $scope.preLoadMessageH3 = "Supported reports are in tables, charts and graphs";

               $scope.initialReportLoad = true; // initial items to show before any report is loaded
               $scope.showReport = false; // hide the reports div until needed
            	$scope.classAnalysisTable = false; // show the table for class analysis
				$scope.streamAnalysisTable = false; // show the table for stream analysis
				$scope.improvementsAnalysisTable = false; // show the table for improvements
            	$scope.activeChartTab = false; // hide charts

            	$("#classAnalysisTable").DataTable().destroy();
				$("#streamAnalysisTable").DataTable().destroy();
				$("#improvementsAnalysisTable").DataTable().destroy();
            	initializeController();
        }

     };

     $scope.gotoDiv2 = function(el) {
	    console.log("Second tab",el);
        var newHash = '2a';
        if ($location.hash() !== newHash) {
            $location.hash('2a');

            // lets first load chart.js
            $.getScript('/components/overviewFiles/js/apexcharts.js', function()
            {
                // script is now loaded and executed.
                document.getElementById("1a").classList.remove("active");
    	        document.getElementById("2a").classList.add("active");

    	        // hide the active table to pave way for charts visibility
                $scope.showReport = true; // show the div
    	        document.getElementById("streamAnalysisTableDiv").style.display = "none";
				document.getElementById("classAnalysisTableDiv").style.display = "none";
				document.getElementById("improvementsAnalysisTableDiv").style.display = "none";

    	        $scope.activeChartTab = true; // show charts

    	        // we need to save the state of the first tab to prevent reloading on return
    	        if($scope.classAnalysisTable == true){
    	            $scope.classAnalysisTable = false; // hide the active table to pave way for charts visibility
    	            $scope.returnToClassAnalysis = true; // enable returning to the above status
    	            console.log("If we click on tab 1, will we go back to the class analysis? " + $scope.returnToClassAnalysis);
    	            $scope.returnToStreamAnalysis = false;
    	            $scope.makeClassPerformanceChart();

    	        }else if($scope.streamAnalysisTable == true){
    	            $scope.streamAnalysisTable = false; // hide the active table to pave way for charts visibility
    	            $scope.returnToStreamAnalysis = true; // enable returning to the above status
    	            $scope.returnToClassAnalysis = false;
    	            $scope.makeStreamPerformanceChart();

    	        }else{
            	   // no selection made yet
            	}

            });

      } else {
        // $anchorScroll();
      }
     };

	var formatTitle = function(title)
	{
		var titleArray = title.split(' ');
		var numWords = titleArray.length;
		var i = 0;
		var result = [];
		for( i = 0; i < numWords; i++)
		{
			var seg = ( titleArray[i].length > 9 ? titleArray[i].substr(0,9) + '...' : titleArray[i]);
			if( seg !== '-' ) result.push(seg);
		}
		return result.join(" ");
	}

	$scope.displayClassAnalysisMark = function(index, key)
	{
		return $scope.examMarks[index][key] || '-';
	}

	$scope.displayStreamAnalysisMark = function(index, key)
	{
		return $scope.examMarks[index][key] || '-';
	}

	$scope.displayClassAnalysisTotalMark = function(key)
	{
		return $scope.totalMarks[key] || '-' ;
	}

	$scope.displayStreamAnalysisTotalMark = function(key)
	{
		return $scope.totalMarks[key] || '-' ;
	}

	$scope.displayClassAnalysisMeanScore = function(key)
	{
		return Math.round($scope.totalMarks[key]/$scope.totalStudents,1) || '-' ;
	}

	$scope.displayStreamAnalysisMeanScore = function(key)
	{
		return Math.round($scope.totalMarks[key]/$scope.totalStudents,1) || '-' ;
	}

	/*
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
	*/
	$scope.exportData = function()
	{
		// get the currently viewable report
		if($scope.filters.analysis == "class_performace"){
			// var reportTableId = 'resultsTable'; var reportName = 'Class Performace Report';
			var divToExport=document.getElementById("resultsTable");
			var rows = document.querySelectorAll('table.classAnalysisTable tr');
		}else if($scope.filters.analysis == "class_mean"){
			//
		}else if($scope.filters.analysis == "class_grades"){
			//
		}else if($scope.filters.analysis == "class_subjects"){
			//
		}else if($scope.filters.analysis == "stream_performace"){
			// var reportTableId = 'resultsTable'; var reportName = 'Stream Performace Report';
			var divToExport=document.getElementById("resultsTable");
			var rows = document.querySelectorAll('table.streamAnalysisTable tr');
		}else if($scope.filters.analysis == "stream_mean"){
			//
		}else if($scope.filters.analysis == "stream_grades"){
			//
		}else if($scope.filters.analysis == "stream_subjects"){
			//
		}else if($scope.filters.analysis == "improvement_report"){
			// var reportTableId = 'resultsTable'; var reportName = 'Improvement Report';
			var divToExport=document.getElementById("resultsTable");
			var rows = document.querySelectorAll('table.improvementsAnalysisTable tr');
		}

		// exportTableToExcel(reportTableId, reportName); // ORIGINAL

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

		exportTableToCSV($scope.filters.analysis + '.csv');
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

		function exportTableToExcel(tableID, filename = ''){
		    var downloadLink;
		    var dataType = 'application/vnd.ms-excel';
		    var tableSelect = document.getElementById(tableID);
		    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

		    // Specify file name
		    filename = filename?filename+'.xls':'excel_data.xls';

		    // Create download link element
		    downloadLink = document.createElement("a");

		    document.body.appendChild(downloadLink);

		    if(navigator.msSaveOrOpenBlob){
		        var blob = new Blob(['\ufeff', tableHTML], {
		            type: dataType
		        });
		        navigator.msSaveOrOpenBlob( blob, filename);
		    }else{
		        // Create a link to the file
		        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;

		        // Setting the file name
		        downloadLink.download = filename;

		        //triggering the function
		        downloadLink.click();
		    }
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
