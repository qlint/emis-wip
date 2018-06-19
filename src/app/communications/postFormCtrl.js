'use strict';

angular.module('eduwebApp').
controller('postFormCtrl', ['$scope', '$rootScope', 'apiService', 'dialogs', 'FileUploader','$timeout','$state',
function($scope, $rootScope, apiService, $dialogs, FileUploader, $timeout, $state){

	$scope.loadingPost = true;
	$scope.editingBlogName = false;

	$scope.filters = {};
	$scope.alert = {};
	$scope.theparent = {};
	$scope.theroute = {};
	$scope.theactivity = {};
	$scope.theemployee = {};

	$scope.edit = ( $state.params.action !== undefined && $state.params.action == 'edit' ? true : false );
	$scope.post = ( $state.params.post !== undefined ? $state.params.post : {} );
	$scope.post_type = ( $state.params.post_type !== undefined ? $state.params.post_type : 'post' );
	$scope.isPost = ($scope.post_type == 'post' ? true : false );
	$scope.isHomework = ($scope.post_type == 'homework' ? true : false );
	$scope.isEmail = ($scope.post_type == 'communication' ? true : false );

	$scope.pageTitle = ( $scope.isHomework ? 'Homework Post' : ( $scope.isEmail ? 'Communication' : 'Blog Post'));
	$scope.filters.send_method = 'email';
	$scope.isTeacher = ( $rootScope.currentUser.user_type == 'TEACHER' ? true : false );
	$scope.noEmpId = ( $rootScope.currentUser.emp_id === null ? true : false );
	$scope.isAdmin = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false );

	if( $scope.isHomework )
	{
		/* init dates for date picker */
		$scope.dates = {};
		$scope.dates.assigned_date = {startDate:moment().format('YYYY-MM-DD')};
		$scope.dates.due_date = {startDate:null};
	}

	var initializeController = function()
	{
		/* post_id was passed, editing a post */
		/* if the post data was not sent, grab post data from post id */
		if( $state.params.post === null && $state.params.post_id !== undefined )
		{
			if( $scope.isHomework )
			{
				apiService.getHomeworkPost($state.params.post_id, function(response, status){
					var result = angular.fromJson(response);

					if( result.response == 'success')
					{
						if( result.nodata )
						{
							$scope.post = {};
							$scope.notFound = true;
							$scope.loadingPost = false;
						}
						else
						{
							$scope.post = result.data;
							$scope.dates.assigned_date = {startDate:moment($scope.post.assigned_date).format('YYYY-MM-DD')};
							$scope.dates.due_date = {startDate:moment($scope.post.due_date).format('YYYY-MM-DD')};
							$scope.optionsSelected = true;
							$scope.selectedClassSubject = {
								subject_name: $scope.post.subject_name,
								class_id: $scope.post.class_id,
								class_name: $scope.post.class_name,
								subject_id: $scope.post.subject_id,
								class_subject_id:  $scope.post.class_subject_id
							}
							$scope.setupBlog = false;
							$scope.loadingPost = false;


						}
						getHomeworkOptions();
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
				}, apiError);
			}
			else if( $scope.isEmail )
			{
				apiService.getCommunication($state.params.post_id, function(response, status){
					var result = angular.fromJson(response);

					if( result.response == 'success')
					{
						if( result.nodata )
						{
							$scope.post = {};
							$scope.notFound = true;
							$scope.loadingPost = false;
						}
						else
						{
							$scope.post = result.data;
							$scope.post.title = result.data.subject;
							$scope.post.body = result.data.message;
							// $scope.post.attachment = [result.data.attachment];
							$scope.post.attachment = uploader.queue;
							$scope.optionsSelected = true;
							$scope.selectedType = $scope.post.com_type;
							$scope.selectedAudience = $scope.post.audience;
							$scope.selectedParent = $scope.post.full_parent_name;
							$scope.selectedRoute = $scope.post.route;
							$scope.selectedActivity = $scope.post.fee_item;
							$scope.post.send_method = ( $scope.post.send_as_sms ? 'sms' : 'email' );
							$scope.selectedMethod = $scope.post.send_method.toUpperCase();
							$scope.setupBlog = false;
							$scope.loadingPost = false;
							if( $scope.post.send_method ==  'sms' ) $scope.post.title = $scope.post.message; // sms message is displayed in title field


							if( $scope.noEmpId || $scope.isAdmin )
							{
								// get list of employees
								apiService.getAllEmployees(true, loadEmployees, apiError);
							}

						}
						// pull types and audiences
						getCommunicationOptions();

					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
				}, apiError);
			}
			else
			{
				apiService.getPost($state.params.post_id, function(response, status){
					var result = angular.fromJson(response);

					if( result.response == 'success')
					{
						if( result.nodata )
						{
							$scope.post = {};
							$scope.notFound = true;
							$scope.loadingPost = false;

						}
						else
						{
							$scope.post = result.data;
							$scope.optionsSelected = true;

							$scope.setupBlog = false;
							$scope.loadingPost = false;

						}
						getPostOptions();
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
				}, apiError);
			}
		}
		else if( $scope.post.post_id !== undefined )
		{
			if( $scope.isEmail )
			{
				$scope.post.title = $scope.post.subject;
				$scope.post.body = $scope.post.message;
				$scope.attachment = $scope.post.attachment;
				$scope.selectedType = $scope.post.com_type;
				$scope.selectedAudience = $scope.post.audience;
				$scope.selectedParent = $scope.post.full_parent_name;
				$scope.selectedRoute = $scope.post.route;
				$scope.selectedActivity = $scope.post.fee_item;
				$scope.post.send_method = ( $scope.post.send_as_sms ? 'sms' : 'email' );
				$scope.selectedMethod = $scope.post.send_method.toUpperCase();
				if( $scope.post.send_method ==  'sms' ) $scope.post.title = $scope.post.message; // sms message is displayed in title field
				getCommunicationOptions();

				if( $scope.noEmpId ) apiService.getAllEmployees(true, loadEmployees, apiError);// get list of employees

			}
			else if( $scope.isHomework )
			{
				$scope.dates.assigned_date = {startDate:moment($scope.post.assigned_date).format('YYYY-MM-DD')};
				$scope.dates.due_date = {startDate:moment($scope.post.due_date).format('YYYY-MM-DD')};
				getHomeworkOptions();
			}
			else
			{
				getPostOptions();
			}

			$scope.optionsSelected = true;
			$scope.setupBlog = false;
			$scope.loadingPost = false;

		}
		else{
			$scope.optionsSelected = false;
			if( $scope.isEmail ) getCommunicationOptions();
			else if( $scope.isHomework ) getHomeworkOptions();
			else getPostOptions();
		}

	}
	$timeout(initializeController,100);


	var getPostOptions = function()
	{
		$scope.loadingPost = false;
		/* if classes not yet set, get list of classes for drop down */
    /*
		if( $rootScope.classes !== undefined )
		{
			$scope.classes = $rootScope.classes;
			// if a class id was passed in, set this as the active filter
			setInitalClass();

			$scope.loadingPost = false;
		}
		else
		{
     */
			var params = $rootScope.currentUser.emp_id + '/true';
			apiService.getTeacherClasses(params, function(response,status){
				var result = angular.fromJson(response);

				if( result.response == 'success')
				{
					$rootScope.classes = $scope.classes = ( result.nodata ? [] : result.data );
					if( $scope.classes.length > 0 ) setInitalClass();
					else $scope.noClasses = true;
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				$scope.loadingPost = false;

			}, apiError);
		//}
	}

	var getHomeworkOptions = function()
	{
		$scope.loadingPost = false;
		/* get teachers associated class subjects */
		if( $rootScope.classSubjects !== undefined )
		{
			$scope.classSubjects = $rootScope.classSubjects;
			setInitalClassSubject();
			$scope.loadingPost = false;
		}
		else
		{
			apiService.getTeacherClassSubjects($rootScope.currentUser.emp_id, function(response,status){

				var result = angular.fromJson(response);

				if( result.response == 'success' )
				{
					$rootScope.classSubjects = $scope.classSubjects = ( result.nodata ? [] : result.data );

					if( $scope.classSubjects.length > 0 )
					{
						setInitalClassSubject();
					}
					else $scope.noClassSubjects = true;
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				$scope.loadingPost = false;

			}, apiError);
		}
	}

	var getCommunicationOptions = function()
	{
		$scope.loadingPost = false;
		if( $rootScope.comTypes === undefined )
		{
			apiService.getCommunicationOptions({}, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success')
				{
					$rootScope.comTypes = $scope.comTypes = result.data.com_types;
					$rootScope.comAudience = $scope.comAudience = result.data.audiences;
					/*
					if( $scope.post.post_id !== undefined )
					{

						// post passed in, set the posts type and audience
						setInitalAudience();
						setInitalComType();

						if( $scope.post.guardian_id ) setInitalParent();
						else if ($scope.post.class_id ) setInitalClass();
					}
					*/
				}

			}, apiError);
		}
		else
		{
			$scope.comTypes = $rootScope.comTypes;
			$scope.comAudience = $rootScope.comAudience;
			/*
			if( $scope.post.post_id !== undefined )
			{
				// post passed in, set the posts type and audience
				setInitalAudience();
				setInitalComType();

				if( $scope.post.guardian_id ) setInitalParent();
				else if ($scope.post.class_id ) setInitalClass();
			}
			*/
		}

	}

	var setInitalClass = function()
	{
		/* if a class id was passed in, set this as the active filter */
		if( $scope.post.post_id !== null && $scope.post.post_id !== undefined )
		{
			$scope.filters.class = $scope.classes.filter(function(item){
				if( item.class_id == $scope.post.class_id  ) return item;
			})[0];

			$scope.setClass();
		}
		else
		{
			/* if not class passed in, set drop down to first class */
			$scope.filters.class = $scope.classes[0];
		}

	}

	var setInitalClassSubject = function()
	{
		/* if a class id was passed in, set this as the active filter */
		if( $scope.post.post_id !== null && $scope.post.post_id !== undefined )
		{
			$scope.filters.subject = $scope.classSubjects.filter(function(item){
				if( item.class_subject_id == $scope.post.class_subject_id ) return item;
			})[0];
			$scope.setClassSubject();
		}
		else
		{
			/* if not class passed in, set drop down to first class */
			$scope.filters.subject = $scope.classSubjects[0];
		}

	}

	var loadStudentCurrentClass = function(response)
	{

		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			// console.log("SUCCESS:: is there student data?");
			$scope.studentClassesData = ( result.nodata ? {} : result.data );
			// console.log($scope.studentClassesData);
		}
		else
		{
			// console.log("FAILED TO GET STUDENT CLASS DATA");
			$scope.error = true;
			$scope.errMsg = result.data;
		}
		// console.log("class id = " + $scope.studentClassesData[0].class_id);

		/* I've nested the api's to that all data is fetched in one go */
		apiService.getCurrentTerm({},function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				// console.log("TERM DATA SUCCESS");
				$rootScope.currentTerm = result.data;
				$scope.term_id = $rootScope.currentTerm.term_id;
				var termName = $rootScope.currentTerm.term_name;
				// we only want the number
				termName = termName.split(' ');
				$rootScope.currentTerm.term_name = termName[1];
				$rootScope.currentTermTitle = $rootScope.currentTerm.term_name  + ', ' + $rootScope.currentTerm.year;
				// console.log($rootScope);
			}

			var loadStreamPOsition = function(response, status)
			{
				var result = angular.fromJson(response);

				console.log(result);
				$scope.streamRankPosition = result.data.streamRank[0].position;
				$scope.streamRankOutOf = result.data.streamRank[0].position_out_of;
				// console.log("Stream = " + $scope.streamRankPosition + "/" + $scope.streamRankOutOf);

				var loadStudentReportCard = function(response)
				{
					// $("#reportCardData").click(function () {
					var result = angular.fromJson(response);

					if( result.response == 'success')
					{
						// console.log("STUDENT DATA SUCCESS ::: Where's the data?");
						$scope.studentData = ( result.nodata ? {} : result.data );
						// console.log($scope.studentData);

						var loadExamMarks = function(response, status)
						{
							var result = angular.fromJson(response);

							if( result.response == 'success')
							{
								if( result.details === false )
								{
									console.log("ERROR - Not getting to report card");
								}
								else
								{
									// console.log("Original report card data");
									// console.log(result.data);
									var school = window.location.host.split('.')[0];
									if (school == "karemeno"){
										$scope.termMarks = result.data.overallByAverage.current_term_marks;
										$scope.termMarksOutOf = result.data.overallByAverage.current_term_marks_out_of;
										// console.log($scope.termMarks + "/" + $scope.termMarksOutOf);
									}else if (school == "rongaiboys"){
										$scope.termMarks = result.data.overallByAverage.current_term_marks;
										$scope.termMarksOutOf = result.data.overallByAverage.current_term_marks_out_of;
										$scope.ovrlGrd = result.data.overall.grade;
										// console.log($scope.termMarks + "/" + $scope.termMarksOutOf);
									}else{
										$scope.termMarks = result.data.overall.current_term_marks;
										$scope.termMarksOutOf = result.data.overall.current_term_marks_out_of;
									}
									var termMarks = "TOT : " + $scope.termMarks + "/" + $scope.termMarksOutOf;
									// console.log(termMarks);
									//the rest of the data -> -> ->
									if (school == "karemeno"){
										var allDataString = JSON.stringify($scope.studentData.report_data, null, "\t").replace(/[\[\]']+/g,'').replace(/[\{\}']+/g,'').replace(/[\"\"']+/g,'').replace(/[\\\\']+/g,'');
										var firstReplaceStart = ",parent_subject_name"; var firstReplaceEnd = "overall_mark";
										var strippedAllDataString1 = allDataString.replace(/parent_subject_name.*?overall_mark/g, 'mks');
										var strippedAllDataString2 = strippedAllDataString1.replace(/comment.*?subject_name/g, 'sub').replace(/overall_grade/g, 'grd').replace(/subjects:subject_name/g, 'sub');
										var strippedAllDataString3 = strippedAllDataString2.split(",position_last_term")[0];
										var strippedAllDataString4 = strippedAllDataString3.replace(/comment.*?position:total_mark/g, '\ntot ').replace(/principal_comment.*?position_out_of/g, 'out_of').replace(/,total_grade_weight:/g, '/').replace(/,percentage.*?out_of:/g, '/').split(",current_term_marks")[0];
										var strippedAllDataString5 = strippedAllDataString4.toUpperCase().replace(/MATHEMATICS/g,'MAT').replace(/ENGLISH/g,'ENG').replace(/KISWAHILI/g,'KIS').replace(/BIOLOGY/g,'BIO').replace(/CHEMISTRY/g,'CHM').replace(/PHYSICS/g,'PHY').replace(/GEOGRAPHY/g,'GEO').replace(/HISTORY/g,'HST').replace(/BSTDS/g,'BST').replace(/AGRICULTURE/g,'AGR').replace(/COMPUTER/g,'CMP').replace(/SUB:/g, '\nSUB:').replace(/RANK/g, '\nPOS').replace(/SUB:/g, '').replace(/,MKS:/g, ' : ').replace(/,GRD:/g, '   ');
										var strippedAllDataString6 = "TERM " + $rootScope.currentTermTitle + " REPORT" + "\n"+$scope.theparent.selected.student_name + "\n"+$scope.studentClassesData[0].class_name;
										var strmPos = "STREAM : " + $scope.streamRankPosition + "/" + $scope.streamRankOutOf;
										var ovrlGrade = allDataString.replace(/subjects:subject_name.*?rank:/g, 'rank:').split(",principal_comment:")[0].replace(/rank:.*?,grade/g, 'OVRL GRD ');
										var strippedAllDataString7 = strippedAllDataString6 + strippedAllDataString5 + "\n"+ termMarks + "\n"+strmPos + "\n"+ovrlGrade;
										var strippedAllDataString8 = strippedAllDataString7.replace(/\nTOT.*?\nPOS/g, '\nPOS');

										// console.log(allDataString);
										$("#textarea1").val(strippedAllDataString8);
									}else if (school == "rongaiboys"){
										var allDataString = JSON.stringify($scope.studentData.report_data, null, "\t").replace(/[\[\]']+/g,'').replace(/[\{\}']+/g,'').replace(/[\"\"']+/g,'').replace(/[\\\\']+/g,'');
										var firstReplaceStart = ",parent_subject_name"; var firstReplaceEnd = "overall_mark";
										var strippedAllDataString1 = allDataString.replace(/parent_subject_name.*?overall_mark/g, 'mks');
										var strippedAllDataString2 = strippedAllDataString1.replace(/comment.*?subject_name/g, 'sub').replace(/overall_grade/g, 'grd').replace(/subjects:subject_name/g, 'sub');
										var strippedAllDataString3 = strippedAllDataString2.split(",position_last_term")[0];
										var strippedAllDataString4 = strippedAllDataString3.replace(/comment.*?position:total_mark/g, '\ntot ').replace(/principal_comment.*?position_out_of/g, 'out_of').replace(/,total_grade_weight:/g, '/').replace(/,percentage.*?out_of:/g, '/').split(",current_term_marks")[0];
										var strippedAllDataString5 = strippedAllDataString4.toUpperCase().replace(/MATHEMATICS/g,'MAT').replace(/ENGLISH/g,'ENG').replace(/KISWAHILI/g,'KIS').replace(/BIOLOGY/g,'BIO').replace(/CHEMISTRY/g,'CHM').replace(/PHYSICS/g,'PHY').replace(/GEOGRAPHY/g,'GEO').replace(/HISTORY/g,'HST').replace(/BUSINESS STUDIES/g,'BST').replace(/AGRICULTURE/g,'AGR').replace(/COMPUTER/g,'CMP').replace(/SUB:/g, '\nSUB:').replace(/RANK/g, '\nPOS').replace(/SUB:/g, '').replace(/,MKS:/g, ' : ').replace(/,GRD:/g, '   ');
										var strippedAllDataString6 = "TERM " + $rootScope.currentTermTitle + " REPORT" + "\n"+$scope.theparent.selected.student_name + "\n"+$scope.studentClassesData[0].class_name;
										var strmPos = "STREAM : " + $scope.streamRankPosition + "/" + $scope.streamRankOutOf;
										var ovrlGrade = 'OVRL GRD : ' + $scope.ovrlGrd;
										var strippedAllDataString7 = strippedAllDataString6 + strippedAllDataString5 + "\n"+ termMarks + "\n"+strmPos + "\n"+ovrlGrade;
										var strippedAllDataString8 = strippedAllDataString7.replace(/\nTOT.*?\nPOS/g, '\nPOS');

										// console.log(allDataString);
										$("#textarea1").val(strippedAllDataString8);
									}else{
										var allDataString = JSON.stringify($scope.studentData.report_data, null, "\t").replace(/[\[\]']+/g,'').replace(/[\{\}']+/g,'').replace(/[\"\"']+/g,'').replace(/[\\\\']+/g,'');
										var firstReplaceStart = ",parent_subject_name"; var firstReplaceEnd = "overall_mark";
										var strippedAllDataString1 = allDataString.replace(/parent_subject_name.*?overall_mark/g, 'overall_mark');
										var strippedAllDataString2 = strippedAllDataString1.replace(/remarks.*?subject_name/g, 'subject_name');
										var strippedAllDataString3 = strippedAllDataString2.replace(/position_last_term.*?teacher_name/g, 'teacher_name');
										var strippedAllDataString4 = strippedAllDataString3.replace(/subject_name/g, 'sub').replace(/overall_grade/g, 'grd').replace(/overall_mark/g, 'mks').replace(/subjects:sub/, 'sub').replace(/principle_comments/, 'comment');
										var strippedAllDataString5 = strippedAllDataString4.replace(/sub/g, '\nsub').replace(/position:total_mark/, '\ntotal').replace(/,total_grade_weight:/, '/').replace(/rank/, '\npos').replace(/comment/, '\ncomment').replace(/remarks.*?\ntotal/g, '\ntotal').split(",teacher_name")[0];
										var strippedAllDataString6 = "REPORT CARD FOR TERM " + $rootScope.currentTermTitle + " " + strippedAllDataString5;
										// console.log($rootScope.currentTermTitle);
										$("#textarea1").val(strippedAllDataString6);
									}
								}
							}
						}
						var lastparam = $scope.post.student_id + '/' + $scope.studentClassesData[0].class_id + '/' +  $scope.term_id;
						apiService.getExamMarksforReportCard(lastparam, loadExamMarks, apiError);

						var school = window.location.host.split('.')[0];
					}
					else
					{
						// console.log("FAILED TO GET STUDENT DATA FROM THE DB");
						$scope.error = true;
						$scope.errMsg = result.data;
					}
					// });
				}

			// console.log("Student>" + $scope.post.student_id + " Class>" + $scope.studentClassesData[0].class_id + " Term>" + $scope.term_id);
			var params = $scope.post.student_id + '/' + $scope.studentClassesData[0].class_id + '/' + $scope.term_id;
			apiService.getStudentReportCard(params,loadStudentReportCard, apiError);

			}

			var getStreamPosition = function()
			{
				var params = $scope.post.student_id + '/' + $scope.studentClassesData[0].entity_id + '/' +  $scope.term_id;

				apiService.getStreamPosition(params, loadStreamPOsition, apiError);
			}
			getStreamPosition();

		}, function(){});

	}

	$scope.studentCurrentClass = function()
	{
		$("#reportCardData").click(function () {
      $(this).text(function(i, text){
          return text === "LOAD REPORD CARD DATA" ? "DATA ALREADY LOADED" : "FOR NEW DATA - RELOAD PAGE";
      })
   });
		var params = $scope.post.student_id;
		apiService.getStudentClasses(params, loadStudentCurrentClass, apiError);
	}

	/*
	var setInitalComType = function()
	{
		// if a class id was passed in, set this as the active filter
		if( $scope.post.post_id !== null && $scope.post.post_id !== undefined )
		{
			$scope.filters.com_type = $scope.comTypes.filter(function(item){
				if( item.com_type_id == $scope.post.com_type_id) return item;
			})[0];
		}
		else
		{
			// if no class passed in, set drop down to first class
			$scope.filters.com_type = $scope.comTypes[0];
		}

	}

	var setInitalAudience = function()
	{
		// if a audience_id was passed in, set this as the active filter
		if( $scope.post.post_id !== null && $scope.post.post_id !== undefined )
		{
			$scope.filters.audience = $scope.comAudience.filter(function(item){
				if( item.audience_id == $scope.post.audience_id ) return item;
			})[0];
		}
		else
		{
			// if no class passed in, set drop down to first class
			$scope.filters.audience = $scope.comAudience[0];
		}

	}

	var setInitalParent = function()
	{
		// if a guardian_id was passed in, set this as the active filter
		if( $scope.post.post_id !== null && $scope.post.post_id !== undefined )
		{
			$scope.theparent.selected = $scope.parents.filter(function(item){
				if( item.guardian_id == $scope.post.guardian_id ) return item;
			})[0];
		}
	}
	*/
	$scope.cancel = function()
	{
		if( $scope.isHomework ) $state.go('communications/homework', {class_subject_id: $scope.selectedClassSubject.class_subject_id });
		if( $scope.isEmail ) $state.go('communications/send_email');
		else  $state.go('communications/blog_posts', {class_id: $scope.selectedClass.class_id });
	}; // end cancel

	$scope.$watch('filters.com_type',function(newVal, oldVal){
		if( newVal == oldVal ) return;

		// filter audience select
		// if student feedback, show student select
		// else show audience select
		$scope.theparent.selected = undefined;
		$scope.theroute.selected = undefined;
		$scope.theactivity.selected = undefined;
		if( $scope.post.post_id === undefined ) $scope.filters.audience = undefined;
		$scope.isParent = false;
		$scope.isTransportRoute = false;
		$scope.isStudentActivity = false;
		$scope.isClassSpecific = false;
		$scope.filters.audience_id = null;
		$scope.isStudentFeedback = ( newVal.com_type == 'Student Feedback' ? true : false );

		if( $scope.isStudentFeedback )
		{
			/* if student feedback, set audience to parent */
			$scope.filters.audience = $scope.comAudience.filter(function(item){
				if( item.audience == 'Parent' ) return item;
			})[0];
		}
		if( $scope.isStudentFeedback  && $scope.parents == undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				var params = $rootScope.currentUser.emp_id;
				apiService.getTeacherParents(params, loadParents, apiError);
			}
			else
			{
				apiService.getAllParents({}, loadParents, apiError);
			}
		}


	})

	$scope.$watch('filters.audience',function(newVal, oldVal){
		/* don't run this if student feedback, automatically set audience to parent */
		if( newVal == oldVal || $scope.isStudentFeedback ) return;


		// if parent, show parent select
		$scope.theparent.selected = undefined;
		$scope.isParent = ( newVal.audience == 'Parent' ? true : false );
		$scope.isClassSpecific = ( newVal.audience == 'Class Specific' ? true : false );
		if( $scope.isParent && $scope.parents === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				var params = $rootScope.currentUser.emp_id;
				apiService.getTeacherParents(params, loadParents, apiError);
			}
			else
			{
				apiService.getAllParents({}, loadParents, apiError);
			}
		}
		if( $scope.isClassSpecific && $scope.classes === undefined)
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				var params = $rootScope.currentUser.emp_id;
				apiService.getTeacherClasses(params, loadClasses, apiError);
			}
			else
			{
				apiService.getAllClasses({}, loadClasses, apiError);
			}
		}
		// if route, show route select
		$scope.theroute.selected = undefined;
		$scope.isTransportRoute = ( newVal.audience == 'Transport Route' ? true : false );
		if( $scope.isTransportRoute && $scope.route === undefined )
		{
			apiService.getTansportRoutes({}, loadRoutes, apiError);
		}
		// if activity, show activity select
		$scope.theactivity.selected = undefined;
		$scope.isStudentActivity = ( newVal.audience == 'Student Activity' ? true : false );
		if( $scope.isStudentActivity && $scope.fee_item === undefined )
		{
			apiService.getActivitiesList({}, loadActivities, apiError);
		}



	})

	var loadParents = function(response)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.parents = ( result.nodata ? {} : result.data );
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}

	}
	var loadRoutes = function(response)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.routes = ( result.nodata ? {} : result.data );
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}

	}
	var loadActivities = function(response)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.fee_items = ( result.nodata ? {} : result.data );
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}

	}

	var loadEmployees = function(response)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.employees = ( result.nodata ? {} : result.data );

			if( $scope.post.post_id !== null && $scope.post.post_id !== undefined )
			{
				/* set emp */
				$scope.theemployee.selected = $scope.employees.filter(function(item){
					if( item.emp_id == $scope.post.message_from  ) return item;
				})[0];
			}
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}

	}

	var loadClasses = function(response)
	{
		var result = angular.fromJson(response);

		if( result.response == 'success')
		{
			$scope.classes = ( result.nodata ? {} : result.data );
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}

	}

	$scope.setClass = function()
	{
		if( $scope.isHomework )
		{
			$scope.setClassSubject();
		}
		else
		{
			$scope.optionsSelected = true;
			$scope.selectedClass = angular.copy($scope.filters.class);

			$scope.setupBlog = ( $scope.selectedClass.blog_id === null ? true : false );
			if( $scope.setupBlog )
			{
				$scope.blog = {};
			}
		}
	}

	$scope.setClassSubject = function()
	{
		$scope.optionsSelected = true;
		$scope.classSubjectSelected = true;
		$scope.selectedClassSubject = angular.copy($scope.filters.subject);
	}

	$scope.setEmailType = function(form)
	{
		form.$setSubmitted();

		if( !form.$invalid )
		{
			$scope.optionsSelected = true;
			$scope.selectedClass = undefined;
			$scope.setupBlog = false

			/* set variables for display of type of message they are entering */
			$scope.selectedAudience = angular.copy($scope.filters.audience.audience);
			$scope.selectedType = angular.copy($scope.filters.com_type.com_type);
			$scope.selectedParent = ( $scope.theparent.selected !== undefined ? angular.copy($scope.theparent.selected.parent_full_name) : undefined );
			$scope.selectedRoute = ( $scope.theroute.selected !== undefined ? angular.copy($scope.theroute.selected.route) : undefined );
			$scope.selectedActivity = ( $scope.theactivity.selected !== undefined ? angular.copy($scope.theactivity.selected.fee_item) : undefined );
			$scope.selectedClassName = ( $scope.filters.class !== undefined ? angular.copy($scope.filters.class.class_name) : undefined );
			$scope.selectedMethod =	angular.copy($scope.filters.send_method).toUpperCase();

			/* set variables to post of selected criteria */
			$scope.post.student_id = ( $scope.theparent.selected !== undefined ? angular.copy($scope.theparent.selected.student_id) : undefined );
			$scope.post.guardian_id = ( $scope.theparent.selected !== undefined ? angular.copy($scope.theparent.selected.guardian_id) : undefined );
			$scope.post.transport_id = ( $scope.theroute.selected !== undefined ? angular.copy($scope.theroute.selected.transport_id) : undefined );
			$scope.post.fee_item = ( $scope.theactivity.selected !== undefined ? angular.copy($scope.theactivity.selected.fee_item) : undefined );
			$scope.post.class_id = ( $scope.filters.class !== undefined ? angular.copy($scope.filters.class.class_id) : undefined );
			$scope.post.audience_id = angular.copy($scope.filters.audience.audience_id);
			$scope.post.com_type_id = angular.copy($scope.filters.com_type.com_type_id);
			$scope.post.send_method = angular.copy($scope.filters.send_method);

			/* if the user is not associated with an employee id, need to ask for one */
			if( $scope.noEmpId ) apiService.getAllEmployees(true, loadEmployees, apiError); // get list of employees

		}
	}

	$scope.updateBlogName = function()
	{
		$scope.editingBlogName = true;
		if( $scope.blog === undefined ) $scope.blog = {};
		$scope.blog.blog_name = angular.copy($scope.selectedClass.blog_name);
	}

	$scope.addPost = function()
	{
		var class_id = ( $scope.selectedClass ? $scope.selectedClass.class_id : null );
		$state.go('communications/add_post', {class_id: class_id, post_type:'post'});
	}

	$scope.addHomework = function()
	{
		var class_subject_id = ( $scope.selectedClassSubject ? $scope.selectedClassSubject.class_subject_id : null );
		$state.go('communications/add_post', {class_subject_id: class_subject_id, post_type:'homework'});
	}

	$scope.addEmail = function()
	{
		$state.go('communications/add_post', { post_type:'communication'});
	}

	$scope.saveBlogName = function()
	{
		var data = {
			user_id: $rootScope.currentUser.user_id,
			blog_id: $scope.post.blog_id,
			blog_name: $scope.blog.blog_name
		}

		apiService.updateBlog(data,function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$scope.editingBlogName = false;
				$scope.selectedClass.blog_name = angular.copy($scope.blog.blog_name);
			}
		},apiError);
	}

	$scope.preview = function()
	{

		if( $scope.isEmail )
		{
			$scope.post.details = {
				com_type : $scope.selectedMethod,
				audience : $scope.selectedAudience,
				class_name : $scope.selectedClassName,
				parent_full_name : $scope.selectedParent,
				route : $scope.selectedRoute,
				activity : $scope.selectedActivity,
				posted_by: ( $scope.isTeacher ? $rootScope.currentUser.full_name : ($scope.theemployee.selected !== undefined ? $scope.theemployee.selected.employee_name : '')),
				attachment: $scope.attachment,
				recipients: $scope.pullRecipients
			}
		}

		var data = {
			type: $scope.post_type,
			post: $scope.post
		}
		$scope.openModal('communications', 'previewPost', 'md', data);
	}

	$scope.saveDraft = function(form)
	{
		$scope.postForm.$setSubmitted();
		$scope.post.post_status_id = 2; // draft
		$scope.save(form);
	}

	$scope.publish = function(form)
	{
		$scope.postForm.$setSubmitted();
		$scope.post.post_status_id = 1; // TO DO: fix this
		// console.log(uploader.queue);
		// console.log($scope.uploader);

		if( $scope.edit ) $scope.updatePost(form);
		else $scope.save(form);
	}

	$scope.updatePost = function(form)
	{
		$scope.postForm.$setSubmitted();
		$scope.saving = true;
		// console.log($scope.post);
		for (var i = 0; i < uploader.queue.length; i++){
				if( uploader.queue[i] !== undefined )
				{
					// need a unique filename
					uploader.queue[i].file.name =  moment() + '_' + uploader.queue[i].file.name;
					uploader.uploadAll();

					if( $scope.isHomework ) $scope.post.attachment = ( uploader.queue[i] !== undefined ? uploader.queue[i].file.name : null);
					else $scope.post.feature_image = ( uploader.queue[i] !== undefined ? uploader.queue[i].file.name : null);
				}
		}

		if( $scope.isHomework )
		{
			$scope.post.due_date = ( $scope.dates.due_date.startDate !== undefined ? moment($scope.dates.due_date.startDate).format('YYYY-MM-DD'): null);
			$scope.post.assigned_date = ( $scope.dates.assigned_date.startDate !== undefined ? moment($scope.dates.assigned_date.startDate).format('YYYY-MM-DD'): null);

			var data = {
				user_id: $rootScope.currentUser.user_id,
				post: $scope.post
			}

			apiService.updateHomework(data,createCompleted,apiError);
		}
		else if( $scope.isEmail )
		{
			$scope.post.send_as_email = ( $scope.filters.send_method == 'email' ? 't' : 'f' );
			$scope.post.send_as_sms = ( $scope.filters.send_method == 'sms' ? 't' : 'f' );

			if( $scope.isTeacher ) $scope.post.message_from = $rootScope.currentUser.emp_id; // needs to be emp id
			else $scope.post.message_from = $scope.theemployee.selected.emp_id;

			if( $scope.post.send_method ==  'sms' )
			{
				$scope.post.body = $scope.post.title; // sms message is displayed in title field
			}
			var data = {
				user_id: $rootScope.currentUser.user_id,
				post: $scope.post
			}

			apiService.updateCommunication(data,createCompleted,apiError);
		}
		else
		{
			var data = {
				user_id: $rootScope.currentUser.user_id,
				post: $scope.post
			}

			apiService.updatePost(data,createCompleted,apiError);
		}

	}

	$scope.deletePost = function()
	{
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete this post? <b>This can not be undone</b>.',{size:'sm'});
		dlg.result.then(function(btn){
			if( $scope.isHomework )  apiService.deleteHomework($scope.post.post_id,createCompleted,apiError);
			if( $scope.isEmail )  apiService.deleteCommunication($scope.post.post_id,createCompleted,apiError);
			else apiService.deletePost($scope.post.post_id,createCompleted,apiError);
		});

	}

	$scope.save = function(form)
	{
		$scope.error = false;
		$scope.errMsg = '';
		if ( !form.$invalid )
		{
			$scope.saving = true;
			if( $scope.setupBlog )
			{
				var data = {
					teacher_id: $rootScope.currentUser.emp_id,
					blog_name: $scope.blog.blog_name,
					class_id: $scope.selectedClass.class_id
				}
				apiService.addBlog(data,function(response, status){
					var result = angular.fromJson( response );
					if( result.response == 'success' )
					{
						$scope.setupBlog = false;
						$scope.post.blog_id = result.data;
						$scope.selectedClass.blog_id = result.data;
						$scope.selectedClass.blog_name = $scope.blog.blog_name;
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
					$scope.saving = false;
				}, apiError);
			}
			else
			{
				var attachmentArray = [];
				for (var i = 0; i < uploader.queue.length; i++)
				{
					if( uploader.queue[i] !== undefined )
					{
						// need a unique filename
						uploader.queue[i].file.name = moment() + '_' + uploader.queue[i].file.name;
						uploader.uploadAll();

						if( $scope.isHomework || $scope.isEmail )
						{
						//$scope.post.attachment = ( uploader.queue[i] !== undefined ? uploader.queue[i].file.name : null);
						attachmentArray[i] = ( uploader.queue[i] !== undefined ? uploader.queue[i].file.name : null);
					 	}
						else $scope.post.feature_image = ( uploader.queue[i] !== undefined ? uploader.queue[i].file.name : null);
						var attachmentFiles = uploader.queue[i].file.name;
						// console.log(uploader.queue[i].file.name);
					}
				}
				$scope.post.attachment = attachmentArray.join(',');
				console.log($scope.post.attachment);

				if( $scope.isHomework )
				{

					$scope.post.due_date = ( $scope.dates.due_date.startDate !== undefined ? moment($scope.dates.due_date.startDate).format('YYYY-MM-DD'): null);
					$scope.post.assigned_date = ( $scope.dates.assigned_date.startDate !== undefined ? moment($scope.dates.assigned_date.startDate).format('YYYY-MM-DD'): null);

					if( $scope.isTeacher ) $scope.post.posted_by = $rootScope.currentUser.emp_id; // needs to be emp id
					else $scope.post.posted_by = $scope.theemployee.selected.emp_id;


					var data = {
						user_id: $rootScope.currentUser.user_id,
						class_subject_id: $scope.selectedClassSubject.class_subject_id,
						post: $scope.post
					}

					apiService.addHomework(data,createCompleted,apiError);
				}
				else if( $scope.isEmail )
				{
					$scope.post.send_as_email = ( $scope.filters.send_method == 'email' ? 't' : 'f' );
					$scope.post.send_as_sms = ( $scope.filters.send_method == 'sms' ? 't' : 'f' );

					if( $scope.isTeacher ) $scope.post.message_from = $rootScope.currentUser.emp_id;
					else $scope.post.message_from = $scope.theemployee.selected.emp_id;

					if( $scope.post.send_method ==  'sms' ) $scope.post.body = $scope.post.title; // sms message is displayed in title field

					var data = {
						user_id: $rootScope.currentUser.user_id,
						post: $scope.post
					}
          if( $scope.post.post_status_id === 1 )
          {
            var dlg = $dialogs.confirm('Publishing Communication', 'You have selected to publish this communication. This will cause the email/sms to be sent to the selected audience. You will no longer be able to edit this message. Do you wish to continue?',{size:'sm'});
						var imageUploads = [$scope.post];
						var seeImageUploads = JSON.stringify(imageUploads);
						// console.log($scope);
						// console.log($scope.post);
            dlg.result.then(function(btn){
              apiService.addCommunication(data,createCompleted,apiError);
            });
          }
          else
          {
            apiService.addCommunication(data,createCompleted,apiError);
          }
				}
				else
				{
					$scope.post.post_type_id = 1;

					if( $scope.isTeacher ) $scope.post.posted_by = $rootScope.currentUser.emp_id; // needs to be emp id
					else $scope.post.posted_by = $scope.theemployee.selected.emp_id;

					var data = {
						user_id: $rootScope.currentUser.user_id,
						blog_id: $scope.selectedClass.blog_id,
						post: $scope.post
					}

					apiService.addPost(data,createCompleted,apiError);
				}

			}
		}

	}

	// if( $scope.isEmail && $scope.post.send_method ==  'email'){
	// 	var uploader = $scope.uploader = new FileUploader({
	//             url: 'upload2.php',
	// 			formData : [{
	// 				'dir': 'posts'
	// 			}]
	//     });
	// } else {
	// 	var uploader = $scope.uploader = new FileUploader({
	//             url: 'upload.php',
	// 			formData : [{
	// 				'dir': 'posts'
	// 			}]
	//     });
	// }

	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'posts'
			}]
    });

	var createCompleted = function ( response, status, params )
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$scope.updated = true;
			if( $scope.isEmail && $scope.post.send_method ==  'sms'){
				//json and ajax
				var loadjson = $('#myHiddenPage').load('sms_backup.php');
			}
			// var imageUploads = [$scope.post.attachment];
			// var seeImageUploads = JSON.stringify(imageUploads);
			// console.log(seeImageUploads);
			//$scope.notificationMsg = args.msg;

			// wait a bit, then turn off the alert
			$timeout(function() { $scope.alert.expired = true;  }, 1000);
			$timeout(function() {
				$scope.updated = false;
				$scope.notificationMsg = '';
				$scope.alert.expired = false;
				if( $scope.isHomework ) $state.go('communications/homework', {class_subject_id: $scope.selectedClassSubject.class_subject_id });
				if( $scope.isEmail ) $state.go('communications/send_email');
				else  $state.go('communications/blog_posts', {class_id: $scope.selectedClass.class_id });
			}, 1500);

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
		$scope.loadingPost = false;
		$scope.saving = false;
	}


} ]);
