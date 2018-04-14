<?php
namespace vibranium;
/*
API 主程式
*/

header("Content-Type: application/json; charset=utf-8");
function data() {
	//API 呼叫 Data 固定為兩層
	$error["status"] = "error";
	$error["message"] = "API call error.";
	$error["code"] = -500;
	
	$path = explode("/", $_REQUEST["data"]);
	if (count($path) != 2) return $error;
	switch ($path["0"]) {
		case "token":
			require_once __DIR__ . "/vibranium/token.php";
			return (new token())->api_call($path["1"]);
		case "file":
			require_once __DIR__ . "/vibranium/file.php";
			return (new file())->api_call($path["1"]);
		case "user":
			require_once __DIR__ . "/vibranium/user.php";
			return (new user())->api_call($path["1"]);
		case "team":
			require_once __DIR__ . "/vibranium/team.php";
			return (new team())->api_call($path["1"]);
		case "team_user":
			require_once __DIR__ . "/vibranium/team_user.php";
			return (new team_user())->api_call($path["1"]);
		case "meeting":
			require_once __DIR__ . "/vibranium/meeting.php";
			return (new meeting())->api_call($path["1"]);
		case "meeting_log":
			require_once __DIR__ . "/vibranium/meeting_log.php";
			return (new meeting_log())->api_call($path["1"]);
		case "project":
			require_once __DIR__ . "/vibranium/project.php";
			return (new project())->api_call($path["1"]);
		case "project_user":
			require_once __DIR__ . "/vibranium/project_user.php";
			return (new project_user())->api_call($path["1"]);
		case "project_task":
			require_once __DIR__ . "/vibranium/project_task.php";
			return (new project_task())->api_call($path["1"]);
		case "project_log":
			require_once __DIR__ . "/vibranium/project_log.php";
			return (new project_log())->api_call($path["1"]);
		case "workday_week":
			require_once __DIR__ . "/vibranium/workday_week.php";
			return (new workday_week())->api_call($path["1"]);
		case "workday_real":
			require_once __DIR__ . "/vibranium/workday_real.php";
			return (new workday_real())->api_call($path["1"]);
		case "contact":
			require_once __DIR__ . "/vibranium/contact.php";
			return (new contact())->api_call($path["1"]);
		case "message":
			require_once __DIR__ . "/vibranium/message.php";
			return (new message())->api_call($path["1"]);
		case "log":
			require_once __DIR__ . "/vibranium/log.php";
			return (new log())->api_call($path["1"]);
		default:
			return $error;
	}
}
echo json_encode(data());
?>
