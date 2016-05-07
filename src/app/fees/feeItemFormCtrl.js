'use strict';

angular.module('eduwebApp').
controller('feeItemFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.classCatSelection = [];
	$scope.edit = ( data !== undefined ? true : false );
	$scope.item = ( data !== undefined ? data : {} );
	//console.log(data);
	
	$scope.initializeController = function()
	{
	
		var frequencies = $rootScope.currentUser.settings['Frequencies'];
		$scope.frequencies = frequencies.split(',');	
		
		if( !$scope.edit )
		{
			// set defaults for radio buttons
			$scope.item.optional = false;
			$scope.item.new_student_only = false;
			$scope.item.replaceable = false;
		}
		else
		{
			// set class categories
			$scope.item.default_amount = parseFloat(data.default_amount_raw);
			$scope.classCatSelection = data.class_cats_restriction || [];
			//console.log($scope.classCatSelection);
			$scope.isTransport = ( data.fee_item == 'Transport' ? true : false );
			
			if( $scope.isTransport )
			{
				// get transport routes
				apiService.getTansportRoutes({}, function(response){
					var result = angular.fromJson(response);
					
					if( result.response == 'success')
					{
						if( result.nodata !== undefined) 
						{
							$scope.transportRoutes = [];
						}
						else
						{
							$scope.transportRoutes = result.data.map(function(item){
								item.amount = parseFloat(item.amount);
								return item;
							});
						}
					}
					
				}, function(){});
			}
		}
		
		
	}
	$scope.initializeController();
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	$scope.toggleClassCat = function(item) 
	{
		var id = $scope.classCatSelection.indexOf(item);

		// is currently selected
		if (id > -1) {
			$scope.classCatSelection.splice(id, 1);
		}

		// is newly selected
		else {		
			$scope.classCatSelection.push(item);
		}
		//console.log($scope.classCatSelection);
	};
	
	$scope.save = function(form)
	{
		//console.log(form);
		if ( !form.$invalid ) 
		{
			//console.log($scope.classCatSelection);
			var data = $scope.item;			
			data.new_student_only = ( data.new_student_only ? 't' : 'f' );
			data.optional = ( data.optional ? 't' : 'f' );
			data.replaceable = ( data.replaceable ? 't' : 'f' );
			data.class_cats_restriction = $scope.classCatSelection;
			data.user_id = $rootScope.currentUser.user_id;
			//console.log(data);
			
			if( $scope.edit )
			{
				if( $scope.isTransport )
				{
					var routeData = {
						user_id: $rootScope.currentUser.user_id,
						routes: $scope.transportRoutes
					}
					apiService.updateRoutes(routeData,function(response,status){
						var result = angular.fromJson( response );
						if( result.response == 'success' )
						{
							apiService.updateFeeItem(data,createCompleted,apiError);
						}
						else
						{
							$scope.error = true;
							$scope.errMsg = result.data;
						}
						
					},apiError);
				}
				else
				{
					apiService.updateFeeItem(data,createCompleted,apiError);
				}
			}
			else
			{
				apiService.addFeeItem(data,createCompleted,apiError);
			}
			
			
		}
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Fee Item  was updated.' : 'Fee Item  was added.');
			$rootScope.$emit('feeItemAdded', {'msg' : msg, 'clear' : true});
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
	
	$scope.deleteFeeItem = function()
	{
		var data = {
			user_id : $rootScope.currentUser.user_id,
			fee_item_id: $scope.item.fee_item_id,
			status: 'f'
		}
		apiService.setFeeItemStatus(data,createCompleted,apiError);
	}
	
	$scope.activateFeeItem = function()
	{
		var data = {
			user_id : $rootScope.currentUser.user_id,
			fee_item_id: $scope.item.fee_item_id,
			status: 't'
		}
		apiService.setFeeItemStatus(data,createCompleted,apiError);
	}
	
	$scope.addRoute = function()
	{		
		$scope.transportRoutes.push({
			transport_id:undefined,
			route:undefined,
			amount:undefined
		});
	}
	
	$scope.removeRoute = function(index)
	{
		$scope.transportRoutes.splice(index,1);
	}
	
	
} ]);