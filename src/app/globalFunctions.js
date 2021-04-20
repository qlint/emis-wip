/**
 * Contains functions that are added to the root AngularJs scope.
 */
angular.module('eduwebApp').run(['$rootScope', '$state', '$window', '$timeout', 'Session', 'Auth', 'AUTH_EVENTS', 'apiService', 'dialogs',
function($rootScope, $state, $window, $timeout, Session, Auth, AUTH_EVENTS, apiService, dialogs) {

	//before each state change, check if the user is logged in
	//and authorized to move onto the next state
	$rootScope.loggedIn = false;
	$rootScope.currentPage = '';
	$rootScope.isSmallScreen = false;
	$rootScope.activeSection = '';
	var refreshingPromise;
	var isRefreshing = false;

	$rootScope.$on('$stateChangeStart', function (event, next, toParams)
	{
		var domain = window.location.host;
		var subdomain = domain.substr(0, domain.indexOf('.'));
		// if(window.location.host == '67.219.189.47'){
		if(window.location.host == 'eduweb.co.ke'){
			var currentUrl = window.location.href;
			var currentUrlReverse = currentUrl.split('/').reverse();
			$rootScope.clientIdentifier = currentUrlReverse[2];
			// console.log("Current referrer from JS is ::: " + $rootScope.clientIdentifier);
		}else{
			$rootScope.clientIdentifier = ( subdomain == 'parents' ? '' : subdomain );
		}

			var authorizedRoles = ( next.data.authorizedRoles.length > 0 ? next.data.authorizedRoles: null);
		var loggedIn = false;

		$rootScope.currentPage = next.name;

		if( $window.sessionStorage["userInfo"] )
		{
			loggedIn = true;
			$rootScope.loggedIn = true;
			$rootScope.currentUser = JSON.parse($window.sessionStorage["userInfo"]);
			Session.create($rootScope.currentUser);
			// console.log($rootScope.currentUser);

			$rootScope.$broadcast(AUTH_EVENTS.loginSuccess);

			// get class categories and classes
			$rootScope.getClassCats();
			$rootScope.getAllClasses();
			$rootScope.getEmpCats();
			$rootScope.getDepts();
			$rootScope.getCountries();
			$rootScope.getInstallmentOptions();

		}
		else
		{
			if( next.name != 'index' )
			{
				if ( authorizedRoles !== null && !Auth.isAuthorized(authorizedRoles))
				{

					if (Auth.isAuthenticated())
					{
					// user is not allowed
					$rootScope.$broadcast(AUTH_EVENTS.notAuthorized);
					event.preventDefault();
					}
					else if( !loggedIn )
					{
					// user is not logged in
					$rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
					event.preventDefault();
					}
				}

			}

		}

	 });

	$rootScope.$on('$stateChangeSuccess', function(event, toState, toParams, fromState, fromParams)
	{

		$rootScope.navOpen = false;

		var section = toState.name;

		// if the path you are checking is a sub-section, keep the / in the current state name
		// otherwise, strip it, as we are looking for the root name
		section = section.split('/');
		$rootScope.activeSection = section[0];
		$rootScope.activeSubSection = ( section[1] === undefined ? section[0] : section[1]);
		$rootScope.activeSubSubSection = ( section[2] === undefined ? section[0] : section[2]);
		if( $rootScope.activeSubSubSection	== 'print' ) $rootScope.isPrinting = true;
		$rootScope.currentPageSection = section[0] + '/' + section[1];

		$timeout( function () {
			//$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			//adjustPositions();
			if( $('#navigation').hasClass('in') )
			{
				$('#mainnav').trigger('click');
				$rootScope.menuOn = false;
			}

		}, 100);



	});

	function adjustPositions()
	{
		$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );

		// need to adjust the top position of body content based
		// on the height of the top fixed items
		var headerHeight = $('.navbar-header').height();
		var fixedHeader = $('.content-fixed-header').height();

		if( $rootScope.isSmallScreen )
		{
			//$('.subnavbar-container').css('top',headerHeight+8);
			$('#mainContainer').css('top',headerHeight+15);
			$('.content-fixed-header').css('top',headerHeight+5);
			$('#body-content .main-datagrid').css('padding-top',fixedHeader+40);

		}
		else
		{

			//$('.subnavbar-container').css('top',headerHeight+8);
			$('#mainContainer').css('top',headerHeight+15);
			$('.content-fixed-header').css('top',headerHeight+5);
			$('#body-content .main-datagrid').css('padding-top',fixedHeader-10);
		}
	}

	$rootScope.wipNotice = function()
	{
		var dlg = dialogs.notify('Feature in Development','This feature is not yet complete, but hang in there, it will be awesome.', {size:'sm'});
	}

	$rootScope.checkIfMobile = (function()
	{
				var check = false;
				(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})(navigator.userAgent||navigator.vendor||window.opera);
				return check;
		})();

	$rootScope.showLogin = function()
	{
		$rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
	}

	$rootScope.logout = function()
	{
		$rootScope.currentUser = null;
		$rootScope.permissions = null;
		Auth.logout();
	};

	$rootScope.userTypes = [];
	apiService.getUserGroups(true, function(response){
		var result = angular.fromJson(response);
		if( result.response == 'success')
		{
			let allUserGroups = result.data;
			for(let x=0;x < allUserGroups.length;x++){
				$rootScope.userTypes.push(allUserGroups[x].user_type);
			}
			// console.log($rootScope.userTypes);
		}
		else{
			// console.log("User groups blank >",result);
		}
	}, function(e){console.log('Error Getting U-Grps',e)});
	// $rootScope.userTypes = ['SYS_ADMIN','ADMIN','PRINCIPAL','ADMIN-FINANCE','ADMIN-TRANSPORT','FINANCE','FINANCE_CONTROLLED','TEACHER'];

	$rootScope.postTxt = function(id){

		let param = window.location.host.split('.')[0];
		apiService.getComUserToken(param, function(response){
			var result = angular.fromJson(response);

			function makeid(length) {
				 var result = '';
				 var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
				 var charactersLength = characters.length;
				 for ( var i = 0; i < length; i++ ) {
						result += characters.charAt(Math.floor(Math.random() * charactersLength));
				 }
				 return result;
			}

			if( result.response == 'success'){
				let data = result.data;
				console.log(data);
				let params = {
					AuthDetails: [
						{
							UserID: data.user_name,
							Token: data.token,
							Timestamp: data.timestamp
						}
					],
					MessageType: ["3"],
					BatchType: ["1"],
					SourceAddr: [data.source],
					MessagePayload: [{Text: null }],
					DestinationAddr: [],
					DeliveryRequest: [
						{
							EndPoint: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/srvScripts/rxSms.php",
							Correlator: null /* "ED" + makeid(12) */
						}
					]
				}
				console.log("Received com_id > ",id);

				apiService.getSmsDetails(id, function(response, status){
					var result = angular.fromJson(response);

					if( result.response == 'success')
					{
						if( result.nodata )
						{ /* no sms to send */ }
						else
						{
							let post = result.data;
							params.MessagePayload[0].Text = post[0].message_text;
							let allRecipients = [];
							post.forEach((item, i) => {
								let recipient = {
									"MSISDN": "254" + item.phone_number,
									"LinkID": "",
									"SourceID": 0
								}
								allRecipients.push(recipient);
							});
							// we use a batch size of 90 so large sms's will
							// be sent to recipients in chunks of 90
							var i,j,temparray,chunk = 90;
							for (i=0,j=allRecipients.length; i<j; i+=chunk) {
									temparray = allRecipients.slice(i,i+chunk);
									params.DestinationAddr = temparray;
									params.DeliveryRequest[0].Correlator = "ED" + makeid(12);
									console.log(params);
									let xhr = new XMLHttpRequest();
									xhr.onload = () => {
											if (xhr.status >= 200 && xhr.status < 300) {
													let response = JSON.parse(xhr.responseText);
													console.log('SMS sent >',response);
													function smsLength(text){return text.length}
													function smsPages(text){return Math.ceil(parseInt(text.length)/160);}
													let recpients = response;
													recpients.forEach((recipient, i) => {
														let recipientPhone = recipient.MSISDN.slice(3);
														for (var p = 0; p < post.length; p++) {
															if(recipientPhone == post[p].phone_number){
																recipient.recipient_name = post[p].recipient_name;
															}
														}
													});
													console.log('Modified Recipients > ',recpients);

													let dataLog = {
														message_text: post[0].message_text,
														message_by: post[0].message_by,
														message_length: smsLength(post[0].message_text),
														page_count: smsPages(post[0].message_text),
														token: data.token,
														subscriber_id: data.subscriber_id,
														recipients: recpients
													}
													console.log("Save to DB >",dataLog);
													apiService.logToSmsServer(dataLog,function(response, status){
														var res = angular.fromJson( response );
														if( res.response == 'success' )
														{
															console.log("Logged successfully.",res);
														}
														else
														{
															$scope.error = true;
															$scope.errMsg = res.data;
														}
													}, function(err){console.log("An Erros Ocurred :: ",err)});
													params.DestinationAddr = null;
											}
									};
									xhr.open('POST', 'https://api.pasha.biz/submit1.php');
									xhr.setRequestHeader('Content-Type', 'application/json');
									xhr.send(JSON.stringify(params));
									params.DestinationAddr = null;
							}

						}
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
				}, function(err){console.log("An error has occurred: ",err);});
			}

		}, function(err){console.log("An error occurred: ",err)});

	}

	$rootScope.formatStudentData = function(data)
	{
		// make adjustments to student data
		var formatedResults = data.map(function(item){
			item.student_name = item.first_name + ' ' + ( item.middle_name || '' ) + ' ' + item.last_name;
			/*
			var theClass = $rootScope.allClasses.filter(function(a){
				return a.class_id == item.current_class;
			})[0];
			item.class_name = (theClass ? theClass.class_name : '');
			item.class_cat_id = (theClass ? theClass.class_cat_id : '');
			item.class_id = (theClass ? theClass.class_id : '');
			*/

			if( item.guardians)
			{
				item.guardians = item.guardians.map(function(parent){
					parent.parent_full_name = parent.first_name + ' ' + ( parent.middle_name || '') + ' ' + parent.last_name;
					return parent;
				});
			}

			item.status = ( item.active ? 'Active' : 'In-Active');
			item.adoptedStr = ( item.adopted ? 'Yes' : 'No');
			item.adoptionAwareStr = ( item.adoption_aware ? 'Yes' : 'No');
			item.other_medical_conditions_str = ( item.other_medical_conditions ? 'Yes' : 'No');
			item.hospitalized_str = ( item.hospitalized ? 'Yes' : 'No');
			item.current_medical_treatment_str = ( item.current_medical_treatment ? 'Yes' : 'No');
			item.admission_date = (item.admission_date !== null ? moment(item.admission_date).format('YYYY-MM-DD') : '');

			return item;
		});

		return formatedResults;
	}

	$rootScope.$on('studentAdded', function(event, args) {
				$rootScope.$broadcast('refreshStudents', args);
		});

  $rootScope.$on('studentsPromoted', function(event, args) {
      $rootScope.$broadcast('refreshStudents', args);
  });

	$rootScope.$on('invoiceAdded', function(event, args) {
				$rootScope.$broadcast('refreshInvoices', args);
		});

	$rootScope.$on('paymentAdded', function(event, args) {
				$rootScope.$broadcast('refreshPayments', args);
		});

	$rootScope.$on('feeItemAdded', function(event, args) {
				$rootScope.$broadcast('refreshItems', args);
		});

	$rootScope.$on('deptAdded', function(event, args) {
				$rootScope.$broadcast('refreshDepartments', args);
		});

	$rootScope.$on('termAdded', function(event, args) {
				$rootScope.$broadcast('refreshDates', args);
		});

	$rootScope.$on('subjectAdded', function(event, args) {
				$rootScope.$broadcast('refreshSubjects', args);
		});

	$rootScope.$on('classAdded', function(event, args) {
				$rootScope.$broadcast('refreshClasses', args);
		});

	$rootScope.$on('examMarksAdded', function(event, args) {
				$rootScope.$broadcast('refreshExamMarks', args);
		});

  $rootScope.$on('examMarksAdded2', function(event, args) {
				$rootScope.$broadcast('refreshExamMarks2', args);
		});

	$rootScope.$on('gradingAdded', function(event, args) {
				$rootScope.$broadcast('refreshGrades', args);
		});

	$rootScope.$on('employeeAdded', function(event, args) {
				$rootScope.$broadcast('refreshStaff', args);
		});

	$rootScope.$on('reportCardAdded', function(event, args) {
				$rootScope.$broadcast('refreshReportCards', args);
		});

	$rootScope.$on('reportCardAdded', function(event, args) {
				$rootScope.$broadcast('refreshReportCards', args);
		});

	$rootScope.$on('userAdded', function(event, args) {
				$rootScope.$broadcast('refreshUsers', args);
		});

	$rootScope.$on('postAdded', function(event, args) {
				$rootScope.$broadcast('refreshPosts', args);
		});

	$rootScope.$on('setSettings', function(event, args) {

				$rootScope.currentUser.settings = angular.copy(args);

		// update the session variable
		var sessionData = JSON.parse($window.sessionStorage["userInfo"]);
		sessionData.settings = args;
		$window.sessionStorage["userInfo"] = JSON.stringify(sessionData);

		});

	$rootScope.getClassCats = function()
	{
		// get class categories
		if( $rootScope.classCats === undefined )
		{
			var params = ( $rootScope.currentUser.user_type == 'TEACHER' ? $rootScope.currentUser.emp_id : undefined);
			apiService.getClassCats(params, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success')	$rootScope.classCats = result.data;

			}, function(){});

		}

	}

	$rootScope.getAllClasses = function()
	{
		// get classes
		if( $rootScope.allClasses === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER')
			{
				apiService.getTeacherClasses($rootScope.currentUser.emp_id, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success') $rootScope.allClasses = result.data;
					return result.data;

				}, function(){});
			}
			else
			{
				apiService.getAllClasses({}, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success') $rootScope.allClasses = result.data;
					return result.data;

				}, function(){});
			}
		}
	}

	$rootScope.getDepts = function()
	{
		// get departments
		if( $rootScope.allDepts === undefined )
		{
			apiService.getDepts(true, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success') $rootScope.allDepts = result.data;
				return result.data;

			}, function(){});
		}
	}

	$rootScope.getEmpCats = function()
	{
		// get classes
		if( $rootScope.empCats === undefined )
		{
			apiService.getEmpCats({}, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success') $rootScope.empCats = result.data;
				return result.data;

			}, function(){});
		}
	}

	$rootScope.getCountries = function()
	{
		// get classes
		if( $rootScope.countries === undefined )
		{
			apiService.getCountries({}, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success') $rootScope.countries = result.data;
				return result.data;

			}, function(){});
		}
	}

	$rootScope.getInstallmentOptions = function()
	{
		// get classes
		if( $rootScope.installmentOptions === undefined )
		{
			apiService.getInstallmentOptions({}, function(response){
				var result = angular.fromJson(response);

				// store these as they do not change often
				if( result.response == 'success') $rootScope.installmentOptions = result.data;
				return result.data;

			}, function(){});
		}
	}

	$rootScope.getCurrentTerm = function()
	{
		// get current term
		apiService.getCurrentTerm({},function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$rootScope.currentTerm = result.data;
				var termName = $rootScope.currentTerm.term_name;
				// we only want the number
				termName = termName.split(' ');
				$rootScope.currentTerm.term_name = termName[1];
				$rootScope.currentTermTitle = $rootScope.currentTerm.term_name  + ', ' + $rootScope.currentTerm.year;
			}
		}, function(){});
	}

	$rootScope.getPreviousTerm = function()
	{
		// get previous term
		apiService.getPreviousTerm({},function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$rootScope.previousTerm = result.data;
				var termName = $rootScope.previousTerm.term_name;
				// we only want the number
				termName = termName.split(' ');
				$rootScope.previousTerm.term_name = termName[1];
				$rootScope.previousTermTitle = $rootScope.previousTerm.term_name  + ', ' + $rootScope.previousTerm.year;
			}
		}, function(){});
	}

	$rootScope.getNextTerm = function()
	{
		// get next term
		apiService.getNextTerm({},function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$rootScope.nextTerm = result.data;
				var termName = $rootScope.nextTerm.term_name;
				// we only want the number
				termName = termName.split(' ');
				$rootScope.nextTerm.term_name = termName[1];
				$rootScope.nextTermTitle = $rootScope.nextTerm.term_name  + ', ' + $rootScope.nextTerm.year;
			}
		}, function(){});
	}

	$rootScope.getTermRange = function()
	{
		// get term range
		apiService.getTermRange({},function(response){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$rootScope.termRange = result.data;
			}
		}, function(){});
	}

	$rootScope.setTermRanges = function(terms)
	{
		$rootScope.termRanges = {};
		angular.forEach(terms, function(item,key){
			$rootScope.termRanges[item.term_year_name] = [item.start_date, item.end_date];
		});
		var last = terms.length-1;
		//$rootScope.termRanges['Full Year'] = [terms[0].start_date, terms[last].end_date];
	}

	$rootScope.zeroPad = function(x, y)
	{
		 y = Math.max(y-1,0);
		 var n = (x / Math.pow(10,y)).toFixed(y);
		 return n.replace('.','');
	}
}]);
