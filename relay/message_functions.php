<?php

require_once("config.php");
require_once("db_functions.php");


//This function sends messages
function send_message($sender_id, $message_content) {

	$return_array = array();
	$return_array["success"] = false;

	$c = db_connect();
	
	$sender_id = mysqli_real_escape_string($c,$sender_id);
	$message_content_sanitized = mysqli_real_escape_string($c,$message_content);

	//insert message into db
	$q  = "INSERT INTO messages (sender_id, message_content, datetime) ";
	$q .= "VALUES ($sender_id, '$message_content_sanitized', NOW())";
	$success = db_query($c, $q);

	if($success == true) {
		$time = time();
		$return_array["success"] = true;
		$return_array["datetime"] = $time;
		$most_recent_message_id = mysqli_insert_id($c);
		$return_array["message_id"] = $most_recent_message_id;

		// next, physically send the message to the recipient
		// $pusher = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID); 
		
		// send message to everyone
		// $channel_name = "feed";
		// $success = $pusher->trigger($channel_name, 'receive_message', array( 'message' => $message_content, 'sender_id' => $sender_id, 'datetime' => $time));	

	}

	return $return_array;
}
