'use strict';

angular.module('eduwebApp').
controller('listExamsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse',
function($scope, $rootScope, apiService, $timeout, $window, $q, $parse){

	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.students = [];
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;
	$rootScope.modalLoading = false;
	$scope.alert = {};
	$scope.refreshing = false;
	$scope.getReport = "examsTable";
	$scope.showExpSheet = false;
	$scope.showUploadSheet = false;
	$scope.showUploadBtn = false;
	$scope.notCsvUpld = false;
	$scope.showUploadTitle = false;
	$scope.importReady = false;
	$scope.importFilters = null;
	//$scope.loading = true;

	var initializeController = function ()
	{
		// get classes
		var requests = [];

		var deferred = $q.defer();
		requests.push(deferred.promise);

		if( $rootScope.allClasses === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				apiService.getTeacherClasses($rootScope.currentUser.emp_id, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						deferred.resolve();
					}
					else
					{
						deferred.reject();
					}

				}, function(){deferred.reject();});
			}
			else
			{
				apiService.getAllClasses({}, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						$scope.filters.class_cat_id = ( $scope.classes[0] ? $scope.classes[0].class_cat_id : null);
						deferred.resolve();
					}
					else
					{
						deferred.reject();
					}

				}, function(){deferred.reject();});
			}
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
			$scope.filters.class = $scope.classes[0];
			$scope.filters.class_id = $scope.classes[0].class_id;
			$scope.filters.class_cat_id = $scope.classes[0].class_cat_id;
			deferred.resolve();
		}


		// get terms
		var deferred2 = $q.defer();
		requests.push(deferred2.promise);
		if( $rootScope.terms === undefined )
		{
			apiService.getTerms(undefined, function(response,status)
			{
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					$scope.terms = result.data;
					$rootScope.terms = result.data;

					var currentTerm = $scope.terms.filter(function(item){
						if( item.current_term ) return item;
					})[0];
					$scope.filters.term_id = currentTerm.term_id;
					deferred2.resolve();
				}
				else
				{
					deferred2.reject();
				}

			}, function(){deferred2.reject();});
		}
		else
		{
			$scope.terms = $rootScope.terms;
			var currentTerm = $scope.terms.filter(function(item){
				if( item.current_term ) return item;
			})[0];
			$scope.filters.term_id = currentTerm.term_id;
			deferred2.resolve();
		}


		/* taking the out, going to need user to choose exam then click load
		// need to wait for three data pieces, then run this
		$q.all(requests).then(function () {
			//if( $scope.filters.class_id !== null ) $scope.getStudentExams();
		});
		*/
	}
	$timeout(initializeController,1);

	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.filters.class_id = newVal.class_id;

		apiService.getExamTypes(newVal.class_cat_id, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success' && !result.nodata ){
				$scope.examTypes = result.data;
				$scope.filters.exam_type_id = $scope.examTypes[0].exam_type_id;
				$timeout(setSearchBoxPosition,10);
			}else if(result.response == 'success' && result.nodata){
				$scope.examTypes = [];
			}
		}, apiError);


	});

	$scope.getStudentExams = function()
	{
		$scope.examMarks = {};
		$scope.tableHeader = [];
		$scope.marksNotFound = false;
		$scope.getReport = "";

		if($scope.filters.class_id && $scope.filters.term_id && $scope.filters.exam_type_id){
			var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
			if( $rootScope.currentUser.user_type == 'TEACHER' ) request += '/' + $rootScope.currentUser.emp_id;
			apiService.getAllStudentExamMarks(request, loadMarks, apiError);
		}
	}

	var loadMarks = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.marksNotFound = true;
				$scope.errMsg = "There are currently no exam marks entered for this search criteria.";
			}
			else
			{

				if( $scope.dataGrid !== undefined )
				{
					$('.fixedHeader-floating').remove();
					$scope.dataGrid.clear();
					$scope.dataGrid.destroy();
				}

				$scope.examMarks = result.data;

				/* loop through the first exam mark result to build the table columns */
				$scope.tableHeader = [];
				var ignoreCols = ['student_id','student_name','rank','exam_type'];
				var subjectsArray = {};
				angular.forEach($scope.examMarks[0], function(value,key){
					if( ignoreCols.indexOf(key) === -1 )
					{
						// keys read like '7', 'C.R.E', '40', remove the ' and replace , with /
						/* need the sort order id on the front so it orders correctly, seems to go alphabetical regardless of sort order applied to query
						   in order to fix this, the first item needs to strip this off */

						var colRow = key.replace(/["']/g, "");
						var subjectDetails = colRow.split(', '),
							parentSubject = subjectDetails[1],
							subjectName = subjectDetails[2],
							gradeWeight = subjectDetails[3];

						//colRow = subjectName + ' / ' + gradeWeight;

						/* each subject group needs to scored out of 100, if a subject does not have a parent, add 100 for grand total
						   if a subject has a parent, add 100 for each parent subject
						*/
						if( parentSubject == '' ) subjectsArray[subjectName] = 100; // no parent
						else subjectsArray[parentSubject] = 100; // has parent, use parents subject name

						$scope.tableHeader.push({
							title: subjectName,
							weight:gradeWeight,
							key: key,
							isParent: (parentSubject == '' ? true : false)
						});
					}
				});

				/* sum up the total grade weight value */
				$scope.totalGradeWeight = 0;
				for (var key in subjectsArray) {
					// skip loop if the property is from prototype
					if (!subjectsArray.hasOwnProperty(key)) continue;

					var value = subjectsArray[key];
					$scope.totalGradeWeight += subjectsArray[key];
				}

				/* loop through all exam mark results and calculate the students total score */
				/* only total up the parent subjects */
				// total up marks

				var total = 0;
				angular.forEach($scope.examMarks, function(item){
					var total = 0;
					angular.forEach(item, function(value,key){
						if( ignoreCols.indexOf(key) === -1 )
						{
							var colRow = key.replace(/["']/g, "");
							var subjectDetails = colRow.split(', '),
								parentSubject = subjectDetails[1],
								subjectName = subjectDetails[2],
								gradeWeight = subjectDetails[3];

							if( parentSubject == '' ) total += value;
						}

					});
					item.total = Math.round(total);

				});

				$scope.getReport = "examsTable";
				$timeout(initDataGrid,100);
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.displayMark = function(index, key)
	{
		return $parse("examMarks[" + index + "][\"" + key + "\"]" )($scope) || '-' ;
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
				order: [2,'asc'],
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
						emptyTable: "No students found."
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
		setSearchBoxPosition();

		if( initialLoad ) setResizeEvent();

	}

	var setSearchBoxPosition = function()
	{
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+55);
		}
	}

	var setResizeEvent = function()
	{
		 initialLoad = false;

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


	$scope.toggleFilter = function()
	{
		$scope.filterShowing = !$scope.filterShowing;

		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}

	$scope.toggleTools = function()
	{
		$scope.toolsShowing = !$scope.toolsShowing;

		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}

	$scope.addExamMarks = function()
	{
		var data = {
			classes: $scope.classes,
			terms: $scope.terms,
			examTypes: $scope.examTypes,
			filters: $scope.filters
		}
		$scope.openModal('exams', 'addExamMarks', 'lg', data);
	}

	$scope.importModal = function()
	{
		// $rootScope.wipNotice();
		$scope.importFilterBtn = "Load";
		$scope.importBtnStatus = "info";
	}

	$scope.exportData = function()
	{
		$rootScope.wipNotice();
	}

	$scope.importExamMarks = function()
	{
		$scope.isUpload = false;
		// console.log($scope.filters);
		if($scope.filters.action_type != null || $scope.filters.action_type != "" || $scope.filters.action_type != undefined){
			if($scope.filters.action_type == "download_csv" && (!$scope.filters.class_id || !$scope.filters.term_id || !$scope.filters.exam_type_id)){
				$scope.importFilterBtn = "All Selections Are Required";
				$scope.importBtnStatus = "danger";
				$scope.showUploadTitle = false;
			}else{
				$scope.importBtnStatus = "info";
				$scope.importFilterBtn = "Processing";
				if($scope.filters.action_type == "download_csv"){
					$scope.showUploadTitle = false;
					$scope.csvData = null;
					$scope.importFilterBtn = "Preparing Exam Sheet";
					$scope.loading = true;
					$scope.showUploadBtn = false;
					// fetch class data
					var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
					if( $rootScope.currentUser.user_type == 'TEACHER' ) request += '/' + $rootScope.currentUser.emp_id;
					apiService.getStudentSubjectsForExams(request, function(response,status)
					{
						$scope.loading = false;
						var result = angular.fromJson( response );
						if( result.response == 'success' )
						{
							if( result.nodata )
							{
								$scope.loading = false;
								$scope.errMsg = "The selected search criteria did not find any results.";
							}
							else
							{
								$scope.expClassSheet = result.data;
								$scope.expClassSheet.forEach((item, i) => {
									item.subjects = item.subjects.split(',');
								});
								$scope.classSheetSubjs = $scope.expClassSheet[0].subjects;

								$scope.showExpSheet = true;
								$scope.showUploadSheet = false;
								console.log("Class Sheet >",$scope.expClassSheet);
								// console.log("Subj Headers >",$scope.classSheetSubjs);
							}
						}
						else
						{
							$scope.loading = false;
							$scope.errMsg = result.data;
						}
					}, apiError);
				}else if($scope.filters.action_type == "upload_csv"){
					// console.log("Should Upload");
					$scope.importFilterBtn = "Processing Exam Sheet";
					$scope.showUploadBtn = true;
					// console.log($scope.csvData);
					// Check for the various File API support.
					if (window.File && window.FileReader && window.FileList && window.Blob) {
						$scope.showUploadSheet = true;
						$scope.showExpSheet = false;
					  // Great success! All the File APIs are supported.
						// console.log("File API's are supported");
						let inp = document.getElementById('csvInp').files[0];
						// console.log(inp);
						var reader = new FileReader();
						reader.readAsArrayBuffer(inp);
						reader.onload = function(e) {
							// load external excel script
							$.getScript('/components/xlsx.full.min.js', function()
							{
								$scope.importReady = true;
								var data = new Uint8Array(reader.result);
								var wb = XLSX.read(data,{type:'array'});
								var processThisObj = {};
								processThisObj.data = wb;
								var nameOfSheet = processThisObj.data.SheetNames[0];
								// console.log(processThisObj.data);
								var htmlstr = XLSX.write(wb,{sheet:nameOfSheet, type:'binary',bookType:'html'});
								// console.log(htmlstr);

								$('#csvWrapper')[0].innerHTML += htmlstr;
								let tbl = document.getElementById('csvWrapper').getElementsByTagName("table")[0];
								tbl.style.width = '95%';
								tbl.style.marginLeft = 'auto';
								tbl.style.marginRight = 'auto'; // set headers bg to green
								let tbody = tbl.getElementsByTagName("tbody")[0];
								let tr = tbody.getElementsByTagName("tr");
								tr[1].style.backgroundColor = '#0BDA51';
								tr[1].style.fontWeight = '600';

								var cols = [];
								var testCols = [];
								var result = [];
								$(tr[1]).map(function(index) {
									// testCols.push($(this).find('td'));
									let theTd = $(this).find('td');
									for (var i = 0; i < theTd.length; i++) {
										cols.push(theTd[i].innerText);
									}
								});
								// console.log("test Cols >",cols);
								$(tr).each(function(id){
								    var row = {'id': id+1};
								    $(this).find('td').each(function(index){
								        row[cols[index]] = $(this).text();
												if(row.id == 1){
													if(row[cols[index]]){
														// console.log("First row >",row[cols[index]]);
														let filters = row[cols[index]].split('/');
														// console.log($scope.filters,$rootScope,$scope);
														$scope.filters.class_id = parseInt(filters[0]);
														$scope.filters.term_id = parseInt(filters[1]);
														$scope.filters.exam_type_id = parseInt(filters[2]);
														// get the class for the uploaded csv
														var filteredClass = $scope.classes.filter(function(item){
															if(item.class_id == $scope.filters.class_id){
																// console.log("Class >",item);
																let selectedClass = item.class_name;
																// get the exam types for the uploaded csv
																apiService.getExamTypes(item.class_cat_id, function(response){
																	var result = angular.fromJson(response);
																	if( result.response == 'success' && !result.nodata ){
																		let examTypes = result.data;
																		var filteredExmTypes = examTypes.filter(function(item3){
																			if(item3.exam_type_id == $scope.filters.exam_type_id){
																				console.log("Exam Type >",item3);
																				let selectedExmType = item3.exam_type;
																				// get the term for the uploaded csv
																				$scope.filteredTerm = $scope.terms.filter(function(item2){
																					if(item2.term_id == $scope.filters.term_id){
																						console.log("Term >",item2);
																						let selectedTerm = item2.term_name;
																						$scope.uploadTitle = "( " + selectedClass + ", " + selectedTerm + ", " + selectedExmType + " Exam )";
																						$scope.showUploadTitle = true;
																						$scope.importFilters = {term_id: item2.term_id, exam_type_id: item3.exam_type_id, class_cat_id: item.class_cat_id, class_id: item.class_id};
																						console.log("Import Filters >",$scope.importFilters);
																					}
																				});
																			}
																		});
																	}
																}, apiError);

															}
														});
													}
												}
												if(row.id > 2){
													row.student_id = parseInt(row.STUDENT.split(' ')[0].match(/\(([^)]+)\)/)[1]);
												}
								    });
								    result.push(row);
								});

								setTimeout(function(){ // half a sec to allow the above operation time
									let studentsCsvData = [];
									// console.log("Result >",result);
									result.forEach((student, i) => {
										if(i>1){
											// console.log(i,student);
											let studentObj = {
												student_id: student.student_id,
												subjects: []
											}

											for (let prop in student) {
												let skip = ["student_id", "id", "STUDENT"];
												if(!skip.includes(prop)){
													let subjObj = {
														subject_name: prop,
														subject_id: parseInt(prop.split(' ')[0].match(/\(([^)]+)\)/)[1]),
														mark: (student[prop] == "" || student[prop] == null ? null : parseInt(student[prop]))
													}
													studentObj.subjects.push(subjObj);
												}
											  // console.log(`${prop}: ${dataObj[prop]}`);
											}
											// console.log(studentObj);
											studentsCsvData.push(studentObj);
										}
									});
									// console.log(studentsCsvData);
									// we want to remove null subject marks to reducee the overall payload
									let importPayload = [];
									studentsCsvData.forEach((studentObj, i) => {
										studentObj.subjects = studentObj.subjects.filter(function( obj ) {
										    return obj.mark !== null;
										});
										if(studentObj.subjects.length > 0){importPayload.push(studentObj);}
									});
									console.log(importPayload);

									var data = {
										user_id: $rootScope.currentUser.user_id,
										exam_marks: importPayload,
										params: $scope.importFilters
									}
									console.log("Data >",data);

									$( "#imptReady" ).click(function() {
										apiService.importExamMarks(data,
																							function ( response, status, params )
																							{
																								var result = angular.fromJson( response );
																								if( result.response == 'success' )
																								{
																									console.log(result);
																									alert("Exam marks have been successfully imported.")
																									// $uibModalInstance.close();
																									// clear the inputs and filters etc
																								}else{
																									$scope.error = true;
																									$scope.errMsg = result.data;
																								}
																							},
																							apiError);
									});
								}, 500);

							/*

							 */

							});
						}
					} else {
					  alert('File functionalities are not fully supported in this browser. Try updating your browser to the lastest versioin or using a different one.');
					}
				}
			}
		}else{
			$scope.importFilterBtn = "Make A Selection";
			$scope.importBtnStatus = "danger";
		}
	}

	$scope.exportData = function(){

		var divToExport=document.getElementById("expSheetDiv");
		var attr = $('table.expSheetDiv tr').attr('style');
		if(typeof attr !== typeof undefined && attr !== false){
			console.log("Table is unfiltered");
		}else{
			console.log("Table has been filtered");
		}

		var rows = document.querySelectorAll('table.expSheetDiv tr[style="display: table-row;"]');
		if(rows.length == 0){
			var rows = document.querySelectorAll('table.expSheetDiv tr');
		}

		function downloadCSV(csv, filename) {
		    var csvFile;
		    var downloadLink;

		    // CSV file
		    csvFile = new Blob([csv], {type: "text/csv"});

		    // Download link
		    downloadLink = document.createElement("a");

		    // File name
		    downloadLink.download = filename;

		    // Create a link to the file
		    downloadLink.href = window.URL.createObjectURL(csvFile);

		    // Hide download link
		    downloadLink.style.display = "none";

		    // Add the link to DOM
		    document.body.appendChild(downloadLink);

		    // Click download link
		    downloadLink.click();
		}

		function exportTableToCSV(filename) {
		    var csv = [];
		    // var rows = document.querySelectorAll('table tr[style*="display: table-row;"]');
				console.log("rows data",typeof rows);
				var headerRow = divToExport.querySelectorAll('table thead tr')[0];
				var titles = headerRow.querySelectorAll("th");
				var titlesText = [];
				for(let x=0; x<titles.length;x++){
					titlesText.push(titles[x].innerText);
				}
				var titlesToCsv = titlesText.join(',');
				console.log(titlesToCsv);
				csv.push(titlesToCsv);

		    for (var i = 0; i < rows.length; i++) {
		        var row = [], cols = rows[i].querySelectorAll("td, th");

		        for (var j = 0; j < cols.length; j++)
		            row.push(cols[j].innerText);

		        csv.push(row.join(","));
		    }

		    // Download CSV file
		    downloadCSV(csv.join("\n"), filename);
		}

		exportTableToCSV($scope.filters.class.class_name + '_class_sheet' + '.csv');

	}

	$scope.actionChanged = function(){
		console.log($scope.filters.action_type);
		$scope.showUploadBtn = ($scope.filters.action_type == 'upload_csv' ? true : false);
		$scope.notCsvUpld = ($scope.showUploadBtn ? false : true);
	}

	$scope.visualizeUpload = function(){
		console.log("$scope.csvData >",$scope.csvData);
		console.log("Init Read");
	}

	$scope.$on('refreshExamMarks', function(event, args) {

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
		$scope.refreshing = true;
		$rootScope.loading = true;
		$scope.getStudentExams();
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	$scope.$on('$destroy', function() {
		if($scope.dataGrid){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.fixedHeader.destroy();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		$rootScope.isModal = false;
    });


} ]);
