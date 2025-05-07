<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity]
#[ORM\Table(name: 'destination')]
class Destination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idDestination', type: 'integer')]
    private ?int $idDestination = null;

    #[ORM\Column(name: 'pays', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le champ pays est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Le pays doit avoir au moins 2 caractères.",
        maxMessage: "Le pays doit avoir au plus 50 caractères."
    )]
    private ?string $pays = null;

    #[ORM\Column(name: 'ville', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le champ ville est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "La ville doit avoir au moins 2 caractères.",
        maxMessage: "La ville doit avoir au plus 50 caractères."
    )]
    private ?string $ville = null;

    #[ORM\Column(name: 'code_postal', type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: "Le champ code postal est obligatoire.")]
    private ?int $codePostal = null;

    #[ORM\Column(name: 'latitude', type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "Le champ latitude est obligatoire.")]
    #[Assert\Type(type: "float")]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "The longitude must be between -90 and 90."
    )]
    private ?float $latitude = null;

    #[ORM\Column(name: 'longitude', type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "Le champ longitude est obligatoire.")]
    #[Assert\Type(type: "float")]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "The longitude must be between -180 and 180."
    )]
    private ?float $longitude = null;

    #[ORM\OneToMany(mappedBy: 'iddestination', targetEntity: Activity::class, cascade: ['persist', 'remove'])]
    private Collection $activities;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
    }

    // Getters and Setters

    public function getIdDestination(): ?int
    {
        return $this->idDestination;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): self
    {
        $this->pays = $pays;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getCodePostal(): ?int
    {
        return $this->codePostal;
    }

    public function setCodePostal(?int $codePostal): self
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return Collection|Activity[]
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): self
    {
        if (!$this->activities->contains($activity)) {
            $this->activities[] = $activity;
            $activity->setIddestination($this);
        }
        return $this;
    }

    public function removeActivity(Activity $activity): self
    {
        if ($this->activities->removeElement($activity)) {
            // set the owning side to null (unless already changed)
            if ($activity->getIddestination() === $this) {
                $activity->setIddestination(null);
            }
        }
        return $this;
    }
}