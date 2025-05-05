<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$sessionId = $_GET['session_id'] ?? '';

try {
    global $pdo;
    $userId = getCurrentUserId();
    
    // Update the most recent active session for this user
    $stmt = $pdo->prepare("
        UPDATE study_sessions
        SET end_time = NOW()
        WHERE user_id = ? AND end_time IS NULL
        ORDER BY start_time DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>