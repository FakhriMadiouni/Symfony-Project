<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'reports')]
#[ORM\Index(name: 'ind_reports_reported_user', columns: ['reported_user_id'])]
#[ORM\Index(name: 'ind_reports_reporter', columns: ['reporter_id'])]
#[ORM\Index(name: 'ind_reports_type', columns: ['type'])]
#[ORM\Index(name: 'ind_reports_date', columns: ['date'])]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'report_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reporter_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $reporter;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reported_user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $reportedUser;

    #[ORM\ManyToOne(targetEntity: Advertisement::class)]
    #[ORM\JoinColumn(name: 'reported_ad_id', referencedColumnName: 'ad_id', nullable: true, onDelete: 'CASCADE')]
    private ?Advertisement $reportedAd = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(name: 'reported_conv_id', referencedColumnName: 'conversation_id', nullable: true, onDelete: 'CASCADE')]
    private ?Conversation $reportedConv = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(name: 'reported_msg_id', referencedColumnName: 'message_id', nullable: true, onDelete: 'CASCADE')]
    private ?Message $reportedMsg = null;

    #[ORM\ManyToOne(targetEntity: AdReview::class)]
    #[ORM\JoinColumn(name: 'reported_ad_review_id', referencedColumnName: 'ad_review_id', nullable: true, onDelete: 'CASCADE')]
    private ?AdReview $reportedAdReview = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

    public function getId(): ?int { return $this->id; }
    public function getReporter(): User { return $this->reporter; }
    public function setReporter(User $u): static { $this->reporter = $u; return $this; }
    public function getReportedUser(): User { return $this->reportedUser; }
    public function setReportedUser(User $u): static { $this->reportedUser = $u; return $this; }
    public function getReportedAd(): ?Advertisement { return $this->reportedAd; }
    public function setReportedAd(?Advertisement $a): static { $this->reportedAd = $a; return $this; }
    public function getReportedConv(): ?Conversation { return $this->reportedConv; }
    public function setReportedConv(?Conversation $c): static { $this->reportedConv = $c; return $this; }
    public function getReportedMsg(): ?Message { return $this->reportedMsg; }
    public function setReportedMsg(?Message $m): static { $this->reportedMsg = $m; return $this; }
    public function getReportedAdReview(): ?AdReview { return $this->reportedAdReview; }
    public function setReportedAdReview(?AdReview $r): static { $this->reportedAdReview = $r; return $this; }
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $d): static { $this->date = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $r): static { $this->reason = $r; return $this; }
}
