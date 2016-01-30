<!DOCTYPE HTML>
<html>
	<head>
		<style type="text/css">
		#scrollbar {
			border-radius:3px;
			cursor: pointer;
		}
		</style>

		<?php
			$widthScrollbar = 1000;
			$heightScrollbar = 22;
		?>

		<script>
		//VARIABLES
		var xAnt = 0;
		var x = 44;
		var y = 3;
		var posCursor;
		var dragok = false;
		var clickok = false;

		var WIDTH = 1000;		
		var HEIGHT = 22;
		var longTrack = 912;		
		var wThumb = 30;
		var hThumb = 16;
		var wButton = 22;

		var segTotal = 0;
		var step = Math.ceil((longECG/(freq/25))/longTrack);
		var desplECG = Math.round(longECG / freq / segVis);

		var slow = 0.5;
		var fast = slow*2;
		var longStep = slow;
		var interval;
		var vel = 150;

		var trackColor = "#DDD";
		var disColor = "#CCC";

		if("<?php echo $tabla ?>" == "") {
			//Si no se ha seleccionado un electro, el thumb luce deshabilitado.
			var thumbColor = "#BBB";
			var thumbLines = "#999";
		}
		else {
			var thumbColor = "#999";
			var thumbLines = "#666";
		}
		</script>

		<script>
		//Dibuja scrollbar.
		function drawScrollbar() {
			//Id y contexto del scrollbar.
			var c=document.getElementById("scrollbar");
			var contexto=c.getContext("2d");			
			contexto.fillStyle=trackColor;
			contexto.strokeStyle=thumbColor;
			contexto.lineJoin = "round";
			contexto.lineWidth=2;
			
			//Buttons.
			contexto.fillRect(0,0,wButton,HEIGHT);
			contexto.fillRect(WIDTH-wButton,0,wButton,HEIGHT);
			contexto.strokeRect(0,0,wButton,HEIGHT);
			contexto.strokeRect(WIDTH-wButton,0,wButton,HEIGHT);

			//Track.
			contexto.fillRect(26,0,948,HEIGHT);
			contexto.strokeRect(26,0,948,HEIGHT);
			//Arrows.				
			if(x<=44)	//Izquierda.
				contexto.fillStyle=disColor;
			else
				contexto.fillStyle=thumbColor;	
			contexto.beginPath();
			contexto.moveTo(8,11);
			contexto.lineTo(14,7);
			contexto.lineTo(14,15);
			contexto.lineTo(8,11);
			contexto.closePath();
			contexto.stroke();
			contexto.fill();				
			if(x>=956)	//Derecha.
				contexto.fillStyle=disColor;
			else
				contexto.fillStyle=thumbColor;			
			contexto.beginPath();
			contexto.moveTo(992,11);
			contexto.lineTo(986,7);
			contexto.lineTo(986,15);
			contexto.lineTo(992,11);
			contexto.closePath();
			contexto.stroke();
			contexto.fill();
		};

		//Dibuja el rectangulo que representa al thumb.
		function rect(x,y,w,h) {
			//Id y contexto de la scrollbar.
			var c=document.getElementById("scrollbar");
			var contexto=c.getContext("2d");
			contexto.strokeStyle=thumbLines;
			contexto.fillStyle=thumbColor;

			contexto.beginPath();
			if(x<=44) {
				contexto.rect(29,y,w,h);
				x=44;
				xAnt=x
			}
			else if(x>=956) {
				contexto.rect(941,y,w,h);
				x=956;
				xAnt=x;	
			}
			else {
				contexto.rect(x-15,y,w,h);
				xAnt=x;
			}
			contexto.closePath();
			contexto.fill();
						
			//Dibuja lineas en el thumb.
			contexto.beginPath();
			if(x<44) {
				contexto.moveTo(40,6);
				contexto.lineTo(40,16);
				contexto.moveTo(44,6);
				contexto.lineTo(44,16);
				contexto.moveTo(48,6);
				contexto.lineTo(48,16);
			}
			else if(x>956) {
				contexto.moveTo(952,6);
				contexto.lineTo(952,16);
				contexto.moveTo(956,6);
				contexto.lineTo(956,16);
				contexto.moveTo(960,6);
				contexto.lineTo(960,16);
			}
			else {
				contexto.moveTo(x-4,6);
				contexto.lineTo(x-4,16);
				contexto.moveTo(x,6);
				contexto.lineTo(x,16);
				contexto.moveTo(x+4,6);
				contexto.lineTo(x+4,16);
			}
			contexto.stroke();
		};

		//Limpia el canvas.
		function clear() {
			var c=document.getElementById("scrollbar");
			var contexto=c.getContext("2d");
			contexto.clearRect(0, 0, WIDTH, HEIGHT);
		};

		//Dibuja los componentes de scrollbar en el canvas.
		function dibujarScrollbar() {			
			clear();			//Limpia el lienzo.
			drawScrollbar();		//Dibuja scrollbar.
			rect(x,y,wThumb,hThumb);	//Dibuja thumb.
		};

		//Redibuja scrollbar cada 50 microsegundos.
		function initScroll() {
			setInterval(dibujarScrollbar,50);
		};

		//Click.
		function myDown(e) {
			if (e.button==2) {
				//Se presiono el botón derecho del mouse.
			}
			else {			
				var c=document.getElementById("scrollbar");
				c.onmousemove = myMove;
				x = e.pageX - c.offsetLeft;
				posCursor=x;

				//Flecha izquierda.
				if(x>0 && x <= wButton) {
					if(xAnt>44) {
						clickok = true;
						longStep = slow;
						izquierda();
					}
				}
				//Flecha derecha.
				else if(x>=WIDTH-wButton && x<WIDTH) {
					if(xAnt<956) {
						clickok = true;
						longStep = slow;
						derecha();
					}
				}
				//Track.
				else {
					if(x>xAnt-15 && x<xAnt+15) {
						//Thumb.
						clickok = false;
						dragok = true;
						longStep = slow;
					}
					else {
						if(x>xAnt) {
							clickok = true;
							longStep = fast;
							derecha();
						}
						else if(x<xAnt) {
							clickok = true;
							longStep = fast;
							izquierda();
						}
					}
				}
				xAnt = x;
			}
		};		
		//Movimiento del mouse.
		function myMove(e) {
			clickok = false;
			clearInterval(interval);

			if (dragok && x>29 && x<971){
				var c=document.getElementById("scrollbar");
				x = e.pageX - c.offsetLeft;
				posCursor=x;
				longStep = fast;

				if(x > xAnt && x<=956) {
					derecha();
				}
				else if(x < xAnt && desplazamiento !=0) {
					izquierda();
				}
				xAnt = x;
			}
		};
		//Soltar boton.
		function myUp() {
			var c=document.getElementById("scrollbar");
			c.onmousemove = null;

			clearInterval(interval);
			dragok = false;
			clickok = false;

			actualizarIndices();
		};

		//Calcular desplazamiento.
		function calcularDespl() {
			var despl = Math.round((x-44)*step);

			if(despl < 0) {
				despl = 0;
				x = 44;
			}
			else if(despl >= maxDespl-25) {
				despl = maxDespl - 50;
				x = 956;
			}
			return despl;
		};
		//Calcular índice inicial de datos graficados.
		function calcularPos() {
			var posicion1 = Math.round((x-44)/(longTrack/(desplECG*segVis))*freq);

			if(posicion1 <= 0) {
				posicion1 = 0;
				x = 44;
			}
			else if(posicion1 >= longECG) {
				posicion1 = longECG - incremento;
				x = 956;
			}
			return posicion1;
		};

		//Incremento hacia la derecha.
		function incrStep() {
			if(clickok) {
				//En caso de que se deje presionado el botón derecho o el track de la barra.
				if(x<956 && x<posCursor) {
					x += longStep;
					xAnt = x;
					desplazamiento = calcularDespl();

					if(desplazamiento >= maxDespl-25) {
						desplazamiento = maxDespl-50;
						pos2 = longECG;
						pos1 = pos2 - freq*segVis;
						x = 956;
					}
					else {
						pos1 = calcularPos();
						pos2 = pos1 + incremento;
					}
					actualizarIndices();
				}
			}
			else { }
		};
		//Incremento hacia la izquierda.
		function decrStep() {
			if(clickok) {
				//En caso de que se deje presionado el botón izquierdo o el track de la barra.
				if(x>44 && x>posCursor) {
					x -= longStep;
					xAnt = x;

					if(desplazamiento>0) {
						desplazamiento = calcularDespl();

						if(desplazamiento <= 0) { 
							desplazamiento = 0;
							pos1 = 0;
							pos2 = pos1+incremento;
						}
						else {
							pos1 = calcularPos();
							pos2 = pos1 + incremento;
						}
					}
					actualizarIndices();
				}
			}
			else { }
		};
		
		//Desplazar a la derecha con click.
		function derecha() {
			if(clickok) {
				x = xAnt + longStep;
				interval=setInterval(incrStep,vel);
			}
			if(desplazamiento<longECG) {
				desplazamiento = calcularDespl();

				if(desplazamiento >= maxDespl) {
					desplazamiento = maxDespl-50;
					pos2 = longECG;
					pos1 = pos2 - freq*segVis;
				}
				else {
					pos1 = calcularPos();
					pos2 = pos1 + incremento;
				}
				actualizarIndices();
			}
		};		
		//Desplazar a la izquierda con click.
		function izquierda() {						
			if(clickok) {
				x = xAnt - longStep;
				interval=setInterval(decrStep,vel);
			}
			if(desplazamiento>0) {
				desplazamiento = calcularDespl();

				if(desplazamiento <= 0) { 
					desplazamiento = 0;
					pos1 = 0;
					pos2 = pos1+incremento;
				}
				else {
					pos1 = calcularPos();
					pos2 = pos1 + incremento;
				}
				actualizarIndices();
			}		
		};
		</script>
	</head>

	<body>
	</body>
</html>
