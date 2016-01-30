#!/bin/bash
dbname="electrodb"
username="postgres"
passwrd="hunabsys123"
psql $dbname $username $passwrd << EOF
SELECT * FROM paciente;
EOF
