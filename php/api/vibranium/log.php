<?php
namespace vibranium;
/*
log 操作記錄
用於系統連線運作機制，不提供 API 進行資料操作。
		uuid - UUID 去除連字號 - 的 UUID 字串
		ip - 使用的 IP，可能是 IPv6 所以用 40 字元長度。
		log - 操作記錄
		user - 登入使用者 UUID
		device - 登入裝置 UUID

add 新增操作記錄資料
	函數與參數：
		add_data($uuid, $ip, $log, $user, $device)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得操作記錄資料
	函數與參數：
		get_data_uuid($uuid)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 uuid, ip, log, user, device 欄位資料或錯誤訊息。

set 設定操作記錄資料
	函數與參數：
		set_data($ip, $log, $user, $device, $pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除操作記錄資料
	函數與參數：
		del_data($pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class log {
	private $data;

	//新增操作記錄資料
	private function add_data($uuid, $ip, $log, $user, $device) {
		return $this->data->executeAndFetchAll("INSERT INTO `log`(`uuid`, `ip`, `log`, `user`, `device`) VALUES(?, ?, ?, ?, ?)", array($uuid, $ip, $log, $user, $device));
	}

	//取得操作記錄資料
	public function get_data_uuid($uuid) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `ip`, `log`, `user`, `device` From `log` Where `uuid` = ?", array($uuid));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `ip`, `log`, `user`, `device` From `log`", array());
	}

	//設定操作記錄資料
	private function set_data($ip, $log, $user, $device, $pk_uuid) {
		return $this->data->executeAndFetchAll("UPDATE `log` SET `ip` = ?, `log` = ?, `user` = ?, `device` = ?  Where `update_time` = ? and uuid = ? ", array($ip, $log, $user, $device, $update_time, $pk_uuid));
	}

	//刪除操作記錄資料
	private function del_data($pk_uuid) {
		return $this->data->executeAndFetchAll("DELETE From `log` Where uuid = ? ", array($pk_uuid));
	}

	//初始化
	function __construct() {
		require_once __DIR__ . '/database.php';
		$this->data = new MySQL();
	}

	//API 呼叫入口
	public function api_call($function) {
		//登入檢查
		require_once __DIR__ . "/user.php";
		if (!user::isLogin()) {
			$result["status"] = "error";
			$result["message"] = "Not login.";
			$result["code"] = -200;
			return $result;
		}		
		$result["status"] = "error";
		$result["message"] = "Parameter error.";
		$result["code"] = -201;
		switch($function) {
			case "add":
				if (isset($_REQUEST["uuid"]) && isset($_REQUEST["ip"]) && isset($_REQUEST["log"]) && isset($_REQUEST["user"]) && isset($_REQUEST["device"])) {
					$result = $this->add_data($_REQUEST["uuid"], $_REQUEST["ip"], $_REQUEST["log"], $_REQUEST["user"], $_REQUEST["device"]);
				}
				break;
			case "get":
				if (isset($_REQUEST["uuid"])) {
					return $this->get_data_uuid($_REQUEST["uuid"]);
				}
				return $this->get_data();
				break;
			case "set":
				if (isset($_REQUEST["ip"]) && isset($_REQUEST["log"]) && isset($_REQUEST["user"]) && isset($_REQUEST["device"]) && isset($_REQUEST["uuid"])) {
					$result = $this->set_data($_REQUEST["ip"], $_REQUEST["log"], $_REQUEST["user"], $_REQUEST["device"], $_REQUEST["uuid"]);
				}
				break;
			case "del":
				if (isset($_REQUEST["uuid"])) {
					$result = $this->del_data($_REQUEST["uuid"]);
				}
				break;
		}
		return $result;
	}
}
?>