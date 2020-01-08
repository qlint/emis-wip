'use strict';

angular.module('eduwebApp').
controller('receiptCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, data){

	$scope.payment = data.payment;
	$scope.student = data.student;
	$scope.feeItems = data.feeItems;
	$scope.receiptMode = $rootScope.currentUser.settings["Use Receipt Items"];
	if($scope.receiptMode == undefined || $scope.receiptMode == "true"){
		$scope.receiptMode = true;
		$scope.itemized = true;
	}else{
		$scope.receiptMode = false;
		$scope.itemized = false;
	}
	$scope.removeHeader = false;

	var initializeController = function()
	{
		apiService.getStudentBalance($scope.student.student_id, function(response,status)
		{
			$scope.loading = false;
			var result = angular.fromJson(response);

			if( result.response == 'success' && result.nodata === undefined )
			{
				$scope.feeSummary = angular.copy(result.data.fee_summary);
			}

			apiService.getPaymentDetails($scope.payment.payment_id, loadPaymentDetails, apiError);
		}, apiError);


	}
	setTimeout(initializeController,1);

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	var loadPaymentDetails = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			var results = ( result.nodata ? {} : result.data );

			$scope.paymentDetails = results.paymentItems;
			$scope.payment.slip_cheque_no = results.payment.slip_cheque_no; // the transaction # for the mode of payment
			$scope.payment.payment_method = results.payment.payment_method; // drop down for mode of payment
			$scope.payment.payment_bank = results.payment.payment_bank;
			$scope.payment.banking_date = results.payment.banking_date;
			$scope.payment.custom_receipt_no = "Receipt #: " + results.payment.custom_receipt_no; // for schools that want to use custom receipt #'s
			$scope.wantReceipt = ( window.location.host.split('.')[0] == "appleton" || window.location.host.split('.')[0] == "hog" ? true : false);

			var invoiceItems = results.invoice;

			if( invoiceItems.length > 0 )
			{
				var termName = invoiceItems[invoiceItems.length - 1].term_name;
				$scope.term_name = termName; // EDIT -- see ORIGINAL BELOW
				var yearFromTermName = termName.split(' '); // just a test
				console.log(yearFromTermName.slice(-1)[0]); // just a test - receipt term conflicts
				// we only want the number
				termName = termName.split(' ');
				// console.log("The invoice has " + invoiceItems.length + " items");
				// $scope.term_name = (invoiceItems.length > 0 ? termName[1] : ''); // ORIGINAL

				// $scope.term_year = (invoiceItems.length > 0 ? invoiceItems[0].term_year : '');
				$scope.term_year = (invoiceItems.length > 0 ? invoiceItems[invoiceItems.length-1].term_year : ''); // using last item in arr as term year
				/*
				var invoiceTotal = invoiceItems.reduce(function(sum,item){
					sum += parseFloat(item.line_item_amount);
					return sum;
				},0);
				$scope.balanceDue = $scope.payment.amount - invoiceTotal;
				*/
			}


			$scope.paymentItems = [];
			if( $scope.paymentDetails.length > 0 )
			{
				/* nested api start */
				apiService.getInvoiceDetails($scope.paymentDetails[0].inv_id, function(response){
					var result = angular.fromJson(response);

					if( result.response == 'success')
					{
						$scope.invoiceLineItems = ( result.nodata ? {} : result.data );
						$scope.custom_invoice_no = $scope.invoiceLineItems[0].custom_invoice_no;

						/* receipt data start */
						var totalAmt = 0;
						var amt;
						angular.forEach( $scope.paymentDetails, function(item,key){
							amt = item.line_item_amount.split('.'),
							$scope.paymentItems.push( {
								inv_id: item.inv_id,
								custom_invoice_no: $scope.custom_invoice_no,
								fee_item: item.fee_item,
								ksh: amt[0],
								cts: amt[1]
							});
							totalAmt += parseFloat(item.line_item_amount);
						});

						var amt = ( String(totalAmt).indexOf('.') > -1 ? String(totalAmt).split('.') : [totalAmt,'00']);
						$scope.totalAmtKsh = amt[0];
						$scope.totalAmtCts = amt[1];
						/* receipt data end */

					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}

				}, apiError);
				/* nested api end */
				// var totalAmt = 0;
				// var amt;
				// angular.forEach( $scope.paymentDetails, function(item,key){
				// 	amt = item.line_item_amount.split('.'),
				// 	$scope.paymentItems.push( {
				// 		inv_id: item.inv_id,
				// 		custom_invoice_no: item.custom_invoice_no,
				// 		fee_item: item.fee_item,
				// 		ksh: amt[0],
				// 		cts: amt[1]
				// 	});
				// 	totalAmt += parseFloat(item.line_item_amount);
				// });
				//
				// var amt = ( String(totalAmt).indexOf('.') > -1 ? String(totalAmt).split('.') : [totalAmt,'00']);
				// $scope.totalAmtKsh = amt[0];
				// $scope.totalAmtCts = amt[1];
			}
			else
			{
				var amt = $scope.payment.amount.split('.');
				$scope.paymentItems.push( {
					inv_id: '-',
					fee_item: 'Credit',
					ksh: amt[0],
					cts: amt[1]
				});
			}

			// is there a credit
			if( $scope.feeSummary )
			{
			    // this is a hck to remove negatives to avoid brackets on the receipt
			    if($scope.feeSummary.balance.charAt(0) === '-')
                {
                 $scope.feeSummary.balance = $scope.feeSummary.balance.substr(1);
                }

				$scope.balanceDue = $scope.feeSummary.balance;

				if( parseFloat($scope.feeSummary.total_credit) > 0 )
				{
					$scope.hasCredit = true;
					$scope.credit = parseFloat($scope.feeSummary.total_credit);
					//$scope.balanceDue = -(Math.abs($scope.balanceDue) - $scope.credit);
				}
			}

		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.print = function()
	{
		var criteria = {
			student : $scope.student,
			payment: $scope.payment,
			paymentItems : $scope.paymentItems,
			termName: $scope.term_name,
			termYear: $scope.term_year,
			totals : {
				totalAmtKsh: $scope.totalAmtKsh,
				totalAmtCts: $scope.totalAmtCts,
				balanceDue : $scope.balanceDue
			},
			feeItems: $scope.feeItems,
			credit: $scope.credit,
			hasCredit: $scope.hasCredit,
			custom_invoice_no: $scope.custom_invoice_no
		}

		var domain = window.location.host;
		var newWindowRef = window.open('https://' + domain + '/#/fees/receipt/print');
		newWindowRef.printCriteria = criteria;
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}



} ]);
