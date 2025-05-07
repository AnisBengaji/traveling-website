<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250417171739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_42c84955a76ed395 ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (Id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD roles JSON NOT NULL COMMENT '(DC2Type:json)', DROP Num_tel, DROP Role, CHANGE Nom nom VARCHAR(255) NOT NULL, CHANGE Prenom prenom VARCHAR(255) NOT NULL, CHANGE Email email VARCHAR(180) NOT NULL, CHANGE MDP password VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_42c84955a76ed395 ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX FK_42C84955A76ED395 ON reservation (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD Num_tel INT NOT NULL, ADD Role VARCHAR(20) NOT NULL, DROP roles, CHANGE email Email VARCHAR(70) NOT NULL, CHANGE nom Nom VARCHAR(20) NOT NULL, CHANGE prenom Prenom VARCHAR(20) NOT NULL, CHANGE password MDP VARCHAR(255) NOT NULL
        SQL);
    }
}
