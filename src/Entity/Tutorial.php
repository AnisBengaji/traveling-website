<?php

namespace App\Entity;

use App\Repository\TutorialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TutorialRepository::class)]
class Tutorial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du tutorial est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le nom du tutorial doit faire au moins {{ limit }} caractères",
        maxMessage: "Le nom du tutorial ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $nom_tutorial = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[Assert\Type(type: "\DateTimeInterface", message: "La date de début doit être une date valide")]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "La date de début doit être égale ou postérieure à aujourd'hui"
    )]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date', name: 'date_fin')]
    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[Assert\Type("\DateTimeInterface", message: "La date de fin doit être une date valide")]
    #[Assert\Expression(
        "this.getDateFin() > this.getDateDebut()",
        message: "La date de fin doit être postérieure à la date de début"
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: "Le prix du tutorial est obligatoire")]
    #[Assert\Type(type: "float", message: "Le prix doit être un nombre")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    private ?float $prix_tutorial = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'URL est obligatoire")]
    #[Assert\Url(message: "L'URL doit être valide")]
    private ?string $url = null;

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: 'tutorials')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: "L'offre est obligatoire")]
    private ?Offre $offre = null;

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomTutorial(): ?string
    {
        return $this->nom_tutorial;
    }

    public function setNomTutorial(string $nom_tutorial): self
    {
        $this->nom_tutorial = $nom_tutorial;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getPrixTutorial(): ?float
    {
        return $this->prix_tutorial;
    }

    public function setPrixTutorial(float $prix_tutorial): self
    {
        $this->prix_tutorial = $prix_tutorial;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getOffre(): ?Offre
    {
        return $this->offre;
    }

    public function setOffre(?Offre $offre): self
    {
        $this->offre = $offre;
        return $this;
    }
}