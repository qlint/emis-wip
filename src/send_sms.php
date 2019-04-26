<!--
NOTICE: To only view the data on this file on a browser, disable your internet connection,
else SMS messeges will be sent out

Also set the SMS API url and subscriber name in the <script> tag at the bottom of the page
on this -> "subscriber_name": "xxxxx"
and this -> var url = "http://xxxxx";
-->
<?php
header('Access-Control-Allow-Origin: *');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SMS</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<style>
    body,h3,table{margin-left: 15px;}
</style>
<body>
    <h3>List of all recipients of the last sms message sent.</h3><hr>
    <?php
    // include __DIR__ '/../api/lib/db.php';
    $getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
    $db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");
    $result = pg_query($db,"SELECT communication_sms.com_id, communication_sms.creation_date as message_date, communications.message as message_text,
                    employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as message_by, communication_sms.first_name ||' ' || communication_sms.last_name AS recipient_name,
                    communication_sms.sim_number AS phone_number
                FROM app.communication_sms
                INNER JOIN app.communications ON communication_sms.com_id = communications.com_id
                INNER JOIN app.employees ON communications.message_from = employees.emp_id
                WHERE communication_sms.com_id = (SELECT last_value FROM app.communications_com_id_seq)");


                $col1 = NULL;
                $col2 = NULL;
                $col3 = NULL;
                echo "<table id='table'>";
                while ($row = pg_fetch_assoc($result)) {
                    $text1 = '';
                    $text2 = '';
                    $text3 = '';
                    if ($row['message_by'] != $col1 ||
                        $row['message_date'] != $col2 ||
                        $row['message_text'] != $col3) {
                        $col1 = $row['message_by'];
                        $col2 = $row['message_date'];
                        $col3 = $row['message_text'];
                        $text1 = $col1;
                        $text2 = $col2;
                        $text3 = $col3;
                    }
                    echo "<tr>";
                    echo "<td align='left' width='200'>" . $text1 . "</td>";
                    echo "<td align='left' width='200'>" . $text2 . "</td>";
                    echo "<td align='left' width='200'>" . $text3 . "</td>";
                    echo "<td align='left' width='200'>" . 0 . $row['phone_number'] . "</td>";
                    echo "<td align='left' width='200'>" . $row['recipient_name'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<h1 id='subscriber'>" . array_shift((explode('.', $_SERVER['HTTP_HOST']))). "</h1>";
    ?>

    <script  type="text/javascript">
        var school = document.getElementById('subscriber');
        var schoolname = school.innerHTML;
        var table = document.getElementById('table');

        var colmn = table.rows[0].cells;

        var message = {
            "message_by": colmn[0].innerHTML,
            "message_date": new Date(),
            "message_recipients": [],
            "message_text": colmn[2].innerHTML,
            "subscriber_name": schoolname
        };

        for (var i = 0, row; row = table.rows[i]; i++) {
            colmn = row.cells;

            message.message_recipients.push({
                "phone_number": colmn[3].innerHTML,
                "recipient_name": colmn[4].innerHTML
            });
        }
        // console.log(message);

        // We determine the number of keys/properties (recipients in this case) in the object
        var recipientLength = Object.keys(message.message_recipients).length;
        // console.log("The message has (" + recipientLength + ") keys.");

        // Create a variable to divide the above object to a predetermined number less than 100.
        // This is because somehow sending a message to recipients greater than 99 causes
        // the message to fail, so we split the recipients to groups of 99
        var messageRep = message.message_recipients.slice();
        for(var i = 0; i < recipientLength; i+=80){
            message.message_recipients = messageRep.slice(i, i+80);
            // console.log(message.message_recipients);
            // We can now create a new message object using the smaller recipient groups
            var newMessage = {
              "message_by": message.message_by,
              "message_date": message.message_date,
              "message_recipients": message.message_recipients,
              "message_text": message.message_text,
              "subscriber_name": message.subscriber_name
            };
            console.log(newMessage);

            // Post the message
              var url = "http://41.72.203.166/sms_api_staging/api/sendBulkSms";
              $.ajax({
                      type: "POST",
                      url: url,
                      data: JSON.stringify(newMessage),
                      contentType: "application/json; charset=utf-8",
                      dataType: "json",
                      processData: true,
                      success: function (data, status, jqXHR) {
                          console.log(data);
                          console.log(status);
                          console.log(jqXHR);
                          //alert("success..." + data);
                          //alert("Success. Message sent.");
                      },
                      error: function (xhr) {
                          //console.log(xhr.responseText);
                          // Do not alert() an error message to the user as often times the api
                          // may delay with a response therefore output an error. This is a false negative
                          // since the messages are already successfully sent.
                          // alert("Success. Message Sent.");
                      }
              });
        }
        
        alert("Success. " + recipientLength + " message(s) sent.");
        
    </script>
<h1><?php echo $subDomain; ?></h1>
</body>
</html>
