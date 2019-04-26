'use strict';

angular.module('eduwebApp').
controller('invoiceCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', '$q', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $q, data){
	console.log(data.invoice);
	$scope.invoice = data.invoice;
	$scope.student = data.student;
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.isSchool = window.location.host.split('.')[0];

	var termName = $scope.invoice.term_name;
	// we only want the number
	termName = termName.split(' ');
	$scope.invoice.term_name = termName[1];
  var requests = [];


	var initializeController = function()
	{
    var deferredArrears = $q.defer();
		requests.push(deferredArrears.promise);
		var params = $scope.invoice.student_id + '/' + moment($scope.invoice.inv_date).format('YYYY-MM-DD');
		apiService.getStudentArrears(params, function(response)
		{
			var result = angular.fromJson(response);
			if( result.response == 'success' && result.nodata === undefined )
			{
				$scope.arrears = result.data.balance;
				$scope.hasArrears = $scope.arrears == '0' || $scope.arrears === null ? false : true;
			}
      deferredArrears.resolve();
		}, function(){deferredArrears.reject();});

    var deferredCredits = $q.defer();
    requests.push(deferredCredits.promise);
		apiService.getStudentCredits($scope.invoice.student_id, function(response,status)
		{
			$scope.loading = false;
			var result = angular.fromJson(response);
			if( result.response == 'success' && result.nodata === undefined )
			{
				$scope.availableCredits = result.data;
				$scope.hasCredit = true;
				// sum of available credit
				$scope.credit = $scope.availableCredits.reduce(function(sum,item){
					return sum += parseFloat(item.amount);
				},0);
			}
      deferredCredits.resolve();
		}, function(){deferredCredits.reject();});

		apiService.getBanking({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				console.log("Banking data success");
				console.log(result);
				$scope.bank_name = result.data[0].bank_name;
				$scope.bank_branch = result.data[0].bank_branch;
				$scope.account_name = result.data[0].account_name;
				$scope.account_number = result.data[0].account_number;
				$scope.bank_name_2 = result.data[0].bank_name_2;
				$scope.bank_branch_2 = result.data[0].bank_branch_2;
				$scope.account_name_2 = result.data[0].account_name_2;
				$scope.account_number_2 = result.data[0].account_number_2;
				$scope.mpesa_details = result.data[0].mpesa_details;
				console.log(result.data);
				
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
			}

		},apiError);

    $q.all(requests).then(function () {
			// calcuate grand total
      $scope.grandTotal = parseFloat($scope.invoice.balance) + (parseFloat($scope.arrears) || 0) + (parseFloat($scope.credit) || 0);
		});


		apiService.getInvoiceDetails($scope.invoice.inv_id, loadInvoiceDetails, apiError);

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
			$scope.wantBankDetails = ( window.location.host.split('.')[0] == "appleton" || window.location.host.split('.')[0] == "hog" ? true : false);
			$scope.invoiceLineItems = ( result.nodata ? {} : result.data );

			$scope.custom_invoice_no = $scope.invoiceLineItems[0].custom_invoice_no;
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
			lineItems: $scope.lineItems,
			credit: $scope.credit,
			hasCredit: $scope.hasCredit,
			arrears: $scope.arrears,
			hasArrears: $scope.hasArrears,
            grandTotal: $scope.grandTotal,
			bank_name: $scope.bank_name,
			bank_branch: $scope.bank_branch,
			account_name: $scope.account_name,
			account_number: $scope.account_number,
			custom_invoice_no: $scope.custom_invoice_no,
			bank_name: $scope.bank_name,
			bank_branch: $scope.bank_branch,
			account_name: $scope.account_name,
			account_number: $scope.account_number,
			bank_name_2: $scope.bank_name_2,
			bank_branch_2: $scope.bank_branch_2,
			account_name_2: $scope.account_name_2,
			account_number_2: $scope.account_number_2,
			mpesa_details: $scope.mpesa_details
		}

		var domain = window.location.host;
		var newWindowRef = window.open('https://' + domain + '/#/fees/invoice/print');
		newWindowRef.printCriteria = criteria;
	}


} ]);
