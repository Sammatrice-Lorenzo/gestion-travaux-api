<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route(path: '/', name:'app', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/login', name:'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        $user = $this->getUser();

        return new JsonResponse([
            'username' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }

    #[Route(path: '/api/logout', name:'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
    }

}
