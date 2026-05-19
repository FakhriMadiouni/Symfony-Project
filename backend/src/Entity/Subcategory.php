<?php

namespace App\Entity;

use App\Repository\SubcategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubcategoryRepository::class)]
#[ORM\Table(name: 'subcategories')]
#[ORM\Index(name: 'ind_subcats_category', columns: ['category_id'])]
#[ORM\UniqueConstraint(name: 'uniq_cat_name', columns: ['category_id', 'name'])]
class Subcategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'subcategory_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'category_id', nullable: false, onDelete: 'RESTRICT')]
    private Category $category;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'logo_file_name', length: 255, nullable: true)]
    private ?string $logoFileName = null;

    #[ORM\Column(name: 'logo_lock_status', type: 'smallint', options: ['default' => 0])]
    private int $logoLockStatus = 0;

    public function getId(): ?int { return $this->id; }
    public function getCategory(): Category { return $this->category; }
    public function setCategory(Category $c): static { $this->category = $c; return $this; }
    public function getCreationDate(): ?\DateTimeInterface { return $this->creationDate; }
    public function setCreationDate(?\DateTimeInterface $d): static { $this->creationDate = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getLogoFileName(): ?string { return $this->logoFileName; }
    public function setLogoFileName(?string $v): static { $this->logoFileName = $v; return $this; }
    public function getLogoLockStatus(): int { return $this->logoLockStatus; }
    public function setLogoLockStatus(int $v): static { $this->logoLockStatus = $v; return $this; }
}
