'use strict';

angular.module('eduwebApp').
controller('schoolSettingsCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$filter','FileUploader','dialogs',
function($scope, $rootScope, apiService, $timeout, $window, $filter, FileUploader, $dialogs){

	$scope.alert = {};
	$scope.bankDetails = false;
	$scope.saveTerms = false;
	$scope.upldMenuAttchmnt = false;
	$scope.uploadTxt = '* Optional';
	function saveMenuName(fileName){
		console.log('Saving the file name ' + fileName);
		var postData = {
			settings: [{
				name: 'Menu Attchment',
				value: fileName,
				append: false
			}]
		}
		apiService.updateSettings(postData, createCompleted, apiError);
	}
	var uploader3 = $scope.uploader3 = new FileUploader({
			url: 'upload.php',
			formData : [{
				'dir': 'menus'
			}]
		});

	var initializeController = function ()
	{
		//var deptCats = $rootScope.currentUser.settings['Department Categories'];
		//$scope.deptCats = deptCats.split(',');
		apiService.getAllCountries({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$scope.countries = [];
				$scope.allCountries = result.data;
				$scope.allCountries.forEach((item, i) => {
					if(item.curriculum){
						item.curriculum = item.curriculum.split(',');
					}
					$scope.countries.push(item.countries_name);
				});
				$scope.countries.sort();
				setSettings();
				// console.log($scope.countries);
			}

		},apiError);

		$scope.schoolTypes = ['Private School','Public School'];
		// $scope.curriculums = ['8-4-4','I.G.C.S.E','Montessori','Dual Curriculum (8-4-4/IGCSE)'];
		// $scope.currencies = ['Ksh'];
		$scope.schoolLevels = ['Primary','Secondary'];
		if( $rootScope.currentUser.settings['School Name'] === undefined ) $scope.initialSetup = true;
		$scope.menuReady = false;
	}
	$timeout(initializeController,1);

	$scope.modifyCurr = function(el){
		$scope.allCountries.forEach((cntry, i) => {
			if(cntry.countries_name == el.settings.Country){
				$scope.curriculums = cntry.curriculum;
				$scope.currencies = [{name: cntry.currency_name, symbol: cntry.currency_symbol}];
			}
		});
	}

	var getSettings = function()
	{
		// update the users settings
		apiService.getSettings({},function(response){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var settings = result.data.reduce(function ( total, current ) {
					total[ current.name ] = current.value;
					return total;
				}, {});

				$rootScope.$emit('setSettings', settings);
				setSettings();
			}

		},apiError);
	}

	var setSettings = function()
	{
		$scope.settings	= {
			'Address 1' : angular.copy($rootScope.currentUser.settings['Address 1']),
			'Address 2' : angular.copy($rootScope.currentUser.settings['Address 2']),
			'Country' : angular.copy($rootScope.currentUser.settings['Country']),
			'Currency' : angular.copy($rootScope.currentUser.settings['Currency']),
			'Curriculum' : angular.copy($rootScope.currentUser.settings['Curriculum']),
			'Email Address' : angular.copy($rootScope.currentUser.settings['Email Address']),
			'Email From' : angular.copy($rootScope.currentUser.settings['Email From']),
			'Phone Number' : angular.copy($rootScope.currentUser.settings['Phone Number']),
			'School Name' : angular.copy($rootScope.currentUser.settings['School Name']),
			'School Type' : angular.copy($rootScope.currentUser.settings['School Type']	),
			'School Level' : angular.copy($rootScope.currentUser.settings['School Level']	),
			'logo' : angular.copy($rootScope.currentUser.settings['logo']	),
			'Letterhead' : angular.copy($rootScope.currentUser.settings['Letterhead']	),
			'Bank Name' : angular.copy($rootScope.currentUser.settings['Bank Name']	),
			'Bank Branch' : angular.copy($rootScope.currentUser.settings['Bank Branch']	),
			'Account Name' : angular.copy($rootScope.currentUser.settings['Account Name']	),
			'Account Number' : angular.copy($rootScope.currentUser.settings['Account Number']	),
			'Bank Name 2' : angular.copy($rootScope.currentUser.settings['Bank Name 2']	),
			'Bank Branch 2' : angular.copy($rootScope.currentUser.settings['Bank Branch 2']	),
			'Account Name 2' : angular.copy($rootScope.currentUser.settings['Account Name 2']	),
			'Account Number 2' : angular.copy($rootScope.currentUser.settings['Account Number 2']	),
			'Bank Name 3' : angular.copy($rootScope.currentUser.settings['Bank Name 3']	),
			'Bank Branch 3' : angular.copy($rootScope.currentUser.settings['Bank Branch 3']	),
			'Account Name 3' : angular.copy($rootScope.currentUser.settings['Account Name 3']	),
			'Account Number 3' : angular.copy($rootScope.currentUser.settings['Account Number 3']	),
			'Bank Name 4' : angular.copy($rootScope.currentUser.settings['Bank Name 4']	),
			'Bank Branch 4' : angular.copy($rootScope.currentUser.settings['Bank Branch 4']	),
			'Account Name 4' : angular.copy($rootScope.currentUser.settings['Account Name 4']	),
			'Account Number 4' : angular.copy($rootScope.currentUser.settings['Account Number 4']	),
			'Bank Name 5' : angular.copy($rootScope.currentUser.settings['Bank Name 5']	),
			'Bank Branch 5' : angular.copy($rootScope.currentUser.settings['Bank Branch 5']	),
			'Account Name 5' : angular.copy($rootScope.currentUser.settings['Account Name 5']	),
			'Account Number 5' : angular.copy($rootScope.currentUser.settings['Account Number 5']	),
			'Mpesa Details' : angular.copy($rootScope.currentUser.settings['Mpesa Details']	),
			'Use Feedback' : angular.copy($rootScope.currentUser.settings['Use Feedback']	),
			'Use Receipt Items' : angular.copy($rootScope.currentUser.settings['Use Receipt Items'] ),
			'Use Autoadmission' : angular.copy($rootScope.currentUser.settings['Use Autoadmission']	),
			'Committees' : angular.copy($rootScope.currentUser.settings['Committees']	),
			'Clubs' : angular.copy($rootScope.currentUser.settings['Clubs']	),
			'Houses' : angular.copy($rootScope.currentUser.settings['Houses']	),
			'Exam Calculation' : angular.copy($rootScope.currentUser.settings['Exam Calculation']),
			'Payment Terms' : angular.copy($rootScope.currentUser.settings['Payment Terms']),
			'Menu Attchment' : angular.copy($rootScope.currentUser.settings['Menu Attchment']),
		}
		if($scope.settings['Payment Terms']){
			$scope.termsTxt = $scope.settings['Payment Terms'];
		}
		if($scope.settings['Use Receipt Items'] == undefined){
			$scope.settings['Use Receipt Items'] = "true";
		}
		if($scope.settings.Currency){
			$scope.allCountries.forEach((cntry, i) => {
				if(cntry.countries_name == $scope.settings.Country){
					$scope.curriculums = cntry.curriculum;
					$scope.currencies = [{name: cntry.currency_name, symbol: cntry.currency_symbol}];
				}
			});
		}
		// console.log($scope.settings);

		if($scope.settings['Bank Name 2'] == 'Null'){ $scope.settings['Bank Name 2'] = ''; }
		if($scope.settings['Bank Branch 2'] == 'Null'){ $scope.settings['Bank Branch 2'] = ''; }
		if($scope.settings['Account Name 2'] == 'Null'){ $scope.settings['Account Name 2'] = ''; }
		if($scope.settings['Account Number 2'] == 'Null'){ $scope.settings['Account Number 2'] = ''; }
		if($scope.settings['Bank Name 3'] == 'Null'){ $scope.settings['Bank Name 3'] = ''; }
		if($scope.settings['Bank Branch 3'] == 'Null'){ $scope.settings['Bank Branch 3'] = ''; }
		if($scope.settings['Account Name 3'] == 'Null'){ $scope.settings['Account Name 3'] = ''; }
		if($scope.settings['Account Number 3'] == 'Null'){ $scope.settings['Account Number 3'] = ''; }
		if($scope.settings['Bank Name 4'] == 'Null'){ $scope.settings['Bank Name 4'] = ''; }
		if($scope.settings['Bank Branch 4'] == 'Null'){ $scope.settings['Bank Branch 4'] = ''; }
		if($scope.settings['Account Name 4'] == 'Null'){ $scope.settings['Account Name 4'] = ''; }
		if($scope.settings['Account Number 4'] == 'Null'){ $scope.settings['Account Number 4'] = ''; }
		if($scope.settings['Bank Name 5'] == 'Null'){ $scope.settings['Bank Name 5'] = ''; }
		if($scope.settings['Bank Branch 5'] == 'Null'){ $scope.settings['Bank Branch 5'] = ''; }
		if($scope.settings['Account Name 5'] == 'Null'){ $scope.settings['Account Name 5'] = ''; }
		if($scope.settings['Account Number 5'] == 'Null'){ $scope.settings['Account Number 5'] = ''; }
		if($scope.settings['Mpesa Details'] == 'Null'){ $scope.settings['Mpesa Details'] = ''; }

		if($scope.settings['Use Feedback'] == 'true'){

		    // console.log("Checking status, feedback = " + $scope.settings['Use Autoadmission']);
		    // Params ($selector, boolean)
            function setSwitchState(el, flag) {
                el.attr('checked', flag);
            }
            // change switch status
            setSwitchState($('#feedbackStat.switch-input'), true);
		}
		if($scope.settings['Use Receipt Items'] == 'true'){
			function setSwitchState2(el, flag) {
				el.attr('checked', flag);
			}
			setSwitchState2($('#receiptStat.switch-input'), true);
		}

		if($scope.settings['Use Autoadmission'] == 'true'){
		    $scope.autoAdmissionEn = true; // show automatic admissions options

		    // console.log("Checking status, auto admission = " + $scope.settings['Use Autoadmission']);
		    // Params ($selector, boolean)
            function setSwitchState(el, flag) {
                el.attr('checked', flag);
            }

            // change switch status
            setSwitchState($('#autoAdmission.switch-input'), true);
		}
		$scope.settings["Exam Calculation"] = ($scope.settings["Exam Calculation"] == undefined ? "" : $scope.settings["Exam Calculation"]);
		// console.log($scope.settings);

	}

	$scope.getFeedbackSetting = function(el){

        // process the switch

        $('#feedbackStat.switch-input').on('change', function() {
            var isChecked = $(this).is(':checked');
            var selectedData;
            var $switchLabel = $('#feedbackSwitch.switch-label');

            if($scope.settings[ 'Use Feedback' ] == "true"){
                // console.log("Feedback was true, now switcing to false");
                selectedData = $switchLabel.attr('data-off');
            } else {
                // console.log("Feedback was false, now switching on");
                selectedData = $switchLabel.attr('data-on');
            }

            // console.log('Selected feedback = ' + selectedData);

        });

        // Params ($selector, boolean)
        function setSwitchState(el, flag) {
            // console.log("Changing feedback switch status .....");
            el.attr('checked', flag);
        }

        // change switch status
        setSwitchState($('#feedbackStat.switch-input'), true);

        // make the change
        var postData = {
			settings: [{
				    name: 'Use Feedback',
    				value: ( $scope.settings[ 'Use Feedback' ] == "false" ? "true" : "false" ),
    				append: false
			}]
		}
        apiService.updateSettings(postData, createCompleted, apiError);
    }

		$scope.getReceiptSetting = function(el){

	        // process the switch

	        $('#receiptStat.switch-input').on('change', function() {
	            var isChecked = $(this).is(':checked');
	            var selectedData;
	            var $switchLabel = $('#receiptSwitch.switch-label');

	            if($scope.settings[ 'Use Receipt Items' ] == "true"){
	                // console.log("Receipt items was true, now switcing to false");
	                selectedData = $switchLabel.attr('data-off');
	            } else {
	                // console.log("Receipt items was false, now switching on");
	                selectedData = $switchLabel.attr('data-on');
	            }

	            // console.log('Selected receipt mode = ' + selectedData);

	        });

	        // Params ($selector, boolean)
	        function setSwitchState(el, flag) {
	            // console.log("Changing receipt switch status .....");
	            el.attr('checked', flag);
	        }

	        // change switch status
	        setSwitchState($('#receiptStat.switch-input'), true);

	        // make the change
	        var postData = {
						settings: [{
							    name: 'Use Receipt Items',
			    				value: ( $scope.settings[ 'Use Receipt Items' ] == "false" ? "true" : "false" ),
			    				append: false
						}]
					}
	        apiService.updateSettings(postData, createCompleted, apiError);
	    }

    $scope.automaticAdmissionNumbers = function(el){

        $scope.autoAdmissionEn = true; // show automatic admissions options

        // process the switch

        $('#autoAdmission.switch-input').on('change', function() {
            var isChecked = $(this).is(':checked');
            var selectedData;
            var $switchLabel = $('#admissionSwitch.switch-label');

            if($scope.settings[ 'Use Autoadmission' ] == "true"){
                // console.log("Auto admission was true, now switcing to false");
                selectedData = $switchLabel.attr('data-off');
            } else {
                // console.log("Auto admission was false, now switching on");
                selectedData = $switchLabel.attr('data-on');
            }

            // console.log('Selected auto admission = ' + selectedData);

        });

        // Params ($selector, boolean)
        function setSwitchState(el, flag) {
            // console.log("Changing switch status .....");
            el.attr('checked', flag);
        }

        // change switch status
        setSwitchState($('#autoAdmission.switch-input'), true);

        var updateAdmission = {
			settings: [{
				    name: 'Use Autoadmission',
    				value: ( $scope.settings[ 'Use Autoadmission' ] == "false" ? "true" : "false" ),
    				append: false
			}]
		}
        apiService.updateSettings(updateAdmission, createCompleted, apiError);
    }

		$scope.setExamCalculation = function(el){
			let selectedOption = el.settings["Exam Calculation"];
			let updateExamCalculation = {
				settings: [{
							name: 'Exam Calculation',
							value: selectedOption,
							append: false
				}]
			}
			apiService.updateSettings(updateExamCalculation, createCompleted, apiError);
		}

		$scope.getBankingDetails = function(){
			apiService.getAllBnkDetails({},function(response){
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					$scope.bankDetails = (result.nodata ? false : true);
					$scope.bankData = (result.data ? result.data : []);
				}

			},apiError);
		}

		$scope.saveBank = function(){
			let bnkNameInp = document.getElementById("bnkName");
			let bnkBranchInp = document.getElementById("bnkBranch");
			let accNameInp = document.getElementById("accName");
			let accNumInp = document.getElementById("accNumber");
			let bnkName = bnkNameInp.value;
			let bnkBranch = bnkBranchInp.value;
			let accName = accNameInp.value;
			let accNumber = accNumInp.value;
			if(bnkName == null || bnkName == ''){
				bnkNameInp.style.border = '2px solid red';
				setTimeout(function(){ bnkNameInp.style.border = '1px solid #cccccc'; }, 2000);
			}
			if(bnkBranch == null || bnkBranch == ''){
				bnkBranchInp.style.border = '2px solid red';
				setTimeout(function(){ bnkBranchInp.style.border = '1px solid #cccccc'; }, 2000);
			}
			if(accName == null || accName == ''){
				accNameInp.style.border = '2px solid red';
				setTimeout(function(){ accNameInp.style.border = '1px solid #cccccc'; }, 2000);
			}
			if(accNumber == null || accNumber == '2px solid red'){
				accNumInp.style.border = '2px solid red';
				setTimeout(function(){ accNumInp.style.border = '1px solid #cccccc'; }, 2000);
			}
			if(bnkName && bnkBranch && accName && accNumber){
				// post
				let data =  {
					bank: bnkName,
					branch: bnkBranch,
					acc_name: accName,
					acc_number: accNumber
				}
				apiService.addBnk(data,function ( response, status, params )
				{
					var result = angular.fromJson( response );
					if( result.response == 'success' ){
						alert("Record saved successfully.");
						bnkNameInp.value = null;
						bnkBranchInp.value = null;
						accNameInp.value = null;
						accNumInp.value = null;
						$scope.getBankingDetails();
					}
					else
					{
						$scope.error = true;
						$scope.errMsg = result.data;
					}
				},apiError);
			}
		}

		$scope.getSchMenu = function(){
			apiService.getSchMenu({},function(response){
				var result = angular.fromJson( response );
				let menu = null;
				if( result.response == 'success' )
				{
					menu = (result.data ? result.data : []);
				}else{
					menu = [];
				}
				console.log("DB menu >",menu);
				$scope.days = [
					{num: 1, day: 'Monday', mealTimes: [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}] },
					{num: 2, day: 'Tuesday', mealTimes: [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}] },
					{num: 3, day: 'Wednesday', mealTimes: [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}] },
					{num: 4, day: 'Thursday', mealTimes: [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}] },
					{num: 5, day: 'Friday', mealTimes: [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}] },
					{num: 6, day: 'Saturday', mealTimes: [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}] },
					{num: 7, day: 'Sunday', mealTimes: [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}] }
				];
				$scope.mealTimes = [{num:1, time:'BREAK'},{num:2, time:'LUNCH'}];
				setTimeout(function(){
					let mod = document.getElementById('schMenu');
					let undoBtns = mod.getElementsByClassName('history_tools');
					for (let i = 0; i < undoBtns.length; i++) {undoBtns[i].style.display = 'none';}
					let boxes = mod.getElementsByClassName('trix-content');
					for (let j = 0; j < boxes.length; j++) {boxes[j].style.minHeight = '0'; boxes[j].style.height = '125px';}
				}, 2000);
				if(menu.length > 0){
					$scope.days.forEach((item, i) => {
						for(let j=0;j < menu.length;j++){
							if(item.day == menu[j].day_name){
								console.log("Day Match ie " + item.day + " and " + menu[j].day_name);
								item.mealTimes.forEach((item2, k) => {
									if(item2.time == menu[j].break_name){
										item2.meal = menu[j].meal;

										// console.log("id = " + idName);
										// document.getElementById(idName).innerHTML = menu[j].meal;
									}
								});

							}
						}
					});

				}
			},apiError);

			// init meu attachment button
			// $( document ).ready(function() {
				$("#upload3_menu")
				.change(function(){
					/*
					if( uploader3.queue[0] !== undefined )
					{
						// need a unique filename
						$scope.filename3 =  window.location.host.split('.')[0] + '_reportCard_' + '_' + $scope.student.student_name.split(" ")[0] + '_' + $scope.student.student_id + "_termId-" + el.term.term_id + "_" + uploader3.queue[0].file.name;
						uploader3.queue[0].file.name = $scope.filename3;
						uploader3.uploadAll();
					}
					*/
					console.log('Change has been detected...');
					// let filename = $("#upload3_menu").val();
					$scope.upldMenuAttchmnt = true;
					let filename = document.getElementById("upload3_menu").files[0].name;
					let exts = ['jpg','png','pdf'];
					let ext = filename.split('.').pop();
					if(exts.includes(ext)){
						$scope.uploadTxt = filename;
						if( uploader3.queue[0] !== undefined )
						{
							console.log('uploader3.queue[0] >',uploader3.queue[0]);
							// need a unique filename
							console.log('Will upload now...');
							$scope.filename3 =  window.location.host.split('.')[0] + '_menu_' + uploader3.queue[0].file.name;
							uploader3.queue[0].file.name = $scope.filename3;
							uploader3.uploadAll();
							saveMenuName($scope.filename3);
						}
					}else{
						$scope.uploadTxt = "Only 'jpg' or 'png' images are allowed or pdf's";
					}
					console.log('The file name is ' + filename, 'Show attachment? ' + $scope.upldMenuAttchmnt, 'Text is = ' + $scope.uploadTxt);
				});
			// });

		}

		$scope.saveMealBtn = function(){ $scope.menuReady = true; console.log($scope.days);}

		$scope.saveSchMenu = function(){
			// console.log($scope.days);
			let payload = [];
			$scope.days.forEach((item, i) => {
				item.mealTimes.forEach((item2, j) => {
					if('meal' in item2){
						let payloadObj = {};
						payloadObj.day = item.day;
						payloadObj.time = item2.time;
						payloadObj.meal = item2.meal;
						payload.push(payloadObj);
					}
				});
			});
			console.log("Payload >",payload);
			let data = {menu: payload}

			apiService.updateSchoolMenu(data,function ( response, status, params )
			{
				var result = angular.fromJson( response );
				if( result.response == 'success' ){
					alert("The school menu changes have been saved successfully.");
					// $scope.getBankingDetails();
				}
				else
				{
					$scope.error = true;
					alert("An error occured while trying to save the menu changes.");
				}
			},function(err){console.log(err)});
		}

		$scope.attachMenu = function(el)
		{
		    $("#upload3_menu")
				.change(function(){
					if( uploader3.queue[0] !== undefined )
					{
						// need a unique filename
						$scope.filename3 =  window.location.host.split('.')[0] + '_menu_' + uploader3.queue[0].file.name;
						uploader3.queue[0].file.name = $scope.filename3;
						uploader3.uploadAll();
					}
				});
		}

	$scope.$watch('uploader.queue[0]', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		$scope.schoolForm.$setDirty();
	});

	$scope.$watch('uploader2.queue[0]', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		$scope.schoolForm.$setDirty();
	});

	$scope.save = function(theForm)
	{
		$scope.error = false;
		$scope.errMsg = '';

		if( !theForm.$invalid )
		{
			// do logo upload
			if( uploader.queue[0] !== undefined )
			{
				uploader.queue[0].file.name = moment() + '_' + uploader.queue[0].file.name;
				uploader.uploadAll();
			}
			// do letterhead upload
			if( uploader2.queue[0] !== undefined )
			{
				uploader2.queue[0].file.name = moment() + '_' + uploader2.queue[0].file.name;
				uploader2.uploadAll();
			}

			var settings = [];
			angular.forEach( $scope.settings, function(item,key){

				if( uploader.queue[0] !== undefined && key == 'logo' )
				{
					settings.push({
						name: 'logo',
						value: uploader.queue[0].file.name,
						append: false
					})
				}
				else if( uploader2.queue[0] !== undefined && key == 'Letterhead' )
				{
					settings.push({
						name: 'Letterhead',
						value: uploader2.queue[0].file.name,
						append: false
					})
				}
				else
				{
					settings.push({
						name: key,
						value: item,
						append: false
					})
				}
			});

			var postData = {
				settings: settings
			}
			$scope.saving = true;
			apiService.updateSettings(postData, createCompleted, apiError);
		}
	}

	var createCompleted = function(response,status)
	{
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			$scope.initialSetup = false;
			getSettings();
			$scope.schoolForm.$setPristine();
			$scope.saving = false;
		}
		else
		{
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	}

	$scope.addEmpCat = function()
	{
		var dlg = $dialogs.create('addEmpCategory.html','addEmpCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.empCats = undefined;
			$rootScope.getEmpCats();
		},function(){

		});
	}

	$scope.editEmpCat = function(item)
	{
		var dlg = $dialogs.create('addEmpCategory.html','addEmpCategoryCtrl',{item:item},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.empCats = undefined;
			$rootScope.getEmpCats();
		},function(){

		});
	}

	$scope.removeEmpCat = function(item)
	{
		$scope.error = false;
		apiService.checkEmpCat(item.emp_cat_id,function(response,status){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var canDelete = ( parseInt(result.data.num_employees) == 0 ? true : false );

				if( canDelete )
				{
					var dlg = $dialogs.confirm('Delete Employee Category','Are you sure you want to permanently delete employee category <strong>' + item.emp_cat_name + '</strong>? ',{size:'sm'});
					dlg.result.then(function(btn){
						apiService.deleteEmpCat(item.emp_cat_id,function(response,status){
							$rootScope.empCats = undefined;
							$rootScope.getEmpCats();
						},apiError);
					});
				}
				else
				{
					var dlg = $dialogs.confirm('Please Confirm','Employee category <strong>' + item.emp_cat_name + '</strong> is associated with <b>' + result.data.num_employees + '</b> employees. Are you sure you want to mark this employee category as in-active? ',{size:'sm'});
					dlg.result.then(function(btn){
						var data = {
							user_id : $rootScope.currentUser.user_id,
							emp_cat_id: item.emp_cat_id,
							status: 'f'
						}
						apiService.setEmployeeCatStatus(data,function(response,status){
							$rootScope.empCats = undefined;
							$rootScope.getEmpCats();
						},apiError);

					});
				}
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		},apiError);

	}

	$scope.addClassCat = function()
	{
		var dlg = $dialogs.create('addClassCategory.html','addClassCategoryCtrl',{},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.classCats = undefined;
			$rootScope.getClassCats();
		},function(){

		});
	}

	$scope.editClassCat = function(item)
	{
		var dlg = $dialogs.create('addClassCategory.html','addClassCategoryCtrl',{item:item},{size: 'sm',backdrop:'static'});
		dlg.result.then(function(category){
			$rootScope.classCats = undefined;
			$rootScope.getClassCats();
		},function(){

		});
	}

	$scope.removeClassCat = function(item)
	{
		$scope.error = false;
		apiService.checkClassCat(item.class_cat_id,function(response,status){
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				var canDelete = ( parseInt(result.data.num_exams) == 0 ? true : false );

				if( canDelete )
				{
					var dlg = $dialogs.confirm('Delete Class Category','Are you sure you want to permanently delete parent class <strong>' + item.class_cat_name + '</strong>? ',{size:'sm'});
					dlg.result.then(function(btn){
						apiService.deleteClassCat(item.class_cat_id,function(response,status){
							$rootScope.classCats = undefined;
							$rootScope.getClassCats();
						},apiError);
					});
				}
				else
				{
					var dlg = $dialogs.confirm('Please Confirm','Parent class <strong>' + item.class_cat_name + '</strong> is associated with <b>' + result.data.num_exams + '</b> exam marks. Are you sure you want to mark this parent class as in-active? ',{size:'sm'});
					dlg.result.then(function(btn){
						var data = {
							user_id : $rootScope.currentUser.user_id,
							class_cat_id: item.class_cat_id,
							status: 'f'
						}
						apiService.setClassCatStatus(data,function(response,status){
							$rootScope.classCats = undefined;
							$rootScope.getClassCats();
						},apiError);

					});
				}
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		},apiError);
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		var msg = ( result.data.indexOf('"U_active_emp_cat"') > -1 ? 'The Employee Category name you entered already exists.' : result.data);
		$scope.errMsg = msg;
	}


	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'schools'
			}]
    });
	var uploader2 = $scope.uploader2 = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'schools'
			}]
    });

	$scope.$on('$destroy', function() {
		if($scope.dataGrid){
			$('.fixedHeader-floating').remove();
			$scope.dataGrid.fixedHeader.destroy();
			$scope.dataGrid.clear();
			$scope.dataGrid.destroy();
		}
		$rootScope.isModal = false;
    });

		$scope.createTerms = function(){
		$scope.saveTerms = true;
	}

	$scope.saveTermsTxt = function(){
		console.log($scope.termsTxt);
		// post
		let data = {terms: $scope.termsTxt}

		apiService.addPaymentTerms(data,function ( response, status, params )
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' ){
				alert("Payment terms have been saved successfully.");
				// $scope.getBankingDetails();
			}
			else
			{
				$scope.error = true;
				alert("An error occured while trying to save the payment terms.");
			}
		},apiError);
	}

} ])
.controller('addEmpCategoryCtrl',[ '$scope','$rootScope','$uibModalInstance','apiService','dialogs','data',
function($scope,$rootScope,$uibModalInstance,apiService,$dialogs,data){

		$scope.edit = (data.item !== undefined ? true : false);
		$scope.empCat = data.item || {};

		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel

		$scope.save = function()
		{
			if( $scope.edit )
			{
				var dlg = $dialogs.confirm('Update Employee Category','Are you sure you want to update this employee category? It will also update all employees that are associated with this category.', {size:'sm'});
				dlg.result.then(function(btn){
					var data = {
						emp_cat_id : $scope.empCat.emp_cat_id,
						emp_cat_name : $scope.empCat.emp_cat_name,
						user_id: $rootScope.currentUser.user_id
					}
					apiService.updateEmployeeCat(data, createCompleted, apiError);
				});
			}
			else
			{
				var data = {
					emp_cat_name : $scope.empCat.emp_cat_name,
					user_id: $rootScope.currentUser.user_id
				}
				apiService.addEmployeeCat(data, createCompleted, apiError);
			}

		}; // end save


		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				$uibModalInstance.close(result.data);
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}
		}


		var apiError = function(response,status)
		{
			var result = angular.fromJson( response );
			$scope.error = true;
			var msg = ( result.data.indexOf('"U_active_emp_cat"') > -1 ? 'The Employee Category name you entered already exists.' : result.data);
			$scope.errMsg = msg;
		}

		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.save();
		};




	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addEmpCategory.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> {{ (edit ? \'Update\' : \'Add\') }} Employee Category</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="catDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- emp_cat_name -->' +
					'<div class="form-group" ng-class="{ \'has-error\' : catDialog.emp_cat_name.$invalid && (catDialog.emp_cat_name.$touched || catDialog.$submitted) }">' +
						'<label for="emp_cat_name" class="col-sm-3 control-label">Employee Category Name</label>' +
						'<div class="col-sm-9">' +
							'<input type="text" name="emp_cat_name" ng-model="empCat.emp_cat_name" class="form-control" required >' +
							'<p ng-show="catDialog.emp_cat_name.$invalid && (catDialog.emp_cat_name.$touched || catDialog.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Employee Category Name is required.</p>' +
						'</div>' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="button" class="btn btn-primary" ng-click="save()">{{ (edit ? \'Update\' : \'Save\') }}</button>' +
			'</div>'
		);
}]);
