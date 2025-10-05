<?php
require '../connection/connection.php';

// Fetch all products
$sql = "SELECT id, name, price, sale_price, image, is_active FROM products ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f9f9f9;
            color: #444;
            line-height: 1.6;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 500;
            color: #2a2a2a;
            margin-bottom: 0.5rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #2a2a2a;
        }
        
        .price {
            font-weight: 500;
            color: #8b5a2b;
            margin-bottom: 0.5rem;
        }
        
        .sale-price {
            color: #c62828;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            margin-right: 0.5rem;
        }
        
        .status {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }
        
        .edit-btn {
            display: inline-block;
            background: #8b5a2b;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .edit-btn:hover {
            background: #6d4524;
        }
        
        .no-products {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Products Management - Admin</h1>
        <p>Edit product details and pricing</p>
    </div>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="products-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($row['image'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                        <?php else: ?>
                            <span>No Image</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($row['name']) ?></h3>
                        <div class="price">
                            <?php if (!empty($row['sale_price'])): ?>
                                <span class="original-price">₱<?= number_format($row['price'], 2) ?></span>
                                <span class="sale-price">₱<?= number_format($row['sale_price'], 2) ?></span>
                            <?php else: ?>
                                ₱<?= number_format($row['price'], 2) ?>
                            <?php endif; ?>
                        </div>
                        <div class="status <?= $row['is_active'] ? 'status-active' : 'status-inactive' ?>">
                            <?= $row['is_active'] ? 'Active' : 'Inactive' ?>
                        </div>
                        <a href="edit-product.php?id=<?= $row['id'] ?>" class="edit-btn">
                            <i class="fas fa-edit"></i> Edit Product
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-products">No products found.</div>
    <?php endif; ?>
</body>
</html>