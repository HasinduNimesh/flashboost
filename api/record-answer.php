<?php
require_once '../includes/auth.php';
require_once '../includes/srs.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get JSON data from POST request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['card_id'], $data['rating'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$cardId = intval($data['card_id']);
$rating = intval($data['rating']);
$sessionId = $data['session_id'] ?? '';

if ($rating < 0 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid rating value']);
    exit;
}

try {
    global $pdo;
    $userId = getCurrentUserId();
    
    // First check if this card belongs to a deck owned by the current user
    $stmt = $pdo->prepare("
        SELECT c.id FROM cards c
        JOIN decks d ON c.deck_id = d.id
        WHERE c.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$cardId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Card not found or access denied']);
        exit;
    }
    
    // Get current progress or initialize new record
    $stmt = $pdo->prepare("
        SELECT * FROM user_card_progress
        WHERE user_id = ? AND card_id = ?
    ");
    $stmt->execute([$userId, $cardId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $now = date('Y-m-d H:i:s');
    
    if ($progress) {
        // Update existing progress
        $easeFactor = max(1.3, $progress['ease_factor'] + (0.1 - (5 - $rating) * (0.08 + (5 - $rating) * 0.02)));
        
        if ($rating < 3) {
            $interval = 0; // Start over on wrong answer
            $repetitions = 0;
        } else {
            $repetitions = $progress['repetitions'] + 1;
            
            if ($repetitions == 1) {
                $interval = 1;
            } else if ($repetitions == 2) {
                $interval = 6;
            } else {
                $interval = round($progress['interval'] * $easeFactor);
            }
        }
        
        $nextReview = date('Y-m-d', strtotime("+$interval days"));
        
        $stmt = $pdo->prepare("
            UPDATE user_card_progress
            SET ease_factor = ?, 
                `interval` = ?,
                repetitions = ?,
                next_review = ?,
                last_reviewed = ?
            WHERE user_id = ? AND card_id = ?
        ");
        $stmt->execute([$easeFactor, $interval, $repetitions, $nextReview, $now, $userId, $cardId]);
    } else {
        // Create new progress record
        $easeFactor = 2.5; // Default ease factor
        $interval = ($rating < 3) ? 0 : 1; // Start with 1 day if correct, 0 if incorrect
        $repetitions = ($rating < 3) ? 0 : 1;
        
        $nextReview = date('Y-m-d', strtotime("+$interval days"));
        
        $stmt = $pdo->prepare("
            INSERT INTO user_card_progress 
            (user_id, card_id, ease_factor, `interval`, repetitions, next_review, last_reviewed)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $cardId, $easeFactor, $interval, $repetitions, $nextReview, $now]);
    }
    
    // Update session statistics
    if (!empty($sessionId)) {
        $stmt = $pdo->prepare("
            UPDATE study_sessions
            SET cards_studied = cards_studied + 1,
                cards_correct = cards_correct + ?
            WHERE user_id = ? AND end_time IS NULL
            ORDER BY start_time DESC
            LIMIT 1
        ");
        $stmt->execute([($rating >= 3 ? 1 : 0), $userId]);
    }
    
    echo json_encode([
        'success' => true,
        'next_review' => $nextReview
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>