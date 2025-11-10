<?php
// actions/save-address.php
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

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

if (empty($data['street']) || empty($data['city']) || empty($data['zip_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: Street, City, and ZIP Code are required']);
    exit;
}

$fullname = trim($data['fullname'] ?? '');
$street = trim($data['street']);
$city = trim($data['city']);
$state = trim($data['state'] ?? '');
$zip_code = trim($data['zip_code']);
$country = trim($data['country'] ?? 'Philippines');
$is_default = (int)($data['set_as_default'] ?? 0);
$type = 'shipping';

try {
    if (!$conn || !$conn->ping()) {
        throw new Exception('Database connection failed');
    }

    // Only unset other defaults if this address is being set as default
    if ($is_default == 1) {
        $unsetStmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        if ($unsetStmt) {
            $unsetStmt->bind_param("i", $user_id);
            $unsetStmt->execute();
            $unsetStmt->close();
        }
    }

    // Check if fullname column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM addresses LIKE 'fullname'");
    $hasFullname = $checkColumn->num_rows > 0;
    
    if ($hasFullname && !empty($fullname)) {
        // Insert with fullname
        $insertSql = "INSERT INTO addresses (user_id, type, fullname, street, city, state, zip_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("isssssssi", $user_id, $type, $fullname, $street, $city, $state, $zip_code, $country, $is_default);
    } else {
        // Insert without fullname
        $insertSql = "INSERT INTO addresses (user_id, type, street, city, state, zip_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("issssssi", $user_id, $type, $street, $city, $state, $zip_code, $country, $is_default);
    }
    
    if (!$insertStmt) {
        throw new Exception('Failed to prepare insert statement: ' . $conn->error);
    }
    
    if ($insertStmt->execute()) {
        $new_address_id = $conn->insert_id;
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Address saved successfully', 
            'address_id' => $new_address_id,
            'is_default' => $is_default
        ]);
    } else {
        throw new Exception('Failed to execute insert: ' . $insertStmt->error);
    }
    
    $insertStmt->close();

} catch (Exception $e) {
    error_log("save-address.php error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Unable to save address']);
}
?>