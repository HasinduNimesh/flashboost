/**
 * FlashBoost - Main JavaScript
 */

// DOM Elements Cache
const domCache = {};

// App State
const appState = {
  currentUser: null,
  currentDeckId: null,
  currentSession: null,
  currentCards: [],
  currentCardIndex: 0,
  isCardFlipped: false
};

/**
 * Authentication Module
 */
const Auth = {
  init() {
    // Check if user is logged in
    fetch('/api/check-auth.php')
      .then(res => res.json())
      .then(data => {
        if (data.isLoggedIn) {
          appState.currentUser = data.user;
          this.updateAuthUI();
        } else {
          // Redirect to login if on protected page
          const protectedPages = ['dashboard', 'decks', 'study', 'analytics'];
          const currentPage = window.location.pathname.split('/').pop().split('.')[0];
          
          if (protectedPages.includes(currentPage)) {
            window.location.href = '/login.php';
          }
        }
      });
  },
  
  updateAuthUI() {
    const authLinks = document.querySelector('.auth-links');
    if (!authLinks) return;
    
    if (appState.currentUser) {
      authLinks.innerHTML = `
        <li><span>Hello, ${appState.currentUser.username}</span></li>
        <li><a href="#" id="logout-btn">Logout</a></li>
      `;
      
      document.getElementById('logout-btn').addEventListener('click', (e) => {
        e.preventDefault();
        this.logout();
      });
    } else {
      authLinks.innerHTML = `
        <li><a href="/login.php">Login</a></li>
        <li><a href="/register.php" class="btn btn-primary">Sign Up</a></li>
      `;
    }
  },
  
  login(email, password) {
    return fetch('/api/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ email, password })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        appState.currentUser = data.user;
        this.updateAuthUI();
        window.location.href = '/dashboard.php';
      }
      return data;
    });
  },
  
  register(username, email, password) {
    return fetch('/api/register.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ username, email, password })
    })
    .then(res => res.json());
  },
  
  logout() {
    return fetch('/api/logout.php')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          appState.currentUser = null;
          this.updateAuthUI();
          window.location.href = '/login.php';
        }
        return data;
      });
  }
};

/**
 * Deck Management Module
 */
