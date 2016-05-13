angular.module('eduwebApp').service('apiService', [ '$rootScope', 'ajaxService', function($rootScope, ajaxService) {
		
	/*********** class categories ***********/
	this.getClassCats = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getClassCats", successFunction, errorFunction, params);
	};
	
	this.getClassCatsSummary = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getClassCatsSummary", successFunction, errorFunction, params);
	};
	
	this.addClassCat = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addClassCat/", successFunction, errorFunction, params);
	};
	
	
	/*********** employee categories ***********/
	this.getEmpCats = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getEmployeeCats", successFunction, errorFunction, params);
	};
	
	/*********** departments ***********/
	
	this.getDepts = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getDepartments", successFunction, errorFunction, params);
	};
	
	this.getDeptSummary = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getDeptSummary", successFunction, errorFunction, params);
	};
	
	this.setDeptStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/setDeptStatus/", successFunction, errorFunction, params);
	};
	
	this.addDept = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addDepartment/", successFunction, errorFunction, params);
	};
	
	this.updateDept = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateDepartment/", successFunction, errorFunction, params);
	};
	
	/*********** settings ***********/
	this.updateSetting = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateSetting/", successFunction, errorFunction, params);
	};
	
	this.updateSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateSettings/", successFunction, errorFunction, params);
	};
	
	this.getSettings = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getSettings", successFunction, errorFunction, params);
	};
	
	
	/*********** grading ***********/
	this.getGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getGrading", successFunction, errorFunction, params);
	};
	
	this.addGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addGrading/", successFunction, errorFunction, params);
	};
	
	this.updateGrading = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateGrading/", successFunction, errorFunction, params);
	};
	
	/*********** countries ***********/
	this.getCountries = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getCountries", successFunction, errorFunction, params);
	};
	
	/*********** installment options ***********/
	this.getInstallmentOptions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getInstallmentOptions", successFunction, errorFunction, params);
	};
	
	
	/*********** classes ***********/
	this.getAllClasses = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getAllClasses", successFunction, errorFunction, params);
	};
	
	this.getClasses = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getClasses/" + param, successFunction, errorFunction, params);
	};
	
	this.addClass = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addClass/", successFunction, errorFunction, params);
	};
	
	this.updateClass = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateClass/", successFunction, errorFunction, params);
	};
	
	this.setClassStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/setClassStatus/", successFunction, errorFunction, params);
	};
	
	/*********** fee items ***********/
	this.getFeeItems = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getFeeItems", successFunction, errorFunction);
	};
	
	this.getTansportRoutes = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getTansportRoutes", successFunction, errorFunction);
	};
	
	this.getReplaceableFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getReplaceableFeeItems/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentFeeItems = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentFeeItems/" + param, successFunction, errorFunction, params);
	};
	
	this.addFeeItem = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addFeeItem/", successFunction, errorFunction, params);
	};
	
	this.updateFeeItem = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateFeeItem/", successFunction, errorFunction, params);
	};
	
	this.setFeeItemStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/setFeeItemStatus/", successFunction, errorFunction, params);
	};
	
	this.updateRoutes = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateRoutes/", successFunction, errorFunction, params);
	};
	
	/*********** terms ***********/
	this.getTerms = function (param, successFunction, errorFunction, params) {     
		if( param === undefined ) ajaxService.AjaxGet("http://api.eduweb.co.ke/getTerms", successFunction, errorFunction, params);
		else ajaxService.AjaxGet("http://api.eduweb.co.ke/getTerms/" + param, successFunction, errorFunction, params);
		
	};
	
	this.addTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addTerm/", successFunction, errorFunction, params);
	};
	
	this.updateTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateTerm/", successFunction, errorFunction, params);
	};	
	
	this.getCurrentTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getCurrentTerm", successFunction, errorFunction, params);
	};
	
	this.getNextTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.co.ke/getNextTerm", successFunction, errorFunction, params);
	};
	
	/*********** subjects ***********/
	this.getSubjects = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getSubjects/" + param, successFunction, errorFunction, params);
	};
	
	this.addSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addSubject/", successFunction, errorFunction, params);
	};
	
	this.updateSubject = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateSubject/", successFunction, errorFunction, params);
	};
	
	this.setSubjectStatus = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/setSubjectStatus/", successFunction, errorFunction, params);
	};
	
	/*********** employees ***********/	
	this.getAllTeachers = function (param, successFunction, errorFunction) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getAllTeachers/" + param, successFunction, errorFunction);
	};
	
	this.getAllEmployees = function (param, successFunction, errorFunction) {  
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getAllEmployees/" + param, successFunction, errorFunction);
	};
	
	this.addEmployee = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addEmployee/", successFunction, errorFunction, params);
	};
	
	this.getEmployeeDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getEmployeeDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.updateEmployee = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateEmployee/", successFunction, errorFunction, params);
	};
	
	/*********** exams ***********/
	this.getExamTypes = function (param, successFunction, errorFunction, params) {          
		if( param === undefined ) ajaxService.AjaxGet("http://api.eduweb.co.ke/getExamTypes", successFunction, errorFunction, params);
		else  ajaxService.AjaxGet("http://api.eduweb.co.ke/getExamTypes/" + param, successFunction, errorFunction, params);
	};
	
	this.deleteExamType = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete("http://api.eduweb.co.ke/deleteExamType/" + param, successFunction, errorFunction, params);
	};
	
	this.addExamMarks = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addExamMarks/", successFunction, errorFunction, params);
	};
	
	this.getClassExams = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getClassExams/" + param, successFunction, errorFunction, params);
	};
	
	this.addExamType = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addExamType/", successFunction, errorFunction, params);
	};
	
	this.getClassExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getClassExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	this.getTopStudents = function (param, successFunction, errorFunction, params) {          
		if( param === undefined ) ajaxService.AjaxGet("http://api.eduweb.co.ke/getTopStudents", successFunction, errorFunction, params);
		else  ajaxService.AjaxGet("http://api.eduweb.co.ke/getTopStudents/" + param, successFunction, errorFunction, params);
	};
	
	/*********** report cards ***********/
	
	this.getAllStudentReportCards = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getAllStudentReportCards/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentReportCards = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentReportCards/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentReportCard = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentReportCard/" + param, successFunction, errorFunction, params);
	};
	
	this.getExamMarksforReportCard = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getExamMarksforReportCard/" + param, successFunction, errorFunction, params);
	};
	
	this.addReportCard = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addReportCard/", successFunction, errorFunction, params);
	};
	
	/*********** students ***********/
	this.getAllStudents = function (param, successFunction, errorFunction) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getAllStudents/" + param, successFunction, errorFunction);
	};
	
	this.getStudentDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentBalance = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentBalance/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentInvoices = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentInvoices/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentPayments = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentPayments/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	this.getAllStudentExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getAllStudentExamMarks/" + param, successFunction, errorFunction, params);
	};
	
	
	
	this.postStudent = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addStudent/", successFunction, errorFunction, params);
	};
	
	this.updateStudent = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateStudent/", successFunction, errorFunction, params);
	};
	
	this.postGuardian = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addGuardian/", successFunction, errorFunction, params);
	};
	
	this.updateGuardian = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateGuardian/", successFunction, errorFunction, params);
	};
	
	this.deleteGuardian = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete("http://api.eduweb.co.ke/deleteGuardian/" + param, successFunction, errorFunction, params);
	};
	
	this.postMedicalConditions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addMedicalConditions/", successFunction, errorFunction, params);
	};
	
	this.updateMedicalConditions = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateMedicalConditions/", successFunction, errorFunction, params);
	};
	
	this.deleteMedicalCondition = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxDelete("http://api.eduweb.co.ke/deleteMedicalCondition/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentClassess = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentClassess/" + param, successFunction, errorFunction, params);
	};
	
	
	
	/*********** payments ***********/
	this.getPaymentsReceived = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getPaymentsReceived/" + param, successFunction, errorFunction, params);
	};
	
	this.getPaymentsDue = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getPaymentsDue/" + param, successFunction, errorFunction, params);
	};
	
	this.getPaymentsPastDue = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getPaymentsPastDue", successFunction, errorFunction, params);
	};
	
	this.getTotalsForTerm = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getTotalsForTerm", successFunction, errorFunction, params);
	};	
	
	this.getStudentBalances = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getStudentBalances/" + param, successFunction, errorFunction, params);
	};	
	
	this.addPayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/addPayment/", successFunction, errorFunction, params);
	};
	
	this.getPaymentDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getPaymentDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.updatePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updatePayment/", successFunction, errorFunction, params);
	};
	
	this.reversePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/reversePayment/", successFunction, errorFunction, params);
	};
	this.reactivatePayment = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/reactivatePayment/", successFunction, errorFunction, params);
	};
	
	/*********** invoices ***********/	
	this.getInvoices = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getInvoices/" + param, successFunction, errorFunction, params);
	};	
	
	this.getOpenInvoices = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getOpenInvoices/" + param, successFunction, errorFunction, params);
	};
	
	this.getInvoiceDetails = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.co.ke/getInvoiceDetails/" + param, successFunction, errorFunction, params);
	};
	
	this.generateInvoices = function (param, successFunction, errorFunction, params) {      
		ajaxService.AjaxGet("http://api.eduweb.co.ke/generateInvoices/" + param, successFunction, errorFunction, params);
	};	
	
	this.createInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost2(request, "http://api.eduweb.co.ke/createInvoice/", successFunction, errorFunction, params);
	};
	
	this.updateInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/updateInvoice/", successFunction, errorFunction, params);
	};
	
	this.cancelInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/cancelInvoice/", successFunction, errorFunction, params);
	};
	this.reactivateInvoice = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPut(request, "http://api.eduweb.co.ke/reactivateInvoice/", successFunction, errorFunction, params);
	};
	
	

	return this;
}]);

