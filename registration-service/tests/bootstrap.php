<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Fixed, obviously fake keys for tests, built at runtime so no key-like
// string is committed.
foreach (['REGISTRY_FIELD_KEY' => 'field', 'REGISTRY_INDEX_KEY' => 'index'] as $name => $purpose) {
    if (!isset($_SERVER[$name]) && !isset($_ENV[$name])) {
        $_SERVER[$name] = $_ENV[$name] = base64_encode(str_pad('test-only-'.$purpose.'-key', 32, '-'));
    }
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
