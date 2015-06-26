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

	// Plural or singular
	function plural( $amount, $singular = '', $plural = 's' ) {
	    if ( $amount == 1 )
	        return $singular;
	    else
	        return $plural;
	}

	// Check what the string starts with
	function startsWith($haystack, $needle) {
	    // search backwards starting from haystack length characters from the end
	    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	// Check what the string ends with
	function endsWith($haystack, $needle) {
	    // search forward starting from end minus needle length characters
	    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}


	function cleanMessage($message) {

		// Turn to lower case
		$message = strtolower ($message);

		// Remove apostrophes
		$message = str_replace("'", "", $message);

		return $message;
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
	function saveMessages($messages, $conn) {

	    // Parse through all messages from Twilio
	    foreach ($messages as $message) {

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

	            // Save users to the database
                $SaveSQL = "INSERT INTO users (phone)
                    			VALUES ('$message->from')
                    				ON DUPLICATE KEY UPDATE phone=phone";

                if (mysqli_query($conn, $SaveSQL)) {
	                //echo "New record created successfully<br>";
	            } else {
	                echo "Error: " . $SaveSQL . "<br>" . mysqli_error($conn);
	            }


	        }
	    }

	}


	// Get new requests
	function getNewRequests($conn) {

		$GetSQL = "SELECT Sid, message, phone, status FROM requests WHERE status <> 1";
	    $requests = $conn->query($GetSQL);

	    return $requests;

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
				$bus->departure_stop	= $steps->transit_details->departure_stop->name;
				$bus->arrival_time 		= $steps->transit_details->arrival_time->text;
				$bus->arrival_stop		= $steps->transit_details->arrival_stop->name;
				$bus->stop_count        = $steps->transit_details->num_stops;

				$buses[] = $bus;
						
			}
		}

		return $buses;

	}


	// Format the response
	function formatResponse($buses) {

		//var_dump($buses);

		$bus = $buses[0];
		//print_r($bus);

		if ($bus) {
			$response = 'Take bus ' . $bus->name_short . ' for ' . $bus->headsign . ' at ' . $bus->departure_time . ' from stop ' . $bus->departure_stop . '. Get off after ' . $bus->stop_count . ' stop' . plural($bus->stop_count) . ' at ' . $bus->arrival_stop . ' at ' . $bus->arrival_time . '.'; 
		} else {
			$response = 'Sorry, there is no bus nearby';
		}
		
		return $response;

	}


	// Update the request status to 1 after the message has been sent
	function updateRequestStatus($requestSid, $messageStatus, $conn) {

        // Check the message status
        if ($messageStatus == 'failed') {
            $status = 2;
        } else {
            $status = 1;
        }

	    // Update the status of the request
        $UpdateSQL = "UPDATE requests SET status='$status' WHERE Sid='$requestSid'";

        if ($conn->query($UpdateSQL) === TRUE) {
            //echo "Record updated successfully";
        } else {
            echo "Error updating record: " . $conn->error;
        }
	}


	// Determine the type of request
	function getRequestType($message) {

		if (startsWith($message, 'home set') == true) {
			$type = 'home';
		} else if (startsWith($message, 'work set') == true) {
			$type = 'work';
		} else {
			$type = 'directions';
		}

		return $type;

	}


 ?>