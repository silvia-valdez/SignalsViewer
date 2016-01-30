#include "postgres.h"
#include "funcapi.h"
#include "utils/builtins.h"
#include "utils/memutils.h"
#include "libpq/libpq-fs.h"
#include "fmgr.h"
#include "catalog/pg_type.h"
#include "libpq/pqformat.h"
#include "utils/lsyscache.h"
#include "utils/array.h"

#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <ctype.h>
#include <limits.h>

#ifdef PG_MODULE_MAGIC
PG_MODULE_MAGIC;
#endif

/* Memoria para guardar los valores
 * del ECG */
typedef struct{
	float8 *sig;
	int n;
	int i;
	int elem;
}srf_f_ctx;

PG_FUNCTION_INFO_V1(muestra_arr);
Datum muestra_arr(PG_FUNCTION_ARGS){
	ArrayType *input, *output; 				/* Tomar el puntero al array */
	Datum *input_data, *output_data; 		/* Valores del array */
	Oid input_eltype, output_eltype;		/* Con esto se determina que tipo de elementos tiene el array (int, float, ... )*/
	int16 input_typelen, output_typelen;	/* LONGITUD en bytes del tipo de elemento */
	bool input_typebyval, output_typebyval;	/* REFERENCIA bandera para determinar si es paso por referencia */
	char input_typealign, output_typealign;	/* Para realizar un alineamiento al colocar la estructura en memoria */
	
	int i, n;
	int ndims, *dims, *lbs;
	bool *nulls;							/* Indica si el elemento es nulo o no */
	float8 val;
	
	
	if(PG_ARGISNULL(0)){
		PG_RETURN_NULL();
	}
	
	/*#### INPUT ####*/
	/* Tomar el array como argumento */
	input = PG_GETARG_ARRAYTYPE_P(0);	
	input_eltype = ARR_ELEMTYPE(input);
	get_typlenbyvalalign(input_eltype, &input_typelen, &input_typebyval, &input_typealign);
	
	/* A priori se que es array de floats */
	output_eltype = FLOAT8OID;
	get_typlenbyvalalign(output_eltype, &output_typelen, &output_typebyval, &output_typealign);
	
	/* Información del array */
	ndims = ARR_NDIM(input);
	dims = ARR_DIMS(input);
	lbs = ARR_LBOUND(input);
	
	/* Obtener los datos del array */	
	deconstruct_array(input, input_eltype, input_typelen, input_typebyval, input_typealign, &input_data, &nulls, &n);
	
	//~ elog(NOTICE, "Las dimensiones son: %d \n", n);
	
	
	/*### OUTPUT ####*/
	output_data = (Datum *) palloc(sizeof(Datum) * n);
	
	/* Tomar los valores e imprimirlos en la consola */
	for(i = 0; i < n; i++){
		if(nulls[i]){
			//~ elog(NOTICE, "El elemento %d es null\n", i);
			output_data[i] = PointerGetDatum(NULL);
		}
		else{
			val = DatumGetFloat8(input_data[i]);
			
			//~ elog(NOTICE, "El elemento %d es %f\n", i, val);
			
			output_data[i] = Float8GetDatum(val);
			
		}
	}
	
	output = construct_md_array((void *) output_data, nulls, ndims, dims, lbs, 
	output_eltype, output_typelen, output_typebyval, output_typealign);
	
	pfree(input_data);
	pfree(output_data);
	pfree(nulls);
	
	PG_RETURN_ARRAYTYPE_P(output);
	
}

