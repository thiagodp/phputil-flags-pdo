<?php
namespace phputil\flags\pdo;

require_once 'vendor/autoload.php';

use PDO;
use phputil\flags\FlagStorage;
use phputil\flags\FlagException;
use phputil\flags\FlagData;

const DEFAULT_TABLE_NAME = 'puf_flag'; // puf = phputilflag

class PDOBasedStorage implements FlagStorage {

    private AbstractPDOBasedStorage $storage;

    public function __construct( PDO $pdo, ?string $flagTableName = null ) {

        $tableName = isset( $flagTableName ) && ! empty( $flagTableName )
            ? $flagTableName : DEFAULT_TABLE_NAME;

        $driverName = $pdo->getAttribute( PDO::ATTR_DRIVER_NAME );

        switch ( $driverName ) { // TODO: change to match() when using PHP 8
            case 'mysql': $this->storage = new MySQLStorage( $pdo, $tableName );
                break;
            case 'sqlite': $this->storage = new SQLiteStorage( $pdo, $tableName );
                break;
            default: throw new FlagException( 'Unsupported database storage: ' . $driverName );
        }

        $this->storage->setup();
    }

    //
    // FlagStorage
    //

    /** @inheritDoc */
    public function isEnabled( string $key ): bool {
        return $this->storage->isEnabled( $key );
    }

    /** @inheritDoc */
    public function get( string $key ): ?FlagData {
        return $this->storage->get( $key );
    }

    /** @inheritDoc */
    public function touch( string $key, ?bool $enabled = null ): ?FlagData {
        return $this->storage->touch( $key, $enabled );
    }

    /** @inheritDoc */
    public function set( string $key, FlagData $data ): bool {
        return $this->storage->set( $key, $data );
    }

    /** @inheritDoc */
    public function remove( string $key ): bool {
        return $this->storage->remove( $key );
    }

    /** @inheritDoc */
    public function removeAll(): void {
        $this->storage->removeAll();
    }

    /** @inheritDoc */
    public function getAll( array $options = [] ): array {
        return $this->storage->getAll( $options );
    }

    /** @inheritDoc */
    public function count( array $options = [] ): int {
        return $this->storage->count( $options );
    }
}
