<?php
require_once 'includes/auth.php';
require_once 'includes/decks.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get deck ID
$deckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get deck information if specified
$deckInfo = null;
if ($deckId > 0) {
    $deckResult = getDecks($deckId);
    if ($deckResult['success'] && !empty($deckResult['decks'])) {
        $deckInfo = $deckResult['decks'][0];
    } else {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study <?php echo $deckInfo ? htmlspecialchars($deckInfo['title']) : 'All Cards'; ?> - FlashBoost</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/study.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
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
        
        /* Navbar styling */
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
        
        /* Study header */
        .study-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 2rem;
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .study-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background-color: var(--secondary-color);
        }
        
        .study-title-group h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .deck-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .deck-description {
            color: var(--text-color);
            opacity: 0.8;
            font-size: 0.95rem;
            max-width: 700px;
        }
        
        .progress-info {
            display: flex;
            gap: 1.5rem;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .progress-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
        }
        
        .back-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--secondary-color);
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-radius: var(--border-radius);
            background-color: var(--card-front);
            transition: var(--transition);
            border: 1.5px solid #E2E8F0;
            white-space: nowrap;
        }
        
        .back-button:hover {
            border-color: var(--secondary-color);
            background-color: rgba(44, 82, 130, 0.05);
            transform: translateX(-3px);
        }
        
        /* Study container */
        .study-container {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            min-height: 500px;
            padding: 0;
            position: relative;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .study-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        /* Flashcard styling */
        .flashcard {
            width: 100%;
            height: 400px;
            perspective: 1000px;
            position: relative;
        }
        
        .flashcard-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }
        
        .flashcard.flipped .flashcard-inner {
            transform: rotateY(180deg);
        }
        
        .flashcard-front, .flashcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .flashcard-front {
            background-color: var(--card-front);
            color: var(--text-color);
            font-size: 1.5rem;
            font-weight: 500;
        }
        
        .flashcard-back {
            background-color: var(--card-back);
            color: var(--text-color);
            transform: rotateY(180deg);
            font-size: 1.5rem;
            font-weight: 500;
        }
        
        .card-content {
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
        }
        
        /* Answer buttons */
        .answer-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 1.5rem;
            background-color: rgba(237, 242, 247, 0.5);
            border-top: 1px solid #E2E8F0;
            flex-wrap: wrap;
        }
        
        .answer-btn {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-again {
            background-color: #FC8181;
            color: white;
        }
        
        .btn-again:hover {
            background-color: #E53E3E;
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(229, 62, 62, 0.2);
        }
        
        .btn-hard {
            background-color: #F6AD55;
            color: white;
        }
        
        .btn-hard:hover {
            background-color: #DD6B20;
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(221, 107, 32, 0.2);
        }
        
        .btn-good {
            background-color: #68D391;
            color: white;
        }
        
        .btn-good:hover {
            background-color: #38A169;
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(56, 161, 105, 0.2);
        }
        
        .btn-easy {
            background-color: #63B3ED;
            color: white;
        }
        
        .btn-easy:hover {
            background-color: #2C5282;
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(44, 82, 130, 0.2);
        }
        
        .btn-show-answer {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(56, 161, 105, 0.15);
            padding: 0.9rem 2rem;
            font-size: 1rem;
        }
        
        .btn-show-answer:hover {
            background-color: #2F8A5B;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(56, 161, 105, 0.2);
        }
        
        /* Loading indicator */
        .loading-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 400px;
            gap: 1.5rem;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(56, 161, 105, 0.2);
            border-left: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-indicator p {
            color: var(--text-color);
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Study results */
        .study-results {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 3rem 2rem;
            gap: 1.5rem;
        }
        
        .results-icon {
            font-size: 3rem;
            color: var(--primary-color);
            height: 80px;
            width: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(56, 161, 105, 0.1);
            margin-bottom: 0.5rem;
        }
        
        .study-results h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
        }
        
        .study-results p {
            font-size: 1.1rem;
            max-width: 500px;
            color: #718096;
        }
        
        .results-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
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
        
        /* Progress bar */
        .progress-container {
            width: 100%;
            height: 6px;
            background-color: #E2E8F0;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 10;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            transition: width 0.5s ease;
        }
        
        /* Footer */
        footer {
            padding: 1.5rem 0;
            background-color: var(--card-back);
            text-align: center;
            font-size: 0.9rem;
            margin-top: auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem 1.5rem;
            }
            
            .nav-links {
                gap: 1.2rem;
            }
            
            .study-header {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1.5rem;
            }
            
            .back-button {
                align-self: flex-start;
            }
            
            .flashcard-front, .flashcard-back {
                padding: 1.5rem;
                font-size: 1.2rem;
            }
            
            .answer-buttons {
                gap: 0.8rem;
            }
            
            .answer-btn {
                min-width: 100px;
                padding: 0.6rem 1rem;
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
                <a href="modules.php"><i class="fas fa-layer-group"></i> Modules</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container">
            <div class="study-header">
                <div class="study-title-group">
                    <h1><i class="fas fa-book-open"></i> Studying: <?php echo $deckInfo ? htmlspecialchars($deckInfo['title']) : 'All Due Cards'; ?></h1>
                    <div class="deck-meta">
                        <?php if ($deckInfo && !empty($deckInfo['description'])): ?>
                            <p class="deck-description"><?php echo htmlspecialchars($deckInfo['description']); ?></p>
                        <?php endif; ?>
                        <div class="progress-info">
                            <div class="progress-item">
                                <i class="fas fa-check-circle" style="color: var(--primary-color);"></i>
                                <span>Remaining: <strong id="remaining-count">-</strong></span>
                            </div>
                            <div class="progress-item">
                                <i class="fas fa-history" style="color: var(--accent-color);"></i>
                                <span>Reviewed: <strong id="reviewed-count">-</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="<?php echo $deckInfo ? "deck.php?id={$deckId}" : "dashboard.php"; ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i> 
                    <span>Back to <?php echo $deckInfo ? 'Deck' : 'Dashboard'; ?></span>
                </a>
            </div>
            
            <div id="studyApp" data-deck-id="<?php echo $deckId; ?>" class="study-container">
                <div class="progress-container">
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
                <div class="loading-indicator">
                    <div class="spinner"></div>
                    <p>Loading your cards...</p>
                </div>
                
                <!-- Flashcard will be inserted by JavaScript -->
                <!-- For example:
                <div class="flashcard">
                    <div class="flashcard-inner">
                        <div class="flashcard-front">
                            <div class="card-content">Front content</div>
                        </div>
                        <div class="flashcard-back">
                            <div class="card-content">Back content</div>
                        </div>
                    </div>
                </div>
                <div class="answer-buttons">
                    <button class="btn-show-answer">Show Answer</button>
                </div>
                -->
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> FlashBoost. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="js/study.js"></script>
</body>
</html>