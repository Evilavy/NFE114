#!/bin/bash

echo "Démarrage de l'application Symfony..."

# Aller dans le répertoire du projet
cd "$(dirname "$0")"

# Vérifier si PHP est installé
if ! command -v php &> /dev/null; then
    echo "Erreur: PHP n'est pas installé. Veuillez installer PHP."
    exit 1
fi

# Vérifier si Composer est installé
if ! command -v composer &> /dev/null; then
    echo "Erreur: Composer n'est pas installé. Veuillez installer Composer."
    exit 1
fi

# Installer les dépendances si nécessaire
if [ ! -d "vendor" ]; then
    echo "Installation des dépendances Composer..."
    composer install
fi

# Installer les dépendances npm si nécessaire
if [ ! -d "node_modules" ]; then
    echo "Installation des dépendances npm..."
    npm install
fi

# Compiler les assets
echo "Compilation des assets..."
npm run build

# Démarrer le serveur Symfony
echo "Démarrage du serveur Symfony..."
symfony server:start -d

echo "L'application Symfony est démarrée sur http://localhost:8000"
echo "Interface de gestion des écoles: http://localhost:8000/api-ecole" 