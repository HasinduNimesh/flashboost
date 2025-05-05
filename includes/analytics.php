<?php
class Analytics {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getDeckStats(int $deckId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) AS total_cards,
                SUM(CASE WHEN next_review <= CURDATE() THEN 1 ELSE 0 END) AS due_cards,
                SUM(CASE WHEN ease_factor > 2.5 AND `interval` > 30 THEN 1 ELSE 0 END) AS mastered_cards,
                AVG(ease_factor) AS avg_ease_factor
            FROM cards
            WHERE deck_id = ?
        ");
        $stmt->bind_param("i", $deckId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            return ['success' => false, 'error' => 'Failed to get deck statistics'];
        }
        
        return ['success' => true, 'stats' => $result];
    }
    
    public function getTagStats(int $deckId): array {
        // This is a more complex query as we need to extract from JSON
        $stmt = $this->db->prepare("
            SELECT 
                c.id,
                c.tags,
                AVG(cr.quality) AS avg_quality,
                COUNT(cr.id) AS review_count
            FROM cards c
            LEFT JOIN card_reviews cr ON c.id = cr.card_id
            WHERE c.deck_id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("i", $deckId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tagStats = [];
        
        while ($row = $result->fetch_assoc()) {
            $tags = json_decode($row['tags'], true) ?? [];
            $avgQuality = $row['avg_quality'] ?? 0;
            $reviewCount = $row['review_count'] ?? 0;
            
            foreach ($tags as $tag) {
                if (!isset($tagStats[$tag])) {
                    $tagStats[$tag] = [
                        'tag' => $tag,
                        'card_count' => 0,
                        'total_quality' => 0,
                        'total_reviews' => 0
                    ];
                }
                
                $tagStats[$tag]['card_count']++;
                $tagStats[$tag]['total_quality'] += $avgQuality * $reviewCount;
                $tagStats[$tag]['total_reviews'] += $reviewCount;
            }
        }
        
        // Calculate averages for each tag
        foreach ($tagStats as &$stat) {
            $stat['avg_quality'] = $stat['total_reviews'] > 0 
                ? round($stat['total_quality'] / $stat['total_reviews'], 2)
                : 0;
            unset($stat['total_quality']);
        }
        
        return ['success' => true, 'tagStats' => array_values($tagStats)];
    }
    
    public function getSessionHistory(int $userId, string $startDate = null, string $endDate = null): array {
        $sql = "
            SELECT 
                s.id,
                s.started_at,
                s.ended_at,
                s.cards_reviewed,
                s.correct_count,
                d.title AS deck_title,
                TIMESTAMPDIFF(SECOND, s.started_at, s.ended_at) AS duration_seconds
            FROM study_sessions s
            JOIN decks d ON s.deck_id = d.id
            WHERE s.user_id = ? AND s.ended_at IS NOT NULL
        ";
        
        $types = "i";
        $params = [$userId];
        
        if ($startDate) {
            $sql .= " AND s.started_at >= ?";
            $types .= "s";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND s.started_at <= ?";
            $types .= "s";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY s.started_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $sessions = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['accuracy'] = $row['cards_reviewed'] > 0 
                ? round(($row['correct_count'] / $row['cards_reviewed']) * 100)
                : 0;
                
            $sessions[] = $row;
        }
        
        return ['success' => true, 'sessions' => $sessions];
    }
}
?>