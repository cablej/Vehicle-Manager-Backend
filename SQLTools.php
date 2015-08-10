<?php

require_once('dbInfo.php');

define("NUM_LINKS", 25);

//returns the database used
function getmysqli() {
	$mysqli = new mysqli(MYSQLI_HOST, MYSQLI_USERNAME, MYSQLI_PASSWORD, MYSQLI_DB_NAME);
	return $mysqli;
}

function isValidTimeStamp($timestamp)
{
    return ($timestamp === $timestamp) 
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
}

//gets a school name with the specific key
function getSchool($key, $mysqli, $require = true) {
	$sql = "SELECT `name` FROM `Schools` WHERE `key` = '$key'";
	
	$row = query($sql, $mysqli);
	
	if(count($row) == 0) {
		if($require) error("Please log in");
		return "";
	}
	
	return $row[0]["name"];
}

//helper method to encrypt the given password
function cryptPass($input, $rounds = 12){ //Sequence - cryptPass, save hash in db, crypt(input, hash) == hash
	$salt = "";
	$saltChars = array_merge(range('A','Z'), range('a','z'), range(0,9));
	for($i = 0; $i < 22; $i++){
		$salt .= $saltChars[array_rand($saltChars)];
	}
	return crypt($input, sprintf('$2y$%02d$', $rounds) . $salt);
}

//signs in a user with the username and password given
function signIn($school, $password, $mysqli) {
	$sql = "SELECT `name`, `password` FROM `Schools` WHERE `name` = '$school'";
	$row = query_one($sql, $mysqli);
	$hashedPass = $row["password"];
	if(crypt($password, $hashedPass) == $hashedPass) {
		$key = uniqid();
		$sql = "UPDATE `Schools` SET `key` = '$key' WHERE `name` = '$school'";
		if(!$mysqli->query($sql)) {
			error("could not log in");
		}
		
		return ["key" => $key, "school" => $row["name"]];
		
	} else {
		error("wrong username/password");
	}
}

//creates a user with the username and password given
function createUser($school, $email, $newPass, $mysqli) {
	$hashedPass = cryptPass($newPass);
	$sql = "SELECT `name` FROM `Schools` WHERE name = '$school'";
	if($result = $mysqli->query($sql)) {
		if($result->num_rows == 0) {
			$sql = "INSERT INTO `Schools`(`name`, `email`, `password`) VALUES ('$school', '$email', '$hashedPass')";
			if(!$mysqli->query($sql)) {
				error("could not create user");
			}
			return signIn($school, $newPass, $mysqli);
		} else {
			error("username already used");
		}
	} else {
		error("could not create user");
	}
}

