'use strict';

angular.module('eduwebApp').
controller('paymentFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$filter','$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $filter, $parse){

	$scope.student = {};
	$scope.selectedStudent = ( data.selectedStudent !== undefined ? data.selectedStudent : undefined);
	$scope.showSelect =  ( data.selectedStudent !== undefined ? false : true );
	$scope.student.selected = $scope.selectedStudent;
	$scope.payment = {};
	$scope.payment.amount = 0;
	$scope.payment.payment_date = {startDate: moment().format('YYYY-MM-DD')};
	$scope.payment.replacement_payment = false;
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.invoiceSelection = [];
	$scope.feeItemsSelection = [];
	$scope.feeItemsSelection2 = [];
	$scope.apply_to_all = [];
	$scope.totalApplied = 0;
	$scope.totalCredit = 0;
	$scope.slip_check_ready = false;
	$scope.bankSelected = false;

	var initializeController = function()
	{
		var paymentMethods = $rootScope.currentUser.settings['Payment Methods'];
		$scope.paymentMethods = paymentMethods.split(',');

		$scope.paymentBanks = [
													$rootScope.currentUser.settings['Bank Name'],
													$rootScope.currentUser.settings['Bank Name 2'],
													$rootScope.currentUser.settings['Bank Name 3'],
													$rootScope.currentUser.settings['Bank Name 4'],
													$rootScope.currentUser.settings['Bank Name 5'],
												];
		// console.log("Payment Banks",$scope.paymentBanks);

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
		else
		{
			apiService.getOpenInvoices($scope.selectedStudent.student_id, loadInvoices, apiError);
			apiService.getStudentBalance($scope.selectedStudent.student_id, loadFeeBalance, apiError);
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

		// grab what should be invoice for next term
		$scope.selectedStudent = $scope.student.selected;
		apiService.getOpenInvoices($scope.student.selected.student_id, loadInvoices, apiError);
		apiService.getStudentBalance($scope.student.selected.student_id, loadFeeBalance, apiError);
	});

	/*
	$scope.$watch('payment.apply_to_all', function(newVal,oldVal){
		if( newVal == oldVal ) return;

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
	*/

	$scope.$watch('payment.replacement_payment', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		// if replacement payment, remove the invoices and show replaceable fee items that belong to the student

		apiService.getReplaceableFeeItems($scope.selectedStudent.student_id,function(response,status){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.replaceableFeeItems = angular.copy(result.data);
			}


		},apiError);

	});

	$scope.checkMethod = function(){
		console.log($scope.payment.payment_method);
		if($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' || $scope.payment.payment_method == 'Cheque'){
			$scope.bankSelected = true;
		}else{
			$scope.bankSelected = false;
		}
	}

	$scope.selectAllItems = function(key, invoice)
	{
		$scope.apply_to_all[key] = !$scope.apply_to_all[key];

		if( $scope.apply_to_all[key] )
		{
			angular.forEach(invoice.fee_items, function(feeitem,key){
				feeitem.amount = Math.abs(feeitem.balance);
				$scope.totalApplied += parseInt(feeitem.amount);
				$scope.feeItemsSelection.push(feeitem);
			});
		}
		else
		{
			angular.forEach(invoice.fee_items, function(feeitem,key){
				feeitem.amount = undefined;
				$scope.totalApplied = 0;
				$scope.feeItemsSelection = [];
			});
		}
		$scope.totalCredit = ( $scope.payment.amount - $scope.totalApplied > 0 ? $scope.payment.amount - $scope.totalApplied : 0) ;
	}

	var loadInvoices = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.invoices = formatInvoices(angular.copy(result.data));
			//setTimeout(initInvoicesDataGrid,10);
		}
	}

	var formatInvoices = function(invoiceData)
	{
		var currentInvoice = {};
		var currentItem = {};
		var invoices = [];
		var feeItems = []
		var overallBalance = 0;
		angular.forEach( invoiceData, function(item,key){

			if( key > 0 && currentInvoice != item.inv_id )
			{
				// store row
				invoices.push({
					inv_id: currentItem.inv_id,
					inv_date: currentItem.inv_date,
					due_date: currentItem.due_date,
					balance: currentItem.balance,
					overall_balance: overallBalance,
					total_due: currentItem.total_due,
					fee_items: feeItems,
				});

				// reset
				feeItems = [];
				overallBalance = 0;
			}

			feeItems.push({
				fee_item_id: item.fee_item_id,
				fee_item: item.fee_item,
				balance: item.balance,
				inv_item_id: item.inv_item_id,
				inv_id: item.inv_id,
				payment_id: item.payment_id,
				amount: null,
				isPaid: parseInt(item.balance) === 0 ? true : false,
				modifiable: parseInt(item.balance) < 0 ? true : false,
			});
			overallBalance += parseFloat(item.balance);

			currentInvoice = item.inv_id;
			currentItem = item;
		});
		// push in last row
		invoices.push({
			inv_id: currentItem.inv_id,
			inv_date: currentItem.inv_date,
			due_date: currentItem.due_date,
			balance: currentItem.balance,
			overall_balance: overallBalance,
			total_due: currentItem.total_due,
			fee_items: feeItems,
		});
		//console.log(invoices);
		return invoices;
	}

	var loadFeeBalance = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson(response);

		if( $scope.dataGrid !== undefined )
		{
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}

		if( result.response == 'success')
		{
			if( result.nodata === undefined )
			{
				$scope.feeSummary = angular.copy(result.data.fee_summary);
				$scope.fees = angular.copy(result.data.fees);
				$scope.nofeeSummary = false;
			}
			else
			{
				$scope.feeSummary = [];
				$scope.fees = [];
				$scope.nofeeSummary = true;
			}

			setTimeout(initFeesDataGrid,50);
		}
	}

	var initFeesDataGrid = function()
	{
		var settings = {
			sortOrder: [4,'asc'],
			noResultsTxt: "This student has not been invoiced."
		}
		initDataGrid(settings);
	}

	var initDataGrid = function(settings)
	{

		var tableElement = $('#resultsTable2');
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
				order: settings.sortOrder,
				filter: false,
				info: false,
				sorting:[],
				scrollY:'200px',
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
					lengthMenu: "Display _MENU_",
					emptyTable: settings.noResultsTxt
				},
			} );
	}

	$scope.checkSlipNo = function(){
		// console.log($scope.payment.slip_cheque_no);
		apiService.checkSlipNo($scope.payment.slip_cheque_no, function(response, status)
		{
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.checkSlipNoStatus = result.data.status;
				console.log(result,$scope.checkSlipNoStatus);
				$scope.slip_check_ready = true;
			}
			else
			{
				//
			}
		}, apiError);
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	$scope.$watch('payment.amount', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		$scope.totalCredit = ( newVal - $scope.totalApplied > 0 ? newVal - $scope.totalApplied : 0) ;
	});

	$scope.$watch('payment.invoice', function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.selectedInvoice = newVal;
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
			$scope.totalApplied = $scope.totalApplied - feeitem.amount;
			feeitem.amount = undefined;
			$scope.feeItemsSelection.splice(id, 1);
		}

		// is newly selected
		else {
			feeitem.amount = Math.abs(feeitem.balance);
			$scope.totalApplied += parseFloat(feeitem.amount);
			$scope.feeItemsSelection.push(feeitem);
		}

		$scope.totalCredit = ( $scope.payment.amount - $scope.totalApplied > 0 ? $scope.payment.amount - $scope.totalApplied : 0) ;
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

	$scope.save = function(theForm)
	{
		if( !theForm.$invalid )
		{
			$scope.saveCredit = false;

			// make sure that the fee items selected do not total up to more than the payment amount
			var totalFeeItems = $scope.feeItemsSelection.reduce(function(sum,item){
				sum = sum + parseFloat(item.amount);
				return sum;
			},0);


			if( $scope.payment.replacement_payment !== true )
			{
				if( totalFeeItems > $scope.payment.amount )
				{
					var dlg = $dialogs.error('Amount Inconsistency','<p>You have entered <strong>' + $filter('number')(totalFeeItems) + ' Ksh</strong> towards fee items, however to total payment amount entered was <strong>' + $filter('number')($scope.payment.amount) + ' Ksh</strong>.</p><p>Please correct, the total amount applied to fee items can not exceed the total payment amount.</p>', {size:'sm'});
				}
				else if ( totalFeeItems < $scope.payment.amount )
				{
					$scope.creditAmt = $scope.payment.amount - totalFeeItems;
					var dlg = $dialogs.confirm('Adding Credit','<p>You have <strong>' +  $filter('number')(($scope.creditAmt)) + ' Ksh</strong> that has not been applied to fee items, do you wish to add this as a credit?</p>', {size:'sm'});
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
			else
			{
				savePayment();
			}
		}
	}

	$scope.save2 = function(theForm2)
	{
		if( !theForm2.$invalid )
		{
			$scope.saveCredit = false;

			// make sure that the fee items selected do not total up to more than the payment amount
			var totalFeeItems = $scope.feeItemsSelection.reduce(function(sum,item){
				sum = sum + parseFloat(item.amount);
				return sum;
			},0);

			if( $scope.payment.replacement_payment !== true )
			{
				if( totalFeeItems > $scope.payment.amount )
				{
					var dlg = $dialogs.error('Amount Inconsistency','<p>You have entered <strong>' + $filter('number')(totalFeeItems) + ' Ksh</strong> towards fee items, however to total payment amount entered was <strong>' + $filter('number')($scope.payment.amount) + ' Ksh</strong>.</p><p>Please correct, the total amount applied to fee items can not exceed the total payment amount.</p>', {size:'sm'});
				}
				else if ( totalFeeItems < $scope.payment.amount )
				{
					$scope.creditAmt = $scope.payment.amount - totalFeeItems;
					var dlg = $dialogs.confirm('Adding Credit','<p>You have <strong>' +  $filter('number')(($scope.creditAmt)) + ' Ksh</strong> that has not been applied to fee items, do you wish to add this as a credit?</p>', {size:'sm'});
					dlg.result.then(function(btn){
						 // save the form
						 $scope.saveCredit = true;
						 savePayment2();

					},function(btn){
						$scope.saveCredit = false;
					});
				} else { savePayment2(); }
			} else { savePayment2(); }
		}
	}

	var savePayment = function()
	{
		console.log("Save payment version 1");
		$scope.error = false;
		$scope.errMsg = '';

		if( $scope.payment.replacement_payment )
		{
			var replacementItems = [];
			angular.forEach($scope.feeItemsSelection2, function(item,key){
				replacementItems.push({
					student_fee_item_id: item.student_fee_item_id,
					amount: item.paying_amount
				});
			});

			var data = {
				user_id: $scope.currentUser.user_id,
				student_id : $scope.selectedStudent.student_id,
				payment_date : moment($scope.payment.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.payment.amount,
				payment_method : $scope.payment.payment_method,
				payment_bank : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? $scope.payment.payment_bank : null),
				payment_bank_date : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? moment($scope.payment.payment_bank_date.startDate).format('YYYY-MM-DD') : null),
				slip_cheque_no: $scope.payment.slip_cheque_no,
				custom_receipt_no: $scope.payment.custom_receipt_no,
				replacement_payment: ($scope.payment.replacement_payment ? 't' : 'f' ),
				inv_id : ($scope.payment.invoice !== undefined ? $scope.payment.invoice.inv_id : null),
				replacement_items: replacementItems,
				hasCredit: $scope.saveCredit,
				creditAmt: $scope.creditAmt
			};
		}
		else{
			var lineItems = [];
			angular.forEach($scope.feeItemsSelection, function(item,key){
				lineItems.push({
					inv_item_id: item.inv_item_id,
					inv_id :item.inv_id,
					amount: item.amount
				});
			});

			var data = {
				user_id: $scope.currentUser.user_id,
				student_id : $scope.selectedStudent.student_id,
				payment_date : moment($scope.payment.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.payment.amount,
				payment_method : $scope.payment.payment_method,
				payment_bank : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? $scope.payment.payment_bank : null),
				payment_bank_date : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? moment($scope.payment.payment_bank_date.startDate).format('YYYY-MM-DD') : null),
				slip_cheque_no: $scope.payment.slip_cheque_no,
				custom_receipt_no: $scope.payment.custom_receipt_no,
				replacement_payment: ($scope.payment.replacement_payment == 'true' ? 't' : 'f' ),
			//	inv_id : $scope.payment.invoice.inv_id,
				line_items: lineItems,
				hasCredit: $scope.saveCredit,
				creditAmt: $scope.creditAmt
			};

		}

		console.log("Data version 1",data);
		apiService.addPayment(data,createCompleted,apiError);

	}

	var savePayment2 = function()
	{
		console.log("Save payment version 2");
		$scope.error = false;
		$scope.errMsg = '';

		if( $scope.payment.replacement_payment )
		{
			var replacementItems = [];
			angular.forEach($scope.feeItemsSelection2, function(item,key){
				replacementItems.push({
					student_fee_item_id: item.student_fee_item_id,
					amount: item.paying_amount
				});
			});

			var data2 = {
				user_id: $scope.currentUser.user_id,
				student_id : $scope.selectedStudent.student_id,
				payment_date : moment($scope.payment.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.payment.amount,
				payment_method : $scope.payment.payment_method,
				payment_bank : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? $scope.payment.payment_bank : null),
				payment_bank_date : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? moment($scope.payment.payment_bank_date.startDate).format('YYYY-MM-DD') : null),
				slip_cheque_no: $scope.payment.slip_cheque_no,
				custom_receipt_no: $scope.payment.custom_receipt_no,
				replacement_payment: ($scope.payment.replacement_payment ? 't' : 'f' ),
				inv_id : ($scope.payment.invoice !== undefined ? $scope.payment.invoice.inv_id : null),
				replacement_items: replacementItems,
				hasCredit: $scope.saveCredit,
				creditAmt: $scope.creditAmt
			};
		}
		else{
			var lineItems = [];
			angular.forEach($scope.feeItemsSelection, function(item,key){
				lineItems.push({
					inv_item_id: item.inv_item_id,
					inv_id :item.inv_id,
					amount: item.amount
				});
			});

			var data2 = {
				user_id: $scope.currentUser.user_id,
				student_id : $scope.selectedStudent.student_id,
				payment_date : moment($scope.payment.payment_date.startDate).format('YYYY-MM-DD'),
				amount: $scope.payment.amount,
				payment_method : $scope.payment.payment_method,
				payment_bank : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? $scope.payment.payment_bank : null),
				payment_bank_date : ($scope.payment.payment_method == 'Bank receipts' || $scope.payment.payment_method == 'Bank transfer' ? moment($scope.payment.payment_bank_date.startDate).format('YYYY-MM-DD') : null),
				slip_cheque_no: $scope.payment.slip_cheque_no,
				custom_receipt_no: $scope.payment.custom_receipt_no,
				replacement_payment: ($scope.payment.replacement_payment == 'true' ? 't' : 'f' ),
			//	inv_id : $scope.payment.invoice.inv_id,
				line_items: lineItems,
				hasCredit: $scope.saveCredit,
				creditAmt: $scope.creditAmt
			};

		}

		console.log("Data version 2",data2);
		apiService.addPayment(data2,createCompleted2,apiError);

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

	var createCompleted2 = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			$rootScope.$emit('paymentAdded', {'msg' : 'Payment was added.', 'clear' : true});
			var thePaymentDone = result.data.payment_id;
			apiService.getPaymentDetails(thePaymentDone, function(response, status)
                                                        	{
                                                        		var result = angular.fromJson(response);
                                                        		if( result.response == 'success')
                                                        		{
                                                        			var results = ( result.nodata ? {} : result.data );
                                                        			$scope.selectedPayment = results.payment;
                                                        			$scope.isCredit = ($scope.selectedPayment.credit_id !== null ? true : false );
                                                        			$scope.selectedInvoice = ( results.invoice.length > 0 ? formatInvoices(results.invoice) : undefined);
                                                        			$scope.paymentItems = results.paymentItems;


                                                        			// if there is an associated invoice, set the line items
                                                        			if( $scope.selectedInvoice !== undefined )
                                                        			{
                                                        				// need to loop through the selected invoice and check off what is associated with payment
                                                        				angular.forEach( $scope.selectedInvoice, function(item0,key0){

                                                        					angular.forEach(item0.fee_items, function(item, key){

                                                        						angular.forEach( $scope.paymentItems, function(item2,key2)
                                                        						{
                                                        							if( item.inv_item_id == item2.inv_item_id)
                                                        							{
                                                        								item.amount = item2.line_item_amount;
                                                        								item.payment_inv_item_id = item2.payment_inv_item_id;
                                                        								item.modifiable = true;
                                                        								$scope.feeItemsSelection.push(item);
                                                        							}
                                                        						});
                                                        					});
                                                        				});

                                                        			}

                                                        			// if its a replacement payment, set the selected replacement items
                                                        			if( $scope.selectedPayment.replacement_payment ) { getReplaceableFeeItems(); }
                                                        			$scope.sumPayment2 = function()
                                                                	{
                                                                		$scope.totalApplied = $scope.feeItemsSelection.reduce(function(sum,item){
                                                                			if( item.amount == '' ) item.amount = 0;
                                                                			sum = sum + parseFloat(item.amount);
                                                                			return sum;
                                                                		},0);
                                                                		$scope.totalCredit = ( $scope.selectedPayment.amount - $scope.totalApplied > 0 ? $scope.selectedPayment.amount - $scope.totalApplied : 0);
                                                                	}
                                                        			$scope.sumPayment2();
                                                        			/* open receipt */
                                                        			if(window.location.host.split('.')[0] == 'thomasburke'){
                                                            			$scope.getReceipt = function(payment)
                                                                    	{
                                                                    	    console.log("Fee items",$scope.feeItems);
                                                                    		var data3 = {
                                                                    		    autoprint: true,
                                                                    			student: $scope.student.selected,
                                                                    			payment: $scope.selectedPayment,
                                                                    			feeItems: null // $scope.feeItems.concat($scope.optFeeItems) // fix this (get fee items from api above)
                                                                    		}
                                                                    		var domain = window.location.host;
                                                                    		var dlg = $dialogs.create('https://' + domain + '/app/fees/receipt.html','receiptCtrl',data3,{size: 'md',backdrop:'static'});
                                                                    	}
                                                                    	$scope.getReceipt($scope.selectedPayment);
                                                        			}
                                                                	/* end receipt opening*/
                                                        		}
                                                        		else
                                                        		{
                                                        			$scope.error = true;
                                                        			$scope.errMsg = result.data;
                                                        		}
                                                        	}, apiError);

		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.sumPayment = function()
	{
		$scope.totalApplied = $scope.feeItemsSelection.reduce(function(sum,item){
			if( item.amount == '' ) item.amount = 0;
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		$scope.totalCredit = ( $scope.payment.amount - $scope.totalApplied > 0 ? $scope.payment.amount - $scope.totalApplied : 0);
	}
} ]);
