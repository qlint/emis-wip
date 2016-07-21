'use strict';

angular.module('eduwebApp').
controller('invoiceCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, data){

	$scope.invoice = data.invoice;
	$scope.student = data.student;
	
	var initializeController = function()
	{	
		var params =  $scope.invoice.inv_id;
		apiService.getInvoiceDetails(params, loadInvoiceDetails, apiError);
	}
	setTimeout(initializeController,1);
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	var loadInvoiceDetails = function(response, status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success')
		{
			$scope.invoiceLineItems = ( result.nodata ? {} : result.data );
						
			$scope.lineItems = [];
			var totalAmt = 0;
			var amt;
			angular.forEach( $scope.invoiceLineItems, function(item,key){
				amt = item.amount.split('.'),
				$scope.lineItems[item.fee_item] = {
					ksh: amt[0],
					cts: amt[1]
				};
				totalAmt += parseFloat(item.amount);
			});

			var amt = ( String(totalAmt).indexOf('.') > -1 ? String(totalAmt).split('.') : [totalAmt,'00']);
			$scope.totalAmtKsh = amt[0];
			$scope.totalAmtCts = amt[1];
			
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	$scope.print = function()
	{
		var criteria = {
			student : $scope.student,
			invoice: $scope.invoice,
			invoiceLineItems : $scope.invoiceLineItems,
			totals : {
				totalAmtKsh: $scope.totalAmtKsh,
				totalAmtCts: $scope.totalAmtCts,
			},
			lineItems: $scope.lineItems
		}

		var domain = window.location.host;
		var newWindowRef = window.open('http://' + domain + '/#/fees/invoice/print');
		newWindowRef.printCriteria = criteria;
	}
	
	
} ]);