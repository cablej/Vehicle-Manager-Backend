<?php

require_once('SQLTools.php');

$ck_id = '/^[A-Za-z0-9]{6,6}$/';
$ck_key = '/^[A-Za-z0-9]{13,13}$/';
$ck_username = '/^[A-Za-z0-9_]{2,20}$/';
$ck_password =  '/^[A-Za-z0-9!@#$%^&*()_]{2,20}$/';
$ck_sort = '/^[A-Za-z0-9_]{2,20}$/';
$ck_type = '/^[A-Za-z0-9_]{2,20}$/';
$ck_url = '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS';

if(!isSet($_POST["action"])) {
	error("no action specified");
}

$action = $_POST["action"];

$mysqli = getmysqli();

switch ($action) {
	case "GetVehiclesReserved":
		$result = getVehiclesReserved($mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "GetVehiclesReservedAtTime":
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		$result = getVehiclesReserved($mysqli, $startTime, $endTime);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "ReserveVehicle":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$owner = $mysqli->real_escape_string($_POST["owner"]);
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		$result = reserveVehicle($vehicleName, $owner, $startTime, $endTime, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "IsVehicleReserved":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		$result = isVehicleReserved($vehicleName, $startTime, $endTime, $mysqli);
		echo(json_encode(["reserved" => $result], JSON_UNESCAPED_SLASHES));
		break;
	case "GetOpenVehicles":
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		$result = getOpenVehicles($startTime, $endTime, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "GetVehicles":
		$result = getVehicles($mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "AddVehicle":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$result = addVehicle($vehicleName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "RemoveReservation":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$owner = $mysqli->real_escape_string($_POST["owner"]);
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		$result = removeReservation($vehicleName, $owner, $startTime, $endTime, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "RemoveVehicle":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$result = removeVehicle($vehicleName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
}

?>