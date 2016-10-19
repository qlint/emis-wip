'use strict';

angular.module('eduwebApp').
controller('invoiceDetailsCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data', '$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $parse){
	
	$scope.invoice = data;
	$scope.date = {startDate: $scope.invoice.inv_date};
	$scope.due_date = {startDate: $scope.invoice.due_date};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.edit = ( $scope.permissions.fees.invoices.edit !== undefined ? $scope.permissions.fees.invoices.edit  : false);
	var allLineItems = undefined;
	$scope.totals = {};
	$scope.overpayment = 0;

	
	// can no longer edit an invoice if it is fully paid
	if( $scope.invoice.balance == 0 && $scope.invoice.total_paid > 0 ) $scope.edit = false;
	
	$scope.$watch('invoice.newItem',function(newVal,oldVal){

		if( newVal == oldVal ) return;
		
		var index = $scope.invoiceLineItems.length - 1;
		//newVal.amount = newVal.amount * newVal.frequency;
		$scope.invoiceLineItems[index] = newVal;
		$scope.sumInvoice();
	});
	
	$scope.initializeController = function()
	{
		apiService.getStudentBalance($scope.invoice.student_id, function(response,status)
		{
			$scope.loading = false;		
			var result = angular.fromJson(response);
					
			if( result.response == 'success') 
			{
				if( result.nodata === undefined )
				{
					$scope.feeSummary = angular.copy(result.data.fee_summary);
				}			
			}
			
			
			apiService.getInvoiceDetails($scope.invoice.inv_id, function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{
					$scope.invoiceLineItems = ( result.nodata ? {} : result.data );	
					allLineItems = angular.copy($scope.invoiceLineItems);
					
					$scope.totals.balance = angular.copy($scope.invoice.balance);
					$scope.totals.total_due = angular.copy($scope.invoice.total_due);
					
					// is there an overpayment?
					if( $scope.feeSummary && $scope.feeSummary.unapplied_payments > 0 )
					{
						$scope.hasOverPayment = true;
						$scope.overpayment = parseFloat($scope.feeSummary.unapplied_payments);
						$scope.totals.balance = Math.abs($scope.totals.balance) - $scope.overpayment;
						$scope.invoice.balance = angular.copy($scope.totals.balance);
					}
					
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, apiError);
			
		}, apiError);
		
		apiService.getTerms(undefined, function(response,status)
		{
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata )
			{
				$scope.terms = result.data;
			}
		}, apiError);
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.cancelInvoice = function()
	{
		var dlg = $dialogs.confirm('Cancel Invoice', 'Are you sure you want to mark this invoice as <strong>canceled</strong>?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				inv_id:$scope.invoice.inv_id
			};
			apiService.cancelInvoice(data,  function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{
					$scope.invoice.canceled = true;
					$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice canceled.', 'clear' : true});
				}
				
			}, apiError);	
		});
	}
	
	$scope.reactivateInvoice = function()
	{
		var dlg = $dialogs.confirm('Activate Invoice', 'Are you sure you want to mark this invoice as <strong>active</strong>?', {size:'sm'});
		dlg.result.then(function(btn){
			var data = {
				user_id: $scope.currentUser.user_id,
				inv_id:$scope.invoice.inv_id
			};
		
			apiService.reactivateInvoice(data,  function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{
					$scope.invoice.canceled = false;
					$rootScope.$emit('invoiceAdded', {'msg' : 'Invoice activated.', 'clear' : true});
				}
				
			}, apiError);		 
		});
	}
	
	$scope.removeLineItem = function(index)
	{
		$scope.invoiceLineItems.splice(index,1);
		$scope.sumInvoice();
		$scope.changes = true;
	}
	
	$scope.revertInvoice = function(index)
	{
		$scope.invoiceLineItems = allLineItems;
		$scope.sumInvoice();
		$scope.changes = false;
	}
	
	$scope.sumInvoice = function()
	{
		$scope.totals.total_due = $scope.invoiceLineItems.reduce(function(sum,item){
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		$scope.totals.balance = ($scope.invoice.total_paid + $scope.overpayment) - $scope.totals.total_due;
		$scope.invoice.balance = angular.copy($scope.totals.balance);
		$scope.changes = true;
	}
	
	$scope.addRow = function(forceRefresh)
	{
		if( $scope.studentFeeItems === undefined || forceRefresh )
		{
			apiService.getStudentFeeItems($scope.invoice.student_id,function(response,status){
		
				var result = angular.fromJson(response);
						
				if( result.response == 'success') 
				{
					$scope.studentFeeItems = angular.copy(result.data);
				}

			
			},apiError);
		}
		$scope.invoiceLineItems.push({
			fee_item:undefined,
			amount:undefined
		});
	}
	
	$scope.viewStudent = function(student,index)
	{
		$scope.removeLineItem(index);
				
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/students/viewStudent.html','viewStudentCtrl',student,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(results){
			// refresh invoice preview
			$scope.addRow(true);
			
		},function(){
			$scope.addRow(true);
		});
		
	}
	
	$scope.printInvoice = function()
	{
		// get the student and invoice line items
		apiService.getStudentDetails($scope.invoice.student_id, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				var student = $rootScope.formatStudentData([result.data]);
				
				// set current class to full class object
				var currentClass = $rootScope.allClasses.filter(function(item){
					if( item.class_id == student[0].class_id ) return item;
				});
				
				student[0].current_class = currentClass[0];

				$scope.student = student[0];
				
				// open up invoice
				var data = {
					student: $scope.student,
					invoice: $scope.invoice
				}	
				$dialogs.create('/app/fees/invoice.html','invoiceCtrl',data,{size: 'md',backdrop:'static'});	
			}
		});
	}
	
	$scope.save = function()
	{
		$scope.error = false;
		$scope.errMsg = '';
		
		
		var lineItems = [];

		angular.forEach($scope.invoiceLineItems, function(item,key){
			lineItems.push({
				student_fee_item_id: item.student_fee_item_id,
				inv_item_id: item.inv_item_id,
				amount: item.amount
			});
		});
		
		var data = {
			user_id: $scope.currentUser.user_id,
			inv_id: $scope.invoice.inv_id,
			total_amount: $scope.totals.total_due,
			inv_date: moment($scope.date.startDate).format('YYYY-MM-DD'),
			due_date: moment($scope.due_date.startDate).format('YYYY-MM-DD'),
			line_items: lineItems,
			term_id: $scope.invoice.term_id
		};
		
		
		apiService.updateInvoice(data,createCompleted,apiError);
		
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