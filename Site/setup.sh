#!/bin/bash

echo "🚀 Configuration et initialisation du projet NFE114 Site"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "composer.json" ]; then
    echo "❌ Erreur: Ce script doit être exécuté depuis le répertoire Site/"
    exit 1
fi

# Vérifier que PHP est installé
if ! command -v php &> /dev/null; then
    echo "❌ Erreur: PHP n'est pas installé. Veuillez installer PHP."
    exit 1
fi

# Vérifier que Composer est installé
if ! command -v composer &> /dev/null; then
    echo "❌ Erreur: Composer n'est pas installé. Veuillez installer Composer."
    exit 1
fi

# 1. Installation des dépendances
echo "📦 Installation des dépendances Composer..."
composer install --no-interaction --prefer-dist --no-progress

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de l'installation des dépendances Composer"
    exit 1
fi

# 2. Configuration de la base de données
echo "🗄️  Configuration de la base de données..."
if [ ! -f ".env.local" ]; then
    echo 'DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"' > .env.local
    echo "✅ Fichier .env.local créé"
fi

# 3. Création de la base de données pré-remplie
echo "🗄️  Création de la base de données pré-remplie..."

# Créer le répertoire var s'il n'existe pas
if [ ! -d "var" ]; then
    mkdir -p var
    echo "📁 Répertoire var créé"
fi

# Créer la base de données si elle n'existe pas
if [ ! -f "var/app.db" ]; then
    echo "🗄️  Création de la base de données..."
    php bin/console doctrine:database:create --quiet
    
    if [ $? -ne 0 ]; then
        echo "❌ Erreur lors de la création de la base de données"
        exit 1
    fi
    
    echo "🏗️  Création du schéma de base de données..."
    php bin/console doctrine:schema:create --quiet
    
    if [ $? -ne 0 ]; then
        echo "❌ Erreur lors de la création du schéma"
        exit 1
    fi
    
    # Ajouter les tables de l'API JavaEE
    echo "🔧 Ajout des tables de l'API JavaEE..."
    if [ -f "database/javaee_tables.sql" ]; then
        sqlite3 var/app.db < database/javaee_tables.sql
        echo "✅ Tables de l'API JavaEE ajoutées"
    else
        echo "⚠️  Fichier database/javaee_tables.sql non trouvé"
    fi
    
    # Ajouter les données par défaut
    echo "📝 Ajout des données par défaut..."
    if [ -f "database/symfony_data.sql" ]; then
        sqlite3 var/app.db < database/symfony_data.sql
        echo "✅ Données par défaut ajoutées"
    else
        echo "⚠️  Fichier database/symfony_data.sql non trouvé"
    fi
    
    echo "✅ Base de données pré-remplie créée avec succès"
else
    echo "✅ Base de données pré-remplie déjà existante"
fi

echo ""
echo "🎉 Configuration terminée avec succès !"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Démarrer l'API JavaEE : cd ../JavaEE-API && bash start-api.sh"
echo "2. Démarrer le serveur Symfony : php -S 127.0.0.1:8000 -t public"
echo "3. Accéder à l'application : http://127.0.0.1:8000"
echo ""
echo "🔑 Comptes de test disponibles :"
echo "   Admin: admin@alloparents.com / admin123"
echo "   Parent: parent@alloparents.com / parent123"
echo ""
echo "👶 Enfants créés :"
echo "   - Emma et Lucas Dupont (Admin)"
echo "   - Chloé et Thomas Martin (Parent)"
echo ""
echo "🚗 Voitures créées :"
echo "   - Renault Clio (Admin)"
echo "   - Peugeot 208 (Parent)"
echo ""
echo "🏫 École créée : Collège Colbert"
