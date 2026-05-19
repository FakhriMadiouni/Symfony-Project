<?php

namespace App\Entity;

use App\Repository\UserAdTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAdTokenRepository::class)]
#[ORM\Table(name: 'user_ad_tokens')]
#[ORM\Index(name: 'ind_tokens_user', columns: ['user_id'])]
class UserAdToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_ad_token')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 1])]
    private int $active = 1;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'price_per_unit', type: 'decimal', precision: 10, scale: 2)]
    private string $pricePerUnit;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '0.00'])]
    private string $discount = '0.00';

    #[ORM\Column(name: 'max_media', options: ['default' => 0])]
    private int $maxMedia = 0;

    #[ORM\Column(name: 'ad_duration')]
    private int $adDuration;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getCreationDate(): ?\DateTimeInterface { return $this->creationDate; }
    public function setCreationDate(?\DateTimeInterface $d): static { $this->creationDate = $d; return $this; }
    public function getActive(): int { return $this->active; }
    public function setActive(int $v): static { $this->active = $v; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getPricePerUnit(): string { return $this->pricePerUnit; }
    public function setPricePerUnit(string $v): static { $this->pricePerUnit = $v; return $this; }
    public function getDiscount(): string { return $this->discount; }
    public function setDiscount(string $v): static { $this->discount = $v; return $this; }
    public function getMaxMedia(): int { return $this->maxMedia; }
    public function setMaxMedia(int $v): static { $this->maxMedia = $v; return $this; }
    public function getAdDuration(): int { return $this->adDuration; }
    public function setAdDuration(int $v): static { $this->adDuration = $v; return $this; }

    public function getDurationMinutes(): int { return $this->adDuration * 1440; }
}
