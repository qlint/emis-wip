'use strict';

angular.module('eduwebApp').
controller('createClassTimetableCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
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
	$scope.getReport = "examsTable";
	$scope.setListener = false;
	$scope.showSave = false;
	$scope.ttDay = null;
	$scope.ttSubject = null;
	$scope.ttStartTime = null;
	$scope.ttEndTime = null;
	// our array that will be used to post to DB
	$scope.saveClassTimetable = [];
	$scope.colorCodes = [
		'color1','color2', 'color3', 'color4', 'color5', 'color6', 'color7', 'color8', 'color9', 'color10'
	];

	$scope.loadSubjects = function(){
		apiService.getAllClassSubjects($scope.filters.class.class_id, function(response,status){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.subjects = result.data;
				for(let x=0;x < $scope.subjects.length;x++){
					$scope.subjects[x].color = $scope.colorCodes[x];
				}
				return $scope.subjects;
			}

		},
		function (response, status){console.log("There's a problem getting the subjects.");}
		);
	}

	function addTtListener (){
		$scope.setListener = true;
		var userSelection = document.getElementsByClassName('time-entry');

		for(var i = 0; i < userSelection.length; i++) {
		(function(index) {
			userSelection[index].addEventListener("click", function() {
				console.log("Clicked index: " + index);
				// need to make the element draggable to make it easy to edit
			});
			let isDown = false; // mouse down
			userSelection[index].addEventListener('mousedown', function(e) {
				isDown = true;
				console.log("The mouse is down");
			}, true);

			document.addEventListener('mouseup', function() {
				isDown = false;
				console.log("Mouse down release. NOW UP");
			}, true);

			document.addEventListener('mousemove', function(event) {
				event.preventDefault();
				if (isDown) {
					console.log("Mouse is down and moving",event);
					var deltaX = event.movementX;
					// var deltaY = event.movementY; // we will only allow x-axis adjustments
					var rect = userSelection[index].getBoundingClientRect();
					console.log("The recorded movement",event.movementX);
					console.log("Current rect position",rect);
					userSelection[index].style.left = rect.x + deltaX + 'px';
				}
			}, true);
		})(i);
		}
	}

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


		/* taking the out, going to need user to choose exam then click load
		// need to wait for three data pieces, then run this
		$q.all(requests).then(function () {
			//if( $scope.filters.class_id !== null ) $scope.getStudentExams();
		});
		*/
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
	var ttView = document.getElementsByClassName("ttView")[0];
	ttView.style.display = "none";

	$scope.setUpTt = function(){
	    //we create an object to store the selected parameters
	    $scope.selectedClassTt = {
	        class_id: $scope.filters.class_id,
	        term_id: $scope.filters.term_id
	    };
			$scope.loadSubjects();
	    ttView.style.display = "block";
	}

	var entryCount = 0;

	// initialize the timetable
	var timetable = new Timetable();
	timetable.setScope(7,18); // limit the time range to between 7am and 6pm
    // timetable.useTwelveHour();
    timetable.addLocations(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);

    $scope.getClassTimetableParams = function()
	{
		console.log("ENTERED SELECTION >");
		console.log("Day",$scope.ttDay);
		console.log("Subject",$scope.ttSubject);
		console.log("Start Time",$scope.ttStartTime);
		console.log("End Time",$scope.ttEndTime);
		// our array that will hold data to draw the timetable
		$scope.timetableData = [];

		// we get the values of elements at the time of submit
		var weekday = document.getElementById("weekday").value;
		var ttSubject_id = document.getElementById("subject").value;
		var startTime = document.getElementById("startTime").value;
		var endTime = document.getElementById("endTime").value;

		var day = weekday.charAt(0).toUpperCase() + weekday.slice(1);

		var subjName = $("#subject option:selected").text();
		$scope.subjColor = null;
		for(let y=0;y < $scope.subjects.length;y++){
			if($scope.subjects[y].subject_name == subjName){
				console.log("The color will be " + $scope.subjects[y].color);
				$scope.subjColor =  $scope.subjects[y].color;
			}
		}

		var timeframeObj = {
		    class_id: $scope.filters.class.class_id,
		    term_id: $scope.filters.term_id,
		    timetable: {
		        day: day,
		        subject_id: ttSubject_id,
		        subject: subjName,
		        start_time: startTime,
		        end_time: endTime
		    }
		}
		// console.log(timeframeObj);

	    // push the created object into an array that will contain all timetable data
	    $scope.timetableData.push(timeframeObj);

		let subjectObj = {
			class_id: $scope.filters.class_id,
			term_id: $scope.filters.term_id,
			subject_id: $scope.ttSubject,
			subject_name: subjName,
			year: new Date().getFullYear(),
			month: new Date().getMonth() + 1,
			day: day,
			start_hour: startTime.split(':')[0],
			start_minutes: startTime.split(':').reverse()[0],
			end_hour: endTime.split(':')[0],
			end_minutes: endTime.split(':').reverse()[0],
			color: $scope.subjColor
		}

		console.log(subjectObj);
		$scope.saveClassTimetable.push(subjectObj);

        var a;
        for (a = 0; a < $scope.timetableData.length; a++) {
            var startHour = subjectObj.start_hour;
            var startMinutes = subjectObj.start_minutes;
            var endHour = subjectObj.end_hour;
			var endMinutes = subjectObj.end_minutes;
			var options = {class: subjectObj.color};
			timetable.addEvent(subjectObj.subject_name, subjectObj.day, new Date(2020,7,17,startHour,startMinutes), new Date(2020,7,17,endHour,endMinutes),{url: '#', class: subjectObj.color});

			setTimeout(function(){
				var ttItems = document.getElementsByClassName("time-entry");
				for(var i = 0; i < ttItems.length; i++)
				{
					let title = ttItems[i].getAttribute("title");
					console.log(title);
					for(let z=0;z < $scope.subjects.length;z++){
						if($scope.subjects[z].subject_name == title){
							console.log("The color will be " + $scope.subjects[z].color);
							// add class to change color
							ttItems[i].classList.add($scope.subjects[z].color);
						}
					}
				}
			}, 2000);

            var renderer = new Timetable.Renderer(timetable);
            renderer.draw('.timetable');
        }

      // increment our counter
      entryCount++;

	  // add an event listener to allow element to be dragged (easier editing)
	  // addTtListener();

	  $scope.showSave = true;
	}

	$scope.saveTimetable = function(){
		console.log("Timetable array to save",$scope.saveClassTimetable);
		// save to db
		var createCompleted = function ( response, status, params ){
			var result = angular.fromJson( response );
			if( result.response == 'success' ){
				// let user know saving was a success
				// console.log(result.message);
				alert("Timetable has been successfully saved. To view and/or download it, click on the 'Class Timetable' tab.");
			}
			else{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		}
		var data = {
			time_tables: $scope.saveClassTimetable
		}
		apiService.addClassTimetable(data,createCompleted,apiError);
	}

	var setSearchBoxPosition = function()
	{
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+55);
		}
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

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}


} ]);
