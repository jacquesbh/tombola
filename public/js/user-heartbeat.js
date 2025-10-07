// Send heartbeat every 5 seconds to signal the user is still connected
const sendHeartbeat = () => {
    const pathParts = window.location.pathname.split('/');
    const code = pathParts[2];
    const playerId = pathParts[4];
    
    if (code && playerId) {
        fetch(`/join/${code}/heartbeat/${playerId}`, { method: 'POST' })
            .catch(err => console.error('Heartbeat failed:', err));
    }
};

// Send initial heartbeat
sendHeartbeat();

// Send heartbeat every 5 seconds
setInterval(sendHeartbeat, 5000);
