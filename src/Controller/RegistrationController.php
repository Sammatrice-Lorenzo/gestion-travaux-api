<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use App\Service\ApiService;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepo,
    ) {}

    #[Route('/api/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $jsonData = json_decode($request->getContent());
        $user = (new User())
            ->setFirstname($jsonData->firstname)
            ->setLastname($jsonData->lastname)
            ->setEmail($jsonData->email)
            ->setRoles(['ROLE_USER'])
        ;

        try {
            $user->validateEmail($this->userRepo, $jsonData->email, isCreation: true);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'code' => '422',
                'Unprocessable entity' => $th->getMessage()
            ], 422);
        }

        $errors = $this->customValidationRegistration($request->getContent());
        $response = ApiService::getJsonResponseErrorForRegistrationUser($errors);
        if (!$errors) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $jsonData->password
                )
            );

            $this->em->persist($user);
            $this->em->flush();

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

            $response = ApiService::getJsonResponseSuccessForRegistrationUser();
        }

        return $response;
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
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

        return new RedirectResponse($_ENV['URL_GESTION_TRAVAUX_PWA']);
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
