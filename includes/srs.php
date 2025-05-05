<?php
// Simple version of SRS file for debugging
require_once 'db.php';
require_once 'auth.php';

// Update card progress after review - simplified version
function updateCardProgress(int $cardId, int $quality): array {
    try {
        global $pdo;
        $userId = getCurrentUserId();
        
        if (!$userId) {
            return ['success' => false, 'error' => 'User not logged in'];
        }
        
        // Calculate simple next review date based on quality
        $interval = 1;
        if ($quality >= 3) {
            $interval = $quality; // 3-5 days based on rating
        }
        
        $nextReview = date('Y-m-d', strtotime("+$interval days"));
        
        return [
            'success' => true,
            'nextReview' => $nextReview,
            'interval' => $interval
        ];
    } catch (Exception $e) {
        return [
            'success' => false, 
            'error' => 'Error: ' . $e->getMessage()
        ];
    }
}

// Simplified function to fetch due cards
function fetchDueCards(?int $deckId = null, int $limit = 20): array {
    try {
        global $pdo;
        $userId = getCurrentUserId();
        
        if (!$userId) {
            return ['success' => false, 'error' => 'User not logged in', 'cards' => []];
        }
        
        $query = "SELECT c.*, d.title as deck_title, d.id as deck_id
                 FROM cards c
                 JOIN decks d ON c.deck_id = d.id
                 WHERE d.user_id = ?";
        
        $params = [$userId];
        
        if ($deckId) {
            $query .= " AND d.id = ?";
            $params[] = $deckId;
        }
        
        $query .= " LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'cards' => $cards
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'cards' => []
        ];
    }
}
?>