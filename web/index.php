<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Bootstrap.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

Bootstrap::createApplication()->run();
