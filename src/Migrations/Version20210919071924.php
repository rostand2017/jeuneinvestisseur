<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210919071924 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(254) NOT NULL, description VARCHAR(254) DEFAULT NULL, image VARCHAR(254) NOT NULL, createdat DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, news INT DEFAULT NULL, user INT DEFAULT NULL, comment INT DEFAULT NULL, content VARCHAR(254) NOT NULL, name VARCHAR(254) NOT NULL, email VARCHAR(254) NOT NULL, createdat DATETIME DEFAULT NULL, INDEX fk_association3 (user), INDEX fk_association2 (news), INDEX fk_association5 (comment), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE emails (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(254) NOT NULL, createdat DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE news (id INT AUTO_INCREMENT NOT NULL, category INT DEFAULT NULL, user INT DEFAULT NULL, title VARCHAR(254) NOT NULL, content VARCHAR(255) NOT NULL, image VARCHAR(254) NOT NULL, createdat DATETIME NOT NULL, updatedat DATETIME NOT NULL, INDEX fk_association6 (user), INDEX fk_association4 (category), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(254) NOT NULL, name VARCHAR(254) NOT NULL, password VARCHAR(254) NOT NULL, roles JSON DEFAULT NULL, createdat DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE viewers (id INT AUTO_INCREMENT NOT NULL, news INT DEFAULT NULL, viewerkey VARCHAR(254) NOT NULL, createdat DATETIME DEFAULT NULL, INDEX fk_association1 (news), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C1DD39950 FOREIGN KEY (news) REFERENCES news (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C8D93D649 FOREIGN KEY (user) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C9474526C FOREIGN KEY (comment) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE news ADD CONSTRAINT FK_1DD3995064C19C1 FOREIGN KEY (category) REFERENCES category (id)');
        $this->addSql('ALTER TABLE news ADD CONSTRAINT FK_1DD399508D93D649 FOREIGN KEY (user) REFERENCES user (id)');
        $this->addSql('ALTER TABLE viewers ADD CONSTRAINT FK_C36DC1DD39950 FOREIGN KEY (news) REFERENCES news (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE news DROP FOREIGN KEY FK_1DD3995064C19C1');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C9474526C');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C1DD39950');
        $this->addSql('ALTER TABLE viewers DROP FOREIGN KEY FK_C36DC1DD39950');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C8D93D649');
        $this->addSql('ALTER TABLE news DROP FOREIGN KEY FK_1DD399508D93D649');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE emails');
        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE viewers');
    }
}
