<?php
namespace vibranium;
//建立 .htaccess
header("Content-Type: text/plain");
$htaccess = "";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "RewriteEngine on\n";
$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-l\n";
$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "RewriteRule ^(.*)$ index.php?data=$1 [QSA,L]\n";
$htaccess .= "</IfModule>";
$htaccessFile = fopen(".htaccess", "w");
fputs($htaccessFile, $htaccess);
fclose($htaccessFile);
if (file_exists(".htaccess")) echo ".htaccess installed.";
else echo ".htaccess install fail.";
?>
