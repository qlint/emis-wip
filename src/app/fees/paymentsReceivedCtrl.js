'use strict';

angular.module('eduwebApp').
controller('paymentsReceivedCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window',
function($scope, $rootScope, apiService, $timeout, $window){

	if($rootScope.currentUser.class_cat_limit){
    $scope.classLimit = $rootScope.currentUser.class_cat_limit.split(',');
    for (var i = 0; i < $scope.classLimit.length; i++) { $scope.classLimit[i] = parseInt($scope.classLimit[i]); }
  }

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
	$scope.loading = true;

	$scope.gridFilter = {};
	$scope.gridFilter.filterValue	 = '';


	var start_date = moment().format('YYYY-01-01');
	var end_date = moment().format('YYYY-MM-DD');
	$scope.date = {startDate: start_date, endDate: end_date};
	var lastQueriedDateRange = angular.copy($scope.date);
	var requery = false;

	$scope.filters.date = $scope.date;

	var rowTemplate = function()
	{
		return '<div class="clickable" ng-class="{\'alert-warning\': row.entity.replacement_payment}">' +
		'	 <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'	 <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"	 ui-grid-cell></div>' +
		'</div>';
	}

	var names = ['Amount ( ' + $scope.currency + ' )'];
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:34,
		columnDefs: [
			{ name: 'Receipt', field: 'payment_id', headerCellClass: 'center', cellClass:'center', enableColumnMenu: false , width:60, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.getReceipt(row.entity)"><i class="glyphicon glyphicon-file"></i><br>{{row.entity.receipt_number}}</div>'},
			{ name: 'Name', field: 'student_name', enableColumnMenu: false , width:150, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewStudent(row.entity)">{{row.entity.student_name}}</div>'},
			{ name: 'Adm.#', field: 'admission_number', enableColumnMenu: false , cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewStudent(row.entity)">{{row.entity.admission_number}}</div>'},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.class_name}}</div>'},
			{ name: 'Bank Date', field: 'banking_date', enableColumnMenu: false , type: 'date', cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.banking_date}}</div>'},
			{ name: 'Entry Date', field: 'payment_date', enableColumnMenu: false , type: 'date', sort: {direction: 'desc' }, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.payment_date|date}}</div>'},
			{ name: 'Payment Method', field: 'payment_method', enableColumnMenu: false, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.payment_method}}</div>'},
			{ name: names[0], field: 'amount', enableColumnMenu: false , type:'number', headerCellClass: 'center', cellClass:'center', cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.amount|currency:""}}</div>'},
			{ name: 'Ref. No.', field: 'slip_cheque_no', enableColumnMenu: false , cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.slip_cheque_no}}</div>'},
			{ name: 'Applied To', field: 'applied_to', enableColumnMenu: false , width:200, cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.applied_to|arrayToList}}</div>'},
			{ name: 'Amount Unapplied', field: 'unapplied_amount', enableColumnMenu: false , headerCellClass: 'center', cellClass:'center', cellTemplate:'<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)" ng-class="{\'alert-danger\': row.entity.unapplied_amount>0}">{{row.entity.unapplied_amount|currency:""}}</div>'},
			{ name: 'Replacement?', field: 'replacement', headerCellClass: 'center', cellClass:'center', enableColumnMenu: false, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.replacement}}</div>'},
			{ name: 'Reversed?', field: 'reverse', headerCellClass: 'center', cellClass:'center', enableColumnMenu: false, cellTemplate: '<div class="ui-grid-cell-contents" ng-click="grid.appScope.viewPayment(row.entity)">{{row.entity.reverse}}</div>'},
		],
		exporterCsvFilename: 'payments-received.csv',
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
					if(result.data){
						result.data = result.data.filter(cat => $scope.classLimit.includes(cat.class_cat_id));
					}
					$scope.classes = result.data;
				}

			}, function(){});
		}
		else
		{
			if($rootScope.allClasses){
				$rootScope.allClasses = $rootScope.allClasses.filter(cat => $scope.classLimit.includes(cat.class_cat_id));
			}
			$scope.classes = $rootScope.allClasses;
		}

		// get terms
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

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);

		getPayments(false);

	}
	$timeout(initializeController,1);

	var getPayments = function(filtering)
	{

		var filters = angular.copy($scope.filters);
		var request =	 moment(filters.date.startDate).format('YYYY-MM-DD') + '/' + moment(filters.date.endDate).format('YYYY-MM-DD') + '/' + filters.payment_status;
		if( status != '' ) request +=	 '/' + filters.status;
		apiService.getPaymentsReceived(request, function(response,status,params){
			var result = angular.fromJson(response);

			// store these as they do not change often
			if( result.response == 'success')
			{
				if(result.nodata !== undefined )
				{
					$scope.payments = [];
				}
				else
				{
					if(result.data){
						result.data = result.data.filter(cat => $scope.classLimit.includes(cat.class_cat_id));
					}

					lastQueriedDateRange = params.filters.date;

					var payments = result.data;

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

					$scope.payments = payments.map(function(item){
						item.replacement = ( item.replacement_payment ? 'Yes' : 'No');
						item.reverse = ( item.reversed ? 'Yes' : 'No');
						item.receipt_number = $rootScope.zeroPad(item.payment_id,5);
						return item;
					});

				}
				initDataGrid($scope.payments);

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
		$scope.totals.total_paid = $scope.payments.reduce(function(sum,item){
			return sum = (sum + parseFloat(item.amount));
		},0);

	}

	var initDataGrid = function(data)
	{
		// updating datagrid, also update totals
		if( $scope.payments.length > 0 ) calcTotals();
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
			[ 'student_name', 'class_name', 'payment_date', 'payment_method', 'receipt_number' ].forEach(function( field ){
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

		if( newVal === undefined || newVal == '' )	$scope.classes = $rootScope.allClasses;
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
			$scope.payments = filterResults(( $scope.filters.payment_status == 'true' ? $scope.reversedPayments : $scope.allPayments), $scope.filters);
			initDataGrid($scope.payments);
		}

	}

	var filterResults = function(data, filters)
	{
		// filter by class category
		if( filters.class_cat_id !== undefined && filters.class_cat_id !== null && filters.class_cat_id !== ''	)
		{
			data = data.reduce(function(sum, item) {
				if( item.class_cat_id.toString() == filters.class_cat_id.toString() ) sum.push(item);
				return sum;
			}, []);
		}

		// filter by class
		if( filters.class_id !== undefined && filters.class_id !== null && filters.class_id !== ''	)
		{
			data = data.reduce(function(sum, item) {
				if( item.class_id.toString() == filters.class_id.toString() ) sum.push(item);
				return sum;
			}, []);
		}

		// filter by status
		if( filters.status !== undefined && filters.status !== null && filters.status !== '' )
		{
			data = data.reduce(function(sum, item) {
				if( item.status.toString() == filters.status.toString() ) sum.push(item);
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
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
	}

	$scope.viewStudent = function(student)
	{
		var data = {
			student: student
		}
		$scope.openModal('students', 'viewStudent', 'lg',data);
	}

	$scope.viewPayment = function(item)
	{
		$scope.openModal('fees', 'paymentDetails', 'lg',item);
	}

	$scope.adjustPayment = function()
	{
		$scope.openModal('fees', 'paymentDetails', 'lg',{});
	}

	$scope.getReceipt = function(payment)
	{
		// get the student and fee items
		apiService.getStudentDetails(payment.student_id, function(response){
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

				// get fee items
				apiService.getFeeItems(true, function(response){
					var result = angular.fromJson(response);

					if( result.response == 'success')
					{

						// set the required fee items
						// format returned fee items for our needs
						$scope.feeItems = formatFeeItems(result.data.required_items);

						// remove any items that do not apply to this students class category
						$scope.feeItems = filterFeeItems($scope.feeItems);


						// repeat for optional fees
						// convert the classCatsRestriction to array for future filtering
						$scope.optFeeItems = formatFeeItems(result.data.optional_items);

						// remove any items that do not apply to this students class category
						$scope.optFeeItems = filterFeeItems($scope.optFeeItems);


						// now, get receipt
						var data = {
							student: $scope.student,
							payment: payment,
							feeItems: $scope.feeItems.concat($scope.optFeeItems)
						}

						$scope.openModal('fees', 'receipt', 'md',data);

					}

				}, function(){});
			}
		});


	}

	var formatFeeItems = function(feeItems)
	{
		// convert the classCatsRestriction to array for future filtering
		return feeItems.map(function(item){
			// format the class restrictions into any array
			if( item.class_cats_restriction !== null && item.class_cats_restriction != '{}' )
			{
				var classCatsRestriction = (item.class_cats_restriction).slice(1, -1);
				item.class_cats_restriction = classCatsRestriction.split(',');
			}
			item.amount = undefined;
			item.payment_method = undefined;

			return item;
		});
	}

	var filterFeeItems = function(feesArray)
	{
		var feeItems = [];

		if( $scope.student.new_student )
		{
			// all fees apply to new students
			feeItems = feesArray;
		}
		else
		{
			// remove new student fees
			feeItems = feesArray.filter(function(item){
				if( !item.new_student_only ) return item;
			});
		}

		// now filter by selected class
		if( $scope.student.current_class !== undefined )
		{
			feeItems = feeItems.filter(function(item){
				if( item.class_cats_restriction === null || item.class_cats_restriction == '{}' ) return item;
				else if( item.class_cats_restriction.indexOf(($scope.student.current_class.class_cat_id).toString()) > -1 ) return item;
			});
		}

		return feeItems;
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
		$timeout(function() { $scope.alert.expired = true;	}, 2000);
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
		$rootScope.isModal = false;
		});

} ]);
