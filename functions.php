<?php
require_once __DIR__ . '/connection/connection.php';

function getCategories() {
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);

    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}
?>
