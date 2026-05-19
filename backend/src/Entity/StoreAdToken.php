<?php

namespace App\Entity;

use App\Repository\StoreAdTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StoreAdTokenRepository::class)]
#[ORM\Table(name: 'store_ad_tokens')]
class StoreAdToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_store_ad_token')]
    private ?int $id = null;

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

    #[ORM\Column(name: 'max_media', options: ['default' => 5])]
    private int $maxMedia = 5;

    #[ORM\Column(name: 'ad_duration')]
    private int $adDuration;

    #[ORM\Column(name: 'offer_start_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $offerStartDate = null;

    #[ORM\Column(name: 'offer_expiration_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $offerExpirationDate = null;

    public function getId(): ?int { return $this->id; }
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
    public function getOfferStartDate(): ?\DateTimeInterface { return $this->offerStartDate; }
    public function setOfferStartDate(?\DateTimeInterface $d): static { $this->offerStartDate = $d; return $this; }
    public function getOfferExpirationDate(): ?\DateTimeInterface { return $this->offerExpirationDate; }
    public function setOfferExpirationDate(?\DateTimeInterface $d): static { $this->offerExpirationDate = $d; return $this; }

    public function isCurrentlyActive(): bool
    {
        $now = new \DateTime();
        if ($this->active !== 1) return false;
        if ($this->offerStartDate && $this->offerStartDate > $now) return false;
        if ($this->offerExpirationDate && $this->offerExpirationDate <= $now) return false;
        return true;
    }
}
