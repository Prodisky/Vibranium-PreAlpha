<?php
namespace vibranium;
/*
token 管理程式
*/

class token {
	public function get() {
	    require_once __DIR__ . "/session.php";
		session::start();
		if (!isset($_SESSION["token"])) $_SESSION["token"] = hash('sha512', rand(0,32767).'vibranium'.time());
		$return["status"] = "success";
		$return["code"] = 1;
		$token["token"]=$_SESSION["token"];
		$return["result"]["count"] = 1;
		$return["result"]["datas"] = Array($token);
		return $return;
	}
	private function clear() {
	    require_once __DIR__ . "/session.php";
		session::start();
		unset($_SESSION["token"]);
		$return["status"] = "success";
		$return["code"] = 1;
		$return["result"]["count"] = 0;
		$return["result"]["datas"] = Array();
		return $return;
	}
	//API 呼叫入口
	public function api_call($function) {
		$result["status"] = "error";
		$result["message"] = "Parameter error.";
		$result["code"] = -201;
		switch($function) {
			case "get":
				return $this->get();
				break;
			case "clear":
				return $this->clear();
				break;
		}
		return $result;
	}
}
?>
