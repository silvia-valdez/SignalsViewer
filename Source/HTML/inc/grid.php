<!DOCTYPE HTML>
<html>
	<head>		
		<?php
			$widthGrid = 1000;
			$heightGrid = 525;
		?>

		<style>
		canvas {
		   padding-left: 10px;
		   padding-right: 20px;
		}
		</style>

		<script>
		//VARIABLES
		var widthCanvas = "<?php echo $widthGrid; ?>";		//Ancho.
		var heightCanvas = "<?php echo $heightGrid; ?>";	//Alto.
		var margenInf = 25;					//Margen inferior.

		var thinLine = 1;					//Ancho de linea.
		var strongLine = 2;
		var strongerLine = 3;
		var lineSignal = 2;

		var font = "Times New Roman";
		var scaleSize = "13px ";
		var annotSize = "15px ";

		var colorSignal = '#111111';
		var colorText = '#111111';
		var colorAnnotation = '#00BB00';
		var lineColor = '#F07070';
		var backColor = '#F0D0D0';

		var cuadroGrande = 50;		
		var cuadroSeg = 500;
		</script>

		<script>
		//DIBUJA LA CUADRICULA EN EL CANVAS.
		function pintarGrid() {
			//Id y contexto del lienzo.
			var c=document.getElementById("lienzo");
			var ctx=c.getContext("2d");
			ctx.strokeStyle = lineColor;
			//Fondo del lienzo.
			ctx.fillStyle = backColor;
			ctx.fillRect(0,0,widthCanvas,heightCanvas-margenInf);
			
			//CICLO PARA LÍNEAS HORIZONTALES.
			for (var i=0;i<=heightCanvas-margenInf;i=i+dx) {
				if(i % cuadroGrande == 0)
					ctx.lineWidth = strongLine;
				else
					ctx.lineWidth = thinLine;

				ctx.beginPath();
				ctx.moveTo(0,i);
				ctx.lineTo(widthCanvas,i);
				ctx.closePath();
				ctx.stroke();
			}
			//CICLO PARA LÍNEAS VERTICALES.
			for (var i=0;i<=widthCanvas;i=i+dx) {
				if((i+desplazamiento*dx) % cuadroGrande == 0)
					ctx.lineWidth = strongLine;
				else
					ctx.lineWidth = thinLine;

				if((i+desplazamiento*dx) % cuadroSeg == 0)
					ctx.lineWidth = strongerLine;

				ctx.beginPath();
				ctx.moveTo(i,0);
				ctx.lineTo(i,heightCanvas-margenInf);
				ctx.closePath();
				ctx.stroke();
			}
		};
		</script>

		<script>
		function obtenerAnotacion(p) {
			switch(p) {
				case 1:
					return 'N';
			
				case 2:
					return 'L';
			
				case 3:
					return 'R';
			
				case 4:
					return 'A';
			
				case 5:
					return 'a';
			
				case 6:
					return 'J';
			
				case 7:
					return 'S';
			
				case 8:
					return 'V';
			
				case 9:
					return 'F';
			
				case 10:
					return '[';
			
				case 11:
					return '!';
			
				case 12:
					return ']';
			
				case 13:
					return 'e';
			
				case 14:
					return 'j';
			
				case 15:
					return 'E';
			
				case 16:
					return '/';
			
				case 17:
					return 'f';
			
				case 18:
					return 'x';
			
				case 19:
					return 'Q';
			
				case 20:
					return '|';
			
				case 21:
					return '~';
			
				case 22:
					return '+';
		
				case 23:
					return '"';
			}	
		};

		function graficarAnotaciones(indice1) {
			var caracter;
			var cantAnot = anotaciones.length;

			var DPCM = 100;    	    		// dots per centimeter
			var CM_PER_SEC = 2.5;			// centimeters per second (25 mm)
			var delta = DPCM*CM_PER_SEC*segVis/freq;

			//Configuraciones.
			ctx.strokeStyle = colorAnnotation;
			ctx.fillStyle = colorText;
			ctx.lineWidth = strongLine;
			ctx.font = annotSize + font;

			for(var i=0; i<cantAnot; i++) {
				if(anotaciones[i][0] >= pos1 && anotaciones[i][0] <= pos2) {
					//Dibujar línea.
					ctx.beginPath();
					ctx.moveTo((anotaciones[i][0]-pos1)*delta,0);
					ctx.lineTo((anotaciones[i][0]-pos1)*delta,heightCanvas-margenInf);
					ctx.stroke();

					//Escribir anotación.
					caracter = obtenerAnotacion(anotaciones[i][1]);
					ctx.fillText(caracter, (anotaciones[i][0]-pos1+5)*delta,35); 
				}
			}
		};		
		</script>
		
		<script> 
		function graficarDatos(arreglo, indice1) {
			//GRAFICAR DATOS
			var DPCM = 100;    	    	// dots per centimeter
			var H_MARGIN = 0;     		// Horizontal margin, given in centimeters
			var V_MARGIN = 2;    		// Vertical margin
			var CM_PER_MV = 1;  		// centimeters per microvolt
			var CM_PER_SEC = 2.5;		// centimeters per second (25 mm)
			var x_pos = H_MARGIN * DPCM;
			var delta = DPCM*CM_PER_SEC*segVis/freq;
			var maxi = 0.0;
			var y;

			var ind1 = pos1 - indice1;
			var ind2 = ind1 + incremento;

			//GRAFICAR LA SEÑAL
			ctx.beginPath();
			ctx.lineWidth = lineSignal;
			ctx.strokeStyle = colorSignal;
			ctx.moveTo(x_pos,((1-(arreglo[ind1]-mini)*CM_PER_MV+ V_MARGIN)*DPCM));

			for(var i=ind1; i<ind2; i++)
			{
				y = (((1-(arreglo[i]-mini)*CM_PER_MV+ V_MARGIN)*DPCM));
				x_pos = x_pos + delta;
				ctx.lineTo (x_pos,y);
			}
			ctx.stroke();
		};
		</script>

		<script>
		//ESCRIBE LAS ABCISAS.
		function escribirEscalas() {
			//Id y contexto del lienzo.
			var c=document.getElementById("lienzo");
			var ctx=c.getContext("2d");

			ctx.font = scaleSize + font;
			ctx.fillStyle = colorText;
			ctx.clearRect(0,heightCanvas-margenInf,widthCanvas,heightCanvas);

			var segs = 0;
			var stepDespl = 0 - (desplazamiento*dx);

			//CICLO PARA LAS ABCISAS
			for(var i=stepDespl; i <= widthCanvas; i=i+100) {
				if(i<-1) { }
				else if(i==0)
					ctx.fillText(segs.toFixed(1) + ' s',i,heightCanvas-5); 
				else if(i >= widthCanvas-40)
					ctx.fillText(segs.toFixed(1) + ' s',i-45,heightCanvas-5); 
				else if(i == widthCanvas)
					ctx.fillText(segs.toFixed(1) + ' s',i-40,heightCanvas-5); 
				else
					ctx.fillText(segs.toFixed(1) + ' s',i-20,heightCanvas-5); 

				segs += 0.2;
			}
		};
		</script>		
	</head>

	<body>		
	</body>
</html>
