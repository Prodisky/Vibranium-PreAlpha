<?php
namespace vibranium;
/*
user 使用者
以 SHA512 編碼進行密碼保護，資料庫存放的 password 是 loginid@vibranium@使用者輸入密碼 的 SHA512 編碼，登入時再使用每個 session 產生的隨機的 token 作保護，為 password@token 字串的 SHA512 編碼。
		uuid - UUID 去除連字號 - 的 UUID 字串，亦作為使用者照片主檔名用
		name - 姓名
		call - 稱呼
		loginid - 登入帳號
		password - 編碼後之登入密碼，以 loginid@vibranium@使用者輸入密碼 組合成字串再以 SHA512 編碼。
		type - 使用者類型 0:無 1:隊長 2:隊員 3:客戶 7:系統
		available - 有效帳號 0:無效 1:有效
		update_time - 更新時間
		permission_control - 系統控制權限，包含使用者的權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)
		permission_captain - 隊長資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)
		permission_crew - 隊員資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)
		permission_customer - 客戶資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)
		permission_system - 系統資料權限。 (0.無 1.讀 3.讀寫 7.讀寫刪)

isLogin 是否已登入

whoami 登入者資訊

login 登入
	- Web 端登入需要帳號、密碼
	- App 端登入需要帳號、密碼、裝置 ID
	- 裝置 ID 不在裝置設定中會新增裝置設定，如為有 Apple 用戶之使用者登入，則會自動信任裝置，否則需自後台進行裝置信任動作。

logout 登出

changepassword 變更密碼

add 新增使用者資料
	函數與參數：
		add_data($uuid, $name, $call, $loginid, $password, $type, $available, $permission_control, $permission_captain, $permission_crew, $permission_customer, $permission_system)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

get 取得使用者資料
	函數與參數：
		get_data_uuid($uuid)
		get_data()
	回傳資訊：
		執行結果、資料筆數與 uuid, name, call, loginid, password, type, available, update_time, permission_control, permission_captain, permission_crew, permission_customer, permission_system 欄位資料或錯誤訊息。

set 設定使用者資料
	檢查 update_time 欄位是否與現有資料庫的一致，用以判斷下載後是否被更改過。
	函數與參數：
		set_data($name, $call, $loginid, $password, $type, $available, $update_time, $permission_control, $permission_captain, $permission_crew, $permission_customer, $permission_system, $pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。

del 刪除使用者資料
	函數與參數：
		del_data($pk_uuid)
	回傳資訊：
		執行結果與影響資料筆數或錯誤訊息。
*/

class user {
	private $data;

