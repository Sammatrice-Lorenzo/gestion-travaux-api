<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->load(dirname(__DIR__) . '/.env');

if (file_exists(dirname(__DIR__) . '/.env.test.local')) {
    (new Dotenv())->load(dirname(__DIR__) . '/.env.test.local');
} elseif (file_exists(dirname(__DIR__) . '/.env.test')) {
    (new Dotenv())->load(dirname(__DIR__) . '/.env.test');
}
