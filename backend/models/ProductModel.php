<?php
require_once __DIR__ . '/../config/db.php';

class ProductModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getNewArrivals($limit = 4) {
        $sql = "
            SELECT p.*, COALESCE(TO_BASE64(pi.image), NULL) AS product_image
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE p.is_active = 1
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductsByCategory($category, $limit = 4) {
        $sql = "
            SELECT p.*, COALESCE(TO_BASE64(pi.image), NULL) AS product_image
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE p.category_group = ? AND p.is_active = 1
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $category, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategoryImage($category_group) {
        $sql = "
            SELECT TO_BASE64(pi.image) AS img
            FROM products p
            JOIN product_images pi ON p.id = pi.product_id
            WHERE p.category_group = ? AND p.is_active = 1
            ORDER BY RAND() LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $category_group);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? 'data:image/jpeg;base64,' . $result['img'] : null;
    }
}
