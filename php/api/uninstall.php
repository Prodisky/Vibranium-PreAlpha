<?php
namespace vibranium;
//移除 .htaccess
header("Content-Type: text/plain");
if (file_exists(".htaccess")) {
	unlink(".htaccess");
	echo "Uninstall .htaccess.";
} else echo ".htaccess not installed.";
?>
