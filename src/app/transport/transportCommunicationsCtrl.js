'use strict';

angular.module('eduwebApp').
controller('transportCommunicationsCtrl', ['$scope', '$rootScope', 'apiService', 'dialogs','$timeout','$window',
function($scope, $rootScope, apiService, $dialogs, $timeout, $window){

    var school = window.location.host.split('.')[0];
	  $scope.isTeacher = ($rootScope.currentUser.user_type == 'TEACHER' ? true : false);
    $scope.rawRoutes = [];
    $scope.transportAudience = [
      'All Students In Neighborhood(s)',
      'All Students In A Trip',
      'All Students In Neighborhood(s) In Trip',
      'Class Students In Neighborhood(s)',
      'Class Students In A Trip',
      'Class Students In Neighborhood(s) In Trip'
    ];
    $scope.msgCreation = false;
    $scope.postMsgStatus = false;

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
          $scope.transportZones = [];
        }
        else
        {
          $scope.transportZones = result.data;
          $scope.transportZones.forEach(function(routesArr) {
            var routeSplit = routesArr.route.split(',');
            routeSplit.forEach(function(rtName) {
              var s1 = rtName.substring(rtName.indexOf(")")+1);
              s1.trim();
              $scope.rawRoutes.push(s1);
            });
          });
          $scope.zones = [...new Set($scope.rawRoutes)];
        }
      }

    }, function(){console.log("There was an error fetching the transport routes.")});

	}
	setTimeout(initializeController,100);

  $scope.typeMessage = function (){
    // validate selections have been done
    console.log("Selected model = " + $scope.selectedAudience);

    if($scope.selectedAudience == undefined || $scope.selectedAudience == null){
      document.getElementById("slctdAudience").style.border = "2px solid red";
    }else{
      document.getElementById("slctdAudience").style.border = "1px solid #ccc";

      document.getElementById("smsCheck").removeAttribute("disabled");
      document.getElementById("appCheck").removeAttribute("disabled");
      document.getElementById("bothCheck").removeAttribute("disabled");
      document.getElementById("theMsg").removeAttribute("disabled");
      document.getElementById("postMsg").removeAttribute("disabled");
    }
  }

  $scope.busTrips = null;
  $scope.showMessageBox = function (el){
    // console.log(el);
    if(el.selectedAudience == 'All Students In Neighborhood(s)'){
      $scope.needsHood = true;
      $scope.needsClass = false;
      $scope.needsTrip = false;
      $scope.needsBus = false;
    }else if(el.selectedAudience == 'All Students In A Trip'){
      $scope.needsTrip = true;
      $scope.needsBus = true;
      $scope.needsHood = false;
      $scope.needsClass = false;
    }else if(el.selectedAudience == 'All Students In Neighborhood(s) In Trip'){
      $scope.needsHood = true;
      $scope.needsTrip = true;
      $scope.needsBus = true;
      $scope.needsClass = false;
    }else if(el.selectedAudience == 'Class Students In Neighborhood(s)'){
      $scope.needsClass = true;
      $scope.needsHood = true;
      $scope.needsTrip = false;
      $scope.needsBus = false;
    }else if(el.selectedAudience == 'Class Students In A Trip'){
      $scope.needsClass = true;
      $scope.needsTrip = true;
      $scope.needsBus = true;
      $scope.needsHood = false;
    }else if(el.selectedAudience == 'Class Students In Neighborhood(s) In Trip'){
      $scope.needsClass = true;
      $scope.needsHood = true;
      $scope.needsTrip = true;
      $scope.needsBus = true;
    }

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

  $scope.selectRoutes = function()
  {
      var currSelection = $('#multiRoute').val();
      $scope.selectedRoutesJoined = currSelection.join();
      $scope.selectedRoutes = currSelection;
      console.log("Selected routes",$scope.selectedRoutes);
  }

  $scope.postTransportMsg = function (){
    // acquire the active filters
    $scope.postFilters = {};
    if($scope.selectedAudience == 'All Students In Neighborhood(s)'){
      $scope.postFilters.neighborhoods = $scope.selectedRoutes.toString();
      scope.audience_id = 14;
    }else if($scope.selectedAudience == 'All Students In A Trip'){
      $scope.postFilters.schoolbus_trip_id = $scope.selectedTrip;
      $scope.postFilters.bus_id = $scope.selectedBus;
      scope.audience_id = 15;
    }else if($scope.selectedAudience == 'All Students In Neighborhood(s) In Trip'){
      $scope.postFilters.neighborhoods = $scope.selectedRoutes.toString();
      $scope.postFilters.schoolbus_trip_id = $scope.selectedTrip;
      $scope.postFilters.bus_id = $scope.selectedBus;
      scope.audience_id = 16;
    }else if($scope.selectedAudience == 'Class Students In Neighborhood(s)'){
      $scope.postFilters.neighborhoods = $scope.selectedRoutes.toString();
      $scope.postFilters.class_cat_id = $scope.selectedClass;
      scope.audience_id = 17;
    }else if($scope.selectedAudience == 'Class Students In A Trip'){
      $scope.postFilters.class_cat_id = $scope.selectedClass;
      $scope.postFilters.schoolbus_trip_id = $scope.selectedTrip;
      $scope.postFilters.bus_id = $scope.selectedBus;
      scope.audience_id = 18;
    }else if($scope.selectedAudience == 'Class Students In Neighborhood(s) In Trip'){
      $scope.postFilters.neighborhoods = $scope.selectedRoutes.toString();
      $scope.postFilters.class_cat_id = $scope.selectedClass;
      $scope.postFilters.schoolbus_trip_id = $scope.selectedTrip;
      $scope.postFilters.bus_id = $scope.selectedBus;
      $scope.audience_id = 19;
    }else if($scope.selectedAudience == undefined || $scope.selectedAudience == null){
      //show error message and prevent post
    }

    // post the message
    $scope.postObj = {
      post: {
        audience: $scope.selectedAudience,
        audience_id: $scope.audience_id,
        filters: $scope.postFilters,
        // msgType: $scope.selectedType,
        send_as_sms: ($scope.selectedType == "sms" || $scope.selectedType == "both" ? 't':'f'),
        send_as_email: ($scope.selectedType == "app" || $scope.selectedType == "both" ? 't':'f'),
        body: $scope.messageBody,
        title: $scope.messageBody.substring(0,50) + '...',
        attachment: null,
        com_type_id: 3,
        post_status_id: 1,
        message_from: $rootScope.currentUser.user_id,
        created_by: $rootScope.currentUser.user_id,
        reply_to: $rootScope.currentUser.settings["School Name"]
      },
      user_id: $rootScope.currentUser.user_id
    };
    console.log($scope.postObj);
    // after post, show status via postMsgStatus and postMsgStatusText
    // apiService.customAddCommunication($scope.postObj,createCompleted,apiError);

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
