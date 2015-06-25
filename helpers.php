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


function getDirections($locations){

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

	// Extract bus information only
	foreach ($directions->routes[0]->legs[0]->steps as $steps){

		// Get the bus details
		if ($steps->travel_mode == "TRANSIT" ) {

			$bus = array(

				'name_short'		=> $steps->transit_details->line->short_name,
				'name_long' 		=> $steps->transit_details->line->name,
				'headsign'			=> $steps->transit_details->headsign,
				'departure_time' 	=> $steps->transit_details->departure_time->text,
				'stop_name' 		=> $steps->transit_details->departure_stop->name
			);

			$buses[] = $bus;
					
		}
	}

	return $buses;

}



function formatResponse($buses) {

	print_r($buses);


		
	}


 ?>