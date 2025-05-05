<?php
require_once 'db.php';
require_once 'auth.php';

// Create a new deck
function createDeck(int $moduleId, string $title, string $description = ''): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    $title = trim($title);
    if (empty($title)) {
        return ['success' => false, 'error' => 'Deck title is required'];
    }
    
    try {
        // First verify that the module belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND user_id = ?");
        $stmt->execute([$moduleId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Module not found or access denied'];
        }
        
        // Create the deck
        $stmt = $pdo->prepare("
            INSERT INTO decks (user_id, module_id, title, description) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $moduleId, $title, $description]);
        
        $deckId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'deckId' => $deckId
        ];
    } catch (PDOException $e) {
        return [
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Get decks by module
function getDecksByModule(int $moduleId): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in', 'decks' => []];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT d.*,
                (SELECT COUNT(*) FROM cards WHERE deck_id = d.id) as card_count,
                (SELECT COUNT(*) FROM user_card_progress ucp 
                 JOIN cards c ON ucp.card_id = c.id 
                 WHERE c.deck_id = d.id AND ucp.user_id = ? AND 
                       (ucp.next_review IS NULL OR ucp.next_review <= CURRENT_DATE())) as due_count
            FROM decks d
            WHERE d.module_id = ? AND d.user_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$userId, $moduleId, $userId]);
        $decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'decks' => $decks
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'decks' => []
        ];
    }
}

// Get a specific deck by ID
function getDecks(int $deckId = null): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in', 'decks' => []];
    }
    
    try {
        if ($deckId) {
            // Get a specific deck
            $stmt = $pdo->prepare("
                SELECT d.*,
                    (SELECT COUNT(*) FROM cards WHERE deck_id = d.id) as card_count,
                    (SELECT COUNT(*) FROM user_card_progress ucp 
                     JOIN cards c ON ucp.card_id = c.id 
                     WHERE c.deck_id = d.id AND ucp.user_id = ? AND 
                           (ucp.next_review IS NULL OR ucp.next_review <= CURRENT_DATE())) as due_count,
                    m.title as module_title
                FROM decks d
                LEFT JOIN modules m ON d.module_id = m.id
                WHERE d.id = ? AND d.user_id = ?
            ");
            $stmt->execute([$userId, $deckId, $userId]);
        } else {
            // Get all decks
            $stmt = $pdo->prepare("
                SELECT d.*,
                    (SELECT COUNT(*) FROM cards WHERE deck_id = d.id) as card_count,
                    (SELECT COUNT(*) FROM user_card_progress ucp 
                     JOIN cards c ON ucp.card_id = c.id 
                     WHERE c.deck_id = d.id AND ucp.user_id = ? AND 
                           (ucp.next_review IS NULL OR ucp.next_review <= CURRENT_DATE())) as due_count,
                    m.title as module_title
                FROM decks d
                LEFT JOIN modules m ON d.module_id = m.id
                WHERE d.user_id = ?
                ORDER BY d.created_at DESC
            ");
            $stmt->execute([$userId, $userId]);
        }
        
        $decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'decks' => $decks
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'decks' => []
        ];
    }
}

// Update deck
function updateDeck(int $deckId, array $data): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    try {
        // First check if the deck belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
        $stmt->execute([$deckId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Deck not found or access denied'];
        }
        
        // Update the deck
        $stmt = $pdo->prepare("
            UPDATE decks 
            SET title = ?, description = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $data['title'] ?? '',
            $data['description'] ?? '',
            $deckId
        ]);
        
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

// Delete deck
function deleteDeck(int $deckId): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    try {
        // First check if the deck belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
        $stmt->execute([$deckId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Deck not found or access denied'];
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete all cards in this deck
        $stmt = $pdo->prepare("DELETE FROM cards WHERE deck_id = ?");
        $stmt->execute([$deckId]);
        
        // Delete the deck
        $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ?");
        $stmt->execute([$deckId]);
        
        $pdo->commit();
        
        return [
            'success' => true
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}
?>