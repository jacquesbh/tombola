const fullscreenBtn = document.getElementById('fullscreen-btn');
const startRoundBtn = document.getElementById('start-round-btn');
const nextRoundBtn = document.getElementById('next-round-btn');
const playersGrid = document.getElementById('players-grid');
const playerCount = document.getElementById('player-count');

const mercureTopicUrl = `${mercureUrl}?topic=${encodeURIComponent(`tombola/${code}/board`)}&authorization=${encodeURIComponent(mercureToken)}`;
console.log('Connecting to Mercure:', mercureTopicUrl);
const eventSource = new EventSource(mercureTopicUrl);

eventSource.onopen = () => {
    console.log('✅ Mercure connection opened successfully');
};

eventSource.onmessage = (event) => {
    const data = JSON.parse(event.data);
    console.log('📨 Mercure event received:', data);

    switch (data.type) {
        case 'player_joined':
            handlePlayerJoined(data.player, data.totalPlayers);
            break;
        case 'round_started':
            handleRoundStarted(data.winnerId);
            break;
        case 'next_round_ready':
            handleNextRoundReady(data.round);
            break;
    }
};

eventSource.onerror = (error) => {
    console.error('❌ EventSource error:', error);
    console.log('ReadyState:', eventSource.readyState);
    if (eventSource.readyState === EventSource.CLOSED) {
        console.log('Connection closed, attempting to reconnect...');
    }
};

function handlePlayerJoined(player, totalPlayers) {
    playerCount.textContent = totalPlayers;

    const existingCard = document.querySelector(`[data-player-id="${player.id}"]`);
    if (!existingCard) {
        const playerCard = document.createElement('div');
        playerCard.className = 'player-card';
        playerCard.setAttribute('data-player-id', player.id);
        playerCard.innerHTML = `
            <img src="${player.gravatarUrl}" alt="${player.firstName}" 
                 class="w-20 h-20 rounded-full mb-2 border-4 border-white shadow-lg">
            <p class="text-sm font-semibold text-gray-800">${player.firstName}</p>
            <p class="text-sm font-semibold text-gray-800">${player.lastName}</p>
        `;
        playersGrid.appendChild(playerCard);
    }
}

function handleRoundStarted(winnerId) {
    startRoundBtn.style.display = 'none';

    const allCards = Array.from(document.querySelectorAll('.player-card'));
    const loserCards = allCards.filter(card => card.getAttribute('data-player-id') !== winnerId);
    
    shuffle(loserCards);

    let index = 0;
    const intervalTime = 250;

    const interval = setInterval(() => {
        if (index >= loserCards.length) {
            clearInterval(interval);
            showWinner(winnerId);
            return;
        }

        const card = loserCards[index];
        if (card) {
            card.classList.add('animate-fade-out');
            setTimeout(() => {
                if (card) {
                    card.style.visibility = 'hidden';
                }
            }, 300);
        }

        index++;
    }, intervalTime);
}

function showWinner(winnerId) {
    const playersGrid = document.getElementById('players-grid');
    const winnerCard = document.querySelector(`[data-player-id="${winnerId}"]`);
    const buttonContainer = document.getElementById('button-container');
    
    if (buttonContainer) {
        buttonContainer.style.display = 'none';
    }
    
    if (winnerCard) {
        playersGrid.innerHTML = '';
        playersGrid.style.gridTemplateColumns = '1fr';
        playersGrid.classList.add('winner-display');
        playersGrid.appendChild(winnerCard);
        
        winnerCard.classList.add('animate-winner');
        winnerCard.style.transform = 'scale(2.5)';
        
        createConfetti();
    }

    setTimeout(() => {
        nextRoundBtn.classList.remove('hidden');
        nextRoundBtn.classList.add('next-round-corner');
        
        const qrSection = document.getElementById('qr-section');
        if (qrSection) {
            qrSection.style.display = 'block';
            qrSection.classList.add('qr-after-winner');
        }
    }, 5000);
}

function createConfetti() {
    const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ffa500', '#ff1493'];
    const confettiCount = 150;
    
    for (let i = 0; i < confettiCount; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
            confetti.style.animationDelay = '0s';
            document.body.appendChild(confetti);
            
            setTimeout(() => {
                confetti.remove();
            }, 5000);
        }, i * 30);
    }
}

function handleNextRoundReady(round) {
    location.reload();
}

function shuffle(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

function enterFullscreen() {
    const boardContainer = document.getElementById('board-container');
    const mainBoard = document.getElementById('main-board');
    const qrSection = document.getElementById('qr-section');
    const boardHeader = document.getElementById('board-header');
    const playersGrid = document.getElementById('players-grid');
    const previousWinners = document.getElementById('previous-winners');
    
    qrSection.style.display = 'none';
    boardHeader.style.display = 'none';
    fullscreenBtn.style.display = 'none';
    startRoundBtn.classList.remove('hidden');
    if (previousWinners) {
        previousWinners.style.display = 'none';
    }
    
    mainBoard.classList.remove('col-span-9');
    mainBoard.classList.add('col-span-12');
    
    boardContainer.classList.add('fullscreen-mode');
    
    const totalCards = document.querySelectorAll('.player-card').length;
    const columns = Math.ceil(Math.sqrt(totalCards * 1.6));
    playersGrid.style.gridTemplateColumns = `repeat(${columns}, minmax(0, 1fr))`;
    playersGrid.classList.add('fullscreen-grid');
    
    adjustCardSize(totalCards);
}

function adjustCardSize(totalCards) {
    const root = document.documentElement;
    let avatarSize, fontSize;
    
    if (totalCards <= 20) {
        avatarSize = '80px';
        fontSize = '0.875rem';
    } else if (totalCards <= 40) {
        avatarSize = '60px';
        fontSize = '0.75rem';
    } else if (totalCards <= 60) {
        avatarSize = '50px';
        fontSize = '0.7rem';
    } else {
        avatarSize = '40px';
        fontSize = '0.65rem';
    }
    
    root.style.setProperty('--avatar-size', avatarSize);
    root.style.setProperty('--card-font-size', fontSize);
}

if (fullscreenBtn) {
    fullscreenBtn.addEventListener('click', () => {
        enterFullscreen();
    });
}

if (startRoundBtn) {
    startRoundBtn.addEventListener('click', async () => {
        try {
            const response = await fetch(`/board/${code}/start-round`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (!response.ok) {
                const error = await response.json();
                alert(error.error || 'Erreur lors du démarrage du tour');
            }
        } catch (error) {
            console.error('Error starting round:', error);
            alert('Erreur lors du démarrage du tour');
        }
    });
}

if (nextRoundBtn) {
    nextRoundBtn.addEventListener('click', async () => {
        try {
            const response = await fetch(`/board/${code}/next-round`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (!response.ok) {
                const error = await response.json();
                alert(error.error || 'Erreur lors du passage au tour suivant');
            }
        } catch (error) {
            console.error('Error going to next round:', error);
            alert('Erreur lors du passage au tour suivant');
        }
    });
}
