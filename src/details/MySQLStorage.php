<?php
namespace phputil\flags;

class MySQLStorage extends AbstractPDOBasedStorage {

    public function setupCommand(): string {
        $tableName = $this->flagTableName;
        return <<<SQL
            CREATE TABLE IF NOT EXISTS $tableName (
                `id`          INT           NOT NULL AUTO_INCREMENT,
                `key`         VARCHAR(50)   NOT NULL,
                `enabled`     TINYINT(1)    DEFAULT 0,
                `description` VARCHAR(200)  DEFAULT '',
                `createdAt`   DATETIME      DEFAULT NOW(),
                `updatedAt`   DATETIME      DEFAULT NOW(),
                `accessCount` INT           DEFAULT 0,
                `tags`        VARCHAR(200)  DEFAULT '',
                CONSTRAINT pk_{$tableName}        PRIMARY KEY,
                CONSTRAINT unq_{$tableName}_name  UNIQUE( `key` )
            ) ENGINE=INNODB;
        SQL;
    }

}

