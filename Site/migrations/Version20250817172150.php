<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817172150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE point');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE trajet');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL COLLATE "BINARY", content CLOB NOT NULL COLLATE "BINARY", image VARCHAR(255) NOT NULL COLLATE "BINARY", created_at DATE NOT NULL)');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, contenu CLOB NOT NULL COLLATE "BINARY", date_envoi DATETIME NOT NULL, is_lu BOOLEAN NOT NULL)');
        $this->addSql('CREATE TABLE point (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, solde INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, statut VARCHAR(255) NOT NULL COLLATE "BINARY", date_reservation DATETIME NOT NULL, points_payes INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('CREATE TABLE trajet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cout_points INTEGER NOT NULL, nombre_places INTEGER NOT NULL, conducteur_id INTEGER NOT NULL, voiture_id INTEGER NOT NULL, date_arrivee DATE NOT NULL, date_depart DATE NOT NULL, description CLOB DEFAULT NULL COLLATE "BINARY", enfants_ids CLOB DEFAULT NULL COLLATE "BINARY" --(DC2Type:json)
        , heure_arrivee VARCHAR(10) NOT NULL COLLATE "BINARY", heure_depart VARCHAR(10) NOT NULL COLLATE "BINARY", point_arrivee VARCHAR(255) NOT NULL COLLATE "BINARY", point_depart VARCHAR(255) NOT NULL COLLATE "BINARY", statut VARCHAR(255) NOT NULL COLLATE "BINARY")');
    }
}
