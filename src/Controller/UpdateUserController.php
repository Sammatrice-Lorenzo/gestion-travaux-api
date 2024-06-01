<?php

namespace App\Controller;

use Exception;
use App\Entity\User;
use App\Service\ApiService;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class UpdateUserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ApiService $apiService,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    /**
     * @throws Exception
     */
    #[Route(path: '/api/user/edit/{id}', name: 'user_edit',  methods: ['PUT'])]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $data = json_decode($request->getContent());
        $token = $request->headers->get('authorization');

        if (!$token || !$user) {
            $responseToken = $this->apiService->getErrorToken();
            $responseUser = $this->apiService->getErrorUser();

            return !$token ? $responseToken : $responseUser;
        }
        
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        try {
            $this->apiService->isValidTokenString($user, $token);
        } catch (Exception $e) {
            throw new AuthenticationException('Invalid token');
        }

        try {
            $user->validateEmail($this->userRepository, $data->email);
        } catch (\Throwable $th) {
            return $this->json(['Unprocessable entity' => $th->getMessage()], 422);
        }

        // Mettre à jour les attributs de l'utilisateur
        $user->setFirstname($data->firstname ?? $user->getFirstname())
            ->setLastname($data->lastname ?? $user->getLastname())
            ->setEmail($data->email ?? $user->getEmail())
        ;

        $em->flush();


        $newToken = $this->jwtManager->create($user);
        $payload = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'token' => $newToken,
        ];
    
        return new JsonResponse($payload);
    }
}
