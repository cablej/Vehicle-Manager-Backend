<?php

require_once('dbInfo.php');

define("NUM_LINKS", 25);

//returns the database used
function getmysqli() {
	$mysqli = new mysqli(MYSQLI_HOST, MYSQLI_USERNAME, MYSQLI_PASSWORD, MYSQLI_DB_NAME);
	return $mysqli;
}

function getVehiclesReserved($mysqli, $startTime = 0, $endTime = 2147483648 /*max time*/) {
	$sql = "SELECT * FROM `Reservations` WHERE `startDateTime` >= $startTime AND `endDateTime` <= $endTime";
	return query($sql, $mysqli);
}

function reserveVehicle($vehicleName, $owner, $startTime, $endTime, $mysqli) {
	if(isVehicleReserved($vehicleName, $startTime, $endTime, $mysqli)) error("This vehicle is already reserved.");
	$sql = "INSERT INTO `Reservations`(`vehicleName`, `owner`, `startDateTime`, `endDateTime`) VALUES ('$vehicleName','$owner', '$startTime', '$endTime')";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function removeReservation($vehicleName, $owner, $startTime, $endTime, $mysqli) {
	$sql = "DELETE FROM `Reservations` WHERE `vehicleName` = '$vehicleName' AND `owner` = '$owner' AND `startDateTime` = '$startTime' AND `endDateTime` = '$endTime'";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function removeVehicle($vehicleName, $mysqli) {
	$sql = "DELETE FROM `Vehicles` WHERE `vehicleName` = '$vehicleName'";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function addVehicle($vehicleName, $mysqli) {
	$sql = "INSERT INTO `Vehicles`(`vehicleName`) VALUES ('$vehicleName')";
	query($sql, $mysqli);
	return ["success" => "true"];
}

function isVehicleReserved($vehicleName, $startTime, $endTime, $mysqli) {
	$sql = "SELECT * FROM `Reservations` WHERE `vehicleName` = '$vehicleName' AND `startDateTime` >= '$startTime' AND `endDateTime` <= '$endTime'";
	//error($sql);
	$result = query($sql, $mysqli);
	return count($result) != 0;
}

function getVehicles($mysqli) {
	$sql = "SELECT * FROM `Vehicles`";
	return query($sql, $mysqli);
}

//a generic query, returns an associative array
function query($sql, $mysqli) {
	$resultArray = [];
	if($result = $mysqli->query($sql)) {
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$resultArray[] = $row;
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