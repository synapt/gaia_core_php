#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;

Tap::plan(1);

$cache = new Store\Gate(new Store\KvpTTL());
$cache->set('test1', 1, 10000000);

$noerrs = TRUE;
set_error_handler( function ( $errno, $errstr) use( & $noerrs ) { $noerrs = FALSE; } );
$v = $cache->get('test1');
restore_error_handler();

// need to run on 32 bit os to verify
Tap::ok( $noerrs, 'no warnings generated by huge number blowup in mt_rand');
