'use strict';

angular.module('eduwebApp').
controller('invoicesCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filters.balance_status = ( $state.params.balance_status !== '' ? $state.params.balance_status : null );
	$scope.filterBalStatus = ( $state.params.balance_status !== '' ? true : false );
	$scope.invoices = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;	
	$rootScope.modalLoading = false;
	$scope.alert = null;
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.totals = {};
	$scope.balanceStatuses = ['Balance Owing','Paid in Full','Due This Month','Past Due'];

	var start_date = moment().format('YYYY-01-01');
	var end_date = moment().format('YYYY-MM-DD');
	$scope.date = {startDate: start_date, endDate: end_date};
	var lastQueriedDateRange = angular.copy($scope.date);
	var requery = false;
	
	$scope.filters.date = $scope.date;			
			

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
					setTermRanges(result.data);
				}		
			}, function(){});
		}
		else
		{
			$scope.terms  = $rootScope.terms;
			setTermRanges($scope.terms );
		}
		
		getInvoices('true',$scope.filterBalStatus);

	}
	$timeout(initializeController,1);

	
	var setTermRanges = function(terms)
	{
		$scope.termRanges = {};
		angular.forEach(terms, function(item,key){
			$scope.termRanges[item.term_year_name] = [item.start_date, item.end_date];
		});
	}
	
	var getInvoices = function(status, filtering)
	{
		if( $scope.dataGrid !== undefined )
		{	
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();			
		}		
		// TO DO: ability to change the invoice canceled status from false to true
		var filters = angular.copy($scope.filters);
		var request =  moment(filters.date.startDate).format('YYYY-MM-DD') + '/' + moment(filters.date.endDate).format('YYYY-MM-DD') + '/false/' + status;
		apiService.getInvoices(request, function(response,status,params){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{	
				if(result.nodata !== undefined )
				{
					$scope.invoices = {};
					$timeout(initDataGrid,10);
				}
				else
				{
					lastQueriedDateRange = params.filters.date;

					var invoices = result.data;			
						
					if( params.filters.status === false )
					{
						$scope.formerStudents = invoices;
						$scope.invoices = filterResults(invoices,params.filters);
					}
					else
					{
						$scope.allStudents = invoices;
						$scope.invoices = ( filtering ? filterResults(invoices,params.filters): invoices);						
					}
					
					$timeout(initDataGrid,10);
				}
				
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, function(){}, {filters:filters});
	}
	
	var calcTotals = function()
	{
		$scope.totals.total_due = $scope.invoices.reduce(function(sum,item){
			return sum = (parseInt(sum) + parseInt(item.total_due));
		},0);
		
		$scope.totals.total_paid = $scope.invoices.reduce(function(sum,item){
			return sum = (parseInt(sum) + parseInt(item.total_paid));
		},0);
		
		$scope.totals.total_balance = $scope.invoices.reduce(function(sum,item){
			return sum = (parseInt(sum) + parseInt(item.balance));
		},0);
	}
	
	var initDataGrid = function() 
	{
		// updating datagrid, also update totals
		if( $scope.invoices.length > 0 ) calcTotals();
		
		
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
				order: [8,'desc'],
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
			$('#resultsTable_filter').css('left',filterFormWidth+50);
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
	
	$scope.$watch('filters.date', function(newVal,oldVal){
		if(newVal == oldVal) return;
		if( newVal !== lastQueriedDateRange ) requery = true;
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
			getInvoices('false', true);			
		}
		else if( requery )
		{
			// need to get fresh data, most likely because the user selected a new year
			getInvoices(currentStatus, true);		
		}
		else
		{
			// otherwise we have all we need, just filter it down 
			$scope.invoices = filterResults(( $scope.filters.status == 'false' ? $scope.formerStudents : $scope.allStudents), $scope.filters);
			$timeout(initDataGrid,1);
		}
		
		// store the current status filter
		currentStatus = $scope.filters.status;
		
	}
	
	var filterResults = function(data, filters)
	{
		if ($scope.dataGrid !== undefined)
		{
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;
		}
		
		// filter by class category		
		
		if( filters.class_cat_id !== undefined && filters.class_cat_id !== null && filters.class_cat_id !== ''  )
		{
			data = data.reduce(function(sum, item) {
			  if( item.class_cat_id.toString() == filters.class_cat_id.toString()  ) sum.push(item);
			  return sum;
			}, []);
		}
		
		if( filters.class_id !== undefined && filters.class_id !== null && filters.class_id !== ''  )
		{
			data = data.reduce(function(sum, item) {
			  if( item.class_id.toString()  == filters.class_id.toString()  ) sum.push(item);
			  return sum;
			}, []);
		}
		
		if( filters.balance_status !== undefined && filters.balance_status !== null && filters.balance_status !== '' )
		{
			switch (filters.balance_status)
			{
				case "Balance Owing":
					data = data.reduce(function(sum, item) {
					  if( item.balance < 0 ) sum.push(item);
					  return sum;
					}, []);
					break;
				case "Paid in Full":
					data = data.reduce(function(sum, item) {
					  if( item.balance >=0 ) sum.push(item);
					  return sum;
					}, []);
					break;
				case "Due This Month":
					var start_date = moment().startOf('month').format('YYYY-MM-DD');
					var end_date = moment().endOf('month').format('YYYY-MM-DD');
					
					data = data.reduce(function(sum, item) {
						var due_date = moment(item.due_date).format('YYYY-MM-DD');
					   if( due_date >= start_date && due_date <= end_date ) sum.push(item);
					   return sum;
					}, []);
					break;
				case "Past Due":
					data = data.reduce(function(sum, item) {
					  if( item.days_overdue > 0 ) sum.push(item);
					  return sum;
					}, []);
					break;
			}
			
		}
		
		return data;
		
	}
	
	$scope.addInvoice = function()
	{
		$scope.openModal('fees', 'invoiceForm', 'lg',{});
	}
	
	$scope.generateInvoices = function()
	{
		$scope.openModal('fees', 'invoiceWizard', 'lg');
	}
	
	$scope.exportInvoices = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.viewStudent = function(student)
	{
		$scope.openModal('students', 'viewStudent', 'lg',student);
	}
	
	$scope.viewInvoice = function(item)
	{
		$scope.openModal('fees', 'invoiceDetails', 'lg', item);	
	}
	
	$scope.$on('refreshInvoices', function(event, args) {

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
		getInvoices(currentStatus,isFiltered);
	}
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });

} ]);