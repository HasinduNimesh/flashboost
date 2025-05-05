<?php
require_once 'db.php';
require_once 'auth.php';

// Create a new module
function createModule(string $title, string $description = ''): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    $title = trim($title);
    if (empty($title)) {
        return ['success' => false, 'error' => 'Module title is required'];
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO modules (user_id, title, description) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $description]);
        
        $moduleId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'moduleId' => $moduleId
        ];
    } catch (PDOException $e) {
        return [
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Get all modules for the current user
function getAllModules(): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in', 'modules' => []];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                (SELECT COUNT(d.id) FROM decks d WHERE d.module_id = m.id) as deck_count,
                (SELECT COUNT(c.id) FROM cards c 
                 JOIN decks d ON c.deck_id = d.id 
                 WHERE d.module_id = m.id) as card_count
            FROM modules m
            WHERE m.user_id = ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'modules' => $modules
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'modules' => []
        ];
    }
}

// Get a specific module by ID
function getModuleById(int $moduleId): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT m.*,
                (SELECT COUNT(d.id) FROM decks d WHERE d.module_id = m.id) as deck_count,
                (SELECT COUNT(c.id) FROM cards c 
                 JOIN decks d ON c.deck_id = d.id 
                 WHERE d.module_id = m.id) as card_count
            FROM modules m
            WHERE m.id = ? AND m.user_id = ?
        ");
        $stmt->execute([$moduleId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Module not found'];
        }
        
        $module = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'module' => $module
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Update module
function updateModule(int $moduleId, array $data): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    try {
        // First check if the module belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND user_id = ?");
        $stmt->execute([$moduleId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Module not found or access denied'];
        }
        
        // Update the module
        $stmt = $pdo->prepare("
            UPDATE modules 
            SET title = ?, description = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $data['title'] ?? '',
            $data['description'] ?? '',
            $moduleId
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

// Delete module
function deleteModule(int $moduleId): array {
    global $pdo;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return ['success' => false, 'error' => 'User not logged in'];
    }
    
    try {
        // First check if the module belongs to this user
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND user_id = ?");
        $stmt->execute([$moduleId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Module not found or access denied'];
        }
        
        // Start a transaction since we'll delete multiple related records
        $pdo->beginTransaction();
        
        // Delete all cards in all decks in this module
        $stmt = $pdo->prepare("
            DELETE c FROM cards c
            JOIN decks d ON c.deck_id = d.id
            WHERE d.module_id = ?
        ");
        $stmt->execute([$moduleId]);
        
        // Delete all decks in this module
        $stmt = $pdo->prepare("DELETE FROM decks WHERE module_id = ?");
        $stmt->execute([$moduleId]);
        
        // Delete the module itself
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
        $stmt->execute([$moduleId]);
        
        $pdo->commit();
        
        return [
            'success' => true
        ];
    } catch (PDOException $e) {
        // If anything goes wrong, roll back the transaction
        $pdo->rollBack();
        
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}
?>