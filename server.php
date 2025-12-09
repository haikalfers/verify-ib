<?php

use Symfony\Component\HttpFoundation\Request;

if (php_sapi_name() === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

    $publicPath = __DIR__.'/public';
    $file = realpath($publicPath.$uri);

    if ($file !== false && is_file($file)) {
        return false;
    }
}

require __DIR__.'/public/index.php';