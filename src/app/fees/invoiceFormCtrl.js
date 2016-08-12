'use strict';

angular.module('eduwebApp').
controller('invoiceFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data', '$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $parse){
	
	$scope.student = {};
	$scope.selectedStudent = ( data.selectedStudent !== undefined ? data.selectedStudent : undefined);
	$scope.selectStudent = ( data.selectedStudent !== undefined ? false : true);
	$scope.student.selected = $scope.selectedStudent;
	$scope.invoice = {};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.invoice.date = {startDate: moment().format('YYYY-MM-DD')};
	$scope.invoice.due_date = {startDate: moment().add(1,'months').format('YYYY-MM-DD')};
	$scope.invoiceLineItems = [];
	$scope.totals = {};
	$scope.alert = {};
	$scope.filters = {};
	$scope.filters.method = 'system';
	$scope.creditApplied = false;
	
	$scope.initializeController = function()
	{
		if( $scope.selectedStudent === undefined )
		{
			apiService.getAllStudents(true, function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{
					$scope.students = ( result.nodata ? {} : $rootScope.formatStudentData(result.data) );				
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, function(){});
		}
	
		apiService.getTerms(undefined, function(response,status)
		{
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata )
			{
				$scope.terms = result.data;
				$rootScope.terms = result.data;
				
				var currentTerm = $scope.terms.filter(function(item){
					if( item.current_term ) return item;
				})[0];
				$scope.filters.term_id = currentTerm.term_id;
			}
		}, apiError);
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.clearSelect = function(item, $event) 
	{
		$event.stopPropagation(); 

		var item = $parse(item + ".selected");
			item.assign($scope, undefined);
	};
	
	$scope.$watch('student.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		// grab what should be invoice for next term
		$scope.selectedStudent = $scope.student.selected;

	});
	
	
	$scope.setManual = function()
	{

		$scope.invoice.creation_method = 'manual';
		$scope.hasCredit = undefined;
		$scope.credit = undefined; 
				
		apiService.getStudentBalance($scope.selectedStudent.student_id, function(response,status)
		{
			$scope.loading = false;		
			var result = angular.fromJson(response);
					
			if( result.response == 'success') 
			{				
				if( result.nodata === undefined )
				{
					$scope.feeSummary = angular.copy(result.data.fee_summary);
					$scope.fees = angular.copy(result.data.fees);
					
					/*
					// if there is any outstanding balances...
					if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) < 0 )
					{
						
						$scope.invoiceLineItems.unshift({
							fee_item: 'Outstanding Balance from previous invoice',
							amount: Math.abs(parseFloat($scope.feeSummary.balance))
						});
						
					}
					*/
					// is there a credit
					if( $scope.feeSummary &&  parseFloat($scope.feeSummary.total_credit) > 0 )
					{
						$scope.hasCredit = true;
						$scope.credit = parseFloat($scope.feeSummary.total_credit);
					}
				}			
			}
			
			// get student fee items if not already set
			if( $scope.selectedStudent !== undefined && $scope.studentFeeItems === undefined )
			{
				apiService.getStudentFeeItems($scope.selectedStudent.student_id,function(response,status){			
					var result = angular.fromJson(response);							
					if( result.response == 'success')  $scope.studentFeeItems = angular.copy(result.data);
				
				},apiError);
			}
			
			
		}, apiError);
		
		
	}
	
	$scope.setAutomatic = function()
	{
		//$scope.invoiceLineItems = {};
		$scope.invoice.creation_method = 'automatic';
	}
	

	$scope.viewStudent = function(student)
	{
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/students/viewStudent.html','viewStudentCtrl',student,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(results){
			// refresh invoice preview
			$scope.generateInvoice();
		},function(){
			$scope.generateInvoice();
		});
	}

	$scope.generateInvoice = function()
	{
		if( $scope.selectedStudent === undefined || $scope.selectedStudent.student_id === undefined )
		{
			$scope.studentError = true;
		}
		else
		{
			$scope.studentError = false;
			$scope.error = false;
			$scope.errMsg = '';
			$scope.hasCredit = undefined;
			$scope.credit = undefined; 
			
			$scope.hasOverPayment = undefined;
			$scope.overpayment = undefined; 
			
			$scope.loadManual = false;
			$scope.loadSystem = false;
			$scope.termId = angular.copy($scope.filters.term_id);
			
			if( $scope.filters.method == 'manual' )
			{
				apiService.getStudentBalance($scope.selectedStudent.student_id, function(response,status)
				{
					$scope.loading = false;
					var result = angular.fromJson(response);
					
					if( result.response == 'success') 
					{
						if( result.nodata === undefined )
						{
							$scope.feeSummary = angular.copy(result.data.fee_summary);
							$scope.fees = angular.copy(result.data.fees);
							
							// if there is any outstanding balances, add as first line item
							// no longer doing this...
							if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) < 0 )
							{
								$scope.hasArrears = true;
								$scope.underpayment = parseFloat($scope.feeSummary.balance);
						
								/*
								$scope.invoiceLineItems.unshift({
									fee_item: 'Outstanding Balance from previous invoice',
									amount: Math.abs(parseFloat($scope.feeSummary.balance))
								});
								*/
							}
							
							// is there an overpayment?
							if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) > 0 )
							{
								$scope.hasOverPayment = true;
								$scope.overpayment = parseFloat($scope.feeSummary.balance);
								$scope.totals.balance = $scope.totals.balance - $scope.overpayment;
							}
						}
					}
					
					// get student fee items if not already set
					if( $scope.selectedStudent !== undefined && $scope.studentFeeItems === undefined )
					{
						apiService.getStudentFeeItems($scope.selectedStudent.student_id,function(response,status){
							var result = angular.fromJson(response);
							if( result.response == 'success')  $scope.studentFeeItems = angular.copy(result.data);
						
						},apiError);
					}
					
					$scope.loadManual = true;
					
				}, apiError);
			}
			else
			{
				apiService.getStudentBalance($scope.selectedStudent.student_id, function(response,status)
				{
					$scope.loading = false;
					var result = angular.fromJson(response);

					if( result.response == 'success') 
					{
						if( result.nodata === undefined )
						{
							$scope.feeSummary = angular.copy(result.data.fee_summary);
							$scope.fees = angular.copy(result.data.fees);
						}
					}
				
					var params = $scope.termId + '/' + $scope.selectedStudent.student_id;
					apiService.generateInvoices(params, displayInvoice, apiError);
					
				}, apiError);
			}
		}
	}
	
	var displayInvoice = function(response,status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.results = ( result.nodata ? [] : result.data );
			$scope.invoices = [];
			
			// group results by due date
			$scope.invoices = $scope.results.reduce(function(sum, item) {
				var date = angular.copy(item.due_date); // store it to use as our key
				item.amount = item.invoice_amount;
				item.inv_date = {startDate:moment().format('YYYY-MM-DD')};
				item.due_date = {startDate:item.due_date}; // put into object for date selector
				if( sum[date] === undefined ) sum[date] = [];
				sum[date].push( item );
				return sum;
			}, {});
			
			$scope.activeInvoice = Object.keys($scope.invoices)[0];
			
			/*
			// if there is any outstanding balances, add...			

			if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) < 0 )
			{
			
				$scope.invoices[$scope.activeInvoice].unshift({
					fee_item: 'Outstanding Balance from previous invoice',
					amount: Math.abs(parseFloat($scope.feeSummary.balance)),
					inv_date = {startDate:moment().format('YYYY-MM-DD')};
					due_date = {startDate:item.due_date}; // put into object for date selector
				});
				
			}
			*/

			
			// get total of each array in the object
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.amount));
				},0);
			});
			
			$scope.invoiceLineItems = $scope.invoices[$scope.activeInvoice];
			$scope.totals.balance = angular.copy($scope.invoiceTotal[$scope.activeInvoice]);
			$scope.totals.invoice = angular.copy($scope.totals.balance);
			
			// is there a credit
			if( $scope.feeSummary && parseFloat($scope.feeSummary.total_credit) > 0 )
			{
				$scope.hasCredit = true;
				$scope.credit = parseFloat($scope.feeSummary.total_credit);
			}
			
			$scope.loadSystem = true;
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.getInvoice = function(key)
	{
		$scope.activeInvoice = key;
		$scope.invoiceLineItems = $scope.invoices[key];
		$scope.totals.balance = $scope.invoiceTotal[key];
		$scope.sumInvoice();
	}
	
	$scope.$watch('invoice.newItem',function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		var index = $scope.invoiceLineItems.length - 1;
		$scope.invoiceLineItems[index] = newVal;
		$scope.sumInvoice();
	});
	
	$scope.removeLineItem = function(index)
	{
		$scope.invoiceLineItems.splice(index,1);
		$scope.sumInvoice();
	}
	
	$scope.sumInvoice = function()
	{
		$scope.totals.balance = $scope.invoiceLineItems.reduce(function(sum,item){
			if( item.amount == '' ) item.amount = 0;
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		$scope.totals.invoice = angular.copy($scope.totals.balance);
		
		
		// sum all totals if automatic
		if( $scope.filters.method == 'automatic' )
		{
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					if( item.amount == '' ) item.amount = 0;
					return sum = (sum + parseFloat(item.amount));
				},0);
			});
		}
		
		if( $scope.creditApplied ) $scope.totals.balance = $scope.totals.balance - $scope.credit; 
	}
	
	$scope.addRow = function()
	{
		if( $scope.studentFeeItems === undefined )
		{
			apiService.getStudentFeeItems($scope.student.selected.student_id,function(response,status){
		
				var result = angular.fromJson(response);
				
				if( result.response == 'success') 
				{
					$scope.studentFeeItems = angular.copy(result.data);
				}

			
			},apiError);
		}
		$scope.invoiceLineItems.push({
			fee_item:undefined,
			amount:undefined
		});
	}
	

	$scope.applyCredit = function()
	{
		$scope.creditApplied = !$scope.creditApplied;
		
		// credit is applied
		if( $scope.creditApplied )
		{
			// if credit is larger than the invoice total, only apply as much as invoice
			if( $scope.totals.invoice < $scope.credit )
			{
				$scope.appliedCreditAmt = angular.copy($scope.totals.invoice);
				$scope.creditAvailable = $scope.credit - $scope.totals.invoice;
				$scope.totals.balance = $scope.totals.invoice - $scope.appliedCreditAmt;
			}
			else
			{
				$scope.appliedCreditAmt = $scope.credit;
				$scope.creditAvailable = 0;
				$scope.totals.balance = $scope.totals.invoice - $scope.credit;
			}
		}
		else
		{
			// credit is not applied
			$scope.totals.balance = $scope.totals.invoice;
			$scope.creditAvailable = $scope.credit;
			$scope.appliedCreditAmt = 0;
		}

	}

	$scope.save = function()
	{
		$scope.error = false;
		$scope.errMsg = '';
		
		var data = {
			user_id: $scope.currentUser.user_id,
			invoices: []
		};
		var lineItems = [];
		
		
		if( $scope.filters.method == 'automatic' )
		{
			angular.forEach($scope.invoices, function(items,key){
				lineItems = [];
				angular.forEach(items, function(item,key2){
					if( item !== null ) 
					{
						lineItems.push({
							student_fee_item_id: item.student_fee_item_id,
							amount: item.amount
						});
					}
				});
				
				data.invoices.push( {
					inv_date: moment( items[0].inv_date.startDate ).format('YYYY-MM-DD'),
					student_id: $scope.selectedStudent.student_id,
					due_date: moment( items[0].due_date.startDate ).format('YYYY-MM-DD'),
					total_amount: $scope.invoiceTotal[key],
					line_items:lineItems,
					term_id: $scope.termId
				});
			});
		}
		else
		{
			angular.forEach($scope.invoiceLineItems, function(item,key){
				if( item !== null )
				{
					lineItems.push({
						student_fee_item_id: item.student_fee_item_id,
						amount: item.amount
					});
				}
			});
			
			data.invoices.push( {
				inv_date: $scope.invoice.date.startDate,
				student_id: $scope.selectedStudent.student_id,
				due_date: $scope.invoice.due_date.startDate,
				total_amount: $scope.totals.balance,
				line_items:lineItems,
				term_id: $scope.termId
			});
		}
		apiService.createInvoice(data,createCompleted,apiError);
		
	}
	
	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			
			if( $scope.creditApplied )
			{
				showCreditApplyForm(result.data);					
			}
			else
			{
				$uibModalInstance.close();
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice(s) created.', 'clear' : true});
			}
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	var showCreditApplyForm = function(data)
	{
		// display the invoice line items for the user to choose for applying payment
		var invoiceData = data;
		
		var domain = window.location.host;
		var dlg = $dialogs.create('applyCredit.html','applyCreditCtrl',{selectedStudent:$scope.student, invoiceData:invoiceData, credit: $scope.appliedCreditAmt},{size: 'md',backdrop:'static'});
		dlg.result.then(function(results){
		
			// save payment
			// user has applied a credit
			// need to send add payment for new invoice just created
			// also need to update the credit
			var invId = results.invId;
			var feeItemsSelection = results.feeItemsSelection;
			var lineItems = [];
			angular.forEach(feeItemsSelection, function(item,key){
				lineItems.push({
					inv_item_id: item.inv_item_id,
					inv_id : invId,
					amount: item.amount
				});
			});
		
			var data = {
				user_id: $scope.currentUser.user_id,
				student_id : $scope.selectedStudent.student_id,
				payment_date : moment().format('YYYY-MM-DD'),
				amount: $scope.appliedCreditAmt,
				payment_method : 'Credit',
				slip_cheque_no: null,
				replacement_payment: 'f',
				line_items: lineItems,
				hasCredit: false,
				creditAmt: $scope.creditAvailable,
				updateCredit: true,
				amtApplied: $scope.appliedCreditAmt
			};
			apiService.addPayment(data,function(response,status){
			
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					// saved, close it all down
					$uibModalInstance.close();
					$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice(s) created.', 'clear' : true});
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
			},apiError);
			
		},function(){
			// user cancelled, now what?
			// ask them if they do not wish to apply the credit?
			var dlg2 = $dialogs.confirm('Cancel Credit?','Do you wish to cancel applying the credit to this invoice?', {size:'sm'});
			dlg2.result.then(function(btn){
				// they want to cancel, close window
				$uibModalInstance.close();
				$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice(s) created.', 'clear' : true});
			},function(btn){
				// if they so no, they need to select the fee items
				showCreditApplyForm(data);				
			});
		});
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
}])
.controller('applyCreditCtrl',['$scope','$rootScope','$uibModalInstance','dialogs','$filter','data',
function($scope,$rootScope,$uibModalInstance,$dialogs,$filter,data){
		
		//-- Variables --//
		$scope.student = data.selectedStudent;
		$scope.invoiceData = data.invoiceData;
		$scope.credit = data.credit;
		$scope.feeItemsSelection = [];
		$scope.apply_to_all = false;
		
		//-- Methods --//
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.selectAllItems = function()
		{
			$scope.apply_to_all = !$scope.apply_to_all;

			if( $scope.apply_to_all )
			{
				angular.forEach($scope.invoiceData, function(feeitem,key){
					feeitem.amount = Math.abs(feeitem.balance);
					$scope.feeItemsSelection.push(feeitem);
				});
			}
			else
			{
				angular.forEach($scope.invoiceData, function(feeitem,key){
					feeitem.amount = undefined;
					$scope.feeItemsSelection = [];
				});
			}
		}
		
		$scope.toggleFeeItems = function(feeitem) 
		{
			var id = $scope.feeItemsSelection.indexOf(feeitem);

			// is currently selected
			if (id > -1) {
				feeitem.amount = undefined;
				$scope.feeItemsSelection.splice(id, 1);
			}

			// is newly selected
			else {
				if( $scope.credit < Math.abs(feeitem.balance) ) feeitem.amount = $scope.credit;
				else feeitem.amount = Math.abs(feeitem.balance);
				$scope.feeItemsSelection.push(feeitem);
			}
		};
	
		$scope.done = function(theForm)
		{			
			if( !theForm.$invalid )
			{
				// make sure that the fee items selected do not total up to more than the payment amount
				var totalFeeItems = $scope.feeItemsSelection.reduce(function(sum,item){
					sum = sum + parseFloat(item.amount);
					return sum;
				},0);
				
				if( totalFeeItems > $scope.credit )
				{
					var dlg = $dialogs.error('Amount Inconsistency','<p>You have entered <strong>' + $filter('number')(totalFeeItems) + ' Ksh</strong> towards fee items, however to total payment amount entered was <strong>' + $filter('number')($scope.credit) + ' Ksh</strong>.</p><p>Please correct, the total amount applied to fee items can not exceed the total payment amount.</p>', {size:'sm'});
				}
				else if ( totalFeeItems < $scope.credit )
				{
					$scope.creditAmt = $scope.credit - totalFeeItems;
					var dlg = $dialogs.confirm('Unapplied Payment','<p>You have <strong>' +  $filter('number')(($scope.creditAmt)) + ' Ksh</strong> remaining of the credit, do you wish to continue?</p>', {size:'sm'});
					dlg.result.then(function(btn){
						 // save the form
						 $scope.saveCredit = true;
						 savePayment();
						 
					},function(btn){
						$scope.saveCredit = false;
					});
				}
				else
				{
					savePayment();
				}
		
				
			}
		}; // end save
		
		var savePayment = function()
		{
			var data = {
				invId: $scope.invoiceData[0].inv_id,
				feeItemsSelection: $scope.feeItemsSelection
			}
			$uibModalInstance.close(data);
		}
		
		
	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('applyCredit.html',
			'<form name="applyCredit" class="form-horizontal modalForm" novalidate role="form" ng-submit="done(applyCredit)">' +	
			'<div class="modal-header dialog-header-confirm">'+
				'<h4 class="modal-title">Apply Credit</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +	
				'<div class="alert alert-info">Select where you would like to apply the credit of <b>{{credit|currency:""}}</b> Ksh.</div>' +
					'<table class="display dataTable" cellspacing="0" width="100%">'+
					'<thead>'+
						'<tr>'+
							'<th class="center">'+
								'<input type="checkbox" name="apply_to_all" ng-model="payment.apply_to_all[$index]" ng-click="selectAllItems()" ng-value="true"  />'+
							'</th>'+
							'<th>Fee Item</th>'+
							'<th>Balance</th>'+
							'<th>Paying</th>'+
						'</tr>'+
					'</thead>'+
					'<tbody>'+
						'<tr ng-repeat="feeitem in invoiceData">'+
							'<td class="center">'+
							 '<input type="checkbox" name="selected_invoices[]" value="{{item.inv_item_id}}" ng-checked="feeItemsSelection.indexOf(feeitem) > -1" ng-click="toggleFeeItems(feeitem)" >'+
							'</td>'+
							'<td ng-click="toggleFeeItems(feeitem)">{{feeitem.fee_item}}</td>'+
							'<td ng-click="toggleFeeItems(feeitem)">{{feeitem.balance|numeric}}</td>'+
							'<td>'+
								'<input type="text" name="fee_item_amount[]" ng-model="feeitem.amount" class="form-control" placeholder="{{feeitem.balance|makePositive}}" />'+
							'</td>'+
						'</tr>'+
					'</tbody>'+
				'</table>' +				
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="submit" class="btn btn-danger">Save</button>' +
			'</div>' +
			'</form>'
		);
}]);