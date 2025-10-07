# 🎰 Tombola - Application de Tombola en Live

Application de tombola en temps réel pour conférences, développée avec Symfony et Mercure.

## 📋 Fonctionnalités

- **Plateau de jeu** : écran principal affiché en salle
  - Affichage des joueurs connectés avec avatars Gravatar
  - QR code pour rejoindre la tombola
  - Sélection aléatoire du gagnant avec animation
  - Historique des gagnants
  - Support de plusieurs tours de jeu

- **Écran utilisateur** : interface mobile pour les participants
  - Inscription rapide (nom, prénom, email)
  - Statut "Online" en temps réel
  - Avatar Gravatar automatique

- **Temps réel** : communication via Mercure (Server-Sent Events)

## 🚀 Installation

### Prérequis

- PHP 8.3+
- Composer
- Node.js / Yarn
- Mercure Hub (Docker ou standalone)

### Étapes d'installation

1. **Cloner le projet**
```bash
cd tombola-ai
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Installer les dépendances frontend**
```bash
yarn install
```

4. **Compiler les assets CSS**
```bash
yarn build:css
```

5. **Configurer l'environnement**

Éditer le fichier `.env` :
```env
APP_SECRET=votre_secret_symfony

# Mercure Hub URLs
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!
```

6. **Lancer Mercure Hub**

Option 1 - Avec Docker :
```bash
docker run -d -p 3000:80 \
    -e SERVER_NAME=':80' \
    -e MERCURE_PUBLISHER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -e MERCURE_SUBSCRIBER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    dunglas/mercure
```

Option 2 - Standalone :
Télécharger depuis https://mercure.rocks/ et lancer

7. **Lancer le serveur Symfony**
```bash
symfony server:start
```
Ou avec PHP :
```bash
php -S localhost:8000 -t public/
```

## 📱 Utilisation

1. Accéder à `http://localhost:8000/` pour créer une nouvelle tombola
2. Le plateau de jeu s'affiche avec un QR code
3. Les participants scannent le QR code avec leur smartphone
4. Ils renseignent leurs informations (prénom, nom, email)
5. Leur carte apparaît sur le plateau en temps réel
6. L'animateur clique sur "Lancer le tour !" pour sélectionner un gagnant
7. Les cartes des perdants disparaissent progressivement (4 par seconde)
8. Le gagnant est affiché en grand avec une animation
9. Cliquer sur "Tour suivant" pour lancer un nouveau tirage

## 🛠️ Architecture Technique

### Backend
- **Framework** : Symfony 7.3
- **Cache** : Filesystem cache (stockage temporaire)
- **Temps réel** : Mercure Bundle
- **QR Code** : Endroid QR Code
- **UID** : Symfony UID

### Frontend
- **CSS** : Tailwind CSS v4
- **JavaScript** : Vanilla JS + EventSource API
- **Animations** : CSS Keyframes

### Services
- `TombolaManager` : gestion de l'état du jeu (joueurs, tours, gagnants)
- `GravatarService` : génération d'URLs Gravatar
- `QRCodeService` : génération de QR codes
- `MercurePublisher` : publication d'événements temps réel

### Structure de cache
- `tombola.{code}.players` - Liste des joueurs
- `tombola.{code}.round` - Numéro du tour actuel
- `tombola.{code}.winners` - Historique des gagnants
- `tombola.{code}.state` - État du jeu (waiting, playing, showing_winner)

## 🔧 Développement

### Compiler le CSS en mode watch
```bash
yarn watch:css
```

### Structure des événements Mercure

**player_joined**
```json
{
  "type": "player_joined",
  "player": {...},
  "totalPlayers": 15
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

**next_round_ready**
```json
{
  "type": "next_round_ready",
  "round": 3
}
```

## 📝 Notes

- Aucune donnée n'est persistée en base de données
- Les tombolas sont automatiquement nettoyées après 24h (TTL du cache)
- Le code de la tombola est généré aléatoirement (6 caractères alphanumériques)
- L'avatar Gravatar utilise "identicon" par défaut si l'email n'a pas de compte Gravatar

## 🎨 Personnalisation

### Modifier la vitesse de disparition des cartes
Éditer `public/js/board.js` :
```javascript
const intervalTime = 250; // 250ms = 4 cartes/seconde
```

### Modifier le délai avant "Tour suivant"
Éditer `public/js/board.js` :
```javascript
setTimeout(() => {
    nextRoundBtn.classList.remove('hidden');
}, 5000); // 5000ms = 5 secondes
```

## 📄 Licence

MIT

## 👨‍💻 Auteur

Développé avec ❤️ pour les événements et conférences
