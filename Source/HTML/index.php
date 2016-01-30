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
	$xajax->register(XAJAX_FUNCTION, 'eliminarArchivo');
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

	function eliminarArchivo($nombreArchivo) {
		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

		$path = "/home/silvia.valdez/public_html/ECG/tmp/";
		$archivo = $path . $nombreArchivo;

		if(file_exists($archivo)) {
			unlink($archivo);
			$output->setReturnValue(true);
		}
		else {
			$output->setReturnValue(false);
		}

		return $output;
	}
?>

<?php 
	require '/home/silvia.valdez/public_html/ECG/inc/vars.php';
	require '/home/silvia.valdez/public_html/ECG/inc/grid.php';
	require '/home/silvia.valdez/public_html/ECG/inc/scrollBar.php';
	require '/home/silvia.valdez/public_html/ECG/inc/download.php';
?>

<!DOCTYPE HTML>
<html>
	<head>
		<title> ECG Signals Viewer </title>

		<?php
		   #Indicamos al objeto xajax se encargue de generar el javascript necesario.
		   $xajax->printJavascript("/ECG/xajax/");
		?> 

		<script>		
		//Valida la entrada de dígitos en inputs.
	   	function validaDigitos(e) {
			var keynum = window.event ? window.event.keyCode : e.which;
			if (keynum == 8)
				return true;					 
			return /\d/.test(String.fromCharCode(keynum));
		};
		
		function actualizarIndices() {
			if(tabla == "") {
				pintarGrid();
				escribirEscalas();
			}
			else {
				var indice1 = document.getElementById('indice1').innerHTML;
				var indice2 = document.getElementById('indice2').innerHTML;
				var incremento = "<?php echo $incremento; ?>";
				var limite = "<?php echo $limite; ?>";
				var canMove = false;

				if(pos1 <= indice1 && dragok == false) {
					//Se llama de manera síncrona a las funciones xajax
					//para que regresen la subsecuencia de datos y anotaciones.
					arreglo =  xajax.call('consultarDatos',{mode:'synchronous',
							parameters:[pos2,incremento,limite,tabla]});	//Subsecuencia.

					indice1 = document.getElementById('indice1').innerHTML;
					anotaciones =  xajax.call('consultarAnotaciones',{mode:'synchronous',
							parameters:[indice1,incremento,tabla]});	//Anotaciones.

					canMove = true;
				}
				else if(pos2 > indice2 && dragok == false) {
					//Se llama de manera síncrona a las funciones xajax
					//para que regresen la subsecuencia de datos y anotaciones.
					arreglo =  xajax.call('consultarDatos',{mode:'synchronous',
							parameters:[pos2,incremento,limite,tabla]}); 	//Subsecuencia.

					indice1 = document.getElementById('indice1').innerHTML;
					anotaciones =  xajax.call('consultarAnotaciones',{mode:'synchronous',
							parameters:[indice1,incremento,tabla]});	//Anotaciones.

					canMove = true;
				}
				else if(pos1 < indice1 && dragok || pos2 > indice2 && dragok) {
					canMove = false;
				}
				else {
					canMove = true;
				}

				if(canMove) {
					pintarGrid();		
					indice1 = document.getElementById('indice1').innerHTML;
					graficarAnotaciones(indice1);
					graficarDatos(arreglo, indice1);
					escribirEscalas();	
				}
				actualizarSeg();
			}
		};
		</script>

		<script>
		function validaSeg(seg) {
			if(seg > desplECG*segVis) {
				return false;
			}
			else {
				return true;
			}
		};
		</script>

		<script>
		//Localiza en el ECG un segudo indicado por el usuario.
		function localizaSegundo() {
			if(tabla == "") { }
			else {
				var campo=document.getElementById("segundo");
				segSelec=parseInt(campo.value);

				if(validaSeg(segSelec) == false) {
					alert("El segundo especificado supera la longitud del ECG.\n("
						+ desplECG*segVis + " segundos)");
				}
				else {
					if(segSelec <= 0) {
						pos1 = 0;
						pos2 = pos1+incremento;
						desplazamiento = 0;
					}
					else {
						if(segSelec == longECG/freq)
							//Comienza a graficar dos segs antes del fin del ECG.
							pos1 = (segSelec - segVis) * freq;	
						else
							//Comienza a graficar un segundo antes del indicado por el usuario.
							pos1 = (segSelec - 1) * freq;		

						pos2 = pos1 + incremento;
						desplazamiento = segSelec * 25 - 25;

						if(desplazamiento == maxDespl-25)
							desplazamiento = desplazamiento - 25;
					}
					x = segSelec * (longTrack/(desplECG*segVis)) + 44;
					xAnt = x;

					actualizarIndices();
					actualizarSeg();
				}
			}
		};
		</script>

		<script>
		//Mensaje que indica la posicion (seg/min) que se visualiza.
		function actualizarSeg() {
			var segInicio = 0;
			var segFin = 0;
			var segString = '';
			var unidad = ' s ';
			var fixed='0';

			if(longECG != 0) {
				segInicio = (desplazamiento / freq)*(freq / 25);
				segFin = segInicio + segVis;
				segTotal = longECG / freq;

				if(segInicio>=60) {
					segInicio = segInicio/60;
					unidad = ' min ';
				}
				else {
					unidad = ' s ';
				}
				if(segInicio % 1 != 0){
					fixed='2';
				}
				else {
					fixed='0';
				}
				document.getElementById('lblsegInicial').innerHTML = 'DESDE '
					+ segInicio.toFixed(fixed) + unidad;

				if(segFin>=60) {
					segFin = segFin/60;
					unidad = ' min ';
				}
				else {
					unidad = ' s ';
				}
				if(segFin % 1 != 0) {
					fixed='2';
				}
				else {
					fixed='0';
				}
				document.getElementById('lblsegFinal').innerHTML = 'HASTA '
					+ segFin.toFixed(fixed) + unidad;

				if(segTotal>=60) {
					if(segTotal % 1 != 0) {
						fixed='2';
					}
					else {
						fixed='0';
					}

					segString = '(' + segTotal.toFixed(fixed) + ' s)';
					segTotal = segTotal/60;
					unidad = ' min ';
				}	
				else {
					unidad = ' s ';
				}	
				if(segTotal % 1 != 0) {
					fixed='2';
				}
				else {
					fixed='0';
				}
				document.getElementById('lblsegTotal').innerHTML = 'DE '
					+ segTotal.toFixed(fixed) + unidad + segString;
			}		
		};
		</script>		

		<script>
		window.onload = function() {
			initScroll();
			actualizarIndices();

			if(tabla == "") { }
			else {
				var c=document.getElementById("scrollbar");
				c.onmousedown = myDown;
				document.onmouseup = myUp;
			}	
		};
		</script>
	</head>

	<body>	
		<header>
			<center><b><h1>ECG Signals Viewer</h1></b></center>
		</header>

		<div align=center>
		<!-- Grid -->
		<canvas  id="lienzo" width="<?php echo $widthGrid; ?>" height="<?php echo $heightGrid; ?>">
			El navegador no soporta canvas HTML5.
		</canvas>
		<br>
		<!-- Scrollbar -->
		<canvas id="scrollbar" width="<?php echo $widthScrollbar; ?>" height="<?php echo $heightScrollbar; ?>"></canvas>

		<script>
		//Id y contexto del grid (canvas).
		var c=document.getElementById("lienzo");
		var ctx=c.getContext("2d");
		</script>

		<!-- DATOS BÁSICOS DEL ELECTRO -->
		<br><br>
		<i><label>ID Paciente: </label> <b><label id="idPaciente"></label> </b>		| 
		   <label>Electrocardiografía: </label> <b><label id="idECG"></label> </b> 	| 
		   <label>Derivación: </label> <b><label id="idDerivacion"></label> </b></i>
		<br><br>

		<!-- PARTE DEL ELECTRO QUE SE ESTÁ VISUALIZANDO -->
		<label id="lblsegInicial"> DESDE 0 s </label>
		<label id="lblsegFinal"> HASTA 0 s </label>
		<label id="lblsegTotal"> DE 0 s </label>

		<br><br><br>
		<label>Ir al segundo: </label>
		<input type="text" id="segundo" onKeyPress="return validaDigitos(event);" value="0">
		<input type="button" id="OK" value="OK" onClick="localizaSegundo();">
		<br><br><br>

		<input type=button onClick="location.href='menu.php'" value='MENÚ'>

		<?php
			if($tabla == NULL)	#Si no se ha seleccionado una señal...
				exit;
		?>

		 | 
		<input type=button id="download" onClick="downloadButton();" value="Descargar Señal">

		<!-- OPCIONES DE DESCARGA -->
		<div id="dialog" style="display: none">
		<br>
			<b>↓</b>
		<br><br>
		<label><h3>Generar .txt</h3></label>
		    <input type='radio' id='complete' value='SC' name='myRadio1' onchange="radioChecked(this.id);" checked='true'>Señal Completa
		    <input type='radio' id='segment' value='SS' name='myRadio1' onchange="radioChecked(this.id);">Segmento <br><br>

			Del seg: <input type="text" id="inicio" onKeyPress="return validaDigitos(event);" value="0" disabled="true">
			al seg: <input type="text" id="fin" onKeyPress="return validaDigitos(event);" value="0" disabled="true">
		<br><br>
			<label>Incluir anotaciones:</label>
		    <input type='radio' id='yes' value='Y' name='myRadio2' checked='true'>Sí
		    <input type='radio' id='no' value='N' name='myRadio2'">No

		<br><br><br>
		<input type=button id="downloadTXT" onClick="download();" value="Descargar">
		<input type=button id="cancel" onClick="closeIt();" value="Cancelar">

		</div>
		<!-- TERMINA DESCARGA -->

		<br><br>
		<p id="indice1" style="display: none">0</p>
		<p id="indice2" style="display: none"><?php echo $incremento; ?></p>

		<!-- style="display: none"; -->

		<br>
	</body>
</html>
