'use strict';

angular.module('eduwebApp').
controller('dashboardCtrl', ['$scope', '$rootScope', 'apiService',
function($scope, $rootScope, apiService){

	$scope.studentsLoading = true;
	$scope.staffLoading = true;
	$scope.examsLoading = true;
	$scope.fees1Loading = true;
	$scope.fees2Loading = true;
	$scope.fees3Loading = true;
	$scope.newsLoading = true;

	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false);
	$scope.isTransport = ( $rootScope.currentUser.user_type == 'ADMIN-TRANSPORT' ? true : false);
	if($rootScope.currentUser.super_teacher == true){ $rootScope.currentUser.user_type = 'SYS_ADMIN'; $scope.isTeacher = false; }

	var getStudentCount = function()
	{

		apiService.getClassCatsSummary(status, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.classCats = result.data;

				if(Array.isArray($scope.classCats)){

					if($rootScope.currentUser.class_cat_limit != null || $rootScope.currentUser.class_cat_limit != undefined){
						$rootScope.currentUser.class_cat_limit = $rootScope.currentUser.class_cat_limit.split(',');
						$scope.classCats = $scope.classCats.filter(cat => $rootScope.currentUser.class_cat_limit.includes(cat.class_cat_id));
					}

					for(var f=0; f < $scope.classCats.length; f++){
					    var parseThis = $scope.classCats[f].classes;
							function isEmpty(obj) {
							    if(obj == "{}" || obj == "{ }" || obj == " {}" || obj == "{} " || obj == " {} "){
										return true
									}else{
										return false
									}
							}
							if(!isEmpty(parseThis)){
								// console.log(f,parseThis);
						    var stripOuter = parseThis.substring(1, parseThis.length-1);
						    var stripInner = stripOuter.substring(1, stripOuter.length-1);
						    var replaceInner = stripInner.replace(/","/g, ",");
						    var replaceAll = replaceInner.replace(/"/g, ",");

						    var objectBlueprint = replaceAll
		                      .match(/\{[^}]*\}/g)
		                      .map(objString => objString.slice(1, -1))
		                      .map(item => item.split(/\s*,\s*/))
		                      .map(item => item.map(subitem => subitem.split(/\s*:\s*/)));

		                    //convert each object
		                    let output = objectBlueprint.map(fromArrayToObject)

		                    for(var j=0; j < output.length; j++){
		                        output[j].tot = output[j].boys + output[j].girls;
		                    }

		                    $scope.classCats[f].classes = output;

		                    function fromArrayToObject(keyValuePairs) {
		                      return keyValuePairs.reduce((obj, [key, value]) => {
		                        obj[key] = guessType(value);

		                        return obj;
		                      }, {})
		                    }

		                    function guessType(value) {
		                      try {
		                        return JSON.parse(value)
		                      } catch (e) {
		                        return value;
		                      }
		                    }
									} // end null check
					}
				}
				$scope.studentsLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.studentsLoading = false;
			}

		}, function(){});
	}

	var getTeacherClasses = function()
	{
		var params = $rootScope.currentUser.emp_id + '/true';
		apiService.getTeacherClasses(params, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.myClasses =  ( result.nodata !== undefined ? [] : result.data);
				$scope.studentsLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.studentsLoading = false;
			}

		}, function(){});
	}

	var getTeacherSubjects = function()
	{
		var params = $rootScope.currentUser.emp_id + '/true';
		apiService.getTeacherSubjects(params, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.subjects = ( result.nodata !== undefined ? [] : result.data);
				$scope.studentsLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.studentsLoading = false;
			}

		}, function(){});
	}


	var getStaffCount = function()
	{
		apiService.getDeptSummary(status, function(response){

			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.deptCats = result.data;
				$scope.staffLoading = false;
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
				$scope.staffLoading = false;
			}

		}, function(){});
	}

	var getFeeSummary = function()
	{
		// get current term
		apiService.getCurrentTerm({},function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.currentTerm = result.data || undefined;
				if( $scope.currentTerm !== undefined )
				{
					$scope.currentTermTitle = $scope.currentTerm.term_name + ' ' + $scope.currentTerm.year;
					var end_date = moment().add(1,'day').format('YYYY-MM-DD');
					$scope.date = {startDate: $scope.currentTerm.start_date, endDate: end_date};
					getPaymentsReceived($scope.currentTerm.start_date, end_date);
				}
				else
				{
					$scope.fees1Loading = false;
					$scope.numPaymentsReceived = 0;
				}
			}
		},function(){});

		// get payments due this month
		var start_date = moment().startOf('month').format('YYYY-MM-DD');
		var end_date = moment().endOf('month').format('YYYY-MM-DD');
		getPaymentsDue(start_date, end_date);

		getOverDuePayments();
	}

	var getPaymentsReceived = function(startDate, endDate)
	{
		// get payments received for current term, that has not been reversed
		var request = startDate + "/" + endDate + "/false";
		apiService.getPaymentsReceived(request, loadPaymentsReceived, apiError);
	}

	var loadPaymentsReceived = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.numPaymentsReceived = ( result.nodata !== undefined ? 0 : result.data.length);
			$scope.fees1Loading = false;
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.fees1Loading = false;
		}
	}

	var getPaymentsDue = function(startDate, endDate)
	{
		// get payments received for curren term
		var request = startDate + "/" + endDate;
		apiService.getPaymentsDue(request, loadPaymentsDue, apiError);
	}

	var loadPaymentsDue = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.numPaymentsDue = ( result.nodata !== undefined ? 0 : result.data.length);
			$scope.fees2Loading = false;
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.fees2Loading = false;
		}
	}

	var getOverDuePayments = function()
	{
		apiService.getPaymentsPastDue({}, loadPaymentsPastDue, apiError);
	}

	var loadPaymentsPastDue = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.numPaymentsPastDue = ( result.nodata !== undefined ? 0 : result.data.length);
			$scope.fees3Loading = false;
		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.fees3Loading = false;
		}
	}

	var getStudentGenderCount = function()
	{
		apiService.studentGenderCount({}, processGenderCount, apiError);
	}

	var processGenderCount = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{

			$scope.maleCount = result.data[0].male;
			$scope.femaleCount = result.data[0].female;
			$scope.totalCount = result.data[0].total;

			if($scope.maleCount == "0"){
			    var genderOverview = "Girls(" + $scope.femaleCount + ")";
			}else if($scope.femaleCount == "0"){
			    var genderOverview = "Boys(" + $scope.maleCount + ")";
			}else{
			    $('#studentModuleIcon').css('margin-top','27px');
			    var totalGnd = Number($scope.maleCount) + Number($scope.femaleCount);
			    var genderOverview = "Boys(" + $scope.maleCount + ") Girls(" + $scope.femaleCount + ") Tot(" + totalGnd + ")";
			}
			$scope.genderCount = genderOverview;
		}
		else
		{
		    $scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.fees3Loading = false;
		}
	}

	var getTopStudents = function()
	{
		if( $scope.isTeacher ) apiService.getTeacherTopStudents($rootScope.currentUser.emp_id, loadTopStudents, apiError);
		else apiService.getTopStudents(undefined, loadTopStudents, apiError);
	}

	var loadTopStudents = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.examsLoading = false;
			var topStudents = ( result.nodata !== undefined ? [] : result.data);

			// group into classes object

			$scope.classes = [];
			var lastClass = '';
			var students = [];
			var i = 0;
			angular.forEach(topStudents, function(item,key){

				if( item.class_id != lastClass )
				{
					// changing to new subject, store the marks
					if( i > 0 ) $scope.classes[(i-1)].students = students;

					$scope.classes.push(
						{
							class_id: item.class_id,
							class_name: item.class_name,
							students: []
						}
					);

					students = [];
					i++;
				}
				students.push({
					student_name: item.student_name,
					student_id: item.student_id,
					total_mark: item.total_mark,
					total_grade_weight: item.total_grade_weight,
					position: item.rank,
					position_out_of: item.position_out_of,
					grade: item.grade
				});

				lastClass = item.class_id;

			});
			if( $scope.classes[(i-1)] ) $scope.classes[(i-1)].students = students;

		}
		else
		{
			$scope.prError = true;
			$scope.prErrMsg = result.data;
			$scope.examsLoading = false;
		}
	}

	var loadStudentsWithBalance = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			// console.log("Students with balance > ",result);
			$scope.studentsWithBalance = ( result.nodata !== undefined ? [] : result.data.filter(function(bal) {return bal.balance > 0;}).sort((a, b) => (b.balance) - (a.balance)) );
		}
		else
		{
			console.log("STUDENTS WITH BALANCE ERROR > ",result);
		}
	}

	var loadStudentsBusUsage = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			// console.log("Students bus usage > ",result);
			$scope.studentsBusUsage = ( result.nodata !== undefined ? [] : result.data);
		}
		else
		{
			console.log("BUS USAGE ERROR > ",result);
		}
	}

	var loadPopularDestinations = function(response, status)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			// console.log("Popular destinations > ",result);
			$scope.popularDestinations = ( result.nodata !== undefined ? [] : result.data);
		}
		else
		{
			console.log("POPULAR DESTINATIONS ERROR > ",result);
		}
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}

	var initializeController = function ()
	{
		if( $scope.isTeacher )
		{
			getTeacherClasses();
			getTeacherSubjects();
			getTopStudents();
			getStudentGenderCount();
		}
		else
		{
			getStudentCount();
			getStaffCount();
			getFeeSummary();
			getTopStudents();
			getStudentGenderCount();
		}

		if($scope.isTransport){
			apiService.getAllStudentsWithTranspBalance({}, loadStudentsWithBalance, apiError);
			apiService.getStudentsBusUsage({}, loadStudentsBusUsage, apiError);
			apiService.getPopularDestinations({}, loadPopularDestinations, apiError);
		}
	}

	setTimeout(initializeController(),10);


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
