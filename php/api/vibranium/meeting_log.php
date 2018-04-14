<?php
namespace vibranium;
/*
meeting_log 會議紀錄
		uuid - UUID 去除連字號 - 的 UUID 字串
		meeting - 會議
		log - 紀錄
		update_user - 更新使用者的 UUID
		update_time - 更新時間

add 新增會議紀錄資料
	函數與參數：
		add_data($uuid, $meeting, $log)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得會議紀錄資料
	函數與參數：
		get_data_uuid($uuid)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 uuid, meeting, log, update_user, update_time 欄位資料或錯誤訊息。

set 設定會議紀錄資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
	函數與參數：
		set_data($meeting, $log, $update_time, $pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除會議紀錄資料
	函數與參數：
		del_data($pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class meeting_log {
	private $data;

	//取得權限資料
	private function get_permission() {
		require_once __DIR__ . "/session.php";
		session::start();
		if (isset($_SESSION["userdata"]["uuid"])) {
			$datas = $this->data->executeAndFetchAll("SELECT `permission_base` as `permission` From `user` Where `uuid` = ?", array($_SESSION["userdata"]["uuid"]));
			foreach($datas["result"]["datas"] as $user) {
				return $user->permission;
			}
		}
		return 0;
	}
	//新增會議紀錄資料
	private function add_data($uuid, $meeting, $log) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("INSERT INTO `meeting_log`(`uuid`, `meeting`, `log`, `update_user`) VALUES(?, ?, ?, ?)", array($uuid, $meeting, $log, $_SESSION["userdata"]["uuid"]));
	}

	//取得會議紀錄資料
	public function get_data_uuid($uuid) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `meeting`, `log`, `update_user`, `update_time`, `create_time` From `meeting_log` Where `uuid` = ? Order by `create_time`", array($uuid));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `meeting`, `log`, `update_user`, `update_time`, `create_time` From `meeting_log` Order by `create_time`", array());
	}

	//設定會議紀錄資料
	private function set_data($meeting, $log, $update_time, $pk_uuid) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("UPDATE `meeting_log` SET `meeting` = ?, `log` = ?, `update_user` = ?  Where `update_time` = ? and uuid = ? ", array($meeting, $log, $_SESSION["userdata"]["uuid"], $update_time, $pk_uuid));
	}

	//刪除會議紀錄資料
	private function del_data($pk_uuid) {
		return $this->data->executeAndFetchAll("DELETE From `meeting_log` Where uuid = ? ", array($pk_uuid));
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
		$no_permission["status"] = "error";
		$no_permission["message"] = "No permission.";
		$no_permission["code"] = -501;
		$permission = $this->get_permission();
		$result["status"] = "error";
		$result["message"] = "Parameter error.";
		$result["code"] = -201;
		switch($function) {
			case "add":
				if($permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["uuid"]) && isset($_REQUEST["meeting"]) && isset($_REQUEST["log"])) {
					$result = $this->add_data($_REQUEST["uuid"], $_REQUEST["meeting"], $_REQUEST["log"]);
				}
				break;
			case "get":
				if($permission != 1 && $permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["uuid"])) {
					return $this->get_data_uuid($_REQUEST["uuid"]);
				}
				return $this->get_data();
				break;
			case "set":
				if($permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["meeting"]) && isset($_REQUEST["log"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["uuid"])) {
					$result = $this->set_data($_REQUEST["meeting"], $_REQUEST["log"], $_REQUEST["update_time"], $_REQUEST["uuid"]);
				}
				break;
			case "del":
				if($permission != 7) return $no_permission;
				if (isset($_REQUEST["uuid"])) {
					$result = $this->del_data($_REQUEST["uuid"]);
				}
				break;
		}
		return $result;
	}
}
?>