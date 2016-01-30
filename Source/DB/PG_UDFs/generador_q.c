/*
 * Supone que el archivo está compuesto por 
 * la primer columna como datos enteros,
 * y dos columnas de datos float.
 * */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define DATA_COLS 2
#define DIR_SALIDA_LEN 32

void ayuda(char *p){
	fprintf(stdout, "USO DEL PROGRAMA\n");
	fprintf(stdout, "%s archivo_ecg.txt\n", p);
	fprintf(stdout, "\t archivo_ecg.txt Es el archivo con los valores del ECG en formato ASCII,\n", p);
	fprintf(stdout, "\t                 que incluye una columna de índices y dos columnas de datos.\n", p);
	
}

float **leer_datos(FILE *ecg, int *n){
	float **ecg_v, vf;
	int i, j, vi;
	int n_valores;
	
	n_valores = 0;
	while(fscanf(ecg, "%d", &vi) == 1){
		for(i = 0; i < DATA_COLS; i++)
			fscanf(ecg, "%f", &vf);
		
		n_valores++;
	}
	rewind(ecg);
	
	ecg_v = (float **) malloc(sizeof(float *) * DATA_COLS);
	for(j = 0; j < DATA_COLS; j++) ecg_v[j] = (float *) malloc(sizeof(float) * n_valores);
	
	for(i = 0; i < n_valores; i++){
		fscanf(ecg, "%d", &vi);
		
		for(j = 0; j < DATA_COLS; j++) 
			fscanf(ecg, "%f", &ecg_v[j][i]);
	}
	
	*n = n_valores;
	return ecg_v;
}

int main(int argc, char **argv){
	FILE *ecg, *salida;
	int n_valores, i, j;
	float **valores;
	char *dir_ecg, *opt, dir_salida[DIR_SALIDA_LEN];
	
	int flag = 0;
	
	if(argc != 2){
		ayuda(argv[0]);
		exit(1);
	}
	
	dir_ecg = argv[1];
	
	ecg = fopen(dir_ecg, "r");
	if(ecg == NULL){
			fprintf(stderr, "Error al abrir el archivo.\n");
			exit(1);
	}
	
	valores = leer_datos(ecg, &n_valores);
	fclose(ecg);
	
	for(i = 0; i < DATA_COLS; i++){
		sprintf(dir_salida, "query_%d.sql", i+1);
		salida = fopen(dir_salida, "w");
		
		fprintf(salida, "INSERT INTO TABLA (ATRIBUTO)\nVALUES('{");
		for(j = 0; j < n_valores-1; j++)
			fprintf(salida, "%f,", valores[i][j]);
			
		fprintf(salida, "%f}');", valores[i][j]);
		fclose(salida);
		fprintf(stdout, "Escribio el archivo query_%d.sql\n", i+1);
	}
	
	return 0;
}
