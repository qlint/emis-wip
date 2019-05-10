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

		var request = $scope.filters.class_id + '/' + $scope.filters.exam_type_id + '/' + $scope.filters.term_id;
		// apiService.getAllStudentExamMarks(request, loadMarks, apiError);
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
		console.log("Show stream? " + $scope.streamAnalysisTable + " ::: Show class? " + $scope.classAnalysisTable);

		console.log($scope.filters);
		var request = $scope.filters.class_id + '/' + $scope.filters.exam_type_id + '/' + $scope.filters.term_id;
		apiService.getStreamAnalysis(request, loadStreamMarks, apiError);
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
							title: (hasChildren ? ( subjectName == 'Kiswahili' ? 'Juml' : 'TOT') : formatTitle(subjectName)),
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

		function beautifyClassAnalysisTable() {
			console.log("Attempting to beautify table");
			// data tables - prepare the table for presentation and download
		  var docName = $scope.filters.class.class_name;
	    var targetTable = document.getElementById('classAnalysisTable').rows[0].cells.length;
	    var orderCol = targetTable - 1;

			$('#classAnalysisTable').DataTable( {
	            fixedHeader: true,
	            dom: 'Bfrtip',
	            "columnDefs": [
	                {"className": "dt-center", "targets": "_all"}
	            ],
	            buttons: [
	                {
	                    extend: 'excelHtml5',
	                    title: docName + ' Class Analysis'
	                },
	                {
	                    extend: 'csvHtml5',
	                    title: docName + ' Class Analysis'
	                },
	                {
	                    extend: 'pdfHtml5',
	                    title: docName + ' Class Analysis'
	                }
	              ],
	              "order": [[orderCol,"asc"]],
	              "bStateSave": true
	      } );
	      // end data tables
		}
		setTimeout(beautifyClassAnalysisTable, 3000);

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
							title: (hasChildren ? ( subjectName == 'Kiswahili' ? 'Juml' : 'TOT') : formatTitle(subjectName)),
							key: key,
							isParent: (parentSubject == '' ? true : false)
						});
					}
				});
				console.log("Stream processed tableHeader",$scope.tableHeader);


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

		function beautifyStreamAnalysisTable() {
			console.log("Attempting to beautify table");
			// data tables - prepare the table for presentation and download
		  var docName = $scope.filters.class.class_name;
	    var targetTable = document.getElementById('streamAnalysisTable').rows[0].cells.length;
	    var orderCol = targetTable - 1;

			$('#streamAnalysisTable').DataTable( {
	            fixedHeader: true,
	            dom: 'Bfrtip',
	            "columnDefs": [
	                {"className": "dt-center", "targets": "_all"}
	            ],
	            buttons: [
	                {
	                    extend: 'excelHtml5',
	                    title: docName + ' Stream Analysis'
	                },
	                {
	                    extend: 'csvHtml5',
	                    title: docName + ' Stream Analysis'
	                },
	                {
	                    extend: 'pdfHtml5',
	                    title: docName + ' Stream Analysis'
	                }
	              ],
	              "order": [[orderCol,"asc"]],
	              "bStateSave": true
	      } );
	      // end data tables
		}
		setTimeout(beautifyStreamAnalysisTable, 3000);

	}

	$scope.gotoDiv1 = function(el) {
	    console.log("First tab",el);
      var newHash = '1a';
      if ($location.hash() !== newHash) {
        $location.hash('1a');
      } else {
        $anchorScroll();
      }
     };

     $scope.gotoDiv2 = function(el) {
	    console.log("Second tab",el);
      var newHash = '2a';
      if ($location.hash() !== newHash) {
        $location.hash('2a');
      } else {
        $anchorScroll();
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
