'use strict';

angular.module('eduwebApp').
controller('openingBalancesCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;	
	$rootScope.modalLoading = false;
	$scope.alert = null;
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.totals = {};
	
	$scope.years = [];
	var currentYear = moment().format('YYYY');
	var startYear = ( $rootScope.currentUser.settings['Initial Year'] !== undefined ? $rootScope.currentUser.settings['Initial Year'] : '2014');
	var diff = currentYear - startYear;
	for(var i=startYear; i<=currentYear; i++)
	{
		$scope.years.push(i);
	}
	$scope.filters.year = currentYear;
	var lastQueriedYear = currentYear;
	var requery = false;

	var initializeController = function () 
	{
		// get classes
		if( $rootScope.allClasses === undefined )
		{
			apiService.getAllClasses({}, function(response){
				var result = angular.fromJson(response);
				
				// store these as they do not change often
				if( result.response == 'success') 
				{
					$rootScope.allClasses = result.data;
					$scope.classes = $rootScope.allClasses;
				}
				
			}, function(){});
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
		}
		
		getStudentBalances('true',false);
	}
	$timeout(initializeController,1);
	
	var getStudentBalances = function(status, filtering)
	{
		if( $scope.dataGrid !== undefined )
		{	
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();			
		}		
		
		var year = angular.copy($scope.filters.year);
		var request =  year + '/' + status;
		apiService.getStudentBalances(request, function(response,status,params){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{	
				if(result.nodata !== undefined )
				{
					$scope.students = [];
					$timeout(initDataGrid,10);
				}
				else
				{
					lastQueriedYear = params.year;
					var formatedResults = result.data;
						
					if( filtering )
					{
						$scope.formerStudents = formatedResults
						filterResults();
					}
					else
					{
						$scope.allStudents = formatedResults;
						$scope.students = formatedResults;
						$timeout(initDataGrid,10);
					}

				}
				
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, function(){}, {year:year});
	}
	
	var calcTotals = function()
	{
		$scope.totals.total_due = $scope.students.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.total_due));
		},0);
		
		$scope.totals.total_paid = $scope.students.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.total_paid));
		},0);
		
		$scope.totals.total_balance = $scope.students.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.balance));
		},0);
	}
	
	var initDataGrid = function() 
	{
		// updating datagrid, also update totals
		calcTotals();
		
		
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
				order: [5,'asc'],
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
						lengthMenu: "Display _MENU_",
						emptyTable: "No student balances found."
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
			//console.log(filterFormWidth);
			$('#resultsTable_filter').css('left',filterFormWidth+40);
		}
		
		$window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				//console.log('here');
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				//console.log(filterFormWidth);
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
		
	}
	
	$scope.$watch('filters.class_cat_id', function(newVal,oldVal){
		if (oldVal == newVal) return;
		
		if( newVal === undefined || newVal == '' ) 	$scope.classes = $rootScope.allClasses;
		else
		{	
			// filter classes to only show those belonging to the selected class category
			$scope.classes = $rootScope.allClasses.reduce(function(sum,item){
				if( item.class_cat_id == newVal ) sum.push(item);
				return sum;
			}, []);
		}
	});
	
	$scope.$watch('filters.year', function(newVal,oldVal){
		if(newVal == oldVal) return;
		if( newVal !== lastQueriedYear ) requery = true;
		else requery = false;
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
		$scope.loading = true;
		isFiltered = true;
		
		// if user is filtering for former students and we have not previously pulled these, get them, then continue to filter
		if( $scope.filters.status == 'false' && $scope.formerStudents === undefined )
		{
			// we need to fetch inactive students first
			getStudentBalances('false', true);			
		}
		else if( requery )
		{
			// need to get fresh data, most likely because the user selected a new year
			getStudentBalances(currentStatus, true);		
		}
		else
		{
			// otherwise we have all we need, just filter it down 
			filterResults();
		}
		
		// store the current status filter
		currentStatus = $scope.filters.status;
		
	}
	
	var filterResults = function()
	{
		if ($scope.dataGrid !== undefined)
		{
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;
		}
		
		// filter by class category
		// allStudents holds current students, formerStudents, the former...
		var filteredResults = ( $scope.filters.status == 'false' ? $scope.formerStudents : $scope.allStudents);
		
		
		if( $scope.filters.class_cat_id !== undefined && $scope.filters.class_cat_id !== null && $scope.filters.class_cat_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_cat_id.toString() == $scope.filters.class_cat_id.toString()  ) sum.push(item);
			  return sum;
			}, []);
		}
		
		if( $scope.filters.class_id !== undefined && $scope.filters.class_id !== null  && $scope.filters.class_id !== ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.class_id.toString() == $scope.filters.class_id.toString()  ) sum.push(item);
			  return sum;
			}, []);
		}
		
		$scope.students = filteredResults;
		$timeout(initDataGrid,1);
	}
	
	$scope.addPayment = function()
	{
		$scope.openModal('fees', 'paymentForm', 'lg',{});
	}
	
	$scope.adjustPayment = function()
	{
		$scope.openModal('fees', 'editPaymentForm', 'lg',{});
	}
	
	$scope.viewStudent = function(student)
	{
		$scope.openModal('students', 'viewStudent', 'lg',student);
	}
	
	$scope.exportBalances = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.$on('refreshBalances', function(event, args) {

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
		getStudentBalances(currentStatus,isFiltered);
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