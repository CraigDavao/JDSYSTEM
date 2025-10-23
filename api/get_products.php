<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../connection/connection.php';

try {
    // ✅ 1) Get products + stock + category info
    $sqlProducts = "
        SELECT 
            p.id AS product_id,
            p.name AS product_name,
            p.category,
            p.category_group,
            p.gender,
            p.subcategory,
            COALESCE(SUM(pc.quantity), 0) AS total_stock
        FROM products p
        LEFT JOIN product_colors pc ON p.id = pc.product_id
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY p.id ASC
    ";

    $resProducts = $conn->query($sqlProducts);

    $products = [];
    $productIds = [];

    while ($row = $resProducts->fetch_assoc()) {
        $pid = (int)$row['product_id'];
        $productIds[] = $pid;

        $products[$pid] = [
            'product_id'     => $pid,
            'product_name'   => $row['product_name'],
            'category'       => $row['category'],
            'category_group' => $row['category_group'],
            'gender'         => $row['gender'],
            'subcategory'    => $row['subcategory'],
            'images'         => [],
            'total_stock'    => (int)$row['total_stock']
        ];
    }

    // ✅ 2) Get images per product
    if (!empty($productIds)) {
        $idsEscaped = implode(',', array_map('intval', $productIds));
        $sqlImages = "
            SELECT product_id, image
            FROM product_images
            WHERE product_id IN ($idsEscaped)
            ORDER BY product_id ASC, sort_order ASC, id ASC
        ";

        $resImages = $conn->query($sqlImages);

        while ($imgRow = $resImages->fetch_assoc()) {
            $pid = (int)$imgRow['product_id'];
            if (!isset($products[$pid])) continue;

            if ($imgRow['image']) {
                $base64 = 'data:image/jpeg;base64,' . base64_encode($imgRow['image']);
                if (!in_array($base64, $products[$pid]['images'], true)) {
                    $products[$pid]['images'][] = $base64;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'products' => array_values($products)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
