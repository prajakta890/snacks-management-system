<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$categoryId = (int)($_POST['category_id'] ?? 0);
$name = sanitize($_POST['name'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$isVeg = (int)($_POST['is_veg'] ?? 1);
$isAvailable = isset($_POST['is_available']) ? 1 : 0;

if (!$name || !$categoryId || $price <= 0) { jsonError('Name, category and price are required'); }

// Handle image upload
$imageName = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) { jsonError('Invalid image type. Only JPG, PNG, GIF, WEBP allowed'); }
    if ($_FILES['image']['size'] > 2 * 1024 * 1024) { jsonError('Image too large. Max 2MB'); }

    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $imageName = uniqid('item_') . '.' . strtolower($ext);
    if (!is_dir(UPLOAD_PATH)) { mkdir(UPLOAD_PATH, 0755, true); }
    if (!move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . $imageName)) {
        jsonError('Failed to upload image');
    }
}

$id = db()->insert("INSERT INTO menu_items (category_id,name,description,price,image,is_veg,is_available) VALUES(?,?,?,?,?,?,?)",
    [$categoryId, $name, $description, $price, $imageName, $isVeg, $isAvailable]);

jsonSuccess(['id' => $id], 'Menu item added successfully');
