<?php

namespace App\Entity;

use App\Repository\AdReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdReviewRepository::class)]
#[ORM\Table(name: 'ad_reviews')]
#[ORM\Index(name: 'ind_reviews_ad', columns: ['ad_id'])]
#[ORM\Index(name: 'ind_reviews_rated', columns: ['rated_user_id'])]
class AdReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ad_review_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Advertisement::class)]
    #[ORM\JoinColumn(name: 'ad_id', referencedColumnName: 'ad_id', nullable: false, onDelete: 'CASCADE')]
    private Advertisement $advertisement;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'conversation_id', nullable: false, onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'rater_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $rater;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'rated_user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $ratedUser;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(length: 20)]
    private string $rate;

    #[ORM\Column]
    private int $score;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'anonymous_status', type: 'smallint', options: ['default' => 0])]
    private int $anonymousStatus = 0;

    public function getId(): ?int { return $this->id; }
    public function getAdvertisement(): Advertisement { return $this->advertisement; }
    public function setAdvertisement(Advertisement $a): static { $this->advertisement = $a; return $this; }
    public function getConversation(): Conversation { return $this->conversation; }
    public function setConversation(Conversation $c): static { $this->conversation = $c; return $this; }
    public function getRater(): User { return $this->rater; }
    public function setRater(User $u): static { $this->rater = $u; return $this; }
    public function getRatedUser(): User { return $this->ratedUser; }
    public function setRatedUser(User $u): static { $this->ratedUser = $u; return $this; }
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $d): static { $this->date = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getRate(): string { return $this->rate; }
    public function setRate(string $r): static { $this->rate = $r; return $this; }
    public function getScore(): int { return $this->score; }
    public function setScore(int $s): static { $this->score = $s; return $this; }
    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $c): static { $this->comment = $c; return $this; }
    public function getAnonymousStatus(): int { return $this->anonymousStatus; }
    public function setAnonymousStatus(int $v): static { $this->anonymousStatus = $v; return $this; }
}
