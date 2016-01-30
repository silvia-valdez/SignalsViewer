<?php
	#Límite de memoria para ejecución del script php.
//	ini_set("memory_limit","512M");	

	#Clase AJAX.
	require '/home/silvia.valdez/public_html/ECG/xajax/xajax_core/xajax.inc.php';
	#Clase Conexión.
	require '/home/silvia.valdez/public_html/ECG/inc/conection.php';

	#Instanciamos el objeto de la clase xajax.
	$xajax = new xajax();

	#Registramos las funciones.
	$xajax->register(XAJAX_FUNCTION, 'consultarDatos');
	$xajax->register(XAJAX_FUNCTION, 'consultarAnotaciones');
	$xajax->register(XAJAX_FUNCTION, 'descargarSeñal');
	#Procesamos el registro.
	$xajax->processRequest();

	function consultarDatos($pos, $incremento, $limite, $tabla) {
		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

		#ACTUALIZAR ÍNDICES.
		$indice1 = $pos - $incremento/2;
		if($indice1<0) {
			$indice1=0;
		}
		$indice2 = $indice1 + $incremento;
		if($indice2>$limite) {
			$indice2 = $limite;
			$indice1 = $indice2 - $incremento;
		}

			conectar();

				#Parámetros de subsecuencia_arr(NombreCampo, ValorInicial, NumDeValoresAconsultar)
			$result = pg_query("SELECT (subsecuencia_arr(signal, $indice1, $incremento)).f2,
				posicion FROM derivacion WHERE id=$tabla");
			$result2 = pg_query("SELECT nombre, id_paciente FROM electrocardiografia WHERE
				id=(SELECT id_electrocardiografia FROM derivacion WHERE id=$tabla)");

			desconectar();

		$i=0;
		while($row=pg_fetch_assoc($result)){
			$a[$i]=$row['f2'];		#Señal.
			$posicion=$row['posicion']; 	#Tipo de derivación.
			$i++;
		}

		while($row=pg_fetch_assoc($result2)) {
			$nombreElectro=$row['nombre'];		#Nombre del ECG.
			$idPaciente=$row['id_paciente'];	#ID del paciente.
		}

		#Se asignan los valores de los índices a controles ocultos.
		$output->Assign("indice1","innerHTML",$indice1);
		$output->Assign("indice2","innerHTML",$indice2);

		#Información sobre la señal.
		$output->Assign("idPaciente","innerHTML",$idPaciente);
		$output->Assign("idECG","innerHTML",$nombreElectro);
		$output->Assign("idDerivacion","innerHTML",$posicion);

		#Retornamos la salida XAJAX.	 
		$output->setReturnValue($a);
		return $output;
	}

	function consultarAnotaciones($indice1, $incremento, $tabla) {	
			conectar();

			#CONSULTAR ANOTACIONES.
			$result3 = pg_query("SELECT indice, nota FROM anotacion WHERE indice IN
					(SELECT (subsecuencia_arr(signal, $indice1, $incremento)).f1
					FROM derivacion WHERE id=$tabla) AND id_derivacion=$tabla");

			desconectar();

		$i = 0;
		while($row=pg_fetch_assoc($result3)) {
			#Guardar los datos en un array bidimensional.
			$b[$i][0] = (int)$row['indice'];
			$b[$i][1] = (int)$row['nota'];

			$i++;
		}

		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

		#Retornamos la salida XAJAX.	 
		$output->setReturnValue($b);
		return $output;
	}


	function obtenerAnotacion($p) {
		switch($p) {
			case 1: return 'N';
		
			case 2: return 'L';
		
			case 3: return 'R';
		
			case 4: return 'A';
		
			case 5: return 'a';
		
			case 6: return 'J';
		
			case 7: return 'S';
		
			case 8: return 'V';
		
			case 9: return 'F';
		
			case 10: return '[';
		
			case 11: return '!';
		
			case 12: return ']';
		
			case 13: return 'e';
		
			case 14: return 'j';
		
			case 15: return 'E';
		
			case 16: return '/';
		
			case 17: return 'f';
		
			case 18: return 'x';
		
			case 19: return 'Q';
		
			case 20: return '|';
		
			case 21: return '~';
		
			case 22: return '+';
	
			case 23: return '"';
		}	
	}

	function escribirArchivo($datos,$anot,$nombreArchivo) {
		#Si el archivo no existe, intenta crearlo. Se coloca el puntero al final del archivo.
		if($file = fopen("/home/silvia.valdez/public_html/ECG/tmp/" . $nombreArchivo, "a")) {
			if($anot) {
				for($i=0; $i < count($datos); $i++) {
					fwrite($file, $datos[$i][0] . " ");			#Índice.
					fwrite($file, number_format($datos[$i][1], 6) . " ");	#Voltaje.
					fwrite($file, $datos[$i][2] . " ");			#Anotación.
					fwrite($file, "\n");
				}
			}
			else {
				for($i=0; $i < count($datos); $i++) {
					fwrite($file, $datos[$i][0] . " ");			#Índice.
					fwrite($file, number_format($datos[$i][1], 6) . " ");	#Voltaje.
					fwrite($file, "\n");
				}
			}
			#Cierra el archivo.
			fclose($file);
		}
		else {
			#Si ocurre un error al crear el archivo, detiene el script.
			exit;
		}
	}

	function descargarSeñal($anot,$tabla,$indice1,$incremento,$nombreArchivo) {
		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

			conectar();

			#Parámetros de subsecuencia_arr: (NombreCampo, ValorInicial, NumDeValoresAconsultar)
			$result4 = pg_query("SELECT (subsecuencia_arr(signal,$indice1,$incremento)).f1,(subsecuencia_arr(signal,$indice1,$incremento)).f2 FROM derivacion WHERE id=$tabla");

			if($anot) {
				$result5 = pg_query("SELECT indice, nota FROM anotacion WHERE indice IN (SELECT (subsecuencia_arr(signal,$indice1,$incremento)).f1 FROM derivacion WHERE id=$tabla) AND id_derivacion=$tabla");
			}

			desconectar();

		#Guardar señal en un array.
		$i = 0;
		while($row=pg_fetch_assoc($result4)) {
			#Guardar los datos en un array bidimensional.
			$s[$i][0] = (int)$row['f1'];	#Índice.
			$s[$i][1] = (float)$row['f2'];	#Voltaje.

			$i++;
		}

		if($anot) {
			#Guardar anotaciones.
			$i = 0;
			while($row=pg_fetch_assoc($result5)) {
				#Guardar los datos en un array bidimensional.
				$a[$i][0] = (int)$row['indice'];
				$a[$i][1] = $row['nota'];

				$i++;
			}

			#Guarda en un arreglo el resultado de ambas consultas.
			for($i=0; $i < count($s); $i++) {
				for($k=0; $k < count($a); $k++) {
					#Si el índice actual coincide con el de una anotación:						
					if($s[$i][0] == $a[$k][0]) {
						$s[$i][2] = obtenerAnotacion($a[$k][1]);	#Nota.
						break;
					}
					else {
						#En caso de no haber anotación, escribe "-".
						$s[$i][2] = "-";	
					}
				}
			}
		}

		escribirArchivo($s,$anot,$nombreArchivo);
		#$output->alert("OK!");

		#Retornamos la salida XAJAX.
		$output->setReturnValue(true);
		return $output;
	}
?>
