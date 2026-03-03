<?php
/**
 * Shared bootstrap for CLI tools.
 *
 * Loads Composer autoloader and .env file from the project root.
 * Environment variables already set in the shell take precedence.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad(); // no exception if .env is missing

// Make .env values available via getenv() for tools that use it
foreach ($_ENV as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
    }
}
