#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;
$cache = new Cache\Prefix(new Cache\Mock(), 'prefixtesting/');
include __DIR__ . '/generic_tests.php';