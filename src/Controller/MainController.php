<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        $events = ["Event Pierre Engine","Event Dorian & Fouzi's Quest","Event Hello World","Event Justin Games"];
        return $this->render('main/main.html.twig', [
            'events' => $events,
        ]);
    }
}
