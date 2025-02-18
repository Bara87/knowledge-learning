<?php

namespace App\Entity;

use App\Repository\PurchaseRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

/**
 * Entité représentant un achat dans le système
 * 
 * Cette entité gère :
 * - Les achats de cursus complets
 * - Les achats de leçons individuelles
 * - Le suivi des paiements via Stripe
 * - L'historique des transactions
 */
#[ORM\Entity(repositoryClass: PurchaseRepository::class)]
class Purchase
{
    /**
     * Constantes de statut
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    /**
     * Liste des statuts valides
     */
    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_EXPIRED,
        self::STATUS_FAILED
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Utilisateur ayant effectué l'achat
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'purchases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Cursus acheté (si achat de cursus complet)
     */
    #[ORM\ManyToOne(targetEntity: Cursus::class, inversedBy: 'purchases')]
    private ?Cursus $cursus = null;

    /**
     * Leçon achetée (si achat individuel)
     */
    #[ORM\ManyToOne(targetEntity: Lesson::class, inversedBy: 'purchases')]
    private ?Lesson $lesson = null;

    /**
     * Montant de l'achat
     */
    #[ORM\Column]
    private float $amount;

    /**
     * Statut de l'achat
     */
    #[ORM\Column(length: 255)]
    private string $status = self::STATUS_PENDING;

    /**
     * Identifiant de la session Stripe
     */
    #[ORM\Column(length: 255)]
    private string $stripeSessionId;

    /**
     * Date de création de l'achat
     */
    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /**
     * Date de dernière modification de l'achat
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Constructeur
     * 
     * Initialise la date de création
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCursus(): ?Cursus
    {
        return $this->cursus;
    }

    public function setCursus(?Cursus $cursus): self
    {
        $this->cursus = $cursus;
        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): self
    {
        $this->lesson = $lesson;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException(sprintf(
                'Statut invalide. Les statuts valides sont : %s',
                implode(', ', self::VALID_STATUSES)
            ));
        }
        $this->status = $status;
        return $this;
    }

    public function getStripeSessionId(): string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Vérifie si l'achat est complété
     * 
     * @return bool True si le statut est 'completed'
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Vérifie si l'achat est en attente
     * 
     * @return bool True si le statut est 'pending'
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifie si l'achat est expiré
     * 
     * @return bool True si le statut est 'expired'
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}