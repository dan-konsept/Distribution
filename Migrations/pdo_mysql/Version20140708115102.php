<?php

namespace Claroline\ScormBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/07/08 11:51:03
 */
class Version20140708115102 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_scorm_2004_sco (
                id INT AUTO_INCREMENT NOT NULL, 
                scorm_resource_id INT NOT NULL, 
                sco_parent_id INT DEFAULT NULL, 
                entry_url VARCHAR(255) DEFAULT NULL, 
                scorm_identifier VARCHAR(255) NOT NULL, 
                title VARCHAR(200) NOT NULL, 
                visible TINYINT(1) NOT NULL, 
                parameters VARCHAR(1000) DEFAULT NULL, 
                time_limit_action VARCHAR(255) DEFAULT NULL, 
                launch_data VARCHAR(4000) DEFAULT NULL, 
                is_block TINYINT(1) NOT NULL, 
                INDEX IDX_E88F1DDD167AFF3D (scorm_resource_id), 
                INDEX IDX_E88F1DDD48C689D5 (sco_parent_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE claro_scorm_2004_sco_tracking (
                id INT AUTO_INCREMENT NOT NULL, 
                user_id INT NOT NULL, 
                scorm_id INT NOT NULL, 
                score_raw INT DEFAULT NULL, 
                score_min INT DEFAULT NULL, 
                score_max INT DEFAULT NULL, 
                lesson_status VARCHAR(255) DEFAULT NULL, 
                session_time INT DEFAULT NULL, 
                total_time INT DEFAULT NULL, 
                entry VARCHAR(255) DEFAULT NULL, 
                suspend_data VARCHAR(4096) DEFAULT NULL, 
                credit VARCHAR(255) DEFAULT NULL, 
                exit_mode VARCHAR(255) DEFAULT NULL, 
                lesson_location VARCHAR(255) DEFAULT NULL, 
                lesson_mode VARCHAR(255) DEFAULT NULL, 
                best_score_raw INT DEFAULT NULL, 
                best_lesson_status VARCHAR(255) DEFAULT NULL, 
                INDEX IDX_3A61CA76ED395 (user_id), 
                INDEX IDX_3A61CD75F22BE (scorm_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE claro_scorm_2004_resource (
                id INT AUTO_INCREMENT NOT NULL, 
                hash_name VARCHAR(50) NOT NULL, 
                resourceNode_id INT DEFAULT NULL, 
                UNIQUE INDEX UNIQ_D16AB015B87FAB32 (resourceNode_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_2004_sco 
            ADD CONSTRAINT FK_E88F1DDD167AFF3D FOREIGN KEY (scorm_resource_id) 
            REFERENCES claro_scorm_2004_resource (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_2004_sco 
            ADD CONSTRAINT FK_E88F1DDD48C689D5 FOREIGN KEY (sco_parent_id) 
            REFERENCES claro_scorm_2004_sco (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_2004_sco_tracking 
            ADD CONSTRAINT FK_3A61CA76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_2004_sco_tracking 
            ADD CONSTRAINT FK_3A61CD75F22BE FOREIGN KEY (scorm_id) 
            REFERENCES claro_scorm_2004_sco (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_2004_resource 
            ADD CONSTRAINT FK_D16AB015B87FAB32 FOREIGN KEY (resourceNode_id) 
            REFERENCES claro_resource_node (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_scorm_2004_sco 
            DROP FOREIGN KEY FK_E88F1DDD48C689D5
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_2004_sco_tracking 
            DROP FOREIGN KEY FK_3A61CD75F22BE
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_2004_sco 
            DROP FOREIGN KEY FK_E88F1DDD167AFF3D
        ");
        $this->addSql("
            DROP TABLE claro_scorm_2004_sco
        ");
        $this->addSql("
            DROP TABLE claro_scorm_2004_sco_tracking
        ");
        $this->addSql("
            DROP TABLE claro_scorm_2004_resource
        ");
    }
}