<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = "TestUser"; // For demo purposes only
}

// Detect if request is AJAX (JSON)
$isAjax = false;
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    $isAjax = true;
} elseif (
    isset($_SERVER['CONTENT_TYPE']) && 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
) {
    $isAjax = true;
}

$message = null;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db_post.php';

    // Default: classic POST
    $content = isset($_POST['post_content']) ? trim($_POST['post_content']) : null;

    // If AJAX/JSON, parse raw input
    if ($isAjax && empty($content)) {
        $data = json_decode(file_get_contents('php://input'), true);
        // Support both 'post_content' and 'pris' keys
        if (isset($data['post_content'])) {
            $content = trim($data['post_content']);
        } elseif (isset($data['pris'])) {
            $content = trim($data['pris']);
        }
    }

    $username = $_SESSION['username'];

    if (empty($content)) {
        $message = ["type" => "error", "text" => "Post content cannot be empty."];
    } else {
        $stmt = $conn->prepare("SELECT name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $author_name = $row['name'];
        } else {
            $message = ["type" => "error", "text" => "Error: User does not exist."];
        }

        $stmt->close();

        if (!isset($message)) {
            $stmt = $conn->prepare("INSERT INTO posts (content, author) VALUES (?, ?)");
            $stmt->bind_param("ss", $content, $author_name);

            if ($stmt->execute()) {
                $message = ["type" => "success", "text" => "Post added successfully."];
            } else {
                $message = ["type" => "error", "text" => "Error saving post: " . $conn->error];
            }

            $stmt->close();
        }
    }

    $conn->close();

    // If AJAX, return JSON and exit
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($message);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Post</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        display: flex;
        justify-content: center;
        padding-top: 50px;
    }
    .container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    h2 {
        margin-top: 0;
    }
    textarea {
        width: 100%;
        height: 100px;
        padding: 10px;
        resize: none;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    button {
        margin-top: 10px;
        padding: 10px;
        background: #4CAF50;
        border: none;
        color: white;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
    }
    button:hover {
        background: #45a049;
    }
    .message {
        margin-top: 10px;
        padding: 10px;
        border-radius: 5px;
    }
    .success {
        background: #d4edda;
        color: #155724;
    }
    .error {
        background: #f8d7da;
        color: #721c24;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Add a New Post</h2>
    <form method="POST">
        <textarea name="post_content" placeholder="Write your post here..."></textarea>
        <button type="submit">Submit</button>
    </form>
    <?php if (!empty($message)): ?>
        <div class="message <?= htmlspecialchars($message['type']) ?>">
            <?= htmlspecialchars($message['text']) ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>