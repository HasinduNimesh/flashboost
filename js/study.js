document.addEventListener('DOMContentLoaded', function() {
    const studyApp = document.getElementById('studyApp');
    if (!studyApp) return;
    
    const deckId = studyApp.getAttribute('data-deck-id');
    let currentCards = [];
    let currentIndex = 0;
    let sessionId = null;
    
    // Initialize the study session
    initStudySession();
    
    function initStudySession() {
        // Start a new study session
        fetch(`api/start-session.php${deckId ? '?deck_id=' + deckId : ''}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    sessionId = data.session_id;
                    return fetchDueCards();
                } else {
                    throw new Error(data.error || 'Failed to start study session');
                }
            })
            .catch(error => {
                showError('Error starting session: ' + error.message);
            });
    }
    
    function fetchDueCards() {
        fetch(`api/fetch-due-cards.php?session_id=${sessionId}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success && data.cards && data.cards.length > 0) {
                    currentCards = data.cards;
                    renderStudyInterface();
                    showCurrentCard();
                } else {
                    renderCompletionMessage();
                }
            })
            .catch(error => {
                showError('Error fetching cards: ' + error.message);
            });
    }
    
    function renderStudyInterface() {
        studyApp.innerHTML = `
            <div class="study-progress slide-up">
                <div class="progress-indicator">
                    <span>Card <span id="currentCardNum">1</span> of <span id="totalCards">${currentCards.length}</span></span>
                    <span id="deckTitle">${currentCards[0]?.deck_title || 'Flashcards'}</span>
                </div>
                <div class="progress-bar">
                    <div id="progressFill" class="progress-fill"></div>
                </div>
            </div>
            
            <div class="flashcard-container fade-in">
                <div class="flashcard" id="flashcard">
                    <div class="flashcard-side flashcard-front">
                        <div class="card-content" id="cardFront"></div>
                    </div>
                    <div class="flashcard-side flashcard-back">
                        <div class="card-content" id="cardBack"></div>
                    </div>
                </div>
            </div>
            
            <div class="card-controls slide-up">
                <div class="primary-controls">
                    <button id="showAnswerBtn" class="show-answer-btn">
                        <i class="bi bi-eye"></i> Show Answer
                    </button>
                    <button id="nextCardBtn" class="next-card-btn" style="display: none;">
                        <span>Next Card</span> <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
                
                <div id="ratingContainer" class="rating-buttons" style="display: none;">
                    <button type="button" class="rating-button rating-0" data-rating="0">
                        <i class="bi bi-emoji-frown"></i> Again
                    </button>
                    <button type="button" class="rating-button rating-1" data-rating="1">
                        <i class="bi bi-emoji-neutral"></i> Hard
                    </button>
                    <button type="button" class="rating-button rating-2" data-rating="2">
                        <i class="bi bi-emoji-smile"></i> Good
                    </button>
                    <button type="button" class="rating-button rating-3" data-rating="3">
                        <i class="bi bi-emoji-smile-fill"></i> Easy
                    </button>
                    <button type="button" class="rating-button rating-4" data-rating="4">
                        <i class="bi bi-emoji-laughing-fill"></i> Very Easy
                    </button>
                    <button type="button" class="rating-button rating-5" data-rating="5">
                        <i class="bi bi-emoji-sunglasses-fill"></i> Perfect
                    </button>
                </div>
            </div>
        `;
        
        // Add event listeners
        document.getElementById('showAnswerBtn').addEventListener('click', showAnswer);
        document.getElementById('nextCardBtn').addEventListener('click', nextCard);
        
        document.querySelectorAll('.rating-button').forEach(button => {
            button.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                
                // Highlight selected button
                document.querySelectorAll('.rating-button').forEach(btn => 
                    btn.classList.remove('selected'));
                this.classList.add('selected');
                
                // Slight delay for better UX
                setTimeout(() => {
                    recordAnswer(rating);
                }, 300);
            });
        });
    }
    
    function showCurrentCard() {
        const card = currentCards[currentIndex];
        const cardFront = document.getElementById('cardFront');
        const cardBack = document.getElementById('cardBack');
        
        // Clear previous content first
        cardFront.innerHTML = '';
        cardBack.innerHTML = '';
        
        // Animation for new content
        setTimeout(() => {
            cardFront.innerHTML = card.front_content;
            cardBack.innerHTML = card.back_content;
            
            // Add animations to content
            cardFront.classList.add('fade-in');
            cardBack.classList.add('fade-in');
            
            // Remove animation classes after animation completes
            setTimeout(() => {
                cardFront.classList.remove('fade-in');
                cardBack.classList.remove('fade-in');
            }, 500);
        }, 50);
        
        document.getElementById('currentCardNum').textContent = currentIndex + 1;
        
        // Reset card state
        const flashcard = document.getElementById('flashcard');
        flashcard.classList.remove('flipped');
        
        document.getElementById('showAnswerBtn').style.display = 'inline-flex';
        document.getElementById('nextCardBtn').style.display = 'none';
        document.getElementById('ratingContainer').style.display = 'none';
        
        // Update progress bar with animation
        const progressPercentage = (currentIndex / currentCards.length) * 100;
        const progressFill = document.getElementById('progressFill');
        progressFill.style.width = progressPercentage + '%';
    }
    
    function showAnswer() {
        const flashcard = document.getElementById('flashcard');
        flashcard.classList.add('flipped');
        
        document.getElementById('showAnswerBtn').style.display = 'none';
        document.getElementById('nextCardBtn').style.display = 'inline-flex';
        
        // Show rating buttons with animation
        const ratingContainer = document.getElementById('ratingContainer');
        ratingContainer.style.display = 'flex';
        ratingContainer.classList.add('slide-up');
        
        // Remove animation class after animation completes
        setTimeout(() => {
            ratingContainer.classList.remove('slide-up');
        }, 500);
    }
    
    function recordAnswer(rating) {
        const card = currentCards[currentIndex];
        
        // Visual feedback - disable buttons while processing
        document.querySelectorAll('.rating-button').forEach(btn => {
            btn.disabled = true;
        });
        
        fetch('api/record-answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                card_id: card.id,
                rating: rating
            })
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                nextCard();
            } else {
                throw new Error(data.error || 'Failed to record answer');
            }
        })
        .catch(error => {
            showError('Error saving progress: ' + error.message);
            
            // Re-enable buttons if there was an error
            document.querySelectorAll('.rating-button').forEach(btn => {
                btn.disabled = false;
            });
        });
    }
    
    function nextCard() {
        currentIndex++;
        
        if (currentIndex < currentCards.length) {
            showCurrentCard();
        } else {
            // End of session
            endSession();
            renderCompletionMessage();
        }
    }
    
    function endSession() {
        fetch(`api/end-session.php?session_id=${sessionId}`)
            .catch(error => console.error('Error ending session:', error));
    }
    
    function renderCompletionMessage() {
        studyApp.innerHTML = `
            <div class="completion-message fade-in">
                <i class="bi bi-check-circle-fill completion-icon"></i>
                <h2>Great job!</h2>
                <p>You've completed all your due cards for today.</p>
                <div class="confetti"></div>
                <div class="button-group">
                    ${deckId ? 
                        `<a href="deck.php?id=${deckId}" class="back-button">
                            <i class="bi bi-collection"></i> Back to Deck
                         </a>` : 
                        `<a href="dashboard.php" class="back-button">
                            <i class="bi bi-speedometer2"></i> Back to Dashboard
                         </a>`
                    }
                    <a href="dashboard.php" class="show-answer-btn">
                        <i class="bi bi-house"></i> Go Home
                    </a>
                </div>
            </div>
        `;
        
        // Create confetti effect
        createConfetti();
    }
    
    function createConfetti() {
        const confettiContainer = document.querySelector('.confetti');
        const colors = ['#4CAF50', '#2196F3', '#FFC107', '#F44336', '#9C27B0'];
        
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.style.width = Math.random() * 10 + 5 + 'px';
            confetti.style.height = Math.random() * 10 + 5 + 'px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.position = 'absolute';
            confetti.style.top = '-20px';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.opacity = Math.random() * 0.7 + 0.3;
            confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
            confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
            
            const animation = confetti.animate(
                [
                    { transform: `translate(0, 0) rotate(0deg)`, opacity: 1 },
                    { transform: `translate(${Math.random() * 100 - 50}px, ${Math.random() * 200 + 200}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                ],
                {
                    duration: Math.random() * 2000 + 2000,
                    easing: 'cubic-bezier(0.215, 0.61, 0.355, 1)',
                    delay: Math.random() * 1500
                }
            );
            
            animation.onfinish = () => confetti.remove();
            confettiContainer.appendChild(confetti);
        }
    }
    
    function showError(message) {
        studyApp.innerHTML = `
            <div class="alert alert-error fade-in">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <p>${message}</p>
                <div class="button-group">
                    ${deckId ? 
                        `<a href="deck.php?id=${deckId}" class="back-button">
                            <i class="bi bi-collection"></i> Back to Deck
                         </a>` : 
                        `<a href="dashboard.php" class="back-button">
                            <i class="bi bi-speedometer2"></i> Back to Dashboard
                         </a>`
                    }
                    <button onclick="location.reload()" class="show-answer-btn">
                        <i class="bi bi-arrow-clockwise"></i> Try Again
                    </button>
                </div>
            </div>
        `;
    }
});