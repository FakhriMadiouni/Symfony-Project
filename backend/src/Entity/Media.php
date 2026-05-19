<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'media')]
#[ORM\Index(name: 'ind_media_ad', columns: ['ad_id'])]
#[ORM\UniqueConstraint(name: 'uniq_ad_position', columns: ['ad_id', 'position'])]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'media_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Advertisement::class)]
    #[ORM\JoinColumn(name: 'ad_id', referencedColumnName: 'ad_id', nullable: false, onDelete: 'CASCADE')]
    private Advertisement $advertisement;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'file_name', length: 255)]
    private string $fileName;

    #[ORM\Column(name: 'file_type', length: 10)]
    private string $fileType;

    #[ORM\Column]
    private int $position;

    public function getId(): ?int { return $this->id; }
    public function getAdvertisement(): Advertisement { return $this->advertisement; }
    public function setAdvertisement(Advertisement $a): static { $this->advertisement = $a; return $this; }
    public function getCreationDate(): ?\DateTimeInterface { return $this->creationDate; }
    public function setCreationDate(?\DateTimeInterface $d): static { $this->creationDate = $d; return $this; }
    public function getFileName(): string { return $this->fileName; }
    public function setFileName(string $f): static { $this->fileName = $f; return $this; }
    public function getFileType(): string { return $this->fileType; }
    public function setFileType(string $t): static { $this->fileType = $t; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $p): static { $this->position = $p; return $this; }
}
