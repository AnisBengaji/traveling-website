<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'publication')]
#[ORM\HasLifecycleCallbacks]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_publication')]
    private ?int $id_publication = null;

    #[ORM\Column(name: 'title', length: 255)]
    #[Assert\NotBlank(message: 'The title cannot be empty.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'The title cannot be longer than {{ limit }} characters.'
    )]
    private ?string $title = null;

    #[ORM\Column(name: 'contenu', type: Types::TEXT)]
    #[Assert\NotBlank(message: 'The content cannot be empty.')]
    private ?string $contenu = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'The author cannot be null.')]
    private ?User $author = null;

    #[ORM\Column(name: 'visibility', length: 255)]
    #[Assert\NotBlank(message: 'The visibility cannot be empty.')]
    #[Assert\Choice(
        choices: ['public', 'private'],
        message: 'The visibility must be either "public" or "private".'
    )]
    private ?string $visibility = 'public';

    #[ORM\Column(name: 'date_publication', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(name: 'image', length: 255, nullable: true)]
    private ?string $image = null;

    private ?string $imageSource = null;

    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: Comment::class, cascade: ['remove'])]
    private Collection $comments;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'idCategory', referencedColumnName: 'idCategory', nullable: false)]
    #[Assert\NotNull(message: 'The category cannot be null.')]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: Like::class, cascade: ['remove'])]
    private Collection $likes;

    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: Dislike::class, cascade: ['remove'])]
    private Collection $dislikes;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->dislikes = new ArrayCollection();
    }

    public function getIdPublication(): ?int
    {
        return $this->id_publication;
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

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getImageSource(): ?string
    {
        return $this->imageSource;
    }

    public function setImageSource(?string $imageSource): static
    {
        $this->imageSource = $imageSource;
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    #[ORM\PrePersist]
    public function setDatePublicationAutomatically(): void
    {
        if ($this->datePublication === null) {
            $this->datePublication = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
        }
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes[] = $like;
            $like->setPublication($this);
        }
        return $this;
    }

    public function removeLike(Like $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getPublication() === $this) {
                $like->setPublication(null);
            }
        }
        return $this;
    }

    public function isLikedByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->likes->exists(function ($key, Like $like) use ($user) {
            return $like->getUser() === $user;
        });
    }

    public function getDislikes(): Collection
    {
        return $this->dislikes;
    }

    public function addDislike(Dislike $dislike): self
    {
        if (!$this->dislikes->contains($dislike)) {
            $this->dislikes[] = $dislike;
            $dislike->setPublication($this);
        }
        return $this;
    }

    public function removeDislike(Dislike $dislike): self
    {
        if ($this->dislikes->removeElement($dislike)) {
            if ($dislike->getPublication() === $this) {
                $dislike->setPublication(null);
            }
        }
        return $this;
    }

    public function isDislikedByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->dislikes->exists(function ($key, Dislike $dislike) use ($user) {
            return $dislike->getUser() === $user;
        });
    }
}