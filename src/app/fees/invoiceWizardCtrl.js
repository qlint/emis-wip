'use strict';

angular.module('eduwebApp').
controller('invoiceWizardCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data', '$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $parse){
	
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.filters = {};
	
	$scope.initializeController = function()
	{
		/*
		apiService.getNextTerm(undefined, function(response,status){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{ 
				$scope.nextTermSet = ( result.nodata !== undefined ? false : true);
			}
		}, apiError);
		*/
		apiService.getTerms(undefined, function(response,status)
		{
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata )
			{
				$scope.terms = result.data;
				$rootScope.terms = result.data;
				
				var currentTerm = $scope.terms.filter(function(item){
					if( item.current_term ) return item;
				})[0];
				$scope.filters.term_id = currentTerm.term_id;
			}
		}, apiError);
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel

	$scope.viewStudent = function(student)
	{
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/students/viewStudent.html','viewStudentCtrl',student,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(results){
			// refresh invoice preview
			$scope.generateInvoice();
		},function(){
			$scope.generateInvoice();
		});
			
	}
	
	$scope.generateInvoice = function()
	{
		$scope.error = false;
		$scope.errMsg = '';
		$scope.showResults = false;
		$scope.termId = angular.copy($scope.filters.term_id);
		apiService.generateInvoices($scope.termId, displayInvoice, apiError);
	}
	
	var displayInvoice = function(response,status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success')
		{
			$scope.allResults = ( result.nodata ? [] : result.data );	
			
			var currentStudent = '';
			var currentItem = {};
			$scope.results = [];
			var total = 0;
			var feeItems = [],
				lineItems = [];
			angular.forEach( $scope.allResults, function(item,key){
			
				if( key > 0 && currentStudent != item.student_id + ' ' + item.inv_date )
				{
					// store row
					$scope.results.push({
						student_id: currentItem.student_id,
						student_name: currentItem.student_name,
						inv_date: currentItem.inv_date,
						due_date: moment(currentItem.inv_date).add(1,'month').format('YYYY-MM-DD'),
						fee_items: feeItems,
						line_items: lineItems,
						total_amount: total
					});
					
					// reset
					total = 0;
					feeItems = [];
					lineItems = [];
				}
				
				total = total + parseFloat(item.invoice_amount);
				feeItems.push(item.fee_item);
				lineItems.push(item);
				
				currentStudent = item.student_id + ' ' + item.inv_date;
				currentItem = item;
			});
			// push in last row
			$scope.results.push({
				student_id: currentItem.student_id,
				student_name: currentItem.student_name,
				inv_date: currentItem.inv_date,
				due_date: moment(currentItem.inv_date).add(1,'month').format('YYYY-MM-DD'),
				fee_items: feeItems,
				line_items: lineItems,
				total_amount: total
			});
			
			
			// group results by due date
			$scope.invoices = $scope.results.reduce(function(sum, item) {
				var date = angular.copy(item.inv_date); // store it to use as our key
				var month = moment(date).format('MMM');
				item.inv_date = {startDate:item.inv_date}; // put into object for date selector
				item.due_date = {startDate: moment(item.inv_date).add(1,'month').format('YYYY-MM-DD')};
				
				if( sum[month] === undefined ) sum[month] = [];	
				sum[month].push( item );
				return sum;
			}, {});

			
			$scope.activeMonth = Object.keys($scope.invoices)[0];
			
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){
				
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					return sum = (parseInt(sum) + parseInt(item.total_amount));
				},0);
			});
			
			$scope.showResults = true;
			
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.getInvoice = function(key)
	{
		$scope.activeMonth = key;
	}
	
	$scope.save = function()
	{
		$scope.error = false;
		$scope.errMsg = '';
		
		var data = {
			user_id: $scope.currentUser.user_id,
			invoices: []
		};
		var lineItems = [];

		angular.forEach($scope.invoices, function(invoices,key){
			angular.forEach(invoices, function(invoice,key){
				lineItems = [];
				angular.forEach(invoice.line_items, function(item,key2){
					if( item !== null ){
						lineItems.push({
							student_fee_item_id: item.student_fee_item_id,
							amount: item.invoice_amount
						});
					}
				});
				
				data.invoices.push( {
					inv_date: moment( invoices[0].inv_date.startDate ).format('YYYY-MM-DD'),
					student_id: invoice.student_id,
					due_date: moment( invoices[0].due_date.startDate ).format('YYYY-MM-DD'),
					total_amount: invoice.total_amount,
					line_items:lineItems,
					term_id: $scope.termId
				});
				
			});
		});

		apiService.createInvoice(data,createCompleted,apiError);
		
	}
	
	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice(s) were created.', 'clear' : true});
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
	
}]);