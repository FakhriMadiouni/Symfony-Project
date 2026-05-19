<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversations')]
#[ORM\Index(name: 'ind_conv_ad', columns: ['ad_id'])]
#[ORM\Index(name: 'ind_conv_sender', columns: ['sender_id'])]
#[ORM\Index(name: 'ind_conv_advertiser', columns: ['advertiser_id'])]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'conversation_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Advertisement::class)]
    #[ORM\JoinColumn(name: 'ad_id', referencedColumnName: 'ad_id', nullable: false, onDelete: 'CASCADE')]
    private Advertisement $advertisement;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'advertiser_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $advertiser;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(name: 'last_message_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastMessageDate = null;

    public function getId(): ?int { return $this->id; }
    public function getAdvertisement(): Advertisement { return $this->advertisement; }
    public function setAdvertisement(Advertisement $a): static { $this->advertisement = $a; return $this; }
    public function getAdvertiser(): User { return $this->advertiser; }
    public function setAdvertiser(User $u): static { $this->advertiser = $u; return $this; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $u): static { $this->sender = $u; return $this; }
    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function setStartDate(?\DateTimeInterface $d): static { $this->startDate = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getLastMessageDate(): ?\DateTimeInterface { return $this->lastMessageDate; }
    public function setLastMessageDate(?\DateTimeInterface $d): static { $this->lastMessageDate = $d; return $this; }
}
