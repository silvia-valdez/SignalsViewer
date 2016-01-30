<?php
	$path = "/home/silvia.valdez/public_html/ECG/tmp/";
	$id = $_GET["id"];

	$enlace = $path . $id;
	header ("Content-Disposition: attachment; filename=".$id." ");
	header ("Content-Type: application/octet-stream");
	header ("Content-Length: ".filesize($enlace));

	readfile($enlace);
?>
