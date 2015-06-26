 <?php
    // Include helpers and configuration settings
    include "helpers.php";
    include "config.php";
   
    // Twilio
    require "lib/twilio/Services/Twilio.php";
    $client = new Services_Twilio($AccountSid, $AuthToken);

    // Connect to Database
    $conn = mysqli_connect($host, $user, $password, $db);
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // STEP 1: GET AND SAVE DIRECTION REQUESTS IN DB
    saveMessages($client->account->messages);

    // STEP 2: PARSE THROUGH THE DB AND SEND RESPONSES TO NEW REQUESTS
    $GetSQL = "SELECT Sid, message, phone, status FROM requests WHERE status <> 1";
    $requests = $conn->query($GetSQL);

    // Process each new request
    foreach ($requests as $request) {

        // Print a log message
        echo 'A new bus directions request from ' . $request["phone"] . ' for ' .  $request["message"] . '<br>';

        // Extract location information from the message
        $locations = parseLocations($request["message"]);

        // Get the bus times 
        $buses = getDirections($locations);

        //print_r($buses);

        // Format the response
        $response = formatResponse($buses);
        echo $response . '<br>';

        // STEP 3: SEND THE MESSAGES
        if ($sendingON == true) {

            // Send a response
            // $message = $client->account->messages->create(array(
            //     "From"  => $fromNumber,
            //     "To"    => $request["phone"],
            //     "Body"  => $response,
            // ));

            updateRequestStatus($request['Sid'], $conn);


        } else {
            echo "Sending is turned off. Please check the settings!<Br>";
        }

    }


    // Close the DB connection   
    mysqli_close($conn);

?>