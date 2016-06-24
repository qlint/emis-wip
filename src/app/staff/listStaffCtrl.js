'use strict';

angular.module('eduwebApp').
controller('listStaffCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.employees = [];
	
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filters.emp_cat_id = ( $state.params.category !== '' ? $state.params.category : null );
	$scope.filterEmpCat = ( $state.params.category !== '' ? true : false );
	$scope.filters.dept_id = ( $state.params.dept !== '' ? $state.params.dept : null );
	$scope.filterDept = ( $state.params.dept !== '' ? true : false );
	
	/* get full employee cat record from state param */
	if( $state.params.category !== null )
	{
		$scope.filters.emp_cat = $rootScope.empCats.filter(function(item){
			if( item.emp_cat_id == $state.params.category ) return item;
		})[0];
	}
	
	$scope.alert = {};
	
	var getStaff = function()
	{
		apiService.getAllEmployees(true, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{
				
				if( $scope.dataGrid !== undefined )
				{
					$('.fixedHeader-floating').remove();
					$scope.dataGrid.clear();
					$scope.dataGrid.destroy();
				}
		
		
				$scope.allEmployees = (result.nondata !== undefined ? [] : result.data);	
				$scope.employees = $scope.allEmployees ;
				
				// if filters set, filter results
				if( $scope.currentFilters !== undefined || $scope.filterEmpCat || $scope.filterDept  )
				{
					filterResults();
				}
				else
				{
					$timeout(initDataGrid,10);
				}
				
				
			}
			else
			{
				//$scope.error = true;
				//$scope.errMsg = result.data;
				$timeout(initDataGrid,10);
			}
			
		}, function(){});
	}
	
	var initializeController = function () 
	{
		// get staff
		$scope.departments = $rootScope.allDepts;
		getStaff()			
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
				order: [1,'asc'],
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
						lengthMenu: "Display _MENU_"
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
	
	$scope.filter = function()
	{
		$scope.currentFilters = angular.copy($scope.filters);
		filterResults();
	}
	
	var filterResults = function()
	{
		if( $scope.dataGrid !== undefined ){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.destroy();
		}
		
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
		
		$scope.employees = filteredResults;
		$timeout(initDataGrid,1);
	}
	
	$scope.addEmployee = function()
	{
		$scope.openModal('staff', 'addEmployee', 'lg');
	}
	
	$scope.viewEmployee = function(item)
	{
		$scope.openModal('staff', 'viewEmployee', 'lg', item);
	}
	
	$scope.exportData = function()
	{
		$rootScope.wipNotice();
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
		getStaff();
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