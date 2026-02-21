<?php
require_once __DIR__ . '/../config/config.php';

$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$sql = "SELECT m.*, c.name as category_name, c.icon as category_icon
        FROM menu_items m
        JOIN menu_categories c ON m.category_id=c.id
        WHERE m.is_deleted=0 AND m.is_available=1";
$params = [];
if ($categoryId) { $sql .= " AND m.category_id=?"; $params[] = $categoryId; }
$sql .= " ORDER BY c.sort_order, m.sort_order, m.name";

$items = db()->fetchAll($sql, $params);
$categories = db()->fetchAll("SELECT * FROM menu_categories WHERE is_active=1 ORDER BY sort_order");

foreach ($items as &$item) {
    $item['image_url'] = $item['image'] && file_exists(UPLOAD_PATH.$item['image'])
        ? UPLOAD_URL.$item['image']
        : null;
}

jsonSuccess(['items' => $items, 'categories' => $categories]);
