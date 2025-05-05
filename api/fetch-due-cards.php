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
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

try {
    global $pdo;
    $userId = getCurrentUserId();
    
    $params = [$userId, $userId];
    $deckCondition = '';
    
    if ($deckId !== null) {
        $deckCondition = "AND d.id = ?";
        $params[] = $deckId;
    }
    
    // Get cards that are due or new (not in user_card_progress)
    $query = "
        SELECT c.*, d.title as deck_title, d.id as deck_id,
               COALESCE(ucp.ease_factor, 2.5) as ease_factor,
               COALESCE(ucp.`interval`, 0) as `interval`,
               ucp.next_review
        FROM cards c
        JOIN decks d ON c.deck_id = d.id
        LEFT JOIN user_card_progress ucp ON c.id = ucp.card_id AND ucp.user_id = ?
        WHERE d.user_id = ? AND (ucp.id IS NULL OR ucp.next_review <= CURRENT_DATE())
        $deckCondition
        ORDER BY RAND()
        LIMIT ?
    ";
    
    $params[] = $limit;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'cards' => $cards,
        'count' => count($cards),
        'deck_id' => $deckId
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'cards' => []
    ]);
}
?>