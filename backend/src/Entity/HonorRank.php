<?php

namespace App\Entity;

use App\Repository\HonorRankRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HonorRankRepository::class)]
#[ORM\Table(name: 'honor_ranks')]
class HonorRank
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'honor_rank_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(type: 'smallint', unique: true)]
    private int $rank;

    #[ORM\Column(name: 'min_score')]
    private int $minScore;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(name: 'badge_file_name', length: 255, nullable: true)]
    private ?string $badgeFileName = null;

    #[ORM\Column(name: 'badge_lock_status', type: 'smallint', options: ['default' => 0])]
    private int $badgeLockStatus = 0;

    #[ORM\Column(name: 'frame_file_name', length: 255, nullable: true)]
    private ?string $frameFileName = null;

    #[ORM\Column(name: 'frame_lock_status', type: 'smallint', options: ['default' => 0])]
    private int $frameLockStatus = 0;

    #[ORM\Column(name: 'ad_warn_score', options: ['default' => 0])]
    private int $adWarnScore = 0;

    #[ORM\Column(name: 'ad_ban_score', options: ['default' => 0])]
    private int $adBanScore = 0;

    #[ORM\Column(name: 'user_warn_score', options: ['default' => 0])]
    private int $userWarnScore = 0;

    #[ORM\Column(name: 'user_ban_score', options: ['default' => 0])]
    private int $userBanScore = 0;

    #[ORM\Column(name: 'mute_warn_score', options: ['default' => 0])]
    private int $muteWarnScore = 0;

    #[ORM\Column(name: 'mute_score', options: ['default' => 0])]
    private int $muteScore = 0;

    #[ORM\Column(name: 'pos_rating_score', options: ['default' => 10])]
    private int $posRatingScore = 10;

    #[ORM\Column(name: 'neg_rating_score', options: ['default' => -20])]
    private int $negRatingScore = -20;

    public function getId(): ?int { return $this->id; }
    public function getCreationDate(): ?\DateTimeInterface { return $this->creationDate; }
    public function setCreationDate(?\DateTimeInterface $d): static { $this->creationDate = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getRank(): int { return $this->rank; }
    public function setRank(int $v): static { $this->rank = $v; return $this; }
    public function getMinScore(): int { return $this->minScore; }
    public function setMinScore(int $v): static { $this->minScore = $v; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $c): static { $this->color = $c; return $this; }
    public function getBadgeFileName(): ?string { return $this->badgeFileName; }
    public function setBadgeFileName(?string $v): static { $this->badgeFileName = $v; return $this; }
    public function getBadgeLockStatus(): int { return $this->badgeLockStatus; }
    public function setBadgeLockStatus(int $v): static { $this->badgeLockStatus = $v; return $this; }
    public function getFrameFileName(): ?string { return $this->frameFileName; }
    public function setFrameFileName(?string $v): static { $this->frameFileName = $v; return $this; }
    public function getFrameLockStatus(): int { return $this->frameLockStatus; }
    public function setFrameLockStatus(int $v): static { $this->frameLockStatus = $v; return $this; }
    public function getAdWarnScore(): int { return $this->adWarnScore; }
    public function setAdWarnScore(int $v): static { $this->adWarnScore = $v; return $this; }
    public function getAdBanScore(): int { return $this->adBanScore; }
    public function setAdBanScore(int $v): static { $this->adBanScore = $v; return $this; }
    public function getUserWarnScore(): int { return $this->userWarnScore; }
    public function setUserWarnScore(int $v): static { $this->userWarnScore = $v; return $this; }
    public function getUserBanScore(): int { return $this->userBanScore; }
    public function setUserBanScore(int $v): static { $this->userBanScore = $v; return $this; }
    public function getMuteWarnScore(): int { return $this->muteWarnScore; }
    public function setMuteWarnScore(int $v): static { $this->muteWarnScore = $v; return $this; }
    public function getMuteScore(): int { return $this->muteScore; }
    public function setMuteScore(int $v): static { $this->muteScore = $v; return $this; }
    public function getPosRatingScore(): int { return $this->posRatingScore; }
    public function setPosRatingScore(int $v): static { $this->posRatingScore = $v; return $this; }
    public function getNegRatingScore(): int { return $this->negRatingScore; }
    public function setNegRatingScore(int $v): static { $this->negRatingScore = $v; return $this; }
}
