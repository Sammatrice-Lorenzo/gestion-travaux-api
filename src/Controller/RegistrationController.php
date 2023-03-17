<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\Mail;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
    ) {}

    #[Route('/api/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $jsonData = json_decode($request->getContent());
        $user = (new User())
            ->setEmail($jsonData->email)
        ;

        $errors = $this->customValidationRegistration($request->getContent());
        if (!$errors) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $jsonData->password
                )
            );

            $em->persist($user);
            $em->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('dev-app77@outlook.fr'))
                    // ->from($_ENV['MAIL_USERNAME'])
                    ->to(new Address($user->getEmail()))
                    ->subject('Confirmer votre compte')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            return new JsonResponse([
                'success' => true,
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ]);
        }

    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            dd($translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app');
        }

        $this->addFlash('success', 'Votre email a été bien confirmé.');

        return $this->redirectToRoute('app');
    }

    /**
     * Permet de vérifier les données
     *
     * @param string $data
     * @return array
     */
    public function customValidationRegistration(string $data): array
    {
        $data = json_decode($data);
        $errors = [];

        if (
            !property_exists($data, 'email') ||
            !property_exists($data, 'password') ||
            !property_exists($data, 'confirmPassword')
        ) {
            return ['Problème de requête'];
        }

        if (!isset($data->email) || strlen($data->email) > 255 || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Votre mail n\'est pas correct';
    
        } //elseif (!checkdnsrr(substr($data->email, strpos($data->email, '@') + 1), 'MX')) {
        //     $errors[] = 'Votre mail n\'est pas valide!';
        // }
    
        // if (
        //     !isset($data->password) ||
        //     !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\~?!@#\$%\^&\*])(?=.{8,})/', $data->password)
        // ) {
        //     $errors[] = 'Votre mot de passe ne contient pas les caractères spéciaux!';
        // }
    
        if ($data->confirmPassword !== $data->password) {
            $errors[] = 'Vos mot de passe de correspond pas';
        }
    
        return $errors;
    }
}
