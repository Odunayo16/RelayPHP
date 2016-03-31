<?php

// require_once("config.php");
// require_once("db_functions.php");
// require_once('Pusher.php');  

// // Allow mobile devices to connect to Pusher (requires User-Id and Auth-Token http headers)
// $http_headers = getallheaders();

// // If post variables are set, override
// if (isset($_POST["user_id"])) {
// 	$http_headers["user_id"] = $_POST["user_id"];
// }

// if (isset($_POST["auth_token"])) {
// 	$http_headers["auth_token"] = $_POST["auth_token"];
// }

// if(isset($http_headers["user_id"]) && isset($http_headers["auth_token"]))
// {

// 	$c = db_connect(); 

// 	$user_id = mysqli_real_escape_string($c,trim($http_headers["user_id"]));
// 	$auth_token = mysqli_real_escape_string($c,trim($http_headers["auth_token"]));

// 	if(strlen($user_id) > 0 && strlen($auth_token) > 0)
// 	{		
// 	    // ensure that the user_id has not been disabled by us
// 	    $q = "SELECT 1 FROM `users` WHERE `id` = $user_id AND `enabled` = 1 LIMIT 1";
// 	    $qexe = db_query($c, $q);

// 	    $arr = mysqli_fetch_array($qexe);

// 	    if (!is_null($arr))
// 	    {
// 		    // get the salt, and encrypted auth_token corresponding to this user from the database
// 		    $q = "SELECT auth_token, salt FROM mobile_auth_tokens WHERE user_id = $user_id AND timestamp_expires > NOW() LIMIT 1";
// 		    $qexe = db_query($c, $q);
// 		    $arr = mysqli_fetch_array($qexe);

// 		    if(!is_null($arr))
// 		    {
// 		    	$salt = $arr["salt"];
// 		    	$auth_token_encrypted = $arr["auth_token"];

// 		    	if (crypt($auth_token, "$" . "2a" . "$" . "07" . "$" . $salt . "$") == $auth_token_encrypted)
// 				{
// 					// this protected API call has been validated (validate the pusher channel)
// 					if($_POST['channel_name'] === ("private-inbox-" . $user_id) || $_POST['channel_name'] === ("private-random-chat-inbox-" . $user_id)
// 						|| $_POST['channel_name'] === ("private-chat-rooms-inbox-" . $user_id))
// 					{
// 						$pusher = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID);
// 						echo $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
// 						exit;
// 					}
// 					else if (strstr($_POST['channel_name'], "private-chat-rooms-")) {
// 						// channel name contains the prefix for chat rooms
// 						// now check if the user is valid (Dartmouth student)
// 						// $q  = " SELECT 1 FROM `users` u, `profiles` p ";
// 						// if (IS_DEV) {
// 						// 	$q .= "	WHERE (u.school = 'Dartmouth College' OR u.school = 'Princeton University' OR u.school = 'Rutgers University' OR u.school = 'Rensselaer Polytechnic Institute') ";
// 						// }
// 						// else {
// 						// 	$q .= "	WHERE u.school = 'Dartmouth College' ";
// 						// }
// 						// $q .= " AND u.id = p.id ";
// 						// $q .= " AND u.id = '$user_id' ";
						
// 						// $qexe = db_query($c, $q);
// 						// if (mysqli_num_rows($qexe) > 0) {
// 							// now search the database for the channel name
// 							$channel_name = mysqli_real_escape_string($c, $_POST["channel_name"]);
// 							$q = "SELECT 1 FROM rooms WHERE room_channel_name = '$channel_name' ";
// 							$qexe = db_query($c, $q);
// 							if ( mysqli_num_rows($qexe) > 0 ) {
// 								$pusher = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID);
// 								echo $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
// 								exit;
// 							}
// 						// }
// 					}
// 				}
// 			}
// 		}
// 	}
// }

// require_once("session_functions.php");

// if(session_validate() == false)
// {
// 	header('', true, 403);
//   	echo("Please log in again.");
//   	exit;
// }

// // get the user's name
// $c = db_connect();

// $session_id = mysqli_real_escape_string($c, $_SESSION["id"]);
// $channel_name = mysqli_real_escape_string($c, $_POST["channel_name"]);

// $q = "SELECT first_name, last_name FROM users WHERE id = $session_id LIMIT 1";
// $qexe = db_query($c, $q);
// $arr = mysqli_fetch_array($qexe);
// $name = $arr["first_name"] . " " . $arr["last_name"];

// // there are two types of channel names, figure out which type this one is
// // first, check to see if this is a type 1 channel (online presence channel for a school)
// $session_school = str_replace(" ", "-", $_SESSION["school"]);
// if($channel_name === ("presence-" . $session_school . "-online"))
// {
// 	// this is an online presence channel request
// 	// it is valid, because the school name within the channel name matches the school name within the session
// 	$pusher = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID);
// 	$presence_data = array('name' => $name);
// 	echo $pusher->presence_auth($_POST['channel_name'], $_POST['socket_id'], $session_id, $presence_data);
// 	exit;
// }

// // next, check to see if this is a type 2 channel (inbox channel for a specific user)
// if($channel_name === ("private-inbox-" . $session_id))
// {
// 	// this is an inbox channel request
// 	// it is valid, because the user id within the channel name matches the user id within the session
// 	$pusher = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID);
// 	echo $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
// 	exit;
// }

// header('', true, 403);
// echo ("Please log in again.");

?>
