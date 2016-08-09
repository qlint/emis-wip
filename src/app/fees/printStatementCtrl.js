'use strict';

angular.module('eduwebApp').
controller('printStatementCtrl', ['$scope', '$rootScope', 
function($scope, $rootScope ){

	
	var initializeController = function()
	{
		var data = window.printCriteria;
		$scope.student = angular.fromJson(data.student);		
		$scope.invoices = angular.fromJson(data.invoices);	
		$scope.currency = $rootScope.currentUser.settings['Currency'];

		$scope.todays_date = moment().format('YYYY-MM-DD');
		
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
		
		
		
		setTimeout( function(){
			window.print();
			
			setTimeout( function(){
				$rootScope.isPrinting = false;
				window.close();
			}, 100);
		}, 100);
		
	}
	setTimeout(initializeController,1);
	

	
} ]);