-- Tables pour l'API JavaEE à ajouter à la base de données Symfony

-- Table User (utilisateurs)
CREATE TABLE IF NOT EXISTS user (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    is_approved_by_admin BOOLEAN NOT NULL DEFAULT 0,
    points INTEGER NOT NULL DEFAULT 50,
    created_at VARCHAR(255)
);

-- Table Ecole (écoles pour l'API JavaEE)
CREATE TABLE IF NOT EXISTS ecole_javaee (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    ville VARCHAR(255) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    telephone VARCHAR(255),
    email VARCHAR(255),
    valide BOOLEAN NOT NULL DEFAULT 0,
    date_creation DATETIME,
    date_validation DATETIME
);

-- Table Message (messages pour l'API JavaEE)
CREATE TABLE IF NOT EXISTS message_javaee (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi DATETIME NOT NULL,
    expediteur_id INTEGER NOT NULL,
    destinataire_id INTEGER NOT NULL,
    trajet_id INTEGER,
    lu BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (expediteur_id) REFERENCES user(id),
    FOREIGN KEY (destinataire_id) REFERENCES user(id)
);

-- Table Trajet (trajets pour l'API JavaEE)
CREATE TABLE IF NOT EXISTS trajet_javaee (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    conducteur_id INTEGER NOT NULL,
    voiture_id INTEGER,
    point_depart VARCHAR(255) NOT NULL,
    point_arrivee VARCHAR(255) NOT NULL,
    date_depart DATE NOT NULL,
    date_arrivee DATE NOT NULL,
    heure_depart VARCHAR(10) NOT NULL,
    heure_arrivee VARCHAR(10) NOT NULL,
    nombre_places INTEGER NOT NULL,
    cout_points INTEGER NOT NULL,
    statut VARCHAR(255) NOT NULL,
    description TEXT,
    enfants_ids TEXT,
    distance_km DOUBLE PRECISION,
    duree_minutes INTEGER,
    FOREIGN KEY (conducteur_id) REFERENCES user(id)
);

-- Insertion des utilisateurs par défaut
INSERT OR IGNORE INTO user (nom, prenom, email, password, role, is_approved_by_admin, points, created_at) VALUES
    ('Dupont', 'Jean', 'admin@alloparents.com', '$2y$12$4SO8JPADZtlvuFegcHGTt.oGjDeNUWZ9ek1G4l.K7Dst0w2gmpCBe', 'ROLE_ADMIN', 1, 50, '2025-08-19'),
    ('Martin', 'Sophie', 'parent@alloparents.com', '$2y$12$K45QGVwZkV2UToBe0wj/1e.jbaHPbTO2LjPqacm.JmS3T2QN7Eoie', 'ROLE_PARENT', 1, 50, '2025-08-19');

-- Insertion de l'école par défaut
INSERT OR IGNORE INTO ecole_javaee (nom, adresse, ville, code_postal, valide, date_creation, date_validation) VALUES
    ('Collège Colbert', '50-72 Rue du Devau', 'Cholet', '49300', 1, datetime('now'), datetime('now'));
