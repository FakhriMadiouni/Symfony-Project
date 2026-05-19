<?php

namespace App\Entity;

use App\Repository\StaffConversationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaffConversationRepository::class)]
#[ORM\Table(name: 'staff_conversations')]
class StaffConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'staff_conv_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'lock_status', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(length: 30)]
    private string $type;

    #[ORM\Column(length: 200)]
    private string $subject;

    #[ORM\Column(name: 'opened_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $openedDate = null;

    #[ORM\Column(name: 'last_reply_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastReplyDate = null;

    #[ORM\Column(name: 'closed_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $closedDate = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $s): static { $this->subject = $s; return $this; }
    public function getOpenedDate(): ?\DateTimeInterface { return $this->openedDate; }
    public function setOpenedDate(?\DateTimeInterface $d): static { $this->openedDate = $d; return $this; }
    public function getLastReplyDate(): ?\DateTimeInterface { return $this->lastReplyDate; }
    public function setLastReplyDate(?\DateTimeInterface $d): static { $this->lastReplyDate = $d; return $this; }
    public function getClosedDate(): ?\DateTimeInterface { return $this->closedDate; }
    public function setClosedDate(?\DateTimeInterface $d): static { $this->closedDate = $d; return $this; }
}
