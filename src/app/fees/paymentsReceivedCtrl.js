'use strict';

angular.module('eduwebApp').
controller('paymentsReceivedCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var isFiltered = false;	
	$rootScope.modalLoading = false;
	$scope.alert = {};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.totals = {};
	$scope.paymentStatuses = [{value:'false',label:'Good'},{value:'true',label:'Reversed'}];
	$scope.filters.payment_status = 'false';


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
		
		getPayments(false);

	}
	$timeout(initializeController,1);

	
	var setTermRanges = function(terms)
	{
		$scope.termRanges = {};
		angular.forEach(terms, function(item,key){
			$scope.termRanges[item.term_year_name] = [item.start_date, item.end_date];
		});
	}
	
	var getPayments = function(filtering)
	{
		if( $scope.dataGrid !== undefined )
		{	
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;			
		}		
		
		var filters = angular.copy($scope.filters);
		var request =  moment(filters.date.startDate).format('YYYY-MM-DD') + '/' + moment(filters.date.endDate).format('YYYY-MM-DD') + '/' + filters.payment_status;
		if( status != '' ) request +=  '/' + filters.status;
		apiService.getPaymentsReceived(request, function(response,status,params){
			var result = angular.fromJson(response);
			
			// store these as they do not change often
			if( result.response == 'success')
			{	
				if(result.nodata !== undefined )
				{
					$scope.payments = {};
					$timeout(initDataGrid,10);
				}
				else
				{
					lastQueriedDateRange = params.filters.date;
					
					var payments = result.data;			
					console.log(params.filters);
					if( params.filters.payment_status == 'true' )
					{
						$scope.reversedPayments = payments;
						$scope.payments = filterResults(payments,params.filters);
					}
					else
					{
						$scope.allPayments = payments;
						$scope.payments = ( filtering ? filterResults(payments,params.filters): payments);						
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
		/*
		$scope.totals.total_due = $scope.invoices.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.total_due));
		},0);
		*/
		$scope.totals.total_paid = $scope.payments.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.amount));
		},0);
		/*
		$scope.totals.total_balance = $scope.invoices.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.balance));
		},0);
		*/
	}
	
	var initDataGrid = function() 
	{
		// updating datagrid, also update totals
		if( $scope.payments.length > 0 ) calcTotals();
		
		
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
				order: [4,'desc'],
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
			console.log(filterFormWidth);
			$('#resultsTable_filter').css('left',filterFormWidth+50);
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
		
		if( $scope.filters.payment_status == 'true' && $scope.reversedPayments === undefined )
		{
			// we need to fetch reversed payments first
			getPayments(true);			
		}
		else if( requery )
		{
			// need to get fresh data, most likely because the user selected a new date range
			getPayments(true);		
		}
		else
		{
			// otherwise we have all we need, just filter it down 
			console.log( $scope.reversedPayments);
			console.log( $scope.allPayments);
			$scope.payments = filterResults(( $scope.filters.payment_status == 'true' ? $scope.reversedPayments : $scope.allPayments), $scope.filters);
			$timeout(initDataGrid,1);
		}
		
	}
	
	var filterResults = function(data, filters)
	{
		if ($scope.dataGrid !== undefined)
		{
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;
		}
		
		// filter by class category				
		if( filters.class_cat_id !== undefined && filters.class_cat_id !== ''  )
		{
			data = data.reduce(function(sum, item) {
			  if( item.class_cat_id == filters.class_cat_id) sum.push(item);
			  return sum;
			}, []);
		}
		
		// filter by class
		if( filters.class_id !== undefined && filters.class_id !== ''  )
		{
			data = data.reduce(function(sum, item) {
			  if( item.class_id == filters.class_id) sum.push(item);
			  return sum;
			}, []);
		}
		
		// filter by status
		if( filters.status !== undefined && filters.status !== '' )
		{
			data = data.reduce(function(sum, item) {
			  if( item.status.toString() == filters.status ) sum.push(item);
			  return sum;
			}, []);
		}
		
		return data;
		
	}
	
	$scope.addPayment = function()
	{
		$scope.openModal('fees', 'paymentForm', 'lg',{});
	}
	
	$scope.adjustPayment = function()
	{
		$scope.openModal('fees', 'adjustPaymentForm', 'lg',{});
	}
	
	$scope.exportPayments = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.viewStudent = function(student)
	{
		$scope.openModal('students', 'viewStudent', 'lg',student);
	}
	
	$scope.viewPayment = function(item)
	{
		$scope.openModal('fees', 'paymentDetails', 'lg',item);
	}
	
	$scope.adjustPayment = function()
	{
		$scope.openModal('fees', 'paymentDetails', 'lg',{});
	}
	
	$scope.$on('refreshPayments', function(event, args) {

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
		getPayments(isFiltered);
	}
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });

} ]);