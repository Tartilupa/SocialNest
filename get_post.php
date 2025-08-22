<?php
session_start();

// Preverimo, ali je uporabnik prijavljen
if (!isset($_SESSION['username'])) {
    echo "<p>Niste prijavljeni. Prosimo, prijavite se.</p>";
    exit();
}

// Povezava na bazo podatkov
include 'db_post.php';

// Pridobivanje objav prijavljenega uporabnika iz baze
try {
    $current_user = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT id, content, author, created_at FROM posts WHERE author = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    // Zapiranje povezave
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo "<p>Napaka pri pridobivanju objav: " . $e->getMessage() . "</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaši prispevki</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #4CAF50;
        }
        .post {
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
        }
        .post:last-child {
            border-bottom: none;
        }
        .post .author {
            font-weight: bold;
            color: #4CAF50;
        }
        .post .date {
            font-size: 0.9em;
            color: #888;
        }
        .post .content {
            margin-top: 10px;
            font-size: 1rem;
            line-height: 1.5;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Vaši prispevki</h1>
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="date"><?php echo date("d.m.Y H:i", strtotime($post['created_at'])); ?></div>
                    <div class="content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Trenutno nimate nobenih objav.</p>
        <?php endif; ?>
    </div>
    <footer>
        <p>MathematicalDB &copy; 2024</p>
    </footer>
</body>
</html>
