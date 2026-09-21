<?php

// Development server router; never deployed under public/.
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri !== '/' && is_file(__DIR__.'/../public'.$uri)) {
    return false;
}
require __DIR__.'/../public/index.php';