const DeckManager = {
  init() {
    // Initialize deck listing if on decks page
    if (document.querySelector('#decks-container')) {
      this.loadDecks();
      this.initDeckForm();
    }
  },
  
  loadDecks() {
    const decksContainer = document.querySelector('#decks-container');
    if (!decksContainer) return;
    
    decksContainer.innerHTML = '<div class="text-center">Loading decks...</div>';
    
    fetch('/api/get-decks.php')
      .then(res => res.json())
      .then(data => {
        if (data.success && data.decks) {
          if (data.decks.length === 0) {
            decksContainer.innerHTML = `
              <div class="text-center mt-8 mb-8">
                <p>You don't have any decks yet.</p>
                <button id="create-first-deck" class="btn btn-primary mt-4">Create Your First Deck</button>
              </div>
            `;
            document.getElementById('create-first-deck').addEventListener('click', () => {
              document.getElementById('new-deck-modal').classList.add('show');
            });
          } else {
            decksContainer.innerHTML = `
              <div class="card-header">
                <h2 class="card-title">Your Decks</h2>
                <button id="new-deck-btn" class="btn btn-primary">
                  <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 0a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2H9v6a1 1 0 1 1-2 0V9H1a1 1 0 1 1 0-2h6V1a1 1 0 0 1 1-1z"/>
                  </svg>
                  New Deck
                </button>
              </div>
              <div class="deck-grid"></div>
            `;
            
            const deckGrid = decksContainer.querySelector('.deck-grid');
            data.decks.forEach(deck => {
              deckGrid.appendChild(this.createDeckCard(deck));
            });
            
            document.getElementById('new-deck-btn').addEventListener('click', () => {
              document.getElementById('new-deck-modal').classList.add('show');
            });
          }
        } else {
          decksContainer.innerHTML = `
            <div class="alert alert-danger">
              ${data.error || 'Failed to load decks. Please try again.'}
            </div>
          `;
        }
      })
      .catch(err => {
        decksContainer.innerHTML = `
          <div class="alert alert-danger">
            An error occurred while loading decks.
          </div>
        `;
        console.error(err);
      });
  },
  
  createDeckCard(deck) {
    const deckCard = document.createElement('div');
    deckCard.className = 'card deck-card';
    deckCard.dataset.deckId = deck.id;
    
    const duePercent = deck.card_count > 0 
      ? Math.round((deck.due_count / deck.card_count) * 100) 
      : 0;
    
    deckCard.innerHTML = `
      <div class="deck-info">
        <h3 class="card-title">${deck.title}</h3>
        <p class="mb-4">${deck.description || 'No description'}</p>
      </div>
      <div class="deck-stats">
        <div>
          <strong>${deck.card_count}</strong> ${deck.card_count === 1 ? 'Card' : 'Cards'}
        </div>
        <div>
          <strong>${deck.due_count}</strong> Due
        </div>
      </div>
      ${deck.due_count > 0 ? `<div class="deck-tag">${duePercent}% due</div>` : ''}
    `;
    
    deckCard.addEventListener('click', () => {
      window.location.href = `/deck.php?id=${deck.id}`;
    });
    
    return deckCard;
  },
  
  initDeckForm() {
    const newDeckForm = document.getElementById('new-deck-form');
    if (!newDeckForm) return;
    
    newDeckForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const title = document.getElementById('deck-title').value;
      const description = document.getElementById('deck-description').value;
      
      fetch('/api/create-deck.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ title, description })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('new-deck-modal').classList.remove('show');
          this.loadDecks();
          // Reset form
          newDeckForm.reset();
        } else {
          const errorEl = document.getElementById('deck-form-error');
          errorEl.textContent = data.error || 'Failed to create deck';
          errorEl.style.display = 'block';
        }
      })
      .catch(err => {
        console.error(err);
      });
    });
    
    // Close modal
    document.querySelectorAll('.close-modal').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('new-deck-modal').classList.remove('show');
        newDeckForm.reset();
      });
    });
  }
};

/**
 * Card Management Module
 */
