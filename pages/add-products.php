<?php
session_start();
require_once "../connection/connection.php";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];

    // Handle image upload
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        $image = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $image;
        move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile);
    }

    $stmt = $conn->prepare("INSERT INTO products (name, category, price, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $name, $category, $price, $image);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Product added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Product</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    form { max-width: 400px; margin: auto; display: flex; flex-direction: column; gap: 15px; }
    input, select { padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    button { padding: 10px; background: #111; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background: #444; }
  </style>
</head>
<body>
  <h2>Add New Product</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Product Name" required>
    
    <select name="category" required>
      <option value="">-- Select Category --</option>
      <option value="boys">Boys</option>
      <option value="girls">Girls</option>
      <option value="baby-boys">Baby Boys</option>
      <option value="baby-girls">Baby Girls</option>
      <option value="newborn">Newborn</option>
    </select>

    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <input type="file" name="image" accept="image/*">

    <button type="submit">Add Product</button>
  </form>
</body>
</html>
