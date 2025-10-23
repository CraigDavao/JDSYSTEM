<?php
require_once __DIR__ . '/../connection/connection.php';

/**
 * Get products (with first image as Base64)
 * 
 * @param array $filters [
 *   'category' => (string),      // Maps to gender field
 *   'category_group' => (string),
 *   'gender' => (string),
 *   'subcategory' => (string),
 *   'limit' => (int),
 *   'offset' => (int),
 *   'orderBy' => (string)
 * ]
 * 
 * @return array
 */
function getProducts($filters = [])
{
    global $conn;

    $perPage = $filters['limit'] ?? 24;
    $offset  = $filters['offset'] ?? 0;
    $orderBy = $filters['orderBy'] ?? 'p.created_at DESC';

    $params = [];
    $types  = '';
    $where  = "WHERE p.is_active = 1";

    // 🟣 Optional filters
    if (!empty($filters['category'])) {
        $where .= " AND p.category = ?";  // Changed from p.gender to p.category
        $params[] = $filters['category'];
        $types .= 's';
    }
    if (!empty($filters['category_group'])) {
        $where .= " AND p.category_group = ?";
        $params[] = $filters['category_group'];
        $types .= 's';
    }
    if (!empty($filters['gender'])) {
        $where .= " AND p.gender = ?";
        $params[] = $filters['gender'];
        $types .= 's';
    }
    if (!empty($filters['subcategory'])) {
        $where .= " AND p.subcategory = ?";
        $params[] = $filters['subcategory'];
        $types .= 's';
    }

    // 🟡 Query: Join product + first image (Base64)
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.price,
            p.sale_price,
            p.actual_sale_price,
            p.category,
            p.category_group,
            p.gender,
            p.subcategory,
            p.description,
            p.created_at,
            CONCAT('data:image/', 
                   COALESCE(pi.image_format, 'jpeg'),
                   ';base64,',
                   TO_BASE64(pi.image)) AS product_image
        FROM products AS p
        LEFT JOIN product_images AS pi 
            ON pi.product_id = p.id 
            AND pi.id = (
                SELECT MIN(pi2.id)
                FROM product_images AS pi2
                WHERE pi2.product_id = p.id
            )
        $where
        ORDER BY $orderBy
        LIMIT ?, ?
    ";

    $params[] = $offset;
    $params[] = $perPage;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result();

    // 🟣 Count total rows (for pagination)
    $countSql = "SELECT COUNT(*) AS c FROM products AS p $where";
    $countStmt = $conn->prepare($countSql);
    if ($types !== 'ii') {
        $bindTypes = str_replace('ii', '', $types);
        $bindParams = array_slice($params, 0, strlen($bindTypes));
        if (!empty($bindParams)) {
            $countStmt->bind_param($bindTypes, ...$bindParams);
        }
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $count = $countResult->fetch_assoc()['c'] ?? 0;

    return [
        'products' => $products,
        'count' => $count
    ];
}

/**
 * Get sale products (with first image as Base64)
 * 
 * @param array $filters [
 *   'limit' => (int),
 *   'offset' => (int),
 *   'orderBy' => (string)
 * ]
 * 
 * @return array
 */
function getSaleProducts($filters = [])
{
    global $conn;

    $perPage = $filters['limit'] ?? 24;
    $offset  = $filters['offset'] ?? 0;
    $orderBy = $filters['orderBy'] ?? 'p.created_at DESC';

    $params = [];
    $types  = '';
    
    // 🟣 Base where clause for sale items
    $where = "WHERE p.is_active = 1 
              AND p.sale_price IS NOT NULL 
              AND p.sale_price > 0 
              AND p.sale_price < p.price
              AND (p.sale_start IS NULL OR p.sale_start <= NOW())
              AND (p.sale_end IS NULL OR p.sale_end >= NOW())";

    // 🟡 Query: Join product + first image (Base64)
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.price,
            p.sale_price,
            p.actual_sale_price,
            p.category,
            p.category_group,
            p.gender,
            p.subcategory,
            p.description,
            p.created_at,
            CONCAT('data:image/', 
                   COALESCE(pi.image_format, 'jpeg'),
                   ';base64,',
                   TO_BASE64(pi.image)) AS product_image
        FROM products AS p
        LEFT JOIN product_images AS pi 
            ON pi.product_id = p.id 
            AND pi.id = (
                SELECT MIN(pi2.id)
                FROM product_images AS pi2
                WHERE pi2.product_id = p.id
            )
        $where
        ORDER BY $orderBy
        LIMIT ?, ?
    ";

    $params[] = $offset;
    $params[] = $perPage;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result();

    // 🟣 Count total sale products
    $countSql = "SELECT COUNT(*) AS c FROM products AS p $where";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $count = $countResult->fetch_assoc()['c'] ?? 0;

    return [
        'products' => $products,
        'count' => $count
    ];
}
?>