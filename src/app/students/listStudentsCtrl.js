'use strict';

angular.module('eduwebApp').
controller('listStudentsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state','dialogs',
function($scope, $rootScope, apiService, $timeout, $window, $state, $dialogs){

  var initialLoad = true;
  $scope.filters = {};
  $scope.filters.status = 'true';
  $scope.filters.date = {startDate:null, endDate:null};

  var lastQueriedDateRange = null;
  var requery = false;
  $scope.students = [];
  $scope.filterShowing = false;
  $scope.toolsShowing = false;
  var currentStatus = true;
  var isFiltered = false;
  $scope.loading = true;
  $rootScope.modalLoading = false;
  $scope.alert = {};

  $scope.gridFilter = {};
  $scope.gridFilter.filterValue  = '';

  $scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false);

  $scope.ViewCats = true;
  $scope.term = {};




  var rowTemplate = function()
  {
    return '<div class="clickable" ng-class="{\'alert-danger\': row.entity.balance > 0, \'alert-success\' : row.entity.balance == 0}" ng-click="grid.appScope.viewStudent(row.entity)">' +
    '  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
    '  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
    '</div>';
  }

  $scope.gridOptions = {
    enableSorting: true,
    rowTemplate: rowTemplate(),
    rowHeight:24,
	headerTemplate: 'app/students/headerTemplate.html',
	category: [
             { name: 'main', visible: true, showCatName: false },
             { name: 'REPORTS CARDS', visible: true, showCatName: true }
        ],
    columnDefs: [
      { name: 'Name', field: 'student_name', category: 'main', enableColumnMenu: false, sort: {direction:'asc'}},
      { name: 'Class', field: 'class_name', category: 'main', enableColumnMenu: false,},
      { name: 'Admission Number', field: 'admission_number', category: 'main', enableColumnMenu: false,},
      { name: 'Admission Date', field: 'admission_date', category: 'main', type:'date', cellFilter:'date', enableColumnMenu: false,},

    ],
    exporterCsvFilename: 'students.csv',
    onRegisterApi: function(gridApi){
      $scope.gridApi = gridApi;
      $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
      $timeout(function() {
      $scope.gridApi.core.handleWindowResize();
      });
    }
  };

  $scope.$watch('filters.date', function(newVal,oldVal){
    if(newVal == oldVal) return;
    if( newVal !== lastQueriedDateRange ) requery = true;
    else requery = false;
    lastQueriedDateRange = newVal;
  });

  var initializeController = function ()
  {
    // if user is a teacher, we only want to give them class categories and classes that they are associated with
    if ( $scope.isTeacher )
    {
      apiService.getClassCats($rootScope.currentUser.emp_id, function(response){
        var result = angular.fromJson(response);

        // store these as they do not change often
        if( result.response == 'success')
        {
          $rootScope.classCats = result.data;

          // get classes
          if( $rootScope.allClasses === undefined )
          {
            apiService.getAllClasses({}, function(response){
              var result = angular.fromJson(response);

              // store these as they do not change often
              if( result.response == 'success')
              {
                //$rootScope.allClasses = result.data;
                $scope.classes = result.data;

                getStudents('true',false );

                if( $state.params.class_cat_id !== null )
                {
                  $scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
                  $scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
                  $scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
                  $scope.filterClass = ( $state.params.class_id !== '' ? true : false );
                }
                else
                {
                  if( $rootScope.classCats.length == 1 ) $scope.filters.class_cat_id = $rootScope.classCats[0].class_cat_id;
                }

              }

            }, apiError);
          }
          else
          {
            $scope.classes = $rootScope.allClasses;
            getStudents('true',false);

            if( $state.params.class_cat_id !== null )
            {
              $scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
              $scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
              $scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
              $scope.filterClass = ( $state.params.class_id !== '' ? true : false );

            }
            else
            {
              if( $rootScope.classCats.length == 1 ) $scope.filters.class_cat_id = $rootScope.classCats[0].class_cat_id;
            }
          }
        }

      }, apiError);
    }
    else
    {
      // get classes
      if( $rootScope.allClasses === undefined )
      {
        apiService.getAllClasses({}, function(response){
          var result = angular.fromJson(response);

          // store these as they do not change often
          if( result.response == 'success')
          {
          //  $rootScope.allClasses = ;
            $scope.classes = result.data;

            getStudents('true',false );

            $scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
            $scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
            $scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
            $scope.filterClass = ( $state.params.class_id !== '' ? true : false );
          }

        }, apiError);
      }
      else
      {
        $scope.classes = $rootScope.allClasses;

        $scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
        $scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
        $scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
        $scope.filterClass = ( $state.params.class_id !== '' ? true : false );


        getStudents('true',false);
      }

	  $scope.filters.term_name = 'TERM 1';

    }

    // get terms
    if( $rootScope.terms === undefined )
    {
      var year = moment().format('YYYY');
      apiService.getTerms(year, function(response){
        var result = angular.fromJson(response);
        if( result.response == 'success')
        {
          $scope.terms = result.data;
          $rootScope.terms = result.data;
          $rootScope.setTermRanges(result.data);
        }
      }, function(){});
    }
    else
    {
      $scope.terms  = $rootScope.terms;
      $rootScope.setTermRanges($scope.terms );
    }




		// get fee items
		apiService.getFeeItems(true, function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.optFeeItems = result.data.optional_items;
			}

		}, function(){});

    // get transport routes
		apiService.getTansportRoutes({}, function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.transportRoutes = result.data;
			}

		}, function(){});

    var studentTypes = $rootScope.currentUser.settings['Student Types'];
		$scope.studentTypes = studentTypes.split(',');

    setTimeout(function(){
      var height = $('.full-height.datagrid').height();
      $('#grid1').css('height', height);
      $scope.gridApi.core.handleWindowResize();
    },100);

  }
  $timeout(initializeController,1);

  var getStudents = function(status, filtering)
  {
    $scope.activeFilters = angular.copy($scope.filters);

    if( $scope.activeFilters.date.startDate !== null )
    {
      var dateRange = moment($scope.activeFilters.date.startDate).format('YYYY-MM-DD') + '/' + moment($scope.activeFilters.date.endDate).format('YYYY-MM-DD');
    }

    if ( $scope.isTeacher )
    {
      var params = $rootScope.currentUser.emp_id + '/' + status;
      if( dateRange ) params += '/' + dateRange;
      apiService.getTeacherStudents(params, loadStudents, apiError, {filtering:filtering});
    }
    else
    {
      var params = status;
      if( dateRange ) params += '/' + dateRange;
      apiService.getAllStudents(params, loadStudents, apiError, {filtering:filtering,status:status});
    }

  }

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

      if( params.status == 'false' )
      {
        $scope.formerStudents = formatedResults
        filterStudents(false);
      }
      else
      {
        $scope.allStudents = formatedResults;
        $scope.students = formatedResults;

        if( $scope.filterClassCat || $scope.filterClass ) filterStudents();
        initDataGrid($scope.students);
      }

    }
    else
    {
      $scope.error = true;
      $scope.errMsg = result.data;
    }
  }

  var apiError = function (response, status)
  {
    var result = angular.fromJson( response );
    $scope.error = true;
    $scope.errMsg = result.data;
  }

  var initDataGrid = function( data )
  {

    $scope.gridOptions.data = $scope.students;
    $scope.loading = false;
    $rootScope.loading = false;

	//Get report cards

	//for(var i=0;i<$scope.students.length;i++) {
	//		console.log($scope.students[i].student_id)
	//		apiService.getStudentReportCards($scope.students[i].student_id, loadReportCards, apiError);
	//	}



  }




  var loadReportCards = function(response,status)
	{

		$scope.loading = false;

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.reportCards = {};
				$scope.reportsNotFound = true;
				$scope.errMsg = "There are currently no report cards entered for this student.";
			}
			else
			{


				$scope.rawReportCards = result.data;

				$scope.reportCards = {};

				// get unique terms
				$scope.reportCards.terms = $scope.rawReportCards.reduce(function(sum,item){
					if( sum.indexOf(item.term_name) === -1 ) sum.push(item.term_name);
					return sum;
				}, []);


				// group the reports by class
				$scope.reportCards.classes = [];
				var lastClass = '';
				var lastTerm = '';
				var reports = {};
				var i = 0;
				var student_id = result.data[0].student_id;
				angular.forEach($scope.rawReportCards, function(item,key){

					if( item.class_name != lastClass )
					{
						// changing to new class, store the report
						if( i > 0 ) $scope.reportCards.classes[(i-1)].reports = reports;

						$scope.reportCards.classes.push(
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
						data: item.report_data,
						entity_id: item.entity_id
					};

					lastClass = item.class_name;
					lastTerm = item.term_name;

				});
				$scope.reportCards.classes[(i-1)].reports = reports;

				$scope.studentReports[student_id] = $scope.reportCards.classes[(i-1)].reports;

			}


			var cnt = 0;

			angular.forEach($scope.studentReports, function(value, key) {
			   cnt++;
			  });


			if(cnt === $scope.students.length)
			{



				angular.forEach($scope.terms, function(value, key) {
				var v =   value.term_name;
					var span = '<input type="submit" class="btn btn-link"  ng-click="grid.appScope.bulkPrint(\'' + v + '\')" value="PRINT" />'
					var col ='<div class="ui-grid-cell-contents" >' + value.term_name + span + '</div>'

					var click = 'ng-click="getReportCard(item, term.term_name, item.reports[term.term_name])"'
					var cell = '<span  class="glyphicon glyphicon-file icon-lg"></span>'

					//$scope.gridOptions.columnDefs.push({ name: value.term_name, field: 'none', category: 'REPORTS CARDS', headerCellTemplate: col, cellTemplate: cell,  enableColumnMenu: false,});


				  });


				$scope.showPrint = true;
			}
		}
		else
		{
			$scope.reportsNotFound = true;
			$scope.errMsg = result.data;
		}
	}



  var BulkData = [];
  $scope.bulkPrint = function(term_name)
	{
		BulkData = [];
		term_name  =$scope.filters.term_name;
		angular.forEach($scope.studentReports, function(item,key)
		{

      // console.log($scope.classes);
			var student = $scope.students.find(function (stud) { return stud.student_id === key; });
      var studentTermObj = $scope.studentReports[student.student_id];
      console.log(studentTermObj);
      //checking to see if all the students in the selected class have an exam in the selected term
      if(studentTermObj.hasOwnProperty(term_name)){
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
              console.log("Succeses. Students found!");
              $scope.students2 = rawFilteredStudents.reduce(function(acc, cur, i) { acc[i] = cur; return acc; }, {});
              // console.log($scope.students2);
      				// $scope.students2 = {};

      			}
      		}
      		else
      		{
      			console.log("There might be an API issue");
      			$scope.errMsg = result.data;
      		}
      	}
        var paramForFilter = $scope.filters.class_id + '/' + $scope.filters.term_name;
        console.log(paramForFilter);
        apiService.getClassStudentsWithExamInTerm(paramForFilter, studentsWithExams, apiError);
        // console.log(studentTermObj);
        // var termMatch = Object.keys(studentTermObj).toString();

        // var myObj = {"a": "test1"} //test this
        // if(myObj.a == "test1") {
        //     alert("test1 exists!"); //test this
        // }
        // console.log(Object.keys(studentTermObj).toString()); //test this

        setTimeout(function(){ console.log("Students with report cards are >>"); },1000);
        setTimeout(function(){ console.log($scope.students2); },1000);

  			var class_id = $scope.studentReports[student.student_id][term_name].class_id;
  			var class_obj = $scope.classes.find(function (obj) { return obj.class_id === class_id; });


  				var data =
  				{
  					student : student,
  					report_card_id: $scope.studentReports[student.student_id][term_name].report_card_id,
  					class_name : $scope.studentReports[student.student_id][term_name].class_name,
  					class_id : $scope.studentReports[student.student_id][term_name].class_id,
  					published: $scope.studentReports[student.student_id][term_name].published,
  					term_id: $scope.studentReports[student.student_id][term_name].term_id,
  					entity_id: $scope.studentReports[student.student_id][term_name].entity_id,
  					term_name : term_name,
  					year: $scope.studentReports[student.student_id][term_name].year,
  					report_card_type: $scope.studentReports[student.student_id][term_name].report_card_type,
  					teacher_id: $scope.studentReports[student.student_id][term_name].teacher_id,
  					teacher_name: $scope.studentReports[student.student_id][term_name].teacher_name,
  					date: $scope.studentReports[student.student_id][term_name].date,
  					reportData: $scope.studentReports[student.student_id][term_name].data,
  					adding: false,
  					filters:{
  						term:{
  							term_name:term_name,
  							term_id: $scope.studentReports[student.student_id][term_name].term_id,
  						},
  						class:{
  							class_id: $scope.studentReports[student.student_id][term_name].class_id,
  							class_cat_id: $scope.activeFilters.class_cat_id
  						}
  					}

  				};

  				BulkData[student.student_id] = data;
      }


			});

			$scope.openModal('exams', 'reportCardData', 'sm', angular.fromJson(BulkData));


	}



  $scope.filterDataTable = function()
  {
    $scope.gridApi.grid.refresh();
  };

  $scope.clearFilterDataTable = function()
  {
    $scope.gridFilter.filterValue = '';
    $scope.gridApi.grid.refresh();
  };

  $scope.singleFilter = function( renderableRows )
  {
    var matcher = new RegExp($scope.gridFilter.filterValue, 'i');
    renderableRows.forEach( function( row ) {
      var match = false;
      [ 'student_name', 'class_name', 'admission_number' ].forEach(function( field ){
      if ( row.entity[field].match(matcher) ){
        match = true;
      }
      });
      if ( !match ){
      row.visible = false;
      }
    });
    return renderableRows;
  };
  /*
  var setSearchBoxPosition = function()
  {
    if( !$rootScope.isSmallScreen )
    {
      var filterFormWidth = $('.dataFilterForm form').width();
      $('#resultsTable_filter').css('left',filterFormWidth+45);
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
  */
  $scope.$watch('filters.class_cat_id', function(newVal,oldVal){
    if (oldVal == newVal) return;

    if( newVal === undefined || newVal === null || newVal == '' )   $scope.classes = $rootScope.allClasses;
    else
    {
      // filter classes to only show those belonging to the selected class category
      $scope.classes = $rootScope.allClasses.reduce(function(sum,item){
        if( item.class_cat_id == newVal ) sum.push(item);
        return sum;
      }, []);
      //$timeout(setSearchBoxPosition,10);

    }
  });

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

  $scope.loadFilter = function()
  {
	 $scope.studentReports = [];

    $scope.loading = true;
    isFiltered = true;
    // make a copy of the current active filters
    $scope.activeFilters = angular.copy($scope.filters);

    // if user is filtering for former students and we have not previously pulled these, get them, then continue to filter
    if( $scope.activeFilters.status == 'false' && $scope.formerStudents === undefined )
    {
      // we need to fetch inactive students first
      getStudents('false', true);
    }
    else if( requery )
    {
      // need to get fresh data, most likely because the user selected a new year
      getStudents(currentStatus, true);
    }
    else
    {
      filterStudents(true);
    }

    // store the current status filter
    currentStatus = $scope.activeFilters.status;

  }

  var filterStudents = function(clearTable)
  {

    // filter by class category
    // allStudents holds current students, formerStudents, the former...
    var filteredResults = ( $scope.activeFilters.status == 'false' ? $scope.formerStudents : $scope.allStudents);


    if( $scope.activeFilters.class_cat_id )
    {
      // console.log($scope.activeFilters.class_cat_id);
      filteredResults = filteredResults.filter(function(item) {
        if( item.class_cat_id.toString() == $scope.activeFilters.class_cat_id.toString() ) return item;
      });
    }

    if( $scope.activeFilters.class_id )
    {

      filteredResults = filteredResults.filter(function(item) {
        if( item.class_id.toString() == $scope.activeFilters.class_id.toString() )
		{

			apiService.getStudentReportCards(item.student_id, loadReportCards, apiError);
			return item;

		}



      });
    }

    if( $scope.activeFilters.student_type )
    {
      filteredResults = filteredResults.filter(function(item) {
        if( item.student_type && item.student_type.toString() == $scope.activeFilters.student_type.toString() ) return item;
      });
    }
    if( $scope.activeFilters.course_id )
    {
      filteredResults = filteredResults.filter(function(item) {
        if( item.enrolled_opt_courses && item.enrolled_opt_courses.indexOf($scope.activeFilters.course_id.toString()) > -1 ) return item;
      });
    }

    if( $scope.activeFilters.route_id )
    {
      // console.log($scope.activeFilters.route_id);
      filteredResults = filteredResults.filter(function(item) {
        if( item.transport_route_id && item.transport_route_id.toString() == $scope.activeFilters.route_id.toString() ) return item;
      });
    }


    $scope.students = filteredResults;

    initDataGrid($scope.students);


  }

  $scope.addStudent = function()
  {
    $scope.openModal('students', 'addStudent', 'lg');
  }

  $scope.viewStudent = function(student)
  {
    var data = {
      student: student
    }
    $scope.openModal('students', 'viewStudent', 'lg',data);
  }

  $scope.importStudents = function()
  {
    $rootScope.wipNotice();
  }

  $scope.exportData = function()
  {
    $scope.gridApi.exporter.csvExport( 'visible', 'visible' );
  }

  $scope.promoteStudents = function ()
  {
    // if the user has selected a class, ask them if they want to promote all students of selected class
    if( $scope.activeFilters.class_id && $scope.activeFilters.class_id !== '' )
    {
      // get selected class
      var selectedClass = $scope.classes.filter(function(item){
        if( item.class_id == $scope.activeFilters.class_id ) return item;
      })[0];

      // show modal to select students and class to promote to
      var data = {
        selectedClass: $scope.activeFilters.class_id,
        selectedClassCat: selectedClass.class_cat_id,
        students: $scope.students,
        classes: $scope.classes
      }
      $scope.openModal('students', 'promoteStudents', 'sm', data);

    }
    else
    {
      // specific class is not selected, show selection
      var data = {
        selectedClass: undefined,
        students: $scope.students,
        classes: $scope.classes
      }
      $scope.openModal('students', 'promoteStudents', 'sm',data);
    }


  }

  $scope.$on('refreshStudents', function(event, args) {

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
    $rootScope.loading = true;
    getStudents(currentStatus,isFiltered);
  }

  $scope.$on('$destroy', function() {
    $rootScope.isModal = false;
    });


} ]);
