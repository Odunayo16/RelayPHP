<?php
require_once("config.php");
require_once("db_functions.php");

// preprare for Amazon S3
// Include the SDK using the Composer autoloader
require_once("aws/aws-autoloader.php");

use Aws\S3\S3Client;
use Aws\Common\Enum\Region;
use Aws\Common\Aws;
use Aws\S3\Enum\CannedAcl;
use Aws\S3\Exception\S3Exception;
use Guzzle\Http\EntityBody;


// define different image sizes for mobile
// you must unserialize this to use it later
define ("MOBILE_SIZES", serialize (array ("small" => 110, "medium" => 215)));

// This function returns the filename of the user's primary picture
// The primary picture is denoted by the picture where order = 1 in the database
// This returns the full, absolute filename starting with http://www.friendsyapp.com...
function get_primary_picture_url($user_id, $size = null) 
{
	$c = db_connect();
	
	// get array of mobile sizes that we support
	$mobile_sizes = unserialize(MOBILE_SIZES);
	$default_pic = "default.png";

	// check to see if size is set
	if(!is_null($size))
	{
		// make sure size is a valid choice
		if(!array_key_exists($size, $mobile_sizes))
		{
			$size = null;
		}
		else
		{
			$default_pic = "default-$size.png";
		}
	}

	// Determine which Amazon S3 bucket to write into depending on if dev or prod
	if (!IS_DEV) {
		$bucket = 'friendsy-profile';
	}
	else {
		$bucket = 'friendsy-dev-profile';
	}

	$q = "SELECT `filename` FROM `users_pictures_mapping` WHERE `id` = $user_id AND `order` = 1 LIMIT 1";
	$qexe = db_query($c, $q);

	if(mysqli_num_rows($qexe) > 0)
	{
		$row = mysqli_fetch_array($qexe);
		$filename = $row["filename"];
		
		// append mobile suffix
		if(!is_null($size))
		{
			$filename = strtr($row["filename"], array(
				".jpg" => "-$size.jpg",
			));
		}

		// get url
		$full_filepath =  PICTURES_DIR . $filename;

		// Ensure that file exists on server
		$client = S3Client::factory(array(
		    'profile' => 'default'
		));

		$does_file_exist = existsInS3($bucket, $filename);
		if($does_file_exist)
		{
			return $full_filepath;
		}
	}

	// if there are no rows, or the file doesn't exist, return the default
	return PICTURES_DIR . $default_pic;
}

// This function returns all of the primary pictures for the given user_ids
// Parameter: $user_ids is an array of user_id values
// Returns:
// 	$ids_pictures_mapping - an associative array of "id"->"picture_url" mapppings
function get_pictures_for_ids($user_ids, $size = null)
{
	$c = db_connect();

	$ids_pictures_mapping = array();
	$default_pic = 'default.png';

	// get array of mobile sizes that we support
	$mobile_sizes = unserialize(MOBILE_SIZES);

	// check to see if size is set
	if(!is_null($size))
	{
		// make sure size is a valid choice
		if(!array_key_exists($size, $mobile_sizes))
		{
			$size = null;
		}
		else
		{
			$default_pic = "default-$size.png";
		}
	}

	// initialize all of the profile pictures to default user (then update the real ones below)
	foreach($user_ids as $user_id)
	{
		$ids_pictures_mapping[$user_id] = PICTURES_DIR . $default_pic;
	}

	$user_ids_string = implode(',', $user_ids);

	$q = "SELECT `id`, `filename` FROM `users_pictures_mapping` WHERE `id` IN ($user_ids_string) AND `order` = 1";
	$qexe = db_query($c, $q);

	while($row = mysqli_fetch_array($qexe))
	{
		$filename = $row["filename"];

		// append mobile suffix
		if(!is_null($size))
		{
			$filename = strtr($row["filename"], array(
		    	".jpg" => "-$size.jpg",
			));
		}

		$ids_pictures_mapping[$row["id"]] = PICTURES_DIR . $filename;
	}
	
	return $ids_pictures_mapping;
}