const CardManager = {
  init() {
    // Initialize if on single deck page
    if (document.querySelector('#cards-container')) {
      this.loadDeckDetails();
      this.initCardForm();
    }
  },
  
  loadDeckDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const deckId = urlParams.get('id');
    
    if (!deckId) {
      window.location.href = '/decks.php';
      return;
    }
    
    appState.currentDeckId = deckId;
    this.loadCards(deckId);
    this.loadDeckInfo(deckId);
  },
  
  loadDeckInfo(deckId) {
    fetch(`/api/get-deck.php?id=${deckId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.deck) {
          document.getElementById('deck-title-display').textContent = data.deck.title;
          document.getElementById('deck-description-display').textContent = data.deck.description || 'No description';
          
          // Update study button status
          const studyBtn = document.getElementById('study-deck-btn');
          if (data.deck.due_count > 0) {
            studyBtn.classList.remove('btn-outline');
            studyBtn.classList.add('btn-primary');
            studyBtn.innerHTML = `Study Now (${data.deck.due_count} Cards Due)`;
          } else {
            studyBtn.classList.remove('btn-primary');
            studyBtn.classList.add('btn-outline');
            studyBtn.innerHTML = `No Cards Due`;
          }
          
          studyBtn.addEventListener('click', () => {
            window.location.href = `/study.php?deck=${deckId}`;
          });
        }
      });
  },
  
  loadCards(deckId) {
    const cardsContainer = document.querySelector('#cards-container');
    if (!cardsContainer) return;
    
    cardsContainer.innerHTML = '<div class="text-center">Loading cards...</div>';
    
    fetch(`/api/get-cards.php?deckId=${deckId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.cards) {
          if (data.cards.length === 0) {
            cardsContainer.innerHTML = `
              <div class="text-center mt-8 mb-8">
                <p>This deck doesn't have any cards yet.</p>
                <button id="create-first-card" class="btn btn-primary mt-4">Add Your First Card</button>
              </div>
            `;
            document.getElementById('create-first-card').addEventListener('click', () => {
              document.getElementById('new-card-modal').classList.add('show');
            });
          } else {
            cardsContainer.innerHTML = `
              <div class="card-header">
                <h2 class="card-title">Cards</h2>
                <button id="new-card-btn" class="btn btn-primary">
                  <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 0a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2H9v6a1 1 0 1 1-2 0V9H1a1 1 0 1 1 0-2h6V1a1 1 0 0 1 1-1z"/>
                  </svg>
                  Add Card
                </button>
              </div>
              <div class="cards-list"></div>
            `;
            
            const cardsList = cardsContainer.querySelector('.cards-list');
            data.cards.forEach(card => {
              cardsList.appendChild(this.createCardItem(card));
            });
            
            document.getElementById('new-card-btn').addEventListener('click', () => {
              document.getElementById('new-card-modal').classList.add('show');
            });
          }
        } else {
          cardsContainer.innerHTML = `
            <div class="alert alert-danger">
              ${data.error || 'Failed to load cards. Please try again.'}
            </div>
          `;
        }
      })
      .catch(err => {
        cardsContainer.innerHTML = `
          <div class="alert alert-danger">
            An error occurred while loading cards.
          </div>
        `;
        console.error(err);
      });
  },
  
  createCardItem(card) {
    const cardItem = document.createElement('div');
    cardItem.className = 'card mb-4';
    cardItem.dataset.cardId = card.id;
    
    // Format tags
    let tagsHtml = '';
    if (card.tags && card.tags.length > 0) {
      tagsHtml = `
        <div class="card-tags mt-4">
          ${card.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
        </div>
      `;
    }
    
    // Check if next review is today or past due
    const today = new Date().toISOString().split('T')[0];
    const isDue = card.next_review <= today;
    
    cardItem.innerHTML = `
      <div class="card-header">
        <div>
          <span class="card-title">Card #${card.id}</span>
          ${isDue ? `<span class="tag" style="background-color: var(--accent);">Due</span>` : ''}
        </div>
        <div>
          <button class="btn btn-outline edit-card" data-card-id="${card.id}">Edit</button>
          <button class="btn btn-danger delete-card" data-card-id="${card.id}">Delete</button>
        </div>
      </div>
      
      <div class="card-preview">
        <div class="card-front mb-4">
          <strong>Front:</strong>
          <div>${card.front_content}</div>
        </div>
        
        <div class="card-back">
          <strong>Back:</strong>
          <div>${card.back_content}</div>
        </div>
        
        ${tagsHtml}
      </div>
    `;
    
    // Add event listeners
    cardItem.querySelector('.edit-card').addEventListener('click', () => {
      this.openEditCardModal(card);
    });
    
    cardItem.querySelector('.delete-card').addEventListener('click', () => {
      this.deleteCard(card.id);
    });
    
    return cardItem;
  },
  
  openEditCardModal(card) {
    const modal = document.getElementById('edit-card-modal');
    if (!modal) return;
    
    modal.classList.add('show');
    
    document.getElementById('edit-card-id').value = card.id;
    document.getElementById('edit-card-front').value = card.front_content;
    document.getElementById('edit-card-back').value = card.back_content;
    
    // Set tags
    const tagsInput = document.getElementById('edit-card-tags');
    tagsInput.value = card.tags ? card.tags.join(', ') : '';
    
    // Set up form submission
    const form = document.getElementById('edit-card-form');
    form.onsubmit = (e) => {
      e.preventDefault();
      
      const cardId = document.getElementById('edit-card-id').value;
      const frontContent = document.getElementById('edit-card-front').value;
      const backContent = document.getElementById('edit-card-back').value;
      const tagsValue = document.getElementById('edit-card-tags').value;
      const tags = tagsValue ? tagsValue.split(',').map(tag => tag.trim()).filter(tag => tag) : [];
      
      fetch('/api/edit-card.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          cardId,
          frontContent,
          backContent,
          tags
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          modal.classList.remove('show');
          form.reset();
          this.loadCards(appState.currentDeckId);
        } else {
          const errorEl = document.getElementById('edit-card-error');
          errorEl.textContent = data.error || 'Failed to update card';
          errorEl.style.display = 'block';
        }
      });
    };
    
    // Close modal
    modal.querySelector('.close-modal').addEventListener('click', () => {
      modal.classList.remove('show');
      form.reset();
    });
  },
  
  deleteCard(cardId) {
    if (confirm('Are you sure you want to delete this card?')) {
      fetch('/api/delete-card.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ cardId })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          this.loadCards(appState.currentDeckId);
        } else {
          alert(data.error || 'Failed to delete card');
        }
      });
    }
  },
  
  initCardForm() {
    const newCardForm = document.getElementById('new-card-form');
    if (!newCardForm) return;
    
    newCardForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const frontContent = document.getElementById('card-front').value;
      const backContent = document.getElementById('card-back').value;
      const tagsValue = document.getElementById('card-tags').value;
      const tags = tagsValue ? tagsValue.split(',').map(tag => tag.trim()).filter(tag => tag) : [];
      
      fetch('/api/add-card.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          deckId: appState.currentDeckId,
          frontContent,
          backContent,
          tags
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('new-card-modal').classList.remove('show');
          this.loadCards(appState.currentDeckId);
          // Reset form
          newCardForm.reset();
        } else {
          const errorEl = document.getElementById('card-form-error');
          errorEl.textContent = data.error || 'Failed to add card';
          errorEl.style.display = 'block';
        }
      });
    });
    
    // Close modal
    document.querySelectorAll('#new-card-modal .close-modal').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('new-card-modal').classList.remove('show');
        newCardForm.reset();
      });
    });
  }
};

