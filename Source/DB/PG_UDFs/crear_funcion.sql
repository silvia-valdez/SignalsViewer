DROP FUNCTION subsecuencia_arr(float ARRAY, integer, integer);
DROP FUNCTION muestra_arr(float ARRAY);
DROP TYPE _ecg_v;

CREATE TYPE _ecg_v AS (f1 integer, f2 float);

CREATE OR REPLACE FUNCTION subsecuencia_arr(float ARRAY, integer, integer)
RETURNS SETOF _ecg_v
AS '/usr/lib/libsubsecuencia.so', 'subsecuencia_arr' 
LANGUAGE C;

CREATE OR REPLACE FUNCTION muestra_arr(float ARRAY)
RETURNS float ARRAY
AS '/usr/lib/libsubsecuencia.so', 'muestra_arr' 
LANGUAGE C;
