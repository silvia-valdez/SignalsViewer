<?php
	#Límite de memoria para ejecución del script php.
	ini_set("memory_limit","512M");	

	#Clase AJAX.
	require '/opt/bitnami/apache2/htdocs/signals-viewer.hunabsys.com/xajax/xajax_core/xajax.inc.php';
	require '/opt/bitnami/apache2/htdocs/signals-viewer.hunabsys.com/inc/conection.php';

	#Instanciamos el objeto de la clase xajax.
	$xajax = new xajax();
	#Registramos las funciones.
	$xajax->register(XAJAX_FUNCTION, 'validaSelect');
	$xajax->register(XAJAX_FUNCTION, 'cargaPaciente');
	$xajax->register(XAJAX_FUNCTION, 'cargaECG');
	$xajax->register(XAJAX_FUNCTION, 'cargaDerivacion');
	#Procesamos el registro.
	$xajax->processRequest();

	#FUNCIONES REGISTRADAS.
	function validaSelect($selectDestino, $opcionSeleccionada) {
		#Array que vincula los IDs de los selects declarados en el HTML
		#con el nombre de la tabla donde se encuentra su contenido.
		$listadoTablas=array(
			"database"=>"bd",
			"patient"=>"paciente",
			"ECG"=>"electrocardiografia",
			"position"=>"derivacion"
		);

		#Se valida que la tabla indicada por el selectDestino exista en el array.
		if(isset($listadoTablas[$selectDestino]))
			$flat1 = true;
		else
			$flat1 = false;

		#Se valida que la opción seleccionada por el usuario en el select tenga un valor numérico.
		if(is_numeric($opcionSeleccionada))
			$flat2 = true;
		else
			$flat2 = false;

		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

		if($flat1 && $flat2)
			$output->true;
		else
			$output->false;

		return $output;
	}

	### PACIENTE ###
	function cargaPaciente($selectDestino, $opcionSeleccionada) {
		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

		if($output->call('xajax_validaSelect',$selectDestino,$opcionSeleccionada)) {
			conectar();
			$query = pg_query("SELECT id FROM paciente WHERE id_bd=$opcionSeleccionada") or die("Error");
			desconectar();

			$i=0;
			while($row=pg_fetch_assoc($query)){
				#Guardar los datos en un array.
				$p[$i] = $row['id'];
				$i++;
			}
			$output->setReturnValue($p);
		}
		return $output;
	}

	### ELECTROCARDIOGRAFÍA ###
	function cargaECG($selectDestino, $opcionSeleccionada) {
		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

		if($opcionSeleccionada == 0) { #Si el valor es 0, traemos todos los id's de la tabla.
			conectar();
			$query = pg_query("SELECT id, nombre FROM electrocardiografia") or die("Error");
			desconectar();

			$i = 0; $j = 0;
			while($row=pg_fetch_assoc($query)){
				#Guardar los datos en un array bidimensional.
				$e[$i][$j] = $row['id'];
				$j++;
				$e[$i][$j] = $row['nombre'];

				$j=0;
				$i++;
			}
		}
		else if($output->call('xajax_validaSelect',$selectDestino,$opcionSeleccionada)) {
			conectar();
			$query = pg_query("SELECT id, nombre FROM electrocardiografia WHERE id_paciente=$opcionSeleccionada") or die("Error");
			desconectar();

			$i = 0; $j = 0;
			while($row=pg_fetch_assoc($query)){
				#Guardar los datos en un array bidimensional.
				$e[$i][$j] = $row['id'];
				$j++;
				$e[$i][$j] = $row['nombre'];

				$j=0;
				$i++;
			}
		}
		$output->setReturnValue($e);
		return $output;
	}

	### DERIVACIÓN ###
	function cargaDerivacion($selectDestino, $opcionSeleccionada) {
		#Instanciamos el objeto para generar la respuesta con ajax.
		$output = new xajaxResponse();

		if($output->call('xajax_validaSelect',$selectDestino,$opcionSeleccionada)) {
			conectar();
			$query = pg_query("SELECT id, posicion FROM derivacion WHERE id_electrocardiografia=$opcionSeleccionada") or die("Error");
			desconectar();

			$i = 0; $j = 0;
			while($row=pg_fetch_assoc($query)){
				#Guardar los datos en un array bidimensional.
				$d[$i][$j] = $row['id'];
				$j++;
				$d[$i][$j] = $row['posicion'];

				$j=0;
				$i++;
			}
			$output->setReturnValue($d);
		}
		return $output;
	}