/**
 * Study Session Module
 */
const StudySession = {
  init() {
    if (document.querySelector('#study-container')) {
      this.initStudySession();
    }
  },
  
  initStudySession() {
    const urlParams = new URLSearchParams(window.location.search);
    const deckId = urlParams.get('deck');
    
    if (!deckId) {
      window.location.href = '/decks.php';
      return;
    }
    
    appState.currentDeckId = deckId;
    
    // Start study session
    this.startSession(deckId);
    
    // Set up event listeners
    document.getElementById('show-answer-btn').addEventListener('click', this.showAnswer.bind(this));
    document.getElementById('end-session-btn').addEventListener('click', this.endSession.bind(this));
    
    // Quality buttons
    const qualityButtons = document.querySelectorAll('.quality-btn');
    qualityButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const quality = parseInt(btn.dataset.quality);
        this.submitAnswer(quality);
      });
    });
    
    // Card flip on click
    document.querySelector('.study-card').addEventListener('click', () => {
      if (appState.isCardFlipped || document.getElementById('answer-section').style.display !== 'none') {
        return;
      }
      this.showAnswer();
    });
  },
  
  startSession(deckId) {
    document.getElementById('loading-indicator').style.display = 'block';
    document.getElementById('study-content').style.display = 'none';
    document.getElementById('session-end').style.display = 'none';
    
    fetch(`/api/start-session.php?deckId=${deckId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          appState.currentSession = data.sessionId;
          appState.currentCards = data.cards;
          appState.currentCardIndex = 0;
          
          document.getElementById('loading-indicator').style.display = 'none';
          
          if (data.cards.length === 0) {
            this.showNoCardsMessage();
          } else {
            document.getElementById('study-content').style.display = 'block';
            document.getElementById('cards-count').textContent = data.cards.length;
            this.renderCurrentCard();
          }
        } else {
          this.showError(data.error || 'Failed to start study session');
        }
      })
      .catch(err => {
        this.showError('An error occurred while starting the session');
        console.error(err);
      });
  },
  
  renderCurrentCard() {
    if (!appState.currentCards.length) {
      return this.showNoCardsMessage();
    }
    
    const card = appState.currentCards[appState.currentCardIndex];
    if (!card) {
      return this.finishSession();
    }
    
    // Update progress
    const progress = ((appState.currentCardIndex + 1) / appState.currentCards.length) * 100;
    document.getElementById('progress-bar').style.width = `${progress}%`;
    document.getElementById('card-counter').textContent = `${appState.currentCardIndex + 1}/${appState.currentCards.length}`;
    
    // Update card content
    document.getElementById('card-front-content').innerHTML = card.front_content;
    document.getElementById('card-back-content').innerHTML = card.back_content;
    
    // Reset card state
    document.querySelector('.study-card').classList.remove('flipped');
    appState.isCardFlipped = false;
    document.getElementById('answer-section').style.display = 'none';
    document.getElementById('show-answer-btn').style.display = 'block';
    document.getElementById('quality-buttons').style.display = 'none';
  },
  
  showAnswer() {
    document.querySelector('.study-card').classList.add('flipped');
    appState.isCardFlipped = true;
    document.getElementById('answer-section').style.display = 'block';
    document.getElementById('show-answer-btn').style.display = 'none';
    document.getElementById('quality-buttons').style.display = 'flex';
  },
  
  submitAnswer(quality) {
    const card = appState.currentCards[appState.currentCardIndex];
    
    fetch('/api/record-answer.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        sessionId: appState.currentSession,
        cardId: card.id,
        quality: quality
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        appState.currentCardIndex++;
        this.renderCurrentCard();
      } else {
        this.showError(data.error || 'Failed to record answer');
      }
    })
    .catch(err => {
      this.showError('An error occurred while recording your answer');
      console.error(err);
    });
  },
  
  finishSession() {
    fetch(`/api/end-session.php?sessionId=${appState.currentSession}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('study-content').style.display = 'none';
          document.getElementById('session-end').style.display = 'block';
          
          // Update session summary
          const summary = data.summary;
          document.getElementById('session-cards-reviewed').textContent = summary.cardsReviewed;
          document.getElementById('session-correct-count').textContent = summary.correctCount;
          document.getElementById('session-accuracy').textContent = summary.accuracy;
          document.getElementById('session-duration').textContent = summary.durationFormatted;
          
          // Update buttons
          document.getElementById('back-to-deck-btn').href = `/deck.php?id=${appState.currentDeckId}`;
          document.getElementById('study-again-btn').addEventListener('click', () => {
            this.startSession(appState.currentDeckId);
          });
        }
      });
  },
  
  endSession() {
    if (confirm('Are you sure you want to end this study session?')) {
      this.finishSession();
    }
  },
  
  showNoCardsMessage() {
    document.getElementById('loading-indicator').style.display = 'none';
    document.getElementById('study-content').style.display = 'none';
    
    const noCardsEl = document.getElementById('session-end');
    noCardsEl.style.display = 'block';
    
    document.getElementById('session-summary-title').textContent = 'No Cards Due';
    document.getElementById('session-summary-content').innerHTML = `
      <p>There are no cards due for review in this deck.</p>
      <p>You can create new cards or check back later.</p>
    `;
    
    document.getElementById('back-to-deck-btn').href = `/deck.php?id=${appState.currentDeckId}`;
  },
  
  showError(message) {
    document.getElementById('loading-indicator').style.display = 'none';
    document.getElementById('study-content').style.display = 'none';
    
    const errorEl = document.createElement('div');
    errorEl.className = 'alert alert-danger';
    errorEl.textContent = message;
    
    const container = document.getElementById('study-container');
    container.innerHTML = '';
    container.appendChild(errorEl);
    
    const backBtn = document.createElement('a');
    backBtn.href = `/deck.php?id=${appState.currentDeckId}`;
    backBtn.className = 'btn btn-primary mt-4';
    backBtn.textContent = 'Back to Deck';
    container.appendChild(backBtn);
  }
};

