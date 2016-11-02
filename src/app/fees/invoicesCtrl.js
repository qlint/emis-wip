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
	$scope.alert = {};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.totals = {};
	$scope.balanceStatuses = ['Balance Owing','Paid in Full','Due This Month','Past Due'];
	$scope.loading = true;
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';

	var start_date = moment().format('YYYY-01-01');
	var end_date = moment().format('YYYY-12-31');
	$scope.date = {startDate: start_date, endDate: end_date};
	var lastQueriedDateRange = angular.copy($scope.date);
	var requery = false;
	
	$scope.filters.date = $scope.date;
			

	var rowTemplate = function() 
	{
		return '<div class="clickable" ng-class="{\'alert-danger\': row.entity.days_overdue > 0}">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	var names = ['Amount ( ' + $scope.currency + ' )', 'Paid ( ' + $scope.currency + ' )', 'Balance ( ' + $scope.currency + ' )'];
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:34,
		columnDefs: [
			{ name: 'Invoice', field: 'inv_id', headerCellClass: 'center', cellClass:'center', enableColumnMenu: false , width:60, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.getInvoice(row.entity)"><i class="glyphicon glyphicon-file"></i><br>{{row.entity.inv_id}}</div>'},
			{ name: 'Name', field: 'student_name', enableColumnMenu: false, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewStudent(row.entity)">{{row.entity.student_name}}</div>'},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false, cellTemplate: '<div class="ui-grid-cell-contents"  ng-click="grid.appScope.viewInvoice(row.entity)">{{row.entity.class_name}}</div>'},
			{ name: 'Invoice Date', field: 'inv_date', type: 'date', cellFilter: 'date', enableColumnMenu: false,  cellTemplate: '<div class="ui-grid-cell-contents"  ng-click="grid.appScope.viewInvoice(row.entity)">{{row.entity.inv_date|date}}</div>'},
			{ name: names[0], field: 'total_due', enableColumnMenu: false, type:'number', cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewInvoice(row.entity)">{{row.entity.total_due|currency:""}}</div>'},
			{ name: names[1], field: 'total_paid', enableColumnMenu: false, type:'number', cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewInvoice(row.entity)">{{row.entity.total_paid|currency:""}}</div>'},
			{ name: names[2], field: 'balance', enableColumnMenu: false, type:'number', cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewInvoice(row.entity)">{{row.entity.balance|numeric}}</div>'},
			{ name: 'Due Date', field: 'due_date', type: 'date', enableColumnMenu: false, cellFilter:'date',  cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewInvoice(row.entity)">{{row.entity.due_date|date}}</div>' },
			{ name: 'Days Over Due', field: 'days_overdue', enableColumnMenu: false,sort: {direction: 'desc', priority: 1}, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewInvoice(row.entity)">{{row.entity.days_overdue}}</div>'},
		],
		exporterCsvFilename: 'invoices.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};
	
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
				//	$rootScope.allClasses = ;
					$scope.classes = result.data;
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
					$rootScope.setTermRanges(result.data);
				}		
			}, function(){});
		}
		else
		{
			$scope.terms  = $rootScope.terms;
			$rootScope.setTermRanges($scope.terms );
		}
		
		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);
		
		getInvoices('true',$scope.filterBalStatus);

	}
	$timeout(initializeController,1);
	
	var getInvoices = function(status, filtering)
	{
		$scope.gridOptions.data = [];
	
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
					$scope.invoices = [];
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
					
					
				}
				
				initDataGrid($scope.invoices);
				
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, function(){}, {filters:filters});
	}
	
	$scope.getInvoice = function(invoice)
	{
		// get the student and invoice line items
		apiService.getStudentDetails(invoice.student_id, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				var student = $rootScope.formatStudentData([result.data]);
				
				// set current class to full class object
				var currentClass = $rootScope.allClasses.filter(function(item){
					if( item.class_id == student[0].class_id ) return item;
				});
				
				student[0].current_class = currentClass[0];

				$scope.student = student[0];
				
				// open up invoice
				var data = {
					student: $scope.student,
					invoice: invoice
				}			
				$scope.openModal('fees', 'invoice', 'md',data);
	
					
			}
		});
		
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
	
	var initDataGrid = function(data) 
	{
		// updating datagrid, also update totals
		if( $scope.invoices.length > 0 ) calcTotals();
		
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
		  [ 'student_name', 'class_name', 'inv_date' ].forEach(function( field ){
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
			initDataGrid($scope.invoices);
		}
		
		// store the current status filter
		currentStatus = $scope.filters.status;
		
	}
	
	var filterResults = function(data, filters)
	{
		
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
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
	}
	
	$scope.viewStudent = function(student)
	{
		var data = {
			student: student
		}
		$scope.openModal('students', 'viewStudent', 'lg',data);
	}
	
	$scope.viewInvoice = function(item)
	{
		$scope.openModal('fees', 'invoiceDetails', 'md', item);
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
		$rootScope.isModal = false;
    });

} ]);