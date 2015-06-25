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
    $db = 'inventory';
    $host = 'localhost';
    $port = 3306;

    $link = mysqli_init();
    $success = mysqli_real_connect(
       $link, 
       $host, 
       $user, 
       $password, 
       $db,
       $port
    );



    // START

    // Parse through all messages from Twilio
    foreach ($client->account->messages as $message) {
        // Show inbound messages only
        if ($message->direction == "inbound") {
           
            // Show the original message
            //echo $message->body;
            //echo "<br>";

            // Parse locations from SMS
            $locations = parseLocations($message->body);

            $buses = getDirections($locations);

            echo formatResponse($buses);

            //break;



            // Sender number
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