/**
 * Analytics Module
 */
const Analytics = {
  init() {
    if (document.querySelector('#analytics-container')) {
      this.loadAnalytics();
    }
  },
  
  loadAnalytics() {
    // Load deck statistics
    const urlParams = new URLSearchParams(window.location.search);
    const deckId = urlParams.get('deck');
    
    if (deckId) {
      appState.currentDeckId = deckId;
      this.loadDeckStats(deckId);
      this.loadTagStats(deckId);
    } else {
      this.loadUserStats();
    }
  },
  
  loadDeckStats(deckId) {
    fetch(`/api/get-deck-stats.php?deckId=${deckId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.stats) {
          const stats = data.stats;
          
          // Update deck stats cards
          document.getElementById('total-cards').textContent = stats.total_cards;
          document.getElementById('due-cards').textContent = stats.due_cards;
          document.getElementById('mastered-cards').textContent = stats.mastered_cards;
          
          // Calculate retention rate
          const masteredPercent = stats.total_cards > 0 
            ? Math.round((stats.mastered_cards / stats.total_cards) * 100) 
            : 0;
          document.getElementById('retention-rate').textContent = `${masteredPercent}%`;
          
          // Render chart if Chart.js is loaded
          if (window.Chart) {
            this.renderDeckChart(stats);
          }
        }
      });
  },
  
  loadTagStats(deckId) {
    fetch(`/api/get-tag-stats.php?deckId=${deckId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.tagStats) {
          const tagStats = data.tagStats;
          const container = document.getElementById('tag-stats-container');
          
          if (tagStats.length === 0) {
            container.innerHTML = '<p>No tags found for this deck.</p>';
            return;
          }
          
          // Sort tags by performance (avg_quality)
          tagStats.sort((a, b) => b.avg_quality - a.avg_quality);
          
          // Create tag performance table
          const table = document.createElement('table');
          table.className = 'tag-stats-table';
          
          table.innerHTML = `
            <thead>
              <tr>
                <th>Tag</th>
                <th>Cards</th>
                <th>Performance</th>
              </tr>
            </thead>
            <tbody></tbody>
          `;
          
          const tbody = table.querySelector('tbody');
          
          tagStats.forEach(stat => {
            const row = document.createElement('tr');
            
            // Create performance bar color based on average quality
            let performanceColor = '#F56565'; // Default red
            if (stat.avg_quality >= 4.0) {
              performanceColor = '#48BB78'; // Green
            } else if (stat.avg_quality >= 3.0) {
              performanceColor = '#ECC94B'; // Yellow
            } else if (stat.avg_quality >= 2.0) {
              performanceColor = '#ED8936'; // Orange
            }
            
            row.innerHTML = `
              <td><span class="tag">${stat.tag}</span></td>
              <td>${stat.card_count}</td>
              <td>
                <div class="performance-bar">
                  <div class="performance-fill" style="width: ${stat.avg_quality * 20}%; background-color: ${performanceColor}"></div>
                  <span>${stat.avg_quality.toFixed(1)} / 5.0</span>
                </div>
              </td>
            `;
            
            tbody.appendChild(row);
          });
          
          container.innerHTML = '';
          container.appendChild(table);
        }
      });
  },
  
  loadUserStats() {
    fetch('/api/get-user-stats.php')
      .then(res => res.json())
      .then(data => {
        if (data.success && data.stats) {
          const stats = data.stats;
          
          // Update dashboard stats
          document.getElementById('total-decks').textContent = stats.total_decks;
          document.getElementById('total-cards').textContent = stats.total_cards;
          document.getElementById('cards-due-today').textContent = stats.cards_due_today;
          document.getElementById('cards-studied').textContent = stats.total_reviews;
          
          // Load recent sessions
          if (stats.recent_sessions && stats.recent_sessions.length > 0) {
            this.renderSessionHistory(stats.recent_sessions);
          } else {
            document.getElementById('recent-sessions-container').innerHTML = 
              '<p>No study sessions yet. Start studying to see your progress!</p>';
          }
        }
      });
  },
  
  renderSessionHistory(sessions) {
    const container = document.getElementById('recent-sessions-container');
    if (!container) return;
    
    const table = document.createElement('table');
    table.className = 'sessions-table';
    
    table.innerHTML = `
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Deck</th>
          <th>Cards</th>
          <th>Accuracy</th>
          <th>Duration</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;
    
    const tbody = table.querySelector('tbody');
    
    sessions.forEach(session => {
      const row = document.createElement('tr');
      
      const date = new Date(session.started_at);
      const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
      
      row.innerHTML = `
        <td>${formattedDate}</td>
        <td>${session.deck_title}</td>
        <td>${session.cards_reviewed} cards</td>
        <td>${session.accuracy}%</td>
        <td>${this.formatDuration(session.duration_seconds)}</td>
      `;
      
      tbody.appendChild(row);
    });
    
    container.innerHTML = '';
    container.appendChild(table);
  },
  
  renderDeckChart(stats) {
    const ctx = document.getElementById('deck-chart').getContext('2d');
    
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Mastered', 'Due', 'Learning'],
        datasets: [{
          data: [
            stats.mastered_cards,
            stats.due_cards,
            stats.total_cards - stats.mastered_cards - stats.due_cards
          ],
          backgroundColor: [
            '#48BB78', // green
            '#ECC94B', // yellow
            '#A0AEC0'  // gray
          ],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        cutoutPercentage: 70,
        legend: {
          position: 'bottom'
        },
        tooltips: {
          callbacks: {
            label: function(tooltipItem, data) {
              const dataset = data.datasets[tooltipItem.datasetIndex];
              const value = dataset.data[tooltipItem.index];
              const total = dataset.data.reduce((prev, curr) => prev + curr, 0);
              const percentage = Math.round((value / total) * 100);
              return `${data.labels[tooltipItem.index]}: ${value} (${percentage}%)`;
            }
          }
        }
      }
    });
  },
  
  formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
  }
};

/**
 * Initialize Application
 */
document.addEventListener('DOMContentLoaded', function() {
  // Initialize modules
  Auth.init();
  DeckManager.init();
  CardManager.init();
  StudySession.init();
  Analytics.init();
  
  // Initialize common UI components
  initModals();
  initTagsInput();
});

/**
 * UI Utility Functions
 */
function initModals() {
  // Close modals when clicking outside
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
      e.target.classList.remove('show');
    }
  });
  
  // Close modals with ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal.show').forEach(modal => {
        modal.classList.remove('show');
      });
    }
  });
}

function initTagsInput() {
  document.querySelectorAll('.tags-input').forEach(input => {
    const tagsContainer = document.createElement('div');
    tagsContainer.className = 'tags-container';
    
    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.placeholder = 'Add tags...';
    
    tagsContainer.appendChild(textInput);
    input.parentNode.insertBefore(tagsContainer, input);
    
    let tags = [];
    
    // Initialize from existing value
    if (input.value) {
      tags = input.value.split(',').map(tag => tag.trim()).filter(tag => tag);
      renderTags();
    }
    
    // Add tag on Enter key
    textInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        
        const tag = textInput.value.trim();
        if (tag && !tags.includes(tag)) {
          tags.push(tag);
          renderTags();
        }
        
        textInput.value = '';
      }
    });
    
    // Focus lost
    textInput.addEventListener('blur', () => {
      const tag = textInput.value.trim();
      if (tag && !tags.includes(tag)) {
        tags.push(tag);
        renderTags();
        textInput.value = '';
      }
    });
    
    function renderTags() {
      // Clear existing tags except the input
      Array.from(tagsContainer.children).forEach(child => {
        if (child !== textInput) {
          tagsContainer.removeChild(child);
        }
      });
      
      // Add tags before the input
      tags.forEach((tag, index) => {
        const tagEl = document.createElement('span');
        tagEl.className = 'tag';
        tagEl.textContent = tag;
        
        const removeBtn = document.createElement('span');
        removeBtn.className = 'remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', () => {
          tags.splice(index, 1);
          renderTags();
        });
        
        tagEl.appendChild(removeBtn);
        tagsContainer.insertBefore(tagEl, textInput);
      });
      
      // Update the original input value
      input.value = tags.join(',');
    }
  });
}
    