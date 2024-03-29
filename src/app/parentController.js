'use strict';

angular.module('eduwebApp').
controller('ParentController', ['$scope', '$rootScope', '$uibModal', 'dialogs', 'Auth', 'AUTH_EVENTS','USER_ROLES','$filter','$state','apiService',
function($scope, $rootScope, $uibModal, $dialogs, Auth, AUTH_EVENTS, USER_ROLES, $filter,$state,apiService){
	// this is the parent controller for all controllers.
	// Manages auth login functions and each controller
	// inherits from this controller

	$scope.modalShown = false;
	$rootScope.updatePwd = false;

	var showLoginDialog = function(args) {
		if(!$scope.modalShown){
			$scope.modalShown = true;
			var modalInstance = $uibModal.open({
				templateUrl : 'app/login.html',
				controller : "LoginCtrl",
				backdrop : 'static',
				resolve: {
				 token: function () {
				   return args.token;
				 }
			  }
			});

			modalInstance.result.then(function() {
				$scope.modalShown = false;
			  }, function() {
				$scope.modalShown = false;
			  })['finally'](function(){
				$scope.modalInstance = undefined  // <--- This fixes
			  });

		}
	};

	var setCurrentUser = function()
	{
		//$scope.currentUser = $rootScope.currentUser;
		$rootScope.permissions = [];
		$rootScope.manageUsers = ( $rootScope.currentUser.user_type == 'SYS_ADMIN' ? true : false);

		$rootScope.useLetterhead = ( $rootScope.currentUser.settings['Letterhead'] !== undefined ? true : false);

		let rightsParam = window.location.host.split('.')[0] + '/' +$rootScope.currentUser.user_type;
		apiService.usrRights(rightsParam, function(response){
			var result = angular.fromJson(response);
			// console.log("user right > ",result);
			// store these as they do not change often
			if( result.response == 'success')
			{
				var rawPermissions = (result.nondata !== undefined ? [] : result.data.rights.rights);
				// console.log("Fetched Permissions >",rawPermissions);
				let perms = {};
				rawPermissions.forEach((item, i) => {
					let modName = item.mod_name.toLowerCase().split(' ').join('_');
					if(item[item.mod_name].length == 1 && item[item.mod_name][0].sub_mod_name == '-'){
						perms[modName] = item[item.mod_name][0]['-'];
					}else{
						perms[modName] = {};
						item[item.mod_name].forEach((item2, j) => {
							let subModName = item2.sub_mod_name.toLowerCase().split(' ').join('_');
							perms[modName][subModName] = item2[item2.sub_mod_name];
						});
					}
				});
				// console.log(perms);
				$rootScope.permissions = perms;
				// dynamic access rights
				// console.log('Is super teacher? ' + $rootScope.currentUser.super_teacher);
				if($rootScope.currentUser.super_teacher == true){

					if($rootScope.permissions.resources == undefined){
						$rootScope.permissions.resources = {};
					}
					$rootScope.permissions.resources.list_resources = {};
					$rootScope.permissions.resources.list_resources.view = true;
					$rootScope.permissions.resources.list_resources.edit = false;
					$rootScope.permissions.resources.list_resources.add = false;

					if($rootScope.permissions.communications == undefined){
						$rootScope.permissions.communications = {};
					}
					$rootScope.permissions.communications.all_homework = {};
					$rootScope.permissions.communications.all_homework.view = true;
					$rootScope.permissions.communications.all_homework.edit = false;
					$rootScope.permissions.communications.all_homework.add = false;

					$rootScope.permissions.communications.all_homework_feedback = {};
					$rootScope.permissions.communications.all_homework_feedback.view = true;
					$rootScope.permissions.communications.all_homework_feedback.edit = false;
					$rootScope.permissions.communications.all_homework_feedback.add = false;

					$rootScope.permissions.communications.teacher_communications = {};
					$rootScope.permissions.communications.teacher_communications.view = true;
					$rootScope.permissions.communications.teacher_communications.edit = false;
					$rootScope.permissions.communications.teacher_communications.add = false;

					$rootScope.permissions.school.subjects.add = true;
					$rootScope.permissions.school.subjects.view = true;
					$rootScope.permissions.school.subjects.edit = true;
					$rootScope.permissions.school.subjects.delete = true;
					$rootScope.permissions.school.subjects.export = true;

					if(!$rootScope.permissions.school.school_settings){
						$rootScope.permissions.school.school_settings = {};
						$rootScope.permissions.school.school_settings.add = true;
						$rootScope.permissions.school.school_settings.view = true;
						$rootScope.permissions.school.school_settings.edit = true;
					}

				}
				// console.log("Default Perms >",$rootScope.permissions);
				// console.log("Default Perms >",$rootScope.currentUser);

				$scope.navItems = [];
				$scope.subOptions = [];
				var i = 0,
				    j = 0;

				angular.forEach( $rootScope.permissions, function(permission, sectionName){
					// if no view permission, likely an object of arrays, dig deeper

					if( permission.view === undefined )
					{
						var navItem = {};
						var subnavItem = {};
						angular.forEach( permission, function(permission2, subSectionName){

							var label = ( permission.alt_label !== undefined ? $filter('titlecase')(permission.alt_label.split("_").join(" ")) : $filter('titlecase')(sectionName.split("_").join(" ")));

							if( subSectionName != 'alt_label' )
							{
								if( i == 0 ) navItem = {id: sectionName + "/" + subSectionName, label: label, section: sectionName, subnav: []};
								// console.log('Permission >',permission);
								// console.log('Section Name >',sectionName);
								// console.log('Permission 2 from permission arr >',permission2);
								// console.log('Sub Section Name >',subSectionName);

								if(permission2.view == true){
									navItem.subnav.push({id: sectionName + "/" + subSectionName, label: $filter('titlecase')(subSectionName.split("_").join(" ")), section: sectionName + '/' + subSectionName, subSection: subSectionName}); //, filters:permission2.filters});
								}

								i++;
							}

						});

						$scope.navItems.push(navItem);

					}
					else
					{
						if( permission.view )
						{
							var label = ( permission.alt_label !== undefined ? $filter('titlecase')(permission.alt_label.split("_").join(" ")) : $filter('titlecase')(sectionName.split("_").join(" ")));
							$scope.navItems.push({id: sectionName, label: label, section: sectionName}); //, icon: icons[sectionName]});
						}
					}

					i = 0;
				});



				$rootScope.navItems = $scope.navItems;

				var section = $rootScope.currentPage;
				section = section.split('/');
				var page = section[0];
				var params = section[1];

				angular.forEach( $rootScope.navItems, function( item, key) {
					var section = item.section;

					if( section.toUpperCase() == page.toUpperCase() )
					{
						$rootScope.mainSubNavItems = item.subnav;
					}
				});
			}
			else
			{
			}

		}, function(err){console.log(err)});

		/*
		switch( $rootScope.currentUser.user_type ){
			case "SYS_ADMIN":
				$rootScope.permissions = {
					'dashboard':{
						'view': true,
					},
					'students':{
						'view': true,
						'add': true,
						'edit': true,
						'import': true
					},
					'staff':{
						'view': true,
						'add': true,
						'edit': true,
						'import': true
					},
					'fees':{
						'dashboard': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'fee_structure': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'opening_balances': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'invoices': {
							'view': true,
							'add': true,
							'edit': true,
							'delete': true
						},
						'payments_received': {
							'view': true,
							'add': true,
							'edit': true,
							'delete': true
						},
						'fees_reports': {
							'view': true,
						},
						'quickbooks': {
							'view': true,
							'add': true,
							'edit':true
						},
					},
					'school':{
						'school_settings': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'school_dates': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'grading': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'subjects': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'departments': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'classes': {
							'view': true,
							'add': true,
							'edit': true,
						}

					},
					'exams':{
							'exams': {
								'view': true,
								'add': true,
								'edit': true,
								'import': true
							},
							'exam_types': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'report_cards': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'class_analysis': {
								'view': true,
							},
							'stream_analysis': {
								'view': true,
							},
							'exam_reports': {
								'view': true,
							},
					},
					'communications':{
						'send_email' : {
							'view': true,
							'add': true,
							'edit': true,
						},
						'feedback' : {
							'view': true,
							'add': true,
							'edit': true,
						}
					},
					'timetables':{
						'create_class_timetable': {
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'class_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'create_teacher_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'teacher_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
					},
					'transport':{
						'school_bus': {
							'view': true,
							'add': true,
							'edit': true
						},
						'trips': {
							'view': true,
							'add': true,
							'edit': true
						},
						// 'pick_up_and_drop_off': {
							// 'view': true,
							// 'add': true,
							// 'edit': true,
						// },
						'mapped_history': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'transport_communications': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'transport_reports': {
							'view': true,
							'add': true,
							'edit': true,
						},
					},
					'attendance':{
						'student_attendance':{
							'view': true,
							'add': true,
							'edit': true,
						},
						'list_absenteeism':{
							'view': true,
							'add': true,
							'edit': true,
						}
					}

				};
				break;
				case "FINANCE_CONTROLLED":
					$rootScope.permissions = {
						'dashboard':{
							'view': true,
						},
						'students':{
							'view': true,
							'add': true,
							'edit': false,
							'import': true
						},
						'staff':{
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'fees':{
							'dashboard': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'fee_structure': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'opening_balances': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'invoices': {
								'view': true,
								'add': true,
								'edit': false,
								'delete': false
							},
							'payments_received': {
								'view': true,
								'add': true,
								'edit': false,
								'delete': false
							},
							'fees_reports': {
								'view': true,
							},
							'quickbooks': {
								'view': true,
								'add': true,
								'edit':true
							},
						},
						'school':{
							'school_settings': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'school_dates': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'grading': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'subjects': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'departments': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'classes': {
								'view': true,
								'add': true,
								'edit': true,
							}

						},
						'exams':{
								'exams': {
									'view': true,
									'add': true,
									'edit': true,
									'import': true
								},
								'exam_types': {
									'view': true,
									'add': true,
									'edit': true,
								},
								'report_cards': {
									'view': true,
									'add': true,
									'edit': true,
								},
								'class_analysis': {
									'view': true,
								},
								'stream_analysis': {
									'view': true,
								},
								'exam_reports': {
									'view': true,
								},
						},
						'communications':{
							'send_email' : {
								'view': true,
								'add': true,
								'edit': true,
							},
							'feedback' : {
								'view': true,
								'add': true,
								'edit': true,
							}
						},
						'timetables':{
							'create_class_timetable': {
								'view': true,
								'add': true,
								'edit': true,
								'import': true
							},
							'class_timetable': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'create_teacher_timetable': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'teacher_timetable': {
								'view': true,
								'add': true,
								'edit': true,
							},
						},
						'transport':{
							'school_bus': {
								'view': true,
								'add': true,
								'edit': true
							},
							'trips': {
								'view': true,
								'add': true,
								'edit': true
							},
							// 'pick_up_and_drop_off': {
								// 'view': true,
								// 'add': true,
								// 'edit': true,
							// },
							'mapped_history': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'transport_communications': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'transport_reports': {
								'view': true,
								'add': true,
								'edit': true,
							},
						}

					};
				break;
				case "ADMIN":
					$rootScope.permissions = {
						'dashboard':{
							'view': true,
						},
						'students':{
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'staff':{
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'school':{
							'school_settings': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'school_dates': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'grading': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'subjects': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'departments': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'classes': {
								'view': true,
								'add': true,
								'edit': true,
							}

						},
						'exams':{
							'exams': {
								'view': true,
								'add': true,
								'edit': true,
								'import': true
							},
							'exam_types': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'report_cards': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'class_analysis': {
								'view': true,
							},
							'stream_analysis': {
								'view': true,
							},
							'exam_reports': {
								'view': true,
							},
						},
						'communications':{
							'send_email' : {
								'view': true,
								'add': true,
								'edit': true,
							},
							'feedback' : {
							'view': true,
							'add': true,
							'edit': true,
						    }
						},
    					'timetables':{
						'create_class_timetable': {
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'class_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'create_teacher_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'teacher_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
					},
							'transport':{
									'school_bus': {
										'view': true,
										'add': true,
										'edit': true
									},
									'trips': {
										'view': true,
										'add': true,
										'edit': true
									},
									// 'pick_up_and_drop_off': {
										// 'view': true,
										// 'add': true,
										// 'edit': true,
									// },
									'mapped_history': {
										'view': true,
										'add': true,
										'edit': true,
									},
									'transport_communications': {
										'view': true,
										'add': true,
										'edit': true,
									},
									'transport_reports': {
										'view': true,
										'add': true,
										'edit': true,
									},
								}

					};
					break;
					case "PRINCIPAL":
					$rootScope.permissions = {
						'dashboard':{
							'view': true,
						},
						'students':{
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'staff':{
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'school':{
							'school_settings': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'school_dates': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'grading': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'subjects': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'departments': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'classes': {
								'view': true,
								'add': true,
								'edit': true,
							}

						},
						'exams':{
							'exams': {
								'view': true,
								'add': true,
								'edit': true,
								'import': true
							},
							'exam_types': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'report_cards': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'class_analysis': {
								'view': true,
							},
							'stream_analysis': {
								'view': true,
							},
							'exam_reports': {
								'view': true,
							},
						},
						'communications':{
							'send_email' : {
								'view': true,
								'add': true,
								'edit': true,
							},
							'feedback' : {
							'view': true,
							'add': true,
							'edit': true,
						    }
						},
    					'timetables':{
						'create_class_timetable': {
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'class_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'create_teacher_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'teacher_timetable': {
							'view': true,
							'add': true,
							'edit': true,
						},
					},
							'transport':{
									'school_bus': {
										'view': true,
										'add': true,
										'edit': true
									},
									'trips': {
										'view': true,
										'add': true,
										'edit': true
									},
									// 'pick_up_and_drop_off': {
										// 'view': true,
										// 'add': true,
										// 'edit': true,
									// },
									'mapped_history': {
										'view': true,
										'add': true,
										'edit': true,
									},
									'transport_communications': {
										'view': true,
										'add': true,
										'edit': true,
									},
									'transport_reports': {
										'view': true,
										'add': true,
										'edit': true,
									},
								}

					};
					break;
			case "ADMIN-FINANCE":
					$rootScope.permissions = {
						'dashboard':{
							'view': true,
						},
						'students':{
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'staff':{
							'view': true,
							'add': false,
							'edit': false,
							'import': true
						},
						'fees':{
							'dashboard': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'fee_structure': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'opening_balances': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'invoices': {
								'view': true,
								'add': true,
								'edit': true,
								'delete': true
							},
							'payments_received': {
								'view': true,
								'add': true,
								'edit': true,
								'delete': true
							},
							'fees_reports': {
    							'view': true,
    						},
								'quickbooks': {
									'view': true,
									'add': true,
									'edit':true
								},
						},
						'school':{
							'school_settings': {
								'view': true,
								'add': false,
								'edit': false,
							},
							'school_dates': {
								'view': true,
								'add': false,
								'edit': false,
							},
							'grading': {
								'view': false,
								'add': false,
								'edit': false,
							},
							'subjects': {
								'view': false,
								'add': false,
								'edit': false,
							},
							'departments': {
								'view': true,
								'add': false,
								'edit': false,
							},
							'classes': {
								'view': false,
								'add': false,
								'edit': false,
							}

						},
						'communications':{
							'send_email' : {
								'view': true,
								'add': true,
								'edit': true,
							},
							'feedback' : {
							'view': true,
							'add': true,
							'edit': true,
						    }
						}

					};
					break;
					case "ADMIN-TRANSPORT":
						$rootScope.permissions = {
							'dashboard':{
								'view': true,
							},
							'students':{
								'view': true,
								'add': false,
								'edit': true,
								'import': false
							},
								'transport':{
										'school_bus': {
											'view': true,
											'add': true,
											'edit': true
										},
										'trips': {
											'view': true,
											'add': true,
											'edit': true
										},
										'dashboard': {
											'view': true,
											'add': true,
											'edit': true
										},
										// 'pick_up_and_drop_off': {
											// 'view': true,
											// 'add': true,
											// 'edit': true,
										// },
										'mapped_history': {
											'view': true,
											'add': true,
											'edit': true,
										},
										'transport_communications': {
											'view': true,
											'add': true,
											'edit': true,
										},
										'transport_reports': {
											'view': true,
											'add': true,
											'edit': true,
										},
									}

						};
						break;
			case "FINANCE":
					$rootScope.permissions = {
						'dashboard':{
							'view': true,
						},
						'students':{
							'view': true,
							'add': false,
							'edit': false,
							'import': false
						},
						'staff':{
							'view': false,
							'add': false,
							'edit': false,
							'import': false
						},
						'fees':{
							'dashboard': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'fee_structure': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'opening_balances': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'invoices': {
								'view': true,
								'add': true,
								'edit': true,
								'delete': true
							},
							'payments_received': {
								'view': true,
								'add': true,
								'edit': true,
								'delete': true
							},
							'fees_reports': {
    							'view': true,
    						},
								'quickbooks': {
									'view': true,
									'add': true,
									'edit':true
								},
						},
						'school':{
							'school_settings': {
								'view': true,
								'add': false,
								'edit': false,
							},
							'school_dates': {
								'view': true,
								'add': false,
								'edit': false,
							},
							'grading': {
								'view': false,
								'add': false,
								'edit': false,
							},
							'subjects': {
								'view': false,
								'add': false,
								'edit': false,
							},
							'departments': {
								'view': true,
								'add': false,
								'edit': false,
							},
							'classes': {
								'view': false,
								'add': false,
								'edit': false,
							}

						}

					};
					break;
			case "TEACHER":
				$rootScope.permissions = {
					'dashboard':{
						'view': true,
					},
					'students':{
						'alt_label': 'my_students',
						'view': true
					},
					'school':{
						'alt_label': 'my_classes',
						'subjects': {
							'view': true,
							'add': false,
							'edit': true,
						},
						'classes': {
							'view': true,
							'add': false,
							'edit': true,
						}

					},
					'exams':{
						'exams': {
							'view': true,
							'add': true,
							'edit': true,
							'import': true
						},
						'exam_types': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'report_cards': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'class_analysis': {
							'view': true,
						},
						'stream_analysis': {
							'view': true,
						},
						'exam_reports': {
								'view': true,
						},
					},
					'transport':{
						'school_bus': {
							'view': false,
							'add': false,
							'edit': false
						},
						'trips': {
							'view': false,
							'add': false,
							'edit': false
						},
						// 'pick_up_and_drop_off': {
    							// 'view': true,
    							// 'add': true,
    							// 'edit': true,
    					// },
							'mapped_history': {
    							'view': true,
    							'add': true,
    							'edit': true,
    					},
							'transport_communications': {
								'view': true,
								'add': true,
								'edit': true,
							},
							'transport_reports': {
								'view': true,
								'add': true,
								'edit': true,
							},
					},
					'communications':{
						'homework': {
							'view': true,
							'add': true,
							'edit': true,
						},
						'homework_feedback': {
							'view': true,
							'add': true,
							'edit': true
						}
					},
					'resources':{
						'create_resource': {
							'view': true,
							'add': true,
							'edit': true,
						}
					}
				};
				break;

			default:
				$rootScope.permissions = {
					dashboard:{
						view: true,
					}
				};
		}
		*/

		// communications notifications icon

		if( $( "li:contains('Communications')" ) ){
    	    // console.log("Notification element");
    	    apiService.getFeedbackUnopenedCount({}, function(response){
    				var result = angular.fromJson(response);

    				if( result.response == 'success' && parseInt(result.data.count) > 0 )
    				{
    					$( "li a:contains('Communications')" ).append( "<span class='notifBox'>" + result.data.count + "</span>" );
    				}

    			}, function(){ console.log("There was an error counting notifications for feedback messages."); });
    	}


	}

	var showNotAuthorized = function()
	{
		alert("Not Authorized");
	}

	var showLoginError = function (args)
	{
		$rootScope.$broadcast('displayLoginError', args);
	}

	var showUpdatePwdForm = function()
	{
		$rootScope.$broadcast('displayLoginError');
		$rootScope.updatePwd = true;
		var dlg = $dialogs.create('updatePwd.html','updatePwdCtrl',{user:$rootScope.currentUser},{size: 'md',backdrop:'static'});
		dlg.result.then(function(result){
			// if success, show the login box again and have them login
			// show message
			$rootScope.$broadcast('pwdUpdatedMsg');


		},function(){

		});
	}

	$scope.updatePassword = function()
	{
		showUpdatePwdForm();
	}

	var goHome = function()
	{
		$rootScope.loggedIn = false;
    $rootScope.currentUser = undefined;
    $rootScope.postStatuses
    $rootScope.comTypes = undefined;
    $rootScope.comAudience = undefined;
    $rootScope.classes = undefined;
    $rootScope.classSubjects = undefined;
    $rootScope.allClasses = undefined;
    $rootScope.terms = undefined;
    $rootScope.classCats = undefined;
    $rootScope.permissions = undefined;
    $rootScope.manageUsers = undefined;
    $rootScope.useLetterhead = undefined;
    $rootScope.navItems = undefined;
    $rootScope.mainSubNavItems = undefined;
    $rootScope.empCats = undefined;
    $rootScope.allDepts = undefined;
    $rootScope.examTypes = undefined;

		$state.go('index');
	}

	//$scope.currentUser = null;
	$scope.userRoles = USER_ROLES;
	$scope.isAuthorized = Auth.isAuthorized;

	//listen to events of unsuccessful logins, to run the login dialog
	$rootScope.$on(AUTH_EVENTS.notAuthorized, showLoginDialog);
	$rootScope.$on(AUTH_EVENTS.notAuthenticated, showLoginDialog);
	$rootScope.$on(AUTH_EVENTS.sessionTimeout, showLoginDialog);
	$rootScope.$on(AUTH_EVENTS.logoutSuccess, goHome);
	$rootScope.$on(AUTH_EVENTS.loginSuccess, setCurrentUser);
	$rootScope.$on(AUTH_EVENTS.loginFailed, function(event,args){showLoginError(args); });
	$rootScope.$on(AUTH_EVENTS.updatePwd, showUpdatePwdForm);

	$scope.openModal = function (section, view, size, item)
	{
		var testEl = $('#filterLinks'); // console.log(testEl);
		// console.log("Check",testEl);
		if(testEl){
			if( $('#filterLinks').hasClass('in') )
			{
				$('#subnav').trigger('click');
			}
		}

		if( !$scope.modalShown )
		{
			$scope.modalShown = true;
			var controller = view + 'Ctrl';
			if (size === undefined ) size = 'lg';
			var dlg = $dialogs.create(
				'app/' + section + '/' + view + '.html',
				controller,
				item,
				{
					keyboard: true,
					backdrop: 'static',
					size: size,
				}
			);
			dlg.result.then(function(data){
			  // save
			  $scope.modalShown = false;
			  $rootScope.isSearchModal = false;
			  $rootScope.printModal = false;
			},function(){
			  // cancel, close, no save
			  $scope.modalShown = false;
			  $rootScope.isSearchModal = false;
			  $rootScope.printModal = false;
			});
			$rootScope.theModal = dlg;
		}

	};


	$rootScope.chartColors = ['rgba(151,187,205,1)','rgba(220,220,220,1)','rgba(247,70,74,1)','rgba(70,191,189,1)','rgba(253,180,92,1)','rgba(148,159,177,1)','rgba(77,83,96,1)','rgba(181,221,56,1)','rgba(218,150,240,1)'];
	// console.log($rootScope);


} ])
