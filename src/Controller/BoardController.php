<?php

namespace App\Controller;

use App\Service\MercurePublisher;
use App\Service\QRCodeService;
use App\Service\TombolaManager;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BoardController extends AbstractController
{
    public function __construct(
        private TombolaManager $tombolaManager,
        private QRCodeService $qrCodeService,
        private MercurePublisher $mercurePublisher,
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $params
    ) {
    }

    #[Route('/board/{code}', name: 'board')]
    public function show(string $code): Response
    {
        dump('Checking tombola: ' . $code);
        dump('Exists: ' . ($this->tombolaManager->tombolaExists($code) ? 'YES' : 'NO'));
        
        if (!$this->tombolaManager->tombolaExists($code)) {
            throw $this->createNotFoundException('Tombola not found');
        }

        $joinUrl = $this->urlGenerator->generate('join', ['code' => $code], UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = $this->qrCodeService->generateQRCode($joinUrl);

        $removedPlayerIds = $this->tombolaManager->removeInactivePlayers($code, 6);
        $totalPlayers = count($this->tombolaManager->getOnlinePlayers($code));
        
        foreach ($removedPlayerIds as $removedPlayerId) {
            try {
                $this->mercurePublisher->publishPlayerLeft($code, $removedPlayerId, $totalPlayers);
            } catch (\Exception $e) {
            }
        }
        
        $state = $this->tombolaManager->getState($code);
        $players = ($state === 'in_round' || $state === 'showing_winner') 
            ? $this->tombolaManager->getActivePlayers($code)
            : $this->tombolaManager->getOnlinePlayers($code);
        $winners = $this->tombolaManager->getWinners($code);
        $round = $this->tombolaManager->getRound($code);

        // Generate JWT token for Mercure subscription
        $jwtSecret = $_ENV['MERCURE_JWT_SECRET'] ?? '!ChangeThisMercureHubJWTSecretKey!';
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret)
        );
        
        $token = $config->builder()
            ->withClaim('mercure', ['subscribe' => ["tombola/{$code}/board"]])
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        return $this->render('board/show.html.twig', [
            'code' => $code,
            'qrCode' => $qrCode,
            'joinUrl' => $joinUrl,
            'players' => $players,
            'winners' => $winners,
            'round' => $round,
            'state' => $state,
            'totalPlayers' => count($players),
            'mercure_public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure',
            'mercure_token' => $token,
        ]);
    }

    #[Route('/board/{code}/check-inactive', name: 'board_check_inactive', methods: ['POST'])]
    public function checkInactive(string $code): JsonResponse
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            return new JsonResponse(['error' => 'Tombola not found'], 404);
        }

        $removedPlayerIds = $this->tombolaManager->removeInactivePlayers($code, 6);
        $totalPlayers = count($this->tombolaManager->getOnlinePlayers($code));
        
        foreach ($removedPlayerIds as $removedPlayerId) {
            try {
                $this->mercurePublisher->publishPlayerLeft($code, $removedPlayerId, $totalPlayers);
            } catch (\Exception $e) {
            }
        }

        return new JsonResponse(['success' => true, 'removed' => count($removedPlayerIds)]);
    }

    #[Route('/board/{code}/enter-fullscreen', name: 'board_enter_fullscreen', methods: ['POST'])]
    public function enterFullscreen(string $code): JsonResponse
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            return new JsonResponse(['error' => 'Tombola not found'], 404);
        }

        $this->tombolaManager->setState($code, 'in_round');
        $this->tombolaManager->freezePlayers($code);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    #[Route('/board/{code}/start-round', name: 'board_start_round', methods: ['POST'])]
    public function startRound(string $code): JsonResponse
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            return new JsonResponse(['error' => 'Tombola not found'], 404);
        }

        $players = $this->tombolaManager->getActivePlayers($code);
        
        if (count($players) < 1) {
            return new JsonResponse(['error' => 'Need at least 1 player'], 400);
        }

        $winner = $this->tombolaManager->selectWinner($code);
        $round = $this->tombolaManager->getRound($code);

        $this->mercurePublisher->publishRoundStarted($code, $winner['id'], $round);

        return new JsonResponse([
            'success' => true,
            'winner' => $winner,
            'round' => $round,
        ]);
    }

    #[Route('/board/{code}/notify-winner', name: 'board_notify_winner', methods: ['POST'])]
    public function notifyWinner(string $code, Request $request): JsonResponse
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            return new JsonResponse(['error' => 'Tombola not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $winnerId = $data['winnerId'] ?? null;
        
        if (!$winnerId) {
            return new JsonResponse(['error' => 'Winner ID required'], 400);
        }

        $round = $this->tombolaManager->getRound($code);
        $this->mercurePublisher->publishWinnerRevealed($code, $winnerId, $round);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/board/{code}/next-round', name: 'board_next_round', methods: ['POST'])]
    public function nextRound(string $code): JsonResponse
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            return new JsonResponse(['error' => 'Tombola not found'], 404);
        }

        $this->tombolaManager->nextRound($code);
        $round = $this->tombolaManager->getRound($code);

        $this->mercurePublisher->publishNextRoundReady($code, $round);

        return new JsonResponse([
            'success' => true,
            'round' => $round,
        ]);
    }
}
