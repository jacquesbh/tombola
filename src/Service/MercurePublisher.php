<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercurePublisher
{
    public function __construct(
        private HubInterface $hub
    ) {
    }

    public function publish(string $topic, array $data): void
    {
        $update = new Update(
            $topic,
            json_encode($data)
        );

        $this->hub->publish($update);
    }

    public function publishPlayerJoined(string $code, array $player, int $totalPlayers): void
    {
        $this->publish("tombola/{$code}/board", [
            'type' => 'player_joined',
            'player' => $player,
            'totalPlayers' => $totalPlayers,
        ]);
    }

    public function publishPlayerLeft(string $code, string $playerId, int $totalPlayers): void
    {
        $this->publish("tombola/{$code}/board", [
            'type' => 'player_left',
            'playerId' => $playerId,
            'totalPlayers' => $totalPlayers,
        ]);
    }

    public function publishRoundStarted(string $code, string $winnerId, int $round): void
    {
        $this->publish("tombola/{$code}/board", [
            'type' => 'round_started',
            'winnerId' => $winnerId,
            'round' => $round,
        ]);
    }
    
    public function publishWinnerRevealed(string $code, string $winnerId, int $round): void
    {
        $this->publish("tombola/{$code}/players", [
            'type' => 'winner_selected',
            'winnerId' => $winnerId,
            'round' => $round,
        ]);
    }

    public function publishNextRoundReady(string $code, int $round): void
    {
        $this->publish("tombola/{$code}/board", [
            'type' => 'next_round_ready',
            'round' => $round,
        ]);
        
        $this->publish("tombola/{$code}/players", [
            'type' => 'round_ready',
            'round' => $round,
        ]);
    }
}
