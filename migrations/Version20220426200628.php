<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220426200628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD country_id INT NOT NULL, ADD band_id INT NOT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA749ABEB17 FOREIGN KEY (band_id) REFERENCES band (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7F92F3E70 ON event (country_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA749ABEB17 ON event (band_id)');
        $this->addSql('ALTER TABLE picture ADD event_id INT NOT NULL');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT FK_16DB4F8971F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('CREATE INDEX IDX_16DB4F8971F7E88B ON picture (event_id)');
        $this->addSql('ALTER TABLE review ADD event_id INT NOT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C671F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('CREATE INDEX IDX_794381C671F7E88B ON review (event_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7F92F3E70');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA749ABEB17');
        $this->addSql('DROP INDEX IDX_3BAE0AA7F92F3E70 ON event');
        $this->addSql('DROP INDEX IDX_3BAE0AA749ABEB17 ON event');
        $this->addSql('ALTER TABLE event DROP country_id, DROP band_id');
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY FK_16DB4F8971F7E88B');
        $this->addSql('DROP INDEX IDX_16DB4F8971F7E88B ON picture');
        $this->addSql('ALTER TABLE picture DROP event_id');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C671F7E88B');
        $this->addSql('DROP INDEX IDX_794381C671F7E88B ON review');
        $this->addSql('ALTER TABLE review DROP event_id');
    }
}
