<?php

namespace App\Entity;

use App\Repository\AdvertisementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementRepository::class)]
#[ORM\Table(name: 'advertisements')]
#[ORM\Index(name: 'ind_ads_user', columns: ['user_id'])]
#[ORM\Index(name: 'ind_ads_subcat', columns: ['subcategory_id'])]
#[ORM\Index(name: 'ind_ads_active', columns: ['active'])]
#[ORM\Index(name: 'ind_ads_created', columns: ['creation_date'])]
#[ORM\Index(name: 'ind_ads_visibility', columns: ['active', 'ban_status', 'creation_date'])]
#[ORM\Index(name: 'ind_ads_countries', columns: ['country_id'])]
class Advertisement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ad_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Subcategory::class)]
    #[ORM\JoinColumn(name: 'subcategory_id', referencedColumnName: 'subcategory_id', nullable: false, onDelete: 'RESTRICT')]
    private Subcategory $subcategory;

    #[ORM\ManyToOne(targetEntity: UserAdToken::class)]
    #[ORM\JoinColumn(name: 'id_ad_token', referencedColumnName: 'id_ad_token', nullable: false, onDelete: 'CASCADE')]
    private UserAdToken $adToken;

    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'country_id', nullable: false, onDelete: 'RESTRICT')]
    private Country $country;

    #[ORM\Column(name: 'region_name', length: 100, nullable: true)]
    private ?string $regionName = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(length: 200)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $price = '0.00';

    #[ORM\Column(name: 'time_left', options: ['default' => 0])]
    private int $timeLeft = 0;

    #[ORM\Column(type: 'smallint', options: ['default' => 1])]
    private int $active = 1;

    #[ORM\Column(name: 'hidden_by_advertiser', type: 'smallint', options: ['default' => 0])]
    private int $hiddenByAdvertiser = 0;

    #[ORM\Column(name: 'ban_status', type: 'smallint', options: ['default' => 0])]
    private int $banStatus = 0;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getSubcategory(): Subcategory { return $this->subcategory; }
    public function setSubcategory(Subcategory $s): static { $this->subcategory = $s; return $this; }
    public function getAdToken(): UserAdToken { return $this->adToken; }
    public function setAdToken(UserAdToken $t): static { $this->adToken = $t; return $this; }
    public function getCountry(): Country { return $this->country; }
    public function setCountry(Country $c): static { $this->country = $c; return $this; }
    public function getRegionName(): ?string { return $this->regionName; }
    public function setRegionName(?string $v): static { $this->regionName = $v; return $this; }
    public function getCreationDate(): ?\DateTimeInterface { return $this->creationDate; }
    public function setCreationDate(?\DateTimeInterface $d): static { $this->creationDate = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getPrice(): string { return $this->price; }
    public function setPrice(string $p): static { $this->price = $p; return $this; }
    public function getTimeLeft(): int { return $this->timeLeft; }
    public function setTimeLeft(int $v): static { $this->timeLeft = $v; return $this; }
    public function getActive(): int { return $this->active; }
    public function setActive(int $v): static { $this->active = $v; return $this; }
    public function getHiddenByAdvertiser(): int { return $this->hiddenByAdvertiser; }
    public function setHiddenByAdvertiser(int $v): static { $this->hiddenByAdvertiser = $v; return $this; }
    public function getBanStatus(): int { return $this->banStatus; }
    public function setBanStatus(int $v): static { $this->banStatus = $v; return $this; }
}
