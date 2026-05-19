<?php

namespace App\Entity;

use App\Repository\SupportConversationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportConversationRepository::class)]
#[ORM\Table(name: 'support_conversations')]
class SupportConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'support_conv_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20, options: ['default' => 'open'])]
    private string $status = 'open';

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
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): static { $this->status = $s; return $this; }
    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $s): static { $this->subject = $s; return $this; }
    public function getOpenedDate(): ?\DateTimeInterface { return $this->openedDate; }
    public function setOpenedDate(?\DateTimeInterface $d): static { $this->openedDate = $d; return $this; }
    public function getLastReplyDate(): ?\DateTimeInterface { return $this->lastReplyDate; }
    public function setLastReplyDate(?\DateTimeInterface $d): static { $this->lastReplyDate = $d; return $this; }
    public function getClosedDate(): ?\DateTimeInterface { return $this->closedDate; }
    public function setClosedDate(?\DateTimeInterface $d): static { $this->closedDate = $d; return $this; }
}
