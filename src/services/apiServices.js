angular.module('eduwebApp').service('apiService', function($rootScope, ajaxService) {
		
	/*********** class categories ***********/
	this.getClassCats = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getClassCats", successFunction, errorFunction, params);
	};
	
	this.getClassCatsSummary = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getClassCatsSummary", successFunction, errorFunction, params);
	};
	
	this.addClassCat = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addClassCat/", successFunction, errorFunction, params);
	};
	
	
	/*********** employee categories ***********/
	this.getEmpCats = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getEmployeeCats", successFunction, errorFunction, params);
	};
	
	/*********** departments ***********/
	
	this.getDepts = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getDepartments", successFunction, errorFunction, params);
	};
	
	this.getDeptSummary = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getDeptSummary", successFunction, errorFunction, params);
	};
	
	this.setDeptStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/setDeptStatus/", successFunction, errorFunction, params);
	};
	
	this.addDept = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addDepartment/", successFunction, errorFunction, params);
	};
	
	this.updateDept = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateDepartment/", successFunction, errorFunction, params);
	};
	
	/*********** settings ***********/
	this.updateSetting = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateSetting/", successFunction, errorFunction, params);
	};
	
	this.updateSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateSettings/", successFunction, errorFunction, params);
	};
	
	this.getSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getSettings", successFunction, errorFunction, params);
	};
	
	
	/*********** grading ***********/
	this.getGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getGrading", successFunction, errorFunction, params);
	};
	
	this.addGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addGrading/", successFunction, errorFunction, params);
	};
	
	this.updateGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateGrading/", successFunction, errorFunction, params);
	};
	
	/*********** countries ***********/
	this.getCountries = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getCountries", successFunction, errorFunction, params);
	};
	
	/*********** installment options ***********/
	this.getInstallmentOptions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getInstallmentOptions", successFunction, errorFunction, params);
	};
	
	
	/*********** classes ***********/
	this.getAllClasses = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getAllClasses", successFunction, errorFunction, params);
	};
	
	this.getClasses = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getClasses/" + param, successFunction, errorFunction, params);
	};
	
	this.addClass = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addClass/", successFunction, errorFunction, params);
	};
	
	this.updateClass = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateClass/", successFunction, errorFunction, params);
	};
	
	this.setClassStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/setClassStatus/", successFunction, errorFunction, params);
	};
	
	/*********** fee items ***********/
	this.getFeeItems = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getFeeItems", successFunction, errorFunction);
	};
	
	this.getTansportRoutes = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getTansportRoutes", successFunction, errorFunction);
	};
	
	this.getReplaceableFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getReplaceableFeeItems/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentFeeItems/" + param, successFunction, errorFunction, params);
	};
	
	this.addFeeItem = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addFeeItem/", successFunction, errorFunction, params);
	};
	
	this.updateFeeItem = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateFeeItem/", successFunction, errorFunction, params);
	};
	
	this.setFeeItemStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/setFeeItemStatus/", successFunction, errorFunction, params);
	};
	
	this.updateRoutes = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateRoutes/", successFunction, errorFunction, params);
	};
	
	/*********** terms ***********/
	this.getTerms = function (param, successFunction, errorFunction, params) {     
		if( param === undefined ) ajaxService.AjaxGet("http://api.eduweb.localhost/getTerms", successFunction, errorFunction, params);
		else ajaxService.AjaxGet("http://api.eduweb.localhost/getTerms/" + param, successFunction, errorFunction, params);
		
	};
	
	this.addTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addTerm/", successFunction, errorFunction, params);
	};
	
	this.updateTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateTerm/", successFunction, errorFunction, params);
	};	
	
	this.getCurrentTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getCurrentTerm", successFunction, errorFunction, params);
	};
	
	this.getNextTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getNextTerm", successFunction, errorFunction, params);
	};
	
	/*********** subjects ***********/
	this.getSubjects = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getSubjects/" + param, successFunction, errorFunction, params);
	};
	
	this.addSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addSubject/", successFunction, errorFunction, params);
	};
	
	this.updateSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateSubject/", successFunction, errorFunction, params);
	};
	
	this.setSubjectStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/setSubjectStatus/", successFunction, errorFunction, params);
	};
	
	/*********** employees ***********/	
	this.getAllTeachers = function (param, successFunction, errorFunction) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllTeachers/" + param, successFunction, errorFunction);
	};
	
	this.getAllEmployees = function (param, successFunction, errorFunction) {  
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllEmployees/" + param, successFunction, errorFunction);
	};
	
	this.addEmployee = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addEmployee/", successFunction, errorFunction, params);
	};
	
	this.getEmployeeDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getEmployeeDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.updateEmployee = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateEmployee/", successFunction, errorFunction, params);
	};
	
	/*********** exams ***********/
	this.getExamTypes = function (param, successFunction, errorFunction, params) {          
		if( param === undefined ) ajaxService.AjaxGet("http://api.eduweb.localhost/getExamTypes", successFunction, errorFunction, params);
		else  ajaxService.AjaxGet("http://api.eduweb.localhost/getExamTypes/" + param, successFunction, errorFunction, params);
	};
	
	this.deleteExamType = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete("http://api.eduweb.localhost/deleteExamType/" + param, successFunction, errorFunction, params);
	};
	
	this.addExamMarks = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addExamMarks/", successFunction, errorFunction, params);
	};
	
	this.getClassExams = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getClassExams/" + param, successFunction, errorFunction, params);
	};
	
	this.addExamType = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addExamType/", successFunction, errorFunction, params);
	};
	
	
	/*********** students ***********/
	this.getAllStudents = function (param, successFunction, errorFunction) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllStudents/" + param, successFunction, errorFunction);
	};
	
	this.getStudentDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentBalance = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentBalance/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentInvoices = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentInvoices/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentPayments = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentPayments/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	this.getAllStudentExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllStudentExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	this.postStudent = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addStudent/", successFunction, errorFunction, params);
	};
	
	this.updateStudent = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateStudent/", successFunction, errorFunction, params);
	};
	
	this.postGuardian = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addGuardian/", successFunction, errorFunction, params);
	};
	
	this.updateGuardian = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateGuardian/", successFunction, errorFunction, params);
	};
	
	this.deleteGuardian = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete("http://api.eduweb.localhost/deleteGuardian/" + param, successFunction, errorFunction, params);
	};
	
	this.postMedicalConditions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addMedicalConditions/", successFunction, errorFunction, params);
	};
	
	this.updateMedicalConditions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateMedicalConditions/", successFunction, errorFunction, params);
	};
	
	this.deleteMedicalCondition = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete("http://api.eduweb.localhost/deleteMedicalCondition/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentClassess = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentClassess/" + param, successFunction, errorFunction, params);
	};
	
	this.getAllStudentReportCards = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllStudentReportCards/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentReportCards = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentReportCards/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentReportCard = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentReportCard/" + param, successFunction, errorFunction, params);
	};
	
	this.getExamMarksforReportCard = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getExamMarksforReportCard/" + param, successFunction, errorFunction, params);
	};
	
	this.addReportCard = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addReportCard/", successFunction, errorFunction, params);
	};
	
	/*********** payments ***********/
	this.getPaymentsReceived = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getPaymentsReceived/" + param, successFunction, errorFunction, params);
	};
	
	this.getPaymentsDue = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.localhost/getPaymentsDue/" + param, successFunction, errorFunction, params);
	};
	
	this.getPaymentsPastDue = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.localhost/getPaymentsPastDue", successFunction, errorFunction, params);
	};
	
	this.getTotalsForTerm = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.localhost/getTotalsForTerm", successFunction, errorFunction, params);
	};	
	
	this.getStudentBalances = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentBalances/" + param, successFunction, errorFunction, params);
	};	
	
	this.addPayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addPayment/", successFunction, errorFunction, params);
	};
	
	this.getPaymentDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getPaymentDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.updatePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updatePayment/", successFunction, errorFunction, params);
	};
	
	this.reversePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/reversePayment/", successFunction, errorFunction, params);
	};
	this.reactivatePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/reactivatePayment/", successFunction, errorFunction, params);
	};
	
	/*********** invoices ***********/	
	this.getInvoices = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.localhost/getInvoices/" + param, successFunction, errorFunction, params);
	};	
	
	this.getOpenInvoices = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getOpenInvoices/" + param, successFunction, errorFunction, params);
	};
	
	this.getInvoiceDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getInvoiceDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.generateInvoices = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.localhost/generateInvoices/" + param, successFunction, errorFunction, params);
	};	
	
	this.createInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/createInvoice/", successFunction, errorFunction, params);
	};
	
	this.updateInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateInvoice/", successFunction, errorFunction, params);
	};
	
	this.cancelInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/cancelInvoice/", successFunction, errorFunction, params);
	};
	this.reactivateInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/reactivateInvoice/", successFunction, errorFunction, params);
	};
	
	

	return this;
});

