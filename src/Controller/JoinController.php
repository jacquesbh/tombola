<?php

namespace App\Controller;

use App\Service\MercurePublisher;
use App\Service\TombolaManager;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class JoinController extends AbstractController
{
    public function __construct(
        private TombolaManager $tombolaManager,
        private MercurePublisher $mercurePublisher
    ) {
    }

    #[Route('/join/{code}', name: 'join', methods: ['GET'])]
    public function form(string $code): Response
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            throw $this->createNotFoundException('Tombola not found');
        }

        return $this->render('join/form.html.twig', [
            'code' => $code,
        ]);
    }

    #[Route('/join/{code}', name: 'join_submit', methods: ['POST'])]
    public function submit(string $code, Request $request): Response
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            throw $this->createNotFoundException('Tombola not found');
        }

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');

        if (empty($firstName) || empty($lastName) || empty($email)) {
            return $this->render('join/form.html.twig', [
                'code' => $code,
                'error' => 'All fields are required',
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('join/form.html.twig', [
                'code' => $code,
                'error' => 'Invalid email address',
            ]);
        }

        $player = $this->tombolaManager->addPlayer($code, $firstName, $lastName, $email);
        $totalPlayers = count($this->tombolaManager->getPlayers($code));

        try {
            $this->mercurePublisher->publishPlayerJoined($code, $player, $totalPlayers);
        } catch (\Exception $e) {
            // Log error but continue
        }

        return $this->redirectToRoute('join_online', ['code' => $code, 'playerId' => $player['id']]);
    }

    #[Route('/join/{code}/online/{playerId}', name: 'join_online', methods: ['GET'])]
    public function online(string $code, string $playerId): Response
    {
        if (!$this->tombolaManager->tombolaExists($code)) {
            throw $this->createNotFoundException('Tombola not found');
        }

        $players = $this->tombolaManager->getPlayers($code);
        $activePlayers = $this->tombolaManager->getActivePlayers($code);
        $state = $this->tombolaManager->getState($code);
        
        $player = null;
        foreach ($players as $p) {
            if ($p['id'] === $playerId) {
                $player = $p;
                break;
            }
        }

        if (!$player) {
            throw $this->createNotFoundException('Player not found');
        }

        $isActive = false;
        foreach ($activePlayers as $p) {
            if ($p['id'] === $playerId) {
                $isActive = true;
                break;
            }
        }

        $isPending = !$isActive && ($state === 'in_round' || $state === 'showing_winner');

        $jwtSecret = $_ENV['MERCURE_JWT_SECRET'] ?? '!ChangeThisMercureHubJWTSecretKey!';
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret)
        );
        
        $token = $config->builder()
            ->withClaim('mercure', ['subscribe' => ["tombola/{$code}/players"]])
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        return $this->render('join/online.html.twig', [
            'code' => $code,
            'player' => $player,
            'isPending' => $isPending,
            'mercure_public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure',
            'mercure_token' => $token,
        ]);
    }
}
