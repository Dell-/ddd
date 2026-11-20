<?php

declare(strict_types=1);

$debug = filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL);

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');

require_once __DIR__ . '/../app/Bootstrap.php';

Bootstrap::createApplication()->run();
