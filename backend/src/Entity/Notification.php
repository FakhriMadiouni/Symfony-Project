<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(name: 'ind_notif_user', columns: ['user_id'])]
#[ORM\Index(name: 'ind_notif_read', columns: ['read_status'])]
#[ORM\Index(name: 'ind_notif_user_read', columns: ['user_id', 'read_status'])]
#[ORM\Index(name: 'ind_notif_user_date', columns: ['user_id', 'date'])]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'notification_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 50)]
    private string $category;

    #[ORM\Column(name: 'reference_type', length: 50)]
    private string $referenceType;

    #[ORM\Column(name: 'reference_id', nullable: true)]
    private ?int $referenceId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(name: 'read_status', type: 'smallint', options: ['default' => 0])]
    private int $readStatus = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $c): static { $this->category = $c; return $this; }
    public function getReferenceType(): string { return $this->referenceType; }
    public function setReferenceType(string $t): static { $this->referenceType = $t; return $this; }
    public function getReferenceId(): ?int { return $this->referenceId; }
    public function setReferenceId(?int $v): static { $this->referenceId = $v; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $c): static { $this->content = $c; return $this; }
    public function getReadStatus(): int { return $this->readStatus; }
    public function setReadStatus(int $v): static { $this->readStatus = $v; return $this; }
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $d): static { $this->date = $d; return $this; }
}
