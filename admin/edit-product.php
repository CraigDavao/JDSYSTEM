<?php 
require '../connection/connection.php';

$errors = [];
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 && isset($_POST['product_id'])) {
    $id = (int)$_POST['product_id'];
}

if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die('<div class="error-message">Product not found.</div>');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $price = (float)$_POST['price'];
    $sale_price = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
    $sale_start = !empty($_POST['sale_start']) ? $_POST['sale_start'] : null;
    $sale_end   = !empty($_POST['sale_end'])   ? $_POST['sale_end']   : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($sale_price !== null && $sale_price >= $price) {
        $errors[] = "Sale price must be less than regular price.";
    }

    // If sale has expired, clear it
    if ($sale_end && strtotime($sale_end) < time()) {
        $sale_price = null;
        $sale_start = null;
        $sale_end   = null;
    }

    if (empty($errors)) {
        $sql = "UPDATE products 
                SET price = ?, sale_price = ?, sale_start = ?, sale_end = ?, is_active = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsssii", $price, $sale_price, $sale_start, $sale_end, $is_active, $id);

        if ($stmt->execute()) {
            $success = "Product updated successfully.";
            $product['price'] = $price;
            $product['sale_price'] = $sale_price;
            $product['sale_start'] = $sale_start;
            $product['sale_end'] = $sale_end;
            $product['is_active'] = $is_active;
        } else {
            $errors[] = "Failed to update: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - <?= htmlspecialchars($product['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
            margin: 2rem auto;
        }
        
        .header {
            background: linear-gradient(135deg, #8b5a2b 0%, #6d4524 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .back-button {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: transform 0.3s;
        }
        
        .back-button:hover {
            transform: translateX(-5px);
        }
        
        .product-info {
            padding: 2rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            overflow: hidden;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-details h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #2a2a2a;
        }
        
        .product-id {
            color: #8b5a2b;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .price-display {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .regular-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2a2a2a;
        }
        
        .sale-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #d32f2f;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 1rem;
        }
        
        .sale-dates {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .sale-dates span {
            display: block;
            margin-bottom: 0.2rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .badge-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-inactive {
            background: #ffebee;
            color: #c62828;
        }
        
        .badge-sale {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .form-container {
            padding: 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .form-group {
            margin-bottom: 1.8rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #2a2a2a;
            font-size: 1rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group span {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8b5a2b;
            font-weight: 500;
            z-index: 1;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 2.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .datetime-control {
            padding-left: 1rem;
        }
        
        .form-control:focus {
            border-color: #8b5a2b;
            background: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 90, 43, 0.1);
        }
        
        .date-time-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-top: 1.5rem;
        }
        
        .checkbox-group input {
            width: 20px;
            height: 20px;
            accent-color: #8b5a2b;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8b5a2b 0%, #6d4524 100%);
            color: white;
            flex: 1;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 90, 43, 0.3);
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e8e8e8;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                border-radius: 12px;
            }
            
            .header {
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 1.8rem;
                padding-right: 2rem;
            }
            
            .product-info {
                flex-direction: column;
                text-align: center;
            }
            
            .date-time-group {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="products.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Edit Product</h1>
            <p>Update product details, pricing, and sale periods</p>
        </div>
        
        <div class="product-info">
            <div class="product-image">
                <?php if (!empty($product['image'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php else: ?>
                    <i class="fas fa-image" style="color: #ccc; font-size: 2rem;"></i>
                <?php endif; ?>
            </div>
            <div class="product-details">
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <p class="product-id">Product ID: <?= $product['id'] ?></p>
                <div class="price-display">
                    <?php if (!empty($product['sale_price'])): ?>
                        <span class="sale-price">₱<?= number_format($product['sale_price'], 2) ?></span>
                        <span class="original-price">₱<?= number_format($product['price'], 2) ?></span>
                    <?php else: ?>
                        <span class="regular-price">₱<?= number_format($product['price'], 2) ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['sale_start']) && !empty($product['sale_end'])): ?>
                    <div class="sale-dates">
                        <span><i class="fas fa-play-circle"></i> <?= date('M j, Y g:i A', strtotime($product['sale_start'])) ?></span>
                        <span><i class="fas fa-stop-circle"></i> <?= date('M j, Y g:i A', strtotime($product['sale_end'])) ?></span>
                    </div>
                <?php endif; ?>
                
                <span class="badge <?= $product['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                    <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
                
                <?php if (!empty($product['sale_price'])): ?>
                    <span class="badge badge-sale">On Sale</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= implode('<br>', $errors) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <div class="form-group">
                    <label for="price">Regular Price</label>
                    <div class="input-group">
                        <span>₱</span>
                        <input type="number" id="price" name="price" step="0.01" min="0" 
                               value="<?= htmlspecialchars($product['price']) ?>" 
                               class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="sale_price">Sale Price (optional)</label>
                    <div class="input-group">
                        <span>₱</span>
                        <input type="number" id="sale_price" name="sale_price" step="0.01" min="0" 
                               value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>" 
                               class="form-control" placeholder="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Sale Period (optional)</label>
                    <div class="date-time-group">
                        <div>
                            <label for="sale_start">Start Date & Time</label>
                            <input type="datetime-local" id="sale_start" name="sale_start" 
                                   value="<?= isset($product['sale_start']) && $product['sale_start'] ? date('Y-m-d\TH:i', strtotime($product['sale_start'])) : '' ?>" 
                                   class="form-control datetime-control">
                        </div>
                        <div>
                            <label for="sale_end">End Date & Time</label>
                            <input type="datetime-local" id="sale_end" name="sale_end" 
                                   value="<?= isset($product['sale_end']) && $product['sale_end'] ? date('Y-m-d\TH:i', strtotime($product['sale_end'])) : '' ?>" 
                                   class="form-control datetime-control">
                        </div>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" value="1" 
                           <?= $product['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active">Product is active and visible to customers</label>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add real-time validation for sale price
        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.getElementById('price');
            const salePriceInput = document.getElementById('sale_price');
            const saleStartInput = document.getElementById('sale_start');
            const saleEndInput = document.getElementById('sale_end');
            
            function validatePrices() {
                if (salePriceInput.value && parseFloat(salePriceInput.value) >= parseFloat(priceInput.value)) {
                    salePriceInput.style.borderColor = '#c62828';
                    salePriceInput.style.backgroundColor = '#ffebee';
                } else {
                    salePriceInput.style.borderColor = '#e0e0e0';
                    salePriceInput.style.backgroundColor = '#fafafa';
                }
            }
            
            function validateDates() {
                if (saleStartInput.value && saleEndInput.value && saleStartInput.value >= saleEndInput.value) {
                    saleEndInput.style.borderColor = '#c62828';
                    saleEndInput.style.backgroundColor = '#ffebee';
                } else {
                    saleEndInput.style.borderColor = '#e0e0e0';
                    saleEndInput.style.backgroundColor = '#fafafa';
                }
            }
            
            priceInput.addEventListener('input', validatePrices);
            salePriceInput.addEventListener('input', validatePrices);
            saleStartInput.addEventListener('change', validateDates);
            saleEndInput.addEventListener('change', validateDates);
            
            // Set minimum datetime for sale end to current time
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16);
            saleStartInput.min = localISOTime;
            saleEndInput.min = localISOTime;
        });
    </script>
</body>
</html>