'use strict';

angular.module('eduwebApp').
controller('schoolBusSettingsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
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
	//$scope.loading = true;

	var initializeController = function ()
	{
		// get classes
		var requests = [];

		var deferred = $q.defer();
		requests.push(deferred.promise);

		// get terms
		var deferred2 = $q.defer();
		requests.push(deferred2.promise);

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

		var loadSharedRoutes = function(response)
    	{
    		var result = angular.fromJson(response);

    		if( result.response == 'success')
    		{
    			$scope.sharedRoutes = ( result.nodata ? {} : result.data );
					$scope.sharedRoutes.forEach(function(eachRoute) {
					  eachRoute.buses = JSON.parse(eachRoute.buses.replace('{','[').replace(/.$/,"]"));
						var parsedBuses = [];
						eachRoute.buses.forEach(function(eachBus) {
							var parsedBus = JSON.parse(eachBus);
							parsedBuses.push(parsedBus);
						});
						eachRoute.buses = parsedBuses;
					});
    		}
    		else
    		{
    			$scope.error = true;
    			$scope.errMsg = result.data;
    		}

    	}

		// fetch buses and shared routes
		apiService.getSchoolBusRouteSharing(true, loadSharedRoutes, apiError);

	}
	$timeout(initializeController,1);

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
	
	$scope.assignStudentToBus = function(el)
	{
        var data = {
			buses: el.item
		}
		$scope.openModal('transport', 'studentBusAssign', 'lg', data);
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

	$scope.$on('refreshExamMarks', function(event, args) {

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
