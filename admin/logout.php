<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
