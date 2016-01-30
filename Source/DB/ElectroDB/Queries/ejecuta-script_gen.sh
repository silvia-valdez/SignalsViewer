#!/bin/bash
# $1 Lista de Archivos
export PGPASSWORD="bitnami"

for ecg in $(cat $1)
do
	psql signals_viewer postgres -f $ecg
	echo "Termino con $ecg"
done
