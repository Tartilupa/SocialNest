<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in to like posts."]);
    exit();
}

include 'db_post.php';

$input = json_decode(file_get_contents("php://input"), true);
$postId = isset($input['post_id']) ? (int)$input['post_id'] : 0;
$username = $_SESSION['username'];

if ($postId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid post ID."]);
    exit();
}

// 1. Check if user already liked the post
$stmt = $conn->prepare("SELECT 1 FROM user_likes WHERE post_id = ? AND username = ?");
$stmt->bind_param("is", $postId, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "You have already liked this post."]);
    exit();
}
$stmt->close();

// 2. Insert into post_likes
$stmt = $conn->prepare("INSERT INTO user_likes (post_id, username) VALUES (?, ?)");
$stmt->bind_param("is", $postId, $username);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Error recording like."]);
    exit();
}
$stmt->close();

// 3. Update like count in posts table
$stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?");
$stmt->bind_param("i", $postId);
$stmt->execute();
$stmt->close();

// 4. Return updated like count
$result = $conn->query("SELECT likes FROM posts WHERE id = $postId");
$likes = $result->fetch_assoc()['likes'];

echo json_encode(["success" => true, "likes" => $likes]);
$conn->close();
?>
