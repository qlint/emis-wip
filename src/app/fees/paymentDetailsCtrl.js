'use strict';

angular.module('eduwebApp').
controller('paymentDetailsCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$filter',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $filter){

	console.log(data);
	$scope.edit = ( $scope.permissions.fees.payments_received.edit !== undefined ? $scope.permissions.fees.payments_received.edit  : false);
	
	$scope.makeSelection = (data.payment_id === undefined ? true : false );
	
	$scope.student = {};
	$scope.payment = {};	

	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.invoiceSelection = [];
	$scope.feeItemsSelection = [];
	$scope.feeItemsSelection2 = [];
	
	var initializeController = function()
	{
		var paymentMethods = $rootScope.currentUser.settings['Payment Methods'];
		$scope.paymentMethods = paymentMethods.split(',');	
		
		// if a student was passed, get their open invoices in case use was to apply to another invoice
		// also get the details on the payment selected
		if( data.payment_id  !== undefined )
		{
			// get payment details
			apiService.getPaymentDetails(data.payment_id, loadPaymentDetails, apiError);
		}
		else
		{		
			// else, user needs to select a student first, add all students
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
	}
	setTimeout(initializeController,1);
	
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
		
		$scope.selectedStudent = $scope.student.selected;
		apiService.getStudentPayments($scope.selectedStudent.student_id, loadPayments, apiError);
		$scope.selectedPayment = undefined;
	});
	
	var loadPayments = function(response,status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success')
		{
			$scope.payments = ( result.nodata ? [] : angular.copy(result.data) );	
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.$watch('payment.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		$scope.selectedPayment = $scope.payment.selected;
		apiService.getPaymentDetails($scope.selectedPayment.payment_id, loadPaymentDetails, apiError);
		apiService.getOpenInvoices($scope.selectedStudent.student_id, loadInvoices, apiError);		
	});
	
	var loadPaymentDetails = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success')
		{
			var results = ( result.nodata ? {} : result.data );
			
			$scope.selectedPayment = results.payment;
			$scope.selectedInvoice = ( results.invoice.length > 0 ? formateInvoices(results.invoice)[0] : undefined);
			console.log($scope.selectedInvoice);	

			apiService.getOpenInvoices($scope.selectedPayment.student_id, loadInvoices, apiError);
			
			// if there is an associated invoice, set the line items
			if( $scope.selectedInvoice !== undefined )
			{
				// need to loop through the selected invoice and check off what is associated with payment			
				angular.forEach( $scope.selectedInvoice.fee_items, function(item, key){
					
					angular.forEach( results.paymentItems, function(item2,key2)
					{
						if( item.inv_item_id == item2.inv_item_id)
						{
							item.amount = item2.line_item_amount;
							item.payment_inv_item_id = item2.payment_inv_item_id;
							$scope.feeItemsSelection.push(item);
						}
					});						
				});

				console.log($scope.feeItemsSelection);
			}
			
			// if its a replacement payment, set the selected replacement items
			if( $scope.selectedPayment.replacement_payment )
			{
				apiService.getReplaceableFeeItems($scope.selectedPayment.student_id,function(response,status){		
					var result = angular.fromJson(response);							
					if( result.response == 'success')
					{
						$scope.replaceableFeeItems = angular.copy(result.data);
						
						angular.forEach( $scope.replaceableFeeItems, function(item, key){
				
							angular.forEach( results.paymentItems, function(item2,key2)
							{
								if( item.student_fee_item_id == item2.inv_item_id )
								{
									item.paying_amount = item2.line_item_amount;
									item.payment_replace_item_id = item2.payment_inv_item_id;
									$scope.feeItemsSelection2.push(item);
								}
							});						
						});

						console.log($scope.feeItemsSelection2);
						
					}
				},apiError);
			}
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	var loadInvoices = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);
				
		if( result.response == 'success') 
		{
			$scope.invoices = ( result.nodata ? [] : formateInvoices(angular.copy(result.data)));	
			//setTimeout(initInvoicesDataGrid,10);
		}
	}
	
	var formateInvoices = function(invoiceData)
	{
		
			var currentInvoice = {};
			var currentItem = {};
			var invoices = [];
			var feeItems = []
			angular.forEach( invoiceData, function(item,key){
			
				if( key > 0 && currentInvoice != item.inv_id )
				{
					// store row
					$scope.invoices.push({
						inv_id: currentItem.inv_id,
						inv_date: currentItem.inv_date,
						due_date: currentItem.due_date,
						balance: currentItem.balance,
						total_due: currentItem.total_due,
						fee_items: feeItems,
					});
					
					// reset
					feeItems = [];
				}
				
				feeItems.push({
					fee_item_id: item.fee_item_id,
					fee_item: item.fee_item,
					balance: item.line_item_amount,
					inv_item_id: item.inv_item_id,
					amount: null
				});
				
				currentInvoice = item.inv_id;
				currentItem = item;				
			});
			// push in last row
			invoices.push({
				inv_id: currentItem.inv_id,
				inv_date: currentItem.inv_date,
				due_date: currentItem.due_date,
				balance: currentItem.balance,
				total_due: currentItem.total_due,
				fee_items: feeItems,
			});
			console.log(invoices);
			return invoices;
	}
	
	$scope.changeInvoice = function(status)
	{
		$scope.changingInvoice = status;
		if( !status )	$scope.selectedNewInvoice = undefined;
	}
	
	$scope.unapplyPayment = function()
	{
		// remove the invoice from payment
		$scope.selectedPayment.inv_id = undefined;
		$scope.selectedInvoice = undefined;
		$scope.changeInvoice( false );
	}
	
	$scope.$watch('selectedPayment.apply_to_all', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		console.log(newVal);
		
		if( newVal )
		{
			angular.forEach($scope.selectedInvoice.fee_items, function(feeitem,key){
				feeitem.amount = feeitem.balance;
				$scope.feeItemsSelection.push(feeitem);
			});
		}
		else
		{
			angular.forEach($scope.selectedInvoice.fee_items, function(feeitem,key){
				feeitem.amount = undefined;
				$scope.feeItemsSelection = [];
			});
		}
		
	});
	
	$scope.$watch('selectedPayment.replacement_payment', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		
	});
	
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
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	$scope.$watch('payment.invoice', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		$scope.selectedNewInvoice = newVal;
	});
	/*
	$scope.showDetails = [];
	$scope.toggleInvoices = function(item,index) 
	{
	
		var id = $scope.invoiceSelection.indexOf(item);

		// is currently selected
		if (id > -1) {
			$scope.invoiceSelection.splice(id, 1);
			$scope.showDetails[index] = 'false';
		}

		// is newly selected
		else {
			$scope.invoiceSelection.push(item);
			$scope.showDetails[index] = 'true';
		}
	};
	*/
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
			feeitem.amount = feeitem.balance;
			$scope.feeItemsSelection.push(feeitem);
		}
	};
	$scope.toggleFeeItems2 = function(feeitem) 
	{
		var id = $scope.feeItemsSelection2.indexOf(feeitem);

		// is currently selected
		if (id > -1) {
			feeitem.paying_amount = undefined;
			$scope.feeItemsSelection2.splice(id, 1);
		}

		// is newly selected
		else {
			feeitem.paying_amount = feeitem.amount;
			$scope.feeItemsSelection2.push(feeitem);
		}
	};
	
	$scope.save = function()
	{
		// make sure that the fee items selected do not total up to more than the payment amount
		var totalFeeItems = $scope.feeItemsSelection.reduce(function(sum,item){
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		console.log(totalFeeItems);
		
		
		if( $scope.selectedPayment.replacement_payment !== true )
		{
			if( totalFeeItems > $scope.selectedPayment.amount )
			{
				var dlg = $dialogs.error('Amount Inconsistency','<p>You have entered <strong>' + $filter('number')(totalFeeItems) + ' Ksh</strong> towards fee items, however to total payment amount enterd was <strong>' + $filter('number')($scope.selectedPayment.amount) + ' Ksh</strong>.</p><p>Please correct, the total amount applied to fee items can not exceed the total payment amount.</p>', {size:'sm'});
			}
			else if ( totalFeeItems < $scope.selectedPayment.amount )
			{
				var dlg = $dialogs.confirm('Unapplied Payment','<p>You have <strong>' +  $filter('number')(($scope.selectedPayment.amount - totalFeeItems)) + ' Ksh</strong> that has not been applied to fee items, do you wish to continue?</p>', {size:'sm'});
				dlg.result.then(function(btn){
					 // save the form
					 savePayment();
					 
				},function(btn){
				});
			}
			else
			{
				savePayment();
			}
		}
		else
		{
			savePayment();
		}
	}
	
	var savePayment = function()
	{
		$scope.error = false;
		$scope.errMsg = '';
		
		if( $scope.selectedPayment.replacement_payment )
		{
			var replacementItems = [];
			angular.forEach($scope.feeItemsSelection2, function(item,key){
				replacementItems.push({
					payment_replace_item_id: item.payment_replace_item_id,
					student_fee_item_id: item.student_fee_item_id,
					amount: item.paying_amount
				});
			});
			
			var data = {
				user_id: $scope.currentUser.user_id,
				payment_id : $scope.selectedPayment.payment_id,
				student_id : $scope.selectedPayment.student_id,
				payment_date : moment($scope.selectedPayment.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.selectedPayment.amount,
				payment_method : $scope.selectedPayment.payment_method,
				slip_cheque_no: $scope.selectedPayment.slip_cheque_no,
				replacement_payment: ($scope.selectedPayment.replacement_payment ? 't' : 'f' ),
				inv_id : ($scope.selectedPayment.invoice !== undefined ? $scope.selectedPayment.invoice.inv_id : ($scope.selectedInvoice !== undefined ? $scope.selectedInvoice.inv_id : null)),
				replacement_items: replacementItems
			};
		}
		else{
			var lineItems = [];
			angular.forEach($scope.feeItemsSelection, function(item,key){
				lineItems.push({
					payment_inv_item_id: item.payment_inv_item_id,
					inv_item_id: item.inv_item_id,
					amount: item.amount
				});
			});
			
			var data = {
				user_id: $scope.currentUser.user_id,
				payment_id : $scope.selectedPayment.payment_id,
				student_id : $scope.selectedPayment.student_id,
				payment_date : moment($scope.selectedPayment.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.selectedPayment.amount,
				payment_method : $scope.selectedPayment.payment_method,
				slip_cheque_no: $scope.selectedPayment.slip_cheque_no,
				replacement_payment: ($scope.selectedPayment.replacement_payment == 'true' ? 't' : 'f' ),
				inv_id : ($scope.selectedPayment.invoice !== undefined ? $scope.selectedPayment.invoice.inv_id : ($scope.selectedInvoice !== undefined ? $scope.selectedInvoice.inv_id : null)),
				line_items: lineItems
			};
			
		}

		console.log(data);
		
		apiService.updatePayment(data,createCompleted,apiError);
		
	}
	
	$scope.reversePayment = function()
	{
		var dlg = $dialogs.confirm('Reverse Payment', 'Are you sure you want to revers this payment?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				payment_id:$scope.selectedPayment.payment_id
			};
			apiService.reversePayment(data,  function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{
					$scope.selectedPayment.reversed = true;
					$rootScope.$emit('paymentAdded', {'msg' : 'Payment reversed.', 'clear' : true});
				}
				
			}, apiError);	
		});
	}
	
	$scope.reactivatePayment = function()
	{
		var dlg = $dialogs.confirm('Activate Payment', 'Are you sure you want to mark this payment as <strong>active</strong>?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				payment_id:$scope.selectedPayment.payment_id
			};
		
			apiService.reactivatePayment(data,  function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{
					$scope.selectedPayment.reversed = false;
					$rootScope.$emit('paymentAdded', {'msg' : 'Payment activated.', 'clear' : true});
				}
				
			}, apiError);		 
		});
	}
	
	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			$rootScope.$emit('paymentAdded', {'msg' : 'Payment was added.', 'clear' : true});
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
} ]);