<?php

namespace Claroline\PortfolioBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2019/02/13 02:48:25
 */
class Version20190213144824 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
            CREATE TABLE claro_portfolio (
                id INT AUTO_INCREMENT NOT NULL, 
                owner_id INT NOT NULL, 
                title VARCHAR(128) NOT NULL, 
                slug VARCHAR(128) DEFAULT NULL, 
                visibility INT NOT NULL, 
                uuid VARCHAR(36) NOT NULL, 
                UNIQUE INDEX UNIQ_FD7B953E989D9B62 (slug), 
                UNIQUE INDEX UNIQ_FD7B953ED17F50A6 (uuid), 
                INDEX IDX_FD7B953E7E3C61F9 (owner_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE claro_portfolio_tabs (
                portfolio_id INT NOT NULL, 
                hometab_id INT NOT NULL, 
                INDEX IDX_22D71F48B96B5643 (portfolio_id), 
                INDEX IDX_22D71F48CCE862F (hometab_id), 
                PRIMARY KEY(portfolio_id, hometab_id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE claro_portfolio_users (
                portfolio_id INT NOT NULL, 
                user_id INT NOT NULL, 
                INDEX IDX_B80F40D6B96B5643 (portfolio_id), 
                INDEX IDX_B80F40D6A76ED395 (user_id), 
                PRIMARY KEY(portfolio_id, user_id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE claro_portfolio_groups (
                portfolio_id INT NOT NULL, 
                group_id INT NOT NULL, 
                INDEX IDX_46A798A8B96B5643 (portfolio_id), 
                INDEX IDX_46A798A8FE54D947 (group_id), 
                PRIMARY KEY(portfolio_id, group_id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE claro_portfolio_teams (
                portfolio_id INT NOT NULL, 
                team_id INT NOT NULL, 
                INDEX IDX_3A4EC767B96B5643 (portfolio_id), 
                INDEX IDX_3A4EC767296CD8AE (team_id), 
                PRIMARY KEY(portfolio_id, team_id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE claro_portfolio_comment (
                id INT AUTO_INCREMENT NOT NULL, 
                portfolio_id INT NOT NULL, 
                user_id INT DEFAULT NULL, 
                content LONGTEXT NOT NULL, 
                creation_date DATETIME NOT NULL, 
                uuid VARCHAR(36) NOT NULL, 
                UNIQUE INDEX UNIQ_1CCAC22BD17F50A6 (uuid), 
                INDEX IDX_1CCAC22BB96B5643 (portfolio_id), 
                INDEX IDX_1CCAC22BA76ED395 (user_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio 
            ADD CONSTRAINT FK_FD7B953E7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id)
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_tabs 
            ADD CONSTRAINT FK_22D71F48B96B5643 FOREIGN KEY (portfolio_id) 
            REFERENCES claro_portfolio (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_tabs 
            ADD CONSTRAINT FK_22D71F48CCE862F FOREIGN KEY (hometab_id) 
            REFERENCES claro_home_tab (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_users 
            ADD CONSTRAINT FK_B80F40D6B96B5643 FOREIGN KEY (portfolio_id) 
            REFERENCES claro_portfolio (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_users 
            ADD CONSTRAINT FK_B80F40D6A76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_groups 
            ADD CONSTRAINT FK_46A798A8B96B5643 FOREIGN KEY (portfolio_id) 
            REFERENCES claro_portfolio (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_groups 
            ADD CONSTRAINT FK_46A798A8FE54D947 FOREIGN KEY (group_id) 
            REFERENCES claro_group (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_teams 
            ADD CONSTRAINT FK_3A4EC767B96B5643 FOREIGN KEY (portfolio_id) 
            REFERENCES claro_portfolio (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_teams 
            ADD CONSTRAINT FK_3A4EC767296CD8AE FOREIGN KEY (team_id) 
            REFERENCES claro_team (id) 
            ON DELETE CASCADE
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_comment 
            ADD CONSTRAINT FK_1CCAC22BB96B5643 FOREIGN KEY (portfolio_id) 
            REFERENCES claro_portfolio (id)
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_comment 
            ADD CONSTRAINT FK_1CCAC22BA76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE SET NULL
        ');
    }

    public function down(Schema $schema)
    {
        $this->addSql('
            ALTER TABLE claro_portfolio_tabs 
            DROP FOREIGN KEY FK_22D71F48B96B5643
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_users 
            DROP FOREIGN KEY FK_B80F40D6B96B5643
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_groups 
            DROP FOREIGN KEY FK_46A798A8B96B5643
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_teams 
            DROP FOREIGN KEY FK_3A4EC767B96B5643
        ');
        $this->addSql('
            ALTER TABLE claro_portfolio_comment 
            DROP FOREIGN KEY FK_1CCAC22BB96B5643
        ');
        $this->addSql('
            DROP TABLE claro_portfolio
        ');
        $this->addSql('
            DROP TABLE claro_portfolio_tabs
        ');
        $this->addSql('
            DROP TABLE claro_portfolio_users
        ');
        $this->addSql('
            DROP TABLE claro_portfolio_groups
        ');
        $this->addSql('
            DROP TABLE claro_portfolio_teams
        ');
        $this->addSql('
            DROP TABLE claro_portfolio_comment
        ');
    }
}