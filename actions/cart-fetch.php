<?php
session_start();
require_once '../connection/connection.php';
header('Content-Type: application/json');

$response = ["status" => "error", "cart" => []];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT 
            c.id AS cart_id,
            c.product_id,
            p.name,
            p.price,
            p.sale_price,
            p.actual_sale_price,
            c.quantity,
            c.size,
            c.color_id,
            -- ✅ Use user's selected color name first, fallback to color table
            CASE 
                WHEN c.color_name IS NOT NULL AND c.color_name != '' 
                THEN c.color_name 
                ELSE pc.color_name 
            END AS color_name,
            
            -- ✅ Match the product image of the selected color
            pi.image,
            pi.image_format

        FROM cart c
        INNER JOIN products p ON c.product_id = p.id
        LEFT JOIN product_colors pc ON c.color_id = pc.id
        LEFT JOIN product_images pi 
            ON pi.product_id = p.id 
            AND (
                LOWER(pi.color_name) = LOWER(
                    CASE 
                        WHEN c.color_name IS NOT NULL AND c.color_name != '' 
                        THEN c.color_name 
                        ELSE pc.color_name 
                    END
                )
            )
        WHERE c.user_id = ?
        GROUP BY c.id
        ORDER BY c.id DESC
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart = [];

    while ($row = $result->fetch_assoc()) {
        // ✅ Convert image binary to base64 if exists
        if (!empty($row['image'])) {
            $mimeType = !empty($row['image_format']) ? $row['image_format'] : 'image/jpeg';
            $row['image'] = 'data:' . $mimeType . ';base64,' . base64_encode($row['image']);
        } else {
            // fallback image if no match
            $row['image'] = 'sample1.jpg';
        }

        // ✅ Determine display price (actual_sale_price > sale_price > regular price)
        $regularPrice = (float)$row['price'];
        $salePrice = (float)$row['sale_price'];
        $actualSale = (float)$row['actual_sale_price'];
        $displayPrice = $regularPrice;

        if ($actualSale > 0 && $actualSale < $regularPrice) {
            $displayPrice = $actualSale;
        } elseif ($salePrice > 0 && $salePrice < $regularPrice) {
            $displayPrice = $salePrice;
        }

        // ✅ Compute subtotal
        $subtotal = $displayPrice * (int)$row['quantity'];

        // ✅ Push to response array
        $cart[] = [
            "cart_id"    => $row["cart_id"],
            "product_id" => $row["product_id"],
            "name"       => $row["name"],
            "price"      => $displayPrice,
            "size"       => $row["size"],
            "color_id"   => $row["color_id"],
            "color"      => $row["color_name"],
            "quantity"   => (int)$row["quantity"],
            "subtotal"   => $subtotal,
            "image"      => $row["image"]
        ];
    }

    $response["status"] = "success";
    $response["cart"] = $cart;
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
