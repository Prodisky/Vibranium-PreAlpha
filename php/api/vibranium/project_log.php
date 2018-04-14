<?php
namespace vibranium;
/*
project_log 照護紀錄
包含時程排定與執行時數與執行狀況。
		uuid - UUID 去除連字號 - 的 UUID 字串
		name - 名稱
		project_task - 照護任務
		plan_starttime - 計畫開始時間
		plan_endtime - 計畫完成時間
		plan_time - 計畫執行時數
		real_starttime - 實際開始時間
		real_endtime - 實際完成時間
		real_time - 實際執行時數
		note2crew - 給隊員的注意事項
		note2customer - 給客戶的注意事項
		feel_from_crew - 隊員此次工作感受
		score_from_customer - 客戶評分
		from_customer - 最後評分的客戶
		update_user - 更新使用者的 UUID
		update_time - 更新時間

add 新增照護紀錄資料
	函數與參數：
		add_data($uuid, $name, $project_task, $plan_time, $real_time, $note2crew, $note2customer, $feel_from_crew, $score_from_customer, $from_customer)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得照護紀錄資料
	函數與參數：
		get_data_uuid($uuid)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 uuid, name, project_task, plan_starttime, plan_endtime, plan_time, real_starttime, real_endtime, real_time, note2crew, note2customer, feel_from_crew, score_from_customer, from_customer, update_user, update_time 欄位資料或錯誤訊息。

set 設定照護紀錄資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
	函數與參數：
		set_data($name, $project_task, $plan_starttime, $plan_endtime, $plan_time, $real_starttime, $real_endtime, $real_time, $note2crew, $note2customer, $feel_from_crew, $score_from_customer, $from_customer, $update_time, $pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除照護紀錄資料
	函數與參數：
		del_data($pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class project_log {
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
	//新增照護紀錄資料
	private function add_data($uuid, $name, $project_task, $plan_time, $real_time, $note2crew, $note2customer, $feel_from_crew, $score_from_customer, $from_customer) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("INSERT INTO `project_log`(`uuid`, `name`, `project_task`, `plan_time`, `real_time`, `note2crew`, `note2customer`, `feel_from_crew`, `score_from_customer`, `from_customer`, `update_user`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($uuid, $name, $project_task, $plan_time, $real_time, $note2crew, $note2customer, $feel_from_crew, $score_from_customer, $from_customer, $_SESSION["userdata"]["uuid"]));
	}

	//取得照護紀錄資料
	public function get_data_uuid($uuid) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `project_task`, `plan_starttime`, `plan_endtime`, `plan_time`, `real_starttime`, `real_endtime`, `real_time`, `note2crew`, `note2customer`, `feel_from_crew`, `score_from_customer`, `from_customer`, `update_user`, `update_time`, `create_time` From `project_log` Where `uuid` = ? Order by `create_time`", array($uuid));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `project_task`, `plan_starttime`, `plan_endtime`, `plan_time`, `real_starttime`, `real_endtime`, `real_time`, `note2crew`, `note2customer`, `feel_from_crew`, `score_from_customer`, `from_customer`, `update_user`, `update_time`, `create_time` From `project_log` Order by `create_time`", array());
	}

	//設定照護紀錄資料
	private function set_data($name, $project_task, $plan_starttime, $plan_endtime, $plan_time, $real_starttime, $real_endtime, $real_time, $note2crew, $note2customer, $feel_from_crew, $score_from_customer, $from_customer, $update_time, $pk_uuid) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("UPDATE `project_log` SET `name` = ?, `project_task` = ?, `plan_starttime` = ?, `plan_endtime` = ?, `plan_time` = ?, `real_starttime` = ?, `real_endtime` = ?, `real_time` = ?, `note2crew` = ?, `note2customer` = ?, `feel_from_crew` = ?, `score_from_customer` = ?, `from_customer` = ?, `update_user` = ?  Where `update_time` = ? and uuid = ? ", array($name, $project_task, $plan_starttime, $plan_endtime, $plan_time, $real_starttime, $real_endtime, $real_time, $note2crew, $note2customer, $feel_from_crew, $score_from_customer, $from_customer, $_SESSION["userdata"]["uuid"], $update_time, $pk_uuid));
	}

	//刪除照護紀錄資料
	private function del_data($pk_uuid) {
		return $this->data->executeAndFetchAll("DELETE From `project_log` Where uuid = ? ", array($pk_uuid));
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
				if (isset($_REQUEST["uuid"]) && isset($_REQUEST["name"]) && isset($_REQUEST["project_task"]) && isset($_REQUEST["plan_time"]) && isset($_REQUEST["real_time"]) && isset($_REQUEST["note2crew"]) && isset($_REQUEST["note2customer"]) && isset($_REQUEST["feel_from_crew"]) && isset($_REQUEST["score_from_customer"]) && isset($_REQUEST["from_customer"])) {
					$result = $this->add_data($_REQUEST["uuid"], $_REQUEST["name"], $_REQUEST["project_task"], $_REQUEST["plan_time"], $_REQUEST["real_time"], $_REQUEST["note2crew"], $_REQUEST["note2customer"], $_REQUEST["feel_from_crew"], $_REQUEST["score_from_customer"], $_REQUEST["from_customer"]);
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
				if (isset($_REQUEST["name"]) && isset($_REQUEST["project_task"]) && isset($_REQUEST["plan_starttime"]) && isset($_REQUEST["plan_endtime"]) && isset($_REQUEST["plan_time"]) && isset($_REQUEST["real_starttime"]) && isset($_REQUEST["real_endtime"]) && isset($_REQUEST["real_time"]) && isset($_REQUEST["note2crew"]) && isset($_REQUEST["note2customer"]) && isset($_REQUEST["feel_from_crew"]) && isset($_REQUEST["score_from_customer"]) && isset($_REQUEST["from_customer"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["uuid"])) {
					$result = $this->set_data($_REQUEST["name"], $_REQUEST["project_task"], $_REQUEST["plan_starttime"], $_REQUEST["plan_endtime"], $_REQUEST["plan_time"], $_REQUEST["real_starttime"], $_REQUEST["real_endtime"], $_REQUEST["real_time"], $_REQUEST["note2crew"], $_REQUEST["note2customer"], $_REQUEST["feel_from_crew"], $_REQUEST["score_from_customer"], $_REQUEST["from_customer"], $_REQUEST["update_time"], $_REQUEST["uuid"]);
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