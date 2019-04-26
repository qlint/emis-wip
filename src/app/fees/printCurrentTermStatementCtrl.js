'use strict';

angular.module('eduwebApp').
controller('printCurrentTermStatementCtrl', ['$scope', '$rootScope', 'apiService',
function($scope, $rootScope, apiService ){

	apiService.getTerms(undefined, function(response){
    			var result = angular.fromJson(response);
    			if( result.response == 'success')
    			{ 
    				$scope.currentTerm = result.data.filter(function(item){
    					if( item.current_term ) return item;
    				})[0];
    				console.log($scope.currentTerm);
    				return $scope.currentTerm;
    			}
    			return $scope.currentTerm;
    			
    		}, function(){});
    		
	var initializeController = function()
	{
		var data = window.printCriteria;
		$scope.student = angular.fromJson(data.student);
		
		// get current term
		$rootScope.getCurrentTerm();

		if( $scope.invoices === undefined )
		{
		    getInvoicesUnfiltered();
			getInvoices();
		}
		else
		{
			$scope.invoices = angular.fromJson(data.invoices);	
			$scope.invoicesUnfiltered = angular.fromJson(data.invoices);	
			setInvoiceTotalsUnfiltered();
			setInvoiceTotals();
		}
		
		if( $scope.payments === undefined )
		{
		    getPaymentsUnfiltered();
			getPayments();
		}
		else
		{
			$scope.payments = angular.fromJson(data.payments);	
			setPaymentTotalsUnfiltered();
			setPaymentTotals();
		}
		
		if( $scope.credits === undefined )
		{
			getCredits();
		}
		else
		{
			$scope.credits = angular.fromJson(data.credits);	
			setCreditTotals();
		}
		
		
		$scope.currency = $rootScope.currentUser.settings['Currency'];
		$scope.todays_date = moment().format('YYYY-MM-DD');
		
	}
	setTimeout(initializeController,1);
	
	var getInvoices = function()
	{
		apiService.getStudentInvoices($scope.student.student_id, loadInvoices, apiError);
	}
	
	var loadInvoices = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);				
		if( result.response == 'success')
		{
			$scope.invoices_raw = ( result.nodata ? {} : angular.copy(result.data) );
			// console.log($scope.invoices);
    		
			var currTerm = $scope.currentTerm.term_id.toString();
			$scope.invoices = $.grep($scope.invoices_raw, function(element, index){ return element.term_id != currTerm}, true);
            // console.log($scope.invoices);
			setInvoiceTotals();
		}
	}
	
	var getInvoicesUnfiltered = function()
	{
		apiService.getStudentInvoices($scope.student.student_id, loadInvoicesUnfiltered, apiError);
	}
	
	var loadInvoicesUnfiltered = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);				
		if( result.response == 'success')
		{
			$scope.invoicesUnfiltered = ( result.nodata ? {} : angular.copy(result.data) );	
			setInvoiceTotalsUnfiltered();
		}
	}
	
	var setInvoiceTotals = function()
	{
		// we don't want to show invoices that are canceled on the statement
		$scope.invoices = $scope.invoices.filter(function(item){
			if( !item.canceled ) return item;
		});
		
		
		$scope.totalAmt = $scope.invoices.reduce(function(sum,item){
			sum += parseFloat(item.total_due);
			return sum;
		},0);
		$scope.totalPaid = $scope.invoices.reduce(function(sum,item){
			sum += parseFloat(item.total_paid);
			return sum;
		},0);
		$scope.totalBalance = $scope.invoices.reduce(function(sum,item){
			sum += parseFloat(item.balance);
			return sum;
		},0);
	}
	
	var setInvoiceTotalsUnfiltered = function()
	{
		// we don't want to show invoices that are canceled on the statement
		$scope.invoicesUnfiltered = $scope.invoicesUnfiltered.filter(function(item){
			if( !item.canceled ) return item;
		});
		
		
		$scope.totalAmtUnfiltered = $scope.invoicesUnfiltered.reduce(function(sum,item){
			sum += parseFloat(item.total_due);
			return sum;
		},0);
		$scope.totalPaidUnfiltered = $scope.invoicesUnfiltered.reduce(function(sum,item){
			sum += parseFloat(item.total_paid);
			return sum;
		},0);
		$scope.totalBalanceUnfiltered = $scope.invoicesUnfiltered.reduce(function(sum,item){
			sum += parseFloat(item.balance);
			return sum;
		},0);
	}
	
	var getPayments = function()
	{
		apiService.getStudentPayments($scope.student.student_id, loadPayments, apiError);
	}
	
	var loadPayments = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);
				
		if( result.response == 'success') 
		{
			var payments_raw = ( result.nodata ? [] : angular.copy(result.data) );
			
			// console.log(payments_raw);
            getInvoices();
            console.log($scope.invoices);
            
            var curr2Term = $scope.currentTerm.term_id.toString();
            var filter2InvoicesForCurrentTerm = $.grep($scope.invoices, function(element, index){ return element.term_id != curr2Term}, true);
            console.log(filter2InvoicesForCurrentTerm);
            
            // our required invoices for the current term
            var invsCheckArr = [];
            var checkAgainstInvs = filter2InvoicesForCurrentTerm.forEach(function(invs) { invsCheckArr.push(invs.inv_id.toString()); });
            console.log(invsCheckArr);
            
            // our required payments that match the above invoices
            var pymntsCheckArr = [];
            var checkAgainstPymnts = payments_raw.forEach(function(pymnts) { pymntsCheckArr.push(pymnts.applied_to); });
            console.log(pymntsCheckArr);
            
            // matching the above payments to their respective invoices
            var currTermPayments = [];
            payments_raw.forEach(function(eachPymnt){ 
                var p;
                for (p = 0; p < invsCheckArr.length; p++) {
        			if( eachPymnt.applied_to.includes(invsCheckArr[p])==true ){
                        currTermPayments.push(eachPymnt);
                    }
        		}
        		
            });
            console.log(currTermPayments);
			
			$scope.payments = currTermPayments.map(function(item){
				item.replacement = ( item.replacement_payment ? 'Yes' : 'No');
				item.reverse = ( item.reversed ? 'Yes' : 'No');
				item.receipt_number = $rootScope.zeroPad(item.payment_id,5);
				return item;
			});
			
			
			
			setPaymentTotals();
		}
	}
	
	var setPaymentTotals = function()
	{
		$scope.totalPayments = $scope.payments.reduce(function(sum,item){
			if( item.payment_method != 'Credit' ) sum += parseFloat(item.amount);
			return sum;
		},0);
		
		$scope.grandTotalBalance = $scope.totalAmt - $scope.totalPayments;
		
		
		setTimeout( function(){
			window.print();
			
			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 500);
		
	}
	
	var getCredits = function()
	{
		apiService.getStudentCredits($scope.student.student_id, loadCredits, apiError);
	}
	
	var loadCredits = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);
				
		if( result.response == 'success') 
		{
			var credits = ( result.nodata ? [] : angular.copy(result.data) );
			
			// only show credit available
			$scope.credits = credits.filter(function(item){
				if( parseFloat(item.amount) > 0 ) return item;
			});
			
			setCreditTotals();
		}
	}
	
	var setCreditTotals = function()
	{
		$scope.totalCredits = $scope.credits.reduce(function(sum,item){
			sum += parseFloat(item.amount);
			return sum;
		},0);
		
		$scope.hasCredit = ( $scope.totalCredits > 0 ? true : false );
		
		/*
		setTimeout( function(){
			window.print();
			
			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 100);
		*/
	}
	
	var getPaymentsUnfiltered = function()
	{
		apiService.getStudentPayments($scope.student.student_id, loadPaymentsUnfiltered, apiError);
	}
	
	var loadPaymentsUnfiltered = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);
				
		if( result.response == 'success') 
		{
			var paymentsUnfiltered = ( result.nodata ? [] : angular.copy(result.data) );
			
			$scope.paymentsUnfiltered = paymentsUnfiltered.map(function(item){
				item.replacement = ( item.replacement_payment ? 'Yes' : 'No');
				item.reverse = ( item.reversed ? 'Yes' : 'No');
				item.receipt_number = $rootScope.zeroPad(item.payment_id,5);
				return item;
			});
			
			
			
			setPaymentTotalsUnfiltered();
		}
	}
	
	var setPaymentTotalsUnfiltered = function()
	{
		$scope.totalPaymentsUnfiltered = $scope.paymentsUnfiltered.reduce(function(sum,item){
			if( item.payment_method != 'Credit' ) sum += parseFloat(item.amount);
			return sum;
		},0);
		
		$scope.grandTotalBalanceUnfiltered = $scope.totalAmtUnfiltered - $scope.totalPaymentsUnfiltered;
		// alert("Our unfiltered grand total = " + $scope.grandTotalBalanceUnfiltered + " since the total amount = " + $scope.totalAmtUnfiltered + " and the total payments = " + $scope.totalPaymentsUnfiltered);
		
		
		setTimeout( function(){
			window.print();
			
			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 500);
		
	}

	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
} ]);