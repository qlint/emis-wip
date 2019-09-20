'use strict';

angular.module('eduwebApp').
controller('viewEmployeeCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader', '$timeout', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, $timeout, data){

	$rootScope.modalLoading = false;
	$scope.tabs = ['Personal Info','Employee Info'];
	$scope.currentTab = $scope.tabs[0];
	$scope.staffLoading = true;

	$scope.hasChanges = false;
	$scope.committees = ($rootScope.currentUser.settings.Committees != null || $rootScope.currentUser.settings.Committees != undefined ? $rootScope.currentUser.settings.Committees.split(',') : []);

	var originalData;
	$scope.filters = {};

	$scope.edit = ($rootScope.permissions.staff.edit ? true : false );

	$scope.employee = {};
	$scope.employee.joined_date = {startDate:''};
	$scope.initLoad = true;

	$scope.initializeController = function()
	{

		apiService.getEmployeeDetails(data.emp_id, function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.employee = angular.copy(result.data);
				$scope.employee.joined_date = {startDate: $scope.employee.joined_date};

				// select emp category
				$scope.employee.emp_cat = $rootScope.empCats.filter(function(item){
					if ( item.emp_cat_id == $scope.employee.emp_cat_id) return item;
				})[0];

				$scope.editUsername = ( result.data.username === null ? true : false);
				$scope.employee.login_active = ( $scope.employee.login_active === null ? true : $scope.employee.login_active);

				$scope.staffLoading = false;

				$timeout(function(){
					$scope.initLoad  = false;
				},100);
			}
		});

		if('Houses' in $rootScope.currentUser.settings){
			if($rootScope.currentUser.settings.Houses !== undefined || $rootScope.currentUser.settings.Houses !== null){
				$scope.houses = $rootScope.currentUser.settings.Houses.split(',');
			}
		}

	}
	$scope.initializeController();


	$scope.getTabContent = function(tab)
	{
		if( !$scope.empForm.$pristine )
		{
			var dlg = $dialogs.confirm('Changes Where Made','You have made changes to the data on this page. Did you want to save these changes?', {size:'sm'});

			dlg.result.then(function(btn){
				 // save the form
				 $scope.save($scope.empForm, tab);

			},function(btn){
				// revert the changes and move on
				$scope.employee = angular.copy(originalData);
				$scope.empForm.$setPristine();
				goToTab(tab);
			});
		}
		else
		{
			goToTab(tab);
		}

	}

	var goToTab = function(tab)
	{
		$scope.currentTab = tab;
		if($scope.currentTab == 'Employee Info'){
		    var config = {
                  '.chosen-select'           : {},
                  '.chosen-select-deselect'  : { allow_single_deselect: true },
                  '.chosen-select-no-single' : { disable_search_threshold: 10 },
                  '.chosen-select-no-results': { no_results_text: 'Oops, nothing found!' },
                  '.chosen-select-rtl'       : { rtl: true },
                  '.chosen-select-width'     : { width: '95%' }
                }

                for (var selector in config) {
                  $$(selector).each(function(element) {
                        new Chosen(element, config[selector]);
                    });
                }
		}
	}

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	$scope.$watch('employee.emp_cat', function(newVal, oldVal){
		if( newVal == oldVal) return;

		if( newVal === undefined || newVal === null || newVal == '' ) 	$scope.departments = $rootScope.allDepts;
		else
		{
			// filter dept to only show those belonging to the selected category
			$scope.departments = $rootScope.allDepts.reduce(function(sum,item){
				if( item.category == newVal.emp_cat_name ) sum.push(item);
				return sum;
			}, []);
			$scope.employee.emp_cat_id = newVal.emp_cat_id;
		}

	});

	$scope.$watch('uploader.queue[0]', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		$scope.empForm.$setDirty();
	});

	$scope.$watch('employee.joined_date', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		if( !$scope.initLoad ) $scope.empForm.$setDirty();
	});


	/************************************* Update Function ***********************************************/
	$scope.save = function(theForm, tab)
	{
		if( !theForm.$invalid )
		{
			// going to only send data that is on the current tab
			if( $scope.currentTab == 'Personal Info' )
			{
				if( uploader.queue[0] !== undefined )
				{
					// need a unique filename
					$scope.filename = $scope.employee.emp_id + "_" + uploader.queue[0].file.name;
					uploader.queue[0].file.name = $scope.filename;
					uploader.uploadAll();
				}

				var postData = {
					emp_id : $scope.employee.emp_id,
					user_id : $rootScope.currentUser.user_id,
					personal : {
						first_name : $scope.employee.first_name,
						middle_name : $scope.employee.middle_name,
						last_name : $scope.employee.last_name,
						initials : $scope.employee.initials,
						id_number : $scope.employee.id_number,
						country : $scope.employee.country,
						gender : $scope.employee.gender,
						dob: $scope.employee.dob,
						emp_image : ( uploader.queue[0] !== undefined ? $scope.filename : null),
						active : ( $scope.employee.active ? 't' : 'f' ),
						telephone : $scope.employee.telephone,
						telephone2 : $scope.employee.telephone2,
						email : $scope.employee.email,
						next_of_kin_name : $scope.employee.next_of_kin_name,
						next_of_kin_telephone : $scope.employee.next_of_kin_telephone,
						next_of_kin_email : $scope.employee.next_of_kin_email,
						house : $scope.employee.house
					}
				}
			}
			else if ( $scope.currentTab == 'Employee Info' )
			{

				var postData = {
					emp_id : $scope.employee.emp_id,
					user_id : $rootScope.currentUser.user_id,
					employee : {
					  active : $scope.employee.active,
						emp_number : $scope.employee.emp_number,
						emp_cat_id : $scope.employee.emp_cat_id,
						emp_id : $scope.employee.emp_id,
						dept_id : $scope.employee.dept_id,
						job_title : $scope.employee.job_title,
						joined_date : ( $scope.employee.joined_date.startDate !== null ? moment($scope.employee.joined_date.startDate).format('YYYY-MM-DD') : null),
						qualifications : $scope.employee.qualifications,
						experience : $scope.employee.experience,
						additional_info : $scope.employee.additional_info,
						first_name : $scope.employee.first_name,
						middle_name : $scope.employee.middle_name,
						last_name : $scope.employee.last_name,
						email : $scope.employee.email,
						username: $scope.employee.username,
						password: $scope.employee.password,
						user_type: $scope.employee.user_type,
						login_active: ( $scope.employee.login_active ? 't' : 'f'),
						id_number : $scope.employee.id_number,
						telephone : $scope.employee.telephone,
						committee : ($scope.employee.committee != null || $scope.employee.committee != undefined ? $scope.employee.committee.join(',') : null),
						subdmn: window.location.host.split('.')[0],
						login_id: $scope.employee.login_id
					}
				}
			}

			apiService.updateEmployee(postData, createCompleted, apiError, {tab:tab});
		}
	}

	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( uploader.queue[0] !== undefined )
			{
				$scope.employee.emp_image = $scope.filename;
			}

			// saved, update the originalData
			originalData = angular.copy($scope.employee);
			$scope.empForm.$setPristine();

			// if moving tabs, continue
			if( params.tab !== undefined ) goToTab(params.tab);

			// refresh the main employee list
			$rootScope.$emit('employeeAdded', {'msg' : 'Employee was updated.', 'clear' : true});
			$scope.initializeController();

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

	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'employees'
			}]
    });

} ]);
