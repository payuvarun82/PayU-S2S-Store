<?php
// Router for PHP built-in server: php -S localhost:8080 router.php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
  return false;
}

if (str_starts_with($uri, '/api/')) {
  return false;
}

if ($uri === '/callback.php') {
  return false;
}

readfile(__DIR__ . '/public/index.html');
return true;
