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
    startRoundBtn.disabled = true;
    startRoundBtn.classList.add('opacity-50', 'cursor-not-allowed');

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
                    card.style.display = 'none';
                }
            }, 300);
        }

        index++;
    }, intervalTime);
}

function showWinner(winnerId) {
    const winnerCard = document.querySelector(`[data-player-id="${winnerId}"]`);
    if (winnerCard) {
        winnerCard.classList.add('animate-winner', 'col-span-5');
        winnerCard.style.transform = 'scale(1.5)';
        winnerCard.style.margin = 'auto';
    }

    setTimeout(() => {
        nextRoundBtn.classList.remove('hidden');
    }, 5000);
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
