<?php
namespace vibranium;
/*
檔案管理程式
*/

class file {
	private function upload($name) {
		if(!isset($_FILES)) {
			$result["status"] = "Parameter error.";
			$result["message"] = "No FILES.";
			$result["code"] = -300;
			return $result;
		}
		if(!isset($_FILES['file'])) {
			$result["status"] = "Parameter error.";
			$result["message"] = "No file.";
			$result["code"] = -301;
			return $result;
		}
		if(!isset($_FILES['file']['tmp_name'])) {
			$result["status"] = "error";
			$result["message"] = "No tmp_name.";
			$result["code"] = -302;
			return $result;
		}
		$path = $_SERVER['DOCUMENT_ROOT']."/";
		$path .= "uploads/";
		$fullname = $path.$name;
		$fullpath = substr($fullname ,0 , strrpos($fullname, "/"));
		$paths = explode ("/", $fullpath);
		$checkpath = $paths[0];
		for ($i = 1; $i < count($paths);$i++) {
			$checkpath.= "/".$paths[$i];
			if (!file_exists($checkpath)) {
				if (!mkdir($checkpath)) {
					$result["status"] = "error";
					$result["message"] = "Upload path ".$checkpath." error.";
					$result["code"] = -303;
					return $result;
				}
			}
		}
		if (file_exists($fullname)) {
			$result["RemoveOldFile"] = "Yes";
			if (!unlink($fullname)) {
				$result["status"] = "error";
				$result["message"] = "Old file ".$name." delete fail.";
				$result["code"] = -402;
				return $result;
			}
		}
		if (!move_uploaded_file($_FILES['file']['tmp_name'], $fullname)){
			$result["status"] = "error";
			$result["message"] = "File ".$name." upload fail.";
			$result["code"] = -311;
			$result["tmp_name"] = $_FILES['file']['tmp_name'];
			return $result;
		}
		$result["status"] = "success";
		$result["code"] = 1;
		$result["result"]["count"] = 1;
		$result["result"]["filename"] = $name;
		return $result;
	}
	private function delete($name) {
		$path = $_SERVER['DOCUMENT_ROOT']."/";
		$path .= "uploads/";
		$fullname = $path.$name;
		if (!file_exists($fullname)) {
			$result["status"] = "error";
			$result["message"] = "File ".$name." not exists.";
			$result["code"] = -401;
			return $result;
		}
		if (!unlink($fullname)) {
			$result["status"] = "error";
			$result["message"] = "File ".$name." delete fail.";
			$result["code"] = -402;
			return $result;
		}
		$result["status"] = "success";
		$result["code"] = 1;
		$result["result"]["count"] = 1;
		$result["result"]["filename"] = $name;
		return $result;
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
			case "upload":
				if (isset($_REQUEST["name"])) {
					$result = $this->upload($_REQUEST["name"]);
				}
				break;
			case "delete":
				if (isset($_REQUEST["name"])) {
					$result = $this->delete($_REQUEST["name"]);
				}
				break;
		}
		return $result;
	}
}
?>
