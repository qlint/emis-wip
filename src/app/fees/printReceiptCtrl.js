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
		$scope.removeHeader = ( window.location.host.split('.')[0] == "thomasburke" ? true : false);
		$scope.receiptMode = $rootScope.currentUser.settings["Use Receipt Items"];
		if($scope.receiptMode == undefined || $scope.receiptMode == "true"){
			$scope.receiptMode = true;
			$scope.itemized = true;
		}else{
			$scope.receiptMode = false;
			$scope.itemized = false;
		}
		$scope.loading = false;

		var adjustBank = document.getElementById('bank_adjust');
		adjustBank.removeAttribute("style");
		adjustBank.style.marginLeft = '49%';
		adjustBank.style.marginTop = '-6.5%';
		document.getElementById('bnkName').style.margin = '0';
		document.getElementById('bnkDate').style.margin = '0';

		setTimeout( function(){
			window.print();

			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 2500);
		}, 2500);
	}
	setTimeout(initializeController,1);

	$scope.$on('$destroy', function() {
		$rootScope.isPrinting = false;
	});



} ]);
