#!/usr/bin/php
<?php

use App\Command\UploadUser;

require __DIR__ . '/vendor/autoload.php';

// execute it
$cli = new UploadUser();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $cli->run();
} catch (\Exception $e) {
    $cli->error($e->getMessage());
}
