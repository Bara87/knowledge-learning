<?php

namespace App\Entity;

use App\Repository\CursusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un cursus de formation
 * 
 * Cette entité gère :
 * - Les informations de base du cursus (titre, description, prix)
 * - Les leçons associées au cursus
 * - Les achats effectués pour ce cursus
 * - Le suivi de la progression des utilisateurs
 */
#[ORM\Entity(repositoryClass: CursusRepository::class)]
class Cursus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Titre du cursus
     */
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * Description détaillée du cursus
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Prix du cursus
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    /**
     * Thème auquel appartient le cursus
     */
    #[ORM\ManyToOne(inversedBy: 'cursus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Theme $theme = null;

    /**
     * Date de création du cursus
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification du cursus
     */
    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    /**
     * Niveau de difficulté du cursus
     */
    #[ORM\Column(length: 20)]
    private ?string $level = null;

    /**
     * Liste des leçons du cursus
     * 
     * @var Collection<int, Lesson>
     */
    #[ORM\OneToMany(mappedBy: 'cursus', targetEntity: Lesson::class)]
    private Collection $lessons;

    /**
     * Liste des achats effectués pour ce cursus
     * 
     * @var Collection<int, Purchase>
     */
    #[ORM\OneToMany(mappedBy: 'cursus', targetEntity: Purchase::class)]
    private Collection $purchases;

    /**
     * Image miniature du cursus
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail = null;

    /**
     * Constructeur
     * 
     * Initialise les collections et définit les dates de création/modification
     */
    public function __construct()
    {
        $this->lessons = new ArrayCollection();
        $this->purchases = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setCursus($this);
        }
        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson)) {
            if ($lesson->getCursus() === $this) {
                $lesson->setCursus(null);
            }
        }
        return $this;
    }
        

    /**
     * Compte le nombre de leçons validées par un utilisateur
     * 
     * @param User|null $user Utilisateur dont on veut compter les validations
     * @return int Nombre de leçons validées
     */
    public function getValidatedLessonsCount(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        $count = 0;
        foreach ($this->lessons as $lesson) {
            if ($lesson->isValidatedByUser($user)) {
                $count++;
            }
        }
        return $count;
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
            $purchase->setCursus($this);
        }
        return $this;
    }

    public function removePurchase(Purchase $purchase): static
    {
        if ($this->purchases->removeElement($purchase)) {
            if ($purchase->getCursus() === $this) {
                $purchase->setCursus(null);
            }
        }
        return $this;
    }

        /**
     * Calcule la durée totale du cursus en minutes
     * 
     * @return int Durée totale en minutes
     */
    public function getTotalDuration(): int
    {
        $total = 0;
        foreach ($this->lessons as $lesson) {
            $total += $lesson->getDuration() ?? 0;
        }
        return $total;
    }

    /**
     * Met à jour la date de modification
     * 
     * Cette méthode est appelée automatiquement avant chaque mise à jour
     */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }

        /**
     * Calcule le pourcentage de progression d'un utilisateur dans ce cursus
     * 
     * @param User|null $user Utilisateur dont on veut calculer la progression
     * @return float Pourcentage de progression (0-100)
     */
    public function getProgressForUser(?User $user): float
    {
        if (!$user) {
            return 0;
        }

        $totalLessons = $this->lessons->count();
        if ($totalLessons === 0) {
            return 0;
        }

        $validatedLessons = 0;
        foreach ($this->lessons as $lesson) {
            if ($lesson->isValidatedByUser($user)) {
                $validatedLessons++;
            }
        }

        return round(($validatedLessons / $totalLessons) * 100);
    }
}