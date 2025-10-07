<?php

namespace App\Controller;

use App\Service\TombolaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private TombolaManager $tombolaManager
    ) {
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $code = $this->tombolaManager->createTombola();
        
        dump('Created tombola: ' . $code);
        dump('Exists check: ' . ($this->tombolaManager->tombolaExists($code) ? 'YES' : 'NO'));
        
        return $this->redirectToRoute('board', ['code' => $code]);
    }
}