// This function returns all of the pictures for the given user_ids
// Parameter: $user_ids is an array of user_id values
// Returns:
// 	$ids_pictures_mapping - an associative array of "id"->["picture_url", "picture_url2", "picture_url3",...] mapppings
function get_all_pictures_for_ids($user_ids, $size = null)
{
	$c = db_connect();

	$ids_pictures_mapping = array();

	// Ensure that the $user_ids array is not empty
	if (count($user_ids) < 1) {
		return $ids_pictures_mapping;
	}

	$default_pic = 'default.png';

	// get array of mobile sizes that we support
	$mobile_sizes = unserialize(MOBILE_SIZES);

	// check to see if size is set
	if(!is_null($size))
	{
		// make sure size is a valid choice
		if(!array_key_exists($size, $mobile_sizes))
		{
			$size = null;
		}
		else
		{
			$default_pic = "default-$size.png";
		}
	}

	// initialize all of the profile pictures to default user (then update the real ones below)
	foreach($user_ids as $user_id)
	{
		$ids_pictures_mapping[$user_id] = array(PICTURES_DIR . $default_pic);
	}

	$user_ids_string = implode(',', $user_ids);

	$q = "SELECT `id`, `filename`, `order` FROM `users_pictures_mapping` WHERE `id` IN ($user_ids_string) ORDER BY `id` ASC, `order` ASC";
	$qexe = db_query($c, $q);

	while($row = mysqli_fetch_array($qexe))
	{
		$filename = $row["filename"];

		// append mobile suffix
		if(!is_null($size))
		{
			$filename = strtr($row["filename"], array(
		    	".jpg" => "-$size.jpg",
			));
		}

		if(($ids_pictures_mapping[$row["id"]][0] == (PICTURES_DIR . $default_pic)))
		{
			$ids_pictures_mapping[$row["id"]] = array();
		}
		// add value to the end of the existing associative array
		array_push($ids_pictures_mapping[$row["id"]], PICTURES_DIR . $filename);
	}
	
	return $ids_pictures_mapping;
}

// This function returns an array of files of the user's pictures ordered in decreasing priority
// This returns the full, absolute filenames starting with http://www.friendsyapp.com...
function get_picture_urls($user_id, $size = null)
{
	$c = db_connect();

	// Determine which Amazon S3 bucket to write into depending on if dev or prod
	if (!IS_DEV) {
		$bucket = 'friendsy-profile';
	}
	else {
		$bucket = 'friendsy-dev-profile';
	}

	// get array of mobile sizes that we support
	$mobile_sizes = unserialize(MOBILE_SIZES);

	// check to see if size is set
	if(!is_null($size))
	{
		// make sure size is a valid choice
		if( !array_key_exists($size, $mobile_sizes) )
		{
			$size = null;
		}
			
	}

	// get all images
	$q = "SELECT `filename`
		  	FROM `users_pictures_mapping`
		  	WHERE `id` = $user_id
		  	ORDER BY `order` ASC
		  	LIMIT " . MAX_PICTURE_LIMIT; 

	$qexe = db_query($c, $q);

	$return_value = array();
	
	if (mysqli_num_rows($qexe) > 0)
	{

		while($row = mysqli_fetch_array($qexe))
		{
			$filename = $row["filename"];

			// append mobile suffix
			if(!is_null($size))
			{
				$filename = strtr($filename, array(
			    	".jpg" => "-$size.jpg",
				));
			}

			// get url
			$full_filepath =  PICTURES_DIR . $filename;

			// Ensure that file exists on server
			$client = S3Client::factory(array(
			    'profile' => 'default'
			));

			$does_file_exist = existsInS3($bucket, $filename );
			if($does_file_exist)
			{
				$return_value[] = $full_filepath;
			}
		}
	}

	// if no images
	if(count($return_value) < 1)
	{
		$default_pic = PICTURES_DIR . 'default.png';

	 	// append mobile suffix
		if(!is_null($size))
		{
			$default_pic = strtr($default_pic, array(
		    	".png" => "-$size.png",
			));
		}

		 $return_value[] = $default_pic;
	}

	return $return_value;
	
}

