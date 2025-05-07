<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'activity')]
#[ORM\Index(columns: ['idDestination'], name: 'idDestination')]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_activity', type: 'integer')]
    private ?int $idActivity = null;

    #[ORM\Column(name: 'nom_activity', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le champ nom de l'activité est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom de l'activité doit avoir au moins 2 caractères.",
        maxMessage: "Le nom de l'activité doit avoir au plus 255 caractères."
    )]
    private ?string $nomActivity = null;

    #[ORM\Column(name: 'image_activity', type: 'string', length: 255, nullable: true)]
    private ?string $imageActivity = null;

    #[ORM\Column(name: 'type', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le champ type est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le type doit avoir au moins 2 caractères.",
        maxMessage: "Le type doit avoir au plus 255 caractères."
    )]
    private ?string $type = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "Le champ description est obligatoire.")]
    private ?string $description = null;

    #[ORM\Column(name: 'activity_price', type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "Le champ prix est obligatoire.")]
    #[Assert\Type(
        type: "float",
        message: "Le prix doit être un nombre."
    )]
    #[Assert\Positive(message: "Le prix doit être positif.")]
    private ?float $activityPrice = null;

    #[ORM\Column(name: 'image_activity2', type: 'string', length: 255, nullable: true)]
    private ?string $imageActivity2 = null;

    #[ORM\Column(name: 'image_activity3', type: 'string', length: 255, nullable: true)]
    private ?string $imageActivity3 = null;

    #[ORM\Column(name: 'date_activite', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: "Le champ date d'activité est obligatoire.")]
    private ?\DateTimeInterface $dateActivite = null;

    #[ORM\ManyToOne(targetEntity: Destination::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(name: 'idDestination', referencedColumnName: 'idDestination')]
    #[Assert\NotNull(message: "La destination est obligatoire.")]
    private ?Destination $iddestination = null;

    // Getters and Setters

    public function getIdActivity(): ?int
    {
        return $this->idActivity;
    }

    public function getNomActivity(): ?string
    {
        return $this->nomActivity;
    }

    public function setNomActivity(?string $nomActivity): self
    {
        $this->nomActivity = $nomActivity;
        return $this;
    }

    public function getImageActivity(): ?string
    {
        return $this->imageActivity;
    }

    public function setImageActivity(?string $imageActivity): self
    {
        $this->imageActivity = $imageActivity;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getActivityPrice(): ?float
    {
        return $this->activityPrice;
    }

    public function setActivityPrice(?float $activityPrice): self
    {
        $this->activityPrice = $activityPrice;
        return $this;
    }

    public function getImageActivity2(): ?string
    {
        return $this->imageActivity2;
    }

    public function setImageActivity2(?string $imageActivity2): self
    {
        $this->imageActivity2 = $imageActivity2;
        return $this;
    }

    public function getImageActivity3(): ?string
    {
        return $this->imageActivity3;
    }

    public function setImageActivity3(?string $imageActivity3): self
    {
        $this->imageActivity3 = $imageActivity3;
        return $this;
    }

    public function getDateActivite(): ?\DateTimeInterface
    {
        return $this->dateActivite;
    }

    public function setDateActivite(?\DateTimeInterface $dateActivite): self
    {
        $this->dateActivite = $dateActivite;
        return $this;
    }

    public function getIddestination(): ?Destination
    {
        return $this->iddestination;
    }

    public function setIddestination(?Destination $iddestination): self
    {
        $this->iddestination = $iddestination;
        return $this;
    }
}