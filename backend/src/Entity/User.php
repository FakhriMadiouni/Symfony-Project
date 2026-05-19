<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'ind_users_username', columns: ['username'])]
#[ORM\Index(name: 'ind_users_staff_rank', columns: ['staff_division_rank_id'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'user_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DivisionRank::class)]
    #[ORM\JoinColumn(name: 'staff_division_rank_id', referencedColumnName: 'div_rank_id', nullable: true, onDelete: 'SET NULL')]
    private ?DivisionRank $staffDivisionRank = null;

    #[ORM\Column(name: 'reg_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $regDate = null;

    #[ORM\Column(name: 'reg_ip', length: 45, nullable: true)]
    private ?string $regIp = null;

    #[ORM\Column(name: 'last_login_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLoginDate = null;

    #[ORM\Column(name: 'last_login_ip', length: 45, nullable: true)]
    private ?string $lastLoginIp = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(length: 50)]
    private string $username = '';

    #[ORM\Column(length: 100, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 255)]
    private string $password = '';

    #[ORM\Column(name: 'profile_picture', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $biography = null;

    #[ORM\Column(name: 'honor_points', options: ['default' => 0])]
    private int $honorPoints = 0;

    #[ORM\Column(name: 'ban_warnings', type: 'smallint', options: ['default' => 0])]
    private int $banWarnings = 0;

    #[ORM\Column(name: 'ban_status', type: 'smallint', options: ['default' => 0])]
    private int $banStatus = 0;

    #[ORM\Column(name: 'ban_time_left', options: ['default' => 0])]
    private int $banTimeLeft = 0;

    #[ORM\Column(name: 'ad_ban_warnings', type: 'smallint', options: ['default' => 0])]
    private int $adBanWarnings = 0;

    #[ORM\Column(name: 'ad_ban_status', type: 'smallint', options: ['default' => 0])]
    private int $adBanStatus = 0;

    #[ORM\Column(name: 'ad_ban_time_left', options: ['default' => 0])]
    private int $adBanTimeLeft = 0;

    #[ORM\Column(name: 'mute_warnings', type: 'smallint', options: ['default' => 0])]
    private int $muteWarnings = 0;

    #[ORM\Column(name: 'mute_status', type: 'smallint', options: ['default' => 0])]
    private int $muteStatus = 0;

    #[ORM\Column(name: 'mute_time_left', options: ['default' => 0])]
    private int $muteTimeLeft = 0;

    #[ORM\Column(name: 'staff_warnings', type: 'smallint', options: ['default' => 0])]
    private int $staffWarnings = 0;

    #[ORM\Column(name: 'staff_ban', type: 'smallint', options: ['default' => 0])]
    private int $staffBan = 0;

    #[ORM\Column(name: 'staff_ban_time_left', options: ['default' => 0])]
    private int $staffBanTimeLeft = 0;

    public function getId(): ?int { return $this->id; }
    public function getStaffDivisionRank(): ?DivisionRank { return $this->staffDivisionRank; }
    public function setStaffDivisionRank(?DivisionRank $rank): static { $this->staffDivisionRank = $rank; return $this; }
    public function getRegDate(): ?\DateTimeInterface { return $this->regDate; }
    public function setRegDate(?\DateTimeInterface $d): static { $this->regDate = $d; return $this; }
    public function getRegIp(): ?string { return $this->regIp; }
    public function setRegIp(?string $ip): static { $this->regIp = $ip; return $this; }
    public function getLastLoginDate(): ?\DateTimeInterface { return $this->lastLoginDate; }
    public function setLastLoginDate(?\DateTimeInterface $d): static { $this->lastLoginDate = $d; return $this; }
    public function getLastLoginIp(): ?string { return $this->lastLoginIp; }
    public function setLastLoginIp(?string $ip): static { $this->lastLoginIp = $ip; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $v): static { $this->username = $v; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $v): static { $this->email = $v; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $v): static { $this->password = $v; return $this; }
    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $v): static { $this->profilePicture = $v; return $this; }
    public function getBiography(): ?string { return $this->biography; }
    public function setBiography(?string $v): static { $this->biography = $v; return $this; }
    public function getHonorPoints(): int { return $this->honorPoints; }
    public function setHonorPoints(int $v): static { $this->honorPoints = $v; return $this; }
    public function getBanWarnings(): int { return $this->banWarnings; }
    public function setBanWarnings(int $v): static { $this->banWarnings = $v; return $this; }
    public function getBanStatus(): int { return $this->banStatus; }
    public function setBanStatus(int $v): static { $this->banStatus = $v; return $this; }
    public function getBanTimeLeft(): int { return $this->banTimeLeft; }
    public function setBanTimeLeft(int $v): static { $this->banTimeLeft = $v; return $this; }
    public function getAdBanWarnings(): int { return $this->adBanWarnings; }
    public function setAdBanWarnings(int $v): static { $this->adBanWarnings = $v; return $this; }
    public function getAdBanStatus(): int { return $this->adBanStatus; }
    public function setAdBanStatus(int $v): static { $this->adBanStatus = $v; return $this; }
    public function getAdBanTimeLeft(): int { return $this->adBanTimeLeft; }
    public function setAdBanTimeLeft(int $v): static { $this->adBanTimeLeft = $v; return $this; }
    public function getMuteWarnings(): int { return $this->muteWarnings; }
    public function setMuteWarnings(int $v): static { $this->muteWarnings = $v; return $this; }
    public function getMuteStatus(): int { return $this->muteStatus; }
    public function setMuteStatus(int $v): static { $this->muteStatus = $v; return $this; }
    public function getMuteTimeLeft(): int { return $this->muteTimeLeft; }
    public function setMuteTimeLeft(int $v): static { $this->muteTimeLeft = $v; return $this; }
    public function getStaffWarnings(): int { return $this->staffWarnings; }
    public function setStaffWarnings(int $v): static { $this->staffWarnings = $v; return $this; }
    public function getStaffBan(): int { return $this->staffBan; }
    public function setStaffBan(int $v): static { $this->staffBan = $v; return $this; }
    public function getStaffBanTimeLeft(): int { return $this->staffBanTimeLeft; }
    public function setStaffBanTimeLeft(int $v): static { $this->staffBanTimeLeft = $v; return $this; }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->staffDivisionRank !== null && $this->staffBan === 0) {
            $roles[] = 'ROLE_STAFF';
        }
        return array_unique($roles);
    }

    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}

    public function isStaff(): bool
    {
        return $this->staffDivisionRank !== null && $this->staffBan === 0;
    }
}
