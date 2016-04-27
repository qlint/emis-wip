angular.module('eduwebApp').service('apiService', function($rootScope, ajaxService) {
		
	this.getClassCats = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getClassCats", successFunction, errorFunction, params);
	};
	
	this.getEmpCats = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getEmployeeCats", successFunction, errorFunction, params);
	};
	
	this.getDepts = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getDepartments", successFunction, errorFunction, params);
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
	
	this.updateSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateSettings/", successFunction, errorFunction, params);
	};
	
	this.getSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getSettings", successFunction, errorFunction, params);
	};
	
	this.getGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getGrading", successFunction, errorFunction, params);
	};
	
	
	this.getCountries = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getCountries", successFunction, errorFunction, params);
	};
	
	this.getInstallmentOptions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getInstallmentOptions", successFunction, errorFunction, params);
	};
	
	this.getAllClasses = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getAllClasses", successFunction, errorFunction, params);
	};
	
	this.getFeeItems = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getFeeItems", successFunction, errorFunction);
	};
	
	this.getTansportRoutes = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getTansportRoutes", successFunction, errorFunction);
	};
	
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
	
	this.getSubjects = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getSubjects/" + param, successFunction, errorFunction, params);
	};
	
	this.addSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addSubject/", successFunction, errorFunction, params);
	};
	
	this.addClassCat = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addClassCat/", successFunction, errorFunction, params);
	};
	
	this.updateSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/updateSubject/", successFunction, errorFunction, params);
	};
	
	this.setSubjectStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.localhost/setSubjectStatus/", successFunction, errorFunction, params);
	};
	
	this.getAllTeachers = function (param, successFunction, errorFunction) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllTeachers/" + param, successFunction, errorFunction);
	};
	
	this.getExamTypes = function (param, successFunction, errorFunction, params) {          
		if( param === undefined ) ajaxService.AjaxGet("http://api.eduweb.localhost/getExamTypes", successFunction, errorFunction, params);
		else  ajaxService.AjaxGet("http://api.eduweb.localhost/getExamTypes/" + param, successFunction, errorFunction, params);
	};
	
	this.addExamMarks = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addExamMarks/", successFunction, errorFunction, params);
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
	
	this.getClassExams = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getClassExams/" + param, successFunction, errorFunction, params);
	};
	
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
	
	this.addPayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addPayment/", successFunction, errorFunction, params);
	};
	
	this.getPaymentDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getPaymentDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.getReplaceableFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getReplaceableFeeItems/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentFeeItems/" + param, successFunction, errorFunction, params);
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
	
	this.getAllEmployees = function (param, successFunction, errorFunction) {  
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllEmployees/" + param, successFunction, errorFunction);
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
	
	this.addExamType = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.localhost/addExamType/", successFunction, errorFunction, params);
	};
	


	/*
	this.doJSONPRequest = function (request, successFunction, errorFunction, params) {        
		ajaxService.JSONPGet(request, "http://41.72.203.166/v4_dev/live_tracking?api_action=" + request.api_action + "&callback=JSON_CALLBACK", successFunction, errorFunction, params);
	};
	*/

		
	return this;
});

