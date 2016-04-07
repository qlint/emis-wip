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
	
	this.getCountries = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getCountries", successFunction, errorFunction, params);
	};
	
	this.getAllClasses = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getAllClasses", successFunction, errorFunction, params);
	};
	
	this.getFeeItems = function (request, successFunction, errorFunction) {  
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getFeeItems", successFunction, errorFunction);
	};
	
	this.getTerms = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getTerms", successFunction, errorFunction, params);
	};
	
	this.getCurrentTerm = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getCurrentTerm", successFunction, errorFunction, params);
	};
	
	this.getExamTypes = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxGetWithData(request, "http://api.eduweb.localhost/getExamTypes", successFunction, errorFunction, params);
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
	
	this.getStudentPayments = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentPayments/" + param, successFunction, errorFunction, params);
	};
	
	this.getStudentExamMarks = function (param, successFunction, errorFunction, params) {          
		ajaxService.AjaxGet("http://api.eduweb.localhost/getStudentExamMarks/" + param, successFunction, errorFunction, params);
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
	
	
	
	
	this.getAllEmployees = function (param, successFunction, errorFunction) {  
		ajaxService.AjaxGet("http://api.eduweb.localhost/getAllEmployees/" + param, successFunction, errorFunction);
	};
	
	
	
	
	
	this.postUserRequest = function (request, successFunction, errorFunction, params) {          
		ajaxService.AjaxPost(request, "http://41.72.203.166/cargoview_dev/user_mngmt_api", successFunction, errorFunction, params);
	};

	
	this.doJSONPRequest = function (request, successFunction, errorFunction, params) {        
		ajaxService.JSONPGet(request, "http://41.72.203.166/v4_dev/live_tracking?api_action=" + request.api_action + "&callback=JSON_CALLBACK", successFunction, errorFunction, params);
	};
		

		
	return this;
});

