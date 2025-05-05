<?php
require_once 'db.php';
require_once 'auth.php';

// Create a new card
function createCard(int $deckId, array $data): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    $frontContent = trim($data['front_content']);
    $backContent = trim($data['back_content']);
    $tags = isset($data['tags']) && !empty(trim($data['tags'])) ? trim($data['tags']) : null;
    
    if (empty($frontContent) || empty($backContent)) {
        return ['success' => false, 'error' => 'Card front and back content are required'];
    }
    
    try {
        // First verify that the deck belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
        $stmt->execute([$deckId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Deck not found or access denied'];
        }
        
        // Create the card
        $stmt = $pdo->prepare("
            INSERT INTO cards (deck_id, front_content, back_content, tags) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$deckId, $frontContent, $backContent, $tags]);
        
        $cardId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'cardId' => $cardId
        ];
    } catch (PDOException $e) {
        return [
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Get cards by deck
function getCardsByDeck(int $deckId): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in', 'cards' => []];
    }
    
    try {
        // First verify that the deck belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
        $stmt->execute([$deckId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Deck not found or access denied', 'cards' => []];
        }
        
        // Get all cards in the deck
        $stmt = $pdo->prepare("
            SELECT c.*,
                   COALESCE(ucp.ease_factor, 2.5) as ease_factor,
                   ucp.interval,
                   ucp.next_review
            FROM cards c
            LEFT JOIN user_card_progress ucp ON c.id = ucp.card_id AND ucp.user_id = ?
            WHERE c.deck_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId, $deckId]);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'cards' => $cards
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'cards' => []
        ];
    }
}

// Get a specific card
function getCard(int $cardId): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*,
                   d.user_id as deck_user_id,
                   COALESCE(ucp.ease_factor, 2.5) as ease_factor,
                   ucp.interval,
                   ucp.next_review
            FROM cards c
            JOIN decks d ON c.deck_id = d.id
            LEFT JOIN user_card_progress ucp ON c.id = ucp.card_id AND ucp.user_id = ?
            WHERE c.id = ?
        ");
        $stmt->execute([$userId, $cardId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Card not found'];
        }
        
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if the card belongs to the user
        if ($card['deck_user_id'] != $userId) {
            return ['success' => false, 'error' => 'Access denied'];
        }
        
        return [
            'success' => true,
            'card' => $card
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Update card
function updateCard(int $cardId, array $data): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    $frontContent = trim($data['front_content']);
    $backContent = trim($data['back_content']);
    $tags = trim($data['tags'] ?? '');
    
    if (empty($frontContent) || empty($backContent)) {
        return ['success' => false, 'error' => 'Card front and back content are required'];
    }
    
    try {
        // First verify that the card belongs to a deck owned by this user
        $stmt = $pdo->prepare("
            SELECT c.id FROM cards c
            JOIN decks d ON c.deck_id = d.id
            WHERE c.id = ? AND d.user_id = ?
        ");
        $stmt->execute([$cardId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Card not found or access denied'];
        }
        
        // Update the card
        $stmt = $pdo->prepare("
            UPDATE cards 
            SET front_content = ?, back_content = ?, tags = ?
            WHERE id = ?
        ");
        $stmt->execute([$frontContent, $backContent, $tags, $cardId]);
        
        return [
            'success' => true
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Delete card
function deleteCard(int $cardId): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    try {
        // First verify that the card belongs to a deck owned by this user
        $stmt = $pdo->prepare("
            SELECT c.id FROM cards c
            JOIN decks d ON c.deck_id = d.id
            WHERE c.id = ? AND d.user_id = ?
        ");
        $stmt->execute([$cardId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Card not found or access denied'];
        }
        
        // Delete the card progress first
        $stmt = $pdo->prepare("DELETE FROM user_card_progress WHERE card_id = ?");
        $stmt->execute([$cardId]);
        
        // Then delete the card
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ?");
        $stmt->execute([$cardId]);
        
        return [
            'success' => true
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Get due cards for study
function fetchDueCards(?int $deckId = null, int $limit = 20): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in', 'cards' => []];
    }
    
    try {
        $params = [$userId];
        $deckCondition = '';
        
        if ($deckId) {
            $deckCondition = "AND d.id = ?";
            $params[] = $deckId;
        }
        
        // Get cards that are due or new (not in user_card_progress)
        $stmt = $pdo->prepare("
            SELECT c.*, d.title as deck_title, d.id as deck_id,
                   COALESCE(ucp.ease_factor, 2.5) as ease_factor,
                   COALESCE(ucp.interval, 0) as interval,
                   ucp.next_review
            FROM cards c
            JOIN decks d ON c.deck_id = d.id
            LEFT JOIN user_card_progress ucp ON c.id = ucp.card_id AND ucp.user_id = ?
            WHERE d.user_id = ? AND (ucp.id IS NULL OR ucp.next_review <= CURRENT_DATE())
            $deckCondition
            ORDER BY RAND()
            LIMIT ?
        ");
        
        $params[] = $userId;
        $params[] = $limit;
        
        $stmt->execute($params);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'cards' => $cards
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'cards' => []
        ];
    }
}
?>