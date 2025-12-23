<!-- update_status.php -->
<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;

    if (!$id || !$status || !in_array($status, ['pending', 'open', 'success', 'cancle'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE instalasi SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>