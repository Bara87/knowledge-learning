<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un thème de formation
 * 
 * Cette entité gère :
 * - Les informations de base du thème (nom, description)
 * - Les cursus associés au thème
 * - Les certifications délivrées pour ce thème
 * - Le suivi des dates de création et modification
 */
#[ORM\Entity(repositoryClass: ThemeRepository::class)]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du thème
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Description détaillée du thème
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Date de création du thème
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification du thème
     */
    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    /**
     * Liste des cursus appartenant à ce thème
     * 
     * @var Collection<int, Cursus>
     */
    #[ORM\OneToMany(mappedBy: 'theme', targetEntity: Cursus::class)]
    private Collection $cursus;

    /**
     * Liste des certifications délivrées pour ce thème
     * 
     * @var Collection<int, Certification>
     */
    #[ORM\OneToMany(mappedBy: 'theme', targetEntity: Certification::class)]
    private Collection $certifications;

    /**
     * Constructeur
     * 
     * Initialise les collections et définit les dates de création/modification
     */
    public function __construct()
    {
        $this->cursus = new ArrayCollection();
        $this->certifications = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    /**
     * @return Collection<int, Cursus>
     */
    public function getCursus(): Collection
    {
        return $this->cursus;
    }

    /**
     * Ajoute un cursus au thème
     * 
     * @param Cursus $cursus Cursus à ajouter
     */
    public function addCursus(Cursus $cursus): static
    {
        if (!$this->cursus->contains($cursus)) {
            $this->cursus->add($cursus);
            $cursus->setTheme($this);
        }
        return $this;
    }

    /**
     * Retire un cursus du thème
     * 
     * @param Cursus $cursus Cursus à retirer
     */
    public function removeCursus(Cursus $cursus): static
    {
        if ($this->cursus->removeElement($cursus)) {
            if ($cursus->getTheme() === $this) {
                $cursus->setTheme(null);
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

    /**
     * Ajoute une certification au thème
     * 
     * @param Certification $certification Certification à ajouter
     */
    public function addCertification(Certification $certification): static
    {
        if (!$this->certifications->contains($certification)) {
            $this->certifications->add($certification);
            $certification->setTheme($this);
        }
        return $this;
    }

    /**
     * Retire une certification du thème
     * 
     * @param Certification $certification Certification à retirer
     */
    public function removeCertification(Certification $certification): static
    {
        if ($this->certifications->removeElement($certification)) {
            if ($certification->getTheme() === $this) {
                $certification->setTheme(null);
            }
        }
        return $this;
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
}