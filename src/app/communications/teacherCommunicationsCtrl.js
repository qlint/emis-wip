'use strict';

angular.module('eduwebApp').
controller('teacherCommunicationsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.allTeacherComms = [];
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
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }" ng-click="grid.appScope.viewTeacherComm(row)" data-target="#privilegesModal"  ui-grid-cell></div>' +
		'</div>';
	}

	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Teacher', field: 'teacher_name', enableColumnMenu: false,},
			{ name: 'Audience', field: 'audience', enableColumnMenu: false,},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false,},
			{ name: 'Parent', field: 'parent_name', enableColumnMenu: false,},
			{ name: 'Reference', field: 'subject', enableColumnMenu: false,},
			{ name: 'Type', field: 'comm_mode', enableColumnMenu: false,},
			{ name: 'Date', field: 'creation_date', enableColumnMenu: false,},
			{ name: 'Seen Count', field: 'seen_count', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'teacher_communications.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};

	var getAllTeacherComms = function()
	{
		if( $rootScope.currentUser.user_type == 'TEACHER' ){ var params = "/" + $rootScope.currentUser.emp_id; }
		else{ params += "/0"; }
		apiService.getAllTeacherComms(params, function(response,status){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.allTeacherComms = ( result.nodata ? [] : result.data );
				initDataGrid($scope.allTeacherComms);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				console.log($scope.errMsg);
			}
		}, function(response,status){console.log(response,status);});
	}

	var initializeController = function ()
	{
		// get homework
		getAllTeacherComms();

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);

	}
	$timeout(initializeController,1000);

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
		  [ 'teacher_name', 'audience', 'subject', 'comm_mode', 'creation_date' ].forEach(function( field ){
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

		if( $rootScope.currentUser.user_type == 'TEACHER' ){ params = "/" + $rootScope.currentUser.emp_id; }
		else{ params = "/0";}
		apiService.getAllTeacherComms(params, function(response,status){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.allTeacherComms = ( result.nodata ? [] : result.data );
				initDataGrid($scope.allTeacherComms);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				console.log($scope.errMsg);
			}
		}, function(response,status){console.log(response,status);});

		filterResults(true);
	}

	var filterResults = function(clearTable)
	{
		$scope.loading = true;
		initDataGrid($scope.allTeacherComms);
	}

	$scope.viewTeacherComm = function(row)
	{
		console.log(row.entity);
		$scope.forReply = row.entity;
		$scope.openedCommTitle = row.entity.subject;
		$scope.openedCommTeacher = row.entity.teacher_name;
		$scope.openedCommClass = row.entity.class_name;
		$scope.openedCommAudience = row.entity.audience;
		$scope.openedCommMode = row.entity.comm_mode;
		$scope.openedCommCount = row.entity.seen_count;
		$scope.openedCommCreationDate = row.entity.creation_date;
		$scope.openedCommBody = row.entity.message;
		$scope.openedCommParentName = row.entity.parent_name;
		$scope.openedCommStudentName = row.entity.student_name;
		$scope.openedCommType = row.entity.com_type;
		$scope.openedCommSpecific = ($scope.openedCommClass != null ? $scope.openedCommClass : ($scope.openedCommParentName != null ? $scope.openedCommParentName : $scope.openedCommAudience));
		document.getElementById('hmwkBody').innerHTML = $scope.openedCommTitle;
		document.getElementById('hmwkMsg').innerHTML = $scope.openedCommBody;

		if(row.entity.attachment == null || row.entity.attachment == '' || row.entity.attachment == ' '){
			$scope.openedCommAttachment = null;
		}else{
			$scope.openedCommAttachment = row.entity.attachment.split(',');
		}

		var school = window.location.host.split('.')[0];
		$scope.openedCommLink = [];
		if($scope.openedCommAttachment != null){
			for(let x=0;x < $scope.openedCommAttachment.length;x++){
				$scope.openedCommLink.push('https://' + school + '.eduweb.co.ke/assets/posts/' + $scope.openedCommAttachment[x]);
			}
		}

		$scope.showAttachmentLink = (row.entity.attachment == null ? false : true);
		$scope.openedCommCreationDate = row.entity.creation_date;

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
			$scope.forReply = null;
			$scope.openedCommTitle = null;
			$scope.openedCommTeacher = null;
			$scope.openedCommClass = null;
			$scope.openedResourceTerm = null;
			$scope.openedResourceType = null;
			$scope.openedCommAttachment = null;
			$scope.openedCommLink = null;
			$scope.openedCommCreationDate = null;
		}

		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
		  if (event.target == modal) {
		    modal.style.display = "none";
				$scope.forReply = null;
				$scope.openedCommTitle = null;
				$scope.openedCommTeacher = null;
				$scope.openedCommClass = null;
				$scope.openedResourceTerm = null;
				$scope.openedResourceType = null;
				$scope.openedCommAttachment = null;
				$scope.openedCommLink = null;
				$scope.openedCommCreationDate = null;
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
																															$scope.closeModals();
																											}
																											else
																											{
																												alert("There seems to be a problem replying to this homework feedback. Please try again or let us know about this problem.");
																												console.log(result);
																											}
																										},function(e){console.log(e)});
	}

	$scope.closeModals = function(){
		var domain = window.location.host;
		window.open('https://' + domain + '/#/communications/blog/homework_feedback');
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
		getAllTeacherComms();
	}

	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });


} ]);
