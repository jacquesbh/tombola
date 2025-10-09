# 🎰 Tombola - Live Raffle Application

Real-time raffle application for conferences, built with Symfony and Mercure.

## 📋 Features

- **Board Display**: main screen displayed in the room
  - Display of connected players with Gravatar avatars
  - QR code to join the raffle
  - Random winner selection with animation
  - Winner history
  - Support for multiple rounds
  - Fullscreen mode with adaptive card layout
  - Confetti animation and sound effects for winners

- **Player Screen**: mobile interface for participants
  - Quick registration (first name, last name, email)
  - Real-time "Online" status with heartbeat system
  - Automatic Gravatar avatar
  - Offline/Online state management
  - Auto-reconnection when returning to tab
  - Multi-language support (English/French)
  - Winner celebration with sound and confetti

- **Real-time Communication**: via Mercure (Server-Sent Events)

- **Connection Management**:
  - Heartbeat every 3 seconds from players
  - 6-second timeout for inactive players
  - Automatic offline status for inactive tabs
  - Reconnection button when offline
  - Players kept in database but marked offline

## 🚀 Installation

### Prerequisites

- PHP 8.3+
- Composer
- Node.js / Yarn
- Mercure Hub (Docker or standalone)

### Installation Steps

1. **Clone the project**
```bash
cd tombola-ai
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install frontend dependencies**
```bash
yarn install
```

4. **Compile CSS assets**
```bash
yarn build:css
```

5. **Configure environment**

Edit the `.env` file:
```env
APP_SECRET=your_symfony_secret

# Mercure Hub URLs
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!
```

6. **Start Mercure Hub**

Option 1 - With Docker:
```bash
docker run -d -p 3000:80 \
    -e SERVER_NAME=':80' \
    -e MERCURE_PUBLISHER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -e MERCURE_SUBSCRIBER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    dunglas/mercure
```

Option 2 - Standalone:
Download from https://mercure.rocks/ and run

7. **Start Symfony server**
```bash
symfony server:start
```
Or with PHP:
```bash
php -S localhost:8000 -t public/
```

## 📱 Usage

1. Access `http://localhost:8000/` to create a new raffle
2. The game board displays with a QR code
3. Participants scan the QR code with their smartphone
4. They fill in their information (first name, last name, email)
5. Their card appears on the board in real-time
6. The host clicks "Enter Fullscreen" then "Start Round!" to select a winner
7. Loser cards progressively disappear (4 per second)
8. The winner is displayed large with animation, confetti, and sound
9. Click "Next Round" to start a new draw

## 🛠️ Technical Architecture

### Backend
- **Framework**: Symfony 7.3
- **Cache**: Filesystem cache (temporary storage)
- **Real-time**: Mercure Bundle
- **QR Code**: Endroid QR Code
- **UID**: Symfony UID

### Frontend
- **CSS**: Tailwind CSS v4
- **JavaScript**: Vanilla JS + EventSource API
- **Animations**: CSS Keyframes
- **i18n**: Client-side translations (EN/FR)

### Services
- `TombolaManager`: game state management (players, rounds, winners)
- `GravatarService`: Gravatar URL generation
- `QRCodeService`: QR code generation
- `MercurePublisher`: real-time event publishing

### Cache Structure
- `tombola.{code}.players` - List of all players (online + offline)
- `tombola.{code}.active_players` - Frozen player list for current round
- `tombola.{code}.round` - Current round number
- `tombola.{code}.winners` - Winner history
- `tombola.{code}.state` - Game state (waiting, in_round, showing_winner)
- `tombola.{code}.initialized` - Tombola existence flag

## 🔧 Development

### Compile CSS in watch mode
```bash
yarn watch:css
```

### Add Fake Players for Testing
```bash
php bin/add-fake-players.php {RAFFLE_CODE} {NUMBER_OF_PLAYERS}
```
Example:
```bash
php bin/add-fake-players.php ABC123 20
```

### Mercure Event Structure

**player_joined**
```json
{
  "type": "player_joined",
  "player": {...},
  "totalPlayers": 15
}
```

**player_left**
```json
{
  "type": "player_left",
  "playerId": "uuid",
  "totalPlayers": 14
}
```

**round_started**
```json
{
  "type": "round_started",
  "winnerId": "uuid",
  "round": 2
}
```

**winner_selected**
```json
{
  "type": "winner_selected",
  "winnerId": "uuid"
}
```

**next_round_ready**
```json
{
  "type": "next_round_ready",
  "round": 3
}
```

## 📊 Player States & Timings

| State | Heartbeat | Visual | Board |
|-------|-----------|--------|-------|
| **Online** | ✅ Every 3s | 🟢 Green | ✅ Visible |
| **Offline** | ❌ Stopped | ⚪ Gray + reconnect button | ❌ Hidden |
| **Pending** | ✅ Every 3s | 🟠 Orange | ❌ Hidden (in round) |

- **Heartbeat Interval**: 3 seconds
- **Timeout**: 6 seconds (players marked offline after)
- **Board Check**: Every 3 seconds for inactive players
- **Card Disappearance**: 4 cards per second (250ms interval)

## 🌍 Internationalization

The application supports English (default) and French:
- Language selector on join form and player status page
- Preference saved in localStorage
- Real-time language switching without page reload
- Translated alerts and messages

## 📝 Notes

- No data is persisted in a database
- Raffles are automatically cleaned after 24h (cache TTL)
- Raffle code is randomly generated (6 alphanumeric characters)
- Gravatar avatar uses "identicon" by default if email has no Gravatar account
- Offline players are kept in database but hidden from board
- SessionStorage preserves player data for reconnection

## 🎨 Customization

### Modify card disappearance speed
Edit `public/js/board.js`:
```javascript
const intervalTime = 250; // 250ms = 4 cards/second
```

### Modify delay before "Next Round"
Edit `public/js/board.js`:
```javascript
setTimeout(() => {
    nextRoundBtn.classList.remove('hidden');
}, 5000); // 5000ms = 5 seconds
```

### Modify heartbeat interval
Edit `templates/join/online.html.twig`:
```javascript
heartbeatInterval = setInterval(() => {
    // ...
}, 3000); // 3000ms = 3 seconds
```

### Modify timeout threshold
Edit `src/Service/TombolaManager.php` and `src/Controller/BoardController.php`:
```php
$this->tombolaManager->removeInactivePlayers($code, 6); // 6 seconds
```

## 📄 License

MIT

## 👨‍💻 Author

Developed with ❤️ for events and conferences
