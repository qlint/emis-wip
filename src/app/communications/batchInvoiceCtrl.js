'use strict';

angular.module('eduwebApp').
controller('batchInvoiceCtrl', ['$scope', '$rootScope', '$uibModalInstance', 'apiService', 'dialogs', 'data','$timeout','$window',
function($scope, $rootScope, $uibModalInstance, apiService, $dialogs, data, $timeout, $window){

	var ignoreCols = ['student_id','student_name','sum','total'];
	var school = window.location.host.split('.')[0];

	$scope.isTeacher = ($rootScope.currentUser.user_type == 'TEACHER' ? true : false);

	$scope.parentsAndStudents = [];
    $scope.showTable = false;
    $scope.wait = function sleep(milliseconds) {
                                    var start = new Date().getTime();
                                    for (var i = 0; i < 1e7; i++) {
                                        if ((new Date().getTime() - start) > milliseconds){
                                            break;
                                        }
                                    }
                                 }

	var initializeController = function()
	{
		// we call some api's that will form the parameters for user selection
	    // classes API
      /*
	    apiService.getAllClasses({},
	                function(response){
    					var result = angular.fromJson(response);

    					// store these as they do not change often
    					if( result.response == 'success')
    					{
    						$scope.classes = result.data || [];
    					}
    					else
    					{
    						console.log("No class data found");
    					}

				},
				function(){
				    console.log("Error fetching data");
				}
		);
    */
    // employees to use as 'Message From'
		apiService.getAllEmployees(true, function(response)
		{
			var result = angular.fromJson(response);

			if( result.response == 'success')
			{
				$scope.employees = ( result.nodata ? {} : result.data );
			}
			else
			{
				$scope.error = true;
				$scope.errMsg = result.data;
			}

		}, apiError);

    // fee balances
    apiService.getStudentsWithFeeBal({},
      function(response){
            var result = angular.fromJson(response);

            // store these as they do not change often
            if( result.response == 'success')
            {
              $scope.feeBalances = result.data || [];

              $scope.feeBalances.forEach(function(perStudent) {
                  var theParents = JSON.parse(perStudent.parents.replace('{','[').replace(/.$/,"]"));
                  perStudent.parents = theParents;
                  var parentsArr = [];
                  perStudent.parents.forEach(function(perParent) {
                      var aParent = JSON.parse(perParent);
                      perParent = aParent;

                      var parentMessage = "Dear " + aParent.name + ", \n Please pay fee balance of Ksh." +  Math.round(Number(perStudent.balance))*-1 + " for " + perStudent.student_name + " to avoid inconveniences. Thank you.";
                      aParent.message = parentMessage;
                      parentsArr.push(perParent);
                  });
                  perStudent.parents = parentsArr;
              });
              // $scope.exportTableToCSV(feeBalances);

            }
            else
            {
              console.log("No fee data found");
              alert("There was a problem fetching fee balances. Please try again.");
            }
            $scope.showTable = true;

            // this will perform while the user is viewing data
            $scope.feeBalances.forEach(function(studentsParents) {
                studentsParents.parents.forEach(function(eachParent) {
                    eachParent.student_id = studentsParents.student_id;
                    eachParent.student_name = studentsParents.student_name;
                    $scope.parentsAndStudents.push(eachParent);
                });
            });

      },
      function(){
          console.log("Error fetching data");
      }
  );

	}
	setTimeout(initializeController,100);


	$scope.$watch('filters.class',function(newVal,oldVal){
		if( newVal == oldVal ) return;

		$scope.filters.class_id = newVal.class_id;

	});

	$scope.smsCounter = 0;

	$scope.sendBySms = function()
	{
    var selectedEmployee = $("#message_from option:selected").val();
		var selectedEmployeeName = $("#message_from option:selected").text();
	// $scope.parentsAndStudents.shift();$scope.parentsAndStudents.shift();$scope.parentsAndStudents.shift();$scope.parentsAndStudents.shift();$scope.parentsAndStudents.shift(); $scope.parentsAndStudents.length = 4; console.log($scope.parentsAndStudents); // use this to test to only one recipient
    $scope.parentsAndStudents.forEach(function(parent) {
                $scope.smsCounter++; // count the messages being processed
				var parentMessage = {
					"message_by": selectedEmployeeName,
					"message_date": new Date().toLocaleString(),
					"message_recipients":[{ "phone_number": parent.phone.replace('0','+254'), "recipient_name": parent.name }],
					"message_text": parent.message,
					"subscriber_name": school
				};
        var postObj = {};
        postObj.post = {
          com_date: new Date().toLocaleString(),
          audience_id: 5, // parent
          com_type_id: 4, // reminder
          post_status_id: 1, // published
          title: "Fee balance reminder for " + parent.student_name + ".",
          body: parent.message,
          message_from: Number(selectedEmployee),
          student_id: parent.student_id,
          guardian_id: parent.id,
          send_as_email: 'f',
          send_as_sms: 't',
          created_by: Number(selectedEmployee),
          reply_to: $rootScope.currentUser.settings["Email Address"],
          user_id: Number(selectedEmployee),
          sent: true,
          subdomain: window.location.host.split('.')[0],
          message_from_name: $( "#message_from" ).val(),
          theParentMessage: parentMessage
        };
        postObj.user_id = Number(selectedEmployee);
        console.log(postObj);
        $scope.postObj = postObj;

        // let's space out the sms's to give time to postSms.php to do it's work, so maybe 1.6 seconds
        $scope.wait(1600);

        apiService.customAddCommunication(postObj,createCompleted,apiError);

    });
		// close the modal
		alert("Fee reminder messages sent to " + $scope.parentsAndStudents.length + " guardians");
		$uibModalInstance.dismiss('canceled');
	};

  $scope.sendByApp = function()
	{
		var selectedEmployee = $("#message_from option:selected").val();
    $scope.parentsAndStudents.forEach(function(parent) {
      var postObj = {};
      postObj.post = {
        com_date: new Date().toLocaleString(),
        audience_id: 5, // parent
        com_type_id: 4, // reminder
        post_status_id: 1, // published
        title: "Fee balance reminder for " + parent.student_name + ".",
        body: parent.message,
        message_from: Number(selectedEmployee),
        student_id: parent.student_id,
        guardian_id: parent.id,
        send_as_email: 't',
        send_as_sms: 'f',
        created_by: Number(selectedEmployee),
        reply_to: $rootScope.currentUser.settings["Email Address"],
        user_id: Number(selectedEmployee),
        sent: true,
        subdomain: window.location.host.split('.')[0]
      };
      postObj.user_id = $rootScope.currentUser.user_id;
      apiService.customAddCommunication(postObj,createCompleted,apiError);
      // console.log(postObj);
      $scope.postObj = postObj;
    });
		//send notifications now
		apiService.sendNotifications({}, function(response){
			var result = angular.fromJson(response);
			console.log(result);
			if( result.response == 'success')
			{
				alert("Success. Mobile App Notifications Have Been Sent To Respective Parents!");
				// close the modal
	      $uibModalInstance.dismiss('canceled');
			}

		}, function(response){
			alert("App Notifications Have Been Sent To Respective Parents!");
			console.log("Notifications error:",response);
		});

	};

	$scope.cancel = function()
	{
		$uibModalInstance.dismiss('canceled');
	}; // end cancel

	var initDataGrid = function()
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
				order: [2,'asc'],
				filter: true,
				info: false,
				sorting:[],
				initComplete: function(settings, json) {
					$scope.loading = false;
					$rootScope.loading = false;
					$scope.$apply();
				},
				language: {
						search: "Search Results<br>",
						searchPlaceholder: "Filter",
						lengthMenu: "Display _MENU_",
						emptyTable: "No students found."
				},
			} );


		var headerHeight = $('.navbar-fixed-top').height();
		//var subHeaderHeight = $('.subnavbar-container.fixed').height();
		var searchHeight = $('#body-content .content-fixed-header').height();
		var offset = ( $rootScope.isSmallScreen ? 22 : 13 );
		new $.fn.dataTable.FixedHeader( $scope.dataGrid, {
				header: true,
				headerOffset: (headerHeight + searchHeight) + offset
			} );


		// position search box
		if( !$rootScope.isSmallScreen )
		{
			var filterFormWidth = $('.dataFilterForm form').width();

			$('#resultsTable_filter').css('left',filterFormWidth+55);
		}

		$window.addEventListener('resize', function() {

			$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
			if( $rootScope.isSmallScreen )
			{
				$('#resultsTable_filter').css('left',0);
			}
			else
			{
				var filterFormWidth = $('.dataFilterForm form').width();

				$('#resultsTable_filter').css('left',filterFormWidth-30);
			}
		}, false);

	}

	$scope.printExams = function()
	{
		$('#resultsTable2').printThis({
    				debug: false,
        		    importCSS: true,
        		    importStyle: true,
        		    printContainer: true,
        		    loadCSS: "css/printMarkSheet.css",
        		    pageTitle: "Student Fee Balances Message List",
        		    removeInline: false,
        		    printDelay: 333,
        		    header: null,
        		    formValues: true
          });
	};

	//download table as CSV
	function downloadCSV(csv, filename) {
        var csvFile;
        var downloadLink;

        // CSV file
        csvFile = new Blob([csv], {type: "text/csv"});

        // Download link
        downloadLink = document.createElement("a");

        // File name
        downloadLink.download = filename;

        // Create a link to the file
        downloadLink.href = window.URL.createObjectURL(csvFile);

        // Hide download link
        downloadLink.style.display = "none";

        // Add the link to DOM
        document.body.appendChild(downloadLink);

        // Click download link
        downloadLink.click();
    }

    $scope.exportTableToCSV = function(filename) {
        //we first hide the grade weights (out-of's)
        var elements = document.getElementsByClassName('input-group-addon')

        for (var i = 0; i < elements.length; i++){
            elements[i].style.display = 'none';
        }
        //the download
        var csv = [];
        var rows = document.querySelectorAll("table tr");

        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");

            for (var j = 0; j < cols.length; j++)
                row.push(cols[j].innerText);

            csv.push(row.join(","));
        }

        // Download CSV file
        downloadCSV(csv.join("\n"), filename);
    }

    //download table as XLS
    $scope.exportTableToXLS = function() {
       //Creates new Generator
       excel = new ExcelGen({
           "src_id": "resultsTable2",
           "show_header": true,
           "file_name": "Student Fee Balances Message List"
       });
       //Generates Excel Output and sends download to the browser.
       excel.generate();
    }

	var createCompleted = function ( response, status, params )
	{
        console.log(response);

		var result = angular.fromJson( response );
		if( result.response == 'success' )
		{
		    if($scope.postObj.send_as_sms = 't'){
                // post the message

                $.ajax({
                        type: "POST",
                        url: "https://" + window.location.host.split('.')[0] + ".eduweb.co.ke/srvScripts/postSms.php",
                        data: { src: result.com_id, school: window.location.host.split('.')[0] },
                        success: function (data, status, jqXHR) {
                            console.log("Data posted for processing.",data,status,jqXHR);
                        },
                        error: function (xhr) {
                            console.log("Error. Data not posted.");
                        }
                });

            }

            if($scope.parentsAndStudents.length == $scope.smsCounter){
    			$uibModalInstance.close();
    			var msg = ($scope.edit ? 'Batch Exam SMS has been sent.' : 'Batch Exam SMS has been sent.');
            }
			if( data.viewing !== undefined && data.viewing == 'report')  $rootScope.$emit('examMarksAdded2', {'msg' : msg, 'clear' : true});
			else $rootScope.$emit('examMarksAdded', {'msg' : msg, 'clear' : true});
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

} ]);
