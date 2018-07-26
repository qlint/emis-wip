'use strict';

angular.module('eduwebApp').
controller('subjectMarkListCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window){

	$scope.postSubjectMarkList = {};
	// $scope.classes = data.classes;
	// $scope.terms = data.terms;
	// $scope.examTypes = data.examTypes;
	// $scope.filters = data.filters;
	var ignoreCols = ['student_id','student_name','admission_number','sum','total'];

	$scope.isTeacher = ($rootScope.currentUser.user_type == 'TEACHER' ? true : false);

	var initializeController = function()
	{
		//getClassDetails($scope.filters.class_id);
		//$scope.getStudentExams();
	}
	setTimeout(initializeController,1);


	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.filters.class_id = newVal.class_id;
		//getClassDetails($scope.filters.class_id);

		apiService.getExamTypes($scope.filters.class_cat_id, function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success'){ $scope.examTypes = result.data;}
		}, apiError);
	});

	/*
	var getClassDetails = function(classId)
	{
		apiService.getAllClassExams(classId,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				$scope.classSubjectExams = result.data;
			}
		}, apiError);
	}
*/


	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	$scope.getMarkList = function()
	{
		// console.log("Class cat = " + $scope.filters.class_cat_id);
		//get the selected class's subjects
		var params = $scope.filters.class_cat_id;
		$scope.postSubjectMarkList.class_cat_id = params;
		$scope.postSubjectMarkList.teacher = [];
		apiService.getClassCatSubjects(params, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				// console.log("Class subjects success");
				$scope.subjects = result.data;
				$scope.subjects.forEach(function(obj) {
					obj.teacherLabel = 'Teacher';
					/*obj.teacherEmpId = '#';*/

					if( obj.parent_subject_id == null )
					{
						obj.is_parent = false;
					}else{
						obj.is_parent = true;
					}

				});
				// $scope.subjects.teacherLabel = 'Teacher';
				// $scope.subjects.teacherId = '#';

				// console.log($scope.subjects);
				return $scope.subjects;

			}

		},
		function (response, status)
			{
				console.log("There's a problem getting the subjects.");
				var result = angular.fromJson( response );
				console.log(result);
			});
		//end of getClassSubjects api
		// setTimeout(function(){ console.log($scope.subjects); }, 300);

		//get all teachers
		apiService.getAllTeachers(true,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				// console.log("Teachers success");
				$scope.teachers = result.data;
				// console.log($scope.teachers);
				return $scope.teachers;
			}
		},
		function (response, status)
			{
				console.log("There's a problem getting the list of teachers.");
				var result = angular.fromJson( response );
				console.log(result);
			});
		//end of getAllTeachers api

		//get temporary subject teachers
		apiService.getTemporaryTeacher(params,function(response){
			var result = angular.fromJson(response);
			if( result.response == 'success')
			{
				console.log("Temporary Teachers success");
				$scope.temporaryTeachers = result.data; //Existing temporary subject teachers
				console.log($scope.temporaryTeachers);

				var fillCheckbox = []; //Our array to fill the checkboxes
				$scope.temporaryTeachers.forEach(function(element) {
					//We create an object that'll match teacher rows and subject columns on the table
					var perCheckbox = {teacher_id: element.tmp_tchr_id, subject_id: element.subject_id};
					//We only want existing values to be able to check/tick the checkboxes on the table
					if(perCheckbox.tmp_tchr_id !== null || perCheckbox.tmp_tchr_id !== undefined){
						//Push the existing non-null objects to the empty fillCheckbox array
						console.log("Pushing");
						fillCheckbox.push(perCheckbox);
						console.log(fillCheckbox);
					}
				});
				// console.log(fillCheckbox );
				//Iterate over the new array and 'check' the checkboxes
				var n = $scope.teachers;
				var c = $scope.subjects;
				console.log("There are (" + fillCheckbox.length + ") temporary subject teachers");
				for(var i=0; i<fillCheckbox.length; i++){
					var ni = n.map(function(_) { return _.teacher_id; }).indexOf(fillCheckbox[i].teacher_id); // match the index of `teacher_id`
					var ci = fillCheckbox[i].subject_id-1; // or like above -> c.map(...).indexOf(...) // but for `subject_id`
					$scope.fillCheckbox[ni][ci] = true;
					if( $scope.fillCheckbox[ni][ci] == true ){
						var checkboxId = $scope.teachers.teacher_id + $scope.subjects.subject_name;
						document.getElementById(checkboxId).checked = true;
					}
				}

				return $scope.temporaryTeachers;
			}
		},
		function (response, status)
			{
				console.log("There's a problem getting the list of temporary teachers.");
				var result = angular.fromJson( response );
				console.log(result);
			});
		//end of get temporary subject teachers

		var checked_boxes = $scope.temporaryTeachers;

		function printThis(){
			$(document).ready(function() {
				var table = $('#resultsTable2').DataTable( {
						fixedHeader: true,
						"scrollX": true,
						keys: true,
						paging: false,
						dom: 'Bfrtip',
						buttons: [
								// 'excelHtml5',
								// 'csvHtml5',
								// 'pdfHtml5',
								{
									extend: 'excelHtml5',
									title: 'Mark-Sheet'
							},
							{
								extend: 'csvHtml5',
								title: 'Mark-Sheet'
						},
							{
									extend: 'pdfHtml5',
									title: 'Mark-Sheet',
									orientation: 'landscape',
									pageSize: 'A4'
							}
						]
				} );
				$('a.toggle-vis').on( 'click', function (e) {
					e.preventDefault();

					// Get the column API object
					var column = table.column( $(this).attr('data-column') );

					// Toggle the visibility
					column.visible( ! column.visible() );
			} );
			// editor.on( 'open', function ( e, mode, action ) {
			// 		if ( mode === 'inline' ) {
			// 				editor.on( 'postSubmit.editorInline', function () {
			// 						var focused = table.cell( { focused: true } );
			//
			// 						if ( focused.any() ) {
			// 								var next = $(focused.node()).next();
			//
			// 								if ( next.length ) {
			// 										editor.one( 'submitComplete', function () {
			// 												table.cell( next ).focus();
			// 										} );
			// 								}
			// 						}
			// 				} );
			// 		}
			// } );

			// editor.on( 'close', function () {
			// 		editor.off( 'postSubmit.editorInline' );
			// } );
			} );
		}
		printThis();
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
						emptyTable: "No data found."
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

	$scope.checked_ones = [];
	$scope.change = function(col,item){
		// console.log(col);
		// console.log(item);
		var markData = { teacher_id: item.teacher_id, subject_id: col.subject_id};
		// console.log(markData);
		$scope.checked_ones.push(markData);
	    // if (checkboxes){
	    //     $scope.checked_ones.push(col);
			// }
	    // else{
	    //     $scope.checked_ones.splice($scope.checked_ones.indexOf(col), 1);
			// }
			// console.log($scope.checked_ones);
	};


	$scope.save = function(form)
	{
		console.log("Saving..."); //console.log(form.$invalid);

		if ( form.$invalid == true )
		{

			console.log($scope.checked_ones);

			var n = $scope.teachers;
			var c = $scope.subjects;
			$scope.checkboxes = n.map( function(x) {
		    return c.map( function(y) {
		      return false;
					// console.log("Step one");
		    });
		  });



			// var data = {
			// 	class_cat_id: $scope.postSubjectMarkList.class_cat_id,
			// 	tmp_tchr_id: $scope.postSubjectMarkList.teacher.teacher_id,
			// 	subject_id: $scope.postSubjectMarkList.teacher.subject_id
			// }
			var data = {
				class_cat_id: $scope.postSubjectMarkList.class_cat_id,
				mark_data: $scope.checked_ones
			}

			// console.log(data);
			apiService.setTemporaryTeacher(data,createCompleted,apiError);
		}
	}

	// console.log();

	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			console.log("Updated successfully.");
			$uibModalInstance.close();
			// console.log(response);
			// console.log(status);
		}
		else
		{
			console.log("Something went wrong >>");
			console.log(response);
			console.log(status);
		}
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

} ]);
