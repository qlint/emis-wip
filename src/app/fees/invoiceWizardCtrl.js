'use strict';

angular.module('eduwebApp').
controller('invoiceWizardCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data', '$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $parse){
	
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	
	$scope.initializeController = function()
	{
		apiService.getNextTerm(undefined, function(response,status){
			var result = angular.fromJson(response);				
			if( result.response == 'success')
			{ 
				$scope.nextTermSet = ( result.nodata !== undefined ? false : true);
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
		var params = $scope.invoice.term;
		apiService.generateInvoices(params, displayInvoice, apiError);
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
			
				if( key > 0 && currentStudent != item.student_id + ' ' + item.due_date )
				{
					// store row
					$scope.results.push({
						student_id: currentItem.student_id,
						student_name: currentItem.student_name,
						due_date: currentItem.due_date,
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
				
				currentStudent = item.student_id + ' ' + item.due_date;
				currentItem = item;				
			});
			// push in last row
			$scope.results.push({
				student_id: currentItem.student_id,
				student_name: currentItem.student_name,
				due_date: currentItem.due_date,
				fee_items: feeItems,
				line_items: lineItems,
				total_amount: total
			});
			
			
			// group results by due date	
			$scope.invoices = $scope.results.reduce(function(sum, item) {	
				var date = angular.copy(item.due_date); // store it to use as our key
				var month = moment(date).format('MMM');
				item.inv_date = {startDate:moment().format('YYYY-MM-DD')};
				item.due_date = {startDate:item.due_date}; // put into object for date selector
					
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
			lineItems = [];
			angular.forEach(invoices, function(invoice,key){
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
					line_items:lineItems
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