// This function randomly generates and returns a unique filename that may be used to name an image file
// If the unique filename already exists in the database for some reason, then we will ensure we create a new one
// If $append_text is set, we will append $append_text to the end of the file that we create
// This function doesnt append extensions
function image_get_unique_filename($prepend_text = null, $append_text = null)
{
	$c = db_connect();

	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';	
	$unique_filename = "";

	// Generate unique filenames, and check if they exist in database already
	while(true)
	{
		// Prepend text
		if(!is_null($prepend_text))
		{
			$unique_filename .= $prepend_text;
		}

		for ($i = 0; $i < rand(50, 70); $i++)
		{
			$unique_filename .= $characters[rand(0, strlen($characters) - 1)];
		}

		// Append timestamp
		$unique_filename .= "_" . date("YmdHis", time());

		// Append text
		if(!is_null($append_text))
		{
			$unique_filename .= $append_text;
		}

		$q = "SELECT 1 FROM `users_pictures_mapping` WHERE `filename` = '$unique_filename'";
		$qexe = db_query($c, $q);

		if (mysqli_num_rows($qexe) == 0)
		{
			break;
		}
	}

	db_close($c);

	return $unique_filename;
}


function create_picture($user_id, $uploaded_file)
{

	// Make sure user is valid and not disabled
	$c = db_connect();
	$user_id = mysqli_real_escape_string($c,$user_id);
	$uploaded_file = mysqli_real_escape_string($c,$uploaded_file);

	// Determine which Amazon S3 bucket to write into depending on if dev or prod
	if (!IS_DEV) {
		$bucket = 'friendsy-profile';
	}
	else {
		$bucket = 'friendsy-dev-profile';
	}

	$q = "SELECT 1 FROM `users` WHERE `id` = $user_id AND `enabled` = 1 LIMIT 1";
	$qexe = db_query($c, $q);

	if(mysqli_num_rows($qexe) == 0)
	{
		$return_array["error_message"] = "User account is disabled";
		return $return_array;
	}

	// Generate a unique filename where we can store the image on our server
	$unique_filename_string = image_get_unique_filename($user_id . "-");
	$unique_filename = $unique_filename_string . ".jpg";
	$unique_filepath = $_SERVER["DOCUMENT_ROOT"] . 'profile_pictures/' . $unique_filename;

	// save image
	$save_result = save_image_jpeg($uploaded_file, 'profile_pictures/' . $unique_filename);

	if(!$save_result["success"])
	{
		$return_array["error_message"] = $save_result["error_message"];
		return $return_array;
	}

	$result = uploadToS3($bucket, $unique_filename, $unique_filepath);


	// get image size and src for creating mobile friendly versions
	list($width, $height) = getimagesize($uploaded_file);
	$src = imagecreatefromjpeg($unique_filepath);

	// Generate mobile friendly versions of images
	// mobile sizes defined at top of page

	$mobile_sizes = unserialize(MOBILE_SIZES);
	foreach($mobile_sizes as $key => $value)
	{
		// get image size
		$mobile_picture_size = $value;

		// create blank image base
		$img_base = imagecreatetruecolor($mobile_picture_size, $mobile_picture_size);

		// duplicate image with new size
		imagecopyresized($img_base, $src, 0, 0, 0, 0, $mobile_picture_size, $mobile_picture_size, $width, $height);

		$mobile_filename = "$unique_filename_string-$key.jpg";
		$mobile_filepath = $_SERVER['DOCUMENT_ROOT'] . "profile_pictures/$unique_filename_string-$key.jpg";
		// save image with new suffix supplied by $key
		$success = imagejpeg($img_base, $mobile_filepath, 100);

		uploadToS3($bucket, $mobile_filename, $mobile_filepath);
	}

	// Make sure the newly created image file exists on our server
	if(existsInS3($bucket, $unique_filename))
	{
		$c = db_connect();
		
		// To make uploading picture compatible with across platforms that support multiple pictures
		// or only 1 picture, new picture should be inserted as primary picture
		$order = 1;

		// See if other pictures exist
		$q = "SELECT 1 FROM `users_pictures_mapping` WHERE `id`=$user_id";
		$qexe = db_query($c, $q);

		// If images already exist
		if( mysqli_num_rows($qexe) > 0)
		{

			//file_put_contents("log.txt", mysqli_num_rows($qexe));

			// check if user is over limit
			if (mysqli_num_rows($qexe) >= MAX_PICTURE_LIMIT)
			{
				// get image with largest order
				$q = "SELECT `filename`
				FROM `users_pictures_mapping`
				WHERE `id` = $user_id
				ORDER BY `order` DESC";
				$qexe = db_query($c, $q);

				if(mysqli_num_rows($qexe) < 1)
				{
					return false;
				}

				// delete image
				$row = mysqli_fetch_array($qexe);
				$last_picture = $row['filename'];
				$delete_success = delete_picture($user_id, $last_picture);
				if(!$delete_success)
				{
					return false;
				}
			}
			
			// Decrease the order of all other pictures to make room for new picture
			$q = "UPDATE `users_pictures_mapping` SET `order` = `order` + 1 WHERE `id`=$user_id";
			$qexe = db_query($c,$q);

		}

		// Set has_picture equal to 1
		$q = "UPDATE `users` SET `has_picture`=1 WHERE `id`=$user_id LIMIT 1";
		db_query($c, $q);

		// Also, we should check if there are any temp images we should delete
		$temp_filepath = PICTURES_DIR . $user_id . "_temp.jpg";
		if(existsInS3($bucket, $user_id . "_temp.jpg"))
		{
			removeFromS3($bucket, $user_id . "_temp.jpg");
		}

		$q = "INSERT INTO `users_pictures_mapping` (`id`, `filename`, `order`) VALUES ($user_id, '$unique_filename', $order)";
		$query_success = db_query($c, $q);

		if($query_success)
		{
			// add to friendsy score
			add_to_friendsy_score($user_id, POINTS_FOR_NEW_PROFILE_PIC);

			return PICTURES_DIR . $unique_filename;
		}
		return false;

	}

}

