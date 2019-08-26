'use strict';

angular.module('eduwebApp').
controller('listReportCardsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
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
	$scope.getReport = "reportTable";
	$scope.loading = true;
	$scope.studentReports2 = [];

	var initializeController = function ()
	{
		// get terms
		var year = moment().format('YYYY');
		apiService.getTerms(year, function(response){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.terms = result.data;
					// console.log($scope.terms);
				}

			}, apiError);

		// get classes
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
						$scope.getStudentReportCards();
					}

				}, apiError);

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
						$scope.getStudentReportCards();
					}

				}, apiError);
			}
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
			$scope.filters.class = $scope.classes[0];
			$scope.filters.class_id = $scope.classes[0].class_id;
			$scope.getStudentReportCards();
		}

        //get all students
        var loadStudents = function(response,status, params)
        {
            var result = angular.fromJson(response);
            if( result.response == 'success')
            {
              if( result.nodata ) var formatedResults = [];
              else {
                // make adjustments to student data
                var formatedResults = $rootScope.formatStudentData(result.data);
              }

              $scope.students = formatedResults;

            }
            else
            {
              $scope.error = true;
              $scope.errMsg = result.data;
            }
        }
        apiService.getAllStudents(true, loadStudents, apiError);

	}
	$timeout(initializeController,1);


	$scope.getStudentReportCards = function()
	{
		$scope.reportCards = {};
		$scope.tableHeader = [];
		$scope.reportsNotFound = false;
		$scope.getReport = "";

		var request = $scope.filters.class.class_id;
		apiService.getAllStudentReportCards(request, loadReportCards, apiError);
	}

	var loadReportCards = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.reportsNotFound = true;
				$scope.errMsg = "There are currently no report cards entered for this class.";
			}
			else
			{

				$scope.rawReportCards = result.data;

				$scope.reportCards = {};

				// group the reports by student
				$scope.reportCards.students = [];
				var lastStudent = '';
				var reports = {};
				var i = 0;
				angular.forEach($scope.rawReportCards, function(item,key){

					if( item.student_id != lastStudent )
					{
						// changing to new student, store the report
						if( i > 0 ) $scope.reportCards.students[(i-1)].reports = reports;

						$scope.reportCards.students.push(
							{
								student_name: item.student_name,
								student_id: item.student_id,
								class_id: item.class_id,
								class_cat_id: item.class_cat_id,
								report_card_id: item.report_card_id,
								report_card_type: item.report_card_type,
								teacher_id: item.teacher_id,
								teacher_name: item.teacher_name,
								class_name: item.class_name,
								term_id: item.term_id,
								date: item.date,
								year: item.year,
								admission_number: item.admission_number,
								published: item.published
							}
						);

						reports = {};
						i++;

					}
					reports[item.term_name] = {
						term_id : item.term_id,
						year: item.year,
						published : item.published,
						report_card_id: item.report_card_id,
						report_card_type: item.report_card_type,
						class_name: item.class_name,
						class_id: item.class_id,
						teacher_id: item.teacher_id,
						teacher_name: item.teacher_name,
						date: item.date,
						data: item.report_data
					};

					lastStudent = item.student_id;

				});
				$scope.reportCards.students[(i-1)].reports = reports;


				$scope.getReport = "reportTable";
				$timeout(initDataGrid,100);
			}

		}
		else
		{
			$scope.reportsNotFound = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.getReportCard = function(item, term_name, reportData)
	{
		var student = {
			student_id :item.student_id,
			student_name : item.student_name,
			admission_number: item.admission_number,
			class_teacher_id: item.teacher_id,
			report_card_type: item.report_card_type
		}
		var data = {
			student : student,
			report_card_id: reportData.report_card_id,
			class_name : reportData.class_name,
			class_id : reportData.class_id,
			published: reportData.published,
			term_id: reportData.term_id,
			term_name : term_name,
			year: reportData.year,
			report_card_type: reportData.report_card_type,
			teacher_id: reportData.teacher_id,
			teacher_name: reportData.teacher_name,
			date: reportData.date,
			reportData: reportData.data,
			adding: false,
			filters:{
				term:{
					term_name:term_name,
					term_id: item.term_id,
				},
				class:{
					class_id: item.class_id,
					class_cat_id: item.class_cat_id
				}
			}
		};

		$scope.openModal('exams', 'reportCard', 'lg', data);

	}

	var initDataGrid = function()
	{

		var tableElement = $('#resultsTable');
		$scope.dataGrid = tableElement.DataTable( {
				responsive: {
					details: {
						type: 'column'
					}
				},
				columnDefs: [ {
					className: 'control',
					orderable: false,
					targets:   0
				} ],
				paging: false,
				destroy:true,
				order: [1,'asc'],
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
						emptyTable: "No report cards found."
				},
			} );


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

	$scope.addReportCard = function()
	{
		var data = {
			classes: $scope.classes,
			terms: $scope.terms,
			filters: $scope.filters,
			adding: true
		}
		$scope.openModal('exams', 'reportCard', 'lg', data);
	}

	var BulkData = [];
	$scope.bulkPrint = function()
	{
		// function rptCardsForBp(){
				// filter the students to the chosen class
				$scope.classStudents = $scope.students.filter(function (el) {
				  return el.class_id == $scope.filters.class.class_id;
				});
				// console.log("These class students",$scope.classStudents);

				for(var i=0;i<$scope.classStudents.length;i++) {
					let percentage = (i/$scope.classStudents.length)*100;
					$scope.preBulkLoad = percentage + '%';
					document.getElementById('preBulkLoad').style.dispaly = 'block';

					var loadBpReportCards = function(response,status)
					{
						var result = angular.fromJson( response );
						if( result.response == 'success' )
						{
							if( result.nodata )
							{
								$scope.bpReportCards = {};
							}
							else
							{
								$scope.bpRawReportCards = result.data;
								$scope.bpReportCards = {};

								// get unique terms
								$scope.bpReportCards.terms = $scope.bpRawReportCards.reduce(function(sum,item){
									if( sum.indexOf(item.term_name) === -1 ) sum.push(item.term_name);
									return sum;
								}, []);

								// group the reports by class
								$scope.bpReportCards.classes = [];
								var lastClass = '';
								var lastTerm = '';
								var reports = {};
								var j = 0;
								var student_id = result.data[0].student_id;
								angular.forEach($scope.bpRawReportCards, function(item,key){
									if( item.class_name != lastClass )
									{
										// changing to new class, store the report
										if( j > 0 ) $scope.bpReportCards.classes[(j-1)].reports = reports;
										$scope.bpReportCards.classes.push(
											{
												report_card_id: item.report_card_id,
												class_name: item.class_name,
												class_id: item.class_id,
												class_cat_id: item.class_cat_id,
												report_card_type: item.report_card_type,
												teacher_id: item.teacher_id,
												teacher_name: item.teacher_name,
												term_id: item.term_id,
												date: item.date,
												year: item.year,
												published: item.published
											}
										);
										reports = {};
										j++;

									}
									reports[item.term_name] = {
										term_id : item.term_id,
										year: item.year,
										published : item.published,
										report_card_id: item.report_card_id,
										report_card_type: item.report_card_type,
										class_name: item.class_name,
										class_id: item.class_id,
										teacher_id: item.teacher_id,
										teacher_name: item.teacher_name,
										date: item.date,
										data: item.report_data,
										entity_id: item.entity_id
									};
									lastClass = item.class_name;
									lastTerm = item.term_name;

								});
								$scope.bpReportCards.classes[(j-1)].reports = reports;
								$scope.studentReports = {};
								$scope.studentReports[student_id] = $scope.bpReportCards.classes[(j-1)].reports;
								$scope.studentReports2.push($scope.studentReports[student_id]);
							}

							/*
							var cnt = 0;
							angular.forEach($scope.studentReports2, function(value, key) {
								// console.log("Incrementing",$scope.studentReports2);
							   cnt++;
							});

							if(cnt === $scope.classStudents.length)
							{
								angular.forEach($scope.terms, function(value, key) {
								var v =   value.term_name;
									var span = '<input type="submit" class="btn btn-link"  ng-click="grid.appScope.bulkPrint(\'' + v + '\')" value="PRINT" />';
									var col ='<div class="ui-grid-cell-contents" >' + value.term_name + span + '</div>';
									var click = 'ng-click="getReportCard(item, term.term_name, item.reports[term.term_name])"';
									var cell = '<span  class="glyphicon glyphicon-file icon-lg"></span>';
									//$scope.gridOptions.columnDefs.push({ name: value.term_name, field: 'none', category: 'REPORTS CARDS', headerCellTemplate: col, cellTemplate: cell,  enableColumnMenu: false,});
								});
								$scope.showPrint = true;
							}
							*/
						}
						else
						{
							console.log(result.data);
						}
					}
					// console.log("Class students", $scope.classStudents[i]);
					apiService.getStudentReportCards($scope.classStudents[i].student_id, loadBpReportCards, apiError);
				}
		// }
		// rptCardsForBp();

	  console.log("Bulk print initiated");
		// console.log($scope.filters);
		BulkData = [];
		var term_name  = $scope.filters.term.term_name;

		$scope.studentReports = $scope.studentReports2;
		setTimeout(function(){
			// console.log("Student reports ::: ",$scope.studentReports);
			// console.log("Reports two ::: ",$scope.studentReports2);
		angular.forEach($scope.studentReports2, function(item,key)
		{
			// console.log("Student reports item",item,"key = " + key);
      var student = $scope.classStudents.find(function (stud) { if(stud.student_id == key){return stud;} });
			// console.log(student);
			// var student = key;
      var studentTermObj = $scope.studentReports2[key];
      // console.log("Student term obj :::",studentTermObj);
      if(studentTermObj.hasOwnProperty(term_name)){
								// console.log("This students has data from needed term",term_name);
                var studentsWithExams = function(response,status)
          	    {

              		var result = angular.fromJson( response );
              		if( result.response == 'success' )
              		{
              			if( result.nodata )
              			{
              				$scope.students2 = {};
              				$scope.reportsNotFound = true;
              				$scope.errMsg = "No students found. Try another criteria.";
              			}
              			else
              			{

              				var rawFilteredStudents = result.data;
                            // console.log("Succeses. Students found!");
                            $scope.students2 = rawFilteredStudents.reduce(function(acc, cur, i) { acc[i] = cur; return acc; }, {});
														// console.log($scope.students2);
              			}
              		}
              		else
              		{
              			console.log("There might be an API issue");
              			$scope.errMsg = result.data;
              		}
              	}
                var paramForFilter = $scope.filters.class.class_id + '/' + $scope.filters.term.term_name;
                // console.log("Param for filter :::",paramForFilter);
                apiService.getClassStudentsWithExamInTerm(paramForFilter, studentsWithExams, apiError);

                // setTimeout(function(){ console.log("Students with report cards are >>"); },1000);
                // setTimeout(function(){ console.log($scope.students2); },1000);
								// console.log("Student reports",$scope.studentReports);
		      			var class_id = $scope.studentReports2[key][term_name].class_id;
		      			var class_obj = $scope.classes.find(function (obj) { return obj.class_id === class_id; });
								// console.log("Class Obj",class_obj);

      			var data =
      				{
      					// student : student,
								student: $scope.classStudents[key],
      					report_card_id: $scope.studentReports2[key][term_name].report_card_id,
      					class_name : $scope.studentReports2[key][term_name].class_name,
      					class_id : $scope.studentReports2[key][term_name].class_id,
      					published: $scope.studentReports2[key][term_name].published,
      					term_id: $scope.studentReports2[key][term_name].term_id,
      					// entity_id: $scope.studentReports2[key][term_name].entity_id,
								entity_id: $scope.filters.class.entity_id,
      					term_name : term_name,
      					year: $scope.studentReports2[key][term_name].year,
      					report_card_type: $scope.studentReports2[key][term_name].report_card_type,
      					teacher_id: $scope.studentReports2[key][term_name].teacher_id,
      					teacher_name: $scope.studentReports2[key][term_name].teacher_name,
      					date: $scope.studentReports2[key][term_name].date,
      					reportData: $scope.studentReports2[key][term_name].data,
      					adding: false,
      					filters:{
      						term:{
      							term_name:term_name,
      							term_id: $scope.studentReports2[key][term_name].term_id,
      						},
      						class:{
      							class_id: $scope.studentReports2[key][term_name].class_id,
      							class_cat_id: $scope.filters.class.class_cat_id,
										entity_id: $scope.filters.class.entity_id
      						}
      					}

      				};
							// console.log(data);
      				BulkData[key] = data;
            }


		});
		},10000);

		setTimeout(function(){
		 	$scope.openModal('exams', 'reportCardData', 'sm', angular.fromJson(BulkData));
		},25000);
		// $scope.openModal('exams', 'reportCardData', 'sm', angular.fromJson(BulkData));

	}

	$scope.preBulkPrint = function(){
		// Get the modal
		var modal = document.getElementById("batchPrntParams");

		// Get the button that opens the modal
		var btn = document.getElementById("batchPreModal");

		// Get the <span> element that closes the modal
		var span = document.getElementsByClassName("close")[0];

		// When the user clicks the button, open the modal
		btn.onclick = function() {
		  modal.style.display = "block";
		}

		// When the user clicks on <span> (x), close the modal
		span.onclick = function() {
		  modal.style.display = "none";
		}

		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
		  if (event.target == modal) {
		    modal.style.display = "none";
		  }
		}

	}

	$scope.$on('refreshReportCards', function(event, args) {

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
		$scope.getStudentReportCards();
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
