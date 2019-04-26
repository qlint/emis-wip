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
		$scope.hasCredit = data.hasCredit;
		$scope.credit = data.credit;
		$scope.hasArrears = data.hasArrears;
		$scope.arrears = data.arrears;
        $scope.grandTotal = data.grandTotal;
		$scope.bank_name = data.bank_name;
		$scope.bank_branch = data.bank_branch;
		$scope.account_name = data.account_name;
		$scope.account_number = data.account_number;
		$scope.custom_invoice_no = data.custom_invoice_no;
		$scope.wantBankDetails = ( window.location.host.split('.')[0] == "appleton" || window.location.host.split('.')[0] == "hog" ? true : false);
		$scope.bank_name = data.bank_name;
		$scope.bank_branch = data.bank_branch;
		$scope.account_name = data.account_name;
		$scope.account_number = data.account_number;
		$scope.bank_name_2 = data.bank_name_2;
		$scope.bank_branch_2 = data.bank_branch_2;
		$scope.account_name_2 = data.account_name_2;
		$scope.account_number_2 = data.account_number_2;
		$scope.mpesa_details = data.mpesa_details;
		
		var bankOne = document.getElementById("bank_one");
		var bankTwo = document.getElementById("bank_two");
		var bankThree = document.getElementById("bank_three");
		var allBanks = document.getElementById("printDetails");
				
		if( $scope.bank_name == null || $scope.bank_name == undefined ){
			// We hide this div and convert the next div from col-6 to col-12
			bankOne.style.display = 'none';
			bankTwo.className = "col_md_12";
		}else if( $scope.bank_name_2 == null || $scope.bank_name_2 == undefined ){
			// We hide this div and convert te previous div from col-6 to col-12
			bankTwo.style.display = 'none';
			bankOne.className = "col_md_12";
		}else if( $scope.mpesa_details == null || $scope.mpesa_details == undefined){
			// We hide the mpesa div
			bankThree.style.display = 'none';
		}else if( $scope.bank_name_2 == null || $scope.bank_name_2 == undefined && $scope.mpesa_details == null || $scope.mpesa_details == undefined){
			// We hide the second div and mpesa div and convert the remaining first div to col-12
			bankTwo.style.display = 'none';
			bankThree.style.display = 'none';
			bankOne.className = "col_md_12";
		}else if( $scope.bank_name == null || $scope.bank_name == undefined && $scope.bank_name_2 == null || $scope.bank_name_2 == undefined && $scope.mpesa_details == null || $scope.mpesa_details == undefined ){
			// No details have been entered at all - we hide the entire div
			allBanks.style.display = 'none';
		}

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