// This function deletes a picture that is associated with a user
// Returns true on success, false on failure

function delete_picture($user_id, $filename)
{
	$c = db_connect();
	$filename = mysqli_real_escape_string($c,$filename);

	$filename = end(explode("/", $filename));

	// Determine which Amazon S3 bucket to write into depending on if dev or prod
	if (!IS_DEV) {
		$bucket = 'friendsy-profile';
	}
	else {
		$bucket = 'friendsy-dev-profile';
	}


	// get priority of picture
	$q = "SELECT `order` from `users_pictures_mapping`
	WHERE `filename` = '$filename'
	AND `id` = $user_id";
	$qexe = db_query($c, $q);
	if (mysqli_num_rows($qexe) == 0)
	{
		return false;
	}
	$arr = mysqli_fetch_array($qexe);
	$order = $arr['order'];

	// if priority is one, check that there are other pictures before deleting
	if($order == 1)
	{
		$q = "SELECT count(1) as count from `users_pictures_mapping`
		WHERE `id` = $user_id";
		$qexe = db_query($c, $q);
		$arr = mysqli_fetch_array($qexe);
		$count = $arr['count'];

		if($count < 2)
		{
			return false;
		}
	}


	// delete picture from database
	$q = "DELETE from `users_pictures_mapping`
	WHERE `filename` = '$filename'
	AND `id` = $user_id";
	$qexe = db_query($c, $q);

	// check if query failed
	if(!$qexe)
	{
		return false;
	}


	// delete file from server
	$filepath = PICTURES_DIR . $filename;
	removeFromS3($bucket, $filename);


	// delete mobile versions of files
	// mobile sizes defined at top of page

	$mobile_sizes = unserialize(MOBILE_SIZES);
	foreach($mobile_sizes as $key => $value)
	{
		// append mobile suffix
		$mobile_filepath = strtr($filepath, array(
		    ".jpg" => "-$key.jpg",
		));
		$mobile_filename = strtr($filename, array(
		    ".jpg" => "-$key.jpg",
		));
		if(existsInS3($bucket, $mobile_filename))
		{
			removeFromS3($bucket, $mobile_filename);
		}
	}

	// lower priority of all other pictures greater than priority
	$q = "UPDATE `users_pictures_mapping` SET `order` = `order` - 1
	WHERE `id`=$user_id
	AND `order` > $order";
	$qexe = db_query($c,$q);


	return true;

}

