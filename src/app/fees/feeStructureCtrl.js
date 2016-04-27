'use strict';

angular.module('eduwebApp').
controller('feeStructureCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter',
function($scope, $rootScope, apiService, $timeout, $window, $filter){


	$scope.alert = {};
	$scope.currency = $rootScope.currentUser.settings['Currency'];

	var initializeController = function () 
	{
		getFeeStructure();
	}
	$timeout(initializeController,1);

	var getFeeStructure = function()
	{
		if( $scope.dataGrid !== undefined )
		{	
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;			
		}		

		apiService.getFeeItems({}, function(response,status,params){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.items = ( result.nodata ? [] : result.data.required_items.concat(result.data.optional_items));	
				console.log($scope.items);
				
				if( $scope.items.length > 0 )
				{
					$scope.items = $scope.items.map(function(item){
						// format the class restrictions into any array
						if( item.class_cats_restriction !== null )
						{
							var classCatsRestriction = (item.class_cats_restriction).slice(1, -1);
							item.class_cats_restriction = classCatsRestriction.split(',');
							
							item.class_categories = [];
							angular.forEach(item.class_cats_restriction, function(classCat,key){
								angular.forEach( $rootScope.classCats, function(classCat2,key2){
									if( classCat == classCat2.class_cat_id ) item.class_categories.push( classCat2.class_cat_name);
								});
								// make an integer for edit form
								item.class_cats_restriction[key] = parseInt(classCat);
							});
						}
						else
						{
							item.class_categories = 'All';
						}
						item.new_student = ( item.new_student_only ? 'Yes':'-');
						
						item.default_amount_raw = item.default_amount;
						
						if( item.fee_item == 'Transport' ) item.default_amount = item.range;
						else item.default_amount = $filter('currency')(item.default_amount,"");
						return item;
					});
				}
				console.log($scope.items);
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
				} ],
				paging: false,
				destroy:true,				
				order:[1,'asc'],
				filter: true,
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
						emptyTable: "No student balances found."
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
	
	
	$scope.addFeeItem = function()
	{
		$scope.openModal('fees', 'feeItemForm', 'md');
	}
	
	$scope.viewFeeItem = function(item)
	{
		$scope.openModal('fees', 'feeItemForm', 'md',item);
	}
	
	$scope.exportItems = function()
	{
		$rootScope.wipNotice();
	}
	

	$scope.$on('refreshItems', function(event, args) {

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
		getFeeStructure();
	}
	
	$scope.$on('$destroy', function() {
		if($scope.dataGrid) $scope.dataGrid.destroy();
		$rootScope.isModal = false;
    });

} ]);