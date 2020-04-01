'use strict';

angular.module('eduwebApp').
controller('listResourcesCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$state',
function($scope, $rootScope, apiService, $timeout, $window, $state){

	var initialLoad = true;
	$scope.resources = [];
	$scope.loading = true;
	$scope.selectionsReady = false;

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
		// $scope.filters.emp_cat = $rootScope.empCats.filter(function(item){
		//	if( item.emp_cat_id == $state.params.category ) return item;
		// })[0];
	}

	$scope.alert = {};

	$scope.enableCheckboxTable = function(){
		    $scope.selectionsReady = true;
	}

	var rowTemplate = function()
	{
		return '<div class="clickable">' +
		'  <div ng-if="row.entity.merge">{{row.entity.title}}</div>' +
		'  <div ng-if="!row.entity.merge" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }" ng-click="grid.appScope.viewEmployee(row)" data-target="#privilegesModal"  ui-grid-cell></div>' +
		'</div>';
	}

	$scope.gridOptions = {
		enableSorting: true,
		rowTemplate: rowTemplate(),
		rowHeight:24,
		columnDefs: [
			{ name: 'Teacher', field: 'teacher_name', enableColumnMenu: false, sort: {direction:'asc'},},
			{ name: 'Class', field: 'class_name', enableColumnMenu: false,},
			{ name: 'Resource', field: 'resource_name', enableColumnMenu: false,},
			{ name: 'Type', field: 'resource_type', enableColumnMenu: false,},
			{ name: 'Term', field: 'term_name', enableColumnMenu: false,},
			{ name: 'Date', field: 'creation_date', enableColumnMenu: false,},
		],
		exporterCsvFilename: 'resources.csv',
		onRegisterApi: function(gridApi){
		  $scope.gridApi = gridApi;
		  $scope.gridApi.grid.registerRowsProcessor( $scope.singleFilter, 200 );
		  $timeout(function() {
			$scope.gridApi.core.handleWindowResize();
		  });
		}
	};

	var getReources = function()
	{
		apiService.getAllResources({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				console.log("Resources data success",result.data);
				$scope.resources = result.data;
				initDataGrid($scope.resources);
			}else{
				initDataGrid($scope.resources);
			}

		},function(err){console.log(err);});
	}

	var initializeController = function ()
	{
		// get resources
		getReources();

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
		  [ 'teacher_name', 'class_name', 'resource_name', 'resource_type', 'term_name', 'creation_date' ].forEach(function( field ){
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

		apiService.getAllResources({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				console.log("Resources data success",result.data);
				$scope.resources = result.data;
				initDataGrid($scope.resources);
			}else{
				initDataGrid($scope.resources);
			}

		},apiError);

		filterResults(true);
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

		$scope.resources = filteredResults;
		initDataGrid($scope.resources);
	}

	$scope.viewEmployee = function(row)
	{
		console.log(row.entity);
		$scope.openedResourceName = row.entity.resource_name;
		$scope.openedResourceTeacher = row.entity.teacher_name;
		$scope.openedResourceClass = row.entity.class_name;
		$scope.openedResourceTerm = row.entity.term_name;
		$scope.openedResourceType = row.entity.resource_type;
		$scope.openedResourceFile = row.entity.file_name;

		function getFileDir(){
			var subDir;
			var fileExtension = $scope.openedResourceFile.split('.').slice(-1).pop();
			if(fileExtension == 'mp4' || fileExtension == 'm4v' || fileExtension == 'avi' || fileExtension == 'wmv' || fileExtension == 'flv' || fileExtension == 'webm' || fileExtension == 'f4v' || fileExtension == 'mov'){
				$scope.actualFileType = 'video';
				$scope.resourceIcon="video-icon.png";
				subDir = "videos";
			}else if(fileExtension == 'mp3' || fileExtension == 'm4a' || fileExtension == 'wav' || fileExtension == 'wma' || fileExtension == 'aac' || fileExtension == 'ogg' || fileExtension == '3gp' || fileExtension == 'f4a' || fileExtension == 'flacc' || fileExtension == 'midi'){
				$scope.actualFileType = 'audio';
				$scope.resourceIcon="audio-icon.png";
				subDir = "audios";
			}else if(fileExtension == 'jpg' || fileExtension == 'jpeg' || fileExtension == 'gif' || fileExtension == 'png' || fileExtension == 'tiff'){
				$scope.actualFileType = 'audio';
				$scope.resourceIcon="img-icon.png";
				subDir = "images";
			}else if(fileExtension == 'pdf'){
				$scope.actualFileType = 'pdf';
				$scope.resourceIcon="pdf-icon.png";
				subDir = "documents";
			}else if(fileExtension == 'doc' || fileExtension == 'docx' || fileExtension == 'odf' || fileExtension == 'xls' || fileExtension == 'xlsx' || fileExtension == 'csv'){
				$scope.actualFileType = 'document';
				$scope.resourceIcon="doc-icon.png";
				subDir = "documents";
			}
			return subDir;
		}
		var school = window.location.host.split('.')[0];
		var fileDir = getFileDir();
		$scope.openedResourceLink = 'https://classroom.eduweb.co.ke/' + school + '/' + fileDir + '/' + $scope.openedResourceFile;
		$scope.openedResourceCreationDate = row.entity.creation_date;

		// Get the modal
		var modal = document.getElementById("resourceModal");

		// Get the button that opens the modal
		var btn = document.getElementById("myBtn");

		// Get the <span> element that closes the modal
		var span = document.getElementsByClassName("closemdl")[0];
		modal.style.display = "block";

		// When the user clicks on <span> (x), close the modal
		span.onclick = function() {
		  modal.style.display = "none";
			$scope.openedResourceName = null;
			$scope.openedResourceTeacher = null;
			$scope.openedResourceClass = null;
			$scope.openedResourceTerm = null;
			$scope.openedResourceType = null;
			$scope.openedResourceFile = null;
			$scope.openedResourceLink = null;
			$scope.openedResourceCreationDate = null;
		}

		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
		  if (event.target == modal) {
		    modal.style.display = "none";
				$scope.openedResourceName = null;
				$scope.openedResourceTeacher = null;
				$scope.openedResourceClass = null;
				$scope.openedResourceTerm = null;
				$scope.openedResourceType = null;
				$scope.openedResourceFile = null;
				$scope.openedResourceLink = null;
				$scope.openedResourceCreationDate = null;
		  }
		}
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
		getReources();
	}

	$scope.$on('$destroy', function() {
		$rootScope.isModal = false;
    });


} ]);
