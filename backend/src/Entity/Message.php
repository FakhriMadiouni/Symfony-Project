<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
#[ORM\Index(name: 'ind_msg_conv_time', columns: ['conversation_id', 'timestamp'])]
#[ORM\Index(name: 'ind_msg_sender', columns: ['sender_id'])]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'message_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'conversation_id', nullable: false, onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 20, options: ['default' => 'text'])]
    private string $type = 'text';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(name: 'read_status', type: 'smallint', options: ['default' => 0])]
    private int $readStatus = 0;

    public function getId(): ?int { return $this->id; }
    public function getConversation(): Conversation { return $this->conversation; }
    public function setConversation(Conversation $c): static { $this->conversation = $c; return $this; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $u): static { $this->sender = $u; return $this; }
    public function getTimestamp(): ?\DateTimeInterface { return $this->timestamp; }
    public function setTimestamp(?\DateTimeInterface $t): static { $this->timestamp = $t; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $c): static { $this->content = $c; return $this; }
    public function getReadStatus(): int { return $this->readStatus; }
    public function setReadStatus(int $v): static { $this->readStatus = $v; return $this; }
}
