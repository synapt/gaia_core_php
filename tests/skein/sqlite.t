#!/usr/bin/env php
<?php
use Gaia\Skein;
use Gaia\Container;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_sqlite_installed.php';

$thread = bcsub( time(), 1000000000 ) . str_pad( mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    
try {
    $db = new Gaia\DB( new PDO( 'sqlite::memory:') );
} catch( \Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}

$cache = new Container;

$callback = function( & $table ) use ( $db, $cache ){
    $table = 'skeintest' . substr($table, 1);
    if( ! $cache->add( $table, 1, 60 ) ) return $db;
    $sql = (substr($table, -5) == 'index') ? 
    Skein\SQLite::indexSchema( $table ) : Skein\SQLite::dataSchema( $table );
    $db->execute( $sql );
    return $db;
};



$skein = new Skein\SQLite( $callback, $thread );

include __DIR__ . '/.basic_test_suite.php';


//Tap::debug( $store->all() );