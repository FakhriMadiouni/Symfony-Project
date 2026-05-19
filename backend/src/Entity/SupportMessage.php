<?php

namespace App\Entity;

use App\Repository\SupportMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportMessageRepository::class)]
#[ORM\Table(name: 'support_messages')]
#[ORM\Index(name: 'ind_support_conv', columns: ['support_conv_id', 'read_status'])]
class SupportMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'support_msg_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportConversation::class)]
    #[ORM\JoinColumn(name: 'support_conv_id', referencedColumnName: 'support_conv_id', nullable: false, onDelete: 'CASCADE')]
    private SupportConversation $supportConversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(name: 'is_staff', type: 'smallint', options: ['default' => 0])]
    private int $isStaff = 0;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(name: 'sent_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentDate = null;

    #[ORM\Column(name: 'read_status', type: 'smallint', options: ['default' => 0])]
    private int $readStatus = 0;

    public function getId(): ?int { return $this->id; }
    public function getSupportConversation(): SupportConversation { return $this->supportConversation; }
    public function setSupportConversation(SupportConversation $c): static { $this->supportConversation = $c; return $this; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $u): static { $this->sender = $u; return $this; }
    public function getIsStaff(): int { return $this->isStaff; }
    public function setIsStaff(int $v): static { $this->isStaff = $v; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }
    public function getSentDate(): ?\DateTimeInterface { return $this->sentDate; }
    public function setSentDate(?\DateTimeInterface $d): static { $this->sentDate = $d; return $this; }
    public function getReadStatus(): int { return $this->readStatus; }
    public function setReadStatus(int $v): static { $this->readStatus = $v; return $this; }
}
