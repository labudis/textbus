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
    saveMessages($client->account->messages, $conn);

    // STEP 2: PARSE THROUGH THE DB AND SEND RESPONSES TO NEW REQUESTS

    // Get new requests
    $requests = getNewRequests($conn);

    // Process each new request
    foreach ($requests as $request) {

        // Print a log message
        echo 'A new bus directions request from ' . $request["phone"] . ' for ' .  $request["message"] . '<br>';

        // Determine the request type
        $request = analyseRequest($request);

        print_r($request);

        if ($request["type"] == 'directions') {
            
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

                //Send a response
                try {
                    $message = $client->account->messages->create(array(
                        "From"  => $fromNumber,
                        "To"    => $request["phone"],
                        "Body"  => $response,
                    ));
                } catch (Services_Twilio_RestException $e) {
                    echo $e->getMessage();
                }

                // Update request status
                updateRequestStatus($request['Sid'], $message->status, $conn);


            } else {
                echo "Sending is turned off. Please check the settings!<Br>";
            }

        } else {

            updateLocations($request, $conn);

        }



    }


    // Close the DB connection   
    mysqli_close($conn);

?>