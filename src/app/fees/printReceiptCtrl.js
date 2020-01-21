'use strict';

angular.module('eduwebApp').
controller('printReceiptCtrl', ['$scope', '$rootScope',
function($scope, $rootScope){

	var initializeController = function()
	{
		var data = window.printCriteria;
		$scope.school = window.location.host.split('.')[0];
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
		$scope.receiptAmountInWords = data.receiptAmountInWords;
		$scope.cashierName = data.cashierName;
		$scope.balanceBroughtFwd = data.balanceBroughtFwd;
		$scope.wantReceipt = ( window.location.host.split('.')[0] == "appleton" || window.location.host.split('.')[0] == "hog" || window.location.host.split('.')[0] == "thomasburke" ? true : false);
		$scope.removeHeader = ( window.location.host.split('.')[0] == "thomasburke" ? true : false);
		$scope.receiptMode = $rootScope.currentUser.settings["Use Receipt Items"];
		if($scope.receiptMode == undefined || $scope.receiptMode == "true"){
			$scope.receiptMode = true;
			$scope.itemized = true;
			document.getElementById('wordAmount').style.marginTop = '5%';
			document.getElementById('cashier').style.marginTop = '-1%';
			document.getElementById('balbd').style.paddingTop = '1%';
			document.getElementById('bank_adjust').paddingTop = '2%';
		}else{
			$scope.receiptMode = false;
			$scope.itemized = false;
		}
		$scope.loading = false;

		var adjustBank = document.getElementById('bank_adjust');
		var adjusttermLabels = document.getElementById('termLabels');
		if($scope.itemized == true){
				setTimeout( function(){
			    adjustBank.removeAttribute("style");
	    		adjustBank.style.marginLeft = '60%';
	    		adjustBank.style.marginTop = '-13%';

	    		adjusttermLabels.style.marginLeft = '65%';
	    		adjusttermLabels.style.marginTop = '-9%';
	    		document.getElementById('bnkName').style.margin = '0';
	    		document.getElementById('bnkDate').style.margin = '0';
				document.getElementById('paymentMethod').style.margin = '0';

				document.getElementById('bnkName').style.paddingBottom = '7px';
    		    document.getElementById('bnkDate').style.paddingBottom = '7px';
				document.getElementById('paymentMethod').style.paddingBottom = '7px';
				}, 1000);
		}else{
		    adjustBank.removeAttribute("style");
    		adjustBank.style.marginLeft = '63%';
    		// adjustBank.style.marginTop = '-12%';
				adjustBank.style.marginTop = '-10%';

    		adjusttermLabels.style.marginLeft = '65%';
    		// adjusttermLabels.style.marginTop = '-10%';
				adjusttermLabels.style.marginTop = '-7%';
    		document.getElementById('bnkName').style.margin = '0';
    		document.getElementById('bnkDate').style.margin = '0';
			document.getElementById('paymentMethod').style.margin = '0';

    		document.getElementById('bnkName').style.paddingBottom = '7px';
    		document.getElementById('bnkDate').style.paddingBottom = '7px';
			document.getElementById('paymentMethod').style.paddingBottom = '7px';
		}

		setTimeout( function(){
			console.log("To execute printing");
			window.print();

			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 2000);
		}, 2500);
	}
	setTimeout(initializeController,1);

	$scope.$on('$destroy', function() {
		$rootScope.isPrinting = false;
	});



} ]);
