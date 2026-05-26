<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526142147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'v1.0.5: Add item_container, item_title, item_link, item_published_at columns to sources table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sources ADD COLUMN item_container VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sources ADD COLUMN item_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sources ADD COLUMN item_link VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sources ADD COLUMN item_published_at VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__sources AS SELECT id, name, url, format, checksum, status, periodicity, last_fetched_at, created_at, updated_at, category_id FROM sources');
        $this->addSql('DROP TABLE sources');
        $this->addSql('CREATE TABLE sources (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, format VARCHAR(100) NOT NULL, checksum VARCHAR(255) DEFAULT NULL, status VARCHAR(100) NOT NULL, periodicity VARCHAR(100) NOT NULL, last_fetched_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INTEGER DEFAULT NULL, CONSTRAINT FK_D25D65F212469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO sources (id, name, url, format, checksum, status, periodicity, last_fetched_at, created_at, updated_at, category_id) SELECT id, name, url, format, checksum, status, periodicity, last_fetched_at, created_at, updated_at, category_id FROM __temp__sources');
        $this->addSql('DROP TABLE __temp__sources');
        $this->addSql('CREATE INDEX IDX_D25D65F212469DE2 ON sources (category_id)');
    }
}
