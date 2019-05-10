'use strict';

angular.module('eduwebApp').
controller('feeStructureCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter',
function($scope, $rootScope, apiService, $timeout, $window, $filter){

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.alert = {};
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	$scope.loading = true;
	
	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';
	
	var rowTemplate = function() 
	{
		return '<div class="clickable" ng-class="{\'alert-warning\': row.entity.replacement_payment}" ng-click="grid.appScope.viewFeeItem(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}
	
	var names = ['Default Fee ( ' + $scope.currency + ' )'];
	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Item Name', field: 'fee_item', enableColumnMenu: false },
			{ name: names[0], field: 'default_amount', enableColumnMenu: false },
			{ name: 'Frequency', field: 'frequency', enableColumnMenu: false },
			{ name: 'Class Categories', field: 'class_categories', cellFilter:'arrayToList', enableColumnMenu: false },
			{ name: 'New Student Only', field: 'new_student', enableColumnMenu: false },
		],
		exporterCsvFilename: 'fee-structure.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};

	var initializeController = function () 
	{
		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);
		
		getFeeStructure();
	}
	$timeout(initializeController,1);

	var getFeeStructure = function()
	{
		apiService.getFeeItems($scope.filters.status, function(response,status,params){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{	
				$scope.items = ( result.nodata ? [] : result.data.required_items.concat(result.data.optional_items));	
				
				if( $scope.items.length > 0 )
				{
					$scope.items = $scope.items.map(function(item){
						// format the class restrictions into any array
						if( item.class_cats_restriction !== null && item.class_cats_restriction != '{}' )
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
							item.class_cats_restriction = [];
						}
						item.new_student = ( item.new_student_only ? 'Yes':'-');
						
						item.default_amount_raw = item.default_amount;
						
						if( item.fee_item == 'Transport' ){
						    item.default_amount = item.range;
						}else if( item.fee_item == 'Uniform' ){
						    item.default_amount = item.uniform_range;
						}else{ 
						    item.default_amount = $filter('currency')(item.default_amount,"");
						}
						return item;
					});
				}

				initDataGrid($scope.items);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
			
		}, apiError);
	}
		
	var initDataGrid = function(data) 
	{
		$scope.gridOptions.data = data;
		$scope.loading = false;
		$rootScope.loading = false;

	}
	
	$scope.filterDataTable = function() 
	{
		$scope.gridApi.grid.refresh();
	};
	
	$scope.clearFilterDataTable = function() 
	{
		$scope.gridFilter.filterValue = '';
		$scope.gridApi.grid.refresh();
	};
	
	$scope.singleFilter = function( renderableRows )
	{
		var matcher = new RegExp($scope.gridFilter.filterValue, 'i');
		renderableRows.forEach( function( row ) {
		  var match = false;
		  [ 'fee_item', 'frequency' ].forEach(function( field ){
			if ( row.entity[field].match(matcher) ){
			  match = true;
			}
		  });
		  if ( !match ){
			row.visible = false;
		  }
		});
		return renderableRows;
	};
	
	var apiError = function (response, status) 
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}
	
	$scope.loadFilter = function()
	{
		$scope.loading = true;
		getFeeStructure();		
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
		$scope.gridApi.exporter.csvExport( 'visible', 'visible' );
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
		$rootScope.isModal = false;
    });

} ]);