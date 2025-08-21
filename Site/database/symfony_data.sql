-- Données par défaut pour Symfony (enfants, voitures, etc.)

-- Création des tables manquantes si elles n'existent pas
CREATE TABLE IF NOT EXISTS ecole (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    code_postal VARCHAR(255) NOT NULL,
    ville VARCHAR(255) NOT NULL,
    valide BOOLEAN DEFAULT 0 NOT NULL,
    date_creation DATETIME DEFAULT NULL,
    date_validation DATETIME DEFAULT NULL,
    commentaire_admin VARCHAR(255) DEFAULT NULL,
    contributeur_id BIGINT DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    telephone VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS user (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    points INTEGER NOT NULL,
    is_approved_by_admin BOOLEAN NOT NULL,
    created_at VARCHAR(255) DEFAULT NULL
);

-- Insertion de l'école par défaut (si pas déjà présente)
INSERT OR IGNORE INTO ecole (nom, adresse, code_postal, ville, valide) 
VALUES (
    'Collège Colbert',
    '50-72 Rue du Devau',
    '49300',
    'Cholet',
    1
);

-- Insertion des utilisateurs par défaut
INSERT OR IGNORE INTO user (email, nom, prenom, password, role, points, is_approved_by_admin, created_at) 
VALUES 
    ('admin@alloparents.com', 'Admin', 'Admin', '$2y$13$5bzXspVm9VZo7kCItKPlvO2.A4UaJEqIRZZgzgNKKFnjuMT0ZCmoS', 'ROLE_ADMIN', 0, 1, '2025-01-01 00:00:00'),
    ('parent@alloparents.com', 'Martin', 'Parent', '$2y$13$flSJzAwpk/kxDJk8/Z4Th.TFP4cXAQjQlOLkSb7fCSzSAnfwtxa/O', 'ROLE_USER', 0, 1, '2025-01-01 00:00:00');

-- Insertion des enfants pour l'admin (user_id = 1)
INSERT OR IGNORE INTO enfant (nom, prenom, date_naissance, sexe, user_id, ecole, valide, date_creation, date_validation) 
VALUES 
    ('Dupont', 'Emma', '2012-05-15', 'F', 1, 'Collège Colbert', 1, datetime('now'), datetime('now')),
    ('Dupont', 'Lucas', '2010-09-22', 'M', 1, 'Collège Colbert', 1, datetime('now'), datetime('now'));

-- Insertion des enfants pour le parent (user_id = 2)
INSERT OR IGNORE INTO enfant (nom, prenom, date_naissance, sexe, user_id, ecole, valide, date_creation, date_validation) 
VALUES 
    ('Martin', 'Chloé', '2011-03-10', 'F', 2, 'Collège Colbert', 1, datetime('now'), datetime('now')),
    ('Martin', 'Thomas', '2009-12-05', 'M', 2, 'Collège Colbert', 1, datetime('now'), datetime('now'));

-- Insertion des voitures pour l'admin (user_id = 1)
INSERT OR IGNORE INTO voiture (marque, modele, couleur, immatriculation, nombre_places, user_id) 
VALUES 
    ('Renault', 'Clio', 'Bleu', 'AB-123-CD', 4, 1);

-- Insertion des voitures pour le parent (user_id = 2)
INSERT OR IGNORE INTO voiture (marque, modele, couleur, immatriculation, nombre_places, user_id) 
VALUES 
    ('Peugeot', '208', 'Blanc', 'EF-456-GH', 4, 2);
