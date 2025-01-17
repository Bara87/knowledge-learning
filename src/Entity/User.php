<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité représentant un utilisateur du système
 * 
 * Cette entité gère :
 * - Les informations d'authentification (email, mot de passe, rôles)
 * - L'activation du compte et la vérification de l'email
 * - Les achats, validations de leçons et certifications
 * - Le suivi de la progression dans les cours
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Email de l'utilisateur (identifiant unique)
     */
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * Rôles de l'utilisateur (ROLE_USER par défaut)
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe hashé de l'utilisateur
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * État d'activation du compte
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    /**
     * Token pour l'activation du compte
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activationToken = null;

    /**
     * Date d'expiration du token d'activation
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $tokenExpiresAt = null;

    /**
     * Liste des achats de l'utilisateur
     * 
     * @var Collection<int, Purchase>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Purchase::class)]
    private Collection $purchases;

    /**
     * Liste des leçons validées par l'utilisateur
     * 
     * @var Collection<int, LessonValidation>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LessonValidation::class)]
    private Collection $lessonValidations;

    /**
     * Liste des certifications obtenues par l'utilisateur
     * 
     * @var Collection<int, Certification>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Certification::class)]
    private Collection $certifications;

    /**
     * État de vérification de l'email
     */
    #[ORM\Column]
    private bool $isVerified = false;

    /**
     * Token pour la réinitialisation du mot de passe
     */
    private ?string $resetToken = null;

    /**
     * Date d'expiration du token de réinitialisation
     */
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    public function __construct()
    {
        $this->purchases = new ArrayCollection();
        $this->lessonValidations = new ArrayCollection();
        $this->certifications = new ArrayCollection();
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
     * Vérifie si l'utilisateur a acheté un cursus spécifique
     * 
     * @param Cursus $cursus Cursus à vérifier
     * @return bool True si l'utilisateur a acheté le cursus
     */
    public function hasPurchasedCursus(Cursus $cursus): bool
    {
        foreach ($this->purchases as $purchase) {
            if ($purchase->getCursus() === $cursus) {
                return true;
            }
        }
        return false;
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

    /**
     * @return Collection<int, Certification>
     */
    public function getCertifications(): Collection
    {
        return $this->certifications;
    }

    public function addCertification(Certification $certification): static
    {
        if (!$this->certifications->contains($certification)) {
            $this->certifications->add($certification);
            $certification->setUser($this);
        }
        return $this;
    }

    public function removeCertification(Certification $certification): static
    {
        if ($this->certifications->removeElement($certification)) {
            if ($certification->getUser() === $this) {
                $certification->setUser(null);
            }
        }
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function isActivated(): bool
    {
        return $this->isActive;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur a complété un thème
     * 
     * Un thème est considéré comme complété si toutes les leçons
     * de tous les cursus du thème ont été validées
     * 
     * @param Theme $theme Thème à vérifier
     * @return bool True si le thème est complété
     */
    public function hasCompletedTheme(Theme $theme): bool
    {
        $totalLessons = 0;
        $validatedLessons = 0;

        foreach ($theme->getCursus() as $cursus) {
            foreach ($cursus->getLessons() as $lesson) {
                $totalLessons++;
                if ($lesson->isValidatedByUser($this)) {
                    $validatedLessons++;
                }
            }
        }

        return $totalLessons > 0 && $validatedLessons === $totalLessons;
    }

    /**
     * Calcule le pourcentage de progression sur un thème
     * 
     * @param Theme $theme Thème dont on veut calculer la progression
     * @return float Pourcentage de progression (0-100)
     */
    public function getThemeProgress(Theme $theme): float
    {
        $totalLessons = 0;
        $validatedLessons = 0;

        foreach ($theme->getCursus() as $cursus) {
            foreach ($cursus->getLessons() as $lesson) {
                $totalLessons++;
                if ($lesson->isValidatedByUser($this)) {
                    $validatedLessons++;
                }
            }
        }

        if ($totalLessons === 0) {
            return 0;
        }

        return round(($validatedLessons / $totalLessons) * 100);
    }

    /**
     * Calcule le pourcentage de progression sur un cursus
     * 
     * @param Cursus $cursus Cursus dont on veut calculer la progression
     * @return float Pourcentage de progression (0-100)
     */
    public function getCursusProgress(Cursus $cursus): float
    {
        $totalLessons = $cursus->getLessons()->count();
        if ($totalLessons === 0) {
            return 0;
        }

        $validatedLessons = 0;
        foreach ($cursus->getLessons() as $lesson) {
            if ($lesson->isValidatedByUser($this)) {
                $validatedLessons++;
            }
        }

        return round(($validatedLessons / $totalLessons) * 100);
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }
}
