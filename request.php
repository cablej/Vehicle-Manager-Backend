<?php

require_once('SQLTools.php');

$ck_id = '/^[A-Za-z0-9]{6,6}$/';
$ck_key = '/^[A-Za-z0-9]{13,13}$/';
$ck_username = '/^[A-Za-z0-9_]{2,20}$/';
$ck_password =  '/^[A-Za-z0-9!@#$%^&*()_]{2,20}$/';
$ck_sort = '/^[A-Za-z0-9_]{2,20}$/';
$ck_type = '/^[A-Za-z0-9_]{2,20}$/';
$ck_url = '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS';
$ck_email = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';

if(!isSet($_POST["action"])) {
	error("no action specified");
}

$action = $_POST["action"];

$mysqli = getmysqli();

switch ($action) {
	case "GetVehiclesReserved":
		$school = $mysqli->real_escape_string($_POST["school"]);
		$result = getVehiclesReserved($school, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "GetVehicleHistory":
		$school = $mysqli->real_escape_string($_POST["school"]);
		$result = getVehiclesReserved($school, $mysqli, 0);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "GetVehiclesReservedAtTime":
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		$school = $mysqli->real_escape_string($_POST["school"]);
		if(!isValidTimeStamp($startTime) || !isValidTimeStamp($endTime)) {
			error("times are not valid");
		}
		$result = getVehiclesReserved($school, $mysqli, $startTime, $endTime);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "ReserveVehicle":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$owner = $mysqli->real_escape_string($_POST["owner"]);
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		if(!isValidTimeStamp($startTime) || !isValidTimeStamp($endTime)) {
			error("times are not valid");
		}
		$result = reserveVehicle($school, $vehicleName, $owner, $startTime, $endTime, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "UpdateReservation":
		$originalVehicleName = $mysqli->real_escape_string($_POST["originalVehicleName"]);
		$originalOwner = $mysqli->real_escape_string($_POST["originalOwner"]);
		$originalStartTime = $_POST["originalStartDate"];
		$originalEndTime = $_POST["originalEndDate"];
		
		$newVehicleName = $mysqli->real_escape_string($_POST["newVehicleName"]);
		$newOwner = $mysqli->real_escape_string($_POST["newOwner"]);
		$newStartTime = $_POST["newStartDate"];
		$newEndTime = $_POST["newEndDate"];
		$keySet = $mysqli->real_escape_string($_POST["keySet"]);
		$gasCard = $mysqli->real_escape_string($_POST["gasCard"]);
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		if(!isValidTimeStamp($originalStartTime) || !isValidTimeStamp($originalEndTime) || !isValidTimeStamp($newStartTime) || !isValidTimeStamp($newEndTime)) {
			error("times are not valid");
		}
		$result = updateReservation($school, $originalVehicleName, $originalOwner, $originalStartTime, $originalEndTime, $newVehicleName, $newOwner, $newStartTime, $newEndTime, $keySet, $gasCard, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "SubmitRequest":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$owner = $mysqli->real_escape_string($_POST["owner"]);
		$school = $mysqli->real_escape_string($_POST["school"]);
		$email = $_POST["email"];
		if(!preg_match($ck_email, $email)) {
			error("email contains illegal characters: $email");
		}
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		if(!isValidTimeStamp($startTime) || !isValidTimeStamp($endTime)) {
			error("times are not valid $startTime, $endTime");
		}
		
		if(isSet($_POST["vehicles"])) {
			$vehicles = $_POST["vehicles"];
			$adjVehicles = [];
			foreach($vehicles as $vehicle) {
				$adjVehicles[] = $mysqli->real_escape_string($vehicle);
			}
			$result = submitRequestMultipleVehicles($owner, $email, $school, $adjVehicles, $startTime, $endTime, $mysqli);
			echo(json_encode($result, JSON_UNESCAPED_SLASHES));
			break;
		}
		
		$result = submitRequest($owner, $email, $school, $vehicleName, $startTime, $endTime, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "IsVehicleReserved":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		$school = $mysqli->real_escape_string($_POST["school"]);
		if(!isValidTimeStamp($startTime) || !isValidTimeStamp($endTime)) {
			error("times are not valid");
		}
		$result = isVehicleReserved($school, $vehicleName, $startTime, $endTime, $mysqli);
		echo(json_encode(["reserved" => $result], JSON_UNESCAPED_SLASHES));
		break;
	case "GetVehicles":
		$school = $mysqli->real_escape_string($_POST["school"]);
		$result = getVehicles($school, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "GetKeySets":
		$school = $mysqli->real_escape_string($_POST["school"]);
		$result = getKeySets($school, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "GetGasCards":
		$school = $mysqli->real_escape_string($_POST["school"]);
		$result = getGasCards($school, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "GetSchools":
		$result = getSchools($mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "AddVehicle":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		$result = addVehicle($school, $vehicleName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "RemoveVehicle":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		$result = removeVehicle($school, $vehicleName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "AddKeySet":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$keySetName = $mysqli->real_escape_string($_POST["keySetName"]);
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		$result = addKeySet($school, $vehicleName, $keySetName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "RemoveKeySet":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$keySetName = $mysqli->real_escape_string($_POST["keySetName"]);
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		$result = removeKeySet($school, $vehicleName, $keySetName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "AddGasCard":
		$gasCardName = $mysqli->real_escape_string($_POST["gasCardName"]);
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		$result = addGasCard($school, $gasCardName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "RemoveGasCard":
		$gasCardName = $mysqli->real_escape_string($_POST["gasCardName"]);
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		$result = removeGasCard($school, $gasCardName, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "RemoveReservation":
		$vehicleName = $mysqli->real_escape_string($_POST["vehicleName"]);
		$owner = $mysqli->real_escape_string($_POST["owner"]);
		$startTime = $_POST["startTime"];
		$endTime = $_POST["endTime"];
		
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		
		if(!isValidTimeStamp($startTime) || !isValidTimeStamp($endTime)) {
			error("times are not valid");
		}
		$result = removeReservation($school, $vehicleName, $owner, $startTime, $endTime, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "SignIn":
		$school = $_POST['school'];
		$newPass = $_POST['password'];
		if(!preg_match($ck_username, $school) || !preg_match($ck_password, $newPass)) {
		   error("username/password contains illegal characters");
		}
		$returnValue = signIn($school, $newPass, $mysqli);
		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		break;
	case "SignUp":
		$school = $_POST['school'];
		$email = $_POST['email'];
		$newPass = $_POST['password'];
		if(!preg_match($ck_username, $school) || !preg_match($ck_email, $email) || !preg_match($ck_password, $newPass)) {
			error("school/password/email contains illegal characters");
		}
		$returnValue = createUser($school, $email, $newPass, $mysqli);

		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		break;
	case "GetRequests":
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		$result = getRequests($school, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "ProcessRequest":
		$timestamp = $_POST["timestamp"];
		if(!isValidTimeStamp($timestamp)) {
			error("timestamp is not valid");
		}
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		
		$type = $_POST["type"];
		if($type != "approve" && $type != "deny") {
			error("type is not valid");
		}
		
		$school = getSchool($key, $mysqli);
		$result = processRequest($school, $timestamp, $type, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "ChangePassword":
		$oldPassword = $_POST['oldPassword'];
		$newPassword = $_POST['newPassword'];
		if(!preg_match($ck_password, $oldPassword) || !preg_match($ck_password, $newPassword)) {
			error("password contains illegal characters");
		}
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		$returnValue = signIn($school, $oldPassword, $mysqli);
		
		changePassword($school, $newPassword, $mysqli);
		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		break;
	case "ChangeEmail":
		$newEmail = $_POST['newEmail'];
		
		$password = $_POST['password'];
		if(!preg_match($ck_password, $password)) {
			error("password contains illegal characters");
		}
		$key = $_POST["key"];
		if(!preg_match($ck_key, $key)) {
			error("key contains illegal characters");
		}
		$school = getSchool($key, $mysqli);
		$returnValue = signIn($school, $password, $mysqli);

		changeEmail($school, $newEmail, $mysqli);
		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		break;
	case "GetColors":
		$school = $mysqli->real_escape_string($_POST["school"]);
		$result = getColors($school, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "SubmitCode":
		$code = $mysqli->real_escape_string($_POST["code"]);
		$result = submitCode($code, $mysqli);
		echo(json_encode($result, JSON_UNESCAPED_SLASHES));
		break;
	case "RequiresCode":
		echo("false");
		break;
}

?>