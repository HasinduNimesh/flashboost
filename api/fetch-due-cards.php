<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$sessionId = $_GET['session_id'] ?? '';
$deckId = isset($_GET['deck_id']) && intval($_GET['deck_id']) > 0 ? intval($_GET['deck_id']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100; // Higher default limit
$ignoreSchedule = isset($_GET['ignore_schedule']) && $_GET['ignore_schedule'] === '1';
$studyMode = $_GET['study_mode'] ?? 'all'; // Default to 'all' to show all cards

try {
    global $pdo;
    $userId = getCurrentUserId();
    
    $params = [$userId];
    $deckCondition = '';
    
    if ($deckId !== null) {
        $deckCondition = "AND d.id = ?";
        $params[] = $deckId;
    }
    
    // Default condition for due cards
    $dueCondition = "";
    
    // Only apply the scheduling restriction if we're in 'due' mode
    if ($studyMode === 'due' && !$ignoreSchedule) {
        $dueCondition = "AND (ucp.id IS NULL OR ucp.next_review <= CURRENT_DATE())";
    }
    
    // If we're in a continued session, exclude already studied cards
    if ($sessionId) {
        $stmt = $pdo->prepare("
            SELECT sr.card_id 
            FROM study_records sr 
            JOIN study_sessions ss ON sr.session_id = ss.id 
            WHERE ss.id = ? AND ss.user_id = ?
        ");
        $stmt->execute([$sessionId, $userId]);
        $excludeCardIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If we have cards to exclude, add that condition
        if (!empty($excludeCardIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeCardIds), '?'));
            $dueCondition .= " AND c.id NOT IN ($placeholders)";
            $params = array_merge($params, $excludeCardIds);
        }
    }
    
    // Log for debugging
    error_log("Study mode: $studyMode, Ignore schedule: " . ($ignoreSchedule ? "true" : "false"));
    
    // Get cards
    $query = "
        SELECT c.*, d.title as deck_title, d.id as deck_id,
               COALESCE(ucp.ease_factor, 2.5) as ease_factor,
               COALESCE(ucp.`interval`, 0) as `interval`,
               ucp.next_review,
               CASE 
                   WHEN ucp.id IS NULL THEN 'new'
                   WHEN ucp.next_review <= CURRENT_DATE() THEN 'due'
                   ELSE 'later'
               END as card_status
        FROM cards c
        JOIN decks d ON c.deck_id = d.id
        LEFT JOIN user_card_progress ucp ON c.id = ucp.card_id AND ucp.user_id = ?
        WHERE d.user_id = $userId
              AND c.is_archived = 0
              $dueCondition
              $deckCondition
        ORDER BY RAND()
        LIMIT ?
    ";
    
    $params[] = $limit;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total cards in deck for reference
    if ($deckId !== null) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM cards c 
            JOIN decks d ON c.deck_id = d.id 
            WHERE d.id = ? AND d.user_id = ? AND c.is_archived = 0
        ");
        $stmt->execute([$deckId, $userId]);
        $totalCardsInDeck = (int)$stmt->fetchColumn();
    } else {
        $totalCardsInDeck = null;
    }
    
    echo json_encode([
        'success' => true,
        'cards' => $cards,
        'count' => count($cards),
        'total_in_deck' => $totalCardsInDeck,
        'deck_id' => $deckId,
        'study_mode' => $studyMode,
        'debug' => [
            'session_id' => $sessionId,
            'deck_id' => $deckId,
            'limit' => $limit,
            'study_mode' => $studyMode,
            'ignore_schedule' => $ignoreSchedule,
            'cards_returned' => count($cards)
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in fetch-due-cards.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'cards' => []
    ]);
}
?>