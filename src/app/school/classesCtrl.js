'use strict';

angular.module('eduwebApp').
controller('classesCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter',
function($scope, $rootScope, apiService, $timeout, $window, $filter){

	$scope.filters= {};
	$scope.alert = {};

	var initializeController = function () 
	{
		getClasses("");
	}
	$timeout(initializeController,1);

	var getClasses = function(class_cat_id)
	{
		if( $scope.dataGrid !== undefined )
		{	
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;			
		}		
		
		var params = (class_cat_id != '' ? class_cat_id + '/true' : "");
		apiService.getClasses(class_cat_id, function(response,status,params){
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
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		getClasses($scope.filters.class_cat_id);		
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
				filter: true,
				order:[[1,'asc'],[2,'asc']],
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
		
		
		// position search box
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+45);
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
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
	$scope.addClass = function()
	{
		$scope.openModal('school', 'classForm', 'md');
	}
	
	$scope.viewClass = function(item)
	{
		$scope.openModal('school', 'classForm', 'md',item);
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
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });

} ]);