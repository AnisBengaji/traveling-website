<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427210048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP FOREIGN KEY fk_comment_author
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP FOREIGN KEY fk_comment_author
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment CHANGE author author INT NOT NULL, CHANGE date_comment date_comment DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT FK_9474526CBDAFD8C8 FOREIGN KEY (author) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_comment_author ON comment
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9474526CBDAFD8C8 ON comment (author)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT fk_comment_author FOREIGN KEY (author) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY dislike_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY dislike_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY dislike_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY dislike_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike CHANGE publication_id publication_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT FK_FE3BECAA38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id_publication)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT FK_FE3BECAAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX publication_id ON dislike
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE3BECAA38B217A7 ON dislike (publication_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX user_id ON dislike
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE3BECAAA76ED395 ON dislike (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT dislike_ibfk_1 FOREIGN KEY (publication_id) REFERENCES publication (id_publication) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT dislike_ibfk_2 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY like_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY like_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY like_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY like_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` CHANGE publication_id publication_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT FK_AC6340B338B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id_publication)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT FK_AC6340B3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX publication_id ON `like`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AC6340B338B217A7 ON `like` (publication_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX user_id ON `like`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AC6340B3A76ED395 ON `like` (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT like_ibfk_1 FOREIGN KEY (publication_id) REFERENCES publication (id_publication) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT like_ibfk_2 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE publication DROP FOREIGN KEY FK_publication_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_publication_user ON publication
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AF3C6779F675F31B ON publication (author_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE publication ADD CONSTRAINT FK_publication_user FOREIGN KEY (author_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP FOREIGN KEY FK_9474526CBDAFD8C8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP FOREIGN KEY FK_9474526CBDAFD8C8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment CHANGE author author INT DEFAULT NULL, CHANGE date_comment date_comment DATE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT fk_comment_author FOREIGN KEY (author) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_9474526cbdafd8c8 ON comment
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_comment_author ON comment (author)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT FK_9474526CBDAFD8C8 FOREIGN KEY (author) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY FK_FE3BECAA38B217A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY FK_FE3BECAAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY FK_FE3BECAA38B217A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike DROP FOREIGN KEY FK_FE3BECAAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike CHANGE publication_id publication_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT dislike_ibfk_1 FOREIGN KEY (publication_id) REFERENCES publication (id_publication) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT dislike_ibfk_2 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_fe3becaaa76ed395 ON dislike
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX user_id ON dislike (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_fe3becaa38b217a7 ON dislike
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX publication_id ON dislike (publication_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT FK_FE3BECAA38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id_publication)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dislike ADD CONSTRAINT FK_FE3BECAAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY FK_AC6340B338B217A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY FK_AC6340B3A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY FK_AC6340B338B217A7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` DROP FOREIGN KEY FK_AC6340B3A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` CHANGE publication_id publication_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT like_ibfk_1 FOREIGN KEY (publication_id) REFERENCES publication (id_publication) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT like_ibfk_2 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_ac6340b338b217a7 ON `like`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX publication_id ON `like` (publication_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_ac6340b3a76ed395 ON `like`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX user_id ON `like` (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT FK_AC6340B338B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id_publication)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `like` ADD CONSTRAINT FK_AC6340B3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_af3c6779f675f31b ON publication
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX FK_publication_user ON publication (author_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779F675F31B FOREIGN KEY (author_id) REFERENCES user (id)
        SQL);
    }
}
