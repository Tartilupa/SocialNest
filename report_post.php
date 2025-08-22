<?php
session_start();
header('Content-Type: application/json');

// Preverimo, ali je uporabnik prijavljen
if (!isset($_SESSION['username'])) {
    echo json_encode(["success" => false, "message" => "Morate biti prijavljeni, da prijavite prispevek."]);
    exit();
}

// Povezava na bazo
include 'db_post.php';

// Preverimo, če je zahteva pravilna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $postId = $data['post_id'];
    $reason = trim($data['reason']);
    $reportedBy = $_SESSION['username'];

    // Preverimo, če so podatki pravilni
    if (empty($postId) || empty($reason)) {
        echo json_encode(["success" => false, "message" => "Vsi podatki morajo biti izpolnjeni."]);
        exit();
    }

    // Dodajanje prijavljenega prispevka v tabelo
    $stmt = $conn->prepare("INSERT INTO reported_posts (post_id, reported_by, report_reason) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $postId, $reportedBy, $reason);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Prispevek je bil uspešno prijavljen."]);
    } else {
        echo json_encode(["success" => false, "message" => "Napaka pri prijavi prispevka: " . $conn->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Neveljavna zahteva."]);
}
?>
