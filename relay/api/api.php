<?php

require_once( dirname(__FILE__) . '/../config.php');
require_once("db_functions.php");

// Require the Slim Framework (this is MANDATORY)
require 'Slim-master/Slim/Slim.php';

// Register the Slim Autoloader
\Slim\Slim::registerAutoloader();

// Instantiate a Slim application
$app = new \Slim\Slim(array(
    'debug' => true
));

// Ensure that this application only sets and receives secure cookies
if(!IS_DEV)
{
	$app->config('cookies.secure', true);
}
// Also, change every instance of "SetCookie" to "SetEncryptedCookie"
// And change every instance of "getCookie" to "getEncryptedCookie"

//--------------------------------------------------------------------------------------
// CONFIGURE LOGGING
//--------------------------------------------------------------------------------------

//Enable logging
$app->log->setEnabled(true);

//--------------------------------------------------------------------------------------
// APPLICATION ROUTES
//--------------------------------------------------------------------------------------

// Define the Slim application routes
// Session/Create Account functions
// $app->post('/sessions', 'create_session_mobile');
// $app->delete('/sessions/:id', 'authenticate', 'delete_session_mobile');

// Private Messaging functions
// $app->post('/messages', 'authenticate', 'send_message_mobile');
$app->post('/messages', 'send_message_mobile');
$app->get('/messages', 'get_messages_mobile');
$app->post('/location', 'send_location_mobile');


//--------------------------------------------------------------------------------------
// HELPER FUNCTIONS
//--------------------------------------------------------------------------------------

// This function parses the body of the HTTP request (POST or PUT)
// Then, it returns an associative array of all of the parameters and their values
function get_request_body_params()
{
	$request = \Slim\Slim::getInstance()->request();
	$body = $request->getBody();
	$body_params = json_decode($body, true); // "true" returns this as associative array
	return $body_params;
}

// This function parses the url of the HTTP request (GET)
// Then it returns the value of the parameter named $name
function get_request_url_param($name)
{
	$request = \Slim\Slim::getInstance()->request();
	return $request->get($name);
}

// This is our global function that returns a response to the client
// Currently, we json-encode all our outputs
// Have a default HTTP Status Code of "Bad Request" (just to be safe)
function return_response($status_code = 400, $data)
{
	$response = \Slim\Slim::getInstance()->response();
	$response->header('Content-Type', 'application/json');

	$response->setStatus($status_code);	

	if($data != null)
	{	
		$data = json_encode($data);
		$response->setBody($data);
	}
}

// This function sanitizes and returns the user_id stored in the current session cookie
function get_user_id()
{
	$app = \Slim\Slim::getInstance();

	// do this just in case someone tries to sql inject the cookie
	$c = db_connect();
    return mysqli_real_escape_string($c,$app->getCookie('user_id'));
}

// This function sanitizes and returns the auth_token stored in the current session cookie
function get_auth_token()
{
	$app = \Slim\Slim::getInstance();

	// do this just in case someone tries to sql inject the cookie
	$c = db_connect();
    return mysqli_real_escape_string($c,$app->getCookie('auth_token'));
}

//--------------------------------------------------------------------------------------
// API FUNCTIONS
//--------------------------------------------------------------------------------------


// Action: POST, Resource: /messages
// Body Parameters:
//	$sender_id: the id of the user that is sending the message
//	$message_content: the content of the private message that we want to send
//	$latitude:
//	$longitude:
// This function sends a private message to the to_id with the specified message_content
// HTTP status code: 201 on success, 400 on failure
// Returns an associative array with the following fields:
//	datetime: if successful, this will contain the UNIX timestamp of when the message was inserted into the database
function send_message_mobile()
{
	require_once("message_functions.php");

	// initialize response to HTTP Status Code
	$response_status_code = 400;

	// initialize return array
	$return_array = null;

	// get request body parameters
	$body_params = get_request_body_params();
	$sender_id = trim($body_params["sender_id"]);
	$message_content = trim($body_params["message_content"]);
	$latitude = trim($body_params["latitude"]);
	$longitude = trim($body_params["longitude"]);

	// get user_id from cookie data, so that we can pass to send_private_message() function
    // $from_id = get_user_id();

	// attempt to send the private message
	$response = send_message($sender_id, $message_content, $latitude, $longitude);
	if($response["success"] == true)
	{
		// message was successfully sent
		$response_status_code = 201;
		$return_array["datetime"] = $response["datetime"];
	}

	// return the correct HTTP status code, and data
	return_response($response_status_code, $return_array);
}

function send_location_mobile() {
	require_once("message_functions.php");

	// initialize response to HTTP Status Code
	$response_status_code = 400;
	$return_array = null;

	$body_params = get_request_body_params();

	$userId = trim($body_params["userId"]);
	$latitude = trim($body_params["latitude"]);
	$longitude = trim($body_params["longitude"]);

	$response = send_location($userId, $latitude, $longitude);

	if ($response["success"] == true){

		$response_status_code = 201;
		$return_array["success"] = $response["success"];
	}

	return_response($response_status_code, $return_array);

}


function get_messages_mobile()
{
	require_once("message_functions.php");

	// initialize response to HTTP Status Code
	$response_status_code = 400;

	// initialize return array
	$return_array = null;


	// attempt to get the messages
	$response = get_messages();
	if($response["success"] == true)
	{
		// message was successfully sent
		$response_status_code = 200;
		$return_array["messages"] = $response["messages"];
	}

	// return the correct HTTP status code, and data
	return_response($response_status_code, $return_array);
}


// Run the SLIM application, this must be called last
$app->run();

?>
