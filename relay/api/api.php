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

function slim_db_log($app) {
	// connect to the database, and put all of the logs there
 //    $c = db_connect();

 //    $request = $app->request;

 //    $response = $app->response;

 //    $request_method = mysqli_real_escape_string($c, trim( $request->getMethod() ) );
 //    $request_path = mysqli_real_escape_string($c, trim( $request->getPathInfo() ) );
 //    $response_status = mysqli_real_escape_string($c, trim( $response->getStatus() ) );
 //    $response_body = mysqli_real_escape_string($c, trim( $response->getBody() ) );
 //    $user_id = get_user_id();

 //    // handle get variables
 //    $allGetVars = $request->get();
 //    if (count($allGetVars) > 0) {
	//     $get_vars_str = mysqli_real_escape_string($c, json_encode($allGetVars));
	// }
	// else {
	// 	$get_vars_str = "";
	// }

	// // handle post variables using the same way we call get_request_body_params()
	// $allPostVars = json_decode($request->getBody(), true);

	// if (count($allPostVars) > 0) {

	// 	// if the request path is the login then drop the password vars
	// 	if ($request_path == "/sessions" && $request_method == "POST" 
	// 		&& array_key_exists("password", $allPostVars)) {
	// 		// Ensure that password is omitted.
	// 		$allPostVars["password"] = "OMITTED";
	// 	}

	//     $post_vars_str = mysqli_real_escape_string($c, json_encode($allPostVars));
	// }
	// else {
	// 	$post_vars_str = "";
	// }

	// // for now, store an identical copy of the post var string as the put var string
	// $put_vars_str = $post_vars_str;

 //    $q = "INSERT INTO history_slim_logs 
 //    		(`user_id`, `get_vars`, `post_vars`, `put_vars`, 
 //    		`request_method`, `request_path`, `response_status`, `response_body`, `datetime`) 
 //    		VALUES 
 //    		('$user_id', '$get_vars_str', '$post_vars_str', '$put_vars_str',
 //    		'$request_method', '$request_path', '$response_status', '$response_body', NOW() )";
 //    $qexe = db_query($c, $q);
}

// modify the hook so that after every request, the request path and response status
$app->hook('slim.after', function () use ($app) {
	slim_db_log($app);
});

// Make an error/exception handler
$app->error(function (\Exception $e) use ($app) {
	// connect to the database, and put all of the logs there
 //    $c = db_connect();

 //    $user_id = get_user_id();

 //    $request = $app->request;
 //    $response = $app->response;

 //    $request_method = mysqli_real_escape_string($c, trim( $request->getMethod() ) );
 //    $request_path = mysqli_real_escape_string($c, trim( $request->getPathInfo() ) );

	// $response_body = mysqli_real_escape_string($c, $e->getMessage() );
 //    $response_body .= mysqli_real_escape_string($c, $e->getFile() );
 //    $response_body .= mysqli_real_escape_string($c, $e->getLine() );
 //    $response_body .= mysqli_real_escape_string($c, $e->getTraceAsString() );
 //    $response_status = 500;

 //    // handle get variables
 //    $allGetVars = $request->get();
 //    if (count($allGetVars) > 0) {
	//     $get_vars_str = mysqli_real_escape_string($c, json_encode($allGetVars));
	// }
	// else {
	// 	$get_vars_str = "";
	// }

	// // handle post variables
	// $allPostVars = $request->post();
	// if (count($allPostVars) > 0) {
	//     $post_vars_str = mysqli_real_escape_string($c, json_encode($allPostVars));
	// }
	// else {
	// 	$post_vars_str = "";
	// }

	// // handle put variables
	// $allPutVars = $request->put();
	// if (count($allPutVars) > 0) {
	//     $put_vars_str = mysqli_real_escape_string($c, json_encode($allPutVars));
	// }
	// else {
	// 	$put_vars_str = "";
	// }

	// $q = "INSERT INTO history_slim_logs 
	// 	(`user_id`, `get_vars`, `post_vars`, `put_vars`, 
	// 	`request_method`, `request_path`, `response_status`, `response_body`, `datetime`) 
	// 	VALUES 
	// 	('$user_id', '$get_vars_str', '$post_vars_str', '$put_vars_str',
	// 	'$request_method', '$request_path', '$response_status', '$response_body', NOW() )";
 //    $qexe = db_query($c, $q);

 //    $app->stop();
});

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
// $app->post('/messages', 'send_location')


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
// AUTHENTICATION FUNCTIONS
//--------------------------------------------------------------------------------------

// This is the function that all of the PROTECTED API calls are routed through
// It makes sure that the request has the correct authentication to proceed
// If the credentials are invalid, or the auth_token has expired, this will return an HTTP 401 status code
function authenticate() 
{
	// ;)
}

//--------------------------------------------------------------------------------------
// API FUNCTIONS
//--------------------------------------------------------------------------------------

// /sessions
// Create a new session, and issue a secure auth_token by making a call to issue_mobile_auth_token
// Required Body Parameters: $email, $password
// HTTP status code: 201 on success, 400 on failure
// Returns an associative array with the following fields:
//	user_id: the user_id of the user that this session was created for
// 	auth_token: 64-byte plaintext of the authtoken that will be sent to the client
//	user_has_profile: 1 if the user has a profile, 0 otherwise (used to prevent entry into app)
//	user_has_picture: 1 if the user has a pictture, 0 otherwise (used to prevent entry into app)
//	message: a descriptive message upon error
//	feautures: the features enabled at the user's school
function create_session_mobile()
{
}

// This function issues a new mobile_auth_token for the given email address
// Additionally, it stores an APN device token along with the record for push notifications
// NOTE: Only call this function once the email/password combination has already been validated
// Calling this function generates, and physically issues a new mobile_auth_token
// Returns an associative array with the following fields:
//	success: true if a new mobile_auth_token was successfully issued, false otherwise
//	user_id: the user_id of the user that this auth_token was issued for
//	auth_token_plaintext: 64-byte plaintext of the auth token that will be sent to the client
function issue_mobile_auth_token($email, $apn_device_token, $type, $version=null)
{
}

// Action: DELETE, Resource: /sessions/:id
// Required Body Parameters: [none]
// This function needs to pass through "authenticate" before it is invoked
// Deletes the session tied to the user with given $id
// HTTP status code: 204 on success, 400 on failure
// Returns an associative array with the following fields:
//	message: a descriptive message upon error
function delete_session_mobile($id)
{
}

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

function send_location() {
	require_once("message_functions.php");

	// initialize response to HTTP Status Code
	$response_status_code = 400;
	$return_array = null;

	$body_params = get_request_body_params();

	$latitude = trim($body_params["latitude"]);
	$longitude = trim($body_params["longitude"]);

	$response = send_location($latitude, $longitude);

	if ($response == true){
		$response_status_code = 201;
		$return_array = $response
	}

	return_response($response_status_code, $return_array);

}

// Run the SLIM application, this must be called last
$app->run();

?>
