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
	
	$scope.setManual = function()
	{

		$scope.invoice.creation_method='manual';
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
	}
	
	$scope.setAutomatic = function()
	{
		//$scope.invoiceLineItems = {};
		$scope.invoice.creation_method='automatic';
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
		var params = $scope.invoice.term + '/' + $scope.student.selected.student_id;
		apiService.generateInvoices(params, displayInvoice, apiError);
	}
	
	var displayInvoice = function(response,status)
	{
		var result = angular.fromJson(response);
			
		if( result.response == 'success')
		{
			$scope.results = ( result.nodata ? [] : result.data );	
			console.log($scope.results.length);
			
			// group results by due date	
			$scope.invoices = $scope.results.reduce(function(sum, item) {		
				item.amount = item.invoice_amount;
				if( sum[item.due_date] === undefined ) sum[item.due_date] = [];	
				sum[item.due_date].push( item );				
				return sum;
			}, {});
				

			console.log($scope.invoices);
			$scope.activeInvoice = Object.keys($scope.invoices)[0];			
			
			// get total of each array in the object
			$scope.invoiceTotal = {};
			angular.forEach($scope.invoices, function(item,key){				
				$scope.invoiceTotal[key] = item.reduce(function(sum,item){
					return sum = (sum + parseFloat(item.amount));
				},0);				
			});
			console.log($scope.invoiceTotal);
			
			$scope.invoiceLineItems = $scope.invoices[$scope.activeInvoice];
			$scope.totals.balance = $scope.invoiceTotal[$scope.activeInvoice];
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
		console.log(newVal);
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
					lineItems.push({
						student_fee_item_id: item.student_fee_item_id,
						amount: item.amount
					});
				});
				
				data.invoices.push( {
					inv_date: moment().format('YYYY-MM-DD'),
					student_id: $scope.selectedStudent.student_id,
					due_date: key,
					total_amount: $scope.invoiceTotal[key],				
					line_items:lineItems
				});
			});
		}
		else
		{
			console.log($scope.invoiceLineItems);
			angular.forEach($scope.invoiceLineItems, function(item,key){				
				lineItems.push({
					student_fee_item_id: item.student_fee_item_id,
					amount: item.amount
				});		
			});
			
			data.invoices.push( {
				inv_date: $scope.invoice.date.startDate,
				student_id: $scope.selectedStudent.student_id,
				due_date: $scope.invoice.due_date.startDate,
				total_amount: $scope.totals.balance,				
				line_items:lineItems
			});
		}
		console.log(data);
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