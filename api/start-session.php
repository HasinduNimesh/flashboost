<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = getCurrentUserId();
$deckId = isset($_GET['deck_id']) && intval($_GET['deck_id']) > 0 ? intval($_GET['deck_id']) : null;

try {
    global $pdo;
    
    // If deckId is provided, verify it exists and belongs to the user
    if ($deckId !== null) {
        $checkStmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$deckId, $userId]);
        if ($checkStmt->rowCount() == 0) {
            // If deck doesn't exist or doesn't belong to user, set to NULL
            $deckId = null;
        }
    }
    
    // Generate a unique session ID
    $sessionId = uniqid('session_', true);
    
    // Record session start in database
    if ($deckId === null) {
        // If studying all cards (no specific deck), set deck_id to NULL explicitly
        $stmt = $pdo->prepare("
            INSERT INTO study_sessions 
            (user_id, deck_id, start_time) 
            VALUES (?, NULL, NOW())
        ");
        $stmt->execute([$userId]);
    } else {
        // If studying a specific deck
        $stmt = $pdo->prepare("
            INSERT INTO study_sessions 
            (user_id, deck_id, start_time) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$userId, $deckId]);
    }
    
    $sessionDbId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'session_db_id' => $sessionDbId,
        'studying_all' => ($deckId === null)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>