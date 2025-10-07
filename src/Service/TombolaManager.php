<?php

namespace App\Service;

use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TombolaManager
{
    public function __construct(
        private CacheInterface $tombolaCache,
        private GravatarService $gravatarService
    ) {
    }

    public function createTombola(): string
    {
        $code = $this->generateUniqueCode();
        
        // Initialize the tombola
        $this->tombolaCache->get("tombola.{$code}.initialized", function (ItemInterface $item) {
            $item->expiresAfter(86400);
            return true;
        });

        $this->setState($code, 'waiting');
        $this->setRound($code, 1);
        
        // Verify it was saved
        $check = $this->tombolaCache->get("tombola.{$code}.initialized", function (ItemInterface $item) {
            $item->expiresAfter(1);
            return null;
        });
        dump("After creation, tombola.{$code}.initialized = " . var_export($check, true));
        
        return $code;
    }

    public function addPlayer(string $code, string $firstName, string $lastName, string $email): array
    {
        $playerId = Uuid::v4()->toRfc4122();
        
        $player = [
            'id' => $playerId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'gravatarUrl' => $this->gravatarService->getGravatarUrl($email),
            'joinedAt' => time(),
            'lastHeartbeat' => time(),
        ];

        $players = $this->getPlayers($code);
        array_unshift($players, $player);
        
        $this->tombolaCache->delete("tombola.{$code}.players");
        $this->tombolaCache->get("tombola.{$code}.players", function (ItemInterface $item) use ($players) {
            $item->expiresAfter(86400);
            return json_encode($players);
        });

        return $player;
    }

    public function getPlayers(string $code): array
    {
        $data = $this->tombolaCache->get("tombola.{$code}.players", function (ItemInterface $item) {
            $item->expiresAfter(86400);
            return json_encode([]);
        });

        return json_decode($data, true);
    }

    public function updatePlayerHeartbeat(string $code, string $playerId): bool
    {
        $players = $this->getPlayers($code);
        $updated = false;
        
        foreach ($players as &$player) {
            if ($player['id'] === $playerId) {
                $player['lastHeartbeat'] = time();
                $updated = true;
                break;
            }
        }
        
        if (!$updated) {
            return false;
        }
        
        $this->tombolaCache->delete("tombola.{$code}.players");
        $this->tombolaCache->get("tombola.{$code}.players", function (ItemInterface $item) use ($players) {
            $item->expiresAfter(86400);
            return json_encode($players);
        });
        
        return true;
    }

    public function removeInactivePlayers(string $code, int $timeoutSeconds = 15): array
    {
        $players = $this->getPlayers($code);
        $currentTime = time();
        $removedPlayers = [];
        
        $activePlayers = array_filter($players, function($player) use ($currentTime, $timeoutSeconds, &$removedPlayers) {
            $isActive = ($currentTime - ($player['lastHeartbeat'] ?? 0)) <= $timeoutSeconds;
            if (!$isActive) {
                $removedPlayers[] = $player['id'];
            }
            return $isActive;
        });
        
        $activePlayers = array_values($activePlayers);
        
        if (count($removedPlayers) > 0) {
            $this->tombolaCache->delete("tombola.{$code}.players");
            $this->tombolaCache->get("tombola.{$code}.players", function (ItemInterface $item) use ($activePlayers) {
                $item->expiresAfter(86400);
                return json_encode($activePlayers);
            });
            
            $frozenPlayers = $this->getActivePlayers($code);
            $frozenPlayers = array_filter($frozenPlayers, function($player) use ($removedPlayers) {
                return !in_array($player['id'], $removedPlayers);
            });
            $frozenPlayers = array_values($frozenPlayers);
            
            $this->tombolaCache->delete("tombola.{$code}.active_players");
            $this->tombolaCache->get("tombola.{$code}.active_players", function (ItemInterface $item) use ($frozenPlayers) {
                $item->expiresAfter(86400);
                return json_encode($frozenPlayers);
            });
        }
        
        return $removedPlayers;
    }

    public function freezePlayers(string $code): void
    {
        $players = $this->getPlayers($code);
        
        $this->tombolaCache->delete("tombola.{$code}.active_players");
        $this->tombolaCache->get("tombola.{$code}.active_players", function (ItemInterface $item) use ($players) {
            $item->expiresAfter(86400);
            return json_encode($players);
        });
    }

    public function getActivePlayers(string $code): array
    {
        $data = $this->tombolaCache->get("tombola.{$code}.active_players", function (ItemInterface $item) use ($code) {
            $item->expiresAfter(86400);
            return $this->tombolaCache->get("tombola.{$code}.players", function (ItemInterface $item) {
                $item->expiresAfter(86400);
                return json_encode([]);
            });
        });

        return json_decode($data, true);
    }

    public function selectWinner(string $code): ?array
    {
        $players = $this->getActivePlayers($code);
        
        if (empty($players)) {
            return null;
        }

        $winnerIndex = array_rand($players);
        $winner = $players[$winnerIndex];

        $round = $this->getRound($code);
        $winners = $this->getWinners($code);
        $winners[] = [
            'round' => $round,
            'playerId' => $winner['id'],
            'playerName' => $winner['firstName'] . ' ' . $winner['lastName'],
            'player' => $winner,
        ];

        $this->tombolaCache->delete("tombola.{$code}.winners");
        $this->tombolaCache->get("tombola.{$code}.winners", function (ItemInterface $item) use ($winners) {
            $item->expiresAfter(86400);
            return json_encode($winners);
        });

        $this->setState($code, 'showing_winner');

        return $winner;
    }

    public function getWinners(string $code): array
    {
        $data = $this->tombolaCache->get("tombola.{$code}.winners", function (ItemInterface $item) {
            $item->expiresAfter(86400);
            return json_encode([]);
        });

        return json_decode($data, true);
    }

    public function nextRound(string $code): void
    {
        $round = $this->getRound($code);
        $this->setRound($code, $round + 1);
        $this->setState($code, 'waiting');
    }

    public function getRound(string $code): int
    {
        return (int) $this->tombolaCache->get("tombola.{$code}.round", function (ItemInterface $item) {
            $item->expiresAfter(86400);
            return 1;
        });
    }

    private function setRound(string $code, int $round): void
    {
        $this->tombolaCache->delete("tombola.{$code}.round");
        $this->tombolaCache->get("tombola.{$code}.round", function (ItemInterface $item) use ($round) {
            $item->expiresAfter(86400);
            return $round;
        });
    }

    public function getState(string $code): string
    {
        return $this->tombolaCache->get("tombola.{$code}.state", function (ItemInterface $item) {
            $item->expiresAfter(86400);
            return 'waiting';
        });
    }

    public function setState(string $code, string $state): void
    {
        $this->tombolaCache->delete("tombola.{$code}.state");
        $this->tombolaCache->get("tombola.{$code}.state", function (ItemInterface $item) use ($state) {
            $item->expiresAfter(86400);
            return $state;
        });
    }

    public function tombolaExists(string $code): bool
    {
        try {
            $item = $this->tombolaCache->getItem("tombola.{$code}.initialized");
            return $item->isHit() && $item->get() === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function generateUniqueCode(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while ($this->tombolaExists($code));

        return $code;
    }
}
