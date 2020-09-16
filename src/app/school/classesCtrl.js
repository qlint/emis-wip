'use strict';

angular.module('eduwebApp').
controller('classesCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','$state',
function($scope, $rootScope, apiService, $timeout, $window, $filter, $state){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.alert = {};

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filters.class_cat_id = ( $state.params.class_cat_id !== '' ? $state.params.class_cat_id : null );
	$scope.filterClassCat = ( $state.params.class_cat_id !== '' ? true : false );
	$scope.filters.class_id = ( $state.params.class_id !== '' ? $state.params.class_id : null );
	$scope.filterClass = ( $state.params.class_id !== '' ? true : false );
	$scope.loading = true;

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );

	var initializeController = function ()
	{
/*		if ( $scope.isTeacher )
		{
			apiService.getClassCats($rootScope.currentUser.emp_id, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success') $rootScope.classCats = result.data;

			}, apiError);
		}
		*/
		getClasses("");
	}
	$timeout(initializeController,1);

	var getClasses = function(class_cat_id)
	{
		if( $scope.dataGrid !== undefined )
		{
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}

		if ( $scope.isTeacher )
		{
			var params = $rootScope.currentUser.emp_id + '/' + $scope.filters.status;
			apiService.getTeacherClasses(params, function(response,status){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.classes = ( result.nodata ? [] : result.data );
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

			var params = (class_cat_id != '' && class_cat_id !== null && class_cat_id !== undefined ? class_cat_id + '/' + $scope.filters.status : "ALL" + '/' + $scope.filters.status);
			apiService.getClasses(params, function(response,status){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$scope.classes = ( result.nodata ? [] : result.data );
					
					$scope.classes.forEach((clss, i) => {
						clss.plainSubjects = [];
						if(clss.subjects != null){
							clss.subjects.forEach((classSubj, j) => {
								clss.plainSubjects.push(classSubj.subject_name);
							});
						}

					});

					//$rootScope.allClasses = $scope.classes
					//console.log($scope.classes);

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
		getClasses($scope.filters.class_cat_id);
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
						emptyTable: "No classes found."
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
				class_id: this.id(),
				sort_order: this.data()[0],

			}
			putData.data.push(data);

		} );

		if( putData.data.length > 0 ) apiService.setClassSortOrder(putData, function(){}, apiError);


	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}


	$scope.addClass = function()
	{
		$scope.openModal('school', 'classForm', 'lg',{'classes': $scope.classes});
	}

	$scope.viewClass = function(item)
	{
		$scope.openModal('school', 'classForm', 'lg',{'selectedClass':item});
	}

	$scope.exportItems = function()
	{
		$rootScope.wipNotice();
	}


	$scope.$on('refreshClasses', function(event, args) {

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
		getClasses($scope.filters.class_cat_id || "");
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
