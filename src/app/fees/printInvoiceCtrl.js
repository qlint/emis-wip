'use strict';

angular.module('eduwebApp').
controller('printInvoiceCtrl', ['$scope', '$rootScope',
function($scope, $rootScope){
	
	var initializeController = function()
	{
		var data = window.printCriteria;
		$scope.student = angular.fromJson(data.student);		
		$scope.invoice = angular.fromJson(data.invoice);		
		$scope.lineItems = angular.fromJson(data.lineItems);	
		$scope.invoiceLineItems = angular.fromJson(data.invoiceLineItems);
		$scope.totalAmtKsh = data.totals.totalAmtKsh;
		$scope.totalAmtCts = data.totals.totalAmtCts;
		
		$scope.loading = false;
		
		setTimeout( function(){
			window.print();
			
			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 100);

	}
	setTimeout(initializeController,1);
	
	$scope.$on('$destroy', function() {
		$rootScope.isPrinting = false;
    });
	
	
	
} ]);