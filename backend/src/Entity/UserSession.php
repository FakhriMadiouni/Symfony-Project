<?php

namespace App\Entity;

use App\Repository\UserSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSessionRepository::class)]
#[ORM\Table(name: 'user_sessions')]
#[ORM\Index(name: 'ind_sessions_user', columns: ['user_id'])]
class UserSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'session_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 255, unique: true)]
    private string $token;

    #[ORM\Column(name: 'expiry_date', type: 'datetime')]
    private \DateTimeInterface $expiryDate;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
    private ?string $userAgent = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getToken(): string { return $this->token; }
    public function setToken(string $t): static { $this->token = $t; return $this; }
    public function getExpiryDate(): \DateTimeInterface { return $this->expiryDate; }
    public function setExpiryDate(\DateTimeInterface $d): static { $this->expiryDate = $d; return $this; }
    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $ip): static { $this->ip = $ip; return $this; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $ua): static { $this->userAgent = $ua; return $this; }

    public function isExpired(): bool
    {
        return $this->expiryDate < new \DateTime();
    }
}
