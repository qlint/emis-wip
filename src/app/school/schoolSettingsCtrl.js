'use strict';

angular.module('eduwebApp').
controller('schoolSettingsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','FileUploader',
function($scope, $rootScope, apiService, $timeout, $window, $filter, FileUploader){


	$scope.alert = {};

	var initializeController = function () 
	{
		//var deptCats = $rootScope.currentUser.settings['Department Categories'];
		//$scope.deptCats = deptCats.split(',');

		setSettings();
		
	}
	$timeout(initializeController,1);
	
	var getSettings = function()
	{
		// update the users settings
		apiService.getSettings({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var settings = result.data.reduce(function ( total, current ) { 
					total[ current.name ] = current.value;
					return total;
				}, {});
				
				$rootScope.$emit('setSettings', settings);
				setSettings();
			}		
			
		},apiError);
	}
	
	var setSettings = function()
	{
		$scope.settings	= {
			'Address 1' : angular.copy($rootScope.currentUser.settings['Address 1']),
			'Address 2' : angular.copy($rootScope.currentUser.settings['Address 2']),
			'Country' : angular.copy($rootScope.currentUser.settings['Country']),
			'Curriculum' : angular.copy($rootScope.currentUser.settings['Curriculum']),
			'Email Address' : angular.copy($rootScope.currentUser.settings['Email Address']),
			'Email From' : angular.copy($rootScope.currentUser.settings['Email From']),
			'Phone Number' : angular.copy($rootScope.currentUser.settings['Phone Number']),
			'School Name' : angular.copy($rootScope.currentUser.settings['School Name']),
			'School Type' : angular.copy($rootScope.currentUser.settings['School Type']	),
			'logo' : angular.copy($rootScope.currentUser.settings['logo']	),
		}
	}
	
	$scope.$watch('uploader.queue[0]', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		$scope.schoolForm.$setDirty();
	});
	
	$scope.save = function(theForm)
	{
		$scope.error = false;
		$scope.errMsg = '';
		
		if( !theForm.$invalid )
		{
			// do logo upload
			if( uploader.queue[0] !== undefined )
			{
				uploader.queue[0].file.name = uploader.queue[0].file.name;
				uploader.uploadAll();
			}
			
			var settings = [];
			angular.forEach( $scope.settings, function(item,key){
				
				if( uploader.queue[0] !== undefined && key == 'logo' )
				{
					settings.push({
						name: 'logo',
						value: 'assets/' + uploader.queue[0].file.name,
						append: false
					})
				}
				else
				{
					settings.push({
						name: key,
						value: item,
						append: false
					})
				}
			});
			
			var postData = {
				settings: settings
			}
			$scope.saving = true;
			apiService.updateSettings(postData, createCompleted, apiError);
		}
	}
	
	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			getSettings();
			$scope.schoolForm.$setPristine();
			$scope.saving = false;
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.addDeptCat = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.removeDeptCat = function(item)
	{
		$rootScope.wipNotice();
	}
	
	$scope.addClassCat = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.removeClassCat = function(item)
	{
		// have to check if this category has classes
		// if no classes, delete, and delete any associated exam types and subjects
		// if has classes, check if associated with any students
		// if not, delete, else, can not delete, mark inactive
		var dlg = $dialogs.confirm('Delete Class Category','You are deleting exam type <strong>' + item.exam_type + '</strong>, this <strong>can not be undone</strong>, do you wish to continue?', {size:'sm'});
		dlg.result.then(function(btn){
			
			apiService.deleteExamType(item.exam_type_id, function(response, status){
				var result = angular.fromJson(response);
			
				if( result.response == 'success')
				{	
					getExamTypes();
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, apiError);
		});
	}		

	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': ''
			}]
    });
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });

} ]);