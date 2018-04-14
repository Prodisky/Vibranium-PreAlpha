<?php
namespace vibranium;
/*
database 資料庫程式
使用 PDO
*/

class MySQL {
	public function executeAndFetchAll($query, $paramArray) {
		try {
			$connect = new \PDO("mysql:host=localhost; dbname=vibranium", "vibranium", "[DBPassword]");
			$connect->query("set names utf8mb4;");
			$prepare = $connect->prepare($query);
			if ($prepare) {
				if ($prepare->execute($paramArray)) {
					$result["status"] = "success";
					$result["code"] = 1;
					$result["result"]["count"] = $prepare->rowCount();
					$result["result"]["datas"] = $prepare->fetchAll(\PDO::FETCH_OBJ);
				} else {
					$result["status"] = "error";
					$result["code"] = -101;
					$result["message"] = "Execute failed.";
				}
			} else {
				$result["status"] = "error";
				$result["code"] = -102;
				$result["message"] = "Prepare failed.";
			}
			$connect = null;
		} catch (PDOException $e) {
			$result["status"] = "error";
			$result["code"] = -103;
			$result["message"] = "Connection failed: ".$e->getMessage();
		}
		return $result;
	}
}
?>
