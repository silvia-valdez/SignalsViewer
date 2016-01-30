
--- ELECTRODB ---

CREATE TABLE BD (
	ID SERIAL PRIMARY KEY,
	Nombre CHAR(30)
);

CREATE TABLE Paciente (
	ID SERIAL PRIMARY KEY,
	ID_BD INT,
	Fecha_Nac DATE NULL,
	Genero CHAR NULL,
	FOREIGN KEY (ID_BD) REFERENCES BD(ID)
);

CREATE TABLE Electrocardiografia (
	ID SERIAL PRIMARY KEY,
	ID_Paciente INT,
	Frecuencia_Muestreo INT,
	Longitud INT,
	FOREIGN KEY (ID_Paciente) REFERENCES Paciente(ID)
);

CREATE TABLE Derivacion (
	ID SERIAL PRIMARY KEY,
	ID_Electrocardiografia INT,
	Signal FLOAT[],
	Posicion CHAR(10),
	FOREIGN KEY (ID_Electrocardiografia) REFERENCES Electrocardiografia(ID)
);

CREATE TABLE Anotacion (
	ID_Derivacion INT,
	Indice INT,
	Nota INT,
	FOREIGN KEY (ID_Derivacion) REFERENCES Derivacion(ID)
);
