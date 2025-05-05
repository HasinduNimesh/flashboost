<?php
require_once 'includes/auth.php';
require_once 'includes/decks.php';
require_once 'includes/cards.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if deck ID is provided
$deckId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($deckId <= 0) {
    header('Location: modules.php');
    exit;
}

// Get deck details
$deckResult = getDecks($deckId);
if (!$deckResult['success'] || empty($deckResult['decks'])) {
    header('Location: modules.php');
    exit;
}
$deck = $deckResult['decks'][0];

// Get cards in this deck
$cardsResult = getCardsByDeck($deckId);
$cards = $cardsResult['success'] ? $cardsResult['cards'] : [];

// Handle card creation
$action = isset($_GET['action']) ? $_GET['action'] : '';
$error = '';
$success = '';

// Process card form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add-card') {
        $front = $_POST['front'] ?? '';
        $back = $_POST['back'] ?? '';
        $tags = $_POST['tags'] ?? '';
        
        if (empty($front) || empty($back)) {
            $error = 'Both front and back content are required';
        } else {
            $createResult = createCard($deckId, [
                'front_content' => $front,
                'back_content' => $back,
                'tags' => $tags
            ]);
            
            if ($createResult['success']) {
                $success = 'Card added successfully!';
                // Refresh card list
                $cardsResult = getCardsByDeck($deckId);
                $cards = $cardsResult['success'] ? $cardsResult['cards'] : [];
            } else {
                $error = $createResult['error'];
            }
        }
    } elseif ($action === 'edit-card' && isset($_POST['card_id'])) {
        $cardId = intval($_POST['card_id']);
        $front = $_POST['front'] ?? '';
        $back = $_POST['back'] ?? '';
        $tags = $_POST['tags'] ?? '';
        
        if (empty($front) || empty($back)) {
            $error = 'Both front and back content are required';
        } else {
            $updateResult = updateCard($cardId, [
                'front_content' => $front,
                'back_content' => $back,
                'tags' => $tags
            ]);
            
            if ($updateResult['success']) {
                $success = 'Card updated successfully!';
                // Refresh card list
                $cardsResult = getCardsByDeck($deckId);
                $cards = $cardsResult['success'] ? $cardsResult['cards'] : [];
            } else {
                $error = $updateResult['error'];
            }
        }
    } elseif ($action === 'delete-card' && isset($_POST['card_id'])) {
        $cardId = intval($_POST['card_id']);
        
        $deleteResult = deleteCard($cardId);
        
        if ($deleteResult['success']) {
            $success = 'Card deleted successfully!';
            // Refresh card list
            $cardsResult = getCardsByDeck($deckId);
            $cards = $cardsResult['success'] ? $cardsResult['cards'] : [];
        } else {
            $error = $deleteResult['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($deck['title']); ?> - FlashBoost</title>
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
        
        /* Deck view */
        .deck-view {
            max-width: 1000px;
        }
        
        .deck-header {
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
        
        .deck-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .deck-title h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }
        
        .deck-description {
            margin-bottom: 1rem;
            color: var(--text-color);
            opacity: 0.8;
            font-size: 0.95rem;
            max-width: 600px;
        }
        
        .deck-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.85rem;
            color: #718096;
        }
        
        .deck-module, .card-count, .due-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .deck-actions {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
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
        
        .btn-danger {
            background-color: #E53E3E;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #C53030;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
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
        
        /* Cards table */
        .cards-section {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .cards-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cards-table-container {
            overflow-x: auto;
        }
        
        .cards-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1rem;
        }
        
        .cards-table th {
            text-align: left;
            padding: 1rem;
            background-color: #F7FAFC;
            font-weight: 600;
            color: var(--text-color);
            border-bottom: 2px solid #E2E8F0;
        }
        
        .cards-table td {
            padding: 1rem;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
        }
        
        .cards-table tr:last-child td {
            border-bottom: none;
        }
        
        .cards-table tr:hover {
            background-color: #F9FAFB;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: var(--border-radius);
            border: 1px dashed #CBD5E0;
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 1rem;
        }
        
        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 550px;
            padding: 2rem;
            position: relative;
            animation: slideIn 0.3s ease-out forwards;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-content h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
        }
        
        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
            color: #718096;
        }
        
        .close-modal:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--text-color);
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
        @media (max-width: 768px) {
            .deck-header {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .deck-actions {
                align-self: flex-start;
            }
            
            .cards-table th:nth-child(3),
            .cards-table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="modules.php"><i class="fas fa-layer-group"></i> Modules</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container deck-view">
            <div class="deck-header">
                <div class="deck-title">
                    <h1><?php echo htmlspecialchars($deck['title']); ?></h1>
                    <?php if (!empty($deck['description'])): ?>
                        <p class="deck-description"><?php echo htmlspecialchars($deck['description']); ?></p>
                    <?php endif; ?>
                    <div class="deck-meta">
                        <span class="deck-module"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($deck['module_title']); ?></span>
                        <span class="card-count"><i class="fas fa-layer-group"></i> <?php echo count($cards); ?> cards</span>
                        <span class="due-count"><i class="fas fa-clock"></i> <?php echo $deck['due_count']; ?> due</span>
                    </div>
                </div>
                
                <div class="deck-actions">
                    <button id="addCardBtn" class="btn btn-primary"><i class="fas fa-plus"></i> Add Card</button>
                    <?php if (count($cards) > 0): ?>
                        <a href="study.php?id=<?php echo $deckId; ?>" class="btn btn-secondary"><i class="fas fa-book-open"></i> Study Deck</a>
                    <?php endif; ?>
                    <a href="module.php?id=<?php echo $deck['module_id']; ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Module</a>
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
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Card Creation Form (Hidden by default) -->
            <div id="addCardForm" class="card mb-4" style="display: none;">
                <div class="card-body">
                    <h3>Add New Card</h3>
                    <form action="deck.php?id=<?php echo $deckId; ?>&action=add-card" method="post">
                        <div class="form-group">
                            <label for="front">Front</label>
                            <textarea class="form-control" id="front" name="front" rows="3" placeholder="Enter the question or front side content" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="back">Back</label>
                            <textarea class="form-control" id="back" name="back" rows="3" placeholder="Enter the answer or back side content" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="tags">Tags (comma-separated)</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="e.g. important, chapter1, exam">
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancelAddCard" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Card</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Cards List -->
            <div class="cards-section">
                <h2><i class="fas fa-layer-group"></i> Cards</h2>
                
                <?php if (count($cards) > 0): ?>
                    <div class="cards-table-container">
                        <table class="cards-table">
                            <thead>
                                <tr>
                                    <th>Front</th>
                                    <th>Back</th>
                                    <th>Tags</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cards as $card): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($card['front_content'], 0, 100)) . (strlen($card['front_content']) > 100 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars(substr($card['back_content'], 0, 100)) . (strlen($card['back_content']) > 100 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($card['tags'] ?? ''); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline edit-card-btn" data-id="<?php echo $card['id']; ?>" 
                                                    data-front="<?php echo htmlspecialchars($card['front_content']); ?>" 
                                                    data-back="<?php echo htmlspecialchars($card['back_content']); ?>" 
                                                    data-tags="<?php echo htmlspecialchars($card['tags'] ?? ''); ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-card-btn" data-id="<?php echo $card['id']; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You haven't added any cards to this deck yet.</p>
                        <button id="createFirstCardBtn" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> Create Your First Card</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Edit Card Modal -->
    <div id="editCardModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Edit Card</h3>
            <form action="deck.php?id=<?php echo $deckId; ?>&action=edit-card" method="post">
                <input type="hidden" name="card_id" id="edit_card_id">
                
                <div class="form-group">
                    <label for="edit_front">Front</label>
                    <textarea class="form-control" id="edit_front" name="front" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_back">Back</label>
                    <textarea class="form-control" id="edit_back" name="back" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_tags">Tags (comma-separated)</label>
                    <input type="text" class="form-control" id="edit_tags" name="tags">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="cancel-edit-btn btn btn-outline"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Card Modal -->
    <div id="deleteCardModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Delete Card</h3>
            <p>Are you sure you want to delete this card? This action cannot be undone.</p>
            <form action="deck.php?id=<?php echo $deckId; ?>&action=delete-card" method="post">
                <input type="hidden" name="card_id" id="delete_card_id">
                
                <div class="form-actions">
                    <button type="button" class="cancel-delete-btn btn btn-outline"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Card</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> FlashBoost. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add Card Toggle
            const addCardBtn = document.getElementById('addCardBtn');
            const addCardForm = document.getElementById('addCardForm');
            const cancelAddCardBtn = document.getElementById('cancelAddCard');
            const createFirstCardBtn = document.getElementById('createFirstCardBtn');
            
            function toggleAddCardForm() {
                addCardForm.style.display = addCardForm.style.display === 'none' ? 'block' : 'none';
            }
            
            if (addCardBtn) {
                addCardBtn.addEventListener('click', toggleAddCardForm);
            }
            
            if (cancelAddCardBtn) {
                cancelAddCardBtn.addEventListener('click', toggleAddCardForm);
            }
            
            if (createFirstCardBtn) {
                createFirstCardBtn.addEventListener('click', function() {
                    addCardForm.style.display = 'block';
                    addCardForm.scrollIntoView({ behavior: 'smooth' });
                });
            }
            
            // Edit Card Modal
            const editModal = document.getElementById('editCardModal');
            const editBtns = document.querySelectorAll('.edit-card-btn');
            const cancelEditBtns = document.querySelectorAll('.cancel-edit-btn');
            const closeEditBtns = document.querySelectorAll('#editCardModal .close-modal');
            
            editBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const front = this.getAttribute('data-front');
                    const back = this.getAttribute('data-back');
                    const tags = this.getAttribute('data-tags');
                    
                    document.getElementById('edit_card_id').value = id;
                    document.getElementById('edit_front').value = front;
                    document.getElementById('edit_back').value = back;
                    document.getElementById('edit_tags').value = tags;
                    
                    editModal.style.display = 'block';
                });
            });
            
            function closeEditModal() {
                editModal.style.display = 'none';
            }
            
            cancelEditBtns.forEach(btn => {
                btn.addEventListener('click', closeEditModal);
            });
            
            closeEditBtns.forEach(btn => {
                btn.addEventListener('click', closeEditModal);
            });
            
            // Delete Card Modal
            const deleteModal = document.getElementById('deleteCardModal');
            const deleteBtns = document.querySelectorAll('.delete-card-btn');
            const cancelDeleteBtns = document.querySelectorAll('.cancel-delete-btn');
            const closeDeleteBtns = document.querySelectorAll('#deleteCardModal .close-modal');
            
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('delete_card_id').value = id;
                    deleteModal.style.display = 'block';
                });
            });
            
            function closeDeleteModal() {
                deleteModal.style.display = 'none';
            }
            
            cancelDeleteBtns.forEach(btn => {
                btn.addEventListener('click', closeDeleteModal);
            });
            
            closeDeleteBtns.forEach(btn => {
                btn.addEventListener('click', closeDeleteModal);
            });
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === editModal) {
                    closeEditModal();
                }
                if (event.target === deleteModal) {
                    closeDeleteModal();
                }
            });
        });
    </script>
</body>
</html>