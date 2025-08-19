-- Données par défaut pour Symfony (enfants, voitures, etc.)

-- Insertion de l'école par défaut (si pas déjà présente)
INSERT OR IGNORE INTO ecole (nom, adresse, code_postal, ville, valide, date_creation, date_validation) 
VALUES (
    'Collège Colbert',
    '50-72 Rue du Devau',
    '49300',
    'Cholet',
    1,
    datetime('now'),
    datetime('now')
);

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
    ('Renault', 'Clio', 'Bleu', 'AB-123-CD', 5, 1);

-- Insertion des voitures pour le parent (user_id = 2)
INSERT OR IGNORE INTO voiture (marque, modele, couleur, immatriculation, nombre_places, user_id) 
VALUES 
    ('Peugeot', '208', 'Blanc', 'EF-456-GH', 5, 2);
