<?php
namespace vibranium;
/*
meeting 會議基本資料
		uuid - UUID 去除連字號 - 的 UUID 字串
		name - 會議名稱
		memo - 備註
		update_user - 更新使用者的 UUID
		update_time - 更新時間

add 新增會議基本資料資料
	函數與參數：
		add_data($uuid, $name, $memo)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得會議基本資料資料
	函數與參數：
		get_data_uuid($uuid)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 uuid, name, memo, update_user, update_time 欄位資料或錯誤訊息。

set 設定會議基本資料資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
	函數與參數：
		set_data($name, $memo, $update_time, $pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除會議基本資料資料
	函數與參數：
		del_data($pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class meeting {
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
	//新增會議基本資料資料
	private function add_data($uuid, $name, $memo) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("INSERT INTO `meeting`(`uuid`, `name`, `memo`, `update_user`) VALUES(?, ?, ?, ?)", array($uuid, $name, $memo, $_SESSION["userdata"]["uuid"]));
	}

	//取得會議基本資料資料
	public function get_data_uuid($uuid) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `memo`, `update_user`, `update_time`, `create_time` From `meeting` Where `uuid` = ?", array($uuid));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `memo`, `update_user`, `update_time`, `create_time` From `meeting`", array());
	}

	//設定會議基本資料資料
	private function set_data($name, $memo, $update_time, $pk_uuid) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("UPDATE `meeting` SET `name` = ?, `memo` = ?, `update_user` = ?  Where `update_time` = ? and uuid = ? ", array($name, $memo, $_SESSION["userdata"]["uuid"], $update_time, $pk_uuid));
	}

	//刪除會議基本資料資料
	private function del_data($pk_uuid) {
		return $this->data->executeAndFetchAll("DELETE From `meeting` Where uuid = ? ", array($pk_uuid));
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
				if (isset($_REQUEST["uuid"]) && isset($_REQUEST["name"]) && isset($_REQUEST["memo"])) {
					$result = $this->add_data($_REQUEST["uuid"], $_REQUEST["name"], $_REQUEST["memo"]);
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
				if (isset($_REQUEST["name"]) && isset($_REQUEST["memo"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["uuid"])) {
					$result = $this->set_data($_REQUEST["name"], $_REQUEST["memo"], $_REQUEST["update_time"], $_REQUEST["uuid"]);
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