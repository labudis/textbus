 <?php

    require "lib/twilio/Services/Twilio.php";

    // set your AccountSid and AuthToken from www.twilio.com/user/account
    $AccountSid = "AC76809471f6ff3116507257fe31f2a595";
    $AuthToken = "5ff20a1f6f13fc44e0e67689dfbe5e3c";
    $fromNumber = "441785472337";

    $client = new Services_Twilio($AccountSid, $AuthToken);





    // Loop over the list of messages and echo a property for each one
    foreach ($client->account->messages as $message) {
        if ($message->direction == "inbound") {
            echo $message->body;
            echo $message->from;
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