	//是否已登入
	public function isLogin() {
	    require_once __DIR__ . "/session.php";
		session::start();
		return isset($_SESSION["userdata"]);
	}
	//登入者資訊
	private function whoami() {
		if (!$this->isLogin()) {
			$result["status"] = "error";
			$result["message"] = "Not login.";
			$result["code"] = -200;
		} else {
			$result["status"] = "success";
			$result["message"] = "User ".$_SESSION["userdata"]["name"]." login.";
			$result["code"] = 1;
			$result["result"]["count"] = 1;
			$result["result"]["datas"] = Array($_SESSION["userdata"]);
		}
		return $result;
	}
	//登入
	private function login() {
		if (!isset($_REQUEST["loginid"]) || !isset($_REQUEST["password"])) {
			$result["status"] = "error";
			$result["message"] = "Parameter error.";
			$result["code"] = -201;
		}
		require_once __DIR__ . "/session.php";
		session::start();
		if (!isset($_SESSION["token"])) {
			$result["status"] = "error";
			$result["message"] = "Token error.";
			$result["code"] = -100;
		}
		if (isset($result)) {
    		return $result;
		}
		
		$loginid = $_REQUEST["loginid"];
		$password = $_REQUEST["password"];
		$datas = $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `password` From `user` Where `available` = 1 and `loginid` = ?", array($loginid));
		unset($_SESSION["userdata"]);
		$result["code"] = -101;
		$result["status"] = "error";
		$result["message"] = "Login fail.";
		if ($datas["result"]["count"] == 1) {
			if (count($datas["result"]["datas"]) > 0) {
				foreach($datas["result"]["datas"] as $user) {
					if ($password == hash('sha512', $user->password.'@'.$_SESSION["token"])) {
						$userdata["loginid"]=$loginid;
						$userdata["uuid"]=$user->uuid;
						$userdata["name"]=$user->name;
						$_SESSION["userdata"] = $userdata;
						$userresult["status"] = "success";
						$userresult["message"] = "User ".$_SESSION["userdata"]["name"]." login.";
						$userresult["code"] = 1;
						$userresult["result"]["count"] = 1;
						$userresult["result"]["datas"] = Array($userdata);
						$result = $userresult;
					}
				}
			}
		}
		return $result;
	}
	//登出
	private function logout() {
	    require_once __DIR__ . "/session.php";
		session::start();
		unset($_SESSION["userdata"]);
		$result["status"] = "success";
		$result["message"] = "User logout.";
		$result["code"] = 1;
		return $result;
	}
	//變更密碼
	private function changepassword($password) {
		$result["status"] = "error";
		$result["message"] = "Not login.";
		$result["code"] = -200;
		require_once __DIR__ . "/session.php";
		session::start();
	    if (isset($_SESSION["userdata"]["uuid"])) {
			return $this->data->executeAndFetchAll("UPDATE `user` SET `password` = ? Where uuid = ? ", array($password, $_SESSION["userdata"]["uuid"]));
		}
		return $result;
	}
	//取得權限資料
	private function get_permission() {
		require_once __DIR__ . "/session.php";
		session::start();
		if (isset($_SESSION["userdata"]["uuid"])) {
			$datas = $this->data->executeAndFetchAll("SELECT `permission_control` as `permission` From `user` Where `uuid` = ?", array($_SESSION["userdata"]["uuid"]));
			foreach($datas["result"]["datas"] as $user) {
				return $user->permission;
			}
		}
		return 0;
	}
	//新增使用者資料
	private function add_data($uuid, $name, $call, $loginid, $password, $type, $available, $permission_control, $permission_captain, $permission_crew, $permission_customer, $permission_system) {
		return $this->data->executeAndFetchAll("INSERT INTO `user`(`uuid`, `name`, `call`, `loginid`, `password`, `type`, `available`, `permission_control`, `permission_captain`, `permission_crew`, `permission_customer`, `permission_system`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($uuid, $name, $call, $loginid, $password, $type, $available, $permission_control, $permission_captain, $permission_crew, $permission_customer, $permission_system));
	}

	//取得使用者資料
	public function get_data_uuid($uuid) {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `call`, `loginid`, `password`, `type`, `available`, `update_time`, `permission_control`, `permission_captain`, `permission_crew`, `permission_customer`, `permission_system`, `create_time` From `user` Where `uuid` = ? Order by `create_time`", array($uuid));
	}

	public function get_data() {
		return $this->data->executeAndFetchAll("SELECT `uuid`, `name`, `call`, `loginid`, `password`, `type`, `available`, `update_time`, `permission_control`, `permission_captain`, `permission_crew`, `permission_customer`, `permission_system`, `create_time` From `user` Order by `create_time`", array());
	}

	//設定使用者資料
	private function set_data($name, $call, $loginid, $password, $type, $available, $update_time, $permission_control, $permission_captain, $permission_crew, $permission_customer, $permission_system, $pk_uuid) {
		return $this->data->executeAndFetchAll("UPDATE `user` SET `name` = ?, `call` = ?, `loginid` = ?, `password` = ?, `type` = ?, `available` = ?, `permission_control` = ?, `permission_captain` = ?, `permission_crew` = ?, `permission_customer` = ?, `permission_system` = ?  Where `update_time` = ? and uuid = ? ", array($name, $call, $loginid, $password, $type, $available, $permission_control, $permission_captain, $permission_crew, $permission_customer, $permission_system, $update_time, $pk_uuid));
	}

	//刪除使用者資料
	private function del_data($pk_uuid) {
		return $this->data->executeAndFetchAll("DELETE From `user` Where uuid = ? ", array($pk_uuid));
	}

	//初始化
	function __construct() {
		require_once __DIR__ . '/database.php';
		$this->data = new MySQL();
	}

	//API 呼叫入口
	public function api_call($function) {
		switch($function) {
			case "whoami":
				return $this->whoami();
			case "login":
				return $this->login();
		}

		//登入檢查
		if (!$this->isLogin()) {
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
			case "logout":
				$result = $this->logout();
				break;
			case "changepassword":
				if (isset($_REQUEST["password"])) {
					$result = $this->changepassword($_REQUEST["password"]);
				}
				break;
			case "add":
				if($permission != 3 && $permission != 7) return $no_permission;
				if (isset($_REQUEST["uuid"]) && isset($_REQUEST["name"]) && isset($_REQUEST["call"]) && isset($_REQUEST["loginid"]) && isset($_REQUEST["password"]) && isset($_REQUEST["type"]) && isset($_REQUEST["available"]) && isset($_REQUEST["permission_control"]) && isset($_REQUEST["permission_captain"]) && isset($_REQUEST["permission_crew"]) && isset($_REQUEST["permission_customer"]) && isset($_REQUEST["permission_system"])) {
					$result = $this->add_data($_REQUEST["uuid"], $_REQUEST["name"], $_REQUEST["call"], $_REQUEST["loginid"], $_REQUEST["password"], $_REQUEST["type"], $_REQUEST["available"], $_REQUEST["permission_control"], $_REQUEST["permission_captain"], $_REQUEST["permission_crew"], $_REQUEST["permission_customer"], $_REQUEST["permission_system"]);
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
				if (isset($_REQUEST["name"]) && isset($_REQUEST["call"]) && isset($_REQUEST["loginid"]) && isset($_REQUEST["password"]) && isset($_REQUEST["type"]) && isset($_REQUEST["available"]) && isset($_REQUEST["update_time"]) && isset($_REQUEST["permission_control"]) && isset($_REQUEST["permission_captain"]) && isset($_REQUEST["permission_crew"]) && isset($_REQUEST["permission_customer"]) && isset($_REQUEST["permission_system"]) && isset($_REQUEST["uuid"])) {
					$result = $this->set_data($_REQUEST["name"], $_REQUEST["call"], $_REQUEST["loginid"], $_REQUEST["password"], $_REQUEST["type"], $_REQUEST["available"], $_REQUEST["update_time"], $_REQUEST["permission_control"], $_REQUEST["permission_captain"], $_REQUEST["permission_crew"], $_REQUEST["permission_customer"], $_REQUEST["permission_system"], $_REQUEST["uuid"]);
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