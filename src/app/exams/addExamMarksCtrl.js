'use strict';

angular.module('eduwebApp').
controller('addExamMarksCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window){

	$scope.classes = data.classes;
	$scope.terms = data.terms;
	$scope.examTypes = data.examTypes;
	$scope.filters = data.filters;
	var ignoreCols = ['student_id','student_name','sum','total'];
	var classExamTypes = function(cat){
		console.log("Fetching class exam types",cat);
		apiService.getExamTypes(cat, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success'){ console.log("Exam types found > ",result.data);$scope.toExamTypesOptions = result.data;}
		}, apiError);
	}

	$scope.isTeacher = ($rootScope.currentUser.user_type == 'TEACHER' ? true : false);
	$scope.advancedBtns = false;

	apiService.getTerms(undefined, function(response,status)
	{
		var result = angular.fromJson(response);
		if( result.response == 'success'){$scope.allTerms = result.data;}
	}, function(err){ console.log('An error was encountered:',err); });

	var initializeController = function()
	{
		document.getElementsByClassName('modal-lg')[0].style.width = '85%';
		//getClassDetails($scope.filters.class_id);
		//$scope.getStudentExams();
		// $scope.allTerms = getAllTerms();
	}
	setTimeout(initializeController,1);


	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.filters.class_id = newVal.class_id;
		//getClassDetails($scope.filters.class_id);

		apiService.getExamTypes(newVal.class_cat_id, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success'){ $scope.examTypes = result.data;}
		}, apiError);
	});

	/*
	var getClassDetails = function(classId)
	{
		apiService.getAllClassExams(classId,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success'){ $scope.classSubjectExams = result.data; }
		}, apiError);
	}
*/


	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	$scope.getStudentExams = function(theForm)
	{
		theForm.$submitted = true;
		if( !theForm.$invalid )
		{
			$scope.examMarks = {};
			$scope.marksNotFound = false;

			if( $scope.dataGrid !== undefined )
			{
				$scope.dataGrid.destroy();
				$scope.dataGrid = undefined;
			}

			var request = $scope.filters.class_id + '/' + $scope.filters.exam_type_id;
			request += ( $scope.isTeacher ? '/' + $rootScope.currentUser.emp_id : '/0');
			apiService.getAllClassExams(request, function(response){
				$scope.loading = false;
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					if( result.nodata !== undefined)
					{
						$scope.marksNotFound = true;
						$scope.errMsg = "The selected exam is not set up for this class.";
					}
					else
					{
						$scope.subjects = result.data;

						var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
						if( $scope.isTeacher ) request += '/' + $rootScope.currentUser.emp_id;
						apiService.getClassExamMarks(request, loadMarks, apiError);

						$scope.advancedBtns = true;
					}
				}
			}, apiError)
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
				$scope.errMsg = "There are no students in the selected classes.";
			}
			else
			{
				$scope.students = result.data;
				$scope.currentFilters = angular.copy($scope.filters);

				// loop through exam marks and build into
				// one object per student, with
				$scope.examMarks = [];
				var lastStudent = '';
				var marks = {};
				var i = 0;
				angular.forEach($scope.students, function(item,key){
					if( item.student_id != lastStudent )
					{
						// changing to new student, store the report
						if( i > 0 ) $scope.examMarks[(i-1)].marks = marks;

						$scope.examMarks.push(
							{
								student_name: item.student_name,
								student_id: item.student_id,
								marks : {}
							}
						);

						marks = {};
						i++;

					}

					var thesubject = $scope.subjects.filter(function(subject){
						if ( subject.subject_name == item.subject_name ) return subject;
					})[0];

					// console.log(thesubject.subject_name);
					// if(thesubject.subject_name !== undefined){
					marks[thesubject.subject_name] = {
						mark: item.mark,
						class_sub_exam_id: item.class_sub_exam_id,
						grade_weight: item.grade_weight,
						is_parent: item.is_parent,
						parent_subject_id : item.parent_subject_id || undefined,
						subject_id : item.subject_id
					};
					// }

					lastStudent = item.student_id;

				});
				$scope.examMarks[(i-1)].marks = marks;

				// console.log($scope,$rootScope);
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.calculateParentSubject = function(marks, markObj)
	{
		var parent_id = markObj.parent_subject_id;
		if( parent_id !== undefined )
		{
			var children = [];
			var parent = null;


			angular.forEach(marks, function(item,key){
				// get marks for children subjects
				if( item.parent_subject_id == parent_id ) children.push(item);
				else if(item.subject_id == parent_id ) parent = item;
			});

			// add them up
			var total = children.reduce(function(sum,item){
				sum += parseFloat(item.mark) || 0;
				// console.log("mark summation :: " + sum);
				return sum;
			},0);
			var totalWeight = children.reduce(function(sum,item){
				sum += parseFloat(item.grade_weight) || 0;
				// console.log("gw summation :: " + sum);
				return sum;
			},0);

			children.reduce(function(sum,item){
				// if( item.mark > item.grade_weight ){
    // 			    console.log("Marks exceed total");
    // 			}else if( item.mark <= item.grade_weight ){
    // 			    console.log("Marks are within range");
    // 			}
			},0);

			parent.mark = Math.round( (total/totalWeight)*100 ) ;
		}

		//This function is to help us highlight errors as it happens
		    var activeInpId = document.activeElement.id;
    	    var activeInpVal = document.activeElement.value;
    	    var activeInpMax = document.activeElement.max;
    	    var pickElement = document.getElementById(activeInpId);
    	    // console.log("The current value in focus is (" + activeInpVal + ") and it's max is (" + activeInpMax + ")");

    	    // console.log(parseInt(activeInpVal) > parseInt(activeInpMax));
    	    if( parseInt(activeInpVal) > parseInt(activeInpMax) ){
    	        // console.log("Error. Value (" + activeInpVal + ") exceeds max (" + activeInpMax + ")");

    	        pickElement.style.border = '2px solid';
    	        pickElement.style.outline = 'none';
    	        pickElement.style.borderColor = '#E60000';
    	        pickElement.style.boxShadow = '0 0 10px #E60000';
    	    }else{
    	        // console.log("Current value in range");

    	        pickElement.style.border = '';
    	        pickElement.style.outline = '';
    	        pickElement.style.borderColor = '';
    	        pickElement.style.boxShadow = '';
    	    }
    	   // console.log(document.activeElement.id);


		$scope.highlightError = function(){
    	   // var activeInpId = document.activeElement.id;
    	   // var activeInpVal = document.activeElement.value;
    	   // var activeInpMax = document.activeElement.max;
    	   // console.log("The current value in focus is (" + activeInpVal + ") and it's max is (" + activeInpMax + ")");
    	   // if( activeInpVal > activeInpMax ){
    	   //     console.log("Error. Value exceeds max");
    	   // }else{
    	   //     console.log("Current value in range");
    	   // }
    	   // console.log(document.activeElement.id);
    	   // var pickElement = document.getElementById('contents');
    	}
	}

	var initDataGrid = function()
	{
		var tableElement = $('#resultsTable2');
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
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();

			$('#resultsTable_filter').css('left',filterFormWidth+55);
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

	$scope.printExams = function()
	{
		$('#resultsTable2').printThis({
    				debug: false,
        		    importCSS: true,
        		    importStyle: true,
        		    printContainer: true,
        		    loadCSS: "css/printMarkSheet.css",
        		    pageTitle: "Exam Marks Mark Sheet",
        		    removeInline: false,
        		    printDelay: 333,
        		    header: null,
        		    formValues: true
          });
	};

	//download table as CSV
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

    $scope.exportTableToCSV = function(filename) {
        //we first hide the grade weights (out-of's)
        var elements = document.getElementsByClassName('input-group-addon')

        for (var i = 0; i < elements.length; i++){
            elements[i].style.display = 'none';
        }
        //the download
        var csv = [];
        var rows = document.querySelectorAll("table tr");

        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");

            for (var j = 0; j < cols.length; j++)
                row.push(cols[j].innerText);

            csv.push(row.join(","));
        }

        // Download CSV file
        downloadCSV(csv.join("\n"), filename);
    }

    //download table as XLS
    $scope.exportTableToXLS = function() {
       //Creates new Generator
       excel = new ExcelGen({
           "src_id": "resultsTable2",
           "show_header": true,
           "file_name": window.location.host.split('.')[0] + "_mark-sheet.xlsx"
       });
       //Generates Excel Output and sends download to the browser.
       excel.generate();
    }

	$scope.save = function(form)
	{

		if ( !form.$invalid )
		{

			var examMarks = [];
			angular.forEach($scope.examMarks, function(item,index){

				var exam = undefined;
				// need to get class_sub_exam_id
				angular.forEach(item.marks, function(mark,key){
					examMarks.push({
						student_id : item.student_id,
						class_sub_exam_id: mark.class_sub_exam_id,
						term_id: $scope.currentFilters.term_id,
						mark: mark.mark,
						parent_subject_id: mark.parent_subject_id || undefined
					});
				});
			});


			var data = {
				user_id: $rootScope.currentUser.user_id,
				exam_marks: examMarks
			}

			apiService.addExamMarks(data,createCompleted,apiError);

		}
	}

	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$uibModalInstance.close();
			var msg = ($scope.edit ? 'Exam mark was updated.' : 'Exam marks were added.');
			if( data.viewing !== undefined && data.viewing == 'report')  $rootScope.$emit('examMarksAdded2', {'msg' : msg, 'clear' : true});
			else $rootScope.$emit('examMarksAdded', {'msg' : msg, 'clear' : true});
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.advancedMarksEdit = function(){
		console.log($scope.currentFilters);
		document.getElementById('advBtn').disabled = false;
		$scope.btnState = 'warning';
		$scope.toOperate = 'move';
		$scope.isMove = true;
		$scope.isDelete = false;
		$scope.btnText = 'MOVE MARKS';
		$scope.btnIcon = 'random';
		//set from class
		let fromClassOptions = $scope.currentFilters.class;
		$scope.fromClassOptions = [fromClassOptions];
		$scope.fromClass = $scope.fromClassOptions[0];
		$scope.toClassOptions2 = [$scope.currentFilters.class];
		console.log("To Class Options >",$scope.toClassOptions2);
		$scope.toClss = $scope.toClassOptions2[0];
		classExamTypes($scope.currentFilters.class.class_cat_id);
		// console.log($scope.toExamTypesOptions);
		$("#move_from_class").mousedown(function(event){ event.preventDefault(); });
		//set from exam
		let fromExamOptions = {};
		fromExamOptions.exam_type_id = $scope.currentFilters.exam_type_id;
		fromExamOptions.exam_type = null;
		$scope.examTypes.forEach((et, i) => {
			if(et.exam_type_id == fromExamOptions.exam_type_id){
				fromExamOptions.exam_type = et.exam_type;
			}
		});
		$scope.fromExamOptions = [fromExamOptions];
		// console.log($scope.fromExamOptions);
		$scope.fromExam = $scope.fromExamOptions[0];
		//set from term
		let fromTermOptions = {};
		if($scope.allTerms){
			$scope.allTerms.forEach((term, i) => {
				if(term.term_id == $scope.currentFilters.term_id){fromTermOptions = term;}
			});
		}
		$scope.fromTermOptions = [fromTermOptions];
		$scope.fromTerm = $scope.fromTermOptions[0];
		console.log($scope.fromTermOptions);
		$("#move_from_exam").mousedown(function(event){ event.preventDefault(); });
		//to classe
		$scope.toClass = null;
		$scope.toClassOptions = $scope.classes.sort((a, b) => (a.class_name > b.class_name) ? 1 : -1);
	}

	$scope.fetchClassExams = function(el){
		console.log(el);
		apiService.getExamTypes(el.toClass, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success'){
				$scope.toExamTypesOptions = result.data;
				document.getElementById('move_to_exam').style.border = '3px solid #00bfff';
				setTimeout(function(){ document.getElementById('move_to_exam').style.border = ''; }, 1500);
			}
		}, apiError);
	}

	$scope.finalSelection = function(el){
		// console.log(el);
		$scope.btnState = 'info';
		let data = {
			from_class_id: $scope.fromClass.class_id,
			from_class_cat_id: $scope.fromClass.class_cat_id,
			from_exam_type_id: $scope.fromExam.exam_type_id,
			to_class_id: null,
			to_class_cat_id: null,
			to_exam_type_id: el.toExam
		}
		$scope.classes.forEach((clss, i) => {
			if(clss.class_cat_id == el.toClass){
				data.to_class_id = clss.class_id;
				data.to_class_cat_id = clss.class_cat_id;
			}
		});
		console.log(data);
	}

	$scope.switchOperation = function(){
		console.log($scope.toOperate);
		if($scope.toOperate == 'move'){
			$scope.isMove = true;
			$scope.isDelete = false;
			$scope.btnState = 'warning';
			$scope.btnText = 'MOVE MARKS';
			$scope.btnIcon = 'random';
		}else if($scope.toOperate == 'delete'){
			$scope.isDelete = true;
			$scope.isMove = false;
			$scope.btnState = 'danger';
			$scope.btnText = 'DELETE MARKS';
			$scope.btnIcon = 'warning-sign';
		}
		$("#move_from_class").mousedown(function(event){ event.preventDefault(); });
		$("#move_from_exam").mousedown(function(event){ event.preventDefault(); });
	}

	$scope.advancedEdit = function(){
		// console.log($scope);

		let data = {
			operation: $scope.toOperate,
			term_id: $scope.filters.term_id,
			marks: []
		}
		if(data.operation == 'move'){
			data.from_class_id = $scope.fromClass.class_id;
			data.from_et_id = $scope.fromExam.exam_type_id;
			data.from_term_id = $scope.fromTerm.term_id;
			data.to_class_id = $scope.toClss.class_id;
			data.to_et_id = parseInt($scope.toExam);
			data.to_term_id = parseInt($scope.toTerm);
		}else if(data.operation == 'delete'){
			data.from_class_id = $scope.fromClass.class_id;
			data.from_et_id = $scope.fromExam.exam_type_id;
		}

		for(let s=0;s < $scope.examMarks.length;s++){

			for (let subjName in $scope.examMarks[s].marks) {
				let stdnt = {};
				stdnt.student_id = $scope.examMarks[s].student_id;
			  stdnt.subject = subjName;
				stdnt.class_sub_exam_id = $scope.examMarks[s].marks[subjName].class_sub_exam_id;
				stdnt.subject_id = $scope.examMarks[s].marks[subjName].subject_id;
				// console.log(stdnt);
				data.marks.push(stdnt);
			}
		}
		console.log(data);

		apiService.advncedExamEdit(data, function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$scope.btnState = 'info';
				$scope.btnText = 'SUCCESS';
				$scope.btnIcon = 'ok-circle';
				setTimeout(function(){
					// $scope.cancel();
					document.getElementById('advBtn').disabled = true;
					var request = $scope.filters.class_id + '/' + $scope.filters.exam_type_id;
					request += ( $scope.isTeacher ? '/' + $rootScope.currentUser.emp_id : '/0');
					apiService.getAllClassExams(request, function(response){
						$scope.loading = false;
						var result = angular.fromJson( response );
						if( result.response == 'success' )
						{
							if( result.nodata !== undefined)
							{
								$scope.marksNotFound = true;
								$scope.errMsg = "The selected exam is not set up for this class.";
							}
							else
							{
								$scope.subjects = result.data;

								var request = $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
								if( $scope.isTeacher ) request += '/' + $rootScope.currentUser.emp_id;
								apiService.getClassExamMarks(request, loadMarks, apiError);

								$scope.advancedBtns = true;
							}
						}
					}, apiError)
				}, 2500);

			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		}, function(response,status)
		{
			var result = angular.fromJson( response );
			console.log(result);
		});

	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

} ]);
