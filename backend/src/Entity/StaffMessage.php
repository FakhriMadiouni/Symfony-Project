<?php

namespace App\Entity;

use App\Repository\StaffMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaffMessageRepository::class)]
#[ORM\Table(name: 'staff_messages')]
#[ORM\Index(name: 'ind_staff_conv', columns: ['staff_conv_id', 'read_status'])]
class StaffMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'staff_msg_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StaffConversation::class)]
    #[ORM\JoinColumn(name: 'staff_conv_id', referencedColumnName: 'staff_conv_id', nullable: false, onDelete: 'CASCADE')]
    private StaffConversation $staffConversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(name: 'sent_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentDate = null;

    #[ORM\Column(name: 'read_status', type: 'smallint', options: ['default' => 0])]
    private int $readStatus = 0;

    public function getId(): ?int { return $this->id; }
    public function getStaffConversation(): StaffConversation { return $this->staffConversation; }
    public function setStaffConversation(StaffConversation $c): static { $this->staffConversation = $c; return $this; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $u): static { $this->sender = $u; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }
    public function getSentDate(): ?\DateTimeInterface { return $this->sentDate; }
    public function setSentDate(?\DateTimeInterface $d): static { $this->sentDate = $d; return $this; }
    public function getReadStatus(): int { return $this->readStatus; }
    public function setReadStatus(int $v): static { $this->readStatus = $v; return $this; }
}
