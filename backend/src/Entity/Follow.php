<?php

namespace App\Entity;

use App\Repository\FollowRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowRepository::class)]
#[ORM\Table(name: 'follows')]
class Follow
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'follower_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $follower;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'followed_user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $followedUser;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    public function getFollower(): User { return $this->follower; }
    public function setFollower(User $u): static { $this->follower = $u; return $this; }
    public function getFollowedUser(): User { return $this->followedUser; }
    public function setFollowedUser(User $u): static { $this->followedUser = $u; return $this; }
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $d): static { $this->date = $d; return $this; }
}
