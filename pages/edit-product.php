<?php
// edit-product.php
require_once __DIR__ . '/../connection/connection.php';  

// 1. Fetch product by ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if (!$product) {
        die("Product not found!");
    }
} else {
    die("Product ID is missing!");
}

// 2. Update product when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = $_POST['name'];
    $price       = $_POST['price'];
    $sale_price  = $_POST['sale_price'] !== '' ? $_POST['sale_price'] : null;
    $description = $_POST['description'];
    $category    = $_POST['category'];
    $is_sale     = isset($_POST['is_sale']) ? 1 : 0;
    $is_new      = isset($_POST['is_new']) ? 1 : 0;

    // If sale_price is set, automatically mark it as sale
    if ($sale_price !== null && $sale_price !== '') {
        $is_sale = 1;
    }

    // Image upload
    if (!empty($_FILES['image']['name'])) {
        $image = 'uploads/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    } else {
        $image = $product['image']; // keep old image if not updated
    }

    $stmt = $conn->prepare("UPDATE products 
        SET name=?, price=?, sale_price=?, description=?, category=?, image=?, is_sale=?, is_new=?
        WHERE id=?");
    $stmt->bind_param("sdsssiiii", $name, $price, $sale_price, $description, $category, $image, $is_sale, $is_new, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Product updated successfully'); window.location='products.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
</head>
<body>
    <h1>Edit Product</h1>
    <form method="POST" enctype="multipart/form-data">
        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required><br><br>

        <label>Price:</label><br>
        <input type="number" name="price" value="<?= $product['price'] ?>" required step="0.01"><br><br>

        <label>Sale Price (optional):</label><br>
        <input type="number" name="sale_price" value="<?= $product['sale_price'] ?>" step="0.01"><br><br>

        <label>Description:</label><br>
        <textarea name="description" required><?= htmlspecialchars($product['description']) ?></textarea><br><br>

        <label>Category:</label><br>
        <input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>" required><br><br>

        <label>Image:</label><br>
        <input type="file" name="image"><br>
        <img src="<?= $product['image'] ?>" alt="Current Image" width="100"><br><br>

        <label><input type="checkbox" name="is_sale" <?= $product['is_sale'] ? 'checked' : '' ?>> Mark as Sale</label><br>
        <label><input type="checkbox" name="is_new" <?= $product['is_new'] ? 'checked' : '' ?>> Mark as New</label><br><br>

        <button type="submit">Update Product</button>
    </form>
</body>
</html>
