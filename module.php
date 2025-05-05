<?php
require_once 'includes/auth.php';
require_once 'includes/modules.php';
require_once 'includes/decks.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if module ID is provided
$moduleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($moduleId <= 0) {
    header('Location: modules.php');
    exit;
}

// Get module details
$moduleResult = getModuleById($moduleId);
if (!$moduleResult['success']) {
    header('Location: modules.php');
    exit;
}
$module = $moduleResult['module'];

// Get decks in this module
$decksResult = getDecksByModule($moduleId);
$decks = $decksResult['success'] ? $decksResult['decks'] : [];

// Check if we need to create a new deck
$action = isset($_GET['action']) ? $_GET['action'] : '';
$error = '';
$success = false;

if ($action === 'create-deck' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $deckTitle = $_POST['title'] ?? '';
    $deckDescription = $_POST['description'] ?? '';
    
    if (empty($deckTitle)) {
        $error = 'Deck title is required';
    } else {
        $createResult = createDeck($moduleId, $deckTitle, $deckDescription);
        
        if ($createResult['success']) {
            $success = true;
            // Refresh deck list
            $decksResult = getDecksByModule($moduleId);
            $decks = $decksResult['success'] ? $decksResult['decks'] : [];
        } else {
            $error = $createResult['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($module['title']); ?> - FlashBoost</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        /* Module view */
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .module-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background-color: var(--secondary-color);
        }
        
        .module-title h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .module-description {
            margin-bottom: 1rem;
            color: var(--text-color);
            opacity: 0.8;
            font-size: 0.95rem;
            max-width: 600px;
        }
        
        .module-actions {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        
        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
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
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(44, 82, 130, 0.15);
        }
        
        .btn-secondary:hover {
            background-color: #234876;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(44, 82, 130, 0.2);
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
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        /* Card styles */
        .card {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .card-body h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 0.9rem;
            border: 1.5px solid #E2E8F0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: rgba(255, 255, 255, 0.8);
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(56, 161, 105, 0.15);
            background-color: white;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        small {
            display: block;
            color: #718096;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        /* Alert styles */
        .alert {
            padding: 1rem 1.2rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background-color: rgba(229, 62, 62, 0.1);
            border-left: 4px solid #E53E3E;
        }
        
        .alert-success {
            background-color: rgba(56, 161, 105, 0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        .mt-2 {
            margin-top: 0.75rem;
        }
        
        /* Decks section */
        .decks-section {
            margin-top: 2.5rem;
        }
        
        .decks-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .decks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .deck-card {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            padding: 1.8rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .deck-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--primary-color);
            transform: scaleX(0.3);
            transform-origin: left;
            transition: var(--transition);
        }
        
        .deck-card:hover::after {
            transform: scaleX(1);
        }
        
        .deck-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .deck-card h3 {
            font-size: 1.3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .deck-card p {
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        
        .deck-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #718096;
        }
        
        .card-count, .due-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .deck-actions {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        /* Footer */
        footer {
            padding: 1.5rem 0;
            background-color: var(--card-back);
            margin-top: 2rem;
        }
        
        .footer-bottom {
            text-align: center;
            font-size: 0.9rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container.navbar {
                padding: 1rem;
            }
            
            .module-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .decks-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="modules.php" class="active"><i class="fas fa-layer-group"></i> Modules</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container">
            <div class="module-header">
                <div class="module-title">
                    <h1><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($module['title']); ?></h1>
                    <?php if (!empty($module['description'])): ?>
                        <p class="module-description"><?php echo htmlspecialchars($module['description']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="module-actions">
                    <button id="createDeckBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Deck
                    </button>
                    <a href="modules.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Modules
                    </a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error mb-4">
                    <i class="fas fa-exclamation-circle" style="color: #E53E3E; margin-right: 0.8rem;"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.8rem;"></i>
                    <p>Deck created successfully!</p>
                </div>
            <?php endif; ?>
            
            <!-- Deck Creation Form (Hidden by default) -->
            <div id="createDeckForm" class="card mb-4" style="display: none;">
                <div class="card-body">
                    <h3><i class="fas fa-layer-group"></i> Create New Deck</h3>
                    <form action="module.php?id=<?php echo $moduleId; ?>&action=create-deck" method="post">
                        <div class="form-group">
                            <label for="title">Deck Title</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter a descriptive title for your deck" required>
                            <small>For example: "Chapter 1", "Basic Vocabulary", "JavaScript Functions"</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Add a description to help you remember what this deck contains"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancelCreateDeck" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Deck
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Decks List -->
            <div class="decks-section">
                <h2><i class="fas fa-layer-group"></i> Your Decks</h2>
                
                <?php if (count($decks) > 0): ?>
                    <div class="decks-grid">
                        <?php foreach ($decks as $deck): ?>
                            <div class="deck-card">
                                <h3><?php echo htmlspecialchars($deck['title']); ?></h3>
                                <?php if (!empty($deck['description'])): ?>
                                    <p><?php echo htmlspecialchars(substr($deck['description'], 0, 120)) . (strlen($deck['description']) > 120 ? '...' : ''); ?></p>
                                <?php endif; ?>
                                <div class="deck-meta">
                                    <span class="card-count">
                                        <i class="fas fa-clone"></i> <?php echo $deck['card_count']; ?> cards
                                    </span>
                                    <span class="due-count">
                                        <i class="fas fa-clock"></i> <?php echo $deck['due_count']; ?> due
                                    </span>
                                </div>
                                <div class="deck-actions">
                                    <a href="deck.php?id=<?php echo $deck['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-folder-open"></i> View Cards
                                    </a>
                                    <?php if ($deck['due_count'] > 0): ?>
                                        <a href="study.php?id=<?php echo $deck['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-book-open"></i> Study Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-plus" style="font-size: 3.5rem; color: #CBD5E0; margin-bottom: 1rem;"></i>
                        <p>You haven't created any decks in this module yet.</p>
                        <button id="createFirstDeckBtn" class="btn btn-primary mt-2">
                            <i class="fas fa-plus"></i> Create Your First Deck
                        </button>
                    </div>
                <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            const createDeckBtn = document.getElementById('createDeckBtn');
            const createDeckForm = document.getElementById('createDeckForm');
            const cancelCreateDeckBtn = document.getElementById('cancelCreateDeck');
            const createFirstDeckBtn = document.getElementById('createFirstDeckBtn');
            
            function toggleCreateDeckForm() {
                createDeckForm.style.display = createDeckForm.style.display === 'none' ? 'block' : 'none';
            }
            
            if (createDeckBtn) {
                createDeckBtn.addEventListener('click', toggleCreateDeckForm);
            }
            
            if (cancelCreateDeckBtn) {
                cancelCreateDeckBtn.addEventListener('click', toggleCreateDeckForm);
            }
            
            if (createFirstDeckBtn) {
                createFirstDeckBtn.addEventListener('click', function() {
                    createDeckForm.style.display = 'block';
                    createDeckForm.scrollIntoView({ behavior: 'smooth' });
                });
            }
        });
    </script>
</body>
</html>