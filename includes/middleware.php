<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth($conn);

function requireLogin() {
    global $auth;
    $auth->requireLogin();
}

function requireAdmin() {
    global $auth;
    $auth->requireAdmin();
}