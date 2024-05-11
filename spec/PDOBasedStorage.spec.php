<?php

use phputil\flags\FlagData;
use phputil\flags\FlagMetadata;
use phputil\flags\PDOBasedStorage;
use phputil\flags\SQLiteStorage;

describe( 'PDOBasedStorage', function() {

    $this->pdo = null;
    $this->storage = null;

    beforeAll( function() {
        $this->pdo = new PDO( 'sqlite:test.sqlite', null, null,
            [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] );

        $this->storage = new PDOBasedStorage( $this->pdo );
    } );

    beforeEach( function() {
        $this->storage->removeAll();
    } );

    describe( 'set()', function() {

        // it( 'can add a flag', function() {
        //     expect( $this->storage->count() )->toBe( 0 );
        //     $flag = new FlagData( 'foo', true, new FlagMetadata() );
        //     $this->storage->set( 'foo', $flag );
        //     expect( $this->storage->count() )->toEqual( 1 );
        // } );

    } );


} );