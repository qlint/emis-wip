'use strict';

angular.module('eduwebApp').
controller('schoolBusCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
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
	$scope.rawRoutes = [];
	$scope.newRawRoutes = [];
	//$scope.loading = true;
	$scope.showTcard = false;
	$scope.cardsLoaded = false;
	$scope.schoolName = $rootScope.currentUser.settings["School Name"];
	$scope.showSignature = (window.location.host.split('.')[0] == 'thomasburke' ? true : false);
	$scope.transpSignature = window.location.host.split('.')[0] + '-transport-signature.png';

	$("#multiRoute").mousedown(function(e){
	    e.preventDefault();
      	var select = this;
        var scroll = select.scrollTop;
        e.target.selected = !e.target.selected;
        setTimeout(function(){select.scrollTop = scroll;}, 0);
        $(select).focus();
    }).mousemove(function(e){e.preventDefault()});

	var initializeController = function ()
	{
		$rootScope.getCurrentTerm();
	    // get all students
	    var loadStudents = function(response,status, params)
          {
            var result = angular.fromJson(response);

            if( result.response == 'success')
            {
              $scope.allStudents = result.data;
              // console.log(result.data);
            }
            else
            {
              $scope.error = true;
              $scope.errMsg = result.data;
            }
          }
	    apiService.getAllStudents(true, loadStudents, apiError);

		// get classes
		var requests = [];

		// get all active buses
		var getBusesParam = true;
		apiService.getBuses(getBusesParam, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
					$scope.allBuses = ( result.nodata ? [] : result.data );

			}
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);

		// get all buses with assigned routes
		var getAssignedBusesParam = true;
		apiService.getAssignedBuses(getAssignedBusesParam, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
					$scope.allAssignedBuses = ( result.nodata ? [] : result.data );
			}
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);

		// get transport routes
        apiService.getTansportRoutes({}, function(response){
          var result = angular.fromJson(response);
          if( result.response == 'success')
          {
            if( result.nodata !== undefined)
            {
              $scope.transportRoutes = [];
            }
            else
            {
              $scope.transportRoutes = result.data;
              $scope.transportRoutes.forEach(function(routesArr) {
                var routeSplit = routesArr.route.split(',');
                routeSplit.forEach(function(rtName) {
                  let s1 = rtName.substring(rtName.indexOf(")")+1);
                  let s2 = s1.trim();
                  $scope.rawRoutes.push(s2.toUpperCase());
                });
              });
              $scope.routesUnsorted = [...new Set($scope.rawRoutes)];
              // $scope.routes = $scope.routesUnsorted.sort();
              $scope.routes0 = $scope.routesUnsorted.sort();
              // console.log("Start here",$scope.routes0);
              $scope.routes0.forEach(function(newRoutesArr) {
                  var newRouteSplit = newRoutesArr.split(" - ").pop();
                  // console.log(newRouteSplit);
                  // newRouteSplit.trim();
                  $scope.newRawRoutes.push(newRouteSplit);
              });
              var newRoutesUnsorted = [...new Set($scope.newRawRoutes)];
              $scope.routes = newRoutesUnsorted.sort();
            }
          }

        }, function(){console.log("There was an error fetching the transport routes.")});

		var loadDrivers = function(response)
    	{
    		var result = angular.fromJson(response);

    		if( result.response == 'success')
    		{
    			$scope.drivers = ( result.nodata ? {} : result.data );
    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}

    	}

		// fetch existing drivers
		apiService.getAllDrivers(true, loadDrivers, apiError);

		var loadBusAssistants = function(response)
    	{
    		var result = angular.fromJson(response);

    		if( result.response == 'success')
    		{
    			$scope.assistants = ( result.nodata ? {} : result.data );
    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}

    	}

		// fetch existing employees (will double as bus assistants)
		apiService.getAllEmployeesExceptDrivers(true, loadBusAssistants, apiError);

		var loadBusesDriversAndRoutes = function(response)
    	{
    		var result = angular.fromJson(response);

    		if( result.response == 'success')
    		{
    			$scope.transportDataTable = ( result.nodata ? {} : result.data );
    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}

    	}

		// fetch existing drivers
		apiService.getAllBusesRoutesAndDrivers(true, loadBusesDriversAndRoutes, apiError);

	}
	$timeout(initializeController,1);

	$scope.singleStudent = function(){
	    document.getElementById("myDropdown").classList.toggle("show");
	}

	$scope.filterFunction = function() {
      var input, filter, ul, li, a, i;
      input = document.getElementById("myInput");
      filter = input.value.toUpperCase();
      var div = document.getElementById("myDropdown");
      a = div.getElementsByTagName("a");
      for (i = 0; i < a.length; i++) {
        var txtValue = a[i].textContent || a[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          a[i].style.display = "";
        } else {
          a[i].style.display = "none";
        }
      }
    }

	$scope.generateTcards = function(){
	    // Get the modal
        var modal = document.getElementById("transportCards");

        modal.style.display = "block";

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("cloze")[0];

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

	$scope.loadTcard = function(el){
	    // console.log(el.student);
	    $scope.showTcard = true;

	    function fetchTheTcards(param){
	        // fetch them
					let postParam = param;
    	    var loadCardsData = function(response,status)
              {
                var result = angular.fromJson(response);

                if( result.response == 'success')
                {
                  $scope.studentTranpCards = result.data;
									$scope.studentTranpCards.forEach(function(item) {
										let formatRawTrip = item.trip_details.substring(2, item.trip_details.length-2);
										item.tripDetails = formatRawTrip.replace(/\\/g, '').replace(/"}/gi, '"}__').split('__');
										item.tripDetails = item.tripDetails.filter(function (el) {
										  return el != "";
										});
										for(let i=0;i < item.tripDetails.length;i++){
											if(i>0){
												item.tripDetails[i] = item.tripDetails[i].substring(3,item.tripDetails[i].length);
												item.tripDetails[i] = JSON.parse(item.tripDetails[i]);
											}
										}
										/*
										for(let j=0;j < item.tripDetails.length;j++){
											item.tripDetails[j] = JSON.parse(item.tripDetails[j]);
										}
										*/
									});
									for(let k=0;k < $scope.studentTranpCards.length;k++){
										let firstTrip = $scope.studentTranpCards[k].tripDetails;
										for(let l=0;l < firstTrip.length; l++){
											if(l<1){
												$scope.studentTranpCards[k].tripDetails[l] = JSON.parse($scope.studentTranpCards[k].tripDetails[l]);
											}
										}
									}
									$scope.studentTranpCards.forEach(function(student) {
									  for(let i=0;i < student.tripDetails.length;i++){
									        if(window.location.host.split('.')[0] == 'thomasburke'){
									            student.tripDetails[i].bus = student.tripDetails[i].dscription + (student.tripDetails[i].dscription == null ? '' : ' ') + student.tripDetails[i].bus;
									        }
											let theTrip = student.tripDetails[i].trip_name.toLowerCase();
											if(theTrip.includes('morning')){
												student.tripDetails[i].trip_time = 'MORNING';
											}else if(theTrip.includes('evening')){
												student.tripDetails[i].trip_time = 'EVENING';
											}
										}
									});
									console.log($scope.studentTranpCards);
                }
                else
                {
                  $scope.error = true;
                  $scope.errMsg = result.data;
                }
              }
    	    apiService.getTransportCards(postParam, loadCardsData, apiError);
	    }

	    function loadAllTcards(){
	        console.log("Fetching all t cards");
	        fetchTheTcards(0)
	    }

	    function loadStudentTcard(){
	        console.log("Fetching student t card");
	        fetchTheTcards(el.student.student_id)
	    }

	    if(el.student == undefined){
	        loadAllTcards();
	    }else{
	        loadStudentTcard();
	    }
			$scope.cardsLoaded = true;
	}

	$scope.printTcards = function(){
		/*
		let body = document.getElementsByTagName("BODY")[0];
		body.style.visibility = 'hidden';
		document.getElementById('printCards').style.visibility = 'visible';
		*/
		$.getScript('/components/printThis.js', function()
		{
			$('#printCards').printThis({
				importCSS: true,
				importStyle: true,
				loadCSS: ["/min/css/dependencies.min.css","/css/template.css"],
				removeInline: false,
				printDelay: 1000,
				copyTagClasses: true,
				formValues: true
			});
		});
		// window.print();
	}

    $scope.selectRoutes = function()
	{
	    var currSelection = $('#multiRoute').val();
    	$scope.selectedRoutesJoined = currSelection.join();
	    $scope.selectedRoutes = currSelection;
	}

	$( "#theBusId" ).change(function() {
	    let busChange = $("#theBusId").val();
        apiService.getBusDestinations(busChange, function(response,status){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
					var thisBus = ( result.nodata ? null : result.data );
					$scope.selectedRoutes = (thisBus.destinations == null ? [] : thisBus.destinations.split(','));
			}
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);
    });

	$scope.trackBusRoute = function(){
	    console.log("Bus selection has changed");
	}

	$scope.viewStudentsInBus = function(el)
	{
	    console.log("Bus row clicked :: ",el);
	    var bus_id = el.item.bus_id;
	    var data = {
			bus_id: bus_id,
			bus_name: el.item.bus_type + ' - ' + el.item.bus_registration,
			route: el.item.route,
			trip_name: el.item.trip_name,
			driver_name: el.item.driver_name,
			guide_name: el.item.guide_name
		}
		$scope.openModal('transport', 'studentsInBus', 'lg', data);
	}

	var addBusSuccess = function ( response, status, params )
    	{

    		var result = angular.fromJson( response );
    		if( result.response == 'success' )
    		{

                $scope.busCreation = true;

                // we allow the success message to be visible only for a duration
                setTimeout(function(){ $scope.busCreation = false; }, 4000);
                $timeout(initializeController,1);

    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}
    	}

    // variable for showing or hiding bus creation success message
    $scope.busCreation = false;

    $scope.createBus = function()
	{

	    // acquire the input values
	    var busType = $( "#busType" ).val().toUpperCase();
	    var busRegistration = $( "#busReg" ).val().toUpperCase();
	    var busDescription = $( "#busDesc" ).val().toUpperCase();
	    var busCapacity = $( "#busCap" ).val().toUpperCase();

	    var busData = {
	        "bus_type": busType,
	        "bus_registration": busRegistration,
	        "bus_description": busDescription,
	        "bus_capacity": (busCapacity == "" || busCapacity == null || busCapacity == undefined ? null : parseInt(busCapacity))
	    };

		apiService.createSchoolBus(busData,addBusSuccess,apiError);
	}

	var assignSuccess = function ( response, status, params )
    	{

    		var result = angular.fromJson( response );
    		if( result.response == 'success' )
    		{

                $scope.routeAssignment = true;

                // we allow the success message to be visible only for a duration
                setTimeout(function(){ $scope.routeAssignment = false; }, 4000);
                $timeout(initializeController,1);

    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}
    	}

	// variable for showing or hiding bus-route assignment success message
    $scope.routeAssignment = false;

	$scope.assignRoute = function()
	{

	    // acquire the input values
	    var busId = $( "#theBusId" ).val();
	    var routeId = $( "#theRouteId" ).val();
	    var destinations = $scope.selectedRoutesJoined;

	    var assignData = {
	        "bus_id": parseInt(busId),
	        "route_id": parseInt(routeId),
	        "destinations": destinations
	    };
	    console.log(assignData);
	    apiService.assignBusToRoute(assignData,assignSuccess,apiError);
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

	$scope.addExamMarks = function()
	{
		var data = {
			classes: $scope.classes,
			terms: $scope.terms,
			examTypes: $scope.examTypes,
			filters: $scope.filters
		}
		$scope.openModal('exams', 'addExamMarks', 'lg', data);
	}

	var assignPersonnelSuccess = function ( response, status, params )
    	{

    		var result = angular.fromJson( response );
    		if( result.response == 'success' )
    		{

                $scope.assignedPersonnel = true;

                // we allow the success message to be visible only for a duration
                setTimeout(function(){ $scope.assignedPersonnel = false; }, 3000);
                $timeout(initializeController,1);

    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}
		}
		setTimeout(function(){ 
			// console.log($rootScope.currentTermTitle);
		$scope.currentTerm = "Term " + $rootScope.currentTermTitle;
		}, 3000);

	$scope.assignDriverAndGuide = function()
	{
		// acquire the input values
	    var selectedBus = $( "#routedBuses" ).val();
	    var selectedDriver = $( "#allDrivers" ).val();
	    var selectedAssistant = $( "#theAssistant" ).val();

	    var assignData = {
	        "bus_id": selectedBus,
	        "bus_driver": selectedDriver,
	        "bus_guide": selectedAssistant
	    };

		apiService.assignPersonnelToBus(assignData,assignPersonnelSuccess,apiError);
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
