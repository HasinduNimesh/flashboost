<?php
require_once 'includes/auth.php';
require_once 'includes/cards.php';
require_once 'includes/decks.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get parameters
$deckId = isset($_GET['deck']) ? intval($_GET['deck']) : 0;
$cardId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $cardId > 0 ? 'edit' : 'add';

// Validate deck
if ($deckId <= 0) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $frontContent = $_POST['front_content'] ?? '';
    $backContent = $_POST['back_content'] ?? '';
    $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
    
    // Clean up tags
    $tags = array_map('trim', $tags);
    $tags = array_filter($tags, function($tag) { return !empty($tag); });
    
    if (empty($frontContent) || empty($backContent)) {
        $error = 'Front and back content are required';
    } else {
        if ($action === 'add') {
            $result = addCard($deckId, $frontContent, $backContent, $tags);
        } else {
            $result = editCard($cardId, [
                'frontContent' => $frontContent,
                'backContent' => $backContent,
                'tags' => $tags
            ]);
        }
        
        if ($result['success']) {
            $success = true;
        } else {
            $error = $result['error'];
        }
    }
}

// If editing, get card details
$card = null;
if ($action === 'edit' && $cardId > 0) {
    $cardsResult = getCards($deckId);
    if ($cardsResult['success']) {
        foreach ($cardsResult['cards'] as $c) {
            if ($c['id'] == $cardId) {
                $card = $c;
                break;
            }
        }
    }
    
    if (!$card) {
        header('Location: deck.php?id=' . $deckId);
        exit;
    }
}

// Get deck info
$deckResult = getDecks($deckId);
if (!$deckResult['success']) {
    header('Location: dashboard.php');
    exit;
}

$deck = $deckResult['decks'][0] ?? null;
if (!$deck) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'add' ? 'Add New Card' : 'Edit Card'; ?> - FlashBoost</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="modules.php">Modules</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="card-editor">
            <div class="editor-header">
                <h1><?php echo $action === 'add' ? 'Add New Card' : 'Edit Card'; ?></h1>
                <p>Deck: <strong><?php echo htmlspecialchars($deck['title']); ?></strong></p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success mb-3">
                    <p>Card successfully <?php echo $action === 'add' ? 'added' : 'updated'; ?>!</p>
                </div>
                
                <div class="action-buttons mb-4">
                    <a href="deck.php?id=<?php echo $deckId; ?>" class="btn btn-outline">Back to Deck</a>
                    <a href="card-editor.php?deck=<?php echo $deckId; ?>" class="btn btn-primary">Add Another Card</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error mb-3">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
                    <div class="editor-field">
                        <label for="front_content">Front Side (Question)</label>
                        <textarea class="editor-textarea" id="front_content" name="front_content" required><?php echo htmlspecialchars($card['front_content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="editor-field">
                        <label for="back_content">Back Side (Answer)</label>
                        <textarea class="editor-textarea" id="back_content" name="back_content" required><?php echo htmlspecialchars($card['back_content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="editor-field">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars(implode(', ', $card['tags'] ?? [])); ?>">
                    </div>
                    
                    <div class="editor-actions">
                        <a href="deck.php?id=<?php echo $deckId; ?>" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add Card' : 'Save Changes'; ?></button>
                    </div>
                </form>
            <?php endif; ?>
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
        // Simple tag input functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add more JavaScript for dynamic tag input if needed
        });
    </script>
</body>
</html>