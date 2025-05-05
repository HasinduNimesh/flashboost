<?php
require_once 'includes/auth.php';
require_once 'includes/modules.php';
require_once 'includes/decks.php';
require_once 'includes/srs.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user info and other data
$userInfo = getUserInfo();

// If userInfo is null, there's a problem with the session
if (!$userInfo) {
    // Clear any potential problematic session data
    logoutUser();
    header('Location: login.php');
    exit;
}

// Initialize counters with zero as defaults
$totalCards = 0;
$totalDecks = 0;
$masteredCards = 0;
$dueCardsCount = 0;

// Try to get actual counts from database (with error handling)
try {
    global $pdo;
    $userId = getCurrentUserId();
    
    // Get modules (with error handling)
    $modulesResult = [];
    try {
        $modulesResult = getAllModules();
    } catch (Exception $e) {
        $modulesResult = ['success' => false, 'modules' => []];
    }
    
    // Get due cards (with error handling)
    $dueCards = [];
    try {
        $dueCards = fetchDueCards(null, 10);
        $dueCardsCount = count($dueCards['success'] ? $dueCards['cards'] : []);
    } catch (Exception $e) {
        $dueCards = ['success' => false, 'cards' => []];
    }
    
    // Safe database queries with error handling
    if ($pdo) {
        // Count cards
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM cards c 
                JOIN decks d ON c.deck_id = d.id 
                WHERE d.user_id = ?
            ");
            $stmt->execute([$userId]);
            $totalCards = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Silently fail and use default
        }
        
        // Count decks
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM decks WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalDecks = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Silently fail and use default
        }
        
        // Count mastered cards
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM user_card_progress ucp 
                JOIN cards c ON ucp.card_id = c.id 
                JOIN decks d ON c.deck_id = d.id 
                WHERE ucp.user_id = ? AND d.user_id = ? AND ucp.ease_factor > 2.5 AND ucp.`interval` > 30
            ");
            $stmt->execute([$userId, $userId]);
            $masteredCards = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Silently fail and use default
        }
    }
} catch (Exception $e) {
    // Silently fail and use defaults for the stats
}
?>
<?php
// Add this code just before your closing PHP tag, after the existing database queries

// Get study data for the past 7 days
$studyData = [];
$labels = [];

try {
    if ($pdo) {
        // Get last 7 days date range
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($date)); // Format for display
            $studyData[$date] = 0; // Initialize with zero
        }
        
        // Query to get cards studied per day for the last 7 days
        $stmt = $pdo->prepare("
            SELECT 
                DATE(last_reviewed) as study_date,
                COUNT(*) as cards_studied
            FROM 
                user_card_progress
            WHERE 
                user_id = ? 
                AND last_reviewed >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
            GROUP BY 
                DATE(last_reviewed)
            ORDER BY 
                study_date
        ");
        
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update the data array with actual values
        foreach ($results as $row) {
            if (isset($studyData[$row['study_date']])) {
                $studyData[$row['study_date']] = (int)$row['cards_studied'];
            }
        }
    }
} catch (Exception $e) {
    // If there's an error, we'll keep the empty data array
}

