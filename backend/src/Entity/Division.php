<?php

namespace App\Entity;

use App\Repository\DivisionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DivisionRepository::class)]
#[ORM\Table(name: 'divisions')]
class Division
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'div_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): ?int { return $this->id; }
    public function getCreationDate(): ?\DateTimeInterface { return $this->creationDate; }
    public function setCreationDate(?\DateTimeInterface $d): static { $this->creationDate = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
}
