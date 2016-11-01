'use strict';

angular.module('eduwebApp').
controller('printStatementCtrl', ['$scope', '$rootScope', 'apiService',
function($scope, $rootScope, apiService ){

	
	var initializeController = function()
	{
		var data = window.printCriteria;
		$scope.student = angular.fromJson(data.student);
		
		// get current term
		$rootScope.getCurrentTerm();

		if( $scope.invoices === undefined )
		{
			getInvoices();
		}
		else
		{
			$scope.invoices = angular.fromJson(data.invoices);	
			setInvoiceTotals();
		}
		
		if( $scope.payments === undefined )
		{
			getPayments();
		}
		else
		{
			$scope.payments = angular.fromJson(data.payments);	
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
			$scope.invoices = ( result.nodata ? {} : angular.copy(result.data) );	
			setInvoiceTotals();
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
			var payments = ( result.nodata ? [] : angular.copy(result.data) );
			
			$scope.payments = payments.map(function(item){
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

	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
} ]);