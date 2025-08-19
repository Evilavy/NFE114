<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250819120000 extends AbstractMigration
{
	public function getDescription(): string
	{
		return 'Drop unused ecole_suggestion table';
	}

	public function up(Schema $schema): void
	{
		$this->addSql('DROP TABLE IF EXISTS ecole_suggestion');
	}

	public function down(Schema $schema): void
	{
		$this->addSql('CREATE TABLE ecole_suggestion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_by_id INTEGER DEFAULT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, state BOOLEAN NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_D75DC5B3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
		$this->addSql('CREATE INDEX IDX_D75DC5B3B03A8386 ON ecole_suggestion (created_by_id)');
	}
}


