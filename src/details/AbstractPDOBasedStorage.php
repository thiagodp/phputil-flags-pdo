<?php
namespace phputil\flags\pdo;

use DateTime;
use PDO;
use PDOException;
use PDOStatement;
use phputil\flags\FlagStorage;
use phputil\flags\FlagException;
use phputil\flags\FlagData;
use phputil\flags\FlagMetadata;

const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

abstract class AbstractPDOBasedStorage implements FlagStorage {

    protected PDO $pdo;
    protected string $flagTableName;
    protected string $dateFormat;

    public function __construct( PDO $pdo, string $flagTableName ) {
        $this->pdo = $pdo;
        $this->flagTableName = $flagTableName;
        $this->dateFormat = DEFAULT_DATE_FORMAT;
    }

    abstract public function setupCommand(): string;

    public function setup() {
        try {
            // Throws \PDOException in case of error
            $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

            // Creates the need database structure, if needed
            $sql = $this->setupCommand();
            $this->pdo->exec( $sql );
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error setting the database up: ' . $e->getMessage(), 0, $e );
        }
    }

    //
    // FlagStorage
    //

    public function isEnabled( string $key ): bool {
        $sql = "SELECT enabled FROM {$this->flagTableName} WHERE key = ?";
        $row = $this->firstRow( $sql, [ $key ] );
        return $row === null ? false : $row[ 'enabled' ];
    }

    /** @inheritDoc */
    public function get( string $key ): ?FlagData {
        $sql = "SELECT * FROM {$this->flagTableName} WHERE key = ?";
        $row = $this->firstRow( $sql, [ $key ] );
        return $row === null ? null : $this->rowToObject( $row );
    }

    /** @inheritDoc */
    public function touch( string $key, ?bool $enabled = null ): ?FlagData {

        $flag = $this->get( $key ) ?? new FlagData( $key, false, new FlagMetadata() );
        if ( $enabled !== null ) {
            $flag->enabled = $enabled;
        }
        $flag->updateAccess();

        if ( $flag->metadata->id == 0 ) { // New flag
            $this->add( $flag );
        } else {
            $this->updateAccess( $flag );
        }

        return $flag;
    }

    /** @inheritDoc */
    public function set( string $key, FlagData $data ): bool {

        if ( $data->metadata->id == 0 ) { // New flag
            $this->add( $data );
            return true;
        }

        $sql = <<<SQL
        UPDATE {$this->flagTableName} SET
            enabled     = :enabled,
            description = :description,
            updatedAt   = :updatedAt,
            accessCount = :accessCount,
            tags        = :tags
        WHERE
            key = :key
        SQL;

        $params = [
            'enabled' => $data->enabled,
            'description' => $data->metadata->description,
            'updatedAt' => $data->metadata->updatedAt->format( $this->dateFormat ),
            'accessCount' => $data->metadata->accessCount,
            'tags' => implode( ',', $data->metadata->tags ),
            // where
            'key' => $key
        ];

        try {
            $ps = $this->run( $sql, $params );
            return $ps->rowCount() > 0;
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error while updating the flag', $e->getCode(), $e );
        }
    }

    /** @inheritDoc */
    public function remove( string $key ): bool {
        try {
            $ps = $this->run( "DELETE FROM {$this->flagTableName} WHERE key = ?", [ $key ] );
            return $ps->rowCount() > 0;
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error while removing the flag', $e->getCode(), $e );
        }
    }

    /** @inheritDoc */
    public function removeAll(): void {
        try {
            $ps = $this->run( "DELETE FROM {$this->flagTableName}", [] );
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error while removing all the flags', $e->getCode(), $e );
        }
    }

    /** @inheritDoc */
    public function getAll( array $options = [] ): array {
        $sql = "SELECT * FROM {$this->flagTableName}";
        $params = [];
        try {
            $ps = $this->run( $sql, $params );
            if ( $ps->rowCount() < 1 ) {
                return [];
            }
            $flags = [];
            foreach ( $ps as $row ) {
                $flags [] = $this->rowToObject( $row );
            }
            return $flags;
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error while getting the flags', $e->getCode(), $e );
        }
    }

    /** @inheritDoc */
    public function count( array $options = [] ): int {
        $sql = "SELECT COUNT(id) AS count FROM {$this->flagTableName}";
        $row = $this->firstRow( $sql );
        return $row === null ? 0 : (int) $row[ 'count' ];
    }

    //
    // Helper methods
    //

    protected function firstRow( string $sql, array $params = [] ): ?array {
        try {
            $ps = $this->run( $sql, $params );
            $content = $ps->fetch();
            $ps->closeCursor();
            return $content === false ? null : $content;
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error while getting the flag', $e->getCode(), $e );
        }
    }

    protected function run( string $sql, array $params ): PDOStatement {
        $ps = $this->pdo->prepare( $sql );
        $ps->setFetchMode( PDO::FETCH_ASSOC );
        $ps->execute( $params );
        return $ps;
    }

    protected function rowToObject( array $r ): FlagData {

        $metadata = new FlagMetadata(
            $r[ 'id' ],
            $r[ 'description' ],
            new DateTime( $r[ 'createdAt' ] ),
            new DateTime( $r[ 'updatedAt' ] ),
            $r[ 'accessCount' ],
            array_map( 'trim', explode( ',', $r[ 'tags' ] ) ),
        );

        return new FlagData( $r[ 'key' ], $r[ 'enabled' ] == 1, $metadata );
    }

    protected function add( FlagData &$data ): void {

        $sql = <<<SQL
            INSERT INTO {$this->flagTableName}
            ( `key`, `enabled`, `description`, `createdAt`, `updatedAt`, `accessCount`, `tags` )
            VALUES
            ( :key, :enabled, :description, :createdAt, :updatedAt, :accessCount, :tags )
        SQL;

        $params = [
            'key' => $data->key,
            'enabled' => $data->enabled,
            'description' => $data->metadata->description,
            'createdAt' => $data->metadata->createdAt->format( $this->dateFormat ),
            'updatedAt' => $data->metadata->updatedAt->format( $this->dateFormat ),
            'accessCount' => $data->metadata->accessCount,
            'tags' => implode( ',', $data->metadata->tags )
        ];

        try {
            $this->run( $sql, $params );
            $data->metadata->id = (int) $this->pdo->lastInsertId();
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error while adding the flag: ' . $e->getMessage(), $e->getCode(), $e );
        }
    }

    protected function updateAccess( FlagData &$data ): bool {

        $sql = <<<SQL
        UPDATE {$this->flagTableName} SET
            updatedAt   = :updatedAt,
            accessCount = :accessCount
        WHERE
            key = :key
        SQL;

        $params = [
            'updatedAt' => $data->metadata->updatedAt,
            'accessCount' => $data->metadata->accessCount,
        ];

        try {
            $ps = $this->run( $sql, $params );
            return $ps->rowCount() > 0;
        } catch ( PDOException $e ) {
            throw new FlagException( 'Error while updating flag access', $e->getCode(), $e );
        }
    }
}
