<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817163357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ecole AS SELECT id, adresse, code_postal, nom, ville, valide, date_creation, date_validation, commentaire_admin, contributeur_id, email, telephone FROM ecole');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('CREATE TABLE ecole (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, adresse VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, nom VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, valide BOOLEAN DEFAULT 0 NOT NULL, date_creation DATETIME DEFAULT NULL, date_validation DATETIME DEFAULT NULL, commentaire_admin CLOB DEFAULT NULL, contributeur_id INTEGER DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO ecole (id, adresse, code_postal, nom, ville, valide, date_creation, date_validation, commentaire_admin, contributeur_id, email, telephone) SELECT id, adresse, code_postal, nom, ville, valide, date_creation, date_validation, commentaire_admin, contributeur_id, email, telephone FROM __temp__ecole');
        $this->addSql('DROP TABLE __temp__ecole');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ecole_suggestion AS SELECT id, created_by_id, nom, adresse, ville, code_postal, state, created_at FROM ecole_suggestion');
        $this->addSql('DROP TABLE ecole_suggestion');
        $this->addSql('CREATE TABLE ecole_suggestion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_by_id INTEGER DEFAULT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, state BOOLEAN NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_D75DC5B3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO ecole_suggestion (id, created_by_id, nom, adresse, ville, code_postal, state, created_at) SELECT id, created_by_id, nom, adresse, ville, code_postal, state, created_at FROM __temp__ecole_suggestion');
        $this->addSql('DROP TABLE __temp__ecole_suggestion');
        $this->addSql('CREATE INDEX IDX_D75DC5B3B03A8386 ON ecole_suggestion (created_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__message AS SELECT id, contenu, date_envoi, is_lu FROM message');
        $this->addSql('DROP TABLE message');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, contenu CLOB NOT NULL, date_envoi DATETIME NOT NULL, is_lu BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO message (id, contenu, date_envoi, is_lu) SELECT id, contenu, date_envoi, is_lu FROM __temp__message');
        $this->addSql('DROP TABLE __temp__message');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trajet AS SELECT id, cout_points, nombre_places, conducteur_id, voiture_id, date_arrivee, date_depart, description, enfants_ids, heure_arrivee, heure_depart, point_arrivee, point_depart, statut FROM trajet');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('CREATE TABLE trajet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cout_points INTEGER NOT NULL, nombre_places INTEGER NOT NULL, conducteur_id INTEGER NOT NULL, voiture_id INTEGER NOT NULL, date_arrivee DATE NOT NULL, date_depart DATE NOT NULL, description CLOB DEFAULT NULL, enfants_ids CLOB DEFAULT NULL --(DC2Type:json)
        , heure_arrivee VARCHAR(10) NOT NULL, heure_depart VARCHAR(10) NOT NULL, point_arrivee VARCHAR(255) NOT NULL, point_depart VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO trajet (id, cout_points, nombre_places, conducteur_id, voiture_id, date_arrivee, date_depart, description, enfants_ids, heure_arrivee, heure_depart, point_arrivee, point_depart, statut) SELECT id, cout_points, nombre_places, conducteur_id, voiture_id, date_arrivee, date_depart, description, enfants_ids, heure_arrivee, heure_depart, point_arrivee, point_depart, statut FROM __temp__trajet');
        $this->addSql('DROP TABLE __temp__trajet');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ecole AS SELECT id, nom, adresse, code_postal, ville, valide, date_creation, date_validation, contributeur_id, telephone, email, commentaire_admin FROM ecole');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('CREATE TABLE ecole (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, ville VARCHAR(255) NOT NULL, valide BOOLEAN DEFAULT 0 NOT NULL, date_creation DATETIME DEFAULT NULL, date_validation DATETIME DEFAULT NULL, contributeur_id BIGINT DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, commentaire_admin VARCHAR(255) DEFAULT NULL, proposeur_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO ecole (id, nom, adresse, code_postal, ville, valide, date_creation, date_validation, contributeur_id, telephone, email, commentaire_admin) SELECT id, nom, adresse, code_postal, ville, valide, date_creation, date_validation, contributeur_id, telephone, email, commentaire_admin FROM __temp__ecole');
        $this->addSql('DROP TABLE __temp__ecole');
        $this->addSql('ALTER TABLE ecole_suggestion ADD COLUMN commentaire_admin VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ecole_suggestion ADD COLUMN contributeur_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE ecole_suggestion ADD COLUMN date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ecole_suggestion ADD COLUMN date_modification DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ecole_suggestion ADD COLUMN email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ecole_suggestion ADD COLUMN statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ecole_suggestion ADD COLUMN telephone VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD COLUMN destinataire_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE message ADD COLUMN expediteur_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE message ADD COLUMN lu BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE message ADD COLUMN trajet_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE trajet ADD COLUMN distance_km DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE trajet ADD COLUMN duree_minutes INTEGER DEFAULT NULL');
    }
}
