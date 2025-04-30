<?php

namespace App\Entity;

use App\Entity\Work;
use App\Entity\Client;
use App\DTO\RegisterInput;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Controller\UserController;
use App\Repository\UserRepository;
use App\Processor\RegisterProcessor;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ApiResource(
    openapi: new Operation(
        security: [['bearerAuth' => []]]
    ),
    operations: [
        new Get(
            name: 'user',
            uriTemplate: '/user',
            controller: UserController::class,
            read: false,
            security: 'is_granted("ROLE_USER")',
            openapi: new Operation(
                security: [['bearerAuth' => []]]
            ),
        ),
        new Put(
            security: "is_granted('EDIT_USER', object)",
            openapi: new Operation(
                security: [['bearerAuth' => []]]
            ),
        ),
        new Post(
            uriTemplate: '/register',
            name: 'register_user',
            input: RegisterInput::class,
            processor: RegisterProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un utilisateur ne peut avoir un seul unique email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, JWTUserInterface
{
    private const string GROUP_USER_READ = 'user:read';

    public const string GROUP_USER_WRITE = 'user:write';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_USER_READ, WorkEventDay::GROUP_WORK_EVENT_DAY_READ, Client::GROUP_CLIENT_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_USER_READ, self::GROUP_USER_WRITE])]
    #[NotBlank]
    private string $firstname;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_USER_READ, self::GROUP_USER_WRITE])]
    #[NotBlank]
    private string $lastname;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups([self::GROUP_USER_READ, self::GROUP_USER_WRITE])]
    #[Assert\Regex(pattern: '/^[^\s@]+@[^\s@]+\.[^\s@]+$/', message: 'Insérer un email valide')]
    #[NotBlank]
    private string $email;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isVerified = false;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'user')]
    private Collection $clients;

    /**
     * @var Collection<int, Work>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Work::class)]
    private Collection $works;

    /**
     * @var Collection<int, WorkEventDay>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WorkEventDay::class, orphanRemoval: true)]
    private Collection $workEventDays;

    /**
     * @var Collection<int, ProductInvoiceFile>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProductInvoiceFile::class)]
    private Collection $productInvoiceFiles;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->works = new ArrayCollection();
        $this->workEventDays = new ArrayCollection();
        $this->productInvoiceFiles = new ArrayCollection();
    }

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    final public function getEmail(): string
    {
        return $this->email;
    }

    final public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    final public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return string[]
     */
    final public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param string[] $roles
     *
     * @return self
     */
    final public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    final public function getPassword(): string
    {
        return $this->password;
    }

    final public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    final public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    final public function isVerified(): bool
    {
        return $this->isVerified;
    }

    final public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * Depuis JWT Interface.
     *
     * @param string $username
     * @param array<string, int|string> $payload
     *
     * @return User
     */
    public static function createFromPayload($username, array $payload): User
    {
        return (new User())
            ->setEmail($username)
            ->setFirstname($payload['firstname'])
            ->setLastname($payload['lastname'])
            ->setId($payload['id'])
        ;
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
            $work->setUser($this);
        }

        return $this;
    }

    final public function removeWork(Work $work): self
    {
        if ($this->works->removeElement($work) && $work->getUser() === $this) {
            // set the owning side to null (unless already changed)
            $work->setUser(null);
        }

        return $this;
    }

    /**
     * @return Client[]|Collection
     */
    final public function getClients(): Collection
    {
        return $this->clients;
    }

    final public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
            $client->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, WorkEventDay>
     */
    final public function getWorkEventDays(): Collection
    {
        return $this->workEventDays;
    }

    final public function addWorkEventDay(WorkEventDay $workEventDay): static
    {
        if (!$this->workEventDays->contains($workEventDay)) {
            $this->workEventDays->add($workEventDay);
            $workEventDay->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductInvoiceFile>
     */
    final public function getProductInvoiceFiles(): Collection
    {
        return $this->productInvoiceFiles;
    }

    final public function addProductInvoiceFile(ProductInvoiceFile $productInvoiceFile): static
    {
        if (!$this->productInvoiceFiles->contains($productInvoiceFile)) {
            $this->productInvoiceFiles->add($productInvoiceFile);
            $productInvoiceFile->setUser($this);
        }

        return $this;
    }
}
