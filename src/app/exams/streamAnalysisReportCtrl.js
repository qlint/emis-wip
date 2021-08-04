'use strict';

angular.module('eduwebApp').
controller('streamAnalysisReportCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
function($scope, $rootScope, apiService, $timeout, $window, $q, $parse){

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

	var initializeController = function ()
	{
		// get classes
		var requests = [];

		var deferred = $q.defer();
		requests.push(deferred.promise);

		$scope.filters.entity_id = 7; // We initialize the selection with class 4 as the default

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

	$scope.watchEntity = function(){
		console.log($scope.filters);
		apiService.getExamTypesByEntity($scope.filters.entity_id, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata ){
				$scope.examTypes = result.data;
				$scope.filters.exam_type_id = $scope.examTypes[0].exam_type_id;
				$timeout(setSearchBoxPosition,10);
			}
		}, apiError);
	}

	$scope.getTheCount = function()
	{
		$scope.doneSubject = {};
		$scope.countNotFound = false;

		var entity = $scope.filters.entity_id;
		var termText = document.getElementById('term').value;
		var term = parseInt(termText.replace(/\D/g,''), 10);

		var request = entity + '/' + term;
		apiService.getStreamDoneExamSubjectCount(request, loadCount, apiError);
	}

	var loadCount = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.countNotFound = true;
				$scope.errMsg = "No count data found.";
			}
			else
			{
				$scope.doneSubject = result.data;
				$scope.doneSubject.count = $scope.doneSubject.map(a => a.count);
				$scope.uniqueMean = $scope.doneSubject.count.map(Number);
				// for (var i = 0; i < $scope.doneSubject.count.length; i++){
				// 		uniqueMean[i] = $scope.doneSubject.count;
				// }
				// console.log($scope.uniqueMean);

			}
		}
		else
		{
			$scope.countNotFound = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.getStudentExams = function()
	{
	    $scope.examMarks = {};
		$scope.totalMarks = {};
		$scope.finalMean = {};
		$scope.meanScores = {};
		$scope.tableHeader = [];
		$scope.marksNotFound = false;
		$scope.getReport = "";

		var entity = $scope.filters.entity_id;
		var termText = document.getElementById('term').value;
		var term = parseInt(termText.replace(/\D/g,''), 10);
		console.log($scope.filters);

		var streamRequest = entity + '/' + term + '/' + $scope.filters.exam_type_id;

		apiService.getAllStudentStreamMarks(streamRequest, loadMarks, apiError);
		$scope.getTheCount();

		//this repositions the text search filter
		$( document ).ready(function() {
			setTimeout(function() {
			    $('.main-datagrid .dataTables_filter').css('left',"450px");
			}, 2000);
		});
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
				// console.log($scope.examMarks);
				$scope.totalStudents = result.data.length;

				/* loop through the first exam mark result to build the table columns */
				$scope.tableHeader = [];
				var ignoreCols = ['gender','student_id','student_name','rank'];
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

						var kiswSubj = 'Kiswahili';
						$scope.tableHeader.push({
							// title: (hasChildren ? ( subjectName.toLowerCase() == kiswSubj.toLowerCase() ? 'Juml' : 'TOT') : formatTitle(subjectName)),
							title: (hasChildren ? ( subjectName.toLowerCase() == kiswSubj.toLowerCase() ? 'Juml' : 'TOT') : subjectName),
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
				$scope.finalMean = {};
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
							if( $scope.finalMean[key] === undefined ) $scope.finalMean[key] = 0;
							$scope.finalMean[key] = $scope.finalMean[key];
						}
					});
					item.total = Math.round(total);
					$scope.grandTotal += item.total;
				});

				$scope.getReport = "examsTable";
				$timeout(initDataGrid,100);
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}
	}

	var formatTitle = function(title)
	{
	    console.log($scope.filters);
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

	$scope.displayMark = function(index, key)
	{
		return $scope.examMarks[index][key] || '-';
		//return $parse("examMarks[" + index + "][\"" + key + "\"]" )($scope) || '-' ;
	}

	$scope.displayTotalMark = function(key)
	{
		return $scope.totalMarks[key] || '-' ;
	}

	$scope.xlsdownload = function(){
	    window.open('data:application/vnd.ms-excel,'+document.getElementById('meow').innerHTML.replace(/ /g, '%20'));
	};

	$scope.displayMeanScore = function(key)
	{
		//this converts our array of # of students who did a subject into an object
		$scope.uniqueMn2 = $scope.uniqueMean.reduce(function(result, item, index, array) {
			  result[index] = item; //a, b, c
			  return result;
			}, {})

		//this takes our original array of # of stdnts who did a subject & divides to the ttl marks
		var cnt = 0;
		$scope.divides=[];
		for (var o in $scope.totalMarks) {
			$scope.divides.push($scope.totalMarks[o] / $scope.uniqueMean[cnt]);
		  cnt++
		}

		$scope.divides2= $scope.divides.map(function(each_element){
		    return Number(each_element.toFixed(2));
		});
		//the result of the above an array of the mean scores

		//this takes the array above and converts it to an object
		$scope.dividesObj = $scope.divides.reduce(function(result, item, index, array) {
			  result[index] = item; //a, b, c
			  return result;
			}, {});
			// console.log($scope.dividesObj);

			// $scope.meanValues = [];
			// for (var key = 0; key < $scope.divides.length; key++){
			// 	$scope.meanValues.push($scope.dividesObj[key]);
			// }
			//
			// $scope.finalMean = $scope.meanValues.reduce(function(result, item, index, array) {
			//   result[index] = item; //a, b, c
			//   return result;
			// }, {});

			for (var i in $scope.divedesObj){
					return $scope.divedesObj[i] || '-' ;
			}
			// console.log($scope.finalMean);


		// return Math.round($scope.totalMarks[key]/$scope.totalStudents,1) || '-' ;

	}

	var initDataGrid = function()
	{

		var tableElement = $('#resultsTable');
		$scope.dataGrid = tableElement.DataTable( {
				paging: false,
				destroy:true,
				filter: true,
				info: false,
				sorting:[],
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
						search: "Search Results<br>",
						searchPlaceholder: "Filter",
						lengthMenu: "Display _MENU_",
						emptyTable: "No students found."
				},
			} );

		tableElement.DataTable().columns(-1).order('asc').draw();

		var headerHeight = $('.navbar-fixed-top').height();
		//var subHeaderHeight = $('.subnavbar-container.fixed').height();
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 13 );
		new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );


		// position search box
		setSearchBoxPosition();

		if( initialLoad ) setResizeEvent();

	}

	var setSearchBoxPosition = function()
	{
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+55);
		}
	}

	var setResizeEvent = function()
	{
		 initialLoad = false;

		 $window.addEventListener('resize', function() {

			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				$('#resultsTable_filter').css('left',filterFormWidth-30);
			}
		}, false);
	}

	$scope.toggleFilter = function()
	{
		$scope.filterShowing = !$scope.filterShowing;

		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}

	$scope.toggleTools = function()
	{
		$scope.toolsShowing = !$scope.toolsShowing;

		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}

	$scope.addExamMarks = function()
	{
		var data = {
			classes: $scope.classes,
			terms: $scope.terms,
			examTypes: $scope.examTypes,
			filters: $scope.filters,
			viewing: 'report'
		}
		$scope.openModal('exams', 'addExamMarks', 'lg', data);
	}

	$scope.importExamMarks = function()
	{
		$rootScope.wipNotice();
	}

	$scope.exportData = function()
	{
		$rootScope.wipNotice();
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
		var newWindowRef = window.open('https://' + domain + '/#/exams/analysis/print');
		newWindowRef.printCriteria = data;
	}

	$scope.$on('refreshExamMarks2', function(event, args) {

		$scope.loading = true;
		$rootScope.loading = true;

		if( args !== undefined )
		{
			$scope.updated = true;
			$scope.notificationMsg = args.msg;
		}
		$scope.refresh();

		// wait a bit, then turn off the alert
		$timeout(function() { $scope.alert.expired = true;  }, 2000);
		$timeout(function() {
			$scope.updated = false;
			$scope.notificationMsg = '';
			$scope.alert.expired = false;
		}, 3000);
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
