'use strict';

angular.module('eduwebApp').
controller('subjectsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter',
function($scope, $rootScope, apiService, $timeout, $window, $filter){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.alert = {};
	$scope.loading = true;

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );

	var initializeController = function ()
	{
		// get class categories
		if( $rootScope.classCats === undefined )
		{
			var params = ( $rootScope.currentUser.user_type == 'TEACHER' ? $rootScope.currentUser.emp_id : undefined);
			apiService.getClassCats(params, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success')	$rootScope.classCats = $scope.classCats = result.data;

			}, function(){});

		}
		else $scope.classCats = $rootScope.classCats;

		$scope.$watch('classCats', function(newVal,oldVal){
			/* wait till the class cats are ready, then fetch subjects for first cat */
			if( $rootScope.classCats && $rootScope.classCats.length > 0 )
			{
				$scope.filters.class_cat_id = $rootScope.classCats[0].class_cat_id;
				getSubjects($rootScope.classCats[0].class_cat_id);
			}
		});
	}
	$timeout(initializeController,1);

	var getSubjects = function(class_cat_id)
	{
		if( $scope.dataGrid !== undefined )
		{
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}

		if ( $scope.isTeacher )
		{
			var params = $rootScope.currentUser.emp_id + '/' + class_cat_id + '/' + $scope.filters.status;
			apiService.getAllTeacherSubjects(params, function(response,status,params){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.subjects = ( result.nodata ? [] : result.data );

					$timeout(initDataGrid,10);
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}

			}, apiError);

		}
		else
		{
			var params = class_cat_id + '/' + $scope.filters.status + '/0';
			apiService.getAllSubjects(params, function(response,status,params){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.subjects = ( result.nodata ? [] : result.data );

					$timeout(initDataGrid,10);
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}

			}, apiError);
		}

	}

	$scope.loadFilter = function()
	{
		$scope.loading = true;
		getSubjects($scope.filters.class_cat_id);
	}

	var initDataGrid = function()
	{
		var tableElement = $('#resultsTable');

		$scope.dataGrid = tableElement.DataTable( {
				rowReorder: true,
				columnDefs: [
					{ orderable: true, className: 'reorder', targets: 0 },
					{ orderable: false, targets: '_all' }
				],
				paging: false,
				destroy:true,
				filter: true,
				info: false,
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
						search: "Search Results<br>",
						searchPlaceholder: "Filter",
						lengthMenu: "Display _MENU_",
						emptyTable: "No subjects found."
				},
			} );


		var headerHeight = $('.navbar-fixed-top').height();
		//var subHeaderHeight = $('.subnavbar-container.fixed').height();
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 41 );
		new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );


		// handle reordering, update sort order and update database
		$scope.dataGrid.on( 'row-reordered', function ( e, diff, edit ) {
			/* need to update the sort order of all the rows */
			updateSortOrder();

		} );


		// position search box
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+55);
		}

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

	var updateSortOrder = function()
	{
		/* loop through all the rows, grab the id from the row and the value in the first table cell */
		/* build array and pass to database */

		var putData = {
			user_id: $rootScope.currentUser.user_id,
			data: []
		};
		$scope.dataGrid.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
			var data = {
				subject_id: this.id(),
				sort_order: this.data()[0],

			}
			putData.data.push(data);

		} );

		if( putData.data.length > 0 ) apiService.setSubjectSortOrder(putData, function(){}, apiError);


	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}


	$scope.addSubject = function()
	{
		var data = { subjects: $scope.subjects };
		$scope.openModal('school', 'subjectForm', 'md', data);
	}

	$scope.viewSubject = function(item)
	{
		var data = {
			subjects: $scope.subjects,
			subject : item
		}
		$scope.openModal('school', 'subjectForm', 'md',data);
	}

	$scope.exportItems = function()
	{
		$rootScope.wipNotice();
	}


	$scope.$on('refreshSubjects', function(event, args) {

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
		getSubjects($scope.filters.class_cat_id || "");
	}

	$scope.$on('$destroy', function() {
		if($scope.dataGrid){
			$scope.dataGrid.off( 'row-reordered' );
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.fixedHeader.destroy();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		$rootScope.isModal = false;
    });

} ]);
