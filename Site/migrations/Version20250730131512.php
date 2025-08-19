<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730131512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ecole ADD COLUMN ville VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ecole ADD COLUMN code_postal VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN role VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN role_autre VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN is_approved_by_admin BOOLEAN DEFAULT 0');
        $this->addSql('ALTER TABLE user ADD COLUMN approved_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN created_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ecole AS SELECT id, nom, adresse FROM ecole');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('CREATE TABLE ecole (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO ecole (id, nom, adresse) SELECT id, nom, adresse FROM __temp__ecole');
        $this->addSql('DROP TABLE __temp__ecole');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, password, ville, roles, nom, prenom, is_verified, points FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, points INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, password, ville, roles, nom, prenom, is_verified, points) SELECT id, email, password, ville, roles, nom, prenom, is_verified, points FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
    }
}
