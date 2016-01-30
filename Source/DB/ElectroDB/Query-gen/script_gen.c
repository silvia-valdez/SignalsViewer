#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define NUM_CHARS 8
#define BD_DIR "signals_ascii_float"
#define ANN_DIR "anotaciones"
#define POSICION_LEN 10
#define DERIVACION_MIT_BIH 2

char clase_latido(int p){
switch(p){
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
return '\"';
}
}

int countlines(FILE *f){
  int ch=0;
  int lines=0;

  //~ if (f == NULL);
  //~ return -1;

  //~ lines++;
  while ((ch = fgetc(f)) != EOF)
    {
      if (ch == '\n')
    lines++;
    }

  return lines;
}


void posicion_derivacion_MIT_ARHYTHMIA(int id_ecg, char**pos){
	memset(pos[0], '\0', POSICION_LEN);
	memset(pos[1], '\0', POSICION_LEN);
	
	switch(id_ecg){
		case 102:
		case 104:
			sprintf(pos[0], "V5");
			sprintf(pos[1], "V2");
			break;
			
		case 103:
		case 117:
			sprintf(pos[0], "MLII");
			sprintf(pos[1], "V2");
			break;
			
		case 123:
		case 100:
			sprintf(pos[0], "MLII");
			sprintf(pos[1], "V5");
			break;
			
		case 114:
			sprintf(pos[0], "V5");
			sprintf(pos[1], "MLII");
			break;
			
		case 124:
			sprintf(pos[0], "MLII");
			sprintf(pos[1], "V4");
			break;
			
		case 101:
		case 105:
		case 106:
		case 107:
		case 108:
		case 109:
		case 111:
		case 112:
		case 113:
		case 115:
		case 116:
		case 118:
		case 119:
		case 121:
		case 122:
		case 200:
		case 201:
		case 202:
		case 203:
		case 205:
		case 207:
		case 208:			
		case 209:
		case 210:
		case 212:
		case 213:
		case 214:
		case 215:
		case 217:
		case 219:
		case 220:
		case 221:
		case 222:
		case 223:
		case 228:
		case 230:
		case 231:
		case 232:
		case 233:
		case 234:
			sprintf(pos[0], "MLII");
			sprintf(pos[1], "V1");
			break;
	
	}
	
	
}

