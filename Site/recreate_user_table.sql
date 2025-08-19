-- Script pour recréer la table user avec la structure Java
CREATE TABLE user (
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

-- Insérer quelques utilisateurs de test
INSERT INTO user (nom, prenom, email, password, role, is_approved_by_admin, points, created_at) VALUES
('Admin', 'Administrateur', 'admin@example.com', '$2y$13$yHrMjqzRYWfF20sjmbWx6OpoBZbzoq8SsQYvXOEiZ6PfiPdBjsb7i', 'parent', 1, 100, '2025-08-07 10:01:28'),;
