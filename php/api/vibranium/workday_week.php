<?php
namespace vibranium;
/*
workday_week 每週可工作時間的週期設定
		uuid - UUID 去除連字號 - 的 UUID 字串
		user - 使用者
		weekday - 星期 0:日 1:一 2:二 3:三 4:四 5:五 6:六
		starttime - 開始時間
		endtime - 結束時間
		memo - 備註
		update_user - 更新使用者的 UUID
		update_time - 更新時間

add 新增每週可工作時間的週期設定資料
	函數與參數：
		add_data($uuid, $user, $weekday, $memo)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得每週可工作時間的週期設定資料
	函數與參數：
		get_data_uuid($uuid)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 uuid, user, weekday, starttime, endtime, memo, update_user, update_time 欄位資料或錯誤訊息。

set 設定每週可工作時間的週期設定資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
	函數與參數：
		set_data($user, $weekday, $starttime, $endtime, $memo, $update_time, $pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除每週可工作時間的週期設定資料
	函數與參數：
		del_data($pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class workday_week {
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
	//新增每週可工作時間的週期設定資料
	private function add_data($uuid, $user, $weekday, $memo) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("INSERT INTO `workday_week`(`uuid`, `user`, `weekday`, `memo`, `update_user`) VALUES(?, ?, ?, ?, ?)", array($uuid, $user, $weekday, $memo, $_SESSION["userdata"]["uuid"]));
	}

	//取得每週可工作時間的週期設定資料
	public function get_data_uuid($uuid) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `user`, `weekday`, `starttime`, `endtime`, `memo`, `update_user`, `update_time`, `create_time` From `workday_week` Where `uuid` = ? Order by `create_time`", array($uuid));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `user`, `weekday`, `starttime`, `endtime`, `memo`, `update_user`, `update_time`, `create_time` From `workday_week` Order by `create_time`", array());
	}

	//設定每週可工作時間的週期設定資料
	private function set_data($user, $weekday, $starttime, $endtime, $memo, $update_time, $pk_uuid) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("UPDATE `workday_week` SET `user` = ?, `weekday` = ?, `starttime` = ?, `endtime` = ?, `memo` = ?, `update_user` = ?  Where `update_time` = ? and uuid = ? ", array($user, $weekday, $starttime, $endtime, $memo, $_SESSION["userdata"]["uuid"], $update_time, $pk_uuid));
	}

	//刪除每週可工作時間的週期設定資料
	private function del_data($pk_uuid) {
		return $this->data->executeAndFetchAll("DELETE From `workday_week` Where uuid = ? ", array($pk_uuid));
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
				if (isset($_REQUEST["uuid"]) && isset($_REQUEST["user"]) && isset($_REQUEST["weekday"]) && isset($_REQUEST["memo"])) {
					$result = $this->add_data($_REQUEST["uuid"], $_REQUEST["user"], $_REQUEST["weekday"], $_REQUEST["memo"]);
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
				if (isset($_REQUEST["user"]) && isset($_REQUEST["weekday"]) && isset($_REQUEST["starttime"]) && isset($_REQUEST["endtime"]) && isset($_REQUEST["memo"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["uuid"])) {
					$result = $this->set_data($_REQUEST["user"], $_REQUEST["weekday"], $_REQUEST["starttime"], $_REQUEST["endtime"], $_REQUEST["memo"], $_REQUEST["update_time"], $_REQUEST["uuid"]);
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