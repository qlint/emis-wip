'use strict';

angular.module('eduwebApp').
controller('viewStudentCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'FileUploader', 'data',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, FileUploader, data){
	
	$rootScope.modalLoading = false;
	$scope.tabs = ( $rootScope.currentUser.user_type == 'TEACHER' ? ['Details','Family','Medical History','Exams','Report Cards','News'] : ['Details','Family','Medical History','Fees','Exams','Report Cards','News'] );
	$scope.feeTabs = ['Fee Summary','Invoices','Payments Received','Fee Items'];
	$scope.currentTab = $scope.tabs[0];
	$scope.currentFeeTab = $scope.feeTabs[0];
	$scope.hasChanges = false;
	$scope.currency = $rootScope.currentUser.settings['Currency'];
	var originalData;
	$scope.filters = {};
	
	$scope.edit = ($rootScope.permissions.students.edit ? true : false );

	
	$scope.feeItemSelection = [];
	$scope.optFeeItemSelection = [];
	$scope.conditionSelection = [];
	
	$scope.student = {};
	$scope.student.admission_date = {};
	$scope.initLoad = true;
	
	$scope.initializeController = function()
	{
		
		apiService.getStudentDetails(data.student_id, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				var student = $rootScope.formatStudentData([result.data]);
				
				// set current class to full class object
				var currentClass = $rootScope.allClasses.filter(function(item){
					if( item.class_id == student[0].class_id ) return item;
				});
				
				student[0].current_class = currentClass[0];

				$scope.student = student[0];

				originalData = angular.copy($scope.student);
				
				getFeeItems();
				
			}
		});
	
		var studentCats = $rootScope.currentUser.settings['Student Categories'];
		$scope.studentCats = studentCats.split(',');	
		
		var paymentOptions = $rootScope.currentUser.settings['Payment Options'];
		$scope.paymentOptions = paymentOptions.split(',');	
		
		var relationships = $rootScope.currentUser.settings['Guardian Relationships'];
		$scope.relationships = relationships.split(',');	
		
		var maritalStatuses = $rootScope.currentUser.settings['Marital Statuses'];
		$scope.maritalStatuses = maritalStatuses.split(',');
		
		var titles = $rootScope.currentUser.settings['Titles'];
		$scope.titles = titles.split(',');
		
		var medicalConditions = $rootScope.currentUser.settings['Medical Conditions'];
		medicalConditions = medicalConditions.split(',');
		
		// map medicalConditions to an object to hold user entry fields
		$scope.medicalConditions = medicalConditions.reduce(function(sum,item){
			sum.push({
				'medical_condition' : item,
				'age': '',
				'comments' :''
			});
			return sum;
		}, []);
		
		// get transport routes
		apiService.getTansportRoutes({}, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
				$scope.transportRoutes = result.data;
			}
			
		}, function(){});
		
		
	}
	$scope.initializeController();
	
	var getFeeItems = function()
	{
		// get fee items
		apiService.getFeeItems({}, function(response){
			var result = angular.fromJson(response);
			
			if( result.response == 'success')
			{
			
				// set the required fee items
				// format returned fee items for our needs
				$scope.feeItems = formatFeeItems(result.data.required_items);
				
				// store unfiltered required fee items				
				$scope.allFeeItems = $scope.feeItems;
				
				// remove any items that do not apply to this students class category
				$scope.feeItems = filterFeeItems($scope.allFeeItems);				
				//console.log($scope.feeItems);
				
				
				// set the selected fee items based on what fee items are set for student
				$scope.feeItemSelection = setSelectedFeeItems($scope.feeItems);
				//console.log($scope.feeItemSelection);
				
				
				// repeat for optional fees
				// convert the classCatsRestriction to array for future filtering
				$scope.optFeeItems = formatFeeItems(result.data.optional_items);
				
				// store unfiltered required fee items				
				$scope.allOptFeeItems = $scope.optFeeItems;
				
				// remove any items that do not apply to this students class category
				$scope.optFeeItems = filterFeeItems($scope.allOptFeeItems);				
				
				// set the selected fee items based on what fee items are set for student
				$scope.optFeeItemSelection = setSelectedFeeItems($scope.optFeeItems);
				
				//console.log($scope.optFeeItemSelection);
				
				$scope.initLoad  = false;
				
			}
			
		}, function(){});
	}

	$scope.getTabContent = function(tab)
	{
		if( !$scope.studentForm.$pristine )
		{
			var dlg = $dialogs.confirm('Changes Where Made','You have made changes to the data on this page. Did you want to save these changes?', {size:'sm'});
			
			dlg.result.then(function(btn){
				 // save the form
				 $scope.save($scope.studentForm, tab);
				 
			},function(btn){
				// revert the changes and move on
				$scope.student = angular.copy(originalData);
				$scope.studentForm.$setPristine();
				goToTab(tab);
			});
		}
		else
		{
			goToTab(tab);
		}
		
	}
	
	var goToTab = function(tab)
	{
		if( tab == 'Fees' )
		{
			// go get fee data
			$scope.loading = true;		
			$scope.currentFeeTab = "Fee Summary";
			apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
		}
		else if( tab == 'Exams' )
		{
			// get data for filters
			$scope.loading = true;	
			
			$scope.filters.class = $scope.student.current_class;
			$scope.filters.class_cat_id = $scope.student.class_cat_id; // set the students current class category as default
			$scope.filters.class_id = $scope.student.class_id; 
	
			// filter the classes based on class category
			$scope.classes = $rootScope.allClasses.filter( function(item){
				if( item.class_cat_id == $scope.filters.class_cat_id )
				{			
					return item;
				}
			});			

			// get terms
			if( $rootScope.terms === undefined )
			{
				apiService.getTerms(undefined, setTerms, function(){});
			}
			else
			{
				$scope.terms = $rootScope.terms;
				var currentTerm = $scope.terms.filter(function(item){
					if( item.current_term ) return item;
				})[0];
				$scope.filters.term_id = currentTerm.term_id;
				$scope.getExams();
			}
			
			
			// get exam types			
			if( $rootScope.examTypes === undefined )
			{
				apiService.getExamTypes($scope.student.class_cat_id, function(response){
					var result = angular.fromJson(response);				
					if( result.response == 'success'){ $scope.examTypes = result.data;	$rootScope.examTypes = result.data;}			
				}, function(){});
			}
			else $scope.examTypes = $rootScope.examTypes;
			
		}
		else if( tab == 'Report Cards' )
		{
			// get data for filters
			$scope.loading = true;	
			
			console.log($scope.student);
			$scope.filters.class = $scope.student.current_class;
			$scope.filters.class_cat_id = $scope.student.class_cat_id; // set the students current class category as default
			$scope.filters.class_id = $scope.student.class_id; 
			
			// filter the classes based on class category
			$scope.classes = $rootScope.allClasses.filter( function(item){
				if( item.class_cat_id == $scope.filters.class_cat_id )
				{			
					return item;
				}
			});	
			
			// get terms
			if( $rootScope.terms === undefined )
			{
				apiService.getTerms(undefined, setTerms, function(){});
			}
			else
			{
				$scope.terms = $rootScope.terms;
				var currentTerm = $scope.terms.filter(function(item){
					if( item.current_term ) return item;
				})[0];
				$scope.filters.term_id = currentTerm.term_id;
			}
			
			

			$scope.getStudentReportCards();			

			
		}
		$scope.currentTab = tab;
	}
	
	var setTerms  = function(response,status)
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
			
			$scope.getExams();
		}			
				
	}
	
	var initDataGrid = function(settings)
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
				order: settings.sortOrder,
				filter: false,
				info: false,
				sorting:[],
				scrollY:'200px',
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
					lengthMenu: "Display _MENU_",
					emptyTable: settings.noResultsTxt
				},
			} );
	}
	
	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');  
	}; // end cancel
	
	/*
	$scope.toggleNewFeeItems = function(newStudents)
	{
		$scope.show_new_student = newStudents;
		$scope.feeItems = filterFeeItems($scope.allFeeItems);
	};
	*/
	
	$scope.$watch('student.current_class', function(newVal, oldVal){
		if( newVal == oldVal) return;

		// update class fields for student
		$scope.student.class_id = $scope.student.current_class.class_id;
		$scope.student.class_name = $scope.student.current_class.class_name;
		$scope.student.class_cat_id = $scope.student.current_class.class_cat_id;
		if( $scope.allFeeItems !== undefined )
		{
			$scope.feeItems = filterFeeItems($scope.allFeeItems);	
			$scope.optFeeItems = filterFeeItems($scope.allOptFeeItems);	
		}
	});
	
	$scope.$watch('uploader.queue[0]', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		$scope.studentForm.$setDirty();
	});
	
	
	$scope.$watch('student.admission_date', function(newVal, oldVal){
		// need to watch the uploaded and manually set form to dirty if changed
		if( newVal === undefined) return;
		if( !$scope.initLoad ) $scope.studentForm.$setDirty();
	});
	
	
	
	/************************************* Guardian Function ***********************************************/
	$scope.addGuardian = function()
	{
		// show small dialog with add form
		var data = {student_id: $scope.student.student_id};
		var dlg = $dialogs.create('addParent.html','addParentCtrl',data,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(parent){
			
			//console.log(parent);
			$scope.student.guardians.push(parent);
			
		},function(){
			
		});
	}
	
	$scope.editGuardian = function(item)
	{
		// show small dialog with edit form
		var data = {
			student_id: $scope.student.student_id,
			guardian: item,
			action: 'edit'
		};
		var dlg = $dialogs.create('addParent.html','addParentCtrl',data,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(guardian){
			
			// find guardian and update
			console.log(guardian);
			angular.forEach( $scope.student.guardians, function(item,key){
				if( item.guardian_id == guardian.guardian_id)
				{
					console.log($scope.student.guardians[key]);
					$scope.student.guardians[key] = guardian;
					console.log($scope.student.guardians[key]);
				}
			});
			
		},function(){
			
		});
		
	}
	
	$scope.deleteGuardian = function(student_id,item,index)
	{

		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete <b>' + item.parent_full_name + '</b> as a parent/guardian of ' + $scope.student.student_name + '? <br><br><b><i>(THIS CAN NOT BE UNDONE)</i></b>',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deleteGuardian(student_id+'/'+item.guardian_id, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					// remove row
					$scope.student.guardians.splice(params.index,1);
				
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, apiError,{index:index});

		});
		
	}
	
	$scope.sendMessage = function(item)
	{
		// show small dialog with message form
	}
	
	/************************************* Medical History Function ***********************************************/
	
	$scope.addMedicalHistory = function()
	{
		// show small dialog with add form
		var data = {
			student_id: $scope.student.student_id,
			medicalHistory: $scope.student.medical_history,
			action: 'add'
		};
		var dlg = $dialogs.create('addMedicalHistory.html','addMedicalHistoryCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(medicalHistory){
			
			angular.forEach(medicalHistory, function(item,key){
				$scope.student.medical_history.push(item);
			});
			
		},function(){
			
		});
	}
	
	$scope.editMedical = function(item)
	{
		// show small dialog with add form
		var data = {
			student_id: $scope.student.student_id,
			medicalCondition: item,
			action: 'edit'
		};
		var dlg = $dialogs.create('updateMedicalCondition.html','updateMedicalConditionCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(medicalCondition){
			
			// find medical condition and update
			angular.forEach( $scope.student.medical_history, function(item,key){
				if( item.medical_id == medicalCondition.medical_id ) $scope.student.medical_history[key] = medicalCondition;
			});
			
		},function(){
			
		});
	}
	
	$scope.deleteMedical = function(item,index)
	{
		// show small dialog with add form
		var dlg = $dialogs.confirm('Please Confirm','Are you sure you want to delete <b>' + item.illness_condition + '</b> as a medical condition for this student? <br><br><b><i>(THIS CAN NOT BE UNDONE)</i></b>',{size:'sm'});
		dlg.result.then(function(btn){
			apiService.deleteMedicalCondition(item.medical_id, function(response,status,params){
				var result = angular.fromJson(response);
				if( result.response == 'success')
				{
					// remove row
					$scope.student.medical_history.splice(params.index,1);
	
				}
				else
				{
					$scope.error = true;
					$scope.errMsg = result.data;
				}
				
			}, apiError,{index:index});

		});
	}
	
	/************************************* Fees Function ***********************************************/
	
	$scope.$watch('student.payment_method', function(newVal, oldVal){
		if( newVal == oldVal) return;
		
		// want to set all selected fee item payment methods to this value
		
		
	});
	
	$scope.$watch('student.transport_route', function(newVal, oldVal){
		if( newVal == oldVal) return;
		//console.log(newVal);
		// use the amount and put it into the input box
		angular.forEach($scope.optFeeItemSelection, function(feeItem,key){
			if( feeItem.fee_item == 'Transport') feeItem.amount = newVal.amount;
		});
	});
	
	$scope.getFeeTabContent = function(tab)
	{
		$scope.currentFeeTab = tab;
		if( tab == 'Fee Summary' )
		{
			setTimeout(initFeesDataGrid,50);
		}
		else if( tab == 'Invoices' )
		{
			$scope.loading = true;			
			// TO DO: ability to send invoice canceled status
			apiService.getStudentInvoices($scope.student.student_id, loadInvoices, apiError);
		}
		else if( tab == 'Payments Received' )
		{
			$scope.loading = true;			
			apiService.getStudentPayments($scope.student.student_id, loadPayments, apiError);
		}
	}
	
	var loadFeeBalance = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);
				
		if( result.response == 'success') 
		{
			if( result.nodata === undefined )
			{
				$scope.feeSummary = angular.copy(result.data.fee_summary);
				$scope.fees = angular.copy(result.data.fees);
				$scope.nofeeSummary = false;
			}
			else
			{
				$scope.feeSummary = [];
				$scope.fees = [];
				$scope.nofeeSummary = true;
			}
			
			if( $scope.currentFeeTab == 'Fee Summary' )	setTimeout(initFeesDataGrid,50);
		}
	}
	
	var loadInvoices = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);
				
		if( result.response == 'success') 
		{
			$scope.invoices = ( result.nodata ? {} : angular.copy(result.data) );		
			
			if( $scope.currentFeeTab == 'Invoices' )	setTimeout(initInvoicesDataGrid,10);
		}
	}
	
	$scope.addInvoice = function()
	{
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/fees/invoiceForm.html','invoiceFormCtrl',{selectedStudent:$scope.student},{size: 'md',backdrop:'static'});
		dlg.result.then(function(invoice){
			// update invoices
			if( $scope.dataGrid !== undefined )
			{
				$scope.dataGrid.destroy();
				$scope.dataGrid = undefined;
			}
			apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
			apiService.getStudentInvoices($scope.student.student_id, loadInvoices, apiError);
		},function(){
			if(angular.equals($scope.invoice,''))
				$scope.errMsg = 'You did not enter an invoice!';
		});
	}
	
	$scope.viewInvoice = function(item)
	{
		item.student_name = $scope.student.student_name;		
		
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/fees/invoiceDetails.html','invoiceDetailsCtrl',item,{size: 'md',backdrop:'static'});
		dlg.result.then(function(invoice){
			// update invoices
			if( $scope.dataGrid !== undefined )
			{
				$scope.dataGrid.destroy();
				$scope.dataGrid = undefined;
			}
			apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
			apiService.getStudentInvoices($scope.student.student_id, loadInvoices, apiError);
			
		},function(){
			// update invoices
			if( $scope.dataGrid !== undefined )
			{
				$scope.dataGrid.destroy();
				$scope.dataGrid = undefined;
			}
			apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
			apiService.getStudentInvoices($scope.student.student_id, loadInvoices, apiError);
		});
	
	}
	
	var loadPayments = function(response,status)
	{
		$scope.loading = false;		
		var result = angular.fromJson(response);
				
		if( result.response == 'success') 
		{
			var payments = ( result.nodata ? [] : angular.copy(result.data) );
			
			$scope.payments = payments.map(function(item){
				item.replacement = ( item.replacement_payment ? 'Yes' : 'No');
				item.reverse = ( item.reversed ? 'Yes' : 'No');
				item.receipt_number = $rootScope.zeroPad(item.payment_id,5);
				return item;
			});
			
			if( $scope.currentFeeTab == 'Payments Received' )	setTimeout(initPaymentsDataGrid,10);
		}
	}
	
	

	var initFeesDataGrid = function() 
	{
		var settings = {
			sortOrder: [5,'asc'],
			noResultsTxt: "This student has not been invoiced."
		}
		initDataGrid(settings);
	}
	
	var initInvoicesDataGrid = function() 
	{
		var settings = {
			sortOrder: [1,'desc'],
			noResultsTxt: "No invoices found."
		}
		initDataGrid(settings);
	}
	
	var initPaymentsDataGrid = function() 
	{
		var settings = {
			sortOrder: [2,'desc'],
			noResultsTxt: "No payments found."
		}
		initDataGrid(settings);
	}
	
	$scope.addPayment = function()
	{
		// open dialog
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/fees/paymentForm.html','paymentFormCtrl',{selectedStudent:$scope.student},{size: 'lg',backdrop:'static'});
		dlg.result.then(function(payment){
			// update payments
			if( $scope.dataGrid !== undefined )
			{
				$scope.dataGrid.destroy();
				$scope.dataGrid = undefined;
			}
			apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
			apiService.getStudentPayments($scope.student.student_id, loadPayments, apiError);
		},function(){
			if(angular.equals($scope.payment,''))
				$scope.errMsg = 'You did not enter a payment!';
		});
	}
	
	$scope.getReceipt = function(payment)
	{
		var data = {
			student: $scope.student,
			payment: payment,
			feeItems: $scope.feeItems.concat($scope.optFeeItems)
		}
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/fees/receipt.html','receiptCtrl',data,{size: 'md',backdrop:'static'});
	}
	
	$scope.viewPayment = function(payment)
	{
		payment.student_id = $scope.student.student_id;
		payment.student_name = $scope.student.student_name;
		$rootScope.modalLoading = true;
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/fees/paymentDetails.html','paymentDetailsCtrl',payment,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(payment){
			// update invoices
			if( $scope.dataGrid !== undefined )
			{
				//$scope.dataGrid.destroy();
				$scope.dataGrid.clear();
			}
			apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
			apiService.getStudentPayments($scope.student.student_id, loadInvoices, apiError);
			
		},function(){
			// update invoices
			if( $scope.dataGrid !== undefined )
			{
				//$scope.dataGrid.destroy();
				$scope.dataGrid.clear();
			}
			apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
			apiService.getStudentPayments($scope.student.student_id, loadInvoices, apiError);
		});
		
	}
	
	var formatFeeItems = function(feeItems)
	{
		// convert the classCatsRestriction to array for future filtering
		return feeItems.map(function(item){
			// format the class restrictions into any array
			if( item.class_cats_restriction !== null && item.class_cats_restriction != '{}' )
			{
				var classCatsRestriction = (item.class_cats_restriction).slice(1, -1);
				item.class_cats_restriction = classCatsRestriction.split(',');
			}
			item.amount = undefined;
			item.payment_method = undefined;
			
			return item;
		});
	}
	
	var setSelectedFeeItems = function(feeItems)
	{
		//console.log(feeItems);
		return feeItems.filter(function(item){
			return $scope.student.fee_items.filter(function(a){
				//console.log(a.fee_item_id + ' ' + item.fee_item_id );
				if( a.fee_item_id == item.fee_item_id && a.active )
				{
					item.amount = a.amount;
					item.payment_method = a.payment_method;
					item.student_fee_item_id = a.student_fee_item_id;
					item.payment_made = a.payment_made;
					return item;
				}
			})[0];
			
		});
	}
	
	var filterFeeItems = function(feesArray)
	{
		//console.log($scope.student.new_student);
		var feeItems = [];

		if( $scope.student.new_student )
		{
			// all fees apply to new students
			feeItems = feesArray;
		}
		else
		{
			// remove new student fees
			feeItems = feesArray.filter(function(item){
				if( !item.new_student_only ) return item;
			});
		}
		
		// now filter by selected class
		//console.log( $scope.student);
		if( $scope.student.current_class !== undefined )
		{
			feeItems = feeItems.filter(function(item){
				if( item.class_cats_restriction === null || item.class_cats_restriction == '{}' ) return item;
				else if( item.class_cats_restriction.indexOf(($scope.student.current_class.class_cat_id).toString()) > -1 ) return item;
			});
		}

		return feeItems;
	}
	
	$scope.toggleFeeItem = function(item,type) 
	{
		var selectionObj = ( type ==  'optional'  ? $scope.optFeeItemSelection : $scope.feeItemSelection);
		
		var id = selectionObj.indexOf(item);

		// is currently selected
		if (id > -1) {
		
			if( type == 'optional' && item.made_payment > 0 )
			{
				var dlg = $dialog.confirm('Payment Made for Current Term','This student has paid for this lesson for the current term, therefore they will be removed from this optional lesson for all remaining terms. Do you wish to continue?', {size:'sm'});
				dlg.result.then(function(btn){
					selectionObj.splice(id, 1);
					$scope.studentForm.$setDirty();
				});
			}
			else
			{
				selectionObj.splice(id, 1);
				$scope.studentForm.$setDirty();
			}
			
			// clear out fields
			//item.amount = undefined;
			//item.payment_method = undefined;
			if( item.fee_item == 'Transport' ) $scope.showTransport = false;
		}

		// is newly selected
		else {
		
			// check the students fee items, was this set previously?
			var previousItem = $scope.student.fee_items.filter(function(a){ 
				if( a.fee_item_id == item.fee_item_id  ) return item;
			})[0];
			
			if( previousItem !== undefined )
			{
				item.amount = previousItem.amount;
				item.payment_method = previousItem.payment_method;
				item.student_fee_item_id = previousItem.student_fee_item_id;
				item.payment_made = previousItem.payment_made;
			}
			else
			{
				// set value and payment method
				item.amount = item.default_amount;
				item.payment_method = $scope.student.payment_method;
			}
		
			selectionObj.push(item);
			$scope.studentForm.$setDirty();
			if( item.fee_item == 'Transport' ) $scope.showTransport = true;
		}
	};
	
	$scope.addFeeItem = function()
	{
		// open dialog
		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/fees/feeItemForm.html','feeItemFormCtrl',undefined,{size: 'md',backdrop:'static'});
		dlg.result.then(function(){
			// update fee items
			getFeeItems();
		},function(){
				
		});
		
	
	}
	/************************************* Exam Functions ***********************************************/
	$scope.$watch('filters.class_cat_id', function(newVal, oldVal){
		if( newVal == oldVal) return;
		
		// filter the classes
		$scope.classes = $rootScope.allClasses.filter( function(item){
			if( item.class_cat_id == newVal ) return item;
		});
	});
	
	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;
		
		$scope.filters.class_id = newVal.class_id;

		apiService.getExamTypes(newVal.class_cat_id, function(response){
			var result = angular.fromJson(response);				
			if( result.response == 'success'){ $scope.examTypes = result.data;}			
		}, apiError);
	});
		
	$scope.getExams = function()
	{
		// /:student_id/:class/:term/:type
		$scope.examMarks = {};
		$scope.marksNotFound = false;
		
		if( $scope.dataGrid !== undefined )
		{
			$scope.dataGrid.destroy();
			$scope.dataGrid = undefined;
		}

		var request = $scope.student.student_id + '/' + $scope.filters.class_id + '/' + $scope.filters.term_id;
		if( $scope.filters.exam_type_id != "" && $scope.filters.exam_type_id !== undefined ) request += '/' + $scope.filters.exam_type_id;
		apiService.getStudentExamMarks(request, loadMarks, apiError);
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
				$scope.rawExamMarks = result.data;
				
				$scope.examMarks = {};
				
				// get unique exam subjects
				$scope.examMarks.subjects = result.data.reduce(function(sum,item){
					if( sum.indexOf(item.subject_name) === -1 ) sum.push(item.subject_name);
					return sum;
				}, []);
				//console.log($scope.examMarks.subjects);
				
				// group the marks by exam types
				$scope.examMarks.types = [];
				var lastExamType = '';
				var marks = [];
				var i = 0;
				angular.forEach(result.data, function(item,key){
					
					if( item.exam_type != lastExamType )
					{
						// changing to new exam type, store the complied marks array
						if( i > 0 ) $scope.examMarks.types[(i-1)].marks = marks;
						
						$scope.examMarks.types.push(
							{
								exam_type: item.exam_type,
								marks: []
							}
						);
						
						// init marks array for this exam type
						marks = [];
						i++;

					}
					marks.push({
						mark:item.mark,
						grade_weight: item.grade_weight
					});
					
					lastExamType = item.exam_type;
					
					
				});
				$scope.examMarks.types[(i-1)].marks = marks;
				
			}
		}
		else
		{
			$scope.marksNotFound = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.addExamMarks = function()
	{
		var data = {
			student_id : $scope.student.student_id,
			classes: $scope.classes,
			terms: $scope.terms,
			examTypes: $scope.examTypes,
			filters: $scope.filters
		}

		var dlg = $dialogs.create('addStudentExamMarks.html','addStudentExamMarksCtrl',data,{size: 'md',backdrop:'static'});
		dlg.result.then(function(examMarks){
			$scope.getExams();
		},function(){
			
		});
	}
	
	$scope.importExamMarks = function()
	{
		$rootScope.wipNotice();
	}
	
	$scope.exportData = function()
	{
		$rootScope.wipNotice();
	}
	
	/************************************* Report Card Functions ***********************************************/
	$scope.getStudentReportCards = function()
	{		
		if( $scope.dataGrid !== undefined )
		{
			$scope.dataGrid.clear();
			//$scope.dataGrid = undefined;
		}
		$scope.reportsNotFound = false;
		apiService.getStudentReportCards($scope.student.student_id, loadReportCards, apiError);
	}
	
	var loadReportCards = function(response,status)
	{
		$scope.loading = false;
		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( result.nodata )
			{
				$scope.reportsNotFound = true;
				$scope.errMsg = "There are currently no report cards entered for this student.";
			}
			else
			{
				
				$scope.rawReportCards = result.data;
					
				$scope.reportCards = {};
				
				// get unique terms
				$scope.reportCards.terms = $scope.rawReportCards.reduce(function(sum,item){
					if( sum.indexOf(item.term_name) === -1 ) sum.push(item.term_name);
					return sum;
				}, []);
				
				
				// group the reports by class
				$scope.reportCards.classes = [];
				var lastClass = '';
				var lastTerm = '';
				var reports = {};
				var i = 0;
				angular.forEach($scope.rawReportCards, function(item,key){
					
					if( item.class_name != lastClass )
					{
						// changing to new class, store the report
						if( i > 0 ) $scope.reportCards.classes[(i-1)].reports = reports;
						
						$scope.reportCards.classes.push(
							{
								class_name: item.class_name,
								class_id: item.class_id,
								class_cat_id: item.class_cat_id,
								report_card_type: item.report_card_type,
								term_id: item.term_id,
								year: item.year
							}
						);
						
						reports = {};
						i++;

					}
					reports[item.term_name] = item.report_data;
					
					lastClass = item.class_name;
					lastTerm = item.term_name;
					
				});
				$scope.reportCards.classes[(i-1)].reports = reports;
				console.log($scope.reportCards);
			}
			
		}
		else
		{
			$scope.reportsNotFound = true;
			$scope.errMsg = result.data;
		}
	}
	
	$scope.addReportCard = function()
	{
		var data = {
			student : $scope.student,
			classes: $scope.classes,
			terms: $scope.terms,
			filters: $scope.filters,
			adding: true
		}

		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/exams/reportCard.html','reportCardCtrl',data,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(examMarks){
			$scope.getStudentReportCards();
		},function(){
			
		});
	}
	
	$scope.getReportCard = function(item, term_name, reportData)
	{
		var data = {
			student : $scope.student,
			class_name : item.class_name,
			class_id : item.class_id,			
			term_id: item.term_id,
			term_name : term_name,
			year: item.year,
			report_card_type: item.report_card_type,
			reportData: reportData,
			adding: false,
			filters:{
				term:{
					term_name:term_name,
					term_id: item.term_id,
				},
				class:{
					class_id: item.class_id,
					class_cat_id: item.class_cat_id
				}
			}
		}

		var domain = window.location.host;
		var dlg = $dialogs.create('http://' + domain + '/app/exams/reportCard.html','reportCardCtrl',data,{size: 'lg',backdrop:'static'});
		dlg.result.then(function(examMarks){
			$scope.getStudentReportCards();
		},function(){
			$scope.getStudentReportCards();
		});
	}
	
	
	/************************************* Update Function ***********************************************/
	$scope.save = function(theForm, tab)
	{
		if( !theForm.$invalid )
		{
			// going to only send data that is on the current tab		
			if( $scope.currentTab == 'Details' )
			{
				if( uploader.queue[0] !== undefined )
				{
					// need a unique filename
					uploader.queue[0].file.name = $scope.student.student_id + "_" + uploader.queue[0].file.name;
					uploader.uploadAll();
				}
				
				//postData.feeItems = $scope.feeItemSelection;
				console.log($scope.student.admission_date);
				
				var postData = {
					student_id : $scope.student.student_id,
					user_id : $rootScope.currentUser.user_id,
					details : {
						student_category : $scope.student.student_category,
						first_name : $scope.student.first_name,
						middle_name : $scope.student.middle_name,
						last_name : $scope.student.last_name,
						gender : $scope.student.gender,
						dob: $scope.student.dob,
						nationality : $scope.student.nationality,
						current_class : $scope.student.class_id,
						update_class : ( originalData.class_id != $scope.student.class_id ? true : false),
						previous_class :  originalData.class_id,
						student_image : ( uploader.queue[0] !== undefined ? uploader.queue[0].file.name : null),
						active : ( $scope.student.active ? 't' : 'f' ),
						admission_date: moment($scope.student.admission_date).format('YYYY-MM-DD'),
						admission_number: $scope.student.admission_number,
						new_student : ( $scope.student.new_student ? 't' : 'f' ),
					}
				}
			}
			else if ( $scope.currentTab == 'Family' )
			{
				var postData = {
					student_id : $scope.student.student_id,
					user_id : $rootScope.currentUser.user_id,
					family : {
						marial_status_parents : $scope.student.marial_status_parents,
						adopted : ( $scope.student.adopted ? 't' : 'f' ),
						adopted_age : $scope.student.adopted_age,
						marital_separation_age : $scope.student.marital_separation_age,
						adoption_aware : ( $scope.student.adoption_aware ? 't' : 'f'),
						emergency_name: $scope.student.emergency_name,
						emergency_relationship : $scope.student.emergency_relationship,
						emergency_telephone : $scope.student.emergency_telephone,
						pick_up_drop_off_individual : $scope.student.pick_up_drop_off_individual
					}
				}
			}
			else if ( $scope.currentTab == 'Medical History' )
			{

				var postData = {
					student_id : $scope.student.student_id,
					user_id : $rootScope.currentUser.user_id,
					medical : {
						has_medical_conditions : ($scope.student.has_medical_conditions || $scope.student.other_medical_conditions ? 't' : 'f' ),
						hospitalized: ( $scope.student.hospitalized ? 't' : 'f' ),
						hospitalized_description: ( $scope.student.hospitalized ? $scope.student.hospitalized_description : null),
						current_medical_treatment: ( $scope.student.current_medical_treatment ? 't' : 'f' ),
						current_medical_treatment_description: ( $scope.student.current_medical_treatment ? $scope.student.current_medical_treatment_description : null),
						other_medical_conditions: ($scope.student.other_medical_conditions ? 't' : 'f'),
						other_medical_conditions_description: ($scope.student.other_medical_conditions ? $scope.student.other_medical_conditions_description : null)
					}
				}

			}
			else if( $scope.currentTab == 'Fees' )
			{

				var postData = {
					student_id : $scope.student.student_id,
					user_id : $rootScope.currentUser.user_id,
					fees : {
						payment_method : $scope.student.payment_method,
						installment_option: $scope.student.installment_option_id,
						feeItems : $scope.feeItemSelection,
						optFeeItems : $scope.optFeeItemSelection
					}
				}
			}
			//console.log(postData);
			apiService.updateStudent(postData, createCompleted, apiError, {tab:tab});
		}
	}
	
	var createCompleted = function ( response, status, params ) 
	{

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
			if( uploader.queue[0] !== undefined )
			{
				$scope.student.student_image = uploader.queue[0].file.name;
			}
			
			// saved, update the originalData
			originalData = angular.copy($scope.student);
			$scope.studentForm.$setPristine();
			
			// if moving tabs, continue
			if( params.tab !== undefined ) goToTab(params.tab);
			
			if( $scope.currentTab == 'Fees' )
			{
				// update the fee data
				apiService.getStudentBalance($scope.student.student_id, loadFeeBalance, apiError);
			}

			// refresh the main student list
			$rootScope.$emit('studentAdded', {'msg' : 'Student was updated.', 'clear' : true});
			
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
	}
	
	var uploader = $scope.uploader = new FileUploader({
            url: 'upload.php',
			formData : [{
				'dir': 'students'
			}]
    });
	
} ])
.controller('addParentCtrl',['$scope','$rootScope','$uibModalInstance','apiService','data',
function($scope,$rootScope,$uibModalInstance,apiService,data){
		//-- Variables --//
		$scope.student_id = data.student_id || null;
		$scope.guardian =  data.guardian || {};
		if( $scope.guardian.login === undefined ) $scope.guardian.login = {};
		$scope.guardian.login.login_active = 'false';
		$scope.edit = ( data.action == 'edit' ? true : false );
		
		var relationships = $rootScope.currentUser.settings['Guardian Relationships'];
		$scope.relationships = relationships.split(',');
		
		var maritalStatuses = $rootScope.currentUser.settings['Marital Statuses'];
		$scope.maritalStatuses = maritalStatuses.split(',');
		
		var titles = $rootScope.currentUser.settings['Titles'];
		$scope.titles = titles.split(',');
				
		$scope.existingGuardian = {};
		$scope.uniqueUsername = undefined;
		$scope.uniqueIdNumber = undefined;

		//-- Methods --//
		
		$scope.initializeController = function()
		{
			if( !$scope.edit )
			{
				// get list of existing guardians
				apiService.getAllGuardians(true,function(response){
					var result = angular.fromJson( response );
					if( result.response == 'success' ) 	$scope.guardians = (result.nodata ? [] : result.data);
				},apiError);
			}
			else
			{
				$scope.getStudents( $scope.guardian.guardian_id );
				$scope.checkMISLogin( $scope.guardian.id_number );
			}
		}
		setTimeout( $scope.initializeController,10);
		
		$scope.getStudents = function( guardian_id )
		{
			apiService.getGuardiansChildren(guardian_id,function(response){
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					$scope.children = (result.nodata ? [] : result.data);
					// filter out current student
					$scope.children = $scope.children.filter(function(item){
						if ( item.student_id != $scope.student_id ) return item;
					});
				}
			},apiError);
		}
		
		$scope.checkMISLogin = function( idNumber )
		{
			// this will query the eduweb_mis database for a login associated with id_number
			apiService.getMISLogin(idNumber,function(response){
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					$scope.hasLogin = (result.nodata ? false : true);
					if( $scope.hasLogin )
					{
						$scope.guardian.login = result.data;
						$scope.guardian.login.login_active = String(result.data.login_active);
						// check if this parent is already associated with this student
						if( result.data.student_ids !== null )
						{
							var existingRecord = result.data.student_ids.indexOf( String($scope.student_id) );
							/*
							var existingRecord = result.data.student_ids.filter(function(item){
								if( item == $scope.student_id ) return item;
							});
							*/
						}
						
						$scope.guardian.login.exists = ( existingRecord > -1 ? true : false);
					}
					else
					{
						if( $scope.guardian.login === undefined ) $scope.guardian.login = {};
						$scope.guardian.login.login_active = 'false';
					}
					console.log($scope.guardian);
				}
			},apiError);
		}
		
		$scope.checkUsername = function( username )
		{
			// this will query the eduweb_mis database to check if username is unique
			if( !$scope.hasLogin )
			{
				apiService.checkUsername(username,function(response){
					var result = angular.fromJson( response );
					if( result.response == 'success' )
					{
						$scope.uniqueUsername = (result.nodata ? true : false);					
					}
				},apiError);
			}
		}
		
		$scope.checkIdNumber = function( username )
		{
			// this will query the guardians table to ensure id number is unique
			apiService.checkIdNumber(username,function(response){
				var result = angular.fromJson( response );
				if( result.response == 'success' )
				{
					$scope.uniqueIdNumber = (result.nodata ? true : false);					
				}
			},apiError);
		}
		
		
		$scope.$watch('existingGuardian.selected',function(newVal,oldVal){
			if( newVal == oldVal || newVal === undefined ) return;
			// populate form
			var relationship = angular.copy($scope.guardian.relationship);
			$scope.guardian = newVal;
			$scope.guardian.relationship = relationship; // get cleared, reset it previous selection
			$scope.getStudents( $scope.guardian.guardian_id );
		});
		
		$scope.$watch('guardian.id_number',function(newVal,oldVal){
			if( newVal == oldVal || newVal === undefined ) return;
			// populate form
			$scope.checkMISLogin(newVal);
			
			$scope.uniqueIdNumber = undefined;
			$scope.checkIdNumber(newVal);
		});	
	
		$scope.$watch('guardian.login.username',function(newVal,oldVal){
			if( newVal == oldVal || newVal === undefined ) return;
			// check if unique
			$scope.uniqueUsername = undefined;
			$scope.checkUsername(newVal);
		});			
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function(theForm)
		{
			console.log(theForm.$invalid);
			console.log($scope.uniqueUsername);
			console.log($scope.uniqueIdNumber);
			if( !theForm.$invalid && $scope.uniqueUsername !== false && $scope.uniqueIdNumber !== false )
			{
				if( $scope.edit ) 
				{
					$scope.update();
				}
				else{
					//console.log($scope.guardian);
					if( $scope.student_id )
					{
						var postData = {
							student_id: $scope.student_id,
							guardian_id: ($scope.existingGuardian.selected !== undefined ? $scope.existingGuardian.selected.guardian_id : undefined),
							guardian: $scope.guardian,
							user_id: $rootScope.currentUser.user_id
						}
						apiService.postGuardian(postData, createCompleted, apiError);
					}
					else
					{
						// adding a guardian for a new student, just return the data
						$scope.guardian.parent_full_name = $scope.guardian.first_name + ' ' + ($scope.guardian.middle_name || '') + ' ' + $scope.guardian.last_name;
						$uibModalInstance.close($scope.guardian);
					}
				}
			}
		}; // end save
		
		$scope.update = function()
		{
			//console.log($scope.guardian);
			var postData = {
				student_id: $scope.student_id,
				guardian: $scope.guardian,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.updateGuardian(postData, createCompleted, apiError);
			
			
		}; // end update
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				if( !$scope.edit ) $scope.guardian.guardian_id = result.data; // when adding, set guardian id returned from API
				$scope.guardian.parent_full_name = $scope.guardian.first_name + ' ' + ($scope.guardian.middle_name || '') + ' ' + $scope.guardian.last_name;
				$uibModalInstance.close($scope.guardian);
			}
			else
			{
				$scope.error = true;
				var msg = ( result.data.indexOf('"U_id_number"') > -1 ? 'The ID Number you entered already exists.' : result.data);
				$scope.errMsg = msg;
			}
		}
		
		var apiError = function(response,status)
		{
			var result = angular.fromJson( response );
			$scope.error = true;
			$scope.errMsg = result.data;
		}

		$scope.clearSelect = function($event) 
		{
			$event.stopPropagation(); 
			$scope.existingGuardian.selecte = undefined;
			$scope.guardian = {};
			$scope.childern = undefined;
		};
		
		
	
	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addParent.html',
			'<form name="parentForm" class="form-horizontal modalForm" novalidate role="form" ng-submit="save(parentForm)">' +
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> {{(edit ? \'Update\' : \'Add\')}} Parent/Guardian</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +				
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<!-- relationship -->' +
					'<div class="form-group">		' +
						'<label for="relationship" class="col-sm-2 control-label">Relationship</label>' +
						'<div class="col-sm-4">' +
							'<select name="relationship" ng-model="guardian.relationship" class="form-control">' +
								'<option value="">--select relationship--</option>' +
								'<option value="{{item}}" ng-repeat="item in relationships">{{item}}</option>' +
							'</select>' +
						'</div>	' +
					'</div>' +	
					'<!-- existing parent -->' +
					'<div class="form-group" ng-show="!edit">' +
						'<label for="guardian_id" class="col-sm-2 control-label">Use Existing Parent</label>' +
						'<div class="col-sm-6">' +
							'<ui-select ng-model="existingGuardian.selected" theme="select2" class="form-control" name="existingGuardian" > ' +
							  '<ui-select-match placeholder="Select or search a parent...">' +
								'<span>{{$select.selected.parent_full_name}}</span>' +
								'<button type="button" class="clear text-danger" ng-click="clearSelect($event)"><span class="glyphicon glyphicon-remove"></span></button>' +	  
							 ' </ui-select-match>' +
							  '<ui-select-choices repeat="item in guardians | filter: $select.search">' +
								'<span ng-bind-html="item.parent_full_name | highlight: $select.search"></span>' +
							 ' </ui-select-choices>' +
							'</ui-select>' +
							'<p class="help-block info-block pull-left"><i class="glyphicon glyphicon-info-sign"></i> If this student\'s parent is already entered, you can select them above. Or, leave above blank and enter a new parent below.</p>' +
						'</div>' +
					'</div>' +
					'<div ng-show="children.length > 0 " class="alert alert-warning">' +
						'<i class="glyphicon glyphicon-alert pull-left"></i> <strong>Any data that is changed below will also affect the following students who are also associated with this parent.</strong>' +
						'<div ng-show="children.length>0">' +
							'<ul>' +
								'<li ng-repeat="child in children">{{child.student_name}}</li>' +
							'</ul>' +
						'</div>' +
					'</div>' +
					'<div class="row">' +
					'<div class="col-sm-6">' +
						'<h3>Personal Info</h3>' +
						'<!-- last name -->' +
						'<div class="form-group" ng-class="{ \'has-error\' : (parentForm.$submitted || parentForm.last_name.$dirty ) && parentForm.last_name.$invalid && parentForm.last_name.$error.required }">' +
							'<label for="last_name" class="col-sm-3 control-label">Last Name</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="last_name" ng-model="guardian.last_name" class="form-control" required />' +
								'<p ng-show="(parentForm.$submitted || parentForm.last_name.$dirty ) && parentForm.last_name.$invalid && parentForm.last_name.$error.required" class="help-block"><i class="fa fa-exclamation-triangle"></i> Last Name is required.</p>' +
							'</div>' +
						'</div>' +
						'<!-- first name -->' +
						'<div class="form-group" ng-class="{ \'has-error\' : (parentForm.$submitted || parentForm.first_name.$dirty ) && parentForm.first_name.$invalid && parentForm.first_name.$error.required }">' +
							'<label for="first_name" class="col-sm-3 control-label">First Name</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="first_name" ng-model="guardian.first_name" class="form-control" required />' +
								'<p ng-show="(parentForm.$submitted || parentForm.first_name.$dirty ) && parentForm.first_name.$invalid && parentForm.first_name.$error.required" class="help-block"><i class="fa fa-exclamation-triangle"></i> First Name is required.</p>' +
							'</div>	' +
						'</div>' +
						'<!-- middle name -->' +
						'<div class="form-group">' +	
							'<label for="middle_name" class="col-sm-3 control-label">Middle Name</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="middle_name" ng-model="guardian.middle_name" class="form-control" />	' +
							'</div>	' +
						'</div>' +
						'<!-- name title -->' +
						'<div class="form-group" >' +	
							'<label for="title" class="col-sm-3 control-label">Title</label>' +
							'<div class="col-sm-9">' +
								'<select name="title" ng-model="guardian.title" class="form-control">' +
									'<option value="{{title}}" ng-repeat="title in titles">{{title}}</option>' +
								'</select>' +
							'</div>	' +
						'</div>	' +
						'<!-- id number -->' +
						'<div class="form-group" ng-class="{ \'has-error\' : (parentForm.$submitted || parentForm.id_number.$dirty ) && parentForm.id_number.$invalid && parentForm.id_number.$error.required }">' +
							'<label for="id_number" class="col-sm-3 control-label">ID Number</label>' +
							'<div class="col-sm-4 nopad-right">' +
								'<input type="text" name="id_number" ng-model="guardian.id_number" ng-model-options="{ debounce: 1000 }" class="form-control" required numeric-only />	' +
								'<p ng-show="(parentForm.$submitted || parentForm.id_number.$dirty ) && parentForm.id_number.$invalid && parentForm.id_number.$error.required" class="help-block"><i class="fa fa-exclamation-triangle"></i> ID Number is required.</p>' +
							'</div>' +
							'<div class="col-sm-5" ng-show="uniqueIdNumber===false">' +
								'<p class="form-control-static alert alert-danger"><i class="glyphicon glyphicon-remove pull-left"></i> Already exists.</p>' +	
							'</div>	' +
							'<div class="col-sm-2" ng-show="uniqueIdNumber===true">' +
								'<p class="form-control-static alert alert-success icon-only"><i class="glyphicon glyphicon-ok"></i></p>' +	
							'</div>	' +
						'</div>' +
						'<!-- address -->' +
						'<div class="form-group">' +
							'<label for="address" class="col-sm-3 control-label">Address</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="address" ng-model="guardian.address" class="form-control"  >	' +
							'</div>' +
						'</div>' +
						
						'<!-- phone number -->' +
						'<div class="form-group" ng-class="{ \'has-error\' : (parentForm.$submitted || parentForm.telephone.$dirty ) && parentForm.telephone.$invalid && parentForm.telephone.$error.required }">' +
							'<label for="telephone" class="col-sm-3 control-label">Telephone</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="telephone" ng-model="guardian.telephone" class="form-control" required numeric-only />	' +
								'<p ng-show="(parentForm.$submitted || parentForm.telephone.$dirty ) && parentForm.telephone.$invalid && parentForm.telephone.$error.required" class="help-block"><i class="fa fa-exclamation-triangle"></i> Telephone number is required.</p>' +
							'</div>	' +
						'</div>' +
						'<!-- email -->' +
						'<div class="form-group" ng-class="{ \'has-error\' : (parentForm.$submitted || parentForm.email.$dirty ) && parentForm.email.$invalid && parentForm.email.$error.required }">' +
							'<label for="email" class="col-sm-3 control-label">Email</label>' +
							'<div class="col-sm-9">' +
								'<input type="email" name="email" ng-model="guardian.email" class="form-control" required />	' +
								'<p ng-show="(parentForm.$submitted || parentForm.email.$dirty ) && parentForm.email.$invalid && parentForm.email.$error.required" class="help-block"><i class="fa fa-exclamation-triangle"></i> Email is required.</p>' +
								'<p ng-show="(parentForm.$submitted || parentForm.email.$dirty ) && parentForm.email.$invalid && parentForm.email.$error.email" class="help-block"><i class="fa fa-exclamation-triangle"></i> Invalid email</p>' +
							'</div>	' +
						'</div>' +
						'<!-- marital status -->' +
						'<div class="form-group">		' +
							'<label for="marital_status" class="col-sm-3 control-label">Marital Status</label>' +
							'<div class="col-sm-9">' +
								'<select name="marital_status" ng-model="guardian.marital_status" class="form-control">' +
									'<option value="">--select one--</option>' +
									'<option value="{{item}}" ng-repeat="item in maritalStatuses">{{item}}</option>' +
								'</select>' +
							'</div>	' +
						'</div>' +	
					'</div>' +
					'<div class="col-sm-6">' +
						'<h3>Employment Info</h3>' +
						'<!-- occupation -->' +
						'<div class="form-group">	' +	
							'<label for="occupation" class="col-sm-3 control-label">Occupation</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="occupation" ng-model="guardian.occupation" class="form-control"  >	' +
							'</div>	' +
						'</div>' +
						'<!-- employer -->' +
						'<div class="form-group">	' +	
							'<label for="employer" class="col-sm-3 control-label">Employer</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="employer" ng-model="guardian.employer" class="form-control"  >	' +
							'</div>	' +
						'</div>' +
						'<!-- employer address -->' +
						'<div class="form-group">	' +	
							'<label for="employer_address" class="col-sm-3 control-label">Employer Address</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="employer_address" ng-model="guardian.employer_address" class="form-control"  >	' +
							'</div>	' +
						'</div>' +
						'<!-- phone number -->' +
						'<div class="form-group">	' +	
							'<label for="work_phone" class="col-sm-3 control-label">Work Phone</label>' +
							'<div class="col-sm-9">' +
								'<input type="text" name="work_phone" ng-model="guardian.work_phone" class="form-control" numeric-only />	' +
							'</div>	' +
						'</div>' +
						'<!-- work_email -->' +
						'<div class="form-group">' +
							'<label for="work_email" class="col-sm-3 control-label">Work Email</label>' +
							'<div class="col-sm-9">' +
								'<input type="email" name="work_email" ng-model="guardian.work_email" class="form-control"  >	' +
							'</div>	' +
						'</div>' +
					
						'<h3>Parent Portal Login (optional)</h3>' +
						'<div ng-if="hasLogin">' +
							'<!-- username -->' +
							'<div class="form-group">' +
								'<label for="username" class="col-sm-3 control-label">Username</label>' +
								'<div class="col-sm-9">' +
									'<p class="form-control-static">{{guardian.login.username}}</p>' +			
								'</div>	' +
							'</div>' +
							'<!-- active -->' +
							'<div class="form-group">' +
								'<label for="login_active" class="col-sm-3 control-label">Login Active</label>' +
								'<div class="col-sm-9">' +
									'<label class="radio-inline">' +
									  '<input type="radio" name="login_active" ng-model="guardian.login.login_active" value="true" > Active' +
									'</label>' +
									'<label class="radio-inline">' +
									  '<input type="radio" name="login_active" ng-model="guardian.login.login_active" value="false" > In-active' +
									'</label>' +			
								'</div>	' +
							'</div>' +
						'</div>' +
						'<div ng-if="!hasLogin">' +						
							'<!-- username -->' +
							'<div class="form-group">' +
								'<label for="username" class="col-sm-3 control-label">Username</label>' +
								'<div class="col-sm-4 nopad-right">' +
									'<input type="text" name="username" ng-model="guardian.login.username" class="form-control" ng-model-options="{ debounce: 1000 }" >' +										
								'</div>	' +
								'<div class="col-sm-5" ng-show="uniqueUsername===false">' +
									'<span class="alert alert-danger"><i class="glyphicon glyphicon-remove"></i> Already taken.</span>' +	
								'</div>	' +
								'<div class="col-sm-2" ng-show="uniqueUsername===true">' +
									'<p class="form-control-static alert alert-success icon-only"><i class="glyphicon glyphicon-ok"></i></p>' +	
								'</div>	' +
							'</div>' +
							'<!-- password -->' +
							'<div class="form-group">' +
								'<label for="password" class="col-sm-3 control-label">Password</label>' +
								'<div class="col-sm-9">' +
									'<input type="text" name="password" ng-model="guardian.login.password" class="form-control"  >' +			
								'</div>	' +
							'</div>' +
							'<!-- active -->' +
							'<div class="form-group">' +
								'<label for="login_active" class="col-sm-3 control-label">Login Active</label>' +
								'<div class="col-sm-9">' +
									'<label class="radio-inline">' +
									  '<input type="radio" name="login_active" ng-model="guardian.login.login_active" value="true" > Active' +
									'</label>' +
									'<label class="radio-inline">' +
									  '<input type="radio" name="login_active" ng-model="guardian.login.login_active" value="false" > In-active' +
									'</label>' +			
								'</div>	' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>' +	
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="submit" class="btn btn-primary">{{(edit ? \'Update\' : \'Save\')}} </button>' +
			'</div>' +
			'</form>'
		);
}])
.controller('addMedicalHistoryCtrl',['$scope','$rootScope','$uibModalInstance','apiService','data',
function($scope,$rootScope,$uibModalInstance,apiService,data){
		
		//-- Variables --//
		
		$scope.fullMedicalHistory =  data.medicalHistory || {};
		
		var medicalConditions = $rootScope.currentUser.settings['Medical Conditions'];
		medicalConditions = medicalConditions.split(',');
		
		// build array of just the medical condition names for the student
		var medicalHistory = $scope.fullMedicalHistory.reduce(function(sum,item){
			sum.push(item.illness_condition);
			return sum;
		}, []);
		
		// map medicalConditions to an object to hold user entry fields
		// remove medical conditions that are already set for the student
		$scope.medicalConditions = medicalConditions.reduce(function(sum,item){
			if( medicalHistory.indexOf(item) === -1 )
			{
				sum.push({
					'illness_condition' : item,
					'age': '',
					'comments' :''
				});
			}
			return sum;
		}, []);


		//-- Methods --//
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			//console.log($scope.guardian);
			var postData = {
				student_id: data.student_id,
				medicalConditions: $scope.conditionSelection,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.postMedicalConditions(postData, createCompleted, apiError);
			
			
		}; // end save
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				// loop through results and add medical_id to each condition
				angular.forEach( result.data, function(item,key){
					$scope.conditionSelection[key].medical_id = item.medical_id;
					$scope.conditionSelection[key].date_medical_added = item.date_medical_added;
				});
				
				$uibModalInstance.close($scope.conditionSelection);
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
			$scope.errMsg = result.data;
		}
		
		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.save();
		};
		
		$scope.conditionSelection = [];
		$scope.toggleMedicalCondition = function(item) 
		{
		
			var id = $scope.conditionSelection.indexOf(item);

			// is currently selected
			if (id > -1) {
				$scope.conditionSelection.splice(id, 1);
			}

			// is newly selected
			else {
				$scope.conditionSelection.push(item);
			}
	};
	
	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addMedicalHistory.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Medical History</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="cargoDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<table class="display dataTable">' +
					'<thead>' +
						'<tr>' +
							'<th>Illness/Condition</th>' +
							'<th>Age</th>' +
							'<th>Explain</th>' +
						'</tr>' +
					'</thead>' +
					'<tbody>' +
						'<tr ng-class-odd="\'odd\'" ng-class-even="\'even\'" ng-repeat="item in medicalConditions track by $index">' +
							'<td width="150">' +
								'<label class="checkbox-inline">' +
								  '<input ' +
									'type="checkbox" ' +
									'name="conditions[]" ' +
									'value="{{item.illness_condition}}" ' +
									'ng-checked="conditionSelection.indexOf(item) > -1" ' +
									'ng-click="toggleMedicalCondition(item)" ' +
								  '> {{item.illness_condition}}' +
								'</label>' +
							'</td>' +
							'<td width="80">' +
								'<input type="text" class="form-control" name="medical_condition_age[]" ng-model="item.age" >' +
							'</td>' +
							'<td>' +
								'<input type="text" class="form-control" name="medical_condition_comments[]" ng-model="item.comments" >' +
							'</td>' +
						'</tr>' +
					'</tbody>' +
				'</table>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button ng-show="!edit" type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
			'</div>'
		);
}])
.controller('updateMedicalConditionCtrl',['$scope','$rootScope','$uibModalInstance','apiService','data',
function($scope,$rootScope,$uibModalInstance,apiService,data){
		
		//-- Variables --//
		
		$scope.medicalCondition =  data.medicalCondition || {};
		
		
		//-- Methods --//
		
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.save = function()
		{
			//console.log($scope.guardian);
			var postData = {
				student_id: data.student_id,
				medicalCondition: $scope.medicalCondition,
				user_id: $rootScope.currentUser.user_id
			}
			apiService.updateMedicalConditions(postData, createCompleted, apiError);
			
			
		}; // end save
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				// loop through results and add medical_id to each condition				
				$uibModalInstance.close($scope.medicalCondition);
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
			$scope.errMsg = result.data;
		}
		
		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.save();
		};
		
	
	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('updateMedicalCondition.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Update Medical Condition : {{medicalCondition.illness_condition}}</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<ng-form name="cargoDialog" class="form-horizontal modalForm" novalidate role="form">' +
					'<div class="row">' +
						'<div class="col-sm-12">' +
							'<div ng-show="error" class="alert alert-danger">' +
								'{{errMsg}}'+
							'</div>' +
							'<!-- Age -->' +
							'<div class="form-group">' +
								'<div class="col-sm-12"><label for="age">Age</label></div>' +
								'<div class="col-sm-3"><input type="text" name="age" ng-model="medicalCondition.age" class="form-control"  ></div>' +
							'</div>' +
							'<!-- Explain -->' +
							'<div class="form-group">' +
								'<div class="col-sm-12"><label for="comments">Explain</label></div>' +
								'<div class="col-sm-12"><textarea name="comments" rows="5" ng-model="medicalCondition.comments" class="form-control"></textarea></div>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</ng-form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button ng-show="!edit" type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
			'</div>'
		);
}])
.controller('addStudentExamMarksCtrl',['$scope','$rootScope','$uibModalInstance','apiService','data',
function($scope,$rootScope,$uibModalInstance,apiService,data){
		
		//-- Variables --//
		//console.log(data);
		$scope.student_id = data.student_id;
		$scope.filters = data.filters;
		$scope.classes = data.classes;
		$scope.terms = data.terms;
		$scope.examTypes = data.examTypes;
		
		//-- Methods --//
		$scope.cancel = function(){
			$uibModalInstance.dismiss('Canceled');
		}; // end cancel
		
		$scope.$watch('filters.class',function(newVal,oldVal){
			if( newVal == oldVal ) return;
			
			$scope.filters.class_id = newVal.class_id;

			apiService.getExamTypes(newVal.class_cat_id, function(response){
				var result = angular.fromJson(response);				
				if( result.response == 'success'){ $scope.examTypes = result.data;}			
			}, apiError);
		});
		
		$scope.getStudentExams = function( theForm )
		{

			if( !theForm.$invalid )
			{
			// /:student_id/:class/:term/:type
				$scope.examMarks = {};
				$scope.marksNotFound = false;
				$scope.currentFilters = angular.copy($scope.filters);
				
				var request = $scope.filters.class_id + '/' + $scope.filters.exam_type_id;
				apiService.getAllClassExams(request, function(response){
					$scope.loading = false;
					var result = angular.fromJson( response );
					if( result.response == 'success' )
					{
						if( result.nodata !== undefined )
						{
							$scope.examNotFound = true;
						}
						else
						{
							var subjects = result.data;
							
							// populate any already entered exam marks
							var request = $scope.student_id + '/' + $scope.filters.class_id + '/' + $scope.filters.term_id + '/' + $scope.filters.exam_type_id;
							apiService.getStudentExamMarks(request, function(response){
								$scope.loading = false;
								var result = angular.fromJson( response );
								if( result.response == 'success' )
								{
									$scope.marks = (result.nodata ? [] : result.data);
									
									if( $scope.marks.length > 0 )
									{
										$scope.subjects = subjects.map(function(item){
											var mark = $scope.marks.filter(function(item2){
												if( item2.subject_name == item.subject_name ) return item2;
											})[0];

											item.mark = (mark !== undefined ? mark.mark : undefined);
											return item;
										});
									}
									else
									{
										$scope.subjects = subjects;
									}
									
								}
							}, apiError);
						}
						
						
					}
				}, apiError);
			}
			
		}
		
		$scope.calculateParentSubject = function(parent_id)
		{
			if( parent_id !== undefined )
			{
				var children = [];
				var parent = null;
				angular.forEach($scope.subjects, function(item,key){
					// get marks for children subjects
					if( item.parent_subject_id == parent_id ) children.push(item);
					else if(item.subject_id == parent_id ) parent = item;
				});
				console.log(children);
				// add them up
				var numChildren = children.length;
				var total = children.reduce(function(sum,item){
					var mark = parseInt(item.mark) || 0;
					console.log(( mark / item.grade_weight) * 100);
					sum += ( mark / item.grade_weight) * 100;
					return sum;
				},0);
				console.log(total);
				parent.mark = Math.round( total / numChildren ) ;
			}
		}		
		
		$scope.save = function()
		{
			var examMarks = [];
			angular.forEach($scope.subjects, function(item,key){
				examMarks.push({
					student_id : $scope.student_id,
					class_sub_exam_id: item.class_sub_exam_id,
					term_id: $scope.currentFilters.term_id,
					mark: item.mark,
					parent_subject_id: item.parent_subject_id
				});
			});
			
			var data = {
				user_id: $rootScope.currentUser.user_id,
				exam_marks: examMarks
			}
			//console.log(data);
			apiService.addExamMarks(data,createCompleted,apiError);				
			
		}; // end save
		
		var createCompleted = function(response,status)
		{
			var result = angular.fromJson( response );
			if( result.response == 'success' )
			{
				// loop through results and add medical_id to each condition				
				$uibModalInstance.close($scope.subjects);
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
			$scope.errMsg = result.data;
		}
		
		$scope.hitEnter = function(evt){
			if( angular.equals(evt.keyCode,13) )
				$scope.save();
		};
		
		var apiError = function (response, status) 
		{
			var result = angular.fromJson( response );
			$scope.error = true;
			$scope.errMsg = result.data;
		}
	
	
	}]) // end controller(addCargoCtrl)
.run(['$templateCache',function($templateCache){
  		$templateCache.put('addStudentExamMarks.html',
			'<div class="modal-header dialog-header-form">'+
				'<h4 class="modal-title"><span class="glyphicon glyphicon-plus"></span> Add Exam Marks</h4>' +
			'</div>' +
			'<div class="modal-body cleafix">' +
				'<form name="examForm" class="form-horizontal modalForm" novalidate role="form">' +									
					'<div ng-show="error" class="alert alert-danger">' +
						'{{errMsg}}'+
					'</div>' +
					'<div class="row header">' +		
						'<div class="modalDataFilter col-sm-12 clearfix">	' +
							'<!-- Class -->' +
							'<div class="form-group">' +
								'<label for="class">Class</label>' +
								'<select name="class_id" class="form-control" ng-options="class.class_name for class in classes track by class.class_id" ng-model="filters.class" required>' +
									'<option value="">--select class--</option>' +
								'</select>' +
								'<p ng-show="examForm.class_id.$invalid && (examForm.class_id.$touched || examForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Class is required.</p>' +
							'</div>	' +
							'<!-- Term -->' +
							'<div class="form-group">' +
								'<label for="term">Term</label>	' +
								'<select name="term_id" class="form-control" ng-options="item.term_id as item.term_year_name for item in terms" ng-model="filters.term_id" required>' +
									'<option value="">--select term--</option>' +
								'</select>' +
								'<p ng-show="examForm.term_id.$invalid && (examForm.term_id.$touched || examForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Term is required.</p>' +
							'</div>' +
							'<!-- Exam -->' +
							'<div class="form-group">' +
								'<label for="exam_type">Exam</label>' +
								'<select name="exam_type" class="form-control" ng-options="exam.exam_type_id as exam.exam_type for exam in examTypes" ng-model="filters.exam_type_id" required>' +
									'<option value="">-- select exam --</option>' +		
								'</select>' +
								'<p ng-show="examForm.exam_type.$invalid && (examForm.exam_type.$touched || examForm.$submitted)" class="help-block"><i class="fa fa-exclamation-triangle"></i> Exam Type is required.</p>' +
							'</div>' +
							'<!-- search btn -->' +
							'<div class="form-group submit-btn">' +
								'<input type="submit" class="btn btn-sm btn-info" ng-click="getStudentExams(examForm)" value="Load" />' +
								'<span ng-show="loading" class="fa fa-spinner fa-pulse"></span>' +
							'</div>	' +
							'<hr>' +
						'</div>' +
					'</div>' +
					'<p ng-show="examNotFound" class="error alert alert-danger">' +
						'The selected exam has not been set up for this class.' +
					'</p>' +
					'<div class="row">' +
						'<div class="col-sm-12" ng-repeat="item in subjects track by $index" ng-class="{\'text-muted\': item.parent_subject_id!==null}">' +
							'<label class="col-sm-6" ng-class="{\'indent\': item.parent_subject_id!==null}">{{item.subject_name}}</label>' +
							'<div class="input-group col-sm-2">' +
								'<input type="text" class="form-control" ng-model="item.mark" numeric-only ng-change="calculateParentSubject({{item.parent_subject_id}})" ng-model-options="{ debounce: 500 }" ng-disabled="item.is_parent" />' +
								'<div class="input-group-addon"> / {{item.grade_weight}}</div>' +
							'</div>' +
						'</div>' +
					'</div>' +							
				'</form>' +
			'</div>'+
			'<div class="modal-footer">' +
				'<button type="button" class="btn btn-link" ng-click="cancel()">Cancel</button>' +
				'<button type="button" class="btn btn-primary" ng-click="save()">Save</button>' +
			'</div>'
		);
}])
;


