 <?php

    require "lib/twilio/Services/Twilio.php";
    include "helpers.php";

    // set your AccountSid and AuthToken from www.twilio.com/user/account
    $AccountSid = "AC76809471f6ff3116507257fe31f2a595";
    $AuthToken = "5ff20a1f6f13fc44e0e67689dfbe5e3c";
    $fromNumber = "441785472337";

    $client = new Services_Twilio($AccountSid, $AuthToken);



    // Get all inbound messages from Twilio
    foreach ($client->account->messages as $message) {
        if ($message->direction == "inbound") {
           
            // Show the original message
            //echo $message->body;
            //echo "<br>";

            $locations = parseLocations($message->body);
            print_r($locations);

            //echo $message->from;
            echo "<br>";
        }
    }









    // // Send a message
    // $message = $client->account->messages->create(array(
    //     "From" => $fromNumber,
    //     "To" => "447530936914",
    //     "Body" => "Test message!",
    // ));

    // // Display a confirmation message on the screen
    // echo "Sent message {$message->sid}";




   
?>