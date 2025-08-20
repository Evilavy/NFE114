# NFE114 - AlloParents

Application de covoiturage scolaire pour les parents d'élèves.

## 🚀 Installation rapide

### Prérequis
- PHP 8.1+
- Composer
- Java 17+
- Maven
- .NET 9.0+
- Symfony CLI

### Ports utilisés
- **Symfony** : http://127.0.0.1:8000
- **API JavaEE** : http://localhost:8080
- **API Points (.NET)** : http://localhost:5000

## 📦 Installation

### 1. Cloner le repository
```bash
git clone https://github.com/Evilavy/NFE114.git
cd NFE114
```

### 2. Configuration Symfony
```bash
cd Site
./setup.sh
```

## 🚀 Démarrage des services

### 1. Démarrer l'API JavaEE
```bash
cd JavaEE-API/AlloParents-api
mvn clean package
java -jar payara-micro.jar --deploy target/demo-api.war --port 8080 --noCluster
```

### 2. Démarrer l'API Points (.NET)
```bash
cd PointsApi
dotnet run
```

### 3. Démarrer Symfony
```bash
cd Site
symfony server:start
```

### 4. Accéder à l'application
Ouvrez votre navigateur sur : http://127.0.0.1:8000

## 👥 Comptes de test

### Parent avec rôle Admin
- **Email** : admin@alloparents.com
- **Mot de passe** : admin123

### Parent
- **Email** : parent@alloparents.com
- **Mot de passe** : parent123

## 📁 Structure du projet

```
NFE114/
├── Site/                 # Application Symfony
├── JavaEE-API/          # API JavaEE (utilisateurs)
├── PointsApi/           # API .NET (système de points)
└── start-all.sh         # Script de démarrage automatique
```

## 🗄️ Base de données

L'application utilise une base de données SQLite unifiée (`Site/var/app.db`) partagée entre :
- **Symfony** : Gestion des enfants, voitures, trajets
- **API JavaEE** : Gestion des utilisateurs et authentification
- **API .NET** : Système de points

## 📚 Documentation

Pour plus de détails sur la configuration Symfony, consultez `Site/README_SETUP.md`.

## 🚗 Exemple d'utilisation

### Créer un trajet
1. Connectez-vous avec un compte parent
2. Cliquez sur "Créer" dans le menu de navigation
3. Remplissez les informations du trajet :
   - **Départ** : 21 rue de l'île de Sein, 49300 Cholet
   - **Arrivée** : Collège Colbert, Cholet
   - **Date** : Date souhaitée
   - **Heure de départ** : 7h30
   - **Nombre de places** : 4
   - **Coût en points** : 5 points par enfant

4. Ajoutez vos enfants qui participent au trajet
5. Publiez le trajet

### Réserver un trajet
1. Connectez-vous avec un autre compte parent
2. Recherchez des trajets disponibles
3. Sélectionnez un trajet qui correspond à vos besoins
4. Choisissez l'enfant à inscrire
5. Confirmez la réservation (coût : 5 points)
6. Communiquez avec le conducteur via la messagerie intégrée