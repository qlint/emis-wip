'use strict';

angular.module('eduwebApp').
controller('allHomeworkCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.allHomework = [];
	$scope.loading = true;
	$scope.selectionsReady = false;

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filters.emp_cat_id = ( $state.params.category !== '' ? $state.params.category : null );
	$scope.filterEmpCat = ( $state.params.category !== '' ? true : false );
	$scope.filters.dept_id = ( $state.params.dept !== '' ? $state.params.dept : null );
	$scope.filterDept = ( $state.params.dept !== '' ? true : false );

	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';

	/* get full employee cat record from state param */
	if( $state.params.category !== null )
	{
		// $scope.filters.emp_cat = $rootScope.empCats.filter(function(item){
		//	if( item.emp_cat_id == $state.params.category ) return item;
		// })[0];
	}

	$scope.alert = {};

	$scope.enableCheckboxTable = function(){
		    $scope.selectionsReady = true;
	}

	var rowTemplate = function()
	{
		return '<div class="clickable">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }" ng-click="grid.appScope.viewHomework(row)" data-target="#privilegesModal"  ui-grid-cell></div>' +
		'</div>';
	}

	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Class', field: 'class_name', enableColumnMenu: false,},
			{ name: 'Subject', field: 'subject_name', enableColumnMenu: false,},
			{ name: 'Title', field: 'title', enableColumnMenu: false,},
			{ name: 'Teacher', field: 'teacher_name', enableColumnMenu: false,},
			{ name: 'Assigned On', field: 'assigned_date', enableColumnMenu: false,},
			{ name: 'Due On', field: 'due_date', enableColumnMenu: false,},
			{ name: 'Created', field: 'creation_date', enableColumnMenu: false,},
			{ name: 'Status', field: 'status', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'homework.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};

	var getHomework = function()
	{
		apiService.getAllHomework({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				console.log("Homework data success",result.data);
				$scope.allHomework = result.data;
				initDataGrid($scope.allHomework);
			}else{
				initDataGrid($scope.allHomework);
			}

		},function(err){console.log(err);});
	}

	var initializeController = function ()
	{
		// get homework
		getHomework();

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);

	}
	$timeout(initializeController,1000);

	$scope.$watch('filters.emp_cat', function(newVal,oldVal){
		if (oldVal == newVal) return;

		if( newVal === undefined || newVal === null || newVal == '' ) 	$scope.departments = $rootScope.allDepts;
		else
		{
			// filter dept to only show those belonging to the selected category
			$scope.departments = $rootScope.allDepts.reduce(function(sum,item){
				if( item.category == newVal.emp_cat_name ) sum.push(item);
				return sum;
			}, []);
			$scope.filters.emp_cat_id = newVal.emp_cat_id;
			$timeout(setSearchBoxPosition,10);
		}
	});

	var initDataGrid = function(data)
	{
		$scope.gridOptions.data = data;
		$scope.loading = false;
		$rootScope.loading = false;

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
		  [ 'class_name', 'subject_name', 'title', 'teacher_name', 'creation_date', 'status' ].forEach(function( field ){
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

	$scope.filter = function()
	{
		$scope.currentFilters = angular.copy($scope.filters);
		console.log($scope.filters.status);

		apiService.getAllHomework({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				console.log("Homework data success",result.data);
				$scope.allHomework = result.data;
				initDataGrid($scope.allHomework);
			}else{
				initDataGrid($scope.allHomework);
			}

		},apiError);

		filterResults(true);
	}

	var filterResults = function(clearTable)
	{
		$scope.loading = true;

		// filter by emp category
		var filteredResults = $scope.allEmployees;


		if( $scope.filters.emp_cat_id !== undefined && $scope.filters.emp_cat_id !== null && $scope.filters.emp_cat_id != ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.emp_cat_id.toString() == $scope.filters.emp_cat_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}


		if( $scope.filters.dept_id !== undefined && $scope.filters.dept_id !== null && $scope.filters.dept_id != '' )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.dept_id.toString() == $scope.filters.dept_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}

		$scope.allHomework = filteredResults;
		initDataGrid($scope.allHomework);
	}

	$scope.viewHomework = function(row)
	{
		console.log(row.entity);
		$scope.forReply = row.entity;
		$scope.openedHomeworkTitle = row.entity.title;
		$scope.openedHomeworkTeacher = row.entity.teacher_name;
		$scope.openedHomeworkClass = row.entity.class_name;
		$scope.openedHomeworkCreationDate = row.entity.creation_date;
		$scope.openedHomeworkSubjectName = row.entity.subject_name;
		$scope.openedHomeworkBody = row.entity.body;
		document.getElementById('hmwkBody').innerHTML = $scope.openedHomeworkBody;
		$scope.openedHomeworkStudents = row.entity.students;
		$scope.openedHomeworkAttachment = row.entity.attachment;

		var school = window.location.host.split('.')[0];
		$scope.openedHomeworkLink = 'https://' + school + '.eduweb.co.ke/assets/posts/' + $scope.openedHomeworkAttachment;
		$scope.openedHomeworkLink = (row.entity.attachment == null || row.entity.attachment == "" || row.entity.attachment == " " ? 'NONE' : $scope.openedHomeworkLink);
		$scope.showAttachmentLink = ($scope.openedHomeworkLink == 'NONE' ? false : true);
		$scope.openedHomeworkCreationDate = row.entity.creation_date;
		$scope.openedHomeworkAssignedDate = row.entity.assigned_date;
		$scope.openedHomeworkDueDate = row.entity.due_date;

		// Get the modal
		var modal = document.getElementById("resourceModal");

		// Get the button that opens the modal
		var btn = document.getElementById("myBtn");

		// Get the <span> element that closes the modal
		var span = document.getElementsByClassName("closemdl")[0];
		modal.style.display = "block";

		// When the user clicks on <span> (x), close the modal
		span.onclick = function() {
		  modal.style.display = "none";
			$scope.openedHomeworkTitle = null;
			$scope.openedHomeworkTeacher = null;
			$scope.openedHomeworkClass = null;
			$scope.openedResourceTerm = null;
			$scope.openedResourceType = null;
			$scope.openedHomeworkAttachment = null;
			$scope.openedHomeworkLink = null;
			$scope.openedHomeworkCreationDate = null;
		}

		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
		  if (event.target == modal) {
		    modal.style.display = "none";
				$scope.openedHomeworkTitle = null;
				$scope.openedHomeworkTeacher = null;
				$scope.openedHomeworkClass = null;
				$scope.openedResourceTerm = null;
				$scope.openedResourceType = null;
				$scope.openedHomeworkAttachment = null;
				$scope.openedHomeworkLink = null;
				$scope.openedHomeworkCreationDate = null;
		  }
		}

		function initModal(){

			function classReg( className ) {
			  return new RegExp("(^|\\s+)" + className + "(\\s+|$)");
			}

			// classList support for class management
			// altho to be fair, the api sucks because it won't accept multiple classes at once
			var hasClass, addClass, removeClass;

			if ( 'classList' in document.documentElement ) {
			  hasClass = function( elem, c ) { return elem.classList.contains( c ); };
			  addClass = function( elem, c ) { elem.classList.add( c ); };
			  removeClass = function( elem, c ) { elem.classList.remove( c ); };
			}
			else {
			  hasClass = function( elem, c ) { return classReg( c ).test( elem.className ); };
			  addClass = function( elem, c ) { if ( !hasClass( elem, c ) ) { elem.className = elem.className + ' ' + c; } };
			  removeClass = function( elem, c ) { elem.className = elem.className.replace( classReg( c ), ' ' ); };
			}

			function toggleClass( elem, c ) { var fn = hasClass( elem, c ) ? removeClass : addClass; fn( elem, c ); }

			var classie = {
			  // full names
			  hasClass: hasClass,
			  addClass: addClass,
			  removeClass: removeClass,
			  toggleClass: toggleClass,
			  // short names
			  has: hasClass,
			  add: addClass,
			  remove: removeClass,
			  toggle: toggleClass
			};

			// transport
			if ( typeof define === 'function' && define.amd ) { define( classie ); } else { window.classie = classie; }

			}
			initModal();

			var ModalEffects = (function() {
				function init() {
					var overlay = document.querySelector( '.md-overlay' );
					[].slice.call( document.querySelectorAll( '.md-trigger' ) ).forEach( function( el, i ) {
						var modal = document.querySelector( '#' + el.getAttribute( 'data-modal' ) ),
							close = modal.querySelector( '.md-close' );
						function removeModal( hasPerspective ) { classie.remove( modal, 'md-show' ); if( hasPerspective ) { classie.remove( document.documentElement, 'md-perspective' ); } }
						function removeModalHandler() { removeModal( classie.has( el, 'md-setperspective' ) ); }
						el.addEventListener( 'click', function( ev ) {
							classie.add( modal, 'md-show' );
							overlay.removeEventListener( 'click', removeModalHandler );
							overlay.addEventListener( 'click', removeModalHandler );
							if( classie.has( el, 'md-setperspective' ) ) { setTimeout( function() { classie.add( document.documentElement, 'md-perspective' ); }, 25 ); }
						});
						close.addEventListener( 'click', function( ev ) { ev.stopPropagation(); removeModalHandler(); });
					} );
				}
				init();
			})();

			var inputs = document.querySelectorAll('.file-input');

			for (var i = 0, len = inputs.length; i < len; i++) {
			  customInput(inputs[i])
			}

			function customInput (el) {
			  const fileInput = el.querySelector('[type="file"]')
			  const label = el.querySelector('[data-js-label]')

			  fileInput.onchange =
			  fileInput.onmouseout = function () {
			    if (!fileInput.value) return

			    var value = fileInput.value.replace(/^.*[\\\/]/, '')
			    el.className += ' -chosen'
			    label.innerText = value
			  }
			}
	}

	$scope.replyToHmwkFeedback = function(){
		console.log("Prepare reply",$scope.forReply);
		let audience = document.getElementById('reply_to').value;
		let postData = {
			post : {
									title: document.getElementById('reply_subject').value,
									body: document.getElementById('reply_msg').value,
									audience_id: (audience == 'to_student' ? 5 : 2), // 5=parent, 2=class specific
									com_type_id: 1, // 1=general
									emp_id: $rootScope.currentUser.emp_id,
									class_id: (audience == 'to_class' ? $scope.forReply.class_id : null),
									guardian_id: (audience == 'to_student' ? $scope.forReply.guardian_id : null),
									student_id: (audience == 'to_student' ? $scope.forReply.student_id : null),
									send_as_email: 't',
									send_as_sms: 'f',
									reply_to: $rootScope.currentUser.settings["Email From"],
									post_status_id: 1, // 1=published, 0=draft
									message_from: $rootScope.currentUser.emp_id,
									sent: true,
									user_id: $rootScope.currentUser.user_id,
									subdomain: window.location.host.split('.')[0]
							},
			 user_id : $rootScope.currentUser.user_id
		}

		apiService.customAddCommunication(postData,function ( response, status, params )
																										{
																													console.log(response);
																											var result = angular.fromJson( response );
																											if( result.response == 'success' )
																											{
																															alert("Your message has been successfully sent.");
																															$.ajax({
																																	type: "POST",
																																	url: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/srvScripts/postNotifications.php",
																																	data: { school: window.location.host.split('.')[0] },
																																	success: function (data, status, jqXHR) {
																																			console.log("Notifications initiated.",data,status,jqXHR);
																																	},
																																	error: function (xhr) {
																																			console.log("Error. Notifications could not be sent.");
																																	}
																															});
																											}
																											else
																											{
																												console.log(result);
																											}
																										},function(e){console.log(e);});
	}

	$scope.$on('refreshStaff', function(event, args) {

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
		getHomework();
	}

	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });


} ]);
