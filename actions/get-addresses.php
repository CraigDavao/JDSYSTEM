<?php
// actions/get-addresses.php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$connectionFile = __DIR__ . '/../connection/connection.php';
if (!file_exists($connectionFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Connection file not found']);
    exit;
}

require_once $connectionFile;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$addresses = [];

try {
    if (!$conn || !$conn->ping()) {
        throw new Exception('Database connection failed');
    }

    // Check if fullname column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM addresses LIKE 'fullname'");
    $hasFullname = $checkColumn->num_rows > 0;
    
    if ($hasFullname) {
        $sql = "SELECT id, fullname, street, city, state, zip_code, country, is_default, type FROM addresses WHERE user_id = ? AND type = 'shipping' ORDER BY is_default DESC, id ASC";
    } else {
        $sql = "SELECT id, street, city, state, zip_code, country, is_default, type FROM addresses WHERE user_id = ? AND type = 'shipping' ORDER BY is_default DESC, id ASC";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('Failed to get result: ' . $stmt->error);
    }

    while ($row = $result->fetch_assoc()) {
        $address = [
            'id' => (int)$row['id'],
            'fullname' => $row['fullname'] ?? '',
            'street' => $row['street'] ?? '',
            'city' => $row['city'] ?? '',
            'state' => $row['state'] ?? '',
            'zip_code' => $row['zip_code'] ?? '',
            'country' => $row['country'] ?? 'Philippines',
            'is_default' => (int)($row['is_default'] ?? 0),
            'type' => $row['type'] ?? 'shipping'
        ];
        
        $addresses[] = $address;
    }
    
    $stmt->close();
    
    echo json_encode([
        'status' => 'success', 
        'addresses' => $addresses,
        'count' => count($addresses)
    ]);

} catch (Exception $e) {
    error_log("get-addresses.php error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Unable to load addresses'
    ]);
}
?>