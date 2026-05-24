<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523115828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'v1.0.3: Add SourceError entity to log errors during fetching/parsing';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE source_errors (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, exception_class VARCHAR(255) NOT NULL, message CLOB NOT NULL, file VARCHAR(255) DEFAULT NULL, line INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, source_id INTEGER NOT NULL, CONSTRAINT FK_8AAE76FB953C1C61 FOREIGN KEY (source_id) REFERENCES sources (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8AAE76FB953C1C61 ON source_errors (source_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE source_errors');
    }
}
