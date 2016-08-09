'use strict';

angular.module('eduwebApp').
controller('classAnalysisReportCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse', 'uiGridConstants',
function($scope, $rootScope, apiService, $timeout, $window, $q, $parse, uiGridConstants){

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
	
	var footerTemplate = function() 
	{
		return   "<div class=\"ui-grid-footer-panel ui-grid-footer-aggregates-row\">" +
				 "<div class=\"ui-grid-footer ui-grid-footer-viewport\"><div class=\"ui-grid-footer-canvas\"><div class=\"ui-grid-footer-cell-wrapper\" ng-style=\"colContainer.headerCellWrapperStyle()\"><div role=\"row\" class=\"ui-grid-footer-cell-row\">" +
				 "<div ui-grid-footer-cell role=\"gridcell\" ng-repeat=\"col in colContainer.renderedColumns track by col.uid\" col=\"col\" render-index=\"$index\" class=\"ui-grid-footer-cell ui-grid-clearfix\">" +
				 "</div></div></div></div></div></div>"
  
	}
	
	$scope.gridOptions = {
		enableSorting: true,
		rowHeight:24,
		columnFooterHeight:40,
		showColumnFooter: true,
		exporterCsvFilename: 'class-analysis.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};
	
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

		apiService.getExamTypes(newVal.class_cat_id, function(response){
			var result = angular.fromJson(response);				
			if( result.response == 'success'){ 
				$scope.examTypes = result.data;
				$scope.filters.exam_type_id = $scope.examTypes[0].exam_type_id;
				$timeout(setSearchBoxPosition,10);
			}			
		}, apiError);
		
		
	});
	
	$scope.getStudentExams = function()
	{
		$scope.examMarks = {};
		$scope.totalMarks = {};
		$scope.meanScores = {};
		$scope.tableHeader = [];
		$scope.marksNotFound = false;
		$scope.getReport = "";
		
		var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
		if( $rootScope.currentUser.user_type == 'TEACHER' ) request += '/' + $rootScope.currentUser.emp_id;
		apiService.getAllStudentExamMarks(request, loadMarks, apiError);
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
				/*
				if( $scope.dataGrid !== undefined )
				{
					$('.fixedHeader-floating').remove();
					$scope.dataGrid.clear();
					$scope.dataGrid.destroy();
				}
				*/
				
				$scope.gridOptions.columnDefs = [
					{ name: 'Student', field: 'student_name', enableColumnMenu: false, footerCellFilter: '<div class="ui-grid-cell-contents right">TOTAL MARKS<br>MEAN SCORE</div>'},
				];
				
				$scope.examMarks = result.data;
				$scope.totalStudents = result.data.length;
				
				/* loop through the first exam mark result to build the table columns */
				$scope.tableHeader = [];
				var ignoreCols = ['student_id','student_name','rank','exam_type'];
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
						var cellClass = ( parentSubject == '' ? 'strong' : 'text-muted');
						/*
						$scope.tableHeader.push({
							title: (hasChildren ? ( subjectName == 'Kiswahili' ? 'Juml' : 'TOT') : subjectName),
							key: key,
							isParent: (parentSubject == '' ? true : false)
						});
						*/
						
						$scope.gridOptions.columnDefs.push({
							name: (hasChildren ? ( subjectName == 'Kiswahili' ? 'Juml' : 'TOT') : subjectName), 
							field: key, 
							enableColumnMenu: false,
							cellClass: cellClass,
							aggregationType: averageAndTotal,
							footerCellFilter: '<div class="ui-grid-cell-contents">{{col.getAggregationValue()[1]}}<br>{{col.getAggregationValue()[0] | number : 1}}</div>'
						});
					}
				});
				

				/* sum up the total grade weight value 
				$scope.totalGradeWeight = 0;
				for (var key in subjectsWeights) {
					// skip loop if the property is from prototype
					if (!subjectsWeights.hasOwnProperty(key)) continue;

					//var value = subjectsWeights[key];
					$scope.totalGradeWeight += subjectsWeights[key];
				}
				*/
				
				/* loop through all exam mark results and calculate the students total score */
				/* only total up the parent subjects */
				// total up marks
				
				var total = 0;
				// need to total up each subject for total marks in footer
				//$scope.totalMarks = {};
				angular.forEach($scope.examMarks, function(item){
					var total = 0;

					angular.forEach(item, function(value,key){
						if( ignoreCols.indexOf(key) === -1 )
						{
							var colRow = key.replace(/["']/g, "");
							var subjectDetails = colRow.split(', '),
								parentSubject = subjectDetails[1];
							
							if( parentSubject == '' ) total += value;
							
							//if( $scope.totalMarks[key] === undefined ) $scope.totalMarks[key] = 0; 
							//$scope.totalMarks[key] = $scope.totalMarks[key] + value;
						}
					});
					item.total = Math.round(total);
					
				});
				
				$scope.gridOptions.columnDefs.push(
					{
						name: 'G.TOT', 
						field: 'total', 
						enableColumnMenu: false,
						cellClass: 'strong',
					},
					{
						name: 'Pos', 
						field: 'rank', 
						enableColumnMenu: false,
						cellClass: 'strong',
						sort: {direction:'asc'},
					}
				);

				$scope.getReport = "examsTable";
				//$timeout(initDataGrid,100);
				$scope.gridOptions.data = $scope.examMarks;
				
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}
	}
	
	Array.prototype.avg = function (e) 
	{
		if (this.length == 0)return 0;
		var t = 0;
		for (var n = 0; n < this.length; n++) {
			t += this[n]["entity"][e] / this.length;
		}
		return t;
	};

	Array.prototype.total = function (e) 
	{
		if (this.length == 0)return 0;
		var t = 0;
		for (var n = 0; n < this.length; n++) {
			t += this[n]["entity"][e];
		}
		return t;
	};

	var averageAndTotal = function(e,t)
	{
		var n = e.avg(t.field);
        var r = e.total(t.field, n);
        return [n, r];
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
	
	$scope.displayMeanScore = function(key)
	{
		return Math.round($scope.totalMarks[key]/$scope.totalStudents,1) || '-' ;
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