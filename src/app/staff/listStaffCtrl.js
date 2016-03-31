'use strict';

angular.module('eduwebApp').
controller('listStaffCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	$scope.employees = [];
	
	$scope.initializeController = function () 
	{
		// get staff
		$timeout(
			getStaff()			
		, 100);
	}
	
	var getStaff = function()
	{
		apiService.getAllEmployees({}, function(response){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{
				console.log(result.data);
				// need to format the employee name
				$scope.employees = result.data.map(function(item){
					item.employee_name = item.first_name + ' ' + item.middle_name + ' ' + item.last_name;
					// set department name
					var theDept = $rootScope.departments.filter(function(a){ 
						return a.dept_id == item.dept_id;
					})[0];
					item.dept_name = (theClass ? theDept.dept_name : '');
					
					// set employee category name					
					var theCat = $rootScope.empCats.filter(function(a){ 
						return a.emp_cat_id == item.emp_cat_id;
					})[0];
					item.emp_cat_name = (theClass ? theCat.emp_cat_name : '');
					
					return item;
				});

				$timeout(initDataGrid,10);
			}
			else
			{
				//$scope.error = true;
				//$scope.errMsg = result.data;
				$timeout(initDataGrid,10);
			}
			
		}, function(){});
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
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			console.log(filterFormWidth);
			$('#resultsTable_filter').css('left',filterFormWidth+45);
		}
		
		$window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				console.log('here');
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				console.log(filterFormWidth);
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
		
	}
	
	
	
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });
	

} ]);