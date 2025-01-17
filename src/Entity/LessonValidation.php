<?php

namespace App\Entity;

use App\Repository\LessonValidationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant la validation d'une leçon par un utilisateur
 * 
 * Cette entité gère :
 * - Le lien entre un utilisateur et une leçon validée
 * - La date de validation
 * - L'unicité des validations (une seule par utilisateur et leçon)
 */
#[ORM\Entity(repositoryClass: LessonValidationRepository::class)]
#[UniqueEntity(
    fields: ['user', 'lesson'],
    message: 'Cette leçon a déjà été validée par cet utilisateur.'
)]
class LessonValidation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Utilisateur ayant validé la leçon
     */
    #[ORM\ManyToOne(inversedBy: 'lessonValidations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $user = null;

    /**
     * Leçon validée
     */
    #[ORM\ManyToOne(inversedBy: 'validations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Lesson $lesson = null;

    /**
     * Date de validation de la leçon
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $validatedAt = null;

    /**
     * Date de création de l'enregistrement
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Constructeur
     * 
     * Initialise les dates de validation et de création
     */
    public function __construct()
    {
        $this->validatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): static
    {
        $this->lesson = $lesson;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
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

    /**
     * Vérifie si la leçon est validée
     * 
     * Note: Cette méthode retourne toujours true car une validation
     * est considérée comme valide dès sa création
     * 
     * @return bool Toujours true
     */
    public function isValidated(): bool
    {
        return true;
    }
}