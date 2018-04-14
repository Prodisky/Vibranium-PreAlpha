<?php
namespace vibranium;
/*
team_user 團隊成員
		team - 團隊
		user - 使用者
		type - 加入類型 0:無 1:隊長 2:隊員 3:其他 7:系統
		update_user - 更新使用者的 UUID
		update_time - 更新時間

add 新增團隊成員資料
	函數與參數：
		add_data($team, $user, $type)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得團隊成員資料
	函數與參數：
		get_data_team_user($team, $user)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 team, user, type, update_user, update_time 欄位資料或錯誤訊息。

set 設定團隊成員資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
	函數與參數：
		set_data($team, $user, $type, $update_time, $pk_team, $pk_user)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除團隊成員資料
	函數與參數：
		del_data($pk_team, $pk_user)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class team_user {
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
	//新增團隊成員資料
	private function add_data($team, $user, $type) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("INSERT INTO `team_user`(`team`, `user`, `type`, `update_user`) VALUES(?, ?, ?, ?)", array($team, $user, $type, $_SESSION["userdata"]["uuid"]));
	}

	//取得團隊成員資料
	public function get_data_team_user($team, $user) {
		return $this->data->executeAndFetchAll("SELECT `team`, `user`, `type`, `update_user`, `update_time`, `create_time` From `team_user` Where `team` = ? and `user` = ? Order by `create_time`", array($team, $user));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `team`, `user`, `type`, `update_user`, `update_time`, `create_time` From `team_user` Order by `create_time`", array());
	}

	//設定團隊成員資料
	private function set_data($team, $user, $type, $update_time, $pk_team, $pk_user) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("UPDATE `team_user` SET `team` = ?, `user` = ?, `type` = ?, `update_user` = ?  Where `update_time` = ? and team = ? and user = ? ", array($team, $user, $type, $_SESSION["userdata"]["uuid"], $update_time, $pk_team, $pk_user));
	}

	//刪除團隊成員資料
	private function del_data($pk_team, $pk_user) {
		return $this->data->executeAndFetchAll("DELETE From `team_user` Where team = ? and user = ? ", array($pk_team, $pk_user));
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
				if (isset($_REQUEST["team"]) && isset($_REQUEST["user"]) && isset($_REQUEST["type"])) {
					$result = $this->add_data($_REQUEST["team"], $_REQUEST["user"], $_REQUEST["type"]);
				}
				break;
			case "get":
				if($permission != 1 && $permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["team"]) && isset($_REQUEST["user"])) {
					return $this->get_data_team_user($_REQUEST["team"], $_REQUEST["user"]);
				}
				return $this->get_data();
				break;
			case "set":
				if($permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["team"]) && isset($_REQUEST["user"]) && isset($_REQUEST["type"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["team"]) && isset($_REQUEST["user"])) {
					$result = $this->set_data($_REQUEST["team"], $_REQUEST["user"], $_REQUEST["type"], $_REQUEST["update_time"], $_REQUEST["team"], $_REQUEST["user"]);
				}
				break;
			case "del":
				if($permission != 7) return $no_permission;
				if (isset($_REQUEST["team"]) && isset($_REQUEST["user"])) {
					$result = $this->del_data($_REQUEST["team"], $_REQUEST["user"]);
				}
				break;
		}
		return $result;
	}
}
?>