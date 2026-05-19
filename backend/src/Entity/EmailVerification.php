<?php

namespace App\Entity;

use App\Repository\EmailVerificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailVerificationRepository::class)]
#[ORM\Table(name: 'email_verifications')]
#[ORM\Index(name: 'ind_email_user', columns: ['user_id'])]
#[ORM\Index(name: 'ind_email_code', columns: ['code'])]
class EmailVerification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_email_verification')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 50)]
    private string $type = 'registration';

    #[ORM\Column(length: 10)]
    private string $code;

    #[ORM\Column(name: 'expiry_date', type: 'datetime')]
    private \DateTimeInterface $expiryDate;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $verified = 0;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $c): static { $this->code = $c; return $this; }
    public function getExpiryDate(): \DateTimeInterface { return $this->expiryDate; }
    public function setExpiryDate(\DateTimeInterface $d): static { $this->expiryDate = $d; return $this; }
    public function getVerified(): int { return $this->verified; }
    public function setVerified(int $v): static { $this->verified = $v; return $this; }

    public function isExpired(): bool { return $this->expiryDate < new \DateTime(); }
}