int main(int argc, char ** argv){
	
	char *dir_sig, *f_dir_sig, *f_dir_ann, *dir_query;
	int id_sig, n, n_ann, i, basura;
	
	char **posiciones;
	
	FILE *f_signal, *f_ann, *f_query;
	
	float *signals[2];
	int *ann_index, *ann_chs;
		
	/* Leer la señal y su id */
	dir_sig = argv[1];
	sscanf(dir_sig, "mitdb-%d-float.txt", &id_sig);
	
	
	/* Construye ele vector para guradar las posiciones de acuerdo con
	 * el ecg_id */
	posiciones = (char **) malloc(sizeof(char *) * DERIVACION_MIT_BIH);
	posiciones[0] = (char *) malloc(sizeof(char) * POSICION_LEN);
	posiciones[1] = (char *) malloc(sizeof(char) * POSICION_LEN);
	
	posicion_derivacion_MIT_ARHYTHMIA(id_sig, posiciones);

	//~ fprintf(stdout, "ELECTRO %d\n", id_sig);
	//~ fprintf(stdout, "Las posiciones son: %s %s\n", posiciones[0], posiciones[1]);
	//~ exit(1);

	
	/* Leer el archivo con la señal de ECG */
	f_dir_sig = (char *) malloc(strlen(dir_sig) + strlen(BD_DIR) + 4);
	memset(f_dir_sig, '\0', strlen(dir_sig) + strlen(BD_DIR) + 4);
	sprintf(f_dir_sig, "%s/%s", BD_DIR, dir_sig);
	f_signal = fopen(f_dir_sig, "r");
	
	if(f_signal == NULL){
		fprintf(stdout, "Error al abrir el archivo %s\n", dir_query);
		exit(1);
	}
	n = countlines(f_signal);
	rewind(f_signal);
	
	signals[0] = (float *) malloc(sizeof(float) * n);
	signals[1] = (float *) malloc(sizeof(float) * n);	
	for(i = 0; i < n; i++)
		fscanf(f_signal, "%d %f %f", &basura, &signals[0][i], &signals[1][i]);
	
	fclose(f_signal);
		
		
	/* Leer las anotaciones */
	f_dir_ann = (char *) malloc(strlen(ANN_DIR) + 20);
	memset(f_dir_ann, '\0', strlen(ANN_DIR) + 20);
	sprintf(f_dir_ann, "%s/ant-%d.txt", ANN_DIR, id_sig);
	f_ann = fopen(f_dir_ann, "r");
	
	if(f_ann == NULL){
		fprintf(stdout, "Error al abrir el archivo: %s\n", f_dir_ann);
		exit(1);
	}
	n_ann = countlines(f_ann);
	rewind(f_ann);
	
	ann_index = (int *) malloc(sizeof(int) * n_ann);
	ann_chs = (int *) malloc(sizeof(int) * n_ann);
	for(i = 0; i < n_ann; i++)
		fscanf(f_ann, "%d %d", &ann_index[i], &ann_chs[i]);
	
	fclose(f_ann);
	
	
	/* Crea y abre el archivo de salida */
	dir_query = (char *) malloc(sizeof(char) * 20);
	memset(dir_query, '\0', 20);
	sprintf(dir_query, "query_%d.sql", id_sig);
	f_query = fopen(dir_query, "w");
	
	if(f_query == NULL){
		fprintf(stderr, "Error al abrir el archivo %s\n", dir_query);
		exit(1);
	}
	
	fprintf(f_query, "INSERT INTO Paciente(ID_BD) VALUES((SELECT id from bd WHERE Nombre = 'MIT-BIH-Arrhytmia'));\n");
	fprintf(f_query, "INSERT INTO Electrocardiografia(Frecuencia_Muestreo,Longitud,ID_Paciente) VALUES(360,%d,(SELECT max(ID) FROM Paciente));\n", n);
	
	fprintf(f_query, "INSERT INTO Derivacion(Posicion, Signal, ID_Electrocardiografia) VALUES('%s','{", posiciones[0]);
	for(i = 0; i < n-1; i++) fprintf(f_query, "%f,", signals[0][i]);
	fprintf(f_query, "%f}',(SELECT max(id) FROM Electrocardiografia);\n", signals[0][n-1]);
	
	for(i = 0; i < n_ann; i++){ 
		fprintf(f_query, "INSERT INTO Anotacion(ID_Derivacion, Indice, Nota) VALUES((SELECT max(id) from Derivacion), %d, %d);\n",
				ann_index[i], ann_chs[i]); 
	}
	
	
	fprintf(f_query, "INSERT INTO Derivacion(Posicion, Signal, ID_Electrocardiografia) VALUES('%s', '{", posiciones[1]);
	for(i = 0; i < n-1; i++) fprintf(f_query, "%f,", signals[1][i]);
	fprintf(f_query, "%f}',(SELECT max(id) FROM Electrocardiografia));\n", signals[1][n-1]);
	
	for(i = 0; i < n_ann; i++){ 
		fprintf(f_query, "INSERT INTO Anotacion(ID_Derivacion, Indice, Nota) VALUES((SELECT max(id) from Derivacion), %d, %d);\n",
				ann_index[i], ann_chs[i]); 
	}
	
	
	
	
	//~ for(i = 0; i < n_ann-1; i++) fprintf(f_query, "%d,", ann_index[i]);
	//~ fprintf(f_query, "%d}','{", ann_index[n_ann-1]);
	
	//~ for(i = 0; i < n_ann-1; i++) fprintf(f_query, "%d,", ann_chs[i]);
	//~ fprintf(f_query, "%d}',(SELECT max(id) FROM Electrocardiografia));\n", ann_chs[n_ann-1]);
	
	//~ for(i = 0; i < n_ann-1; i++) fprintf(f_query, "%d,", ann_index[i]);
	//~ fprintf(f_query, "%d}','{", ann_index[n_ann-1]);
	//~ 
	//~ for(i = 0; i < n_ann-1; i++) fprintf(f_query, "%d,", ann_chs[i]);
	//~ fprintf(f_query, "%d}',(SELECT max(id) FROM Electrocardiografia));\n", ann_chs[n_ann-1]);
	
	fclose(f_query);
		
	return 0;
}



















