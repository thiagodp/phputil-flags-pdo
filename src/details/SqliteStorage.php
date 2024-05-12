<?php
namespace phputil\flags\pdo;

class SQLiteStorage extends AbstractPDOBasedStorage {

    public function __construct( \PDO $pdo, string $flagTableName ) {
        parent::__construct( $pdo, $flagTableName );
        $this->dateFormat = 'c'; // ISO 8601 date
    }

    public function setupCommand(): string {
        $tableName = $this->flagTableName;
        return <<<SQL
            CREATE TABLE IF NOT EXISTS $tableName (
                `id`          INTEGER       PRIMARY KEY AUTOINCREMENT,
                `key`         TEXT          NOT NULL UNIQUE,
                `enabled`     INTEGER       DEFAULT 0,
                `description` TEXT          DEFAULT '',
                `createdAt`   TEXT          DEFAULT CURRENT_TIMESTAMP,
                `updatedAt`   TEXT          DEFAULT CURRENT_TIMESTAMP,
                `accessCount` INTEGER       DEFAULT 0,
                `tags`        TEXT          DEFAULT ''
            );
        SQL;
    }

}
