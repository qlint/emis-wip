'use strict';

angular.module('eduwebApp').
controller('createResourceCtrl', ['$scope', '$rootScope', 'apiService','$timeout','$window','$q','$parse','$sce',
function($scope, $rootScope, apiService, $timeout, $window, $q, $parse,$sce){

	var initialLoad = true;
	$scope.filters = {};
	$scope.filters.status = 'true';
	$scope.filterShowing = false;
	$scope.toolsShowing = false;
	var currentStatus = true;
	var isFiltered = false;
	$rootScope.modalLoading = false;
	$scope.alert = {};
	$scope.getReport = "examsTable";
	$scope.setListener = false;
	$scope.showSave = false;
	$scope.isEdit = false;
	$scope.editButtonTxt = ($scope.isEdit == true ? "Save" : "Edit");
	$scope.editButtonStyle = ($scope.isEdit == true ? "success" : "primary");
	$scope.schoolDir = window.location.host.split('.')[0];
	$scope.showVimeoFrame = false;

	$scope.copyLink = function() {
          var copyText = document.getElementById("linkUrl");
          /* Select the text field */
          copyText.select();
          copyText.setSelectionRange(0, 99999); /*For mobile devices*/
          /* Copy the text inside the text field */
          document.execCommand("copy");
          console.log("Copied the text: " + copyText.value);
    }

	var initializeController = function ()
	{
		// get classes
		var requests = [];

		var deferred = $q.defer();
		requests.push(deferred.promise);

		if( $rootScope.allClasses === undefined )
		{
			if ( $rootScope.currentUser.user_type == 'TEACHER' )
			{
				apiService.getTeacherClasses($rootScope.currentUser.emp_id, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						deferred.resolve();
					}
					else
					{
						deferred.reject();
					}

				}, function(){deferred.reject();});
			}
			else
			{
				apiService.getAllClasses({}, function(response){
					var result = angular.fromJson(response);

					// store these as they do not change often
					if( result.response == 'success')
					{
						$scope.classes = result.data || [];
						$scope.filters.class = $scope.classes[0];
						$scope.filters.class_id = ( $scope.classes[0] ? $scope.classes[0].class_id : null);
						$scope.filters.class_cat_id = ( $scope.classes[0] ? $scope.classes[0].class_cat_id : null);
						deferred.resolve();
					}
					else
					{
						deferred.reject();
					}

				}, function(){deferred.reject();});
			}
		}
		else
		{
			$scope.classes = $rootScope.allClasses;
			$scope.filters.class = $scope.classes[0];
			$scope.filters.class_id = $scope.classes[0].class_id;
			$scope.filters.class_cat_id = $scope.classes[0].class_cat_id;
			deferred.resolve();
		}


		// get terms
		var deferred2 = $q.defer();
		requests.push(deferred2.promise);
		if( $rootScope.terms === undefined )
		{
			apiService.getTerms(undefined, function(response,status)
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
					deferred2.resolve();
				}
				else
				{
					deferred2.reject();
				}

			}, function(){deferred2.reject();});
		}
		else
		{
			$scope.terms = $rootScope.terms;
			var currentTerm = $scope.terms.filter(function(item){
				if( item.current_term ) return item;
			})[0];
			$scope.filters.term_id = currentTerm.term_id;
			deferred2.resolve();
		}

		apiService.getTeacherResources($rootScope.currentUser.emp_id, function(response,status){
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
					$scope.teacherResources = ( result.nodata ? [] : result.data );
					if($scope.teacherResources.length > 0){
						for(let r=0;r < $scope.teacherResources.length;r++){
							let theDate = $scope.teacherResources[r].creation_date.split(' ')[0];
							let dateSpecifics = theDate.split('-');
							let theYear = dateSpecifics[0];
							let theMonth = dateSpecifics[1];
							let theDay = dateSpecifics[2];

							let months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
							let dayPostfix = ['st','nd','rd','th'];

							let dayDate = null;
							if(theDay == 1 || theDay == 21){ dayDate = theDay + dayPostfix[0]; }
							else if(theDay == 2 || theDay == 22){ dayDate = theDay + dayPostfix[1]; }
							else if(theDay == 3 || theDay == 23){ dayDate = theDay + dayPostfix[2]; }
							else{ dayDate = theDay + dayPostfix[3]; }

							if(theMonth == 1){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[0] + ', ' + theYear;
							}else if(theMonth == 2){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[1] + ', ' + theYear;
							}else if(theMonth == 3){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[2] + ', ' + theYear;
							}else if(theMonth == 4){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[3] + ', ' + theYear;
							}else if(theMonth == 5){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[4] + ', ' + theYear;
							}else if(theMonth == 6){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[5] + ', ' + theYear;
							}else if(theMonth == 7){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[6] + ', ' + theYear;
							}else if(theMonth == 8){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[7] + ', ' + theYear;
							}else if(theMonth == 9){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[8] + ', ' + theYear;
							}else if(theMonth == 10){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[9] + ', ' + theYear;
							}else if(theMonth == 11){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[10] + ', ' + theYear;
							}else if(theMonth == 12){
								$scope.teacherResources[r].resource_date = dayDate + ' ' + months[11] + ', ' + theYear;
							}

						}
					}
					setTimeout(function(){
						for(let r=0; r < $scope.teacherResources.length;r++){
							for(let c=0; c < $scope.classes.length;c++){
								if($scope.classes[c].class_id == $scope.teacherResources[r].class_id){
									$scope.teacherResources[r].class_name = $scope.classes[c].class_name;
								}
							}
						}
					}, 2000);
			}
			else
			{
					$scope.error = true;
					$scope.errMsg = result.data;
			}

		}, apiError);

	}
	$timeout(initializeController,1);

	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.filters.class_id = newVal.class_id;

	});

	// hide the resource setup until the class and term are selected
	setTimeout(function(){
		var resourceView = document.getElementsByClassName("resourceView")[0];

		var resourceInputs = document.getElementsByClassName("enswitch");
		for (var i = 0; i < resourceInputs.length; i++) {
		    resourceInputs[i].disabled = true;
		}
	}, 1500);

	$scope.setUpResource = function(){
	    //we create an object to store the selected parameters
	    $scope.selectedResourceParams = {
	        class_id: $scope.filters.class_id,
	        term_id: $scope.filters.term_id
	    };
			var resourceInputs = document.getElementsByClassName("enswitch");
			for (var j = 0; j < resourceInputs.length; j++) {
			    resourceInputs[j].disabled = false;
			}
	}

	$scope.createResource = function()
	{
			$scope.fieldErrMessage = null;
			// acquire the input values
			var resource_name = $( "#resource_name" ).val();
			var resource_type = $( "#resource_type" ).val().toUpperCase();
			$scope.resourceFile = document.getElementById('file_name').files[0];
			var file_name = $scope.resourceFile.name;
			var additional_notes = $( "#additional_notes" ).val();

			var resourceData = {
					"class_id": $scope.filters.class_id,
					"term_id": $scope.filters.term_id,
					"resource_name": resource_name,
					"resource_type": resource_type,
					"file_name": file_name,
					"additional_notes": additional_notes,
					"teacher_id": $rootScope.currentUser.emp_id
			};
			console.log($rootScope.currentUser.emp_id,resourceData);

			if(resource_name == null || resource_name == ''){
				$scope.fieldErrMessage = "Please enter the name of the resource before saving.";
			}else if(resource_type == null || resource_type == ''){
				$scope.fieldErrMessage = "Please select the type of resource before saving.";
			}else if(file_name == null || file_name == ''){
				$scope.fieldErrMessage = "Please select the file for the resource before saving.";
			}

			var addResourceSuccess = function ( response, status, params )
		  {
						// first upload the file before proceeding
						var formData = new FormData();
						formData.append('files[]', $scope.resourceFile);

						fetch('srvScripts/uploadResource.php', {
					    method: 'POST',
					    body: formData,
					  }).then(response => {
					    console.log(response);
					  });
						// end file upload

		    		var result = angular.fromJson( response );
		    		if( result.response == 'success' )
		    		{
		    		    resource_name = $( "#resource_name" ).val(null);
                        resource_type = $( "#resource_type" ).val(null);
                        document.getElementById('file_name').value = null;
                        additional_notes = $( "#additional_notes" ).val(null);
		        	    $timeout(initializeController,1);
		    		}
		    		else
		    		{
		    			$scope.error = true;
		    			$scope.errMsg = result.data;
		    		}
		  }
			apiService.createResource(resourceData,addResourceSuccess,apiError);
	}

	$scope.updateResource = function(){
		console.log("Editing resource");

		if($scope.isEdit == false){
			var resourceEdit = document.getElementsByClassName("enEdit");
			for (var i = 0; i < resourceEdit.length; i++) {
			    resourceEdit[i].disabled = false;
			}
		}else{
			var params = {
				title: $scope.resourceTitle,
				additional_text: $scope.resourceAdditionalText,
				resource_id: $scope.resourceId
			}
			console.log(params);
			apiService.updateResource(params,function ( response, status, params )
		    	{
		    		var result = angular.fromJson( response );
		    		if( result.response == 'success' )
		    		{
		        	$timeout(initializeController,1);
							var modal = document.getElementById("resourceModal");
							modal.style.display = "none";
							$scope.isEdit = false;
							$scope.editButtonTxt = ($scope.isEdit == true ? "Save" : "Edit");
							$scope.editButtonStyle = ($scope.isEdit == true ? "success" : "primary");
		    		}
		    		else
		    		{
		    			$scope.error = true;
		    			$scope.errMsg = result.data;
		    		}
		    	},apiError);
		}
	}

	$scope.saveUpdate = function(){
		$scope.isEdit = true;
		$scope.editButtonTxt = ($scope.isEdit == true ? "Save" : "Edit");
		$scope.editButtonStyle = ($scope.isEdit == true ? "success" : "primary");
	}

	$scope.openSrc = function(el){
		console.log(el.resource,$scope);
		$scope.filters.class_id = el.resource.class_id;

		$scope.resourceTitle = el.resource.resource_name;
		$scope.resourceAdditionalText = el.resource.additional_text;
		$scope.resourceClassName = el.resource.class_name;
		$scope.resourceDate = el.resource.resource_date;
		$scope.resourceType = el.resource.resource_type;
		$scope.resourceFile = el.resource.file_name;
		$scope.resourceId = el.resource.resource_id;
		$scope.vimeoPath = el.resource.vimeo_path;
		console.log($scope.vimeoPath);
		$scope.showVimeoFrame = ($scope.vimeoPath == null ? false : true);
		$scope.vimeoId = $sce.trustAsResourceUrl("https://player.vimeo.com/video/" + $scope.vimeoPath + "?color=33cc33&portrait=0"); // 403128423
		console.log("Uploaded to vimeo? " + $scope.showVimeoFrame + ($scope.showVimeoFrame? ", So show vimeo player.":", so show html video player."));
		let fileSplit = $scope.resourceFile.split('.');
		let fileExtension = fileSplit[fileSplit.length - 1];
		console.log($scope.vimeoId,fileExtension);

		if(fileExtension == 'mp4' || fileExtension == 'm4v' || fileExtension == 'avi' || fileExtension == 'wmv' || fileExtension == 'flv' || fileExtension == 'webm' || fileExtension == 'f4v' || fileExtension == 'mov'){
			$scope.actualFileType = 'video';
			$scope.resourceIcon="video-icon.png";
			$scope.assetSubDir = "videos";
		}else if(fileExtension == 'mp3' || fileExtension == 'm4a' || fileExtension == 'wav' || fileExtension == 'wma' || fileExtension == 'aac' || fileExtension == 'ogg' || fileExtension == '3gp' || fileExtension == 'f4a' || fileExtension == 'flacc' || fileExtension == 'midi'){
			$scope.actualFileType = 'audio';
			$scope.resourceIcon="audio-icon.png";
			$scope.assetSubDir = "audios";
		}else if(fileExtension == 'jpg' || fileExtension == 'jpeg' || fileExtension == 'gif' || fileExtension == 'png' || fileExtension == 'tiff'){
			$scope.actualFileType = 'audio';
			$scope.resourceIcon="img-icon.png";
			$scope.assetSubDir = "images";
		}else if(fileExtension == 'pdf'){
			$scope.actualFileType = 'pdf';
			$scope.resourceIcon="pdf-icon.png";
			$scope.assetSubDir = "documents";
		}else if(fileExtension == 'doc' || fileExtension == 'docx' || fileExtension == 'odf' || fileExtension == 'xls' || fileExtension == 'xlsx' || fileExtension == 'csv'){
			$scope.actualFileType = 'document';
			$scope.resourceIcon="doc-icon.png";
			$scope.assetSubDir = "documents";
		}
		// View resource modal
		var modal = document.getElementById("resourceModal");

		// Get the <span> element that closes the modal
		var span = document.getElementsByClassName("clze")[0];

		// When the user clicks on the resource row (<li>), open the modal
		modal.style.display = "block";

		// When the user clicks on <span> (x), close the modal
		span.onclick = function() {
		  modal.style.display = "none";
		}

		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
		  if (event.target == modal) {
		    modal.style.display = "none";
		  }
		}

	}

	$scope.sendToApp = function(){
	    // post object
	    var link = 'https://classroom.eduweb.co.ke/' + $scope.schoolDir + '/' + $scope.assetSubDir + '/' + $scope.resourceFile;
	    console.log("The link is = "+link);
	    $scope.postObj = {
	        post : {
        	            title: $scope.resourceTitle,
        	            body: $scope.resourceAdditionalText + "\n\n GET IT HERE: " + "<a href='"+link+"'>" + $scope.resourceTitle + "</a>",
        	            audience_id: 2, // class specific
        	            com_type_id: 1, // 1=general
        	            emp_id: $rootScope.currentUser.emp_id,
        	            class_id: $scope.filters.class_id,
        	            send_as_email: 't',
        	            send_as_sms: 'f',
        	            reply_to: $rootScope.currentUser.settings["Email From"],
        	            post_status_id: 1, // 1=published, 0=draft
        	            message_from: $rootScope.currentUser.emp_id,
        	            sent: true,
        	            user_id: $rootScope.currentUser.user_id,
        	            subdomain: $scope.schoolDir
        	        },
        	 user_id : $rootScope.currentUser.user_id
	    }
	    console.log($scope.postObj);
	    apiService.customAddCommunication($scope.postObj,function ( response, status, params )
                                                    	{
                                                            console.log(response);
                                                    		var result = angular.fromJson( response );
                                                    		if( result.response == 'success' )
                                                    		{
                                                                alert("The resource has been published to the parents mobile app successfully.");
                                                                $.ajax({
                                                                    type: "POST",
                                                                    url: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/srvScripts/postNotifications.php",
                                                                    data: { school: window.location.host.split('.')[0] },
                                                                    success: function (data, status, jqXHR) {
                                                                        console.log("Notifications initiated.",data,status,jqXHR);
                                                                    },
                                                                    error: function (xhr) {
                                                                        console.log("Error. Notifications could not be sent.");
                                                                    }
                                                                });
                                                                /*
                                                                apiService.sendSchoolNotifications($scope.schoolDir, function(response){
																																	var result = angular.fromJson(response);
																																	if( result.response == 'success'){ console.log("Notifications sent to parties!"); }
																																}, apiError);
																*/
                                                    		}
                                                    		else
                                                    		{
                                                    			console.log(result);
                                                    		}
                                                    	},apiError);
	}

	var setSearchBoxPosition = function()
	{
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();
			$('#resultsTable_filter').css('left',filterFormWidth+55);
		}
	}

	$scope.toggleFilter = function()
	{
		$scope.filterShowing = !$scope.filterShowing;

		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}

	$scope.toggleTools = function()
	{
		$scope.toolsShowing = !$scope.toolsShowing;

		if( $scope.filterShowing || $scope.toolsShowing )
		{
			$('#resultsTable_filter').hide();
		}
		else
		{
			$timeout( function()
			{
				$('#resultsTable_filter').show()
			},500);
		}
	}

	var apiError = function (response, status)
	{
		var result = angular.fromJson( response );
		$scope.error = true;
		$scope.errMsg = result.data;
	}


} ]);