// Convert to JSON for JavaScript to use
$studyChartLabels = json_encode(array_values($labels));
$studyChartData = json_encode(array_values($studyData));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FlashBoost</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #F0FFF4;
            --primary-color: #38A169;
            --secondary-color: #2C5282;
            --accent-color: #ECC94B;
            --text-color: #1A202C;
            --card-front: #FFFFFF;
            --card-back: #EDF2F7;
            --border-radius: 10px;
            --box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Modern navbar */
        .navbar {
            padding: 1rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            background-color: var(--card-front);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: -0.5px;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            position: relative;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-color);
            transition: var(--transition);
        }
        
        .nav-links a:hover::after, 
        .nav-links a.active::after {
            width: 100%;
        }
        
        .nav-links a:hover, 
        .nav-links a.active {
            color: var(--primary-color);
        }
        
        /* Main content */
        main {
            flex: 1;
            padding: 2rem 0;
        }
        
        /* Dashboard */
        .dashboard {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--secondary-color);
        }
        
        .date-display {
            font-size: 1rem;
            color: #718096;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Stats container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            padding: 1.8rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--primary-color);
        }
        
        .stat-card:nth-child(2)::before {
            background-color: var(--accent-color);
        }
        
        .stat-card:nth-child(3)::before {
            background-color: var(--secondary-color);
        }
        
        .stat-card:nth-child(4)::before {
            background-color: #805AD5;
        }
        
        .stat-card h3 {
            color: #718096;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .stat-card .stat-icon {
            position: absolute;
            top: 1.8rem;
            right: 1.8rem;
            font-size: 1.2rem;
            color: #CBD5E0;
        }
        
        /* Dashboard sections */
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .dashboard-section {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .dashboard-section:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        /* Due cards list */
        .cards-due-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .due-card-item {
            padding: 1.2rem;
            border-radius: var(--border-radius);
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid #E2E8F0;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .due-card-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: white;
        }
        
        .deck-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .due-content {
            font-size: 0.95rem;
            color: var(--text-color);
        }
        
        /* Module cards */
        .module-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
        }
        
        .module-card {
            padding: 1.5rem;
            border-radius: var(--border-radius);
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid #E2E8F0;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .module-card:hover {
            border-color: var(--secondary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: white;
        }
        
        .module-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .module-card p {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 0.8rem;
            flex-grow: 1;
        }
        
        /* Chart container */
        .chart-container {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            height: 300px;
            position: relative;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            border: 1px dashed #CBD5E0;
            border-radius: var(--border-radius);
            margin-top: 0.5rem;
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 1rem;
        }
        
        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(56, 161, 105, 0.15);
        }
        
        .btn-primary:hover {
            background-color: #2F8A5B;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(56, 161, 105, 0.2);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--text-color);
            border: 1.5px solid #CBD5E0;
        }
        
        .btn-outline:hover {
            border-color: var(--text-color);
            background-color: rgba(0, 0, 0, 0.05);
            transform: translateY(-3px);
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .mt-2 {
            margin-top: 0.75rem;
        }
        
        /* Footer */
        footer {
            padding: 1.5rem 0;
            background-color: var(--card-back);
            text-align: center;
            font-size: 0.9rem;
            margin-top: 2rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
            
            .module-cards {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }
            
            .navbar {
                padding: 1rem 1.5rem;
            }
            
            .nav-links {
                gap: 1.2rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-card .stat-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="modules.php"><i class="fas fa-layer-group"></i> Modules</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container dashboard">
            <div class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($userInfo['username']); ?></h1>
                <p class="date-display"><i class="far fa-calendar-alt"></i> <?php echo date('F j, Y'); ?></p>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                    <h3><i class="fas fa-clone"></i> Total Cards</h3>
                    <div class="stat-value"><?php echo number_format($totalCards); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3><i class="fas fa-history"></i> Due Today</h3>
                    <div class="stat-value"><?php echo number_format($dueCardsCount); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                    <h3><i class="fas fa-award"></i> Mastered</h3>
                    <div class="stat-value"><?php echo number_format($masteredCards); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-folder"></i></div>
                    <h3><i class="fas fa-folder-open"></i> Total Decks</h3>
                    <div class="stat-value"><?php echo number_format($totalDecks); ?></div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-clock"></i> Due Cards</h2>
                        <?php if (!empty($dueCards['cards'])): ?>
                            <a href="study.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-book-open"></i> Study All
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($dueCards['cards'])): ?>
                        <div class="cards-due-list">
                            <?php foreach ($dueCards['cards'] as $card): ?>
                                <div class="due-card-item">
                                    <div class="deck-name">
                                        <i class="fas fa-layer-group"></i> 
                                        <?php echo htmlspecialchars($card['deck_title'] ?? 'Unknown Deck'); ?>
                                    </div>
                                    <div class="due-content">
                                        <?php echo htmlspecialchars(substr($card['front_content'], 0, 50)) . (strlen($card['front_content']) > 50 ? '...' : ''); ?>
                                    </div>
                                    <a href="study.php?id=<?php echo $card['deck_id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-book-reader"></i> Study Deck
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p><i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i> No cards due for review. Great job staying on top of your studies!</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-folder-open"></i> Your Modules</h2>
                        <a href="modules.php" class="btn btn-sm btn-outline">
                            <i class="fas fa-th-list"></i> View All
                        </a>
                    </div>
                    
                    <?php if (!empty($modulesResult['modules'])): ?>
                        <div class="module-cards">
                            <?php foreach (array_slice($modulesResult['modules'], 0, 3) as $module): ?>
                                <div class="module-card">
                                    <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($module['description'], 0, 80)) . (strlen($module['description']) > 80 ? '...' : ''); ?></p>
                                    <a href="module.php?id=<?php echo $module['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-folder-open"></i> View Decks
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p><i class="fas fa-folder-plus" style="margin-right: 0.5rem;"></i> You haven't created any modules yet.</p>
                            <a href="modules.php?action=create" class="btn btn-primary mt-2">
                                <i class="fas fa-plus"></i> Create Module
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Study Progress</h2>
                </div>
                <div class="chart-container">
                    <canvas id="studyProgressChart"></canvas>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> FlashBoost. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
// Dynamic chart data based on study sessions
const ctx = document.getElementById('studyProgressChart').getContext('2d');

// Use the PHP data instead of random values
const labels = <?php echo $studyChartLabels; ?>;
const data = <?php echo $studyChartData; ?>;

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Cards Studied',
            data: data,
            borderColor: '#38A169',
            backgroundColor: 'rgba(56, 161, 105, 0.1)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Daily Study Activity',
                font: {
                    family: 'Inter',
                    weight: 600,
                    size: 14
                }
            },
            tooltip: {
                callbacks: {
                    title: function(tooltipItems) {
                        return tooltipItems[0].label;
                    },
                    label: function(context) {
                        const value = context.raw;
                        return value === 1 ? '1 card studied' : `${value} cards studied`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
    </script>
</body>
</html>