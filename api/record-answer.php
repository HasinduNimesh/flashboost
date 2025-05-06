<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/srs.php';

// Set a longer timeout for processing
ini_set('max_execution_time', 30); // 30 seconds

header('Content-Type: application/json');

// Check if logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['card_id']) || !isset($data['rating'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

$cardId = (int)$data['card_id'];
$rating = (int)$data['rating'];
$sessionId = $data['session_id'] ?? '';
$ignoreSchedule = isset($data['ignore_schedule']) && $data['ignore_schedule'] === true;
$isLastCard = isset($data['is_last_card']) && $data['is_last_card'] === true;

// Validate rating range (0-5)
if ($rating < 0 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid rating value']);
    exit;
}

try {
    global $pdo;
    $userId = getCurrentUserId();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // First, check if this is a legitimate card and user has access
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM cards c 
        JOIN decks d ON c.deck_id = d.id 
        WHERE c.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$cardId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Card not found or access denied']);
        exit;
    }
    
    // Get current card progress
    $stmt = $pdo->prepare("
        SELECT * FROM user_card_progress
        WHERE user_id = ? AND card_id = ?
    ");
    $stmt->execute([$userId, $cardId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate new SRS values based on rating
    if ($progress && !$ignoreSchedule) {
        // Card exists in progress table - update
        list($easeFactor, $interval, $nextReview) = calculateNextReview($progress['ease_factor'], $progress['interval'], $rating);
        
        $stmt = $pdo->prepare("
            UPDATE user_card_progress
            SET ease_factor = ?,
                `interval` = ?,
                next_review = ?,
                repetitions = repetitions + 1,
                last_reviewed = NOW()
            WHERE user_id = ? AND card_id = ?
        ");
        $stmt->execute([$easeFactor, $interval, $nextReview, $userId, $cardId]);
    } 
    elseif (!$progress) {
        // New card - insert initial progress
        list($easeFactor, $interval, $nextReview) = calculateNextReview(2.5, 0, $rating);
        
        $stmt = $pdo->prepare("
            INSERT INTO user_card_progress
            (user_id, card_id, ease_factor, `interval`, next_review, repetitions, last_reviewed)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$userId, $cardId, $easeFactor, $interval, $nextReview]);
    }
    else {
        // This is an out-of-schedule review, just update the last_reviewed timestamp
        // without affecting the scheduling algorithm
        $stmt = $pdo->prepare("
            UPDATE user_card_progress
            SET last_reviewed = NOW()
            WHERE user_id = ? AND card_id = ?
        ");
        $stmt->execute([$userId, $cardId]);
    }
    
    // Record study data in study_records if session ID is provided
    if ($sessionId) {
        $stmt = $pdo->prepare("
            SELECT id FROM study_sessions WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$sessionId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            $sessionDbId = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("
                INSERT INTO study_records (session_id, card_id, rating, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$sessionDbId, $cardId, $rating]);
            
            // Update session stats
            $stmt = $pdo->prepare("
                UPDATE study_sessions 
                SET cards_studied = cards_studied + 1,
                    cards_correct = cards_correct + IF(? >= 3, 1, 0)
                WHERE id = ?
            ");
            $stmt->execute([$rating, $sessionDbId]);
            
            // If this is the last card, update end_time
            if ($isLastCard) {
                $stmt = $pdo->prepare("
                    UPDATE study_sessions 
                    SET end_time = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$sessionDbId]);
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Answer recorded successfully',
        'is_scheduled_review' => !$ignoreSchedule,
        'card_id' => $cardId,
        'is_last_card' => $isLastCard
    ]);
    
} catch (Exception $e) {
    // Roll back transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error for server-side troubleshooting
    error_log('FlashBoost Error in record-answer.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage(),
        'debug_info' => [
            'time' => date('Y-m-d H:i:s'),
            'card_id' => $cardId,
            'user_id' => $userId
        ]
    ]);
}

/**
 * Calculate next review date based on SM-2 algorithm
 */
function calculateNextReview($easeFactor, $interval, $rating) {
    // Convert rating from 0-5 to 0-1 scale for the SRS algorithm
    $adjustedRating = $rating / 5;
    
    // Implement SuperMemo SM-2 algorithm
    $easeFactor = max(1.3, $easeFactor + (0.1 - (5 - $rating) * (0.08 + (5 - $rating) * 0.02)));
    
    // Calculate new interval
    if ($rating < 3) {
        // If rating is less than "Good", reset interval to 0 (relearning)
        $interval = 0;
    } else {
        if ($interval === 0) {
            $interval = 1;
        } elseif ($interval === 1) {
            $interval = 6;
        } else {
            $interval = round($interval * $easeFactor);
        }
    }
    
    // Cap maximum interval at 365 days
    $interval = min(365, $interval);
    
    // Calculate next review date
    $nextReview = date('Y-m-d', strtotime("+{$interval} day"));
    
    return [$easeFactor, $interval, $nextReview];
}
?>