# Tombola

Cette application permet de jouer à une tombola en live, lors d’une conférence.

Elle est composée d’un plateau de jeu et d’un écran utilisateur.

L’écran utilisateur est utilisé par toutes les personnes qui souhaitent participer.

Le plateau de jeu lui, est l’écran qui est présenté dans la salle.

## Plateau de jeu

Le plateau affiche en direct les informations suivantes :

- Nombre de joueurs connectés.
- Le nom des joueurs connectés
- Le tour de jeu
- Les gagnants passés
- Le QR Code pour rejoindre le plateau

### Le nombre de joueurs connectés

Il s’agit de tous les joueurs qui sont connectés via leur écran utilisateur. On les dénombre et on affiche ce nombre en haut du plateau de jeu.

### Le nom des joueurs connectés

Chaque joueur qui se connecte (par l’utilisation de l’écran utilisateur) doit renseigner (sur l’écran utilisateur) un prénom, un nom, et un email.

On affiche donc les joueurs connectés en utilisant leur image Gravatar (via leur email) et leur prénom + nom.

On affiche ces joueurs sous forme de petite carte avec l’avatar dans un rond en haut de la carte et le nom et prénom en dessous de l’avatar.

Les cartes sont positionnées les unes à côté des autres dans l’espace dédié du plateau de jeu.

Techniquement le plateau de jeu sait combien de joueurs, et surtout quels joueurs sont connectés car ils se connectent via leur écran utilisateur, à l’aide du protocole Mercure (Symfony).

### Le tour de jeu

La tombola peut mettre en jeu plusieurs lots, par conséquent nous devons réaliser des tours de jeu. Il y a donc un tirage par tour.

On doit afficher le tour de jeu en cours, par exemple : Tour 1, Tour 2, Tour 3, etc.

### Les gagnants passés

Il y a un gagnant par tour de jeu ! Par conséquent si nous réalisons plusieurs tirages, il faut pouvoir afficher la liste des gagnants passés, probablement en bas du plateau de jeu.

### Le QR Code pour rejoindre le plateau

En bas de l’écran, sans que les gagnants bloquent cet affichage, on doit visualiser le QR Code qui permet de rejoindre la tombola. Ce QR Code est composé de l’URL pour devenir joueur. Cette URL contient donc le code de la tombola en cours et lance ainsi l’écran utilisateur pour chaque personne qui scanne ce code, via son smartphone.

## L’écran utilisateur

Les joueurs se connectent au plateau de jeu via une URL dédiée, celle qu’ils ont scanné sur le QR Code affiché sur le plateau de jeu.

Cet écran utilisateur est en plusieurs étapes.

La première étape : on demande à l’utilisateur son nom complet et son email (pour l’URL du gravatar).

La seconde étape, on affiche sa carte d’identité et un flag “Online” pour lui montrer qu’il est bien en train de participer.

C’est tout.

Techniquement, l’écran utilisateur communique au plateau de jeu via le protocole Mercure (Symfony).

## Comment se déroule un tour de jeu

Lors du démarrage de la tombola le plateau de jeu n’affiche que le QR Code de connexion pour les utilisateurs. Le QR Code est déplacé en bas de l’écran, et les joueurs affichés quand on a les 10 premiers utilisateurs qui sont connectés.

A chaque nouvel utilisateur qui se connecte sa carte d’identité apparaît sur l’écran.

Lorsque le “maître du jeu” (un humain) le décide, il clique sur “Lancer le tour !” et c’est alors que le choix du gagnant est fait, de manière totalement aléatoire.

Lorsque le système a sélectionné le gagnant, les “perdants” disparaissent (leur carte donc…) les uns après les autres avec un petit effet de disparition. Disons 4 par seconde.

Il ne reste à la fin que la carte d’identité du gagnant. Celle-ci est affichée en grand à l’écran avec un effet sympa.

Quelques secondes plus tard (5s max) un bouton apparaît pour lancer un nouveau tour.

## Techniquement

Nous sommes sur une application codée en PHP avec l’utilisation du framework Symfony.

Pour les échanges entre le plateau et les joueurs, on utilise Mercure.

L’utilisation de composer comme gestionnaire de paquet pour la partie backend est parfaite.

Côté frontend, on est sur du JS simple et du CSS Tailwind.

L’utilisation de “bun” comme gestionnaire de paquets pour la partie front est parfaite.

## Stockage des données

Aucun stockage des données, on ne garde rien.

## Hébergement

Le site doit être accessible via un serveur, donc on considèrera que nous sommes sur un simple serveur Apache avec PHP-FPM, tout ce qu’il y a de plus standard, rien de particulier de ce côté à prévoir donc.