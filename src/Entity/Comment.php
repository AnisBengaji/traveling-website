<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_comment")]
    private ?int $id_comment = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Content cannot be empty")]
    private ?string $content = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "Please select an author")]
    private ?User $author = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)] // Changed to DATETIME_MUTABLE
    private ?\DateTimeInterface $date_comment = null;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'id_publication', referencedColumnName: 'id_publication', nullable: false)]
    #[Assert\NotNull(message: "Please select a publication")]
    private ?Publication $publication = null;

    public function getIdComment(): ?int
    {
        return $this->id_comment;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content; // Remove default empty string
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

    public function getDateComment(): ?\DateTimeInterface
    {
        return $this->date_comment;
    }

    public function setDateComment(?\DateTimeInterface $date_comment): static
    {
        $this->date_comment = $date_comment;
        return $this;
    }

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;
        return $this;
    }

    #[ORM\PrePersist]
    public function setDateCommentAutomatically(): void
    {
        if ($this->date_comment === null) {
            $this->date_comment = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
        }
    }
}