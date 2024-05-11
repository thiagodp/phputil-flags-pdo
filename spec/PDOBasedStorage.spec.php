<?php

use phputil\flags\FlagData;
use phputil\flags\FlagMetadata;
use phputil\flags\PDOBasedStorage;

describe( 'PDOBasedStorage', function() {

    $this->pdo = null;
    $this->storage = null;

    beforeAll( function() {
        // MySQL
        // $this->pdo = new PDO( 'mysql:dbname=test;host=localhost;charset=utf8', 'root', '',
        // SQLite
        $this->pdo = new PDO( 'sqlite:test.sqlite', null, null,
            [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] );

        $this->storage = new PDOBasedStorage( $this->pdo );
    } );

    beforeEach( function() {
        $this->storage->removeAll();
    } );

    describe( 'set()', function() {

        it( 'can add a flag', function() {
            expect( $this->storage->count() )->toBe( 0 );
            $flag = new FlagData( 'foo', true, new FlagMetadata() );
            $ok = $this->storage->set( 'foo', $flag );
            expect( $ok )->toBeTruthy();
            expect( $this->storage->count() )->toBe( 1 );
        } );

        it( 'can change a flag', function() {
            expect( $this->storage->count() )->toBe( 0 );
            $flag = new FlagData( 'foo', true, new FlagMetadata() );
            $ok = $this->storage->set( 'foo', $flag );
            expect( $ok )->toBeTruthy();
            expect( $this->storage->count() )->toBe( 1 );
            $oldAccessCount = $flag->metadata->accessCount;
            $flag->metadata->updateAccess();
            $ok = $this->storage->set( 'foo', $flag );
            expect( $ok )->toBeTruthy();
            expect( $flag->metadata->accessCount )->toBeGreaterThan( $oldAccessCount );
        } );

    } );

    describe( 'remove()', function() {

        it( 'can remove a flag', function() {
            expect( $this->storage->count() )->toBe( 0 );
            $flag = new FlagData( 'foo', true, new FlagMetadata() );
            $ok = $this->storage->set( 'foo', $flag );
            expect( $ok )->toBeTruthy();
            expect( $this->storage->count() )->toBe( 1 );
            $ok = $this->storage->remove( 'foo' );
            expect( $ok )->toBeTruthy();
            expect( $this->storage->count() )->toBe( 0 );
        } );

    } );


} );