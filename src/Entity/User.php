<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $tokenExpiresAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Purchase::class)]
    private Collection $purchases;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LessonValidation::class)]
    private Collection $lessonValidations;

    public function __construct()
    {
        $this->purchases = new ArrayCollection();
        $this->lessonValidations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    public function setActivationToken(?string $activationToken): static
    {
        $this->activationToken = $activationToken;
        return $this;
    }

    public function getTokenExpiresAt(): ?\DateTime
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTime $tokenExpiresAt): static
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
        return $this;
    }

    /**
     * @return Collection<int, Purchase>
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    public function addPurchase(Purchase $purchase): static
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases->add($purchase);
            $purchase->setUser($this);
        }
        return $this;
    }

    public function removePurchase(Purchase $purchase): static
    {
        if ($this->purchases->removeElement($purchase)) {
            if ($purchase->getUser() === $this) {
                $purchase->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, LessonValidation>
     */
    public function getLessonValidations(): Collection
    {
        return $this->lessonValidations;
    }

    public function addLessonValidation(LessonValidation $lessonValidation): static
    {
        if (!$this->lessonValidations->contains($lessonValidation)) {
            $this->lessonValidations->add($lessonValidation);
            $lessonValidation->setUser($this);
        }
        return $this;
    }

    public function removeLessonValidation(LessonValidation $lessonValidation): static
    {
        if ($this->lessonValidations->removeElement($lessonValidation)) {
            if ($lessonValidation->getUser() === $this) {
                $lessonValidation->setUser(null);
            }
        }
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}