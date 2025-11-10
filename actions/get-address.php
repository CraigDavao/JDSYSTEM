<?php
// actions/get-address.php (FIXED VERSION)
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

$address_id = (int)($data['address_id'] ?? 0);

if ($address_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid address ID']);
    exit;
}

try {
    if (!$conn || !$conn->ping()) {
        throw new Exception('Database connection failed');
    }

    // REMOVED: The transaction that sets address as default
    // This function should ONLY get address details, not modify them
    
    // Check if fullname column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM addresses LIKE 'fullname'");
    $hasFullname = $checkColumn->num_rows > 0;
    
    if ($hasFullname) {
        $sql = "SELECT id, fullname, street, city, state, zip_code, country, is_default FROM addresses WHERE id = ? AND user_id = ? LIMIT 1";
    } else {
        $sql = "SELECT id, street, city, state, zip_code, country, is_default FROM addresses WHERE id = ? AND user_id = ? LIMIT 1";
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare select statement');
    }
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc();
    $stmt->close();

    if ($address) {
        echo json_encode(['status' => 'success', 'address' => $address]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Address not found']);
    }

} catch (Exception $e) {
    error_log("get-address.php error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Unable to fetch address']);
}
?>