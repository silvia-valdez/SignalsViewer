<!DOCTYPE HTML>
<html>
	<head>
		<script>
		function downloadButton() {
			//Muestra las opciones de descarga en la página.
			document.getElementById('dialog').style.display = "block";
		};

		function radioChecked(radioID) {
			switch(radioID) {
				case 'complete':
					document.getElementById('inicio').disabled = true;
					document.getElementById('inicio').value = '0';

					document.getElementById('fin').disabled = true;
					document.getElementById('fin').value = '0';

					inicioSubs = 0;
					finSubs = 0;
					break;

				case 'segment':
					var inicio = document.getElementById('inicio').disabled = false;
					var fin = document.getElementById('fin').disabled = false;
					break;
			}
		};


		function closeIt() {
			document.getElementById('complete').checked = true;
			document.getElementById('yes').checked = true;

			document.getElementById('inicio').disabled = true;
			document.getElementById('inicio').value = '0';

			document.getElementById('fin').disabled = true;
			document.getElementById('fin').value = '0';

			inicioSubs = 0;
			finSubs = 0;

			document.getElementById('dialog').style.display = "none";
		};


		function queryAndWrite(subsecuencia, nombreArchivo, inicioSubs, finSubs) {
			var resultado = false;
			var noConsultas;

			//Anotaciones.
			if(document.getElementById('yes').checked == true)
				anot = true;	//Incluir anotaciones.
			else if(document.getElementById('no').checked == true)
				anot = false;	//No incluir anotaciones.

			//Invocar función PHP mediante AJAX para realizar las consultas y escribir el archivo.
			if(subsecuencia) {
				inicioSubs = inicioSubs * freq;
				finSubs = finSubs * freq;

				var incr = finSubs - inicioSubs;

				if(incr > noDatos) {
					noConsultas = incr / noDatos;
					noConsultas = Math.ceil(noConsultas);
				}
				else {
					noConsultas = 1;
					noDatos = incr;
				}

				for(var i=0; i < noConsultas; i++) {	
					if((inicioSubs + noDatos) > finSubs ) {		
						noDatos = finSubs - inicioSubs;

						resultado = xajax.call('descargarSeñal',{mode:'synchronous',
							parameters:[anot,tabla,inicioSubs,noDatos,nombreArchivo]});
					}
					else {
						resultado = xajax.call('descargarSeñal',{mode:'synchronous',
							parameters:[anot,tabla,inicioSubs,noDatos,nombreArchivo]});
					}

					inicioSubs += noDatos;
				}
			}
			else {	//Descargar señal completa.			
				noConsultas = longECG / noDatos;
				noConsultas = Math.ceil(noConsultas);
				inicioSubs = 0;

				for(var i=0; i < noConsultas; i++) {	
					if((inicioSubs + noDatos) > longECG ) {		
						noDatos = longECG - inicioSubs;

						resultado = xajax.call('descargarSeñal',{mode:'synchronous',
							parameters:[anot,tabla,inicioSubs,noDatos,nombreArchivo]});
					}
					else {
						resultado = xajax.call('descargarSeñal',{mode:'synchronous',
							parameters:[anot,tabla,inicioSubs,noDatos,nombreArchivo]});
					}

					inicioSubs += noDatos;
				}
			}

			return resultado;
		};


		function download() {
			var itsOK = true;

			//Señal completa.
			if(document.getElementById('complete').checked == true) {
				subsecuencia = false;
			}
			//Una subsecuencia de la señal.
			else if(document.getElementById('segment').checked == true) {
				subsecuencia = true;

				inicioSubs = parseInt(document.getElementById('inicio').value);
				finSubs = parseInt(document.getElementById('fin').value);

				//Valida los segundos indicados por el usuario.
				if(validaSeg(inicioSubs) == false || validaSeg(finSubs) == false) {
					alert("El rango de tiempo especificado supera la longitud del ECG.\n("
							+ desplECG*segVis + " segundos)");
					itsOK = false;
				}
				else if(finSubs <= 0) {
					alert("Debe especificar un segundo final mayor a 0.");	
					itsOK = false;			
				}
				else if(inicioSubs > finSubs) {
					alert("El segundo final debe ser mayor al segundo inicial.");	
					itsOK = false;	
				}
			}

			if(itsOK) {
				alert("Generando archivo... Esto puede demorar.");

				var nombreECG, nombreDerivacion, nombreArchivo, resultado;

				//Nombre que se asignará al archivo.
				nombreECG = document.getElementById('idECG').innerHTML;
				nombreDerivacion = document.getElementById('idDerivacion').innerHTML;
				nombreArchivo = nombreECG + '-' + nombreDerivacion + '.txt';
				nombreArchivo = nombreArchivo.replace(/\s/g,"");

				resultado = queryAndWrite(subsecuencia, nombreArchivo, inicioSubs, finSubs);
				
				//Si las consultas se realizaron con éxito...
				if(resultado) {
					alert("Ha comenzado la descarga...");

					var ruta = './inc/downloading.php?id=' + nombreArchivo;
					window.location.href = ruta;
				}
				else {
					alert("Ha ocurrido un error al descargar la señal.");
				}

				//Elimina el archivo temporal para que no vuelva a sobreescribirse.
				xajax_eliminarArchivo(nombreArchivo);
				
				closeIt();	
			}
		};
		</script>
	</head>
</html>
