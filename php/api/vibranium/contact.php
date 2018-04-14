<?php
namespace vibranium;
/*
contact 聯絡人
		user - 使用者
		contact - 聯絡人
		update_user - 更新使用者的 UUID
		update_time - 更新時間

add 新增聯絡人資料
	函數與參數：
		add_data($user, $contact)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得聯絡人資料
	函數與參數：
		get_data_user_contact($user, $contact)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 user, contact, update_user, update_time 欄位資料或錯誤訊息。

set 設定聯絡人資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
	函數與參數：
		set_data($user, $contact, $update_time, $pk_user, $pk_contact)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除聯絡人資料
	函數與參數：
		del_data($pk_user, $pk_contact)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class contact {
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
	//新增聯絡人資料
	private function add_data($user, $contact) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("INSERT INTO `contact`(`user`, `contact`, `update_user`) VALUES(?, ?, ?)", array($user, $contact, $_SESSION["userdata"]["uuid"]));
	}

	//取得聯絡人資料
	public function get_data_user_contact($user, $contact) {
		return $this->data->executeAndFetchAll("SELECT `user`, `contact`, `update_user`, `update_time`, `create_time` From `contact` Where `user` = ? and `contact` = ? Order by `create_time`", array($user, $contact));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `user`, `contact`, `update_user`, `update_time`, `create_time` From `contact` Order by `create_time`", array());
	}

	//設定聯絡人資料
	private function set_data($user, $contact, $update_time, $pk_user, $pk_contact) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("UPDATE `contact` SET `user` = ?, `contact` = ?, `update_user` = ?  Where `update_time` = ? and user = ? and contact = ? ", array($user, $contact, $_SESSION["userdata"]["uuid"], $update_time, $pk_user, $pk_contact));
	}

	//刪除聯絡人資料
	private function del_data($pk_user, $pk_contact) {
		return $this->data->executeAndFetchAll("DELETE From `contact` Where user = ? and contact = ? ", array($pk_user, $pk_contact));
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
				if (isset($_REQUEST["user"]) && isset($_REQUEST["contact"])) {
					$result = $this->add_data($_REQUEST["user"], $_REQUEST["contact"]);
				}
				break;
			case "get":
				if($permission != 1 && $permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["user"]) && isset($_REQUEST["contact"])) {
					return $this->get_data_user_contact($_REQUEST["user"], $_REQUEST["contact"]);
				}
				return $this->get_data();
				break;
			case "set":
				if($permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["user"]) && isset($_REQUEST["contact"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["user"]) && isset($_REQUEST["contact"])) {
					$result = $this->set_data($_REQUEST["user"], $_REQUEST["contact"], $_REQUEST["update_time"], $_REQUEST["user"], $_REQUEST["contact"]);
				}
				break;
			case "del":
				if($permission != 7) return $no_permission;
				if (isset($_REQUEST["user"]) && isset($_REQUEST["contact"])) {
					$result = $this->del_data($_REQUEST["user"], $_REQUEST["contact"]);
				}
				break;
		}
		return $result;
	}
}
?>