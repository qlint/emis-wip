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
				// console.log("Banking data success");
				$scope.bank_name = result.data[0].bank_name;
				$scope.bank_branch = result.data[0].bank_branch;
				$scope.account_name = result.data[0].account_name;
				$scope.account_number = result.data[0].account_number;
				// console.log($scope.bank_name + "//" + $scope.bank_branch + "//" + $scope.account_name + "//" + $scope.account_number);
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
			$scope.wantBankDetails = ( window.location.host.split('.')[0] == "localhost:8008" || window.location.host.split('.')[0] == "hog" ? true : false);
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
			wantBankDetails: $scope.wantBankDetails
		}

		// var domain = window.location.host;
		var domain = "localhost:8008/highschool";
		var newWindowRef = window.open('http://' + domain + '/#/fees/invoice/print');
		newWindowRef.printCriteria = criteria;
	}


} ]);
