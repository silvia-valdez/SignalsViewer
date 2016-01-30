<?php
	function conectar() {
		#ABRE CONEXIÓN
		pg_connect("dbname=electrodb user=postgres password=12AB34cd")
				or die("\n\nError al conectarse a la Base de Datos.\n");
	}

	function desconectar() {
		#CIERRA CONEXIÓN
		pg_close();
	}
?>
