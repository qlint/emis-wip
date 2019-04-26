$(document).ready(function() {
    //this gets the select options based on another select
	$("#classes").change(function() {
		var class_id = $(this).val();
		if(class_id != "") {
			$.ajax({
				url:"ajax/get_examTypes.php",
				data:{c_id:class_id},
				type:'POST',
				success:function(response) {
					var resp = $.trim(response);
					$("#examTypes").html(resp);
				}
			});
		} else {
			$("#examTypes").html("<option value=''>------- Select --------</option>");
		}
	});
});

//this gets the values posted and returns the table headers for class analysis
function getTitle() {
    
    console.log("Executing function...");
    
    //get the submitted values
    var termVal = parseInt($('#termVal').val());
    var classVal = parseInt($('#classes').val());
    var examVal = parseInt($('#examTypes').val());
    
    
    var params = {
        "term": termVal,
        "class": classVal,
        "exam": examVal
    }
    
    if(termVal != "" && classVal != "" && examVal != ""){
        
        console.log("Proceeding...");
        
        $.ajax({
			url:"ajax/get_documentTitle.php",
			data:{c_id:JSON.stringify(params)},
			type:'POST',
			success:function(response) {
				var resp = $.trim(response);
				console.log("AJX Success:: " + response);
				
				console.log(resp);
				localStorage.setItem('clicked', resp);
				var clicked = localStorage.getItem('clicked');
				console.log("Clicked :: " + clicked);
    			// $("#expTitle").html(clicked);
			}
		});
    }else{
        console.log("AJX error");
        $("#expTitle").html("Select a Term, Class and Exam");
    }
}
	
//this gets the values posted and returns the table headers for stream analysis
function getStreamTitle() {
    
    console.log("Executing function...");
    
    //get the submitted values
    var termVal = parseInt($('#termVal').val());
    var classVal = parseInt($('#classes').val());
    
    
    var params = {
        "term": termVal,
        "class": classVal
    }
    
    if(termVal != "" && classVal != ""){
        
        console.log("Proceeding...");
        
        $.ajax({
			url:"ajax/get_streamDocumentTitle.php",
			data:{c_id:JSON.stringify(params)},
			type:'POST',
			success:function(response) {
				var resp = $.trim(response);
				console.log("AJX Success:: " + response);
				
				console.log(resp);
				localStorage.setItem('clicked', resp);
				var clicked = localStorage.getItem('clicked');
				console.log("Clicked :: " + clicked);
    			// $("#expTitle").html(clicked);
			}
		});
    }else{
        console.log("AJX error");
        $("#expTitle").html("Select a Term and Class");
    }
}