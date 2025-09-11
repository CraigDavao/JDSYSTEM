<?php
// include your DB connection
include '../connection/connection.php'; // adjust the path

if (isset($_GET['query'])) {
    $search = trim($_GET['query']);

    // simple SQL search (searches in product name & description)
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ?");
    $like = "%" . $search . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Search results for "<?php echo htmlspecialchars($search); ?>"</title>
    <style>
        .product {
            border: 1px solid #ddd;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <h1>Search results for "<?php echo htmlspecialchars($search); ?>"</h1>

    <?php if (!empty($result) && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="product">
                <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                <!-- <p><?php echo htmlspecialchars($row['description']); ?></p> -->
                <p>Price: $<?php echo htmlspecialchars($row['price']); ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No products found.</p>
    <?php endif; ?>
</body>
</html>
