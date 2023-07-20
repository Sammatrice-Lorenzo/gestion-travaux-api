<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Work;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use App\Controller\ClientController;
use App\Repository\ClientRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
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
            openapiContext: [
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'description' => 'The user ID',
                    ]
                ]
            ]
        )
    ],
    normalizationContext: ['groups' => 'read:Client'],
)]
#[ApiResource]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[Groups(['read:Client', 'read:Work'])]

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;
    #[Groups(['read:Client', 'read:Work'])]

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;
    #[Groups(['read:Client', 'read:Work'])]

    #[ORM\Column(length: 255)]
    #[Assert\Regex(pattern: '/^(0|\+33|0033)[1-9](\d{2}){4}$/', message: 'Insérer un numéro de téléphone valide')]
    private ?string $phoneNumber = null;
    #[Groups(['read:Client', 'read:Work'])]
    
    #[ORM\Column(length: 255)]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Insérer un code postale valide')]
    private ?string $postalCode = null;
    #[Groups(['read:Client', 'read:Work'])]

    #[ORM\Column(length: 255)]
    private ?string $city = null;
    #[Groups(['read:Client', 'read:Work'])]

    #[ORM\Column(length: 255)]
    private ?string $streetAddress = null;
    #[Groups(['read:Client', 'read:Work'])]

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'clients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    public function setStreetAddress(?string $streetAddress): void
    {
        $this->streetAddress = $streetAddress;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Work>
     */
    public function getWorks(): Collection
    {
        return $this->works;
    }

    public function addWork(Work $work): self
    {
        if (!$this->works->contains($work)) {
            $this->works->add($work);
            $work->setClient($this);
        }

        return $this;
    }

    public function removeWork(Work $work): self
    {
        if ($this->works->removeElement($work) && $work->getClient() === $this) {
            // set the owning side to null (unless already changed)
            $work->setClient(null);
        }

        return $this;
    }
}
