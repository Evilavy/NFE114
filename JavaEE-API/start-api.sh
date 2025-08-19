#!/bin/bash

echo "Démarrage de l'API JavaEE..."

# Aller dans le répertoire du projet
cd "$(dirname "$0")/AlloParents-api"

# Vérifier si Maven est installé
if ! command -v mvn &> /dev/null; then
    echo "Erreur: Maven n'est pas installé. Veuillez installer Maven."
    exit 1
fi

# Compiler et démarrer l'API avec Payara Micro
echo "Compilation et démarrage de l'API..."
mvn clean package payara-micro:start

echo "L'API JavaEE est démarrée sur http://localhost:8080"
echo "Endpoints disponibles:"
echo "  - GET  http://localhost:8080/AlloParents-api/api/ecoles"
echo "  - POST http://localhost:8080/AlloParents-api/api/ecoles"
echo "  - GET  http://localhost:8080/AlloParents-api/api/ecoles/{id}"
echo "  - PUT  http://localhost:8080/AlloParents-api/api/ecoles/{id}"
echo "  - DELETE http://localhost:8080/AlloParents-api/api/ecoles/{id}" 