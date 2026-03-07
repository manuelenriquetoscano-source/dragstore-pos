<?php

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/logger.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/error_handler.php';

$appTimezone = env('APP_TIMEZONE', 'America/Argentina/Buenos_Aires');
if (!@date_default_timezone_set($appTimezone)) {
    date_default_timezone_set('America/Argentina/Buenos_Aires');
}

initRequestContext();
