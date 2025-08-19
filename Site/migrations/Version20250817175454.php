<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817175454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ecole_suggestion');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE trajet');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ecole_suggestion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_by_id INTEGER DEFAULT NULL, nom VARCHAR(255) NOT NULL COLLATE "BINARY", adresse VARCHAR(255) NOT NULL COLLATE "BINARY", ville VARCHAR(255) NOT NULL COLLATE "BINARY", code_postal VARCHAR(10) NOT NULL COLLATE "BINARY", state BOOLEAN NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_D75DC5B3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D75DC5B3B03A8386 ON ecole_suggestion (created_by_id)');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT DEFAULT NULL, contenu VARCHAR(255) NOT NULL COLLATE "BINARY", date_envoi VARCHAR(255) NOT NULL COLLATE "BINARY", destinataire_id BIGINT NOT NULL, expediteur_id BIGINT NOT NULL, lu BOOLEAN NOT NULL, trajet_id BIGINT NOT NULL)');
        $this->addSql('CREATE TABLE trajet (id INTEGER PRIMARY KEY AUTOINCREMENT DEFAULT NULL, conducteur_id BIGINT NOT NULL, date_arrivee VARCHAR(255) NOT NULL COLLATE "BINARY", date_depart VARCHAR(255) NOT NULL COLLATE "BINARY", description VARCHAR(255) DEFAULT NULL COLLATE "BINARY", distance_km DOUBLE PRECISION DEFAULT NULL, duree_minutes INTEGER DEFAULT NULL, enfants_ids VARCHAR(255) DEFAULT NULL COLLATE "BINARY", heure_arrivee VARCHAR(255) NOT NULL COLLATE "BINARY", heure_depart VARCHAR(255) NOT NULL COLLATE "BINARY", nombre_places INTEGER NOT NULL, point_depart VARCHAR(255) NOT NULL COLLATE "BINARY", cout_points INTEGER NOT NULL, statut VARCHAR(255) NOT NULL COLLATE "BINARY", voiture_id BIGINT NOT NULL, ecole_arrivee_id INTEGER DEFAULT NULL)');
    }
}
