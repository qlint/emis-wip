'use strict';

angular.module('eduwebApp').
controller('datesFormCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data){

	$scope.edit = ( data !== undefined ? true : false );
	$scope.date = ( data !== undefined ? data : {} );
	$scope.canDelete = ( $scope.date.has_exams == '0' ? true : false);

	$scope.initializeController = function()
	{

		if( !$scope.edit )
		{
			var currentDate = moment().format('YYYY-MM-DD');
			$scope.start_date = {startDate: currentDate};
			$scope.end_date = {startDate: currentDate};
		}
		else
		{
			$scope.start_date = {startDate: $scope.date.start_date};
			$scope.end_date = {startDate: $scope.date.end_date};
		}
	}
	$scope.initializeController();

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	$scope.save = function(form)
	{
		if ( !form.$invalid )
		{
			var data = $scope.date;
			console.log("Data >",data);
			data.start_date = moment($scope.start_date.startDate).format('YYYY-MM-DD');
			data.end_date = moment($scope.end_date.startDate).format('YYYY-MM-DD');
			data.term_number = parseInt(data.term_number);

			if( $scope.edit )
			{
				apiService.updateTerm(data,createCompleted,apiError);
			}
			else
			{
				apiService.addTerm(data,createCompleted,apiError);
			}


		}
	}

	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Term was updated.' : 'Term was added.');
			$rootScope.$emit('termAdded', {'msg' : msg, 'clear' : true});
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

	$scope.deleteTerm = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete this term? <br><br><b><i>(THIS CAN NOT BE UNDONE)</i></b>',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deleteTerm($scope.date.term_id, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					var msg = 'Term was deleted.';
					$rootScope.$emit('termAdded', {'msg' : msg, 'clear' : true});
					$uibModalInstance.dismiss('canceled');
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}

			}, apiError);

		});
	}

} ]);
