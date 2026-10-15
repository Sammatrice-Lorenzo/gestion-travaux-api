<?php

declare(strict_types=1);

$envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

if (!is_file($envFile)) {
    fwrite(STDERR, ".env file not found.\n");
    exit(1);
}

$content = file_get_contents($envFile);
if (false === $content) {
    fwrite(STDERR, "Unable to read .env file.\n");
    exit(1);
}

$newContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=test', $content);

// Only write when needed: touching .env invalidates the debug container, which then gets rebuilt
// concurrently by the test server and the Codeception kernel (fails on Windows with locked files).
if ($newContent !== $content) {
    file_put_contents($envFile, $newContent);
}
