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




 ?>