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
		$scope.removeHeader = false;
	}else{
		$scope.receiptMode = false;
		$scope.itemized = false;
		$scope.removeHeader = true;
	}

	$scope.numInWords = function NumInWords (number) {
	  const first = ['','One ','Two ','Three ','Four ', 'Five ','Six ','Seven ','Eight ','Nine ','Ten ','Eleven ','Twelve ','Thirteen ','Fourteen ','Fifteen ','Sixteen ','Seventeen ','Eighteen ','Nineteen '];
	  const tens = ['', '', 'Twenty','Thirty','Forty','Fifty', 'Sixty','Seventy','Eighty','Ninety'];
	  const mad = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];
	  let word = '';

	  for (let i = 0; i < mad.length; i++) {
	    let tempNumber = number%(100*Math.pow(1000,i));
	    if (Math.floor(tempNumber/Math.pow(1000,i)) !== 0) {
	      if (Math.floor(tempNumber/Math.pow(1000,i)) < 20) {
	        word = first[Math.floor(tempNumber/Math.pow(1000,i))] + mad[i] + ' ' + word;
	      } else {
	        word = tens[Math.floor(tempNumber/(10*Math.pow(1000,i)))] + '-' + first[Math.floor(tempNumber/Math.pow(1000,i))%10] + mad[i] + ' ' + word;
	      }
	    }

	    tempNumber = number%(Math.pow(1000,i+1));
	    if (Math.floor(tempNumber/(100*Math.pow(1000,i))) !== 0) word = first[Math.floor(tempNumber/(100*Math.pow(1000,i)))] + 'Hundred ' + word;
	  }
			return word + ' Only.';
	}

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
			$scope.cashierName = $rootScope.currentUser.first_name + ' ' + $rootScope.currentUser.last_name;

			var invoiceItems = results.invoice;

			if( invoiceItems.length > 0 )
			{
				var termName = invoiceItems[invoiceItems.length - 1].term_name;
				$scope.term_name = termName; // EDIT -- see ORIGINAL BELOW
				var yearFromTermName = termName.split(' '); // just a test
				// console.log(yearFromTermName.slice(-1)[0]); // just a test - receipt term conflicts
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

						$scope.receiptAmountInWords = $scope.numInWords(Number($scope.payment.amount));
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
			console.log($scope.payment);
			if( $scope.feeSummary )
			{
			    // this is a hck to remove negatives to avoid brackets on the receipt
			    if($scope.feeSummary.balance.charAt(0) === '-')
                {
                 $scope.feeSummary.balance = $scope.feeSummary.balance.substr(1);
                }

				$scope.balanceDue = $scope.feeSummary.balance;
				$scope.balanceBroughtFwd = Number($scope.payment.amount) + Number($scope.balanceDue);

				if( parseFloat($scope.feeSummary.total_credit) > 0 )
				{
					$scope.hasCredit = true;
					$scope.credit = parseFloat($scope.feeSummary.total_credit);
					$scope.balanceBroughtFwd = Number($scope.payment.amount) + Number($scope.credit);
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
			custom_invoice_no: $scope.custom_invoice_no,
			receiptAmountInWords: $scope.receiptAmountInWords,
			cashierName: $scope.cashierName,
			balanceBroughtFwd: $scope.balanceBroughtFwd
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
