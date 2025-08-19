<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731091332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ecole_suggestion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_by_id INTEGER DEFAULT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, state BOOLEAN NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_D75DC5B3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D75DC5B3B03A8386 ON ecole_suggestion (created_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ecole AS SELECT id, nom, adresse, ville, code_postal FROM ecole');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('CREATE TABLE ecole (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL)');
        $this->addSql('INSERT INTO ecole (id, nom, adresse, ville, code_postal) SELECT id, nom, adresse, ville, code_postal FROM __temp__ecole');
        $this->addSql('DROP TABLE __temp__ecole');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, password, ville, roles, nom, prenom, is_verified, points, role, role_autre, is_approved_by_admin, approved_at, created_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, points INTEGER DEFAULT 0 NOT NULL, role VARCHAR(50) NOT NULL, role_autre VARCHAR(255) DEFAULT NULL, is_approved_by_admin BOOLEAN NOT NULL, approved_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, password, ville, roles, nom, prenom, is_verified, points, role, role_autre, is_approved_by_admin, approved_at, created_at) SELECT id, email, password, ville, roles, nom, prenom, is_verified, points, role, role_autre, is_approved_by_admin, approved_at, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ecole_suggestion');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ecole AS SELECT id, nom, adresse, ville, code_postal FROM ecole');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('CREATE TABLE ecole (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(255) DEFAULT NULL, code_postal VARCHAR(10) DEFAULT NULL)');
        $this->addSql('INSERT INTO ecole (id, nom, adresse, ville, code_postal) SELECT id, nom, adresse, ville, code_postal FROM __temp__ecole');
        $this->addSql('DROP TABLE __temp__ecole');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, password, ville, roles, nom, prenom, is_verified, points, role, role_autre, is_approved_by_admin, approved_at, created_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, points INTEGER DEFAULT 0 NOT NULL, role VARCHAR(50) DEFAULT NULL, role_autre VARCHAR(255) DEFAULT NULL, is_approved_by_admin BOOLEAN DEFAULT 0, approved_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, password, ville, roles, nom, prenom, is_verified, points, role, role_autre, is_approved_by_admin, approved_at, created_at) SELECT id, email, password, ville, roles, nom, prenom, is_verified, points, role, role_autre, is_approved_by_admin, approved_at, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
    }
}