PG_FUNCTION_INFO_V1(subsecuencia_arr);
Datum subsecuencia_arr(PG_FUNCTION_ARGS){
	ArrayType *input; 		/* Tomar el puntero al array */
	Datum *input_data; 		/* Valores del array */
	Oid input_eltype;		/* Con esto se determina que tipo de elementos tiene el array (int, float, ... )*/
	int16 input_typelen;	/* LONGITUD en bytes del tipo de elemento */
	bool input_typebyval;	/* REFERENCIA bandera para determinar si es paso por referencia */
	char input_typealign;	/* Para realizar un alineamiento al colocar la estructura en memoria */
	
	int i, n;
	bool *nulls;			/* Indica si el elemento es nulo o no */
	float8 val;
	
	/* limitar la subsecuencia */
	int offset, lim;
	
	/* Variables para la creación del set */
	FuncCallContext *funcctx;
	srf_f_ctx *fctx;
	MemoryContext old_context;
	TupleDesc tupdesc;
    AttInMetadata *attinmeta;
    
    /* Variables para cada tupla en el set */
    char **values;
    HeapTuple tuple;
    Datum result;
	
	/* Primera ejecución de la función */
	if(SRF_IS_FIRSTCALL()){
		
		/* Si hay argumento null, el resultado será null */
		if(PG_ARGISNULL(0)) PG_RETURN_NULL();
		
		/* Tomar el array como argumento */
		input = PG_GETARG_ARRAYTYPE_P(0);	
		input_eltype = ARR_ELEMTYPE(input);
		get_typlenbyvalalign(input_eltype, &input_typelen, &input_typebyval, &input_typealign);
		
		/* Obtener los datos del array */	
		deconstruct_array(input, input_eltype, input_typelen, input_typebyval, input_typealign, &input_data, &nulls, &n);
		
		offset = PG_GETARG_INT32(1);
		lim = PG_GETARG_INT32(2);
		
		if(n < (offset + lim)){
			elog(ERROR, "Subsecuencia fuera de rango\n");
			PG_RETURN_NULL();
		}
		
		/* Una vez leidos los datos, se solicita la memoria para depositarlos
		 * en el set resultante. */
		funcctx = SRF_FIRSTCALL_INIT();
		old_context = MemoryContextSwitchTo(funcctx->multi_call_memory_ctx);
		
		fctx = (srf_f_ctx *) palloc(sizeof(srf_f_ctx));
		fctx->sig = (float8 *) palloc(sizeof(float8) * n);
		fctx->i = offset;
		fctx->elem = (lim == 0) ? n-offset : lim;
		fctx->n = n;
		
		/* Iterar en el array */
		for(i = 0; i < n; i++) fctx->sig[i] = DatumGetFloat8(input_data[i]);
		
		tupdesc = CreateTemplateTupleDesc (2 , 0);
		TupleDescInitEntry (tupdesc, 1, "Indice", INT4OID, -1, 0);
		TupleDescInitEntry (tupdesc, 2, "Valor", FLOAT8OID, -1, 0);
		
		attinmeta = TupleDescGetAttInMetadata(tupdesc);
        funcctx->attinmeta = attinmeta;
		funcctx->user_fctx = fctx;
		
		MemoryContextSwitchTo(old_context);
		
		pfree(input_data);
	}
	
	/* Construir el set */
	funcctx = SRF_PERCALL_SETUP();
	fctx = funcctx->user_fctx;
	attinmeta = funcctx->attinmeta;
	
	if(fctx->elem > 0){
		
		values = (char **) palloc(sizeof(char *) * 2);
		values[0] = (char *) palloc(sizeof(char) * 16);
		values[1] = (char *) palloc(sizeof(char) * 16);
		
		snprintf(values[0], sizeof(char) * 16, "%d", fctx->i);
		snprintf(values[1], sizeof(char) * 16, "%f", fctx->sig[fctx->i]);
		fctx->i = fctx->i + 1;
		fctx->elem = fctx->elem - 1;
		
		tuple = BuildTupleFromCStrings(attinmeta, values);
		result = HeapTupleGetDatum(tuple);
		
		pfree(values[0]);
		pfree(values[1]);
		pfree(values);
		
		SRF_RETURN_NEXT(funcctx, result);		
	}
	else{
		SRF_RETURN_DONE(funcctx);
	}
	
}
