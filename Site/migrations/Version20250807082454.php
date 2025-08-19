<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250807082454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE trajet_api');
        $this->addSql('CREATE TEMPORARY TABLE __temp__message AS SELECT id, contenu, date_envoi, is_lu FROM message');
        $this->addSql('DROP TABLE message');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, contenu CLOB NOT NULL, date_envoi DATETIME NOT NULL, is_lu BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO message (id, contenu, date_envoi, is_lu) SELECT id, contenu, date_envoi, is_lu FROM __temp__message');
        $this->addSql('DROP TABLE __temp__message');
        $this->addSql('CREATE TEMPORARY TABLE __temp__voiture AS SELECT id, marque, modele, couleur, nombre_places, user_id, immatriculation FROM voiture');
        $this->addSql('DROP TABLE voiture');
        $this->addSql('CREATE TABLE voiture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, marque VARCHAR(255) NOT NULL, modele VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, nombre_places INTEGER NOT NULL, user_id INTEGER NOT NULL, immatriculation VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO voiture (id, marque, modele, couleur, nombre_places, user_id, immatriculation) SELECT id, marque, modele, couleur, nombre_places, user_id, immatriculation FROM __temp__voiture');
        $this->addSql('DROP TABLE __temp__voiture');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trajet_api (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, point_depart VARCHAR(255) NOT NULL COLLATE "BINARY", point_arrivee VARCHAR(255) NOT NULL COLLATE "BINARY", date_depart VARCHAR(255) NOT NULL COLLATE "BINARY", heure_depart VARCHAR(255) NOT NULL COLLATE "BINARY", date_arrivee VARCHAR(255) NOT NULL COLLATE "BINARY", heure_arrivee VARCHAR(255) NOT NULL COLLATE "BINARY", nombre_places INTEGER NOT NULL, conducteur_id INTEGER NOT NULL, voiture_id INTEGER NOT NULL, statut VARCHAR(255) NOT NULL COLLATE "BINARY", description CLOB DEFAULT NULL COLLATE "BINARY", prix DOUBLE PRECISION NOT NULL, enfants_ids CLOB DEFAULT NULL COLLATE "BINARY" --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('ALTER TABLE message ADD COLUMN destinataire_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE message ADD COLUMN expediteur_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE message ADD COLUMN lu BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE message ADD COLUMN trajet_id BIGINT NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__voiture AS SELECT id, marque, modele, couleur, immatriculation, nombre_places, user_id FROM voiture');
        $this->addSql('DROP TABLE voiture');
        $this->addSql('CREATE TABLE voiture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, marque VARCHAR(255) NOT NULL, modele VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, immatriculation VARCHAR(255) DEFAULT NULL, nombre_places INTEGER NOT NULL, user_id INTEGER NOT NULL)');
        $this->addSql('INSERT INTO voiture (id, marque, modele, couleur, immatriculation, nombre_places, user_id) SELECT id, marque, modele, couleur, immatriculation, nombre_places, user_id FROM __temp__voiture');
        $this->addSql('DROP TABLE __temp__voiture');
    }
}
