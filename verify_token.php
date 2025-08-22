<?php
session_start();
header('Content-Type: application/json');

// Database connection (prilagodi svoje podatke)
include 'db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token is required']);
        exit;
    }
    
    try {
        // Hash the token to match database storage
        $hashedToken = hash('sha256', $token);
        
        // Check if token exists and is not expired
        $stmt = $pdo->prepare("
            SELECT rt.user_id, u.username 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$hashedToken]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Token is valid, set session
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $result['username'];
            
            // Update token expiry (extend for another 30 days)
            $newExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            $stmt = $pdo->prepare("UPDATE remember_tokens SET expires_at = ? WHERE token = ?");
            $stmt->execute([$newExpiry, $hashedToken]);
            
            echo json_encode(['success' => true, 'message' => 'Token verified']);
        } else {
            // Token is invalid or expired, clean up
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ? OR expires_at <= NOW()");
            $stmt->execute([$hashedToken]);
            
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Token verification failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>