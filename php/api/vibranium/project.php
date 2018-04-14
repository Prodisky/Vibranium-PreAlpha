<?php
namespace vibranium;
/*
project 照護計畫案基本資料
		uuid - UUID 去除連字號 - 的 UUID 字串
		name - 照護計畫案名稱
		memo - 備註
		startdate - 開始日期
		enddate - 結案日期
		captain_note - 隊長給的注意事項
		customer_note - 客戶給的注意事項
		from_customer - 最後修改注意事項的客戶
		finished - 是否結案 0:未結案 1:已結案
		update_user - 更新使用者的 UUID
		update_time - 更新時間

add 新增照護計畫案基本資料資料
	函數與參數：
		add_data($uuid, $name, $memo, $captain_note, $customer_note, $from_customer, $finished)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得照護計畫案基本資料資料
	函數與參數：
		get_data_uuid($uuid)
		get_data($finished)
	回傳資訊：
		執行結果、資料筆數與 uuid, name, memo, startdate, enddate, captain_note, customer_note, from_customer, finished, update_user, update_time 欄位資料或錯誤訊息。

search 搜尋照護計畫案基本資料資料
	函數與參數：
		search_data($q, $finished)
	搜尋欄位：
		name, memo
	回傳資訊：
		執行結果、資料筆數與 uuid, name, memo, startdate, enddate, captain_note, customer_note, from_customer, finished, update_user, update_time 欄位資料或錯誤訊息。

set 設定照護計畫案基本資料資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	記錄最後一次修改的使用者。
	函數與參數：
		set_data($name, $memo, $startdate, $enddate, $captain_note, $customer_note, $from_customer, $finished, $update_time, $pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除照護計畫案基本資料資料
	函數與參數：
		del_data($pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class project {
	private $data;

	//取得權限資料
	private function get_permission() {
		require_once __DIR__ . "/session.php";
		session::start();
		if (isset($_SESSION["userdata"]["uuid"])) {
			$datas = $this->data->executeAndFetchAll("SELECT `permission_project` as `permission` From `user` Where `uuid` = ?", array($_SESSION["userdata"]["uuid"]));
			foreach($datas["result"]["datas"] as $user) {
				return $user->permission;
			}
		}
		return 0;
	}
	//鎖定檢查
	private function lock_check() {
		require_once __DIR__ . "/session.php";
		session::start();
		require_once __DIR__ . "/database.php";
		if (isset($_REQUEST["uuid"]) && isset($_SESSION["userdata"]["uuid"]) && isset($_SESSION["userdata"]["deviceid"])) {
			$datas = $this->data->executeAndFetchAll("SELECT count(*) as `count` From `lock` Where `uuid` = ? and not (`user` = ? and `device` = ?)", array($_REQUEST["uuid"], $_SESSION["userdata"]["uuid"], $_SESSION["userdata"]["deviceid"]));
			foreach($datas["result"]["datas"] as $lock) {
				return $lock->count;
			}
		} else if (isset($_REQUEST["uuid"])) {
			$datas = $this->data->executeAndFetchAll("SELECT count(*) as `count` From `lock` Where `uuid` = ?", array($_REQUEST["uuid"]));
			foreach($datas["result"]["datas"] as $lock) {
				return $lock->count;
			}
		}
		return 1;
	}
	//新增照護計畫案基本資料資料
	private function add_data($uuid, $name, $memo, $captain_note, $customer_note, $from_customer, $finished) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("INSERT INTO `project`(`uuid`, `name`, `memo`, `captain_note`, `customer_note`, `from_customer`, `finished`, `update_user`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)", array($uuid, $name, $memo, $captain_note, $customer_note, $from_customer, $finished, $_SESSION["userdata"]["uuid"]));
	}

	//取得照護計畫案基本資料資料
	public function get_data_uuid($uuid) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `memo`, `startdate`, `enddate`, `captain_note`, `customer_note`, `from_customer`, `finished`, `update_user`, `update_time`, `create_time` From `project` Where `uuid` = ? Order by `startdate`, `create_time`", array($uuid));
	}

	public function get_data($finished) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `memo`, `startdate`, `enddate`, `captain_note`, `customer_note`, `from_customer`, `finished`, `update_user`, `update_time`, `create_time` From `project` Where `finished` = ?  Order by `startdate`, `create_time`", array($finished));
	}

	//搜尋照護計畫案基本資料資料
	private function search_data($q, $finished) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `memo`, `startdate`, `enddate`, `captain_note`, `customer_note`, `from_customer`, `finished`, `update_user`, `update_time`, `create_time` From `project` Where (name like ? or memo like ?) and `finished` = ?  Order by `startdate`, `create_time`", array( $q, $q, $finished));
	}

	//設定照護計畫案基本資料資料
	private function set_data($name, $memo, $startdate, $enddate, $captain_note, $customer_note, $from_customer, $finished, $update_time, $pk_uuid) {
		require_once __DIR__ . "/session.php";
		session::start();
		return $this->data->executeAndFetchAll("UPDATE `project` SET `name` = ?, `memo` = ?, `startdate` = ?, `enddate` = ?, `captain_note` = ?, `customer_note` = ?, `from_customer` = ?, `finished` = ?, `update_user` = ?  Where `update_time` = ? and uuid = ? ", array($name, $memo, $startdate, $enddate, $captain_note, $customer_note, $from_customer, $finished, $_SESSION["userdata"]["uuid"], $update_time, $pk_uuid));
	}

	//刪除照護計畫案基本資料資料
	private function del_data($pk_uuid) {
		return $this->data->executeAndFetchAll("DELETE From `project` Where uuid = ? ", array($pk_uuid));
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
		$data_lock["status"] = "error";
		$data_lock["message"] = "Data lock.";
		$data_lock["code"] = -701;
		$lock_status = $this->lock_check();
		$result["status"] = "error";
		$result["message"] = "Parameter error.";
		$result["code"] = -201;
		switch($function) {
			case "add":
				if($permission != 3 && $permission != 7) return $no_permission;
				if($lock_status != 0) return $data_lock;
				if (isset($_REQUEST["uuid"]) && isset($_REQUEST["name"]) && isset($_REQUEST["memo"]) && isset($_REQUEST["captain_note"]) && isset($_REQUEST["customer_note"]) && isset($_REQUEST["from_customer"]) && isset($_REQUEST["finished"])) {
					$result = $this->add_data($_REQUEST["uuid"], $_REQUEST["name"], $_REQUEST["memo"], $_REQUEST["captain_note"], $_REQUEST["customer_note"], $_REQUEST["from_customer"], $_REQUEST["finished"]);
				}
				break;
			case "get":
				if($permission != 1 && $permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["uuid"])) {
					return $this->get_data_uuid($_REQUEST["uuid"]);
				}
				if (isset($_REQUEST["finished"])) {
					return $this->get_data($_REQUEST["finished"]);
				}
			case "search":
				if($permission != 1 && $permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["q"])&&isset($_REQUEST["finished"])) {
					return $this->search_data("%".$_REQUEST["q"]."%", $_REQUEST["finished"]);
				}
			case "set":
				if($permission != 3 && $permission != 7) return $no_permission;
				if($lock_status != 0) return $data_lock;
				if (isset($_REQUEST["name"]) && isset($_REQUEST["memo"]) && isset($_REQUEST["startdate"]) && isset($_REQUEST["enddate"]) && isset($_REQUEST["captain_note"]) && isset($_REQUEST["customer_note"]) && isset($_REQUEST["from_customer"]) && isset($_REQUEST["finished"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["uuid"])) {
					$result = $this->set_data($_REQUEST["name"], $_REQUEST["memo"], $_REQUEST["startdate"], $_REQUEST["enddate"], $_REQUEST["captain_note"], $_REQUEST["customer_note"], $_REQUEST["from_customer"], $_REQUEST["finished"], $_REQUEST["update_time"], $_REQUEST["uuid"]);
				}
				break;
			case "del":
				if($permission != 7) return $no_permission;
				if($lock_status != 0) return $data_lock;
				if (isset($_REQUEST["uuid"])) {
					$result = $this->del_data($_REQUEST["uuid"]);
				}
				break;
		}
		return $result;
	}
}
?>