<?php

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\PasswordStrength;

final class RegisterInput
{
    #[Assert\NotBlank]
    #[Groups([User::GROUP_USER_WRITE])]
    public string $firstname;
    
    #[Assert\NotBlank]
    #[Groups([User::GROUP_USER_WRITE])]
    public string $lastname;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups([User::GROUP_USER_WRITE])]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\PasswordStrength([
        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
        'message' => 'Votre mot de passe est trop faible !',
    ])]
    #[Assert\Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins 6 caractères !')]
    #[Groups([User::GROUP_USER_WRITE])]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\EqualTo(propertyPath: 'password', message: 'Le deux mots de passes ne correspondants pas !')]
    #[Groups([User::GROUP_USER_WRITE])]
    public string $confirmPassword;
}