// return true on success
// return false on error
// update_picture_order(14126, array("friendsyapp.com/profile_pics/4324-image1.jpg", "friendsyapp.com/profile_pics/4324-image2.jpg", "friendsyapp.com/profile_pics/4324-image3.jpg"));
// delete images that don't exist in array and are in database

//update_picture_order(14126, array('POOOP','http://localhost:8888/profile_pics/14126-7n2uG0JSdyiuHqU5JIq58RHFLfpDmJePPhhSYNY6dkCfwLkfGUpT_20140717210322.jpg'));

function update_picture_order($user_id, $file_data)
{
	$c = db_connect();

	// Determine which Amazon S3 bucket to write into depending on if dev or prod
	if (!IS_DEV) {
		$bucket = 'friendsy-profile';
	}
	else {
		$bucket = 'friendsy-dev-profile';
	}

	// keep array of invalid_pictures
	$invalid_pictures = array();

	// my_real_escape each filename
	foreach($file_data as $key => $value)
	{

		// since we're getting ROOTPATH with every image
		// overwrite filename with only image name after final slash
		$file_data[$key] = substr( $value, strrpos( $value, '/' )+1 );

		// mysqli_real_escape_string
		$file_data[$key] = mysqli_real_escape_string($c,$file_data[$key]);
		$filename = $file_data[$key];

		// make sure image is currently linked to user
		$q = "SELECT 1
		FROM `users_pictures_mapping`
		WHERE `filename` = '$filename'
		AND `id` = $user_id
		LIMIT 1";
		$qexe = db_query($c, $q);

		// if image isn't linked to user, discard it
		if (mysqli_num_rows($qexe) == 0)
		{
			$invalid_pictures[] = $key;
		}
	}

	// unset all invalid pictures
	foreach($invalid_pictures as $key => $value)
	{
		unset($file_data[$value]);
	}
	$file_data = array_values($file_data);

	// There must be one valid picture
	if(count($file_data) < 1)
	{
		$response["success"] = false;
		$response["message"] = "There was an error during your request.";
		return $response;
	}

	// iterate over every possible image in database
	for( $order = 1; $order < MAX_PICTURE_LIMIT + 1; $order++ )
	{
		// select image that currently exists at $order
		$q = "SELECT `filename`
		FROM `users_pictures_mapping`
		WHERE `order` = $order
		AND `id` = $user_id
		LIMIT 1";
		$qexe = db_query($c, $q);

		// see if image exists at $order
		if(mysqli_num_rows($qexe) > 0)
		{
			$row = mysqli_fetch_array($qexe);
			$file_to_delete = $row['filename'];

			$q_delete = "DELETE FROM `users_pictures_mapping`
				WHERE `order` = '$order'
				AND `id` = $user_id
				LIMIT 1";
			$qexe_delete = db_query($c, $q_delete);

			// unlink image if it's not in file_data
			if(!in_array($file_to_delete,  $file_data))
			{
				$file_key_to_delete = end(explode("/", $file_to_delete));
				removeFromS3($bucket, $file_key_to_delete);
			}


		}

		// check if there is a new image to insert for $order
		if (isset($file_data[$order - 1]))
		{

			$filename = $file_data[$order - 1];

			// insert new image
			$q = "REPLACE `users_pictures_mapping`
					SET `order` = $order, `filename` = '$filename', `id` = $user_id";
			$qexe2 = db_query($c, $q);
		}
	}

	// send back new order
	$response["image_urls"] = get_picture_urls($user_id);
	$response["success"] = true;
	return $response;

}


