<?php

	#Clase Conexión.
//	require '/home/silvia.valdez/public_html/ECG/inc/conection.php';

?>
<!DOCTYPE HTML>
<html>
	<head>
		<?php
		#ÍNDICES PARA BD.
		$incremento=10000;	#Número de datos a consultar
		$indice1=0;
		$tabla = $_GET['z'];

		#Si se seleccionó un ECG...
		if($tabla != NULL) {
			#CONECTAR PHP A POSTGRES
			$db = pg_connect("dbname=electrodb user=postgres password=12AB34cd")
					or die("\n\nError al conectarse a la Base de Datos.\n");

			#LONGITUD DEL ELECTROCARDIOGRAMA
			$result2 = pg_query($db,"SELECT longitud AS tam FROM electrocardiografia
				WHERE id=(SELECT id_electrocardiografia FROM derivacion WHERE id=$tabla)");

			while($row=pg_fetch_assoc($result2))
				$limite=$row['tam'];	#Longitud del ECG

			#VALOR DE LA FRECUENCIA DE MUESTREO
			$result3 = pg_query($db,"SELECT frecuencia_muestreo AS freq FROM
				electrocardiografia WHERE id=(SELECT id_electrocardiografia FROM derivacion
				WHERE id=$tabla)");

			while($row=pg_fetch_assoc($result3))
				$freq=$row['freq'];	#Frecuencia de Muestreo

			#CONSULTA Y GUARDA UNA SUBSECUENCIA DE LA SEÑAL EN UN ARRAY
				#Parámetros de subsecuencia_arr(NombreCampo, ValorInicial, NumDeValores)
			$result = pg_query($db,"SELECT (subsecuencia_arr(signal, $indice1, $incremento)).f1,
					(subsecuencia_arr(signal, $indice1, $incremento)).f2 FROM derivacion
					WHERE id=$tabla");

			$i=0;
			while($row=pg_fetch_assoc($result)){
				$a[$i]=$row['f2'];	#Guarda los datos en un array
				$i++;
			}

			/* Promedio entre valores mínimo y máximo del ECG (se resta 0.5 al valor *
			 * para que la señal se grafique más cercana al centro del eje Y).	 */
			$mini = (min($a) + max($a)) / 2 - 0.5;

			#CIERRA CONEXIÓN
			pg_close($db);
//			desconectar();
		}
		?>

		<script>
		//VARIABLES GLOBALES JAVASCRIPT
		var tabla = "<?php echo $tabla ?>";
		var longECG = "<?php echo $limite; ?>";
		var freq = "<?php echo $freq; ?>";

		var segVis = 2;			//Cantidad de segundos que se visualizan.
		var incremento = freq * segVis;
		var dx= 20;			//Separación entre líneas.
		var mini = parseFloat("<?php echo $mini; ?>");
		var desplazamiento=0;
		var maxDespl = longECG/(freq*2/50);
		var segSelec=0; 
		var pos1 = 0;
		var pos2 = pos1 + incremento;
		var arreglo;
		var anotaciones;

		var opcion=0;
		var pacienteSelected = false;

		var descargarAnot = false;
		var subsecuencia = false;
		var inicioSubs = 0;
		var finSubs = 0;
		var noDatos = 30000;	//Valor soportado por un array PHP.
		</script>
	</head>

	<body>
	</body>
</html>
