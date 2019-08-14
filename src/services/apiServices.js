angular.module('eduwebApp').service('apiService', [ '$rootScope', 'ajaxService', function($rootScope, ajaxService) {

	var domain = window.location.host;
	// var path = ( domain.indexOf('dev.eduweb.co.ke') > -1 ? 'http://devapi.eduweb.co.ke' : (domain.indexOf('eduweb.co.ke') > -1	? 'http://api.eduweb.co.ke': 'http://api.eduweb.localhost'));
	// if(domain == '67.219.189.47'){
	if(domain == 'eduweb.co.ke'){
		// var path = 'http://67.219.189.47/api';
		var path = 'https://eduweb.co.ke/api';
	}else{
		var path = ( domain.indexOf('dev.eduweb.co.ke') > -1 ? 'https://devapi.eduweb.co.ke' : (domain.indexOf('eduweb.co.ke') > -1	? 'https://api.eduweb.co.ke': 'https://api.eduweb.localhost'));
	}

	/*********** class categories ***********/
	this.getClassCats = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getClassCats", successFunction, errorFunction, params);
		else ajaxService.AjaxGet(path + "/getClassCats/" + param, successFunction, errorFunction, params);
	};

	this.getStreamExamMarks = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStreamExamMarks/" + param, successFunction, errorFunction, params);
	};

	this.getStreamPosition = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getStreamPosition", successFunction, errorFunction, params);
		else ajaxService.AjaxGet(path + "/getStreamPosition/" + param, successFunction, errorFunction, params);
	};

	this.getSpecialStreamPosition = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getStreamPosition", successFunction, errorFunction, params);
		else ajaxService.AjaxGet(path + "/getSpecialStreamPosition/" + param, successFunction, errorFunction, params);
	};

	this.getClassCatsSummary = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getClassCatsSummary", successFunction, errorFunction, params);
	};

	this.addClassCat = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addClassCat", successFunction, errorFunction, params);
	};

	this.updateClassCat = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateClassCat", successFunction, errorFunction, params);
	};

	this.updateExamClass = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateExamClass", successFunction, errorFunction, params);
	};

	this.setClassCatStatus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setClassCatStatus", successFunction, errorFunction, params);
	};

	this.deleteClassCat = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteClassCat/" + param, successFunction, errorFunction, params);
	};

	this.checkClassCat = function (param, successFunction, errorFunction, params) {
		 ajaxService.AjaxGet(path + "/checkClassCat/" + param, successFunction, errorFunction, params);
	};

	/*********** employee categories ***********/
	this.getEmpCats = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getEmployeeCats", successFunction, errorFunction, params);
	};

	this.addEmployeeCat = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addEmployeeCat", successFunction, errorFunction, params);
	};

	this.updateEmployeeCat = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateEmployeeCat", successFunction, errorFunction, params);
	};

	this.setEmployeeCatStatus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setEmployeeCatStatus", successFunction, errorFunction, params);
	};

	this.deleteEmpCat = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteEmpCat/" + param, successFunction, errorFunction, params);
	};

	this.checkEmpCat = function (param, successFunction, errorFunction, params) {
		 ajaxService.AjaxGet(path + "/checkEmpCat/" + param, successFunction, errorFunction, params);
	};

	/*********** departments ***********/
	this.getDepts = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getDepartments", successFunction, errorFunction, params);
		else ajaxService.AjaxGet(path + "/getDepartments/" + param, successFunction, errorFunction, params);
	};

	this.getDeptSummary = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getDeptSummary", successFunction, errorFunction, params);
	};

	this.checkDepartment = function (param, successFunction, errorFunction, params) {
		 ajaxService.AjaxGet(path + "/checkDepartment/" + param, successFunction, errorFunction, params);
	};

	this.setDeptStatus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setDeptStatus", successFunction, errorFunction, params);
	};

	this.addDept = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addDepartment", successFunction, errorFunction, params);
	};

	this.updateDept = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateDepartment", successFunction, errorFunction, params);
	};

	this.deleteDept = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteDept/" + param, successFunction, errorFunction, params);
	};

	/*********** settings ***********/
	this.updateSetting = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateSetting", successFunction, errorFunction, params);
	};

	this.updateSettings = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateSettings", successFunction, errorFunction, params);
	};

	this.getSettings = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getSettings", successFunction, errorFunction, params);
	};

	this.getBanking = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getBanking", successFunction, errorFunction, params);
	};


	/*********** grading ***********/
	this.getGrading = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getGrading", successFunction, errorFunction, params);
	};

	//lower school get grading
	this.getGrading2 = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getGrading2", successFunction, errorFunction, params);
	};

	this.addGrading = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addGrading", successFunction, errorFunction, params);
	};

	// lower school add grading
	this.addGrading2 = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addGrading2", successFunction, errorFunction, params);
	};

	this.updateGrading = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateGrading", successFunction, errorFunction, params);
	};

	// lower school update grading
	this.updateGrading2 = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateGrading2", successFunction, errorFunction, params);
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
		ajaxService.AjaxPost2(request, path + "/addClass", successFunction, errorFunction, params);
	};

	this.updateClass = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateClass", successFunction, errorFunction, params);
	};

	this.updateTeacherSubject = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateTeacherSubject", successFunction, errorFunction, params);
	};

	this.setClassStatus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setClassStatus", successFunction, errorFunction, params);
	};

	this.setClassSortOrder = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setClassSortOrder", successFunction, errorFunction, params);
	};

	this.deleteClass = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteClass/" + param, successFunction, errorFunction, params);
	};

	this.checkClass = function (param, successFunction, errorFunction, params) {
		 ajaxService.AjaxGet(path + "/checkClass/" + param, successFunction, errorFunction, params);
	};

	/*********** fee items ***********/
	this.getFeeItems = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getFeeItems/" + param, successFunction, errorFunction);
	};

	this.getActivitiesList = function (request, successFunction, errorFunction) {
		ajaxService.AjaxGetWithData(request, path + "/getActivitiesList", successFunction, errorFunction);
	};

	this.getTansportRoutes = function (request, successFunction, errorFunction) {
		ajaxService.AjaxGetWithData(request, path + "/getTansportRoutes", successFunction, errorFunction);
	};

	this.getUniforms = function (request, successFunction, errorFunction) {
		ajaxService.AjaxGetWithData(request, path + "/getUniforms", successFunction, errorFunction);
	};

	this.getReplaceableFeeItems = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getReplaceableFeeItems/" + param, successFunction, errorFunction, params);
	};

	this.getStudentFeeItems = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentFeeItems/" + param, successFunction, errorFunction, params);
	};

	this.addFeeItem = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addFeeItem", successFunction, errorFunction, params);
	};

	this.updateFeeItem = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateFeeItem", successFunction, errorFunction, params);
	};

	this.setFeeItemStatus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setFeeItemStatus", successFunction, errorFunction, params);
	};

	this.updateRoutes = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateRoutes", successFunction, errorFunction, params);
	};

	this.updateUniforms = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateUniforms", successFunction, errorFunction, params);
	};

	this.deleteFeeItem = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteFeeItem/" + param, successFunction, errorFunction, params);
	};

	this.checkFeeItem = function (param, successFunction, errorFunction, params) {
		 ajaxService.AjaxGet(path + "/checkFeeItem/" + param, successFunction, errorFunction, params);
	};

	/*********** terms ***********/
	this.getTerms = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getTerms", successFunction, errorFunction, params);
		else ajaxService.AjaxGet(path + "/getTerms/" + param, successFunction, errorFunction, params);
	};

	this.getTermsByYear = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getTermsByYear/" + param, successFunction, errorFunction, params);
	};

	this.addTerm = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addTerm", successFunction, errorFunction, params);
	};

	this.updateTerm = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateTerm", successFunction, errorFunction, params);
	};

	this.getCurrentTerm = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getCurrentTerm", successFunction, errorFunction, params);
	};

	this.getPreviousTerm = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getPreviousTerm", successFunction, errorFunction, params);
	};

	this.getNextTerm = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getNextTerm", successFunction, errorFunction, params);
		else	ajaxService.AjaxGet(path + "/getNextTerm/" + param, successFunction, errorFunction, params);
	};

	this.getTermRange = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getTermRange", successFunction, errorFunction, params);
	};

	this.deleteTerm = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteTerm/" + param, successFunction, errorFunction, params);
	};

	/*********** subjects ***********/
	this.getAllSubjects = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllSubjects/" + param, successFunction, errorFunction, params);
	};

	this.getAllTeacherSubjects = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllTeacherSubjects/" + param, successFunction, errorFunction, params);
	};

	this.getTeacherSubjects = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getTeacherSubjects/" + param, successFunction, errorFunction, params);
	};

	this.getTeacherClassSubjects = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getTeacherClassSubjects/" + param, successFunction, errorFunction, params);
	};

	this.getSubjects = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getSubjects/" + param, successFunction, errorFunction, params);
	};

	this.addSubject = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addSubject", successFunction, errorFunction, params);
	};

	this.updateSubject = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateSubject", successFunction, errorFunction, params);
	};

	this.setSubjectStatus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setSubjectStatus", successFunction, errorFunction, params);
	};

	this.setSubjectSortOrder = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setSubjectSortOrder", successFunction, errorFunction, params);
	};

	this.deleteSubject = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteSubject/" + param, successFunction, errorFunction, params);
	};

	this.checkSubject = function (param, successFunction, errorFunction, params) {
		 ajaxService.AjaxGet(path + "/checkSubject/" + param, successFunction, errorFunction, params);
	};

	/*********** employees ***********/
	this.getAllTeachers = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getAllTeachers/" + param, successFunction, errorFunction);
	};

	this.exportAllStaffDetails = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/exportAllStaffDetails", successFunction, errorFunction, params);
	};

	this.getAllEmployees = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getAllEmployees/" + param, successFunction, errorFunction);
	};

	this.addEmployee = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addEmployee", successFunction, errorFunction, params);
	};

	this.getEmployeeDetails = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getEmployeeDetails/" + param, successFunction, errorFunction, params);
	};

	this.updateEmployee = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateEmployee", successFunction, errorFunction, params);
	};

	/*********** exams ***********/
	this.getExamTypes = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getExamTypes", successFunction, errorFunction, params);
		else	ajaxService.AjaxGet(path + "/getExamTypes/" + param, successFunction, errorFunction, params);
	};

	this.getSpecialExamTypes = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getSpecialExamTypes", successFunction, errorFunction, params);
		else	ajaxService.AjaxGet(path + "/getSpecialExamTypes/" + param, successFunction, errorFunction, params);
	};

	this.getNonSpecialExamTypes = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getNonSpecialExamTypes", successFunction, errorFunction, params);
		else	ajaxService.AjaxGet(path + "/getNonSpecialExamTypes/" + param, successFunction, errorFunction, params);
	};

	this.deleteExamType = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteExamType/" + param, successFunction, errorFunction, params);
	};

	this.addExamMarks = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addExamMarks", successFunction, errorFunction, params);
	};

	this.getClassExams = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassExams/" + param, successFunction, errorFunction, params);
	};

	this.getAllClassExams = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllClassExams/" + param, successFunction, errorFunction, params);
	};

	this.addExamType = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addExamType", successFunction, errorFunction, params);
	};

	this.getClassExamMarks = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassExamMarks/" + param, successFunction, errorFunction, params);
	};

	this.getDoneExamSubjectCount = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getDoneExamSubjectCount/" + param, successFunction, errorFunction, params);
	};

	this.getStreamDoneExamSubjectCount = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStreamDoneExamSubjectCount/" + param, successFunction, errorFunction, params);
	};

	this.getTopStudents = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getTopStudents", successFunction, errorFunction, params);
		else	ajaxService.AjaxGet(path + "/getTopStudents/" + param, successFunction, errorFunction, params);
	};

	this.getTeacherTopStudents = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getTeacherTopStudents/" + param, successFunction, errorFunction, params);
	};

	this.setExamTypeSortOrder = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setExamTypeSortOrder", successFunction, errorFunction, params);
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

	this.getLowerSchoolExamMarksforReportCard = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getLowerSchoolExamMarksforReportCard/" + param, successFunction, errorFunction, params);
	};

	this.getSpecialExamMarksforReportCard = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getSpecialExamMarksforReportCard/" + param, successFunction, errorFunction, params);
	};

	this.getSpecialLowerSchoolExamMarksforReportCard = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getSpecialLowerSchoolExamMarksforReportCard/" + param, successFunction, errorFunction, params);
	};

	this.addReportCard = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addReportCard", successFunction, errorFunction, params);
	};

	this.deleteReportCard = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteReportCard/" + param, successFunction, errorFunction, params);
	};

	/*********** students ***********/
	this.getAllStudents = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllStudents/" + param, successFunction, errorFunction, params);
	};

	this.getAllParents = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getAllParents", successFunction, errorFunction, params);
	};

	this.exportAllStudentDetails = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/exportAllStudentDetails", successFunction, errorFunction, params);
	};

	this.studentGenderCount = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/studentGenderCount", successFunction, errorFunction, params);
	};

	this.getTeacherStudents = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getTeacherStudents/" + param, successFunction, errorFunction, params);
	};

	this.getTeacherParents = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getTeacherParents/" + param, successFunction, errorFunction, params);
	};

	this.getStudentDetails = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentDetails/" + param, successFunction, errorFunction, params);
	};

	this.getStudentBalance = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentBalance/" + param, successFunction, errorFunction, params);
	};

	this.getStudentsWithFeeBal = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getStudentsWithFeeBal", successFunction, errorFunction, params);
	};

	this.getStudentInvoices = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentInvoices/" + param, successFunction, errorFunction, params);
	};

	this.getStudentPayments = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentPayments/" + param, successFunction, errorFunction, params);
	};

	this.getStudentCredits = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentCredits/" + param, successFunction, errorFunction, params);
	};

	this.getStudentArrears = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentArrears/" + param, successFunction, errorFunction, params);
	};

	this.getStudentExamMarks = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentExamMarks/" + param, successFunction, errorFunction, params);
	};

	this.getAllStudentExamMarks = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllStudentExamMarks/" + param, successFunction, errorFunction, params);
	};

	this.getAllStudentStreamMarks = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllStudentStreamMarks/" + param, successFunction, errorFunction, params);
	};

	this.postStudent = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addStudent", successFunction, errorFunction, params);
	};

	this.updateStudent = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateStudent", successFunction, errorFunction, params);
	};

	this.addDocReport = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addDocReport", successFunction, errorFunction, params);
	};

	this.getDocReport = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getDocReport/" + param, successFunction, errorFunction, params);
	};

	this.deleteDocReport = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteDocReport/" + param, successFunction, errorFunction, params);
	};

	this.getAllGuardians = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllGuardians/" + param, successFunction, errorFunction, params);
	};

	this.getGuardiansChildren = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getGuardiansChildren/" + param, successFunction, errorFunction, params);
	};

	this.getMISLogin = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getMISLogin/" + param, successFunction, errorFunction, params);
	};

	this.postUserRequest = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/postUserRequest", successFunction, errorFunction, params);
	};

	this.checkUsername = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/checkUsername/" + param, successFunction, errorFunction, params);
	};

	this.checkIdNumber = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/checkIdNumber/" + param, successFunction, errorFunction, params);
	};

	this.checkAdmNumber = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/checkAdmNumber/" + param, successFunction, errorFunction, params);
	};

	this.getLatestAdmission = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getLatestAdmission", successFunction, errorFunction, params);
	};

	this.postGuardian = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addGuardian", successFunction, errorFunction, params);
	};

	this.updateGuardian = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateGuardian", successFunction, errorFunction, params);
	};

	this.deleteGuardian = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteGuardian/" + param, successFunction, errorFunction, params);
	};

	this.postMedicalConditions = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addMedicalConditions", successFunction, errorFunction, params);
	};

	this.addStudentDestination = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addStudentDestination", successFunction, errorFunction, params);
	};

	this.addStudentTrips = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addStudentTrips", successFunction, errorFunction, params);
	};

	this.updateMedicalConditions = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateMedicalConditions", successFunction, errorFunction, params);
	};

	this.deleteMedicalCondition = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteMedicalCondition/" + param, successFunction, errorFunction, params);
	};

	this.getStudentClasses = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentClasses/" + param, successFunction, errorFunction, params);
	};

	this.adminDeleteStudent = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/adminDeleteStudent/" + param, successFunction, errorFunction, params);
	};

    this.promoteStudents = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/promoteStudents", successFunction, errorFunction, params);
	};

	this.rmvStudentImg = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/rmvStudentImg", successFunction, errorFunction, params);
	};

	this.rmvPickUpIndividualImg = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/rmvPickUpIndividualImg", successFunction, errorFunction, params);
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
		ajaxService.AjaxPost2(request, path + "/addPayment", successFunction, errorFunction, params);
	};

	this.getPaymentDetails = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getPaymentDetails/" + param, successFunction, errorFunction, params);
	};

	this.updatePayment = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updatePayment", successFunction, errorFunction, params);
	};

	this.applyCredit = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/applyCredit", successFunction, errorFunction, params);
	};

	this.reversePayment = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/reversePayment", successFunction, errorFunction, params);
	};

	this.reactivatePayment = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/reactivatePayment", successFunction, errorFunction, params);
	};

	this.deletePayment = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deletePayment/" + param, successFunction, errorFunction, params);
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
		ajaxService.AjaxPost2(request, path + "/createInvoice", successFunction, errorFunction, params);
	};

	this.updateInvoice = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateInvoice", successFunction, errorFunction, params);
	};

	this.cancelInvoice = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/cancelInvoice", successFunction, errorFunction, params);
	};

	this.reactivateInvoice = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/reactivateInvoice", successFunction, errorFunction, params);
	};

	this.deleteInvoice = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteInvoice/" + param, successFunction, errorFunction, params);
	};

	/*********** users ***********/
	this.getUsers = function (param, successFunction, errorFunction, params) {
		if( param === undefined ) ajaxService.AjaxGet(path + "/getUsers", successFunction, errorFunction, params);
		else	ajaxService.AjaxGet(path + "/getUsers/" + param, successFunction, errorFunction, params);
	};

	this.setUserStatus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/setUserStatus", successFunction, errorFunction, params);
	};

	this.addUser = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addUser", successFunction, errorFunction, params);
	};

	this.updateUser = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateUser", successFunction, errorFunction, params);
	};

	/*********** manage blog ***********/
	this.getClassPosts = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassPosts/" + param, successFunction, errorFunction, params);
	};

	this.getBlogPostTypes = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getBlogPostTypes", successFunction, errorFunction, params);
	};

	this.getBlogPostStatuses = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getBlogPostStatuses", successFunction, errorFunction, params);
	};

	this.getPost = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getPost/" + param, successFunction, errorFunction, params);
	};

	this.addBlog = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addBlog", successFunction, errorFunction, params);
	};

	this.addPost = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addPost", successFunction, errorFunction, params);
	};

	this.updatePost = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updatePost", successFunction, errorFunction, params);
	};

	this.updateBlog = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateBlog", successFunction, errorFunction, params);
	};

	this.deletePost = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deletePost/" + param, successFunction, errorFunction, params);
	};

	this.getHomeworkPosts = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getHomeworkPosts/" + param, successFunction, errorFunction, params);
	};

	this.getHomeworkPost = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getHomeworkPost/" + param, successFunction, errorFunction, params);
	};

	this.addHomework = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addHomework", successFunction, errorFunction, params);
	};

	this.updateHomework = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateHomework", successFunction, errorFunction, params);
	};

	this.deleteHomework = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteHomework/" + param, successFunction, errorFunction, params);
	};

	this.getCommunicationOptions = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getCommunicationOptions", successFunction, errorFunction, params);
	};

	this.getTeacherCommunications = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getTeacherCommunications/" + param, successFunction, errorFunction, params);
	};

	this.getSchoolCommunications = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getSchoolCommunications", successFunction, errorFunction, params);
	};

	this.getAllFeedback = function (request, successFunction, errorFunction, params) {
	    ajaxService.AjaxGetWithData(request, path + "/getAllFeedback", successFunction, errorFunction, params);
	};

	this.addCommunication = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/addCommunication", successFunction, errorFunction, params);
	};

	this.customAddCommunication = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/customAddCommunication", successFunction, errorFunction, params);
	};

	this.getCommunication = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getCommunication/" + param, successFunction, errorFunction, params);
	};

	this.updateCommunication = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateCommunication", successFunction, errorFunction, params);
	};

	this.deleteCommunication = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxDelete(path + "/deleteCommunication/" + param, successFunction, errorFunction, params);
	};

	this.sendNotifications = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/sendNotifications", successFunction, errorFunction, params);
	};

	this.getFeedbackUnopenedCount = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getFeedbackUnopenedCount", successFunction, errorFunction, params);
	};

	this.updateOpenedFeedbackMessage = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/updateOpenedFeedbackMessage", successFunction, errorFunction, params);
	};

	this.getUnPublishedMsgCount = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getUnPublishedMsgCount", successFunction, errorFunction, params);
	};

	this.publishMessage = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/publishMessage", successFunction, errorFunction, params);
	};

	this.batchPublishMessages = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/batchPublishMessages", successFunction, errorFunction, params);
	};

	this.unPublishMessage = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/unPublishMessage", successFunction, errorFunction, params);
	};

	this.getCommunicationForSms = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getCommunicationForSms/" + param, successFunction, errorFunction, params);
	};

	/*********** Transport ***********/

	this.createSchoolBus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/createSchoolBus", successFunction, errorFunction, params);
	};

	this.getAllBuses = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllBuses/" + param, successFunction, errorFunction, params);
	};

	this.getAllAssignedBuses = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllAssignedBuses/" + param, successFunction, errorFunction, params);
	};

	this.getStudentTransportDetails = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentTransportDetails/" + param, successFunction, errorFunction, params);
	};

	this.getStudentTripOptions = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentTripOptions/" + param, successFunction, errorFunction, params);
	};

	this.getBusDestinations = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getBusDestinations/" + param, successFunction, errorFunction, params);
	};

	this.getBusDriverAndGuide = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getBusDriverAndGuide/" + param, successFunction, errorFunction, params);
	};

	this.getActiveRoutes = function (param, successFunction, errorFunction, params) {
	  ajaxService.AjaxGet(path + "/getActiveRoutes/" + param, successFunction, errorFunction, params);
	};

	this.assignBusToRoute = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/assignBusToRoute", successFunction, errorFunction, params);
	};

	this.getAllDrivers = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getAllDrivers", successFunction, errorFunction);
	};

	this.getSchoolBusRouteSharing = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getSchoolBusRouteSharing", successFunction, errorFunction);
	};

	this.getAllEmployeesExceptDrivers = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getAllEmployeesExceptDrivers", successFunction, errorFunction);
	};

	this.assignPersonnelToBus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPut(request, path + "/assignPersonnelToBus", successFunction, errorFunction, params);
	};

	this.getAllBusesRoutesAndDrivers = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllBusesRoutesAndDrivers/" + param, successFunction, errorFunction, params);
	};

	this.getDriverOrGuideRouteBusStudents = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getDriverOrGuideRouteBusStudents/" + param, successFunction, errorFunction, params);
	};

	this.getStudentsInBus = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentsInBus/" + param, successFunction, errorFunction, params);
	};

	this.getStudentsInRoute = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStudentsInRoute/" + param, successFunction, errorFunction, params);
	};

	this.createSchoolBusHistory = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/createSchoolBusHistory", successFunction, errorFunction, params);
	};

	this.getAllSchoolBusTrips = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllSchoolBusTrips", successFunction, errorFunction, params);
	};

	this.getSchoolBusTrips = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getSchoolBusTrips/" + param, successFunction, errorFunction, params);
	};

	this.getBusTrips = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getBusTrips/" + param, successFunction, errorFunction, params);
	};

	this.createSchoolBusTrip = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/createSchoolBusTrip", successFunction, errorFunction, params);
	};

	this.updateSchoolBusTrip = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/updateSchoolBusTrip", successFunction, errorFunction, params);
	};

	this.assignStudentToBus = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxPost2(request, path + "/assignStudentToBus", successFunction, errorFunction, params);
	};

	this.getAlreadyAssignedStudentsInBus = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAlreadyAssignedStudentsInBus", successFunction, errorFunction, params);
	};

	this.getBusesWithPickDropHistory = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getBusesWithPickDropHistory", successFunction, errorFunction, params);
	};

	this.getBusPickUpDropOffHistory = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getBusPickUpDropOffHistory/" + param, successFunction, errorFunction, params);
	};

	/*********** Reports ***********/

	this.getClassAnalysis = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassAnalysis/" + param, successFunction, errorFunction, params);
	};

	this.getStreamAnalysis = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getStreamAnalysis/" + param, successFunction, errorFunction, params);
	};

	this.getOverallFinancials = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getOverallFinancials/" + param, successFunction, errorFunction);
	};

	this.getOverallStudentFeePayments = function (param, successFunction, errorFunction) {
		ajaxService.AjaxGet(path + "/getOverallStudentFeePayments/" + param, successFunction, errorFunction);
	};

	this.getAllStudentsWithTransport = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getAllStudentsWithTransport", successFunction, errorFunction, params);
	};

	this.getAllStudentsInTrip = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllStudentsInTrip/" + param, successFunction, errorFunction, params);
	};

	this.getAllStudentsInTranspZone = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getAllStudentsInTranspZone", successFunction, errorFunction, params);
	};

	this.getAllStudentsWithTranspBalance = function (request, successFunction, errorFunction, params) {
		ajaxService.AjaxGetWithData(request, path + "/getAllStudentsWithTranspBalance", successFunction, errorFunction, params);
	};

	this.getClassStudentsWithTransp = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassStudentsWithTransp/" + param, successFunction, errorFunction, params);
	};

	this.getClassStudentsInBus = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassStudentsInBus/" + param, successFunction, errorFunction, params);
	};

	this.getClassStudentsInTrip = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassStudentsInTrip/" + param, successFunction, errorFunction, params);
	};

	this.getClassStudentsInTranspZone = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassStudentsInTranspZone/" + param, successFunction, errorFunction, params);
	};
	
	this.getAllStudentsInBusInTrip = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getAllStudentsInBusInTrip/" + param, successFunction, errorFunction, params);
	};
	
	this.getClassStudentsInBusInTrip = function (param, successFunction, errorFunction, params) {
		ajaxService.AjaxGet(path + "/getClassStudentsInBusInTrip/" + param, successFunction, errorFunction, params);
	};

	return this;
}]);
