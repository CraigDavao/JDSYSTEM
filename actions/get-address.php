<?php
// actions/get-address.php (COMPLETE WORKING VERSION - ALL FUNCTIONALITY PRESERVED)
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

// Support BOTH JSON input (for checkout) and POST form data (for dashboard editing)
$address_id = 0;
$is_json_input = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First check for JSON input (for checkout page)
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['address_id'])) {
            $address_id = (int)($data['address_id'] ?? 0);
            $is_json_input = true;
        }
    }
    
    // If no JSON input or invalid JSON, check for POST form data (for dashboard editing)
    if ($address_id <= 0 && isset($_POST['address_id'])) {
        $address_id = (int)$_POST['address_id'];
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if ($address_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid address ID']);
    exit;
}

try {
    if (!$conn || !$conn->ping()) {
        throw new Exception('Database connection failed');
    }

    // Check if this is a checkout request that needs to set default address
    $is_checkout_request = false;
    if ($is_json_input) {
        $is_checkout_request = isset($data['set_as_default']) && $data['set_as_default'] === true;
    }
    
    if ($is_checkout_request) {
        // For checkout: Set this address as default and return it
        $conn->begin_transaction();
        
        try {
            // Get the address type first
            $type_sql = "SELECT type FROM addresses WHERE id = ? AND user_id = ?";
            $type_stmt = $conn->prepare($type_sql);
            $type_stmt->bind_param("ii", $address_id, $user_id);
            $type_stmt->execute();
            $type_result = $type_stmt->get_result();
            $address_type = $type_result->fetch_assoc()['type'] ?? 'shipping';
            $type_stmt->close();
            
            // Set all addresses of this type to not default
            $reset_sql = "UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?";
            $reset_stmt = $conn->prepare($reset_sql);
            $reset_stmt->bind_param("is", $user_id, $address_type);
            $reset_stmt->execute();
            $reset_stmt->close();
            
            // Set the selected address as default
            $update_sql = "UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $address_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    // Check if fullname column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM addresses LIKE 'fullname'");
    $hasFullname = $checkColumn->num_rows > 0;
    
    if ($hasFullname) {
        $sql = "SELECT id, fullname, type, street, city, state, zip_code, country, is_default FROM addresses WHERE id = ? AND user_id = ? LIMIT 1";
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
        // For dashboard editing: Return success format that JavaScript expects
        if (!$is_json_input) {
            echo json_encode([
                'success' => true, 
                'address' => $address
            ]);
        } else {
            // For checkout: Return status format
            echo json_encode([
                'status' => 'success', 
                'address' => $address
            ]);
        }
    } else {
        if (!$is_json_input) {
            echo json_encode([
                'success' => false, 
                'message' => 'Address not found'
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Address not found'
            ]);
        }
    }

} catch (Exception $e) {
    error_log("get-address.php error: " . $e->getMessage());
    
    if (!$is_json_input) {
        echo json_encode([
            'success' => false, 
            'message' => 'Unable to fetch address: ' . $e->getMessage()
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Unable to fetch address: ' . $e->getMessage()
        ]);
    }
}
?>