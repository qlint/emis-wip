'use strict';

angular.module('eduwebApp').
controller('examTypesCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','dialogs',
function($scope, $rootScope, apiService, $timeout, $window, $filter, $dialogs){


	$scope.alert = {};

	var initializeController = function () 
	{
		getExamTypes();
	}
	$timeout(initializeController,1);

	var getExamTypes = function()
	{
		if( $scope.dataGrid !== undefined )
		{	
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;			
		}		

		apiService.getExamTypes(undefined, function(response,status,params){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.examTypes = ( result.nodata ? [] : result.data );	

				$timeout(initDataGrid,10);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, apiError);
	}
		
	var initDataGrid = function() 
	{
	
		var tableElement = $('#resultsTable');
		$scope.dataGrid = tableElement.DataTable( {
				responsive: {
					details: {
						type: 'column'
					}
				},
				columnDefs: [ {
					className: 'control',
					orderable: false,
					targets:   0
				},
				{
					targets:1,
					orderable:false
				}				
				],
				paging: false,
				destroy:true,				
				filter: true,
				order:[2,'asc'],
				info: false,
				sorting:[],
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
						search: "Search Results<br>",
						searchPlaceholder: "Filter",
						lengthMenu: "Display _MENU_",
						emptyTable: "No grading entries found."
				},
			} );
			
		
		var headerHeight = $('.navbar-fixed-top').height();
		//var subHeaderHeight = $('.subnavbar-container.fixed').height();
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 13 );
		new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );
		
		
		// position search box
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',0);
		}
		
		$window.addEventListener('resize', function() {
			
			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();
				$('#resultsTable_filter').css('left',filterFormWidth-30);	
			}
		}, false);
		
	}
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	$scope.addExamType = function()
	{
		//$scope.openModal('exam', 'examTypeForm', 'md');
		// show small dialog with add form		
		var dlg = $dialogs.create('addExamType.html','addExamTypeCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(examType){
			
			getExamTypes();
					
		},function(){
			
		});
	}
	
	$scope.deleteExamType = function(item)
	{
		var dlg = $dialogs.confirm('Delete Exam Type','You are deleting exam type <strong>' + item.exam_type + '</strong>, this <strong>can not be undone</strong>, do you wish to continue?', {size:'sm'});
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

	$scope.$on('refreshExamTypes', function(event, args) {

		$scope.loading = true;
		$rootScope.loading = true;
		
		if( args !== undefined )
		{
			$scope.updated = true;
			$scope.notificationMsg = args.msg;
		}
		$scope.refresh();
		
		// wait a bit, then turn off the alert
		$timeout(function() { $scope.alert.expired = true;  }, 2000);
		$timeout(function() { 
			$scope.updated = false;
			$scope.notificationMsg = ''; 
			$scope.alert.expired = false;
		}, 3000);
	});
	
	$scope.refresh = function () 
	{
		$scope.loading = true;
		$rootScope.loading = true;
		getExamTypes();
	}
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });

} ]);