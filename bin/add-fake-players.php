#!/usr/bin/env php
<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($argc < 2) {
    echo "Usage: php bin/add-fake-players.php <tombola_code> [number_of_players]\n";
    exit(1);
}

$code = $argv[1];
$numberOfPlayers = isset($argv[2]) ? (int)$argv[2] : 30;
$baseUrl = 'https://127.0.0.1:8001';

// Liste de prénoms et noms français
$firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Julie', 'Paul', 'Emma', 'Thomas', 'Léa', 'Antoine', 'Chloé', 'Nicolas', 'Laura', 'Alexandre', 'Camille', 'Julien', 'Sarah', 'Maxime', 'Manon', 'Lucas', 'Anaïs', 'Hugo', 'Clara', 'Louis', 'Inès', 'Arthur', 'Lucie', 'Gabriel', 'Zoé'];
$lastNames = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier', 'Morel', 'Girard', 'André', 'Lefevre', 'Mercier', 'Dupont', 'Lambert', 'Bonnet', 'François', 'Martinez'];

echo "🎰 Ajout de $numberOfPlayers joueurs à la tombola $code...\n\n";

for ($i = 1; $i <= $numberOfPlayers; $i++) {
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    $uniqueId = time() . rand(1000, 9999) . $i;
    $email = strtolower($firstName . '.' . $lastName . '.' . $uniqueId . '@example.com');
    
    $data = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
    ];
    
    $ch = curl_init("$baseUrl/join/$code");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 400) {
        preg_match('/Location: (.+)/', $response, $matches);
        if (isset($matches[1])) {
            $redirectUrl = trim($matches[1]);
            
            $ch2 = curl_init($redirectUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch2);
            curl_close($ch2);
        }
        
        echo "✅ Joueur $i/$numberOfPlayers ajouté et connecté: $firstName $lastName ($email)\n";
    } else {
        echo "❌ Erreur pour le joueur $i: HTTP $httpCode\n";
    }
    
    usleep(100000);
}

echo "\n✨ Terminé! $numberOfPlayers joueurs ont été ajoutés.\n";
echo "📊 Rechargez la page du board pour voir tous les joueurs!\n";
