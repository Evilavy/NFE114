<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806132709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__enfant AS SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM enfant');
        $this->addSql('DROP TABLE enfant');
        $this->addSql('CREATE TABLE enfant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, certificat_scolarite VARCHAR(255) DEFAULT NULL, sexe VARCHAR(10) NOT NULL, ecole_id INTEGER NOT NULL, user_id INTEGER NOT NULL, valide_par_admin BOOLEAN NOT NULL, date_creation DATETIME NOT NULL)');
        $this->addSql('INSERT INTO enfant (id, nom, prenom, date_naissance, certificat_scolarite) SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM __temp__enfant');
        $this->addSql('DROP TABLE __temp__enfant');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trajet AS SELECT id, point_depart, heure_depart, places_disponibles, statut, ville, cout_points FROM trajet');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('CREATE TABLE trajet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, point_depart VARCHAR(255) NOT NULL, heure_depart VARCHAR(10) NOT NULL, nombre_places INTEGER NOT NULL, statut VARCHAR(255) NOT NULL, point_arrivee VARCHAR(255) NOT NULL, cout_points INTEGER NOT NULL, date_depart DATE NOT NULL, date_arrivee DATE NOT NULL, heure_arrivee VARCHAR(10) NOT NULL, conducteur_id INTEGER NOT NULL, voiture_id INTEGER NOT NULL, description CLOB DEFAULT NULL, enfants_ids CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('INSERT INTO trajet (id, point_depart, heure_depart, nombre_places, statut, point_arrivee, cout_points) SELECT id, point_depart, heure_depart, places_disponibles, statut, ville, cout_points FROM __temp__trajet');
        $this->addSql('DROP TABLE __temp__trajet');
        $this->addSql('ALTER TABLE voiture ADD COLUMN user_id INTEGER NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__enfant AS SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM enfant');
        $this->addSql('DROP TABLE enfant');
        $this->addSql('CREATE TABLE enfant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, certificat_scolarite VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO enfant (id, nom, prenom, date_naissance, certificat_scolarite) SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM __temp__enfant');
        $this->addSql('DROP TABLE __temp__enfant');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trajet AS SELECT id, point_depart, point_arrivee, heure_depart, statut, cout_points FROM trajet');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('CREATE TABLE trajet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, point_depart VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, heure_depart DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, cout_points INTEGER NOT NULL, places_disponibles INTEGER NOT NULL)');
        $this->addSql('INSERT INTO trajet (id, point_depart, ville, heure_depart, statut, cout_points) SELECT id, point_depart, point_arrivee, heure_depart, statut, cout_points FROM __temp__trajet');
        $this->addSql('DROP TABLE __temp__trajet');
        $this->addSql('CREATE TEMPORARY TABLE __temp__voiture AS SELECT id, marque, modele, couleur, nombre_places FROM voiture');
        $this->addSql('DROP TABLE voiture');
        $this->addSql('CREATE TABLE voiture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, marque VARCHAR(255) NOT NULL, modele VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, nombre_places INTEGER NOT NULL)');
        $this->addSql('INSERT INTO voiture (id, marque, modele, couleur, nombre_places) SELECT id, marque, modele, couleur, nombre_places FROM __temp__voiture');
        $this->addSql('DROP TABLE __temp__voiture');
    }
}
