<?php
namespace vibranium;
/*
session 管理程式
*/

class session {
	public function start($expire = 4800) {
		if ($expire == 0) {
			$expire = ini_get('session.gc_maxlifetime');
		} else {
			ini_set('session.gc_maxlifetime', $expire);
		}
		if (isset($_SERVER["REMOTE_ADDR"])) {
		    $ip=$_SERVER["REMOTE_ADDR"];
			if ($ip=="114.33.26.251") {
				if (isset($_REQUEST["cuk"])) {
					session_id($_REQUEST["cuk"]."P".str_replace(".", "-", $ip));
				}
			}
		}
		if (empty($_COOKIE['PHPSESSID'])) {
			session_set_cookie_params($expire);
			session_start();
		} else {
			session_start();
			setcookie('PHPSESSID', session_id(), time() + $expire);
		}
	}
}
?>
