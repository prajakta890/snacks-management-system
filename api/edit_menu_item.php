<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$itemId = (int)($_POST['item_id'] ?? 0);
if (!$itemId) { jsonError('Item ID required'); }

$item = db()->fetchOne("SELECT * FROM menu_items WHERE id=? AND is_deleted=0", [$itemId]);
if (!$item) { jsonError('Item not found'); }

// Toggle availability only
if (isset($_POST['toggle_availability'])) {
    $newAvail = (int)$_POST['is_available'];
    db()->execute("UPDATE menu_items SET is_available=? WHERE id=?", [$newAvail, $itemId]);
    jsonSuccess([], $newAvail ? 'Item marked as available' : 'Item marked as unavailable');
}

$categoryId = (int)($_POST['category_id'] ?? $item['category_id']);
$name = sanitize($_POST['name'] ?? $item['name']);
$description = sanitize($_POST['description'] ?? $item['description']);
$price = floatval($_POST['price'] ?? $item['price']);
$isVeg = (int)($_POST['is_veg'] ?? $item['is_veg']);
$isAvailable = isset($_POST['is_available']) ? 1 : 0;

if (!$name || $price <= 0) { jsonError('Name and price required'); }

// Handle image upload
$imageName = $item['image'];
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) { jsonError('Invalid image type'); }
    if ($_FILES['image']['size'] > 2*1024*1024) { jsonError('Image too large. Max 2MB'); }

    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $newName = uniqid('item_') . '.' . strtolower($ext);
    if (!is_dir(UPLOAD_PATH)) { mkdir(UPLOAD_PATH, 0755, true); }
    if (move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH.$newName)) {
        if ($imageName && file_exists(UPLOAD_PATH.$imageName)) @unlink(UPLOAD_PATH.$imageName);
        $imageName = $newName;
    }
}

db()->execute("UPDATE menu_items SET category_id=?,name=?,description=?,price=?,image=?,is_veg=?,is_available=? WHERE id=?",
    [$categoryId, $name, $description, $price, $imageName, $isVeg, $isAvailable, $itemId]);

jsonSuccess([], 'Menu item updated successfully');
