'use strict';

angular.module('eduwebApp').
controller('receiptCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, data){

	console.log(data);
	$scope.payment = data.payment;
	$scope.student = data.student;
	$scope.feeItems = data.feeItems;
	
	var initializeController = function()
	{
		apiService.getPaymentDetails($scope.payment.payment_id, loadPaymentDetails, apiError);
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
			var invoiceItems = results.invoice;
			
			$scope.paymentItems = [];
			var totalAmt = 0;
			var amt;
			angular.forEach( $scope.paymentDetails, function(item,key){
				amt = item.line_item_amount.split('.'),
				$scope.paymentItems[item.fee_item] = {
					ksh: amt[0],
					cts: amt[1]
				};
				totalAmt += parseFloat(item.line_item_amount);
			});

			var amt = ( String(totalAmt).indexOf('.') > -1 ? String(totalAmt).split('.') : [totalAmt,'00']);
			$scope.totalAmtKsh = amt[0];
			$scope.totalAmtCts = amt[1];
			
			if( invoiceItems.length > 0 )
			{
				var invoiceTotal = invoiceItems[0].total_due;
				$scope.balanaceDue = invoiceTotal - $scope.payment.amount;
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
			totals : {
				totalAmtKsh: $scope.totalAmtKsh,
				totalAmtCts: $scope.totalAmtCts,
				balanceDue : $scope.balanceDue
			},
			feeItems: $scope.feeItems
		}

		var domain = window.location.host;
		var newWindowRef = window.open('http://' + domain + '/#/fees/receipt/print');
		newWindowRef.printCriteria = criteria;
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
	
} ]);