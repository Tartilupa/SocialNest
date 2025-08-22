<?php
$data = json_decode(file_get_contents('php://input'), true);

// Check if any data is received
if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received."]);
    exit;
}

// Check required fields
if (!isset($data['name']) || !isset($data['email']) || !isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Missing data: " . json_encode($data)]);
    exit;
}

include 'db_post.php';

$name = $data['name'];  // Now using "name" instead of "fullname"
$email = $data['email'];
$username = $data['username'];
$password = password_hash($data['password'], PASSWORD_BCRYPT); // Hash the password

// Validate name: only letters, spaces, hyphens, and apostrophes
if (!preg_match('/^[\p{L} \'-]+$/u', $name)) {
    echo json_encode(["success" => false, "message" => "Full name contains invalid characters."]);
    exit;
}

// Check if the username or email already exists
$sql = "SELECT * FROM users WHERE username = ? OR email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username or email already exists."]);
    exit;
}

// Insert new user
$sql = "INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $email, $username, $password);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Registration successful!"]);
} else {
    echo json_encode(["success" => false, "message" => "Error during registration."]);
}

$stmt->close();
$conn->close();
?>
