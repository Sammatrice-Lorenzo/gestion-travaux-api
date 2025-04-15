<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Work;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\ClientTrait;
use App\Controller\ClientController;
use App\Repository\ClientRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ApiResource(
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(
            name: 'getClientsByUser',
            uriTemplate: '/clientsByUser/{id}',
            controller: ClientController::class,
            read: false,
            security: 'is_granted("ROLE_USER")',
            openapi: new Operation(
                security: [['bearerAuth' => []]],
                parameters: [
                    new Parameter(
                        name: 'id',
                        in: 'path',
                        required: true,
                        description: 'The user ID',
                        schema: ['type' => 'string']
                    )
                ]
            )
        )
    ],
    normalizationContext: ['groups' => 'read:Client'],
)]
#[ApiResource]
class Client
{
    use ClientTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups (['read:Client', 'read:Work', 'read:EventDay'])]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups (['read:Client', 'read:Work'])]
    private string $firstname;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Client', 'read:Work'])]
    private string $lastname;
    
    #[ORM\Column(length: 255)]
    #[Groups(['read:Client', 'read:Work'])]
    #[Assert\Regex(pattern: '/^0[1-9](?:[\s.-]?[0-9]{2}){4}$/', message: 'Insérer un numéro de téléphone valide')]
    private string $phoneNumber;
    
    #[ORM\Column(length: 255)]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Insérer un code postale valide')]
    #[Groups(['read:Client', 'read:Work'])]
    private string $postalCode;
    
    #[ORM\Column(length: 255)]
    #[Groups(['read:Client', 'read:Work'])]
    private string $city;
    
    #[ORM\Column(length: 255)]
    #[Groups(['read:Client', 'read:Work'])]
    private string $streetAddress;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'clients')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Client', 'read:Work'])]
    private User $user;

    /**
     * @var Collection<int, Work>|null
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Work::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $works = null;

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

    final public function removeWork(Work $work): self
    {
        if ($this->works->removeElement($work) && $work->getClient() === $this) {
            // set the owning side to null (unless already changed)
            $work->setClient(null);
        }

        return $this;
    }
}
