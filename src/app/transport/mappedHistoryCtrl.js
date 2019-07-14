'use strict';

angular.module('eduwebApp').
controller('mappedHistoryCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state','dialogs',
function($scope, $rootScope, apiService, $timeout, $window, $state, $dialogs){

  var initialLoad = true;
  $scope.filters = {};
  $scope.filters.status = 'true';
  $scope.filters.activity = 'pickup';
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
      // future use
    }
    else
    {
      // future use
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
    
    // get transport routes
	apiService.getTansportRoutes({}, function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.transportRoutes = result.data;
			}

	}, function(){});
	
	// get school buses
	apiService.getBusesWithPickDropHistory(true, function(response,status){
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
		
    var studentTypes = $rootScope.currentUser.settings['Student Types'];
		$scope.studentTypes = studentTypes.split(',');

  }
  $timeout(initializeController,1);
  

  var getStudents = function(status, filtering)
  {
    $scope.activeFilters = angular.copy($scope.filters);


  }


  var apiError = function (response, status)
  {
    var result = angular.fromJson( response );
    $scope.error = true;
    $scope.errMsg = result.data;
  }

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
    
    isFiltered = true;
    
    // make a copy of the current active filters
    $scope.activeFilters = angular.copy($scope.filters);
    
    var busParam = document.getElementById("bus").value; 
    var activityParam = document.getElementById("activity").value;
    var dateParam = document.getElementById("date").value;
    
    var dateArr = dateParam.split('-');
    
    // busParam=bus_id, activityParam='pickup' or 'dropoff',  date[0]='year', date[1]='month', date[2]='day'
    var historyParam = busParam + "/" + activityParam + "/" + dateArr[0] + "/" + dateArr[1] + "/" + dateArr[2];
    console.log("Filters Button Clicked",historyParam);
    
    // Load selected bus gps data
    // get school buses
	apiService.getBusPickUpDropOffHistory(historyParam, function(response,status){
			var result = angular.fromJson(response);
				
			if( result.response == 'success')
			{	
					$scope.busHistory = ( result.nodata ? [] : result.data );
					console.log("Success, gps data fetched",$scope.busHistory);
					
					$scope.gpsCoords = [];
					$scope.theStudents = [];
					
					$scope.busHistory.forEach(function(element) {
					    
					    var convertGpsToNum = element.gps.split(",");
					    var splitGps = [Number(convertGpsToNum[0]),Number(convertGpsToNum[1])];
					    $scope.gpsCoords.push(splitGps);
					    
					    var perStudent = [element.student_name, element.gps_order, element.activity]
					    $scope.theStudents.push(perStudent);
                    });
                    
					// coords arr
                    var receivedCoords = $scope.gpsCoords;
                
                    var ltlng = [];
                
                    //we push the received coords to a new array -> ltlng
                    receivedCoords.forEach(function(eachCoord) {
                        // console.log(eachCoord);
                        ltlng.push(new google.maps.LatLng(eachCoord[0],eachCoord[1]));
                    });
                
                    //
                    // console.log(ltlng);
                
                    var map, marker;
                    var startPos = ltlng[0];
                    var image = '1.png';
                
                    var delay = 100;
                          // If you set the delay below 1000ms and you go to another tab,
                          // the setTimeout function will wait to be the active tab again
                          // before running the code.
                          // See documentation :
                          // https://developer.mozilla.org/en-US/docs/Web/API/WindowTimers/setTimeout#Inactive_tabs
                
                    function initialize()
                    {
                            var myOptions = {
                                zoom: 16,
                                center: ltlng[0],
                                mapTypeId: google.maps.MapTypeId.ROADMAP
                            };
                            map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
                
                            //we now place a marker on each coord
                            for (var i = 0; i < ltlng.length; i++) {
                                var marker = new google.maps.Marker
                                    (
                                    {
                                        // position: new google.maps.LatLng(-34.397, 150.644),
                                        position: ltlng[i],
                                        map: map,
                                        title: $scope.theStudents[i][0] + " was the " + $scope.theStudents[i][1] + "th on " + $scope.theStudents[i][2],
                                        icon: image
                                    }
                                    );
                            }
                
                            //Intialize the Path Array
                            var path = new google.maps.MVCArray();
                
                            //Intialize the Direction Service
                            var service = new google.maps.DirectionsService();
                
                            var flightPath = new google.maps.Polyline({
                              path: ltlng,
                              geodesic: true,
                              strokeColor: '#00ff00',
                              strokeOpacity: 1.0,
                              strokeWeight: 2
                            });
                
                            flightPath.setMap(map);
                        }
                
                        initialize();
                        
					/*
					// map begin
                        $(window).load(function(){
                          //these coords come from the API
                          var receivedCoords = [
                            [-1.300184, 36.776811],
                            [-1.299840, 36.779386],
                            [-1.298897, 36.779407],
                            [-1.299004, 36.777841],
                            [-1.298982, 36.776811],
                            [-1.297459, 36.776747],
                            [-1.296193, 36.776726],
                            [-1.296097, 36.779236],
                            [-1.296151, 36.777637],
                            [-1.296215, 36.776693],
                            [-1.294252, 36.776586],
                            [-1.294048, 36.776790],
                            [-1.293973, 36.779118],
                            [-1.292622, 36.779075],
                            [-1.291844, 36.779049],
                            [-1.291879, 36.778389],
                            [-1.291844, 36.779049],
                            [-1.292622, 36.779075],
                            [-1.293973, 36.779118],
                            [-1.294048, 36.776790],
                            [-1.294252, 36.776586],
                            [-1.296215, 36.776693],
                            [-1.296151, 36.777637],
                            [-1.296097, 36.779236],
                            [-1.296193, 36.776726],
                            [-1.297459, 36.776747],
                            [-1.298982, 36.776811],
                            [-1.299004, 36.777841],
                            [-1.298897, 36.779407],
                            [-1.299840, 36.779386]
                          ];
                
                          var ltlng = [];
                
                          //we push the received coords to a new array -> ltlng
                          receivedCoords.forEach(function(eachCoord) {
                            // console.log(eachCoord);
                            ltlng.push(new google.maps.LatLng(eachCoord[0],eachCoord[1]));
                          });
                
                          //just a test
                          // console.log(ltlng);
                
                          var map, marker;
                          var startPos = ltlng[0];
                          var image = '1.png';
                
                          var delay = 100;
                          // If you set the delay below 1000ms and you go to another tab,
                          // the setTimeout function will wait to be the active tab again
                          // before running the code.
                          // See documentation :
                          // https://developer.mozilla.org/en-US/docs/Web/API/WindowTimers/setTimeout#Inactive_tabs
                
                        function initialize()
                        {
                            var myOptions = {
                                zoom: 16,
                                center: ltlng[0],
                                mapTypeId: google.maps.MapTypeId.ROADMAP
                            };
                            map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
                
                            //we now place a marker on each coord
                            for (var i = 0; i < ltlng.length; i++) {
                                var marker = new google.maps.Marker
                                    (
                                    {
                                        // position: new google.maps.LatLng(-34.397, 150.644),
                                        position: ltlng[i],
                                        map: map,
                                        title: 'Student_' + i,
                                        icon: image
                                    }
                                    );
                            }
                
                            //Intialize the Path Array
                            var path = new google.maps.MVCArray();
                
                            //Intialize the Direction Service
                            var service = new google.maps.DirectionsService();
                
                            var flightPath = new google.maps.Polyline({
                              path: ltlng,
                              geodesic: true,
                              strokeColor: '#00ff00',
                              strokeOpacity: 1.0,
                              strokeWeight: 2
                            });
                
                            flightPath.setMap(map);
                        }
                
                        initialize();
                        });//]]>
                    // map end
                    */
			}       
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}
				
	}, apiError);
    
  }

  $scope.importStudents = function()
  {
    $rootScope.wipNotice();
  }

  $scope.exportData = function()
  {
    $scope.gridApi.exporter.csvExport( 'visible', 'visible' );
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