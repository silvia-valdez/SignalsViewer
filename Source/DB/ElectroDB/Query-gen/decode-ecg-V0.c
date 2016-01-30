/*
  Lee archivos binarios conteniendo
  ECG de tres canales codificados a 8 bits

  Los valores de los canales se almacenan en el stream de la siguiente forma:
  c1(0)c2(0)c3(0)c1(1)c2(1)c3(1)c1(2)c2(2)c3(2)...
  
  Cada valor es un entero de 8 bits sin signo.


  Ines F. Vega-Lopez
  Universidad Autonomade Sinaloa

  Culiacan, Sinaloa, Mexico

  Abril 2012
  
  Last Change by: Daniel E. Lopez
  October 2013.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <unistd.h>

#define CANALES 3

/* 
	Baseline for Holter files
	from Dr. Lerma (Megaoyi)
*/
#define BASELINE_L 0.5

/*
	Baseline for Holter files
	Physiology Department, National Institute for Cardiology, Mexico (Spacelab)
*/ 
#define BASELINE_E 2.5


/*
	Return the index of the min value from 
	an n-dimensional vector of length n
*/
int indice_min(int *vector, int n){
	int i, minimo;
	
	minimo = 0;
	for(i = 1; i < n; i++){
		if(vector[i] < vector[minimo])
			minimo = i;
	}
	
	return minimo;
}

/* 
	Return the max value from an n-dimensioal vector (int version)
*/
int max(int *vector, int n){
	int i, maximo;
	
	maximo = vector[0];
	for(i = 1; i < n; i++)
		if(vector[i] > maximo) maximo = vector[i];
	
	return maximo;
}

/* 
	Return the max value from an n-dimensioal vector (float version)
*/
float max_float(float *vector, int n){
	int i;
	float maximo;
	
	maximo = vector[0];
	for(i = 1; i < n; i++)
		if(vector[i] > maximo) maximo = vector[i];
	
	return maximo;
}


int main(int argc, char **argv)
{
  FILE *infile;
  int *data[CANALES];
  
  int i,j;
  int n;
  
  float scale_factor;

  struct stat buf;

  /* Verify correct usage */
  if (argc < 2)
    {
      fprintf(stderr, "Usage: %s datafile [n]\n", argv[0]);
      fprintf(stderr, "\t\t n is optional, read n values from datafile\n");
      fprintf(stderr, "\t\t default behavior is to read the entire datafile.\n");
      exit(1);
    }

  /* Get the size of datafile */
  if (stat(argv[1], &buf))
    {
      fprintf(stderr, "Error, could not open datafile %s\n", argv[1]);
      exit(1);
    }

  /* If there is a fourth param, get n */
  if (argc == 3)
    n = atoi(argv[2]);
  else
    n = buf.st_size / CANALES;



  /* assign memory */
  for (i = 0; i < CANALES; i ++)
    data[i] =(int *) malloc (sizeof(int) * n);


  /* open datafile */
  infile = fopen(argv[1], "r");
  

  /* read data */
  for(j = 0; j < n; j++)
    {
      for (i = 0; i < CANALES; i ++)
			{
				fread(&data[i][j], 1, sizeof(char), infile);
	  			data[i][j] = data[i][j] & 0xFF; /*this is needed since data is coded as 8-bit integers */
			}
    }

	/*
		Change conding from integers to floats.
		Integer values are coded in a form of quantization
		
		Assume value range is 5 volts.
		Since encoding is 8-bits, each quantil is 5/(2^8) volts.
		
		To this, add the baseline
	*/
  
  	scale_factor = 5.0/256;
  
	/* Simply send decoded values to stdout */
  	fprintf(stdout, "#time \t C1 \t C2 \t C3 \n");
  	for(i = 0; i < n; i++)
		{
			fprintf(stdout, "%d \t", i);
	  
			for(j = 0; j < CANALES; j++) 
				fprintf(stdout, "%f \t", data[j][i]*scale_factor);
	  
			fprintf(stdout, "\n");
		}

	return 0;
}