?>

<?php
	#Genera el código HTML necesario para cargar el select que contiene las BD disponibles.
	function generaBD() {		
		conectar();	#ABRE CONEXIÓN.
		$result1 = pg_query("SELECT id, nombre FROM bd");		
		desconectar();	#CIERRA CONEXIÓN.

		echo "<select name='database' id='database' onChange='cargaContenido(this.id);' autocomplete='off'>";
		echo "<option selected='selected' value='0'>Seleccione...</option>";

		while($registro=pg_fetch_row($result1)) {
			echo "<option value='".$registro[0]."'>".$registro[1]."</option>";
		}
		echo "</select>";
	}

	require '/home/silvia.valdez/public_html/ECG/inc/vars.php';
?>

<html>
	<head>
		<title>MENÚ | ECG Signals Viewer</title>

		<style>
		table {
			border: 1px solid black;
			border-radius: 18px;
			padding: 15px;
		}
		td {
			padding: 15px;
		}
		</style>

		<?php
		   #Indicamos al objeto xajax se encargue de generar el javascript necesario.
		   $xajax->printJavascript("/ECG/xajax/");
		?> 

		<script>
		//Selects que componen el documento HTML. Su atributo ID debe figurar aquí.
		var listadoSelects=new Array();
		listadoSelects[0]="database";
		listadoSelects[1]="patient";
		listadoSelects[2]="ECG";
		listadoSelects[3]="position";

		function buscarEnArray(array, dato) {
			//Retorna el índice de la posición donde se encuentra el elemento
			//en el array, o null si no se encuentra.
			var x=0;
			while(array[x]) {
				if(array[x]==dato)
					return x;
				x++;
			}
			return null;
		};

		function cargaContenido(idSelectOrigen) {
			//Posición que ocupa en el array declarado más arriba el select que debe ser cargado .
			var posicionSelectDestino=buscarEnArray(listadoSelects, idSelectOrigen)+1;
			//Select que el usuario modificó.
			var selectOrigen=document.getElementById(idSelectOrigen);
			//Opción que el usuario seleccionó.
			opcionSeleccionada=selectOrigen.options[selectOrigen.selectedIndex].value;

			//Si se eligió la opcion "Seleccione...", no va al servidor y se asigna a los selects siguientes el estado "Seleccione..."
			if(opcionSeleccionada==0) {
				var selectActual=null;
				var x, i;

				//Se deshabilita el botón "visualizar".
				var botonVisualizar = document.getElementById('boton');
				botonVisualizar.disabled = true;

				//Si el select modificado fue 'Paciente', va al servidor y se vuelve a cargar el select 'ECG'.
				if(posicionSelectDestino==2) { 
					pacienteSelected = false;
					x=posicionSelectDestino+1;

					var selectDestino = document.getElementById('ECG');
					var opcionesECG =  xajax.call('cargaECG',{mode:'synchronous',parameters:[selectDestino.id, 0]});

					selectDestino.innerHTML = '';
					selectDestino.options[0]=new Option("Seleccione...", "0");

					i = 0;
					while(i < opcionesECG.length) {
						selectDestino.options[i+1]=new Option(opcionesECG[i][1], opcionesECG[i][0]);
						i++;
					}
				}
				else {
					x=posicionSelectDestino;
				}

				//Busca todos los selects siguientes al que inició el evento onChange, se les cambia el estado y deshabilita.
				while(listadoSelects[x]) {
					selectActual=document.getElementById(listadoSelects[x]);

					selectActual.innerHTML = '';
					selectActual.options[0]=new Option("Seleccione...", "0");
					selectActual.disabled=true;

					x++;
				}
			}
			//Comprueba que el select modificado no sea el último del array.
			else if(idSelectOrigen != listadoSelects[listadoSelects.length-1]) {
				//El elemento del select que se debe cargar.
				var idSelectDestino=listadoSelects[posicionSelectDestino];
				var selectDestino=document.getElementById(idSelectDestino);
				var opcionesPaciente, opcionesECG, opcionesDerivacion;
				var i;

				// Mientras carga, se reemplaza la opción "Seleccione..." por "Cargando..."
				selectDestino.innerHTML = '';
				selectDestino.options[0]=new Option("Cargando...", "0");	
				
				switch(idSelectDestino) {
					case 'patient':
						//Se carga el select 'Paciente'.
						pacienteSelected = false;
						opcionesPaciente =  xajax.call('cargaPaciente',{mode:'synchronous',parameters:[idSelectDestino, opcionSeleccionada]});

						selectDestino.innerHTML = '';
						selectDestino.options[0]=new Option("Seleccione...", "0");

						i = 0;
						while(i < opcionesPaciente.length) {
							selectDestino.options[i+1]=new Option(opcionesPaciente[i], i+1);
							i++;
						}

						//Se carga el select 'ECG'.
						selectECG=document.getElementById('ECG');
						opcionesECG =  xajax.call('cargaECG',{mode:'synchronous',parameters:[idSelectDestino, 0]});

						selectECG.innerHTML = '';
						selectECG.options[0]=new Option("Seleccione...", "0");

						i = 0;
						while(i < opcionesECG.length) {
							selectECG.options[i+1]=new Option(opcionesECG[i][1], opcionesECG[i][0]);
							i++;
						}

						selectECG.disabled=false;

						break;

					case 'ECG':
						pacienteSelected = true;
						opcionesECG =  xajax.call('cargaECG',{mode:'synchronous',parameters:[idSelectDestino, opcionSeleccionada]});

						selectDestino.innerHTML = '';
						selectDestino.options[0]=new Option("Seleccione...", "0");

						i = 0;
						while(i < opcionesECG.length) {
							selectDestino.options[i+1]=new Option(opcionesECG[i][1], opcionesECG[i][0]);
							i++;
						}

						//Se deshabilita el select 'Derivación'.
						var selectDerivacion = document.getElementById('position');
						selectDerivacion.innerHTML = '';
						selectDerivacion.options[0]=new Option("Seleccione...", "0");
						selectDerivacion.disabled = true;

						break;

					case 'position':
						opcionesDerivacion =  xajax.call('cargaDerivacion',{mode:'synchronous',parameters:[idSelectDestino, selectOrigen.value]});

						selectDestino.innerHTML = '';
						selectDestino.options[0]=new Option("Seleccione...", "0");

						i = 0;
						while(i < opcionesDerivacion.length) {
							selectDestino.options[i+1]=new Option(opcionesDerivacion[i][1], opcionesDerivacion[i][0]);
							i++;
						}

						//Si no se seleccionó un ID en el select 'Paciente', se le asigna el mismo seleccionado en 'ECG'.
						selectPaciente=document.getElementById('patient');

						if(selectPaciente.options[selectPaciente.selectedIndex].value == 0 || pacienteSelected == false)
							selectPaciente.options[selectOrigen.options.selectedIndex].selected = true;

						break;
				}
				selectDestino.disabled=false;	
			}
			//El select modificado es el último del array.
			else if(idSelectOrigen == listadoSelects[listadoSelects.length-1]) {
				//Se habilita el botón "visualizar".
				var botonVisualizar = document.getElementById('boton');
				botonVisualizar.disabled = false;

				//Se obtiene el ID de la señal seleccionada.
				opcion = selectOrigen.value;
			}
		};

		function menu() {
			//Redirecciona a la página 'index' enviando como parámetro el ID de la señal seleccionada.
			window.location.href="index.php?z="+opcion+"";
		};
		</script>
	</head>

	<body>
		<header>
			<center><h1>MENÚ</h1></center>	<br>
		</header>

		<center>
		<form>
		<table>
			<tr>
				<td style="text-align:right">Base de Datos:</td>
				<td> <?php generaBD(); ?> </td>
			</tr>
			<tr>
				<td style="text-align:right">ID Paciente:</td>
				<td>
				<select name="patient" id="patient" onChange="cargaContenido(this.id)" autocomplete="off" disabled="true">
					<option selected="selected" value="0">Seleccione...</option>
				</select>
				</td>
			</tr>
			<tr>
				<td style="text-align:right">Electrocardiografía:</td>
				<td>
				<select name="ECG" id="ECG" onChange="cargaContenido(this.id)" autocomplete="off" disabled="true">
					<option selected="selected" value="0">Seleccione...</option>
				</select>
				</td>
			</tr>
			<tr>
				<td style="text-align:right">Derivación:</td>
				<td>
				<select name="position" id="position" onChange="cargaContenido(this.id)" autocomplete="off" disabled="true">
					<option selected="selected" value="0">Seleccione...</option>
				</select>
				</td>
			</tr>
		</table>

		<br><br>
		<input type="button" id="boton" name="boton" value="Visualizar Señal" onClick="menu()" disabled="true">
		<br><br><br>

		</form>
		<center>
	</body>
</html>
