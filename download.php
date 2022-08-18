<?php
if ($_GET["f"] != "index.php" && $_GET["f"] != "download.php")
{
	header('Content-Type: application/octet-stream');
	header("Content-Transfer-Encoding: Binary"); 
	header("Content-disposition: attachment; filename=\"" . $_GET["f"] . "\""); 
	readfile($_GET["f"]);
	unlink($_GET["f"]);
}
?>