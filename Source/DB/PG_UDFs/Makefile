PS_CONF = pg_config

INCLUDE_DIR := $(shell $(PS_CONF) --includedir-server)
LIB_DIR := $(shell $(PS_CONF) --libdir)

generador_q: generador_q.c
	gcc -o generador_q generador_q.c

libsubsecuencia.so: libsubsecuencia.c
	gcc -fPIC -shared -o libsubsecuencia.so libsubsecuencia.c -I$(INCLUDE_DIR) -L$(LIB_DIR) -lpq -lm


lib-de-array.so: lib-de-array.c
	gcc -fPIC -shared -o lib-de-array.so lib-de-array.c -I$(INCLUDE_DIR) -L$(LIB_DIR) -lpq -lm


