 <?php
    // helpers
    include "helpers.php";
   
    // Twilio
    require "lib/twilio/Services/Twilio.php";
    $AccountSid = "AC76809471f6ff3116507257fe31f2a595";
    $AuthToken = "5ff20a1f6f13fc44e0e67689dfbe5e3c";
    $fromNumber = "441785472337";
    $client = new Services_Twilio($AccountSid, $AuthToken);

    // MySQL database
    $user = 'root';
    $password = 'root';
    $db = 'textbus';
    $host = 'localhost';
    $port = 3306;
    
    // Create connection
    $conn = mysqli_connect($host, $user, $password, $db);
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // STEP 1: GET AND SAVE DIRECTION REQUESTS IN DB

    // Parse through all messages from Twilio
    foreach ($client->account->messages as $message) {
        // Show inbound messages only
        if ($message->direction == "inbound") {
           
            // Save messages to the database if they don't already exist
            $SaveSQL = "INSERT INTO requests (Sid, message, phone) 
                        VALUES ('$message->sid', '$message->body', '$message->from') 
                            ON DUPLICATE KEY UPDATE Sid=Sid";

            if (mysqli_query($conn, $SaveSQL)) {
                //echo "New record created successfully<br>";
            } else {
                echo "Error: " . $SaveSQL . "<br>" . mysqli_error($conn);
            }

        }
    }


    // STEP 2: PARSE THROUGH THE DB AND SEND RESPONSES TO NEW REQUESTS

    // Get new requests from the database
    $GetSQL = "SELECT Sid, message, phone, status FROM requests WHERE status <> 1";
    $requests = $conn->query($GetSQL);

    //var_dump($result);

    // Process each new request
    foreach ($requests as $request) {

        // Print a log message
        echo 'A new bus directions request from ' . $request["phone"] . ' for ' .  $request["message"] . '<br>';

        // Extract location information from the message
        $locations = parseLocations($request["message"]);

        // Get the bus times 
        $buses = getDirections($locations);

        // Format the response
        $response = formatResponse($buses);

        echo $response . '<br>';

        // Send a response
        $message = $client->account->messages->create(array(
            "From"  => $fromNumber,
            "To"    => $request["phone"],
            "Body"  => $response,
        ));

        // Update the status of the request
        $requestSid = $request['Sid'];
        $UpdateSQL = "UPDATE requests SET status='1' WHERE Sid='$requestSid'";

        if ($conn->query($UpdateSQL) === TRUE) {
            //echo "Record updated successfully";
        } else {
            echo "Error updating record: " . $conn->error;
        }

    }


    // Close the DB connection   
    mysqli_close($conn);

?>