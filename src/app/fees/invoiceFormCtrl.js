'use strict';

angular.module('eduwebApp').
controller('invoiceFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data', '$parse',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $parse){
	
	$scope.student = {};
	$scope.selectedStudent = ( data.selectedStudent !== undefined ? data.selectedStudent : undefined);
	$scope.selectStudent = ( data.selectedStudent !== undefined ? false : true);
	$scope.student.selected = $scope.selectedStudent;
	$scope.invoice = {};
	$scope.invoice.creation_method = "automatic";
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.invoice.date = {startDate: moment().format('YYYY-MM-DD')};
	$scope.invoice.due_date = {startDate: moment().add(1,'months').format('YYYY-MM-DD')};
	$scope.invoiceLineItems = [];
	$scope.totals = {};
	$scope.alert = {};
	
	
	$scope.initializeController = function()
	{
		if( $scope.selectedStudent === undefined )
		{
			apiService.getAllStudents(true, function(response){
				var result = angular.fromJson(response);
				
				if( result.response == 'success')
				{
					$scope.students = ( result.nodata ? {} : $rootScope.formatStudentData(result.data) );				
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, function(){});
		}
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.clearSelect = function(item, $event) 
	{
		$event.stopPropagation(); 

		var item = $parse(item + ".selected");
			item.assign($scope, undefined);
	};
	
	$scope.$watch('student.selected', function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		// grab what should be invoice for next term
		$scope.selectedStudent = $scope.student.selected;
		
		
	});
	
	var getStudentDetails = function()
	{
		
	}
	

	
	$scope.setManual = function()
	{

		$scope.invoice.creation_method = 'manual';
		$scope.hasOverPayment = undefined;
		$scope.overpayment = undefined; 
				
		apiService.getStudentBalance($scope.selectedStudent.student_id, function(response,status)
		{
			$scope.loading = false;		
			var result = angular.fromJson(response);
					
			if( result.response == 'success') 
			{				
				if( result.nodata === undefined )
				{
					$scope.feeSummary = angular.copy(result.data.fee_summary);
					$scope.fees = angular.copy(result.data.fees);
					
					
					// if there is any outstanding balances, add as first line item
					if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) < 0 )
					{
						$scope.hasArrears = true;
					$scope.underpayment = parseFloat($scope.feeSummary.balance);
				
						/*
						$scope.invoiceLineItems.unshift({
							fee_item: 'Outstanding Balance from previous invoice',
							amount: Math.abs(parseFloat($scope.feeSummary.balance))
						});
						*/
					}
					
					// is there an overpayment?
					if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) > 0 )
					{
						$scope.hasOverPayment = true;
						$scope.overpayment = parseFloat($scope.feeSummary.balance);
						$scope.totals.balance = $scope.totals.balance - $scope.overpayment;
					}
				}			
			}
			
			// get student fee items if not already set
			if( $scope.selectedStudent !== undefined && $scope.studentFeeItems === undefined )
			{
				apiService.getStudentFeeItems($scope.selectedStudent.student_id,function(response,status){			
					var result = angular.fromJson(response);							
					if( result.response == 'success')  $scope.studentFeeItems = angular.copy(result.data);
				
				},apiError);
			}
			
			
		}, apiError);
		
		
	}
	
	$scope.setAutomatic = function()
	{
		//$scope.invoiceLineItems = {};
		$scope.invoice.creation_method = 'automatic';
	}
	
	
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
		$scope.hasOverPayment = undefined;
		$scope.overpayment = undefined; 
		
		
		apiService.getStudentBalance($scope.selectedStudent.student_id, function(response,status)
		{
			$scope.loading = false;		
			var result = angular.fromJson(response);
					
			if( result.response == 'success') 
			{
				if( result.nodata === undefined )
				{
					$scope.feeSummary = angular.copy(result.data.fee_summary);
					$scope.fees = angular.copy(result.data.fees);
				}			
			}
		
			var params = $scope.invoice.term + '/' + $scope.student.selected.student_id;
			apiService.generateInvoices(params, displayInvoice, apiError);
			
		}, apiError);
	}
	
	var displayInvoice = function(response,status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success')
		{
			$scope.results = ( result.nodata ? [] : result.data );	
			$scope.invoices = [];
			
			// group results by due date	
			$scope.invoices = $scope.results.reduce(function(sum, item) {		
				var date = angular.copy(item.due_date); // store it to use as our key
				item.amount = item.invoice_amount;
				item.inv_date = {startDate:moment().format('YYYY-MM-DD')};
				item.due_date = {startDate:item.due_date}; // put into object for date selector
				if( sum[date] === undefined ) sum[date] = [];	
				sum[date].push( item );				
				return sum;
			}, {});
			
			$scope.activeInvoice = Object.keys($scope.invoices)[0];	
			
			// if there is any outstanding balances, add as first line item
			
			if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) < 0 )
			{
				$scope.hasArrears = true;
				$scope.underpayment = parseFloat($scope.feeSummary.balance);

			/*
				$scope.invoices[$scope.activeInvoice].unshift({
					fee_item: 'Outstanding Balance from previous invoice',
					amount: Math.abs(parseFloat($scope.feeSummary.balance)),
					inv_date = {startDate:moment().format('YYYY-MM-DD')};
					due_date = {startDate:item.due_date}; // put into object for date selector
				});
				*/
			}
			
			
			// get total of each array in the object
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){				
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.amount));
				},0);				
			});
			
			$scope.invoiceLineItems = $scope.invoices[$scope.activeInvoice];
			$scope.totals.balance = angular.copy($scope.invoiceTotal[$scope.activeInvoice]);
			$scope.totals.invoice = angular.copy($scope.totals.balance);
			
			// is there an overpayment?
			if( $scope.feeSummary &&  parseFloat($scope.feeSummary.balance) === 0 && $scope.feeSummary.unapplied_payments > 0 )
			{
				$scope.hasOverPayment = true;
				$scope.overpayment = parseFloat($scope.feeSummary.unapplied_payments);
				$scope.totals.balance = $scope.totals.balance - $scope.overpayment;
			}
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.getInvoice = function(key)
	{
		$scope.activeInvoice = key;
		$scope.invoiceLineItems = $scope.invoices[key];
		$scope.totals.balance = $scope.invoiceTotal[key];
		$scope.sumInvoice();
	}
	
	$scope.$watch('invoice.newItem',function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		var index = $scope.invoiceLineItems.length - 1;
		$scope.invoiceLineItems[index] = newVal;
		$scope.sumInvoice();
	});	
	
	$scope.removeLineItem = function(index)
	{
		$scope.invoiceLineItems.splice(index,1);
		$scope.sumInvoice();
	}
	

	$scope.sumInvoice = function()
	{
		$scope.totals.balance = $scope.invoiceLineItems.reduce(function(sum,item){
			sum = sum + parseFloat(item.amount);
			return sum;
		},0);
		$scope.totals.invoice = angular.copy($scope.totals.balance);
		
		
		// sum all totals if automatic
		if( $scope.invoice.creation_method == 'automatic' )
		{
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){				
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.amount));
				},0);				
			});
		}
		
		if( $scope.hasOverPayment ) $scope.totals.balance = $scope.totals.balance - $scope.overpayment; 
	}
	
	$scope.addRow = function()
	{		
		if( $scope.studentFeeItems === undefined )
		{
			apiService.getStudentFeeItems($scope.student.selected.student_id,function(response,status){
		
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
	
	
	$scope.save = function()
	{
		$scope.error = false;
		$scope.errMsg = '';
		
		var data = {
			user_id: $scope.currentUser.user_id,
			invoices: []
		};
		var lineItems = [];
		
		
		if( $scope.invoice.creation_method == 'automatic' )
		{
			angular.forEach($scope.invoices, function(items,key){			
				lineItems = [];
				angular.forEach(items, function(item,key2){
					if( item !== null ) 
					{
						lineItems.push({
							student_fee_item_id: item.student_fee_item_id,
							amount: item.amount
						});
					}
				});
				
				data.invoices.push( {
					inv_date: moment( items[0].inv_date.startDate ).format('YYYY-MM-DD'),
					student_id: $scope.selectedStudent.student_id,
					due_date: moment( items[0].due_date.startDate ).format('YYYY-MM-DD'),
					total_amount: $scope.invoiceTotal[key],				
					line_items:lineItems
				});
			});
		}
		else
		{
			angular.forEach($scope.invoiceLineItems, function(item,key){	
				if( item !== null )
				{
					lineItems.push({
						student_fee_item_id: item.student_fee_item_id,
						amount: item.amount
					});	
				}					
			});
			
			data.invoices.push( {
				inv_date: $scope.invoice.date.startDate,
				student_id: $scope.selectedStudent.student_id,
				due_date: $scope.invoice.due_date.startDate,
				total_amount: $scope.totals.balance,				
				line_items:lineItems
			});
		}
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