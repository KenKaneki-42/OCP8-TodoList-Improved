<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank(message: 'Vous devez saisir un titre.')]
    #[Assert\Length(
      min: 2,
      max: 255,
      minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
      maxMessage: 'Le titre ne peut excéder {{ limit }} caractères.'
  )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Vous devez saisir du contenu.')]
    private ?string $content = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private ?bool $isDone = null;

    // spécifier que l'entité associée doit être chargée immédiatement avec l'entité principale
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks',  fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true, name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isDone = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    public function setDone(bool $isDone): static
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function toggle(bool $flag): void
    {
        $this->isDone = $flag;
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
}
