<?php
require_once __DIR__ . '/config/auth.php';

logoutUser();
header('Location: /dragstore-pos/login.php');
exit;
