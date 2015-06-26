 <?php

	//Helpers

	// Parse locations from SMS
	 function parseLocations($text) {
		
	    // Parse out origin and destination
	    if (strpos($text,'to') !== false){ 

	    	$array = preg_split('/To|to/', $text);

	    	$locations['origin'] = $array[0]; 
	    	$locations['destination'] = $array[1]; 

	    	return $locations;
	    } else {
	    	return false;
	    }
	}


	// Make our API request
	function getJSON($url) {

		// Get data
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$return = curl_exec($curl);
		curl_close($curl);

		// Display the output
		//print_r($return);

		// Format to JSON output
		$json = json_decode($return);

		return $json;
	}


	// Save new messages to the database
	function saveMessages($messages) {

	    // Parse through all messages from Twilio
	    foreach ($client->account->messages as $message) {
	        // Show inbound messages only
	        if ($message->direction == "inbound") {

	            // Strip apostrophes
	            $messageBody = str_replace("'", "", $message->body);
	           
	            // Save messages to the database if they don't already exist
	            $SaveSQL = "INSERT INTO requests (Sid, message, phone) 
	                        VALUES ('$message->sid', '$messageBody', '$message->from') 
	                            ON DUPLICATE KEY UPDATE Sid=Sid";

	            if (mysqli_query($conn, $SaveSQL)) {
	                //echo "New record created successfully<br>";
	            } else {
	                echo "Error: " . $SaveSQL . "<br>" . mysqli_error($conn);
	            }

	        }
	    }

	}


	// Get directions based on origin and destination
	function getDirections($locations) {

		// Prepare the array
		$buses = array();

		// Our parameters
		$params = array(
			'key'			=> 'AIzaSyAc3DkAB-03kNJVxCj5I_Wh3xdVDgGENjE',
		    'origin'        => $locations['origin'],
		    'destination'   => $locations['destination'],
		    'region'		=> 'gb',
		    'sensor'        => 'false',
		    'mode'   		=> 'transit',
		    'transit_mode'  => 'bus',
		   // 'transit_routing_preference' => 'fewer_transfers',
		    'departure_time' => 'now',
		    'units'         => 'imperial'
		);
		     
		// Join parameters into URL string
		foreach($params as $var => $val){
		    $params_string .= '&' . $var . '=' . urlencode($val);  
		}
		     
		// Request URL
		$url = "https://maps.googleapis.com/maps/api/directions/json?".ltrim($params_string, '&');

		// Call the API
		$directions = getJSON($url);

		//var_dump($directions);

		// Extract bus information only
		foreach ($directions->routes[0]->legs[0]->steps as $steps){

			// Get the bus details
			if ($steps->travel_mode == "TRANSIT" ) {

				$bus = new stdClass();
				
				$bus->name_short		= $steps->transit_details->line->short_name;
				$bus->name_long 		= $steps->transit_details->line->name;
				$bus->headsign			= $steps->transit_details->headsign;
				$bus->departure_time 	= $steps->transit_details->departure_time->text;
				$bus->stop_name 		= $steps->transit_details->departure_stop->name;

				$buses[] = $bus;
						
			}
		}

		return $buses;

	}


	// Format the response
	function formatResponse($buses) {

		//var_dump($buses);

		$bus = $buses[0];

		if ($bus) {
			$response = 'Bus ' . $bus->name_short . ' for ' . $bus->headsign	. ' at ' . $bus->departure_time . ' from ' . $bus->stop_name;
		} else {
			$response = 'Sorry, there is no bus nearby';
		}
		
		return $response;

	}


	// Update the request status to 1 after the message has been sent
	function updateRequestStatus($requestSid, $conn) {

	    // Update the status of the request
        $UpdateSQL = "UPDATE requests SET status='1' WHERE Sid='$requestSid'";

        if ($conn->query($UpdateSQL) === TRUE) {
            //echo "Record updated successfully";
        } else {
            echo "Error updating record: " . $conn->error;
        }
	}


 ?>