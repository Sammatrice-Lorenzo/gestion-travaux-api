<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Work;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\ClientTrait;
use App\Repository\ClientRepository;
use ApiPlatform\Metadata\ApiResource;
use App\Interface\UserOwnerInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Processor\UserAssignmentProcessor;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ApiResource(
    openapi: new Operation(
        security: [['bearerAuth' => []]],
    ),
    denormalizationContext: ['groups' => ['client:write']],
    normalizationContext: ['groups' => ['client:read']],
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            security: "is_granted('VIEW', object)"
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: UserAssignmentProcessor::class,
        ),
        new Put(
            security: "is_granted('EDIT', object)"
        ),
        new Delete(
            security: "is_granted('EDIT', object)"
        ),
    ],
)]
class Client implements UserOwnerInterface
{
    use ClientTrait;

    private const string GROUP_CLIENT_WRITE = 'client:write';

    public const string GROUP_CLIENT_READ = 'client:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_CLIENT_READ, Work::GROUP_WORK_READ, WorkEventDay::GROUP_WORK_EVENT_DAY_READ])]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_CLIENT_READ, Work::GROUP_WORK_READ, self::GROUP_CLIENT_WRITE])]
    #[NotBlank]
    private string $firstname;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_CLIENT_READ, self::GROUP_CLIENT_WRITE, Work::GROUP_WORK_READ])]
    #[NotBlank]
    private string $lastname;
    
    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_CLIENT_READ, self::GROUP_CLIENT_WRITE, Work::GROUP_WORK_READ])]
    #[Assert\Regex(pattern: '/^0[1-9](?:[\s.-]?[0-9]{2}){4}$/', message: 'Insérer un numéro de téléphone valide')]
    private string $phoneNumber;
    
    #[ORM\Column(length: 255)]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Insérer un code postale valide')]
    #[Groups([self::GROUP_CLIENT_READ, self::GROUP_CLIENT_WRITE, Work::GROUP_WORK_READ])]
    #[NotBlank]
    private string $postalCode;
    
    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_CLIENT_READ, self::GROUP_CLIENT_WRITE, Work::GROUP_WORK_READ])]
    #[NotBlank]
    private string $city;
    
    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_CLIENT_READ, self::GROUP_CLIENT_WRITE, Work::GROUP_WORK_READ])]
    #[NotBlank]
    private string $streetAddress;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'clients')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_CLIENT_READ, Work::GROUP_WORK_READ])]
    private User $user;

    /**
     * @var Collection<int, Work>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Work::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $works;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_CLIENT_READ, self::GROUP_CLIENT_WRITE, Work::GROUP_WORK_READ])]
    #[Assert\Regex(pattern: '/^[^\s@]+@[^\s@]+\.[^\s@]+$/', message: 'Insérer un email valide')]
    #[NotBlank]
    private string $email;

    public function __construct()
    {
        $this->works = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    final public function getFirstname(): string
    {
        return $this->firstname;
    }

    final public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    final public function getLastname(): string
    {
        return $this->lastname;
    }

    final public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    final public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    final public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    final public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }

    final public function setStreetAddress(string $streetAddress): self
    {
        $this->streetAddress = $streetAddress;

        return $this;
    }

    final public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    final public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    final public function getCity(): string
    {
        return $this->city;
    }

    final public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    final public function getUser(): User
    {
        return $this->user;
    }

    final public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Work>
     */
    final public function getWorks(): Collection
    {
        return $this->works;
    }

    final public function addWork(Work $work): self
    {
        if (!$this->works->contains($work)) {
            $this->works->add($work);
            $work->setClient($this);
        }

        return $this;
    }

    final public function getEmail(): string
    {
        return $this->email;
    }

    final public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
}
