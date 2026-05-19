<?php

namespace App\Entity;

use App\Repository\DivisionRankRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DivisionRankRepository::class)]
#[ORM\Table(name: 'division_ranks')]
#[ORM\Index(name: 'ind_div_ranks_div_id', columns: ['div_id'])]
#[ORM\UniqueConstraint(name: 'uniq_div_rank', columns: ['div_id', 'rank'])]
class DivisionRank
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'div_rank_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Division::class)]
    #[ORM\JoinColumn(name: 'div_id', referencedColumnName: 'div_id', nullable: false, onDelete: 'CASCADE')]
    private Division $division;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(name: 'lock_status', type: 'smallint', options: ['default' => 0])]
    private int $lockStatus = 0;

    #[ORM\Column(type: 'smallint')]
    private int $rank;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'badge_file_name', length: 255, nullable: true)]
    private ?string $badgeFileName = null;

    #[ORM\Column(name: 'badge_lock_status', type: 'smallint', options: ['default' => 0])]
    private int $badgeLockStatus = 0;

    #[ORM\Column(name: 'frame_file_name', length: 255, nullable: true)]
    private ?string $frameFileName = null;

    #[ORM\Column(name: 'frame_lock_status', type: 'smallint', options: ['default' => 0])]
    private int $frameLockStatus = 0;

    #[ORM\Column(name: 'mute_warn', type: 'smallint', options: ['default' => 0])]
    private int $muteWarn = 0;
    #[ORM\Column(name: 'mute', type: 'smallint', options: ['default' => 0])]
    private int $mute = 0;
    #[ORM\Column(name: 'mute_unwarn', type: 'smallint', options: ['default' => 0])]
    private int $muteUnwarn = 0;
    #[ORM\Column(name: 'unmute', type: 'smallint', options: ['default' => 0])]
    private int $unmute = 0;
    #[ORM\Column(name: 'ad_warn', type: 'smallint', options: ['default' => 0])]
    private int $adWarn = 0;
    #[ORM\Column(name: 'ad_ban', type: 'smallint', options: ['default' => 0])]
    private int $adBan = 0;
    #[ORM\Column(name: 'ad_unwarn', type: 'smallint', options: ['default' => 0])]
    private int $adUnwarn = 0;
    #[ORM\Column(name: 'ad_unban', type: 'smallint', options: ['default' => 0])]
    private int $adUnban = 0;
    #[ORM\Column(name: 'ban_ads', type: 'smallint', options: ['default' => 0])]
    private int $banAds = 0;
    #[ORM\Column(name: 'unban_ads', type: 'smallint', options: ['default' => 0])]
    private int $unbanAds = 0;
    #[ORM\Column(name: 'ban_reviews', type: 'smallint', options: ['default' => 0])]
    private int $banReviews = 0;
    #[ORM\Column(name: 'unban_reviews', type: 'smallint', options: ['default' => 0])]
    private int $unbanReviews = 0;
    #[ORM\Column(name: 'ban_warn', type: 'smallint', options: ['default' => 0])]
    private int $banWarn = 0;
    #[ORM\Column(name: 'ban', type: 'smallint', options: ['default' => 0])]
    private int $ban = 0;
    #[ORM\Column(name: 'ban_unwarn', type: 'smallint', options: ['default' => 0])]
    private int $banUnwarn = 0;
    #[ORM\Column(name: 'unban', type: 'smallint', options: ['default' => 0])]
    private int $unban = 0;
    #[ORM\Column(name: 'staff_warn', type: 'smallint', options: ['default' => 0])]
    private int $staffWarn = 0;
    #[ORM\Column(name: 'staff_ban', type: 'smallint', options: ['default' => 0])]
    private int $staffBan = 0;
    #[ORM\Column(name: 'staff_unwarn', type: 'smallint', options: ['default' => 0])]
    private int $staffUnwarn = 0;
    #[ORM\Column(name: 'staff_unban', type: 'smallint', options: ['default' => 0])]
    private int $staffUnban = 0;
    #[ORM\Column(name: 'check_reports', type: 'smallint', options: ['default' => 0])]
    private int $checkReports = 0;
    #[ORM\Column(name: 'close_reports', type: 'smallint', options: ['default' => 0])]
    private int $closeReports = 0;
    #[ORM\Column(name: 'reopen_reports', type: 'smallint', options: ['default' => 0])]
    private int $reopenReports = 0;
    #[ORM\Column(name: 'manage_users', type: 'smallint', options: ['default' => 0])]
    private int $manageUsers = 0;
    #[ORM\Column(name: 'manage_categories', type: 'smallint', options: ['default' => 0])]
    private int $manageCategories = 0;
    #[ORM\Column(name: 'manage_honor_ranks', type: 'smallint', options: ['default' => 0])]
    private int $manageHonorRanks = 0;
    #[ORM\Column(name: 'manage_token_offers', type: 'smallint', options: ['default' => 0])]
    private int $manageTokenOffers = 0;
    #[ORM\Column(name: 'manage_locations', type: 'smallint', options: ['default' => 0])]
    private int $manageLocations = 0;
    #[ORM\Column(name: 'manage_advertisements', type: 'smallint', options: ['default' => 0])]
    private int $manageAdvertisements = 0;
    #[ORM\Column(name: 'manage_divisions', type: 'smallint', options: ['default' => 0])]
    private int $manageDivisions = 0;
    #[ORM\Column(name: 'manage_founding_page', type: 'smallint', options: ['default' => 0])]
    private int $manageFoundingPage = 0;
    #[ORM\Column(name: 'manage_support', type: 'smallint', options: ['default' => 0])]
    private int $manageSupport = 0;
    #[ORM\Column(name: 'manage_founding_mailbox', type: 'smallint', options: ['default' => 0])]
    private int $manageFoundingMailbox = 0;
    #[ORM\Column(name: 'use_founding_mails', type: 'smallint', options: ['default' => 0])]
    private int $useFoundingMails = 0;
    #[ORM\Column(name: 'manage_dev_mailbox', type: 'smallint', options: ['default' => 0])]
    private int $manageDevMailbox = 0;
    #[ORM\Column(name: 'use_dev_mails', type: 'smallint', options: ['default' => 0])]
    private int $useDevMails = 0;

    public function getId(): ?int { return $this->id; }
    public function getDivision(): Division { return $this->division; }
    public function setDivision(Division $d): static { $this->division = $d; return $this; }
    public function getCreationDate(): ?\DateTimeInterface { return $this->creationDate; }
    public function setCreationDate(?\DateTimeInterface $d): static { $this->creationDate = $d; return $this; }
    public function getLockStatus(): int { return $this->lockStatus; }
    public function setLockStatus(int $v): static { $this->lockStatus = $v; return $this; }
    public function getRank(): int { return $this->rank; }
    public function setRank(int $v): static { $this->rank = $v; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getBadgeFileName(): ?string { return $this->badgeFileName; }
    public function setBadgeFileName(?string $v): static { $this->badgeFileName = $v; return $this; }
    public function getBadgeLockStatus(): int { return $this->badgeLockStatus; }
    public function setBadgeLockStatus(int $v): static { $this->badgeLockStatus = $v; return $this; }
    public function getFrameFileName(): ?string { return $this->frameFileName; }
    public function setFrameFileName(?string $v): static { $this->frameFileName = $v; return $this; }
    public function getFrameLockStatus(): int { return $this->frameLockStatus; }
    public function setFrameLockStatus(int $v): static { $this->frameLockStatus = $v; return $this; }
    public function getMuteWarn(): int { return $this->muteWarn; } public function setMuteWarn(int $v): static { $this->muteWarn = $v; return $this; }
    public function getMute(): int { return $this->mute; } public function setMute(int $v): static { $this->mute = $v; return $this; }
    public function getMuteUnwarn(): int { return $this->muteUnwarn; } public function setMuteUnwarn(int $v): static { $this->muteUnwarn = $v; return $this; }
    public function getUnmute(): int { return $this->unmute; } public function setUnmute(int $v): static { $this->unmute = $v; return $this; }
    public function getAdWarn(): int { return $this->adWarn; } public function setAdWarn(int $v): static { $this->adWarn = $v; return $this; }
    public function getAdBan(): int { return $this->adBan; } public function setAdBan(int $v): static { $this->adBan = $v; return $this; }
    public function getAdUnwarn(): int { return $this->adUnwarn; } public function setAdUnwarn(int $v): static { $this->adUnwarn = $v; return $this; }
    public function getAdUnban(): int { return $this->adUnban; } public function setAdUnban(int $v): static { $this->adUnban = $v; return $this; }
    public function getBanAds(): int { return $this->banAds; } public function setBanAds(int $v): static { $this->banAds = $v; return $this; }
    public function getUnbanAds(): int { return $this->unbanAds; } public function setUnbanAds(int $v): static { $this->unbanAds = $v; return $this; }
    public function getBanReviews(): int { return $this->banReviews; } public function setBanReviews(int $v): static { $this->banReviews = $v; return $this; }
    public function getUnbanReviews(): int { return $this->unbanReviews; } public function setUnbanReviews(int $v): static { $this->unbanReviews = $v; return $this; }
    public function getBanWarn(): int { return $this->banWarn; } public function setBanWarn(int $v): static { $this->banWarn = $v; return $this; }
    public function getBan(): int { return $this->ban; } public function setBan(int $v): static { $this->ban = $v; return $this; }
    public function getBanUnwarn(): int { return $this->banUnwarn; } public function setBanUnwarn(int $v): static { $this->banUnwarn = $v; return $this; }
    public function getUnban(): int { return $this->unban; } public function setUnban(int $v): static { $this->unban = $v; return $this; }
    public function getStaffWarn(): int { return $this->staffWarn; } public function setStaffWarn(int $v): static { $this->staffWarn = $v; return $this; }
    public function getStaffBan(): int { return $this->staffBan; } public function setStaffBan(int $v): static { $this->staffBan = $v; return $this; }
    public function getStaffUnwarn(): int { return $this->staffUnwarn; } public function setStaffUnwarn(int $v): static { $this->staffUnwarn = $v; return $this; }
    public function getStaffUnban(): int { return $this->staffUnban; } public function setStaffUnban(int $v): static { $this->staffUnban = $v; return $this; }
    public function getCheckReports(): int { return $this->checkReports; } public function setCheckReports(int $v): static { $this->checkReports = $v; return $this; }
    public function getCloseReports(): int { return $this->closeReports; } public function setCloseReports(int $v): static { $this->closeReports = $v; return $this; }
    public function getReopenReports(): int { return $this->reopenReports; } public function setReopenReports(int $v): static { $this->reopenReports = $v; return $this; }
    public function getManageUsers(): int { return $this->manageUsers; } public function setManageUsers(int $v): static { $this->manageUsers = $v; return $this; }
    public function getManageCategories(): int { return $this->manageCategories; } public function setManageCategories(int $v): static { $this->manageCategories = $v; return $this; }
    public function getManageHonorRanks(): int { return $this->manageHonorRanks; } public function setManageHonorRanks(int $v): static { $this->manageHonorRanks = $v; return $this; }
    public function getManageTokenOffers(): int { return $this->manageTokenOffers; } public function setManageTokenOffers(int $v): static { $this->manageTokenOffers = $v; return $this; }
    public function getManageLocations(): int { return $this->manageLocations; } public function setManageLocations(int $v): static { $this->manageLocations = $v; return $this; }
    public function getManageAdvertisements(): int { return $this->manageAdvertisements; } public function setManageAdvertisements(int $v): static { $this->manageAdvertisements = $v; return $this; }
    public function getManageDivisions(): int { return $this->manageDivisions; } public function setManageDivisions(int $v): static { $this->manageDivisions = $v; return $this; }
    public function getManageFoundingPage(): int { return $this->manageFoundingPage; } public function setManageFoundingPage(int $v): static { $this->manageFoundingPage = $v; return $this; }
    public function getManageSupport(): int { return $this->manageSupport; } public function setManageSupport(int $v): static { $this->manageSupport = $v; return $this; }
    public function getManageFoundingMailbox(): int { return $this->manageFoundingMailbox; } public function setManageFoundingMailbox(int $v): static { $this->manageFoundingMailbox = $v; return $this; }
    public function getUseFoundingMails(): int { return $this->useFoundingMails; } public function setUseFoundingMails(int $v): static { $this->useFoundingMails = $v; return $this; }
    public function getManageDevMailbox(): int { return $this->manageDevMailbox; } public function setManageDevMailbox(int $v): static { $this->manageDevMailbox = $v; return $this; }
    public function getUseDevMails(): int { return $this->useDevMails; } public function setUseDevMails(int $v): static { $this->useDevMails = $v; return $this; }

    public function toPermissionsArray(): array
    {
        return [
            'mute_warn' => $this->muteWarn, 'mute' => $this->mute, 'mute_unwarn' => $this->muteUnwarn, 'unmute' => $this->unmute,
            'ad_warn' => $this->adWarn, 'ad_ban' => $this->adBan, 'ad_unwarn' => $this->adUnwarn, 'ad_unban' => $this->adUnban,
            'ban_ads' => $this->banAds, 'unban_ads' => $this->unbanAds, 'ban_reviews' => $this->banReviews, 'unban_reviews' => $this->unbanReviews,
            'ban_warn' => $this->banWarn, 'ban' => $this->ban, 'ban_unwarn' => $this->banUnwarn, 'unban' => $this->unban,
            'staff_warn' => $this->staffWarn, 'staff_ban' => $this->staffBan, 'staff_unwarn' => $this->staffUnwarn, 'staff_unban' => $this->staffUnban,
            'check_reports' => $this->checkReports, 'close_reports' => $this->closeReports, 'reopen_reports' => $this->reopenReports,
            'manage_users' => $this->manageUsers, 'manage_categories' => $this->manageCategories,
            'manage_honor_ranks' => $this->manageHonorRanks, 'manage_token_offers' => $this->manageTokenOffers,
            'manage_locations' => $this->manageLocations, 'manage_advertisements' => $this->manageAdvertisements,
            'manage_divisions' => $this->manageDivisions, 'manage_founding_page' => $this->manageFoundingPage,
            'manage_support' => $this->manageSupport, 'manage_founding_mailbox' => $this->manageFoundingMailbox,
            'use_founding_mails' => $this->useFoundingMails, 'manage_dev_mailbox' => $this->manageDevMailbox,
            'use_dev_mails' => $this->useDevMails,
        ];
    }
}
