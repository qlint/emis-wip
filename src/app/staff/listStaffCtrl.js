'use strict';

angular.module('eduwebApp').
controller('listStaffCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.employees = [];
	$scope.loading = true;
	$scope.privileges = {};
	$scope.showAdmin = false;
	$scope.showSysAdmin = false;
	$scope.showEmployees = false;
	$scope.showTeachers = false;
	$scope.showCategories = false;
	$scope.showDepartments = false;
	$scope.showPrincipal = false;
	$scope.showAdmnFin = false;
	$scope.showAdmnTransp = false;
	$scope.showFin = false;
	$scope.showFinCtrld = false;
	$scope.selectionsReady = false;
	$scope.permsTable = {};
	$scope.updateParams = [];
	$scope.updated = false;

	$scope.checkObjArr = function pushToArray(arr, obj) {
	    let index = arr.findIndex((e) => e.id === obj.id);
	    if (index === -1) {arr.push(obj);} else {arr[index] = obj;}
	}

	var getUserGroups = function(){
		apiService.getUserGroups(true, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.allUserGroups = result.data;
				$scope.userTypes = $scope.allUserGroups;
			}
			else{
				console.log("User groups blank >",result);
			}
		}, function(e){console.log(e)});
	}

	$scope.permsTable.allPermissions =
                                {
                                    globalPermissions: {
                                        name: 'Global Access',
                                        values: [
                                            {name: 'All', icon: 'lock_open', isSelected: false},
                                            {name: 'create', icon: 'add', isSelected: false},
                                            {name: 'edit', icon: 'edit', isSelected: false},
                                            {name: 'delete', icon: 'delete', isSelected: false},
                                            {name: 'view', icon: 'remove_red_eye', isSelected: false},
                                            {name: 'export', icon: 'open_in_new', isSelected: false}
                                        ]
                                    },
																		/*
                                    permissions: [
                                        {
                                            name: 'Dashboard',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: []
                                        },
                                        {
                                            name: 'Students',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: []
                                        },
                                        {
                                            name: 'Staff',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: []
                                        },
                                        {
                                            name: 'Fees',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: [
                                                {
                                                    name: 'dashboard',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'opening_balances',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'invoices',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'payments_received',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'fee_structure',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'fee_reports',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'm_pesa',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }
                                            ]
                                        },
                                        {
                                            name: 'School',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: [
                                                {
                                                    name: 'school_settings',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'school_dates',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'grading',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'subjects',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'departments',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'classes',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }
                                            ]
                                        },
                                        {
                                            name: 'Exams',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: [
                                                {
                                                    name: 'exams',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'exam_types',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'report_cards',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'class_analysis',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'stream_analysis',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'exam_reports',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }
                                            ]
                                        },
                                        {
                                            name: 'Communications',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: [
                                                {
                                                    name: 'send_email',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'feedback',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }
                                            ]
                                        },
                                        {
                                            name: 'Timetables',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: [
                                                {
                                                    name: 'create_class_timetable',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'class_timetable',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'create_teacher_timetable',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'teacher_timetable',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'master_timetable',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }
                                            ]
                                        },
                                        {
                                            name: 'Transport',
                                            values: [
                                                {name: 'full', isSelected: false},
                                                {name: 'create', isSelected: false},
                                                {name: 'edit', isSelected: false},
                                                {name: 'delete', isSelected: false},
                                                {name: 'view', isSelected: false},
                                                {name: 'export', isSelected: false}
                                            ],
                                            children: [
                                                {
                                                    name: 'school_bus',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'trips',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'mapped_history',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'transport_communications',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }, {
                                                    name: 'transport_reports',
                                                    values: [
                                                        {name: 'full', isSelected: false},
                                                        {name: 'create', isSelected: false},
                                                        {name: 'edit', isSelected: false},
                                                        {name: 'delete', isSelected: false},
                                                        {name: 'view', isSelected: false},
                                                        {name: 'export', isSelected: false}
                                                    ]
                                                }
                                            ]
                                        }
                                    ]
																		*/
    };
    $scope.permsTable.gridName = $scope.permsTable.allPermissions.globalPermissions.name;
    // $scope.permsTable.rows = $scope.permsTable.allPermissions.permissions;
    $scope.permsTable.header = $scope.permsTable.allPermissions.globalPermissions;

    // Set column header
    $scope.permsTable.setColumnHeader = function(index, header, rows, stateCheck) {
        for (var i = 0; i < rows.length; i++) {
            rows[i].values[index].isSelected = header.values[index].isSelected;
            if (index === 0) {
                $scope.permsTable.setCell(index, rows[i], rows, header, false);
            }
            if (angular.isDefined(rows[i].children) && rows[i].children.length > 0) {
                $scope.permsTable.setColumnHeader(index, rows[i], rows[i].children, false);
            }
        }
        if (stateCheck) {
            setState(header, rows, true);
        }
    };

    // Set cell
    $scope.permsTable.setCell = function(index, row, rows, header, stateCheck) {
        if (index === 0) {
            for (var i = 1; i < row.values.length; i++) {
                row.values[i].isSelected = row.values[0].isSelected;
            }
        }
        if (angular.isDefined(row.children) && row.children.length > 0) {
            $scope.permsTable.setColumnHeader(index, row, row.children, false);
        }
        if (stateCheck) {
            setState(header, rows, true);
        }
    };

    // Get row state
    function getRowState(row) {
        var state = true;
        for (var i = 1; i < row.values.length; i++) {
            if (row.values[i].isSelected === false) {
                state = false;
                break;
            }
        }
        row.values[0].isSelected = state;
    }

    // Get column state
    function getColumnState(header, rows, index) {
        var state = true;
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].values[index].isSelected === false) {
                state = false;
                break;
            }
        }
        header.values[index].isSelected = state;
    }

    // Set state
    function setState(header, rows, recursion) {
        for (var i = 0; i < rows.length; i++) {
            getRowState(rows[i]);

            if (recursion && angular.isDefined(rows[i].children) && rows[i].children.length > 0) {
                setState(rows[i], rows[i].children, false);
            }
        }
        for (var j = 0; j < header.values.length; j++) {
            getColumnState(header, rows, j);
        }
        getRowState(header);

        if (rows !== $scope.permsTable.rows) {
            setState($scope.permsTable.header, $scope.permsTable.rows, false);
        }
    }

    // Toggle sub row
    $scope.permsTable.toggleSubRow = function(row) {
        row.subRowsToggled = !row.subRowsToggled;
        row.isExpanded = row.subRowsToggled;
    };
    // console.log($scope.permsTable.gridName);
    // console.log("$scope.permsTable.allPermissions.globalPermissions.name",$scope.permsTable.allPermissions.globalPermissions.name);
    // console.log("$scope.permsTable",$scope.permsTable);

	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filters.emp_cat_id = ( $state.params.category !== '' ? $state.params.category : null );
	$scope.filterEmpCat = ( $state.params.category !== '' ? true : false );
	$scope.filters.dept_id = ( $state.params.dept !== '' ? $state.params.dept : null );
	$scope.filterDept = ( $state.params.dept !== '' ? true : false );

	$scope.gridFilter = {};
	$scope.gridFilter.filterValue  = '';

	/* get full employee cat record from state param */
	if( $state.params.category !== null )
	{
		$scope.filters.emp_cat = $rootScope.empCats.filter(function(item){
			if( item.emp_cat_id == $state.params.category ) return item;
		})[0];
	}

	$scope.alert = {};

	$scope.enableCheckboxTable = function(){
		    $scope.selectionsReady = true;
	}

	var rowTemplate = function()
	{
		return '<div class="clickable" ng-click="grid.appScope.viewEmployee(row.entity)">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>' +
		'</div>';
	}

	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Name', field: 'employee_name', enableColumnMenu: false, sort: {direction:'asc'},},
			{ name: 'Category', field: 'emp_cat_name', enableColumnMenu: false,},
			{ name: 'Department', field: 'dept_name', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'staff.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};

	var getStaff = function()
	{
		apiService.getAllEmployees(true, function(response){
			var result = angular.fromJson(response);

			// store these as they do not change often
			if( result.response == 'success')
			{
				$scope.allEmployees = (result.nondata !== undefined ? [] : result.data);
				$scope.employees = $scope.allEmployees ;

				// if filters set, filter results
				if( $scope.currentFilters !== undefined || $scope.filterEmpCat || $scope.filterDept  )
				{
					filterResults(false);
				}
				else
				{
					initDataGrid($scope.employees);
				}


			}
			else
			{
				initDataGrid($scope.employees);
			}

		}, function(){});
	}

	var initializeController = function ()
	{
		// get staff
		$scope.departments = $rootScope.allDepts;
		getStaff();

		setTimeout(function(){
			var height = $('.full-height.datagrid').height();
			$('#grid1').css('height', height);
			$scope.gridApi.core.handleWindowResize();
		},100);

	}
	$timeout(initializeController,1000);

	$scope.$watch('filters.emp_cat', function(newVal,oldVal){
		if (oldVal == newVal) return;

		if( newVal === undefined || newVal === null || newVal == '' ) 	$scope.departments = $rootScope.allDepts;
		else
		{
			// filter dept to only show those belonging to the selected category
			$scope.departments = $rootScope.allDepts.reduce(function(sum,item){
				if( item.category == newVal.emp_cat_name ) sum.push(item);
				return sum;
			}, []);
			$scope.filters.emp_cat_id = newVal.emp_cat_id;
			$timeout(setSearchBoxPosition,10);
		}
	});

	var initDataGrid = function(data)
	{
		$scope.gridOptions.data = data;
		$scope.loading = false;
		$rootScope.loading = false;

	}

	$scope.userPrivileges = function(){
		getUserGroups();
	}

	$scope.checkPrivilegeSelection = function(){

		if($scope.privileges.user_type == null || $scope.privileges.user_type == ""){
				$scope.showWarning = true;
				document.getElementById('notifySelection').style.color = '#FF0000'; // red
				$scope.selectionMsg = "A user type has to be selected to proceed!";
		}else{
			$scope.showWarning = false;
			let param = window.location.host.split('.')[0] + '/' + $scope.privileges.user_type;
			apiService.usrRights(param, function(response){
				var result = angular.fromJson(response);
				if( result.response == 'success'){
					$scope.userRights = result.data;
					// console.log($scope.userRights);
				}
				// console.log($scope.userRights);
				$scope.permsTable.allPermissions.permissions = [];
				// we use the results to create an object we will use to display on a table
				for(let x=0;x < $scope.userRights.rights.rights.length; x++){
					let parentRight = Object.getOwnPropertyNames($scope.userRights.rights.rights[x])[0];
					let parentRightObj = $scope.userRights.rights.rights[x][parentRight];
					// console.log("Obj Props >",parentRight);
					// console.log("Parent right main obj",parentRightObj);
					// this is how each object will look like including sub modules (children)
					let rightsObj = {
															name: parentRight,
															values: [
																	{name: 'full', isSelected: false},
																	{name: 'create', isSelected: false},
																	{name: 'edit', isSelected: false},
																	{name: 'delete', isSelected: false},
																	{name: 'view', isSelected: false},
																	{name: 'export', isSelected: false}
															],
															children: []
													};
					for(let y=0;y < parentRightObj.length;y++){
						if( Object.getOwnPropertyNames(parentRightObj[y]).length > 0 && Object.getOwnPropertyNames(parentRightObj[y])[1] != '-' ){
							let childName = Object.getOwnPropertyNames(parentRightObj[y])[1];
							let childId = parentRightObj[y].id;
							let childObj = parentRightObj[y][childName];
							// console.log("Has Children >",childName);
							// console.log("Child Obj >",childObj);
							// console.log("Child Id >",childId);
							// console.log("Full Prop >",parentRightObj[y]);
							let child = {
														id: childId,
														name: childName,
														values: [
															{name: 'full', isSelected: false},
															{name: 'create', isSelected: childObj.add},
															{name: 'edit', isSelected: childObj.edit},
															{name: 'delete', isSelected: childObj.delete},
															{name: 'view', isSelected: childObj.view},
															{name: 'export', isSelected: childObj.export}
														]
													};
							rightsObj.children.push(child);
						}
					}
					// console.log("Per rights obj",rightsObj);
					$scope.permsTable.allPermissions.permissions.push(rightsObj);
				}
				// here we need to set the "full" states of the parent modules checkboxes
				$scope.permsTable.allPermissions.permissions.forEach((module) => {
					module.children.forEach((child) => {
						let moduleChildVals = [];

						for(let i=0;i<child.values.length;i++){
							let childObj = child.values[i];
							let childValCheck = childObj[Object.keys(childObj)[1]]; // the parameter to check is 2nd ie [1]
							if(childObj.name != 'full'){moduleChildVals.push(childValCheck);}
						}

						function isTrue(element, index, array) {
						  return element == true;
						}
						if(moduleChildVals.every(isTrue)){
							// tick the parent
							module.values.forEach((item) => {
								item.isSelected = true;
							});

						}
					});

				});
				$scope.permsTable.rows = $scope.permsTable.allPermissions.permissions;
				// console.log("New perms table >",$scope.permsTable.allPermissions.permissions);

			}, function(err){console.log("An error occurred: ",err)});
				/*
		    if($scope.privileges.user_type == 'SYS_ADMIN' || $scope.privileges.user_type == 'ADMIN' || $scope.privileges.user_type == 'PRINCIPAL' || $scope.privileges.user_type == 'ADMIN-FINANCE' || $scope.privileges.user_type == 'ADMIN-TRANSPORT' || $scope.privileges.user_type == 'FINANCE' || $scope.privileges.user_type == 'FINANCE-CONTROLLED' || $scope.privileges.user_type == 'TEACHER'){
		        delete $scope.privileges.emp_id;
		        delete $scope.privileges.emp_cat_id;
		        delete $scope.privileges.dept_id;
		    }else if($scope.privileges.user_type == 'employees'){
		        delete $scope.privileges.emp_cat_id;
		        delete $scope.privileges.dept_id;
		    }else if($scope.privileges.user_type == 'categories'){
		        delete $scope.privileges.emp_id;
		        delete $scope.privileges.dept_id
		    }else if($scope.privileges.user_type == 'departments'){
		        delete $scope.privileges.emp_id;
		        delete $scope.privileges.emp_cat_id;
		    }
				*/

		    // console.log($scope.privileges);
				$scope.enableCheckboxTable();
		}

	}

	$scope.captureType = function(el){
	    // console.log("Capture user type >",el);
			let selectedType = el.privileges;
			$scope.privileges.user_type = selectedType.user_type;
			$scope.privileges.user_id = null
	    if($scope.privileges.user_type != null && $scope.selectionMsg == 'A user type has to be selected to proceed!'){
	        $scope.selectionMsg = null;
	    }
	    if($scope.privileges.user_type == 'SYS_ADMIN'){
	        apiService.getSysAdmns(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
						{ $scope.sysAdmins = result.data; }
						else{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = true;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'ADMIN'){
	        apiService.getAdmns(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
	    			{ $scope.admins = result.data; }
	    			else
	    			{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = true;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'employees'){
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = true;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'categories'){
	        $scope.empCats = $rootScope.empCats;
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = true;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'departments'){
	        $scope.empDepts = $rootScope.allDepts;
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = true;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'TEACHER'){
	        apiService.getTchrs(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
	    			{ $scope.tchrs = result.data; }
	    			else
	    			{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = true;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'PRINCIPAL'){
	        apiService.getPrincipals(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
	    			{ $scope.principals = result.data; }
	    			else
	    			{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = true;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'ADMIN-FINANCE'){
	        apiService.getAdmnFinance(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
	    			{ $scope.admnFinance = result.data; }
	    			else
	    			{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = true;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'ADMIN-TRANSPORT'){
	        apiService.getAdmnTransp(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
	    			{ $scope.admnTransport = result.data; }
	    			else
	    			{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = true;
        	$scope.showFin = false;
        	$scope.showFinCtrld = false;
	    }
			else if($scope.privileges.user_type == 'FINANCE'){
	        apiService.getFnance(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
	    			{ $scope.fnance = result.data; }
	    			else
	    			{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = true;
        	$scope.showFinCtrld = false;
	    }else if($scope.privileges.user_type == 'FINANCE-CONTROLLED'){
	        apiService.getFnanceCtrld(true, function(response){
	    			var result = angular.fromJson(response);
	    			if( result.response == 'success')
	    			{ $scope.fnanceCtrld = result.data; }
	    			else
	    			{
	    				$scope.error = true;
	    				$scope.errMsg = result.data;
	    				$scope.studentsLoading = false;
	    			}
    		}, function(e){console.log(e)});
	        $scope.showSysAdmin = false;
	        $scope.showAdmin = false;
        	$scope.showEmployees = false;
        	$scope.showTeachers = false;
        	$scope.showCategories = false;
        	$scope.showDepartments = false;
        	$scope.showPrincipal = false;
        	$scope.showAdmnFin = false;
        	$scope.showAdmnTransp = false;
        	$scope.showFin = false;
        	$scope.showFinCtrld = true;
	    }
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
		  [ 'employee_name', 'emp_cat_name', 'dept_name' ].forEach(function( field ){
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

	$scope.filter = function()
	{
		$scope.currentFilters = angular.copy($scope.filters);
		console.log($scope.filters.status);

		apiService.getAllEmployees($scope.filters.status, function(response){
			var result = angular.fromJson(response);

			// store these as they do not change often
			if( result.response == 'success')
			{
				$scope.allEmployees = (result.nondata !== undefined ? [] : result.data);
				$scope.employees = $scope.allEmployees ;

				// if filters set, filter results
				if( $scope.currentFilters !== undefined || $scope.filterEmpCat || $scope.filterDept  )
				{
					filterResults(false);
				}
				else
				{
					initDataGrid($scope.employees);
				}


			}
			else
			{
				initDataGrid($scope.employees);
			}

		}, function(){});

		filterResults(true);
	}

	$scope.headerChange = function(el){
	    console.log("Global change detected",el);

	    // function to update tbody with thead values
	    function updateTableBody(col,val){
	        // each tbody row
	        $scope.permsTable.rows.forEach(function(eachRow){
                for(let i=0;i < eachRow.values.length;i++){
    	            if(eachRow.values[i].name == col){
    	                // set it to the global changed value
    	                eachRow.values[i].isSelected = val;
    	            }
    	        }
    	        // change it for the children as well
    	        for(let k=0;k < eachRow.children.length;k++){
    	            eachRow.children[k].values.forEach(function(eachChild){
                        if(eachChild.name == col){
                            // set it to the global chaged value
                            eachChild.isSelected = val;
                        }
                    });
    	        }
            });

	        // if val is true, we also need to disable editing the tbody checkboxes to avoid conflicting permissions
	    }
	    updateTableBody(el.header.name,el.header.isSelected);
	}

	$scope.bodyHeaderChange = function(el,id){
		$scope.updated = false;
		console.log(id,el);

		if(el.mainPerm){
			console.log("Full change detected",el.mainPerm);
			console.log("Parent property",el.$parent.perms);

			for(let a=0;a < el.$parent.perms.children.length;a++){
				let updateParam = {
					id:el.$parent.perms.children[a].id,
					col:el.mainPerm.name,
					val:(el.mainPerm.isSelected == true ? 'true' : 'false')
				}
				$scope.checkObjArr($scope.updateParams,updateParam);
				// $scope.updateParams.push(updateParam);
			}
			console.log($scope.updateParams);
			function updateChildren(col,val){
	        $scope.permsTable.rows.forEach(function(eachRow){
                for(let x=0;x < eachRow.children.length;x++){
    	            eachRow.children[x].values.forEach(function(eachChild){
										// console.log("Children Data >",eachRow.children[x]);
                        if(eachChild.name == col){
													// console.log(col,eachChild);
													// set it to the global chaged value
													eachChild.isSelected = val;
                        }
                    });
    	        }
            });
	    }
	    updateChildren(el.mainPerm.name,el.mainPerm.isSelected);

		}else if(el.subModuleVals){
			console.log("Single change detected",el.subModuleVals);
			console.log("Parent property",el.$parent.perms);
			let updateParam = {id:id, col:el.subModuleVals.name, val:(el.subModuleVals.isSelected == true ? 'true' : 'false')}
			$scope.checkObjArr($scope.updateParams,updateParam);
			// $scope.updateParams.push(updateParam);
			console.log(updateParam,$scope.updateParams);
		}
	}

	$scope.savePermissions = function(){
		let params = {
			sch: window.location.host.split('.')[0],
			user_type: $scope.privileges.user_type,
			perms: $scope.permsTable.allPermissions.permissions
		}
		console.log("Post Params >",params);
		// apiService.updateUsrRights(params,
		// 														function ( response, status, params )
		// 														{
		// 															var result = angular.fromJson( response );
		// 															if( result.response == 'success' )
		// 															{console.log('Success >',JSON.parse(result.output));}
		// 															else
		// 															{console.log('Error >',result);}
		// 														},
		// 														function(err){console.log('An Error Occurred > ',err)});
		apiService.updateAccessRights($scope.updateParams,
																function ( response, status, params )
																{
																	var result = angular.fromJson( response );
																	if( result.response == 'success' )
																	{
																		console.log('Success >',JSON.parse(result.output));
																		$scope.updateParams = [];
																		$scope.updatedRes = result.message;
																		$scope.updated = true;
																		$scope.privileges.user_type = null;
																		$timeout(function() { console.log("Changed"); $scope.updated = false;  }, 4000);
																	}
																	else
																	{console.log('Error >',result);}
																},
																function(err){console.log('An Error Occurred > ',err)});
	}

	var filterResults = function(clearTable)
	{
		$scope.loading = true;

		// filter by emp category
		var filteredResults = $scope.allEmployees;


		if( $scope.filters.emp_cat_id !== undefined && $scope.filters.emp_cat_id !== null && $scope.filters.emp_cat_id != ''  )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.emp_cat_id.toString() == $scope.filters.emp_cat_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}


		if( $scope.filters.dept_id !== undefined && $scope.filters.dept_id !== null && $scope.filters.dept_id != '' )
		{
			filteredResults = filteredResults.reduce(function(sum, item) {
			  if( item.dept_id.toString() == $scope.filters.dept_id.toString() ) sum.push(item);
			  return sum;
			}, []);
		}

		$scope.employees = filteredResults;
		initDataGrid($scope.employees);
	}

	$scope.addEmployee = function()
	{
		$scope.openModal('staff', 'addEmployee', 'lg');
	}

	$scope.viewEmployee = function(item)
	{
		$scope.openModal('staff', 'viewEmployee', 'lg', item);
	}

	$scope.exportData = function()
	{
		// $scope.gridApi.exporter.csvExport( 'visible', 'visible' );

		class XlsExport {
  // data: array of objects with the data for each row of the table
  // name: title for the worksheet
  constructor(data, title = 'Worksheet') {
    // input validation: new xlsExport([], String)
    if (!Array.isArray(data) || (typeof title !== 'string' || Object.prototype.toString.call(title) !== '[object String]')) {
      throw new Error('Invalid input types: new xlsExport(Array [], String)');
    }

    this._data = data;
    this._title = title;
  }

  set setData(data) {
    if (!Array.isArray(data)) throw new Error('Invalid input type: setData(Array [])');

    this._data = data;
  }

  get getData() {
    return this._data;
  }

  exportToXLS(fileName = 'export.xls') {
    if (typeof fileName !== 'string' || Object.prototype.toString.call(fileName) !== '[object String]') {
      throw new Error('Invalid input type: exportToCSV(String)');
    }

    const TEMPLATE_XLS = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"/>
        <head><!--[if gte mso 9]><xml>
        <x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{title}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml>
        <![endif]--></head>
        <body>{table}</body></html>`;
    const MIME_XLS = 'application/vnd.ms-excel;base64,';

    const parameters = {
      title: this._title,
      table: this.objectToTable(),
    };
    const computeOutput = TEMPLATE_XLS.replace(/{(\w+)}/g, (x, y) => parameters[y]);

    const computedXLS = new Blob([computeOutput], {
      type: MIME_XLS,
    });
    const xlsLink = window.URL.createObjectURL(computedXLS);
    this.downloadFile(xlsLink, fileName);
  }

  exportToCSV(fileName = 'export.csv') {
    if (typeof fileName !== 'string' || Object.prototype.toString.call(fileName) !== '[object String]') {
      throw new Error('Invalid input type: exportToCSV(String)');
    }
    const computedCSV = new Blob([this.objectToSemicolons()], {
      type: 'text/csv;charset=utf-8',
    });
    const csvLink = window.URL.createObjectURL(computedCSV);
    this.downloadFile(csvLink, fileName);
  }

  downloadFile(output, fileName) {
    const link = document.createElement('a');
    document.body.appendChild(link);
    link.download = fileName;
    link.href = output;
    link.click();
  }

  toBase64(string) {
    return window.btoa(unescape(encodeURIComponent(string)));
  }

  objectToTable() {
    // extract keys from the first object, will be the title for each column
    const colsHead = `<tr>${Object.keys(this._data[0]).map(key => `<td>${key}</td>`).join('')}</tr>`;

    const colsData = this._data.map(obj => [`<tr>
                ${Object.keys(obj).map(col => `<td>${obj[col] ? obj[col] : ''}</td>`).join('')}
            </tr>`]) // 'null' values not showed
      .join('');

    return `<table>${colsHead}${colsData}</table>`.trim(); // remove spaces...
  }

  objectToSemicolons() {
    const colsHead = Object.keys(this._data[0]).map(key => [key]).join(';');
    const colsData = this._data.map(obj => [ // obj === row
      Object.keys(obj).map(col => [
        obj[col], // row[column]
      ]).join(';'), // join the row with ';'
    ]).join('\n'); // end of row

    return `${colsHead}\n${colsData}`;
  }
}

  // meanwhile - fetch data
  apiService.exportAllStaffDetails({}, function(response, status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
      $scope.staffExport = result.data;
      $scope.exportToXls = new XlsExport($scope.staffExport, 'Staff Data Workbook');
      $scope.exportToXls.exportToXLS('Staff_Data_Workbook.xls');
    }else{
      // failed to fetch data
    }
  }, {});
	}

	$scope.$on('refreshStaff', function(event, args) {

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
		getStaff();
	}

	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });


} ]);
