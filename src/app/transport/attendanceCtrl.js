'use strict';

angular.module('eduwebApp').
controller('attendanceCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
function($scope, $rootScope, apiService, $timeout, $window, $q, $parse){

	var initialLoad = true;
	document.getElementById("forNonAssigned").style.display = "none";
	document.getElementById("checkNonAssigned").style.display = "none";
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
	$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' || $rootScope.currentUser.user_type == 'FINANCE_CONTROLLED' ? true : false );
	$scope.nonTransport = ( $rootScope.currentUser.emp_id == null || $rootScope.currentUser.emp_id == undefined ? true : false );
	$scope.notTeacherMessage = "For mobility, this feature (Picking up / Dropping off students) is used from the mobile app. This allows the school to later track the movement of the school bus and where each student was picked or dropped off, in what order and at what time.";


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


		var loadCurrUserResults = function(response)
    	{
    		var result = angular.fromJson(response);

    		if( result.response == 'success')
    		{
    			$scope.transportData = ( result.nodata ? {} : result.data );
    			// console.log("Fetching current user data",$scope.transportData);

    			if( $rootScope.currentUser.emp_id != null && $scope.transportData.employeeCheck == 'stop'){
    			    document.getElementById("forNonAssigned").style.display = "";
    			}
    			if( $rootScope.currentUser.emp_id != null && $scope.transportData.employeeCheck == 'okay'){
    			    document.getElementById("checkNonAssigned").style.display = "";
    			    document.getElementById("forNonAssigned").style.display = "none";
    			}
    			$scope.nonAssignedMessage = "You have not been assigned a school bus either as a driver or an assistant. To proceed you must be either of the two.";

    			// Check if the user is assigned to more than one bus. If so, ask to select which one to proceed with
    			if( parseInt($scope.transportData.employeeCheckBusCount)>1 ){
    			    // console.log("The user is assigned to " + parseInt($scope.transportData.employeeCheckBusCount) + " buses");
    			}else{
    			    // console.log("The user is assigned to only " + parseInt($scope.transportData.employeeCheckBusCount) + " bus.");
    			}
    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}

    	}

		// fetch school bus data related to the current logged in user
		if($rootScope.currentUser.emp_id != null){
		    apiService.getDriverOrGuideRouteBusStudents($rootScope.currentUser.emp_id, loadCurrUserResults, apiError);
		}

		// console.log("The Scope",$scope);
		// console.log("The rootScope",$rootScope);
	}
	$timeout(initializeController,1);

	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.filters.class_id = newVal.class_id;

		apiService.getExamTypes(newVal.class_cat_id, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata ){
				$scope.examTypes = result.data;
				$scope.filters.exam_type_id = $scope.examTypes[0].exam_type_id;
				$timeout(setSearchBoxPosition,10);
			}
		}, apiError);


	});

	// hide the timetable setup until the class and term are selected
	// var ttView = document.getElementsByClassName("ttView")[0];
	// ttView.style.display = "none";

	$scope.activityStatus1 = false; //we use this to hide the div until a button is clicked
	$scope.activityStatus2 = false; //we use this to hide the div until a button is clicked

	$scope.pickingUp = function(){
	    // console.log("Picking Up Students");
	    $('.pickUp').addClass('pickUpSelected').removeClass('pickUp');

	    $scope.activityStatus1 = true;
	    $scope.boxTitle1 = "Picking Up Students";
	    $scope.selectRouteMessage = "Confirm the bus / route to proceed with pick up";
	    $scope.boxTitle2 = "Picking Up Students In " ;

	    if ($(".dropOffSelected")[0]){
            $('.dropOffSelected').addClass('dropOff').removeClass('dropOffSelected');
        }

        $scope.pickUpOrDropOffStatus = "Done Picking Up Students";
	}

    $scope.droppingOff = function(){
	    // console.log("Dropping Off Students");
	    $('.dropOff').addClass('dropOffSelected').removeClass('dropOff');

	    $scope.activityStatus1 = true;
	    $scope.boxTitle1 = "Dropping Off Students";
	    $scope.selectRouteMessage = "Confirm the bus / route to proceed dropping off";
	    $scope.boxTitle2 = "Dropping Off Students In ";

	    if ($(".pickUpSelected")[0]){
            $('.pickUpSelected').addClass('pickUp').removeClass('pickUpSelected');
        }

        $scope.pickUpOrDropOffStatus = "Done Dropping Off Students";
	}

	$scope.pickOrDrop = function(){
	    $scope.theRoute = $("#routeName option:selected").text();
	    $scope.activityStatus2 = true;
	}

	$scope.redirectTransportHome = function(){
	    location.reload();
	}

	var loadStudentsInBus = function(response)
    {
            /* make the pane visible */
            $scope.pickOrDrop();

    		var result = angular.fromJson(response);

    		if( result.response == 'success')
    		{
    			$scope.studentsInBus = ( result.nodata ? {} : result.data );
    			console.log("Fetching students in the bus",$scope.studentsInBus);


    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}

    }

	$scope.getChildren = function(el){
      console.log(el);
      // console.log("The selected bus id is " + el.selection.bus_id);

      /* fetch the students in the selected bus */
      apiService.getStudentsInBus(el.selection.bus_id, loadStudentsInBus, apiError);
    }

    $scope.pickUpStdnt = function(el){
        /*$.get( "https://cors-anywhere.herokuapp.com/https://simply-analytics.co.ke/gpos.php", function( data ) {
          console.log( "Load was performed." );
          console.log(data);
        });*/
        // console.log("We're picking up this student");
        console.log(el);

        var timenow = new Date();
        timenow.getHours();
        timenow.getMinutes();
        timenow.getSeconds();

        var weekday = new Array(7);
          weekday[0] = "Sun";
          weekday[1] = "Mon";
          weekday[2] = "Tue";
          weekday[3] = "Wed";
          weekday[4] = "Thur";
          weekday[5] = "Fri";
          weekday[6] = "Sat";

          var theDay = weekday[timenow.getDay()];

        var fullTimeNow = timenow.getHours() + ":" + timenow.getMinutes() + ":" + timenow.getSeconds();

        var guardian_name = ( el.student.guardian_name == null ? "No Parent" : el.student.guardian_name );
        var guardian_phone = ( el.student.telephone == null ? "No Parent Phone" : el.student.telephone );

        $.get( "https://cors-anywhere.herokuapp.com/http://gd.geobytes.com/GetCityDetails", function( data ) {
          console.log( "Load was performed." );
          // console.log(data);

                          var gpsObj = data;
                          var gpsLoc = "LAT= " + gpsObj.geobyteslatitude + " LONG= " + gpsObj.geobyteslongitude;
                          // compose the message
                          var messageToParent = "PARENT: " + guardian_name + "\n\n Your child " + el.student.student_name + " has been picked up by school bus " + el.student.bus_registration + " on " + theDay + " " + fullTimeNow + ", driven by " + el.student.driver_name;
                          var messageToAlert = "PARENT: " + guardian_name + "\n\n Your child " + el.student.student_name + " has been picked up by school bus " + el.student.bus_registration + " on " + theDay + " " + fullTimeNow + ", driven by " + el.student.driver_name + "\n\n PARENT PHONE: " + guardian_phone + "\n\n GPS: " + gpsLoc;


                          // post to database

                          // send the sms
                          var smsToPost = {
                                    "message_by": window.location.host.split('.')[0] + " schoolbus " + el.student.bus_registration,
                                    "message_date": new Date(),
                                    "message_recipients": [{"phone_number": guardian_phone, "recipient_name": guardian_name}],
                                    "message_text": messageToParent,
                                    "subscriber_name": window.location.host.split('.')[0]
                                };

                        // console.log("sms obj",smsToPost);
                        /*
                          var smsurl = "http://41.72.203.166/sms_api_staging/api/sendBulkSms";
                                  $.ajax({
                                          type: "POST",
                                          url: smsurl,
                                          data: JSON.stringify(smsToPost),
                                          contentType: "application/json",
                                          dataType: "json",
                                          success: function (data, status, jqXHR) {
                                              console.log("Success Func. Msg Sent");
                                              console.log(data);
                                              console.log(status);
                                          },
                                          error: function (xhr) {
                                              console.log("Error Func. Probably a false positive");
                                              console.log(xhr);
                                          }
                                  });
                        */
                        alert(messageToAlert);

                        var sortOrder = parseInt(document.getElementById('sortOrd').value, 10);
                        sortOrder = isNaN(sortOrder) ? 0 : sortOrder;
                        sortOrder ++;
                        document.getElementById('sortOrd').value = sortOrder;

                        var schoolBusHistoryObj = {
                            bus_id: el.student.bus_id,
                            bus_type: el.student.bus_type,
                            bus_registration: el.student.bus_registration,
                            route_id: el.student.route_id,
                            bus_driver: el.student.driver_id,
                            bus_guide: el.student.guide_id,
                            gps: gpsObj.geobyteslatitude + "," + gpsObj.geobyteslongitude,
                            gps_time: new Date(),
                            gps_order: sortOrder,
                            activity: "pickup",
                            student_id: el.student.student_id
                        };

                        // console.log(schoolBusHistoryObj);

                        /* pick up this student */
                        var createHistorySuccess = function ( response, status, params )
                        {

                        		var result = angular.fromJson( response );
                        		if( result.response == 'success' )
                        		{

                                    // console.log("Success. Activity saved.");
                                    var progressMessageEl = document.getElementById('checklist');

                                    var checklistCount = parseInt(document.getElementById('sortOrd2').value, 10);
                                    checklistCount = isNaN(checklistCount) ? 0 : checklistCount;
                                    checklistCount ++;
                                    document.getElementById('sortOrd2').value = checklistCount;

                                    var progressMsg = "(" + checklistCount + " students picked up)";
                                    // console.log(progressMsg);
                                    progressMessageEl.textContent = progressMsg;

                        		}
                        		else
                        		{
                        			$scope.error = true;
                        			$scope.errMsg = result.data;
                        		}
                        }
                        apiService.createSchoolBusHistory(schoolBusHistoryObj, createHistorySuccess, apiError);
                          // return data and exit
                          return gpsObj;
                      });
        /*
        console.log("My GPS obj",gpsObj);
        var messageToParent = "PARENT: " + guardian_name + "\n\n Your child " + el.student.student_name + " has been picked up by school bus " + el.student.bus_registration + " on " + theDay + " " + fullTimeNow + ", driven by " + el.student.driver_name + "\n\n PARENT PHONE: " + guardian_phone + "\n\n GPS: Requires https";
        alert(messageToParent);
        */
      /* pick up this student */
      // apiService.getStudentsInBus(el.selection.bus_id, loadStudentsInBus, apiError);

      console.log(this);
    }

    $scope.dropOffStdnt = function(el){
        console.log("We're dropping off this student");
        console.log(el);

        var timenow = new Date();
        timenow.getHours();
        timenow.getMinutes();
        timenow.getSeconds();

        var weekday = new Array(7);
          weekday[0] = "Sun";
          weekday[1] = "Mon";
          weekday[2] = "Tue";
          weekday[3] = "Wed";
          weekday[4] = "Thur";
          weekday[5] = "Fri";
          weekday[6] = "Sat";

          var theDay = weekday[timenow.getDay()];

        var fullTimeNow = timenow.getHours() + ":" + timenow.getMinutes() + ":" + timenow.getSeconds();

        var guardian_name = ( el.student.guardian_name == null ? "No Parent" : el.student.guardian_name );
        var guardian_phone = ( el.student.telephone == null ? "No Parent Phone" : el.student.telephone );

        var messageToParent = "PARENT: " + guardian_name + "\n\n Your child " + el.student.student_name + " has been dropped off by school bus " + el.student.bus_registration + " on " + theDay + " " + fullTimeNow + ", driven by " + el.student.driver_name + "\n\n PARENT PHONE: " + guardian_phone + "\n\n GPS: Requires https";
        alert(messageToParent);

      /* drop off this student */
      // apiService.getStudentsInBus(el.selection.bus_id, loadStudentsInBus, apiError);
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
				order: [2,'asc'],
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

	$scope.importExamMarks = function()
	{
		$rootScope.wipNotice();
	}

	$scope.exportData = function()
	{
		$rootScope.wipNotice();
	}

	$scope.refresh = function ()
	{
		$scope.loading = true;
		$scope.refreshing = true;
		$rootScope.loading = true;
		$scope.getStudentExams();
	}

	/*
	var createHistorySuccess = function ( response, status, params )
    {

    		var result = angular.fromJson( response );
    		if( result.response == 'success' )
    		{

                console.log("Success. Activity saved.");
                var progressMessageEl = document.getElementById('checklist');

                var checklistCount = parseInt(document.getElementById('sortOrd2').value, 10);
                checklistCount = isNaN(checklistCount) ? 0 : checklistCount;
                checklistCount ++;
                document.getElementById('checklistCount').value = checklistCount;

                var progressMsg = "(" + checklistCount + " students picked up)";

                checklistCount.appendChild(progressMsg);

    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}
    }
    */

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

    $scope.remove = function (e) {
        console.log("Attempting to remove element");
    }


} ]);
