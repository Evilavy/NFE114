# NFE114 - AlloParents

📱 Important — Application conçue pour mobile

Cette application est optimisée pour un affichage mobile. Si vous l'utilisez sur un ordinateur, activez l'affichage mobile dans votre navigateur :
1. Ouvrez les outils de développement (clic droit → Inspecter).
2. Activez la vue mobile (icône « Toggle device toolbar »).
3. Sélectionnez un appareil (ex. iPhone 14, Pixel 7).


Application de covoiturage scolaire pour les parents d'élèves.

## 🚀 Installation rapide

### Prérequis
- PHP 8.1+
- Composer
- Java 17+
- Payara Micro 6.2025.7
- Apache Maven 3.9.10
- .NET 9.0+
- Symfony CLI

### Ports utilisés
⚠️ Assurez-vous que les ports suivants sont libres avant de lancer les applications, afin d’éviter les conflits :  
- **Symfony** : [http://127.0.0.1:8000](http://127.0.0.1:8000)  
- **API JavaEE (Payara)** : [http://localhost:8080](http://localhost:8080)  
- **API Points (.NET)** : [http://localhost:5000](http://localhost:5000)  

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
├── Site/                # Application Symfony
├── JavaEE-API/          # API JavaEE (utilisateurs)
├── PointsApi/           # API .NET (système de points)
└── start-all.sh         # Script de démarrage automatique
```

## 🗄️ Base de données

L'application utilise une base de données SQLite unifiée (`Site/var/app.db`) partagée entre :
- **Symfony** : Gestion des enfants, voitures, trajets
- **API JavaEE** : Gestion des utilisateurs et authentification
- **API .NET** : Système de points

## 🚗 Exemple d'utilisation

### Créer un trajet
1. Connectez-vous avec un compte parent
2. Cliquez sur "Créer" dans le menu de navigation
3. Remplissez les informations du trajet :
   - **Départ** : 21 rue de l'île de Sein, 49300 Cholet
   - **Arrivée** : Collège Colbert, Cholet
   - **Date** : Date souhaitée
   - **Heure de départ** : 7h30

4. Ajoutez vos enfants qui participent au trajet
5. Publiez le trajet

### Réserver un trajet
1. Connectez-vous avec un autre compte parent
2. Recherchez des trajets disponibles
3. Sélectionnez un trajet qui correspond à vos besoins
4. Choisissez l'enfant à inscrire
5. Confirmez la réservation
6. Communiquez avec le conducteur via la messagerie intégrée