//function to retroactively create small versions of all profile pictures
//can be used if we want to support a new size
function create_mobile_images_retroactive()
{
	/*


	// mobile sizes we need
	$mobile_sizes = unserialize(MOBILE_SIZES);

	// connect to database
	$c = db_connect();

	// get all images
	$q = "SELECT `filename`
		FROM `users_pictures_mapping` WHERE `id` > 15800 
		order by `id` ASC";
	$qexe = db_query($c, $q);

	while($row = mysqli_fetch_array($qexe))
	{
		$filepath = $_SERVER['DOCUMENT_ROOT'] . ROOTDIR . PICTURES_DIR . $row["filename"];
		if (file_exists($filepath))
		{

			// get width, and height
			list($width, $height) = getimagesize($filepath);

			// create php image
			$src = imagecreatefromjpeg($filepath);

			foreach($mobile_sizes as $key => $value)
			{

				// append mobile suffix
				$mobile_filepath = strtr($filepath, array(
				    ".jpg" => "-$key.jpg",
				));

				if(file_exists($mobile_filepath))
				{
					echo("File exists: $mobile_filepath<br>");
					continue;
				}

				// get image size
				$mobile_picture_size = $value;

				// create blank image base
				$img_base = imagecreatetruecolor($mobile_picture_size, $mobile_picture_size);

				// duplicate image with new size
				imagecopyresized($img_base, $src, 0, 0, 0, 0, $mobile_picture_size, $mobile_picture_size, $width, $height);



				// save image with new suffix supplied by $key
				$success = imagejpeg($img_base, $mobile_filepath, 100);

				echo("added " . $mobile_filepath . "<br/>");

			}

		}
	}*/
}

// save an uploaded file to target_filepath
function save_image_jpeg($uploaded_file, $target_filepath, $force_square=true)
{

	$return_array["success"] = false;

	// The picture was uploaded successfully, validate it
    // Don't rely on extensions - these can easily be forged by a client, so attempt to read the image
    $imageData = @getimagesize($uploaded_file);
    $file_format = $imageData[2];

    // If imageData is false, or if it is not one of the supported types (JPEG), then abort
    if($imageData === FALSE || !($file_format == IMAGETYPE_JPEG )) 
    {
		$return_array["error_message"] = "You must upload a JPG image.";
		return $return_array;

    }

	// Verify the image dimensions
	list($width, $height) = getimagesize($uploaded_file);

	if ($force_square && ($width != $height))
	{
		$return_array["error_message"] = "Error - must upload image with minimum resolution of ".MIN_PICTURE_DIMENSION." x ".MIN_PICTURE_DIMENSION;
      	return $return_array;
	}

    if($width < MIN_PICTURE_DIMENSION || $height < MIN_PICTURE_DIMENSION)
    {
        $return_array["error_message"] = "Error - must upload image with minimum resolution of ".MIN_PICTURE_DIMENSION." x ".MIN_PICTURE_DIMENSION;
      	return $return_array;
    }

    // Store image as original size
	$src = imagecreatefromjpeg($uploaded_file);
	$target_filepath = $_SERVER['DOCUMENT_ROOT'] . $target_filepath;
	$success = imagejpeg($src, $target_filepath , 100);
	$success = true;

	if(!$success)
	{
		$return_array["error_message"] = "There was an error uploading your image.";
		return $return_array;
	}
	else
	{
		$return_array["success"] = true;
		return $return_array;
	}

}

function uploadToS3($bucket, $key, $temp_filepath) {

	// Instantiate the S3 class and point it at the desired host
	$client = S3Client::factory(array(
	    'profile' => 'default'
	));

	// now put the file in the bucket
	try {
	    $result = $client->putObject(array(
	        'Bucket' => $bucket,
	        'Key' => $key,
	        'SourceFile'   => $temp_filepath,
	        'ACL' => 'public-read',
	        'ContentType' => 'image/jpeg',
	        'StorageClass' => 'STANDARD'
	    ));
	} catch (S3Exception $e) {
	    return "There was an error uploading the file.\n";
	}
	return $result;

}

function removeFromS3($bucket, $key) {

	// Instantiate the S3 class and point it at the desired host
	$client = S3Client::factory(array(
	    'profile' => 'default'
	));

	$result = $client->deleteObject(array(
	    'Bucket' => $bucket,
	    'Key' => $key,
	));
	return $result;
}

function existsInS3($bucket, $key) {

	// Instantiate the S3 class and point it at the desired host
	$client = S3Client::factory(array(
	    'profile' => 'default'
	));

	$does_file_exist = $client->doesObjectExist( $bucket, $key );

	return $does_file_exist;
}

?>