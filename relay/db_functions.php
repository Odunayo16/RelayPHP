<?php

require_once('config.php');

// returns a MYSQL connection to the specified database, or FALSE on error
function db_connect($database = MAIN_DATABASE)
{
	//$c = mysqli_connect(HOSTNAME, USERNAME, PASSWORD, $database, 8889);
	$c = mysqli_connect(HOSTNAME, USERNAME, PASSWORD, $database, PORT, SOCKET);
	
	if ($c === FALSE)
		return FALSE;

	// change charset
	mysqli_set_charset($c, "utf8mb4");
		
	return $c;
}


function db_query($c, $q)
{
	return mysqli_query($c, $q);	
}


?>
