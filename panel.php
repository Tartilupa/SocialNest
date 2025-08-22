<?php
session_start();
require 'db_post.php';

$accessmemb = ['test', 'Lenart']; // Popravljena napaka - dodana manjkajoča vejica

// Preverimo, ali je uporabnik prijavljen in ali ima dostop
if (!isset($_SESSION['username']) || !in_array($_SESSION['username'], $accessmemb)) {
    die("Access Denied!");
}

// Če admin potrdi ali odstrani verifikacijo uporabnika
if (isset($_POST['user_id']) && isset($_POST['action'])) {
    $verified = ($_POST['action'] == "verify") ? 1 : 0;
    $user_id = intval($_POST['user_id']); // Preprečitev SQL injection
    
    $stmt = $conn->prepare("UPDATE users SET verified = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $verified, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: panel.php");
    exit();
}

// Pridobi vse neverificirane uporabnike
$stmt = $conn->prepare("SELECT id, name, username, email FROM users WHERE verified = 0");
$stmt->execute();
$unverified_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Pridobi vse verificirane uporabnike
$stmt = $conn->prepare("SELECT id, name, username, email FROM users WHERE verified = 1");
$stmt->execute();
$verified_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f4f4f9;
            color: #333;
        }
        h2 {
            text-align: center;
            color: #4CAF50;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #4CAF50;
            color: white;
        }
        tr:hover {
            background: #f1f1f1;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
        }
        button.unverify {
            background: #ff4444;
        }
        button:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Admin Panel - Uporabniki</h2>
    <a href="dashboard.php" style="text-decoration: none;"><h2>⏪ Nazaj</h2></a>
    <h3>⏳ Neverificirani uporabniki</h3>
    <table>
        <tr><th>ID</th><th>Ime</th><th>Uporabniško ime</th><th>Email</th><th>Akcija</th></tr>
        <?php foreach ($unverified_users as $user): ?>
        <tr>
            <td><?= $user['id']; ?></td>
            <td><?= htmlspecialchars($user['name']); ?></td>
            <td><?= htmlspecialchars($user['username']); ?></td>
            <td><?= htmlspecialchars($user['email']); ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                    <button type="submit" name="action" value="verify">
                        ✅ Verificiraj
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <h3>✅ Verificirani uporabniki</h3>
    <table>
        <tr><th>ID</th><th>Ime</th><th>Uporabniško ime</th><th>Email</th><th>Akcija</th></tr>
        <?php foreach ($verified_users as $user): ?>
        <tr>
            <td><?= $user['id']; ?></td>
            <td><?= htmlspecialchars($user['name']); ?></td>
            <td><?= htmlspecialchars($user['username']); ?></td>
            <td><?= htmlspecialchars($user['email']); ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                    <button type="submit" name="action" value="unverify" class="unverify">
                        ❌ Odverificiraj
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
