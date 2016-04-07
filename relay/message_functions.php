<?php

require_once("config.php");
require_once("db_functions.php");
require_once('Pusher.php'); 


function send_location($userId, $latitude, $longitude) {

	$return_array = array();
	$return_array["success"] = true;

	$pusher = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID); 
	$channel_name = "location_data";
	$success = $pusher->trigger($channel_name, 'receive_location', array( 'userId' => $userId, 'latitude' => $latitude, 'longitude' => $longitude));	

	return $return_array;

}

//This function sends messages
function send_message($sender_id, $message_content, $latitude = -1, $longitude = -1) {

	$return_array = array();
	$return_array["success"] = false;

	$c = db_connect();
	
	$sender_id = mysqli_real_escape_string($c,$sender_id);
	$message_content_sanitized = mysqli_real_escape_string($c,$message_content);
	$latitude = mysqli_real_escape_string($c,$latitude);
	$longitude = mysqli_real_escape_string($c,$longitude);

	//insert message into db
	$q  = "INSERT INTO messages (sender_id, message_content , latitude, longitude, datetime) ";
	$q .= "VALUES ($sender_id, '$message_content_sanitized', '$latitude', '$longitude', NOW())";
	$success = db_query($c, $q);

	if($success == true) {
		$time = time();
		$return_array["success"] = true;
		$return_array["datetime"] = $time;
		$most_recent_message_id = mysqli_insert_id($c);
		$return_array["message_id"] = $most_recent_message_id;

		// next, physically send the message to the recipient
	    $pusher = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID); 
		
		// send message to everyone
		 $channel_name = "feed";
		 $success = $pusher->trigger($channel_name, 'receive_message', array( 'message' => $message_content, 'sender_id' => $sender_id, 'datetime' => $time));	

	}

	return $return_array;
}

//get messages from the db
function get_messages(){

	$return_array = array();
	$return_array["success"] = false;
	$return_array["messages"] = array();

	$c = db_connect();

	$q  = "SELECT * FROM messages ORDER BY datetime DESC LIMIT 1";
	$qexe = db_query($c, $q);

	if (!$qexe) {
		return $return_array;
	}
	else {
		$return_array["success"] = true;
	}

	while($row = mysqli_fetch_assoc($qexe)) {
    	$return_array["messages"][] = $row;
	}

	return $return_array;

}
