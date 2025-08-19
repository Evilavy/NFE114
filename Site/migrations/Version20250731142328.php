<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731142328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trajet_api (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, point_depart VARCHAR(255) NOT NULL, point_arrivee VARCHAR(255) NOT NULL, date_depart VARCHAR(255) NOT NULL, heure_depart VARCHAR(255) NOT NULL, date_arrivee VARCHAR(255) NOT NULL, heure_arrivee VARCHAR(255) NOT NULL, nombre_places INTEGER NOT NULL, conducteur_id INTEGER NOT NULL, voiture_id INTEGER NOT NULL, statut VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, prix DOUBLE PRECISION NOT NULL, enfants_ids CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE trajet_api');
    }
}
