<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idCategory")]
    private ?int $idCategory = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The category name cannot be empty.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'The category name must be at least {{ limit }} characters long.',
        maxMessage: 'The category name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $nomCategory = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The description cannot be empty.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'The description must be at least {{ limit }} characters long.',
        maxMessage: 'The description cannot be longer than {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Publication::class)]
    private Collection $publications;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
        
    }

    public function getIdCategory(): ?int
    {
        return $this->idCategory;
    }

    public function setIdCategory(int $idCategory): static
    {
        $this->idCategory = $idCategory;
        return $this;
    }

    public function getNomCategory(): ?string
    {
        return $this->nomCategory;
    }

    public function setNomCategory(string $nomCategory): static
    {
        $this->nomCategory = $nomCategory;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    // Optional: Keep getId() if needed by your system
    public function getId(): ?int
    {
        return $this->idCategory;
    }

    /**
     * @return Collection<int, Publication>
     */
    public function getPublications(): Collection
    {
        return $this->publications;
    }

    public function addPublication(Publication $publication): static
    {
        if (!$this->publications->contains($publication)) {
            $this->publications[] = $publication;
            $publication->setCategory($this);
        }
        return $this;
    }

    public function removePublication(Publication $publication): static
    {
        if ($this->publications->removeElement($publication)) {
            if ($publication->getCategory() === $this) {
                $publication->setCategory(null);
            }
        }
        return $this;
    }
}