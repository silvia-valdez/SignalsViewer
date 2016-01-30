<?php

	#Directorio donde se escriben los archivos.
	$path = "/home/silvia.valdez/public_html/ECG/tmp/";
	#Nombre del archivo, contenido en la URL.
	$id = $_GET['id'];

	$enlace = $path . $id;
	header ("Content-Disposition: attachment; filename=".$id." ");
	header ("Content-Type: application/octet-stream");
	header ("Content-Length: ".filesize($enlace));

	readfile($enlace);

?>

