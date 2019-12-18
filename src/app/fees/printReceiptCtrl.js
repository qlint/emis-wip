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
		$scope.term_year = data.termYear;
		$scope.credit = data.credit;
		$scope.hasCredit = data.hasCredit;
		$scope.custom_invoice_no = data.custom_invoice_no;
		$scope.wantReceipt = ( window.location.host.split('.')[0] == "appleton" || window.location.host.split('.')[0] == "hog" ? true : false);

		$scope.loading = false;

		setTimeout( function(){
			window.print();

			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 3000);
		}, 3000);
	}
	setTimeout(initializeController,1);

	$scope.$on('$destroy', function() {
		$rootScope.isPrinting = false;
	});



} ]);
