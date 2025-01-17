<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant une leçon dans un cursus
 * 
 * Cette entité gère :
 * - Les informations de base de la leçon (titre, contenu, vidéo)
 * - Les validations par les utilisateurs
 * - Les achats individuels de leçons
 * - La navigation entre les leçons d'un cursus
 */
#[ORM\Entity(repositoryClass: LessonRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Titre de la leçon
     */
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * Contenu textuel de la leçon
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    /**
     * URL de la vidéo associée
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoUrl = null;

    /**
     * Prix de la leçon (si achat individuel)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    /**
     * Cursus auquel appartient la leçon
     */
    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cursus $cursus = null;

    /**
     * Date de création de la leçon
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification de la leçon
     */
    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    /**
     * Durée estimée de la leçon en minutes
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duration = null;

    /**
     * Liste des validations de la leçon par les utilisateurs
     * 
     * @var Collection<int, LessonValidation>
     */
    #[ORM\OneToMany(mappedBy: 'lesson', targetEntity: LessonValidation::class)]
    private Collection $validations;

    /**
     * Liste des achats individuels de la leçon
     * 
     * @var Collection<int, Purchase>
     */
    #[ORM\OneToMany(mappedBy: 'lesson', targetEntity: Purchase::class)]
    private Collection $purchases;

    /**
     * Constructeur
     * 
     * Initialise les collections et définit les dates de création/modification
     */
    public function __construct()
    {
        $this->validations = new ArrayCollection();
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;
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

    public function getCursus(): ?Cursus
    {
        return $this->cursus;
    }

    public function setCursus(?Cursus $cursus): static
    {
        $this->cursus = $cursus;
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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return Collection<int, LessonValidation>
     */
    public function getValidations(): Collection
    {
        return $this->validations;
    }

    public function addValidation(LessonValidation $validation): static
    {
        if (!$this->validations->contains($validation)) {
            $this->validations->add($validation);
            $validation->setLesson($this);
        }
        return $this;
    }

    public function removeValidation(LessonValidation $validation): static
    {
        if ($this->validations->removeElement($validation)) {
            if ($validation->getLesson() === $this) {
                $validation->setLesson(null);
            }
        }
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
            $purchase->setLesson($this);
        }
        return $this;
    }

    public function removePurchase(Purchase $purchase): static
    {
        if ($this->purchases->removeElement($purchase)) {
            if ($purchase->getLesson() === $this) {
                $purchase->setLesson(null);
            }
        }
        return $this;
    }

    /**
     * Vérifie si la leçon a été validée par l'utilisateur donné
     * 
     * @param User|null $user Utilisateur à vérifier
     * @return bool True si la leçon est validée par l'utilisateur
     */
    public function isValidatedByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($this->validations as $validation) {
            if ($validation->getUser() === $user && $validation->isValidated()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Récupère la date de validation pour un utilisateur
     * 
     * @param User|null $user Utilisateur concerné
     * @return \DateTimeInterface|null Date de validation ou null si non validée
     */
    public function getValidationDate(?User $user): ?\DateTimeInterface
    {
        if (!$user) {
            return null;
        }

        foreach ($this->validations as $validation) {
            if ($validation->getUser() === $user && $validation->isValidated()) {
                return $validation->getCreatedAt();
            }
        }
        return null;
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

    /**
     * Récupère la leçon précédente dans le même cursus
     * 
     * @return self|null Leçon précédente ou null si première leçon
     */
    public function getPrevious(): ?self
    {
        if (!$this->cursus) {
            return null;
        }

        $lessons = $this->cursus->getLessons()->toArray();
        $currentIndex = array_search($this, $lessons);
        
        if ($currentIndex === false || $currentIndex === 0) {
            return null;
        }

        return $lessons[$currentIndex - 1];
    }

    /**
     * Récupère la leçon suivante dans le même cursus
     * 
     * @return self|null Leçon suivante ou null si dernière leçon
     */
    public function getNext(): ?self
    {
        if (!$this->cursus) {
            return null;
        }

        $lessons = $this->cursus->getLessons()->toArray();
        $currentIndex = array_search($this, $lessons);
        
        if ($currentIndex === false || $currentIndex === count($lessons) - 1) {
            return null;
        }

        return $lessons[$currentIndex + 1];
    }

    /**
     * Génère l'URL d'intégration YouTube à partir de l'URL de la vidéo
     * 
     * @return string|null URL d'intégration ou null si pas de vidéo
     */
    public function getYoutubeEmbedUrl(): ?string
    {
        if (!$this->videoUrl) {
            return null;
        }

        // Extrait l'ID YouTube de différents formats d'URL
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        if (preg_match($pattern, $this->videoUrl, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        return null;
    }
}