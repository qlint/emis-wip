angular.module('eduwebApp').service('apiService', [ '$rootScope', 'ajaxService', function($rootScope, ajaxService) {
		
	var domain = window.location.host;
	var path = ( domain.indexOf('eduweb.co.ke') > -1  ? 'http://api.eduweb.co.ke': 'http://api.eduweb.localhost');
	
	/*********** class categories ***********/	
	this.getClassCats = function (param, successFunction, errorFunction, params) {      
		if( param === undefined ) ajaxService.AjaxGet(path + "/getClassCats", successFunction, errorFunction, params);
		else ajaxService.AjaxGet(path + "/getClassCats/" + param, successFunction, errorFunction, params);
	};
	
	this.getClassCatsSummary = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getClassCatsSummary", successFunction, errorFunction, params);
	};
	
	this.addClassCat = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addClassCat/", successFunction, errorFunction, params);
	};
	
	this.updateClassCat = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateClassCat/", successFunction, errorFunction, params);
	};
	
	this.setClassCatStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/setClassCatStatus/", successFunction, errorFunction, params);
	};
	
	
	/*********** employee categories ***********/
	this.getEmpCats = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getEmployeeCats", successFunction, errorFunction, params);
	};
	
	this.addEmployeeCat = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addEmployeeCat/", successFunction, errorFunction, params);
	};
	
	this.updateEmployeeCat = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateEmployeeCat/", successFunction, errorFunction, params);
	};
	
	this.setEmployeeCatStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/setEmployeeCatStatus/", successFunction, errorFunction, params);
	};
	
	/*********** departments ***********/
	
	this.getDepts = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getDepartments", successFunction, errorFunction, params);
	};
	
	this.getDeptSummary = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getDeptSummary", successFunction, errorFunction, params);
	};
	
	this.setDeptStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/setDeptStatus/", successFunction, errorFunction, params);
	};
	
	this.addDept = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addDepartment/", successFunction, errorFunction, params);
	};
	
	this.updateDept = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateDepartment/", successFunction, errorFunction, params);
	};
	
	/*********** settings ***********/
	this.updateSetting = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateSetting/", successFunction, errorFunction, params);
	};
	
	this.updateSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateSettings/", successFunction, errorFunction, params);
	};
	
	this.getSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getSettings", successFunction, errorFunction, params);
	};
	
	
	/*********** grading ***********/
	this.getGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getGrading", successFunction, errorFunction, params);
	};
	
	this.addGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addGrading/", successFunction, errorFunction, params);
	};
	
	this.updateGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateGrading/", successFunction, errorFunction, params);
	};
	
	/*********** countries ***********/
	this.getCountries = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getCountries", successFunction, errorFunction, params);
	};
	
	/*********** installment options ***********/
	this.getInstallmentOptions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getInstallmentOptions", successFunction, errorFunction, params);
	};
	
	
	/*********** classes ***********/
	this.getAllClasses = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getAllClasses", successFunction, errorFunction, params);
	};
	
	this.getTeacherClasses = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getTeacherClasses/" + param, successFunction, errorFunction, params);
	};
	
	this.getClasses = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getClasses/" + param, successFunction, errorFunction, params);
	};
	
	this.addClass = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addClass/", successFunction, errorFunction, params);
	};
	
	this.updateClass = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateClass/", successFunction, errorFunction, params);
	};
	
	this.setClassStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/setClassStatus/", successFunction, errorFunction, params);
	};
	
	/*********** fee items ***********/
	this.getFeeItems = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, path + "/getFeeItems", successFunction, errorFunction);
	};
	
	this.getTansportRoutes = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, path + "/getTansportRoutes", successFunction, errorFunction);
	};
	
	this.getReplaceableFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getReplaceableFeeItems/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentFeeItems/" + param, successFunction, errorFunction, params);
	};
	
	this.addFeeItem = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addFeeItem/", successFunction, errorFunction, params);
	};
	
	this.updateFeeItem = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateFeeItem/", successFunction, errorFunction, params);
	};
	
	this.setFeeItemStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/setFeeItemStatus/", successFunction, errorFunction, params);
	};
	
	this.updateRoutes = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateRoutes/", successFunction, errorFunction, params);
	};
	
	/*********** terms ***********/
	this.getTerms = function (param, successFunction, errorFunction, params) {     
		if( param === undefined ) ajaxService.AjaxGet(path + "/getTerms", successFunction, errorFunction, params);
		else ajaxService.AjaxGet(path + "/getTerms/" + param, successFunction, errorFunction, params);
		
	};
	
	this.addTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addTerm/", successFunction, errorFunction, params);
	};
	
	this.updateTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateTerm/", successFunction, errorFunction, params);
	};	
	
	this.getCurrentTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getCurrentTerm", successFunction, errorFunction, params);
	};
	
	this.getNextTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, path + "/getNextTerm", successFunction, errorFunction, params);
	};
	
	/*********** subjects ***********/
	this.getSubjects = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getSubjects/" + param, successFunction, errorFunction, params);
	};
	
	this.addSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addSubject/", successFunction, errorFunction, params);
	};
	
	this.updateSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateSubject/", successFunction, errorFunction, params);
	};
	
	this.setSubjectStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/setSubjectStatus/", successFunction, errorFunction, params);
	};
	
	/*********** employees ***********/	
	this.getAllTeachers = function (param, successFunction, errorFunction) {          
		ajaxService.AjaxGet(path + "/getAllTeachers/" + param, successFunction, errorFunction);
	};
	
	this.getAllEmployees = function (param, successFunction, errorFunction) {  
		ajaxService.AjaxGet(path + "/getAllEmployees/" + param, successFunction, errorFunction);
	};
	
	this.addEmployee = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addEmployee/", successFunction, errorFunction, params);
	};
	
	this.getEmployeeDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getEmployeeDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.updateEmployee = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateEmployee/", successFunction, errorFunction, params);
	};
	
	/*********** exams ***********/
	this.getExamTypes = function (param, successFunction, errorFunction, params) {          
		if( param === undefined ) ajaxService.AjaxGet(path + "/getExamTypes", successFunction, errorFunction, params);
		else  ajaxService.AjaxGet(path + "/getExamTypes/" + param, successFunction, errorFunction, params);
	};
	
	this.deleteExamType = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete(path + "/deleteExamType/" + param, successFunction, errorFunction, params);
	};
	
	this.addExamMarks = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addExamMarks/", successFunction, errorFunction, params);
	};
	
	this.getClassExams = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getClassExams/" + param, successFunction, errorFunction, params);
	};
	
	this.addExamType = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addExamType/", successFunction, errorFunction, params);
	};
	
	this.getClassExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getClassExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	this.getTopStudents = function (param, successFunction, errorFunction, params) {          
		if( param === undefined ) ajaxService.AjaxGet(path + "/getTopStudents", successFunction, errorFunction, params);
		else  ajaxService.AjaxGet(path + "/getTopStudents/" + param, successFunction, errorFunction, params);
	};
	
	/*********** report cards ***********/
	
	this.getAllStudentReportCards = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getAllStudentReportCards/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentReportCards = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentReportCards/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentReportCard = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentReportCard/" + param, successFunction, errorFunction, params);
	};
	
	this.getExamMarksforReportCard = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getExamMarksforReportCard/" + param, successFunction, errorFunction, params);
	};
	
	this.addReportCard = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addReportCard/", successFunction, errorFunction, params);
	};
	
	/*********** students ***********/
	this.getAllStudents = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getAllStudents/" + param, successFunction, errorFunction, params);
	};
	
	this.getTeacherStudents = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getTeacherStudents/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentBalance = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentBalance/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentInvoices = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentInvoices/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentPayments = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentPayments/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	this.getAllStudentExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getAllStudentExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	
	
	this.postStudent = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addStudent/", successFunction, errorFunction, params);
	};
	
	this.updateStudent = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateStudent/", successFunction, errorFunction, params);
	};
	
	this.postGuardian = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addGuardian/", successFunction, errorFunction, params);
	};
	
	this.updateGuardian = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateGuardian/", successFunction, errorFunction, params);
	};
	
	this.deleteGuardian = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete(path + "/deleteGuardian/" + param, successFunction, errorFunction, params);
	};
	
	this.postMedicalConditions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addMedicalConditions/", successFunction, errorFunction, params);
	};
	
	this.updateMedicalConditions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateMedicalConditions/", successFunction, errorFunction, params);
	};
	
	this.deleteMedicalCondition = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete(path + "/deleteMedicalCondition/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentClassess = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getStudentClassess/" + param, successFunction, errorFunction, params);
	};
	
	
	
	/*********** payments ***********/
	this.getPaymentsReceived = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getPaymentsReceived/" + param, successFunction, errorFunction, params);
	};
	
	this.getPaymentsDue = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet(path + "/getPaymentsDue/" + param, successFunction, errorFunction, params);
	};
	
	this.getPaymentsPastDue = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet(path + "/getPaymentsPastDue", successFunction, errorFunction, params);
	};
	
	this.getTotalsForTerm = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet(path + "/getTotalsForTerm", successFunction, errorFunction, params);
	};	
	
	this.getStudentBalances = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet(path + "/getStudentBalances/" + param, successFunction, errorFunction, params);
	};	
	
	this.addPayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/addPayment/", successFunction, errorFunction, params);
	};
	
	this.getPaymentDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getPaymentDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.updatePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updatePayment/", successFunction, errorFunction, params);
	};
	
	this.reversePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/reversePayment/", successFunction, errorFunction, params);
	};
	this.reactivatePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/reactivatePayment/", successFunction, errorFunction, params);
	};
	
	/*********** invoices ***********/	
	this.getInvoices = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet(path + "/getInvoices/" + param, successFunction, errorFunction, params);
	};	
	
	this.getOpenInvoices = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getOpenInvoices/" + param, successFunction, errorFunction, params);
	};
	
	this.getInvoiceDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet(path + "/getInvoiceDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.generateInvoices = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet(path + "/generateInvoices/" + param, successFunction, errorFunction, params);
	};	
	
	this.createInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, path + "/createInvoice/", successFunction, errorFunction, params);
	};
	
	this.updateInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/updateInvoice/", successFunction, errorFunction, params);
	};
	
	this.cancelInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/cancelInvoice/", successFunction, errorFunction, params);
	};
	this.reactivateInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, path + "/reactivateInvoice/", successFunction, errorFunction, params);
	};
	
	

	return this;
}]);

