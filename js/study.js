document.addEventListener('DOMContentLoaded', function() {
    const studyApp = document.getElementById('studyApp');
    if (!studyApp) return;
    
    const deckId = studyApp.getAttribute('data-deck-id');
    const studyMode = studyApp.getAttribute('data-study-mode') || 'all';
    let currentCards = [];
    let currentIndex = 0;
    let sessionId = null;
    let cardsReviewed = 0;
    let isIgnoringSchedule = false;
    
    console.log("Starting study session with mode:", studyMode);
    
    // Initialize the study session
    initStudySession();
    
    function initStudySession() {
        // Show loading indicator
        studyApp.innerHTML = `
            <div class="progress-container">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
            <div class="loading-indicator">
                <div class="spinner"></div>
                <p>Loading your cards...</p>
            </div>
        `;
        
        // Start a new study session
        fetch(`api/start-session.php${deckId ? '?deck_id=' + deckId : ''}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    sessionId = data.session_id;
                    console.log("Session started successfully:", sessionId);
                    return fetchCards(false, studyMode);
                } else {
                    throw new Error(data.error || 'Failed to start study session');
                }
            })
            .catch(error => {
                showError('Error starting session: ' + error.message);
                console.error("Session start error:", error);
            });
    }
    
    function fetchCards(ignoreSchedule = false, mode = 'all') {
        console.log(`Fetching cards... (ignore_schedule=${ignoreSchedule ? '1' : '0'}, mode=${mode})`);
        isIgnoringSchedule = ignoreSchedule;
        
        // Set a high limit to get all cards
        const limit = 100;
        
        fetch(`api/fetch-due-cards.php?session_id=${sessionId}&ignore_schedule=${ignoreSchedule ? '1' : '0'}&study_mode=${mode}&limit=${limit}${deckId ? '&deck_id=' + deckId : ''}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log("API Response:", data);
                
                if (data.success && data.cards && data.cards.length > 0) {
                    currentCards = data.cards;
                    console.log(`Loaded ${currentCards.length} cards for study (out of ${data.total_in_deck || 'unknown'} total in deck)`);
                    currentIndex = 0;
                    renderStudyInterface();
                    showCurrentCard();
                    updateCounters();
                    
                    if (ignoreSchedule) {
                        showToast('Studying additional cards - these aren\'t due yet!', 'info');
                    }
                } else {
                    if (ignoreSchedule) {
                        showError('No more cards available to study in this deck.');
                    } else {
                        console.log("No cards to study");
                        renderCompletionMessage();
                    }
                }
            })
            .catch(error => {
                showError('Error fetching cards: ' + error.message);
                console.error("Card fetch error:", error);
            });
    }
    
    function renderStudyInterface() {
        studyApp.innerHTML = `
            <div class="progress-container">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
            <div class="study-progress">
                <div class="progress-indicator">
                    <span>Card <span id="currentCardNum">1</span> of <span id="totalCards">${currentCards.length}</span></span>
                    <span id="deckTitle">${currentCards[0]?.deck_title || 'Flashcards'}</span>
                </div>
            </div>
            
            <div class="flashcard-container">
                <div class="flashcard" id="flashcard">
                    ${isIgnoringSchedule ? '<span class="card-tag bonus">Bonus Study</span>' : ''}
                    <div class="flashcard-side flashcard-front">
                        <div class="card-content" id="cardFront"></div>
                    </div>
                    <div class="flashcard-side flashcard-back">
                        <div class="card-content" id="cardBack"></div>
                    </div>
                </div>
            </div>
            
            <div class="card-controls">
                <div class="primary-controls">
                    <button id="showAnswerBtn" class="btn-show-answer">
                        <i class="fas fa-eye"></i> Show Answer
                    </button>
                    <button id="nextCardBtn" class="next-card-btn" style="display: none;">
                        <span>Next Card</span> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <div id="ratingContainer" class="answer-buttons" style="display: none;">
                    <button type="button" class="answer-btn btn-again rating-button" data-rating="0">
                        <i class="fas fa-frown"></i> Again
                    </button>
                    <button type="button" class="answer-btn btn-hard rating-button" data-rating="1">
                        <i class="fas fa-meh"></i> Hard
                    </button>
                    <button type="button" class="answer-btn btn-good rating-button" data-rating="2">
                        <i class="fas fa-smile"></i> Good
                    </button>
                    <button type="button" class="answer-btn btn-easy rating-button" data-rating="3">
                        <i class="fas fa-laugh"></i> Easy
                    </button>
                    <button type="button" class="answer-btn rating-button" data-rating="4">
                        <i class="fas fa-laugh-beam"></i> Very Easy
                    </button>
                    <button type="button" class="answer-btn rating-button" data-rating="5">
                        <i class="fas fa-laugh-squint"></i> Perfect
                    </button>
                </div>
            </div>
            
            <div class="study-stats">
                <div class="stat-item">
                    <i class="fas fa-check-circle"></i> 
                    <span>Remaining: <strong id="remaining-count">${currentCards.length}</strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-history"></i> 
                    <span>Reviewed: <strong id="reviewed-count">${cardsReviewed}</strong></span>
                </div>
            </div>
        `;
        
        // Add event listeners
        document.getElementById('showAnswerBtn').addEventListener('click', showAnswer);
        document.getElementById('nextCardBtn').addEventListener('click', nextCard);
        
        // Use event delegation for rating buttons
        document.getElementById('ratingContainer').addEventListener('click', function(event) {
            const button = event.target.closest('.rating-button');
            if (!button || button.disabled) return;
            
            const rating = parseInt(button.getAttribute('data-rating'));
            console.log(`Rating selected: ${rating}`);
            
            // Highlight selected button and add animation
            document.querySelectorAll('.rating-button').forEach(btn => 
                btn.classList.remove('selected'));
            button.classList.add('selected');
            button.classList.add('pulse');
            
            // Slight delay for better UX
            setTimeout(() => {
                button.classList.remove('pulse');
                recordAnswer(rating);
            }, 300);
        });
    }
    
    function showCurrentCard() {
        if (currentIndex >= currentCards.length) {
            console.error(`Invalid card index: ${currentIndex}, total cards: ${currentCards.length}`);
            endSession();
            renderCompletionMessage();
            return;
        }
        
        const card = currentCards[currentIndex];
        const cardFront = document.getElementById('cardFront');
        const cardBack = document.getElementById('cardBack');
        
        console.log(`Showing card ${currentIndex + 1}/${currentCards.length}`);
        
        // Clear previous content
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
        
        // Update card number
        document.getElementById('currentCardNum').textContent = currentIndex + 1;
        
        // Reset card state
        const flashcard = document.getElementById('flashcard');
        flashcard.classList.remove('flipped');
        
        // Reset buttons
        document.getElementById('showAnswerBtn').style.display = 'inline-flex';
        document.getElementById('nextCardBtn').style.display = 'none';
        document.getElementById('ratingContainer').style.display = 'none';
        
        // Reset rating buttons
        document.querySelectorAll('.rating-button').forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('disabled');
            btn.classList.remove('selected');
        });
        
        // Update progress bar
        const progressPercentage = (currentIndex / currentCards.length) * 100;
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) progressBar.style.width = `${progressPercentage}%`;
        
        // Update counters
        updateCounters();
    }
    
    function updateCounters() {
        // Update the remaining and reviewed counters
        const remainingEl = document.getElementById('remaining-count');
        const reviewedEl = document.getElementById('reviewed-count');
        
        if (remainingEl) {
            // Calculate how many cards are left (not including the current one)
            const remaining = Math.max(0, currentCards.length - currentIndex);
            remainingEl.textContent = remaining;
        }
        
        if (reviewedEl) {
            reviewedEl.textContent = cardsReviewed;
        }
    }
    
    function showAnswer() {
        const flashcard = document.getElementById('flashcard');
        flashcard.classList.add('flipped');
        
        document.getElementById('showAnswerBtn').style.display = 'none';
        document.getElementById('nextCardBtn').style.display = 'inline-flex';
        
        // Show rating buttons
        const ratingContainer = document.getElementById('ratingContainer');
        ratingContainer.style.display = 'flex';
        ratingContainer.classList.add('slide-up');
        
        // Remove animation class after animation completes
        setTimeout(() => {
            ratingContainer.classList.remove('slide-up');
        }, 500);
    }
    
    function recordAnswer(rating) {
        if (currentIndex >= currentCards.length) {
            console.error("Trying to record answer for invalid card index");
            return;
        }
        
        const card = currentCards[currentIndex];
        const isLastCard = currentIndex >= currentCards.length - 1;
        
        // Add loading class for visual feedback
        const ratingContainer = document.getElementById('ratingContainer');
        if (ratingContainer) {
            ratingContainer.classList.add('loading');
        }
        
        // Disable buttons while processing
        document.querySelectorAll('.rating-button').forEach(btn => {
            btn.disabled = true;
            btn.classList.add('disabled');
        });
        
        console.log(`Sending rating ${rating} for card ID ${card.id} (${currentIndex + 1}/${currentCards.length})`);
        
        // Set up safety timeout
        const timeoutId = setTimeout(() => {
            if (ratingContainer && ratingContainer.classList.contains('loading')) {
                console.warn("Buttons were stuck in loading state - forcing reset");
                ratingContainer.classList.remove('loading');
                
                document.querySelectorAll('.rating-button').forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                });
                
                showToast('Request timed out. Please try again.', 'error');
            }
        }, 10000); // 10 second timeout
        
        fetch('api/record-answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                card_id: card.id,
                rating: rating,
                is_last_card: isLastCard,
                ignore_schedule: isIgnoringSchedule,
                current_index: currentIndex,
                total_cards: currentCards.length
            })
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                console.error(`Server response error: ${response.status}`);
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Rating recorded:", data);
            if (data.success) {
                // Increment the reviewed counter
                cardsReviewed++;
                
                // Clear loading state
                if (ratingContainer) {
                    ratingContainer.classList.remove('loading');
                }
                
                // Flash success message
                showToast('Answer recorded!', 'success');
                
                // Move to next card with slight delay for feedback
                setTimeout(() => {
                    nextCard();
                }, 500);
            } else {
                throw new Error(data.error || 'Failed to record answer');
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error("Error recording answer:", error);
            
            // Re-enable buttons if there was an error
            if (ratingContainer) {
                ratingContainer.classList.remove('loading');
            }
            
            document.querySelectorAll('.rating-button').forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('disabled');
            });
            
            showToast('Failed to save your rating. Please try again.', 'error');
        });
    }
    
    function nextCard() {
        currentIndex++;
        
        console.log(`Moving to next card: ${currentIndex} of ${currentCards.length}`);
        
        // IMPORTANT: The correct check to see if we've reached the end
        if (currentIndex < currentCards.length) {
            showCurrentCard();
        } else {
            console.log("Reached the end of cards!");
            endSession();
            renderCompletionMessage();
        }
    }
    
    function showToast(message, type = 'info') {
        // Create toast element if it doesn't exist
        let toast = document.getElementById('study-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'study-toast';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        
        // Set message and type
        toast.textContent = message;
        toast.className = `toast toast-${type} show-toast`;
        
        // Hide after 3 seconds
        setTimeout(() => {
            toast.className = toast.className.replace('show-toast', '');
        }, 3000);
    }
    
    function endSession() {
        console.log("Ending study session...");
        fetch(`api/end-session.php?session_id=${sessionId}`)
            .then(response => response.json())
            .then(data => console.log("Session ended:", data))
            .catch(error => console.error('Error ending session:', error));
    }
    
    function renderCompletionMessage() {
        // Clear any loading states
        const loadingElements = document.querySelectorAll('.loading');
        loadingElements.forEach(el => el.classList.remove('loading'));
        
        studyApp.innerHTML = `
            <div class="completion-message fade-in">
                <i class="fas fa-check-circle completion-icon"></i>
                <h2>Great job!</h2>
                <p>You've completed ${isIgnoringSchedule ? 'studying these cards' : 'all your cards for today'}.</p>
                <div class="stats-summary">
                    <div class="summary-item">
                        <span class="summary-label">Cards Studied</span>
                        <span class="summary-value">${cardsReviewed}</span>
                    </div>
                </div>
                <div class="confetti"></div>
                <div class="study-options">
                    <p class="options-label">What would you like to do next?</p>
                    <div class="button-group">
                        ${deckId ? 
                            `<a href="deck.php?id=${deckId}" class="btn btn-outline">
                                <i class="fas fa-layer-group"></i> Back to Deck
                             </a>` : 
                            `<a href="dashboard.php" class="btn btn-outline">
                                <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                             </a>`
                        }
                        <a href="dashboard.php" class="btn btn-outline">
                            <i class="fas fa-home"></i> Go Home
                        </a>
                        <button id="studyMoreBtn" class="btn btn-primary">
                            <i class="fas fa-book"></i> Study More Cards
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Create confetti effect
        createConfetti();
        
        // Add event listener for Study More button
        document.getElementById('studyMoreBtn').addEventListener('click', studyMoreCards);
    }
    
    function studyMoreCards() {
        // Show loading indicator
        studyApp.innerHTML = `
            <div class="progress-container">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
            <div class="loading-indicator">
                <div class="spinner"></div>
                <p>Finding more cards to study...</p>
            </div>
        `;
        
        // Reset index but keep track of total studied
        currentIndex = 0;
        
        // Fetch cards ignoring the schedule - use 'all' study mode
        fetchCards(true, 'all');
    }
    
    function createConfetti() {
        const confettiContainer = document.createElement('div');
        confettiContainer.className = 'confetti';
        document.querySelector('.completion-message').appendChild(confettiContainer);
        
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
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
                <div class="button-group">
                    ${deckId ? 
                        `<a href="deck.php?id=${deckId}" class="back-button">
                            <i class="fas fa-layer-group"></i> Back to Deck
                         </a>` : 
                        `<a href="dashboard.php" class="back-button">
                            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                         </a>`
                    }
                    <button onclick="location.reload()" class="show-answer-btn">
                        <i class="fas fa-sync-alt"></i> Try Again
                    </button>
                </div>
            </div>
        `;
    }
    
    // Add this function for debugging - comment out for production
    function addDebugPanel() {
        const debugPanel = document.createElement('div');
        debugPanel.id = 'debug-panel';
        debugPanel.style.cssText = 'position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: #fff; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999; max-width: 300px; max-height: 200px; overflow: auto;';
        
        document.body.appendChild(debugPanel);
        
        function updateDebugInfo() {
            if (!document.getElementById('debug-panel')) return;
            
            const info = {
                'Current Index': currentIndex,
                'Total Cards': currentCards.length,
                'Cards Reviewed': cardsReviewed,
                'Ignoring Schedule': isIgnoringSchedule,
                'Study Mode': studyMode
            };
            
            let html = '<strong>Debug Info</strong><br>';
            for (const [key, value] of Object.entries(info)) {
                html += `${key}: <span style="color: #72FF8D">${value}</span><br>`;
            }
            
            document.getElementById('debug-panel').innerHTML = html;
        }
        
        // Update every second
        setInterval(updateDebugInfo, 1000);
    }
    
    // Uncomment this line to enable debugging
    // addDebugPanel();
});