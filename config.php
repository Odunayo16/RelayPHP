<?php 


if (!empty($_SERVER["DOCUMENT_ROOT"])) {
	set_include_path ( $_SERVER['DOCUMENT_ROOT'] . "/");
}
else {
	set_include_path ( "/srv/www/relay_app/current/relay" );
}


define("IS_DEV", false);


define("ROOTDIR", "/");
define("PICTURES_DIR", "https://s3.amazonaws.com/princeton-relay/");
define("TEMP_PICTURES_DIR", "/var/www/html/pictures/");
define("HOSTNAME", "relaydb.cnkkxztzehfg.us-east-1.rds.amazonaws.com");
define("MAIN_DATABASE", "relaydb");
define("USERNAME", "relayadmin");
define("PASSWORD", "masterblaster1");
define("PORT", ini_get("mysqli.default_port"));
define("SOCKET", "/tmp/mysql5.sock");


// define Amazon S3 Connection
define('AWS_KEY', "");
define('AWS_SECRET_KEY', '');
define('AWS_CANONICAL_ID', '');
define('AWS_CANONICAL_NAME', '');


// Pusher Credentials go here
define("PUSHER_APP_ID", '');
define("PUSHER_APP_KEY", '');
define("PUSHER_APP_SECRET", '');


date_default_timezone_set('EST');

ini_set('display_errors', false);
error_reporting(0);

?>
