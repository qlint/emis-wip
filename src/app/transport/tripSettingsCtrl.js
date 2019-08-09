'use strict';

angular.module('eduwebApp').
controller('tripSettingsCtrl', ['$scope', '$rootScope', 'apiService', 'dialogs','$timeout','$window',
function($scope, $rootScope, apiService, $dialogs, $timeout, $window){

    var school = window.location.host.split('.')[0];
	  $scope.isTeacher = ($rootScope.currentUser.user_type == 'TEACHER' ? true : false);
    $scope.rawRoutes = [];
    $scope.allTransportData = [];
    $scope.tripCreation = false;
    $scope.tripAssignment = false;
    $scope.busAssignment = false;
    $scope.selectedRoutes = null;
    $scope.changedTrip = false;

    var ccParams = ( $rootScope.currentUser.user_type == 'TEACHER' ? $rootScope.currentUser.emp_id : undefined);
  	apiService.getClassCats(ccParams, function(response){
  		var result = angular.fromJson(response);
          if( result.response == 'success')	$scope.classCats = result.data;

  	}, function(){});

    $("#multiRoute").mousedown(function(e){
      e.preventDefault();

  		var select = this;
      var scroll = select.scrollTop;

      e.target.selected = !e.target.selected;

      setTimeout(function(){select.scrollTop = scroll;}, 0);

      $(select).focus();
  }).mousemove(function(e){e.preventDefault()});

	var initializeController = function()
	{
    // get all active buses
		var getBusesParam = true;
		apiService.getAllBuses(getBusesParam, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
					$scope.buses = ( result.nodata ? [] : result.data );

			}
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);

    //get all existing trips
    apiService.getAllSchoolBusTrips({}, function(response){
      var result = angular.fromJson(response);
      if( result.response == 'success')
      {
        if( result.nodata !== undefined)
        {
          $scope.trips = [];
        }
        else
        {
          $scope.trips = result.data;
          console.log($scope.trips);
        }
      }

    }, function(){console.log("There was an error fetching the existing trips.")});

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
              var s1 = rtName.substring(rtName.indexOf(")")+1);
              s1.trim();
              $scope.rawRoutes.push(s1);
            });
          });
          $scope.routes = [...new Set($scope.rawRoutes)];
        }
      }

    }, function(){console.log("There was an error fetching the transport routes.")});

    // get all data for schoolbuses
    apiService.getAllBusesRoutesAndDrivers(true, function(response){
      var result = angular.fromJson(response);
      if( result.response == 'success')
      {
        $scope.transportDataTable = result.data;
        $scope.allTransportData = $scope.transportDataTable;
      }

    }, apiError);

	}
	setTimeout(initializeController,100);

  $scope.createTrip = function (){

    var selectedCats = [];
    $.each($("input[name='class_cat[]']:checked"), function(){
                selectedCats.push($(this).val());
    });
    var categories = selectedCats.join();

    //post
    var trpName = document.getElementById("tripName").value;
    console.log("Trip name = " + trpName);
    var createTrip = {
        "trip_name": trpName,
        "class_cats": categories
    };

    apiService.createSchoolBusTrip(createTrip,function(response,status){
          // var result = angular.fromJson(response);
          $scope.tripCreation = true;
          $scope.tripCreationStatus = "Trip created successfully!";
          setTimeout(initializeController,2500);

      },apiError);
  }

  $scope.busTrips = null;
  $scope.showTrips = function (el){
    let busTrip = parseInt(el.busTrips);
    console.log("This bus id = " + busTrip);

    apiService.getBusTrips(busTrip, function(response){
      var result = angular.fromJson(response);
      if( result.response == 'success')
      {
        if( result.nodata !== undefined)
        {
          $scope.thisBusTrips = [];
        }
        else
        {
          $scope.thisBusTrips = result.data;
          if($scope.thisBusTrips == null || $scope.thisBusTrips == undefined){
            $scope.thisBusTrips = [];
            $scope.changedTrip = false;
          }else{
            $scope.changedTrip = true;
          }
          console.log($scope.thisBusTrips);
        }
      }

    }, function(){console.log("There was an error fetching the existing trips.")});
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

  $scope.updateTrip = function (){
    var theTrip = document.getElementById("slctdTrip").value;
    var theRoutes = $("#multiRoute").val().join(',');

    var updateTrip = {
        "trip_id": theTrip,
        "routes": theRoutes,
        "delete": "NO"
    };
    apiService.updateSchoolBusTrip(updateTrip,function(response,status){
          // var result = angular.fromJson(response);
          $scope.tripAssignment = true;
          $scope.tripRouteAssignmentStatus = "Success! Routes assigned to trip.";
          setTimeout(initializeController,2500);

      },apiError);

  }

  $scope.updateTrip2 = function (){
    var theTrip = document.getElementById("slctdTrip2").value;
    var theBus = document.getElementById("slctdBus").value;

    var updateTrip2 = {
        "trip_id": parseInt(theTrip),
        "bus_id": parseInt(theBus),
        "delete": "NO"
    };
    apiService.updateSchoolBusTrip(updateTrip2,function(response,status){
          // var result = angular.fromJson(response);
          $scope.busAssignment = true;
          $scope.busAssignmentStatus = "Success! Bus assigned to trip.";
          setTimeout(initializeController,2500);

      },apiError);

  }

  $scope.deleteBusTrip = function (el){
    console.log(el);
    var deleteTrip = {
        "bus_trip_id": parseInt(el),
        "delete": "YES"
    };
    apiService.updateSchoolBusTrip(deleteTrip,function(response,status){
          setTimeout(initializeController,1000);
          $scope.changedTrip = false;
      },apiError);
  }

  $('#slctdTrip').on('change', function() {
    var tripId = this.value;
    apiService.getSchoolBusTrips(tripId, function(response){
      var result = angular.fromJson(response);
      if( result.response == 'success')
      {
        if( result.nodata !== undefined)
        {
          var thisTrip = [];
        }
        else
        {
          var thisTrip = result.data;
          console.log(thisTrip);
          $scope.selectedRoutes = (thisTrip.trip_routes != null || thisTrip.trip_routes != undefined ? thisTrip.trip_routes.split(',') : []);
        }
      }

    }, function(){console.log("There was an error fetching the existing trips.")});

  });


	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
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

} ]);
