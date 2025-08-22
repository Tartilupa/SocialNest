<?php
session_start();
require 'db_post.php'; // Include the database connection file

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "error" => "000001", "message" => "Please fill in all fields."]);
    exit;
}

$username = $data['username'];
$password = $data['password'];

// Prepare the query
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE BINARY username = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => "000002", "message" => "Error preparing query."]);
    exit;
}

$stmt->bind_param("s", $username);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "error" => "000003", "message" => "Error executing query."]);
    exit;
}

$result = $stmt->get_result();
if (!$result) {
    echo json_encode(["success" => false, "error" => "000004", "message" => "Error fetching results."]);
    exit;
}

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(["success" => true, "message" => "Login successful."]);
    } else {
        echo json_encode(["success" => false, "error" => "000005", "message" => "Incorrect username or password."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "000006", "message" => "User does not exist."]);
}

$stmt->close();
$conn->close();
?>