function changePassword($school, $newPassword, $mysqli) {
	$hashedPass = cryptPass($newPassword);
	$sql = "UPDATE `Schools` SET `password` = '$hashedPass' WHERE name = '$school'";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function changeEmail($school, $newEmail, $mysqli) {
	$sql = "UPDATE `Schools` SET `email` = '$newEmail' WHERE name = '$school'";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function getVehiclesReserved($school, $mysqli, $startTime = 0, $endTime = 2147483648 /*max time*/) {
	if($startTime == 0) $startTime = time(); //only get future reservations
	$sql = "SELECT * FROM `Reservations` WHERE `school` = '$school' AND `startDateTime` >= $startTime AND `endDateTime` <= $endTime ORDER BY `startDateTime`";
	return query($sql, $mysqli);
}

function getRequests($school, $mysqli, $startTime = 0) {
	if($startTime == 0) $startTime = time(); //only get future requests
	$sql = "SELECT * FROM `Requests` WHERE `active` = 1 AND `school` = '$school' AND `startDateTime` >= $startTime";
	return query($sql, $mysqli);
}

function reserveVehicle($school, $vehicleName, $owner, $startTime, $endTime, $mysqli) {
	if($endTime <= $startTime) error("End time is before start time.");
	if(!vehicleExists($vehicleName, $school, $mysqli)) error("Vehicle does not exist (It is case sensitive).");
	if(isVehicleReserved($school, $vehicleName, $startTime, $endTime, $mysqli)) error("This vehicle is already reserved.");
	$sql = "INSERT INTO `Reservations`(`vehicleName`, `owner`, `startDateTime`, `endDateTime`, `school`) VALUES ('$vehicleName','$owner', '$startTime', '$endTime', '$school')";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function processRequest($school, $timestamp, $type, $mysqli) {
	$sql = "SELECT * FROM `Requests` WHERE `school` = '$school' AND `timestamp` = '$timestamp'";
	$result = query_one($sql, $mysqli);
	if($type == "approve") {
		if(isVehicleReserved($school, $result["vehicleName"], $result["startDateTime"], $result["endDateTime"], $mysqli)) error("This vehicle is already reserved.");
	}
	$sql = "UPDATE `Requests` SET `active` = 0 WHERE `school` = '$school' AND `timestamp` = '$timestamp'";
	query($sql, $mysqli);
	if($type == "approve") {
		reserveVehicle($result["school"], $result["vehicleName"], $result["user"], $result["startDateTime"], $result["endDateTime"], $mysqli);
	}
	return ["success" => "true"];
}

function submitRequest($owner, $email, $school, $vehicleName, $startTime, $endTime, $mysqli) {
	if(isVehicleReserved($school, $vehicleName, $startTime, $endTime, $mysqli)) error("This vehicle is already reserved.");
	$currentTime = time();
	$sql = "INSERT INTO `Requests`(`user`, `email`, `vehicleName`, `startDateTime`, `endDateTime`, `school`, `timestamp`) VALUES ('$owner', '$email', '$vehicleName', '$startTime', '$endTime', '$school', '$currentTime')";
	$result = query($sql, $mysqli);
	
	$sql = "SELECT * FROM `Schools` WHERE `name` = '$school'";
	$result = query_one($sql, $mysqli);
	
	$schoolEmail = $result["email"];
	
	$startTimeString = timestampToString($startTime);
	$endTimeString = timestampToString($endTime);
	$timestampString = timestampToString($currentTime);
	
	$subject = "Reservation request from $owner";
	$message = "Hello $school,\r\n\r\n$owner has requested at $timestampString to use the vehicle '$vehicleName' from $startTimeString to $endTimeString. Click here to review it.\r\n\r\nYou can contact $owner at $email.\r\n\r\nFeedback? Contact me at jackhcable@gmail.com";
	sendEmail($schoolEmail, $subject, $message);
	
	$subject = "Reservation confirmation";
	$message = "Hello $owner,\r\n\r\nThis is confirming your request at $timestampString for the vehicle '$vehicleName' from $startTimeString to $endTimeString. Click here to cancel this.\r\n\r\nYou can contact $school at $email.\r\n\r\nFeedback? Contact me at jackhcable@gmail.com";
	sendEmail($email, $subject, $message);
	
	return ["success" => "true"];
}

function timestampToString($timestamp) {
	$time = (int) $timestamp;
	$dt = new DateTime("@$timestamp");
	$dt->setTimeZone(new DateTimeZone('America/Chicago'));
	return $dt->format('Y-m-d H:i');
}

function sendEmail($to, $subject, $message) {
	$headers = 'From: jackhcable@gmail.com' . "\r\n";
	mail($to, $subject, $message, $headers);
}

function removeReservation($school, $vehicleName, $owner, $startTime, $endTime, $mysqli) {
	$sql = "DELETE FROM `Reservations` WHERE `school` = '$school' AND `vehicleName` = '$vehicleName' AND `owner` = '$owner' AND `startDateTime` = '$startTime' AND `endDateTime` = '$endTime'";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function removeVehicle($school, $vehicleName, $mysqli) {
	$sql = "DELETE FROM `Vehicles` WHERE `school` = '$school' AND `vehicleName` = '$vehicleName'";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function addVehicle($school, $vehicleName, $mysqli) {
	$sql = "INSERT INTO `Vehicles`(`vehicleName`, `school`) VALUES ('$vehicleName', '$school')";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function isVehicleReserved($school, $vehicleName, $startP1, $endP1, $mysqli) {
	$sql = "SELECT * FROM `Reservations` WHERE `school` = '$school' AND `vehicleName` = '$vehicleName'";
	$result = query($sql, $mysqli);
	foreach($result as $reservation) {
		$startP2 = $reservation["startDateTime"];
		$endP2 = $reservation["endDateTime"];
		if(($startP1 > $startP2 && $startP1 < $endP2) || ($startP2 > $startP1 && $startP2 < $endP1)) { //meeting exists
			return true;
		}
	}
	return false;
}

function getVehicles($school, $mysqli) {
	$sql = "SELECT * FROM `Vehicles` WHERE `school` = '$school'";
	return query($sql, $mysqli);
}

function vehicleExists($vehicleName, $school, $mysqli) {
	$sql = "SELECT * FROM `Vehicles` WHERE `school` = '$school' AND `vehicleName` = '$vehicleName'";
	$result = query($sql, $mysqli);
	return count($result) > 0;
}

//a generic query, returns an associative array
function query($sql, $mysqli) {
	$resultArray = [];
	if($result = $mysqli->query($sql)) {
		if($result->num_rows > 0) {
			while($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$resultArray[] = $row;
			}
		}
	} else {
		error("could not query");
	}
	return $resultArray;
}

//queries exactly one row
function query_one($sql, $mysqli) {
	if($result = $mysqli->query($sql)) {
	    if($result->num_rows == 1) {
	        $row = $result->fetch_array(MYSQLI_ASSOC);
	        return $row;
		} else {
			error("could not query");
		}
	} else {
		error("could not query");
	}
}

//terminates the program with an error
function error($message) {
	die(json_encode(["error" => $message], JSON_UNESCAPED_SLASHES));
}

?>