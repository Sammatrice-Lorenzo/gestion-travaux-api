<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepo,
    ) {}

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     *
     * @throws VerifyEmailExceptionInterface
     *
     * @return RedirectResponse
     */
    #[Route(path: '/api/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): RedirectResponse
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $request->query->get('id')]);

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            $translator->trans($exception->getReason(), [], 'VerifyEmailBundle');

            return $this->redirectToRoute('app');
        }

        $this->addFlash('success', 'Votre email a été bien confirmé.');

        return new RedirectResponse($this->getParameter('url_front'));
    }
}
