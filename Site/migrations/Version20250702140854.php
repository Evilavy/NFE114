<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250702140854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ecole AS SELECT id, nom, adresse FROM ecole');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('CREATE TABLE ecole (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO ecole (id, nom, adresse) SELECT id, nom, adresse FROM __temp__ecole');
        $this->addSql('DROP TABLE __temp__ecole');
        $this->addSql('CREATE TEMPORARY TABLE __temp__enfant AS SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM enfant');
        $this->addSql('DROP TABLE enfant');
        $this->addSql('CREATE TABLE enfant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, certificat_scolarite VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO enfant (id, nom, prenom, date_naissance, certificat_scolarite) SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM __temp__enfant');
        $this->addSql('DROP TABLE __temp__enfant');
        $this->addSql('CREATE TEMPORARY TABLE __temp__message AS SELECT id, contenu, date_envoi, is_lu FROM message');
        $this->addSql('DROP TABLE message');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, contenu CLOB NOT NULL, date_envoi DATETIME NOT NULL, is_lu BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO message (id, contenu, date_envoi, is_lu) SELECT id, contenu, date_envoi, is_lu FROM __temp__message');
        $this->addSql('DROP TABLE __temp__message');
        $this->addSql('CREATE TEMPORARY TABLE __temp__reservation AS SELECT id, statut, date_reservation FROM reservation');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, statut VARCHAR(255) NOT NULL, date_reservation DATETIME NOT NULL, points_payes INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO reservation (id, statut, date_reservation) SELECT id, statut, date_reservation FROM __temp__reservation');
        $this->addSql('DROP TABLE __temp__reservation');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trajet AS SELECT id, point_depart, heure_depart, places_disponibles, statut, ville FROM trajet');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('CREATE TABLE trajet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, point_depart VARCHAR(255) NOT NULL, heure_depart DATETIME NOT NULL, places_disponibles INTEGER NOT NULL, statut VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, cout_points INTEGER NOT NULL)');
        $this->addSql('INSERT INTO trajet (id, point_depart, heure_depart, places_disponibles, statut, ville) SELECT id, point_depart, heure_depart, places_disponibles, statut, ville FROM __temp__trajet');
        $this->addSql('DROP TABLE __temp__trajet');
        $this->addSql('ALTER TABLE user ADD COLUMN points INTEGER DEFAULT 0 NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__voiture AS SELECT id, marque, modele, couleur, nombre_places FROM voiture');
        $this->addSql('DROP TABLE voiture');
        $this->addSql('CREATE TABLE voiture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, marque VARCHAR(255) NOT NULL, modele VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, nombre_places INTEGER NOT NULL)');
        $this->addSql('INSERT INTO voiture (id, marque, modele, couleur, nombre_places) SELECT id, marque, modele, couleur, nombre_places FROM __temp__voiture');
        $this->addSql('DROP TABLE __temp__voiture');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ecole ADD COLUMN ville VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ecole ADD COLUMN code_postal VARCHAR(255) NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__enfant AS SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM enfant');
        $this->addSql('DROP TABLE enfant');
        $this->addSql('CREATE TABLE enfant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, parent_id INTEGER NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, certificat_scolarite VARCHAR(255) NOT NULL, CONSTRAINT FK_34B70CA2727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO enfant (id, nom, prenom, date_naissance, certificat_scolarite) SELECT id, nom, prenom, date_naissance, certificat_scolarite FROM __temp__enfant');
        $this->addSql('DROP TABLE __temp__enfant');
        $this->addSql('CREATE INDEX IDX_34B70CA2727ACA70 ON enfant (parent_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__message AS SELECT id, contenu, date_envoi, is_lu FROM message');
        $this->addSql('DROP TABLE message');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, expediteur_id INTEGER NOT NULL, destinataire_id INTEGER NOT NULL, trajet_id INTEGER NOT NULL, enfant_id INTEGER NOT NULL, contenu CLOB NOT NULL, date_envoi DATETIME NOT NULL, is_lu BOOLEAN NOT NULL, CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B6BD307FD12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B6BD307F450D2529 FOREIGN KEY (enfant_id) REFERENCES enfant (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO message (id, contenu, date_envoi, is_lu) SELECT id, contenu, date_envoi, is_lu FROM __temp__message');
        $this->addSql('DROP TABLE __temp__message');
        $this->addSql('CREATE INDEX IDX_B6BD307F450D2529 ON message (enfant_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FD12A823 ON message (trajet_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FA4F84F6E ON message (destinataire_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F10335F61 ON message (expediteur_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__reservation AS SELECT id, statut, date_reservation FROM reservation');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, trajet_id INTEGER NOT NULL, enfant_id INTEGER NOT NULL, parent_id INTEGER NOT NULL, statut VARCHAR(255) NOT NULL, date_reservation DATETIME NOT NULL, CONSTRAINT FK_42C84955D12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_42C84955450D2529 FOREIGN KEY (enfant_id) REFERENCES enfant (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_42C84955727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO reservation (id, statut, date_reservation) SELECT id, statut, date_reservation FROM __temp__reservation');
        $this->addSql('DROP TABLE __temp__reservation');
        $this->addSql('CREATE INDEX IDX_42C84955727ACA70 ON reservation (parent_id)');
        $this->addSql('CREATE INDEX IDX_42C84955450D2529 ON reservation (enfant_id)');
        $this->addSql('CREATE INDEX IDX_42C84955D12A823 ON reservation (trajet_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trajet AS SELECT id, point_depart, heure_depart, places_disponibles, statut, ville, cout_points FROM trajet');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('CREATE TABLE trajet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, conducteur_id INTEGER NOT NULL, voiture_id INTEGER NOT NULL, ecole_id INTEGER NOT NULL, point_depart VARCHAR(255) NOT NULL, heure_depart DATETIME NOT NULL, places_disponibles INTEGER NOT NULL, statut VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, CONSTRAINT FK_2B5BA98CF16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2B5BA98C181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2B5BA98C77EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trajet (id, point_depart, heure_depart, places_disponibles, statut, ville, conducteur_id) SELECT id, point_depart, heure_depart, places_disponibles, statut, ville, cout_points FROM __temp__trajet');
        $this->addSql('DROP TABLE __temp__trajet');
        $this->addSql('CREATE INDEX IDX_2B5BA98C77EF1B1E ON trajet (ecole_id)');
        $this->addSql('CREATE INDEX IDX_2B5BA98C181A8BA ON trajet (voiture_id)');
        $this->addSql('CREATE INDEX IDX_2B5BA98CF16F4AC6 ON trajet (conducteur_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, password, ville, roles, nom, prenom, is_verified FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, password, ville, roles, nom, prenom, is_verified) SELECT id, email, password, ville, roles, nom, prenom, is_verified FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE TEMPORARY TABLE __temp__voiture AS SELECT id, marque, modele, couleur, nombre_places FROM voiture');
        $this->addSql('DROP TABLE voiture');
        $this->addSql('CREATE TABLE voiture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, proprietaire_id INTEGER NOT NULL, marque VARCHAR(255) NOT NULL, modele VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, nombre_places INTEGER NOT NULL, CONSTRAINT FK_E9E2810F76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO voiture (id, marque, modele, couleur, nombre_places) SELECT id, marque, modele, couleur, nombre_places FROM __temp__voiture');
        $this->addSql('DROP TABLE __temp__voiture');
        $this->addSql('CREATE INDEX IDX_E9E2810F76C50E4A ON voiture (proprietaire_id)');
    }
}
