'use strict';

angular.module('eduwebApp').
controller('printReceiptCtrl', ['$scope', '$rootScope',
function($scope, $rootScope){

	var initializeController = function()
	{
		var data = window.printCriteria;
		$scope.student = angular.fromJson(data.student);		
		$scope.payment = angular.fromJson(data.payment);		
		$scope.feeItems = angular.fromJson(data.feeItems);	
		$scope.paymentItems = angular.fromJson(data.paymentItems);
		$scope.totalAmtKsh = data.totals.totalAmtKsh;
		$scope.totalAmtCts = data.totals.totalAmtCts;
		$scope.balanceDue = data.totals.balanceDue;
		$scope.term_name = data.termName;
		
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