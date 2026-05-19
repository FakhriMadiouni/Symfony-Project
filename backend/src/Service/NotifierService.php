<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotifierService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationRepository $notificationRepository
    ) {}

    private function send(User $user, string $category, string $type, string $content, ?int $refId = null): void
    {
        $n = new Notification();
        $n->setUser($user);
        $n->setCategory($category);
        $n->setReferenceType($type);
        $n->setContent($content);
        $n->setReferenceId($refId);
        $n->setReadStatus(0);
        $n->setDate(new \DateTime());
        $this->em->persist($n);
        $this->em->flush();
    }

    // ── Advertisements ────────────────────────────────────────────────
    public function adExpired(User $user, int $adId, string $adTitle): void
    {
        $this->send($user, 'advertisements', 'ad_expired',
            "Your ad \"$adTitle\" has expired and is no longer visible in search.", $adId);
    }

    // ── Conversations ─────────────────────────────────────────────────
    public function convStarted(User $user, int $convId, string $senderName, string $adTitle): void
    {
        $this->send($user, 'conversations', 'conv_started',
            "$senderName started a conversation about your ad \"$adTitle\".", $convId);
    }

    public function convEnded(User $user, int $convId, string $adTitle): void
    {
        $existing = $this->notificationRepository->findLastForUserAndRef($user->getId(), 'conversations', $convId);
        if ($existing) {
            $existing->setReferenceType('conv_ended');
            $existing->setContent("The conversation about \"$adTitle\" has ended — the ad has expired. You can still see it.");
            $existing->setReadStatus(0);
            $existing->setDate(new \DateTime());
            $this->em->flush();
        } else {
            $this->send($user, 'conversations', 'conv_ended',
                "The conversation about \"$adTitle\" has ended — the ad has expired. You can still see it.", $convId);
        }
    }

    // ── Tokens ────────────────────────────────────────────────────────
    public function tokensPurchasedBulk(User $user, array $lines): void
    {
        $body = "You purchased the following tokens:\n" . implode("\n", $lines);
        $this->send($user, 'tokens', 'token_purchased', $body);
    }

    public function tokenUsed(User $user, int $adId, string $adTitle, string $offerName, int $visibilityDays): void
    {
        $this->send($user, 'tokens', 'token_used',
            "A $offerName token was used to publish your ad \"$adTitle\" ($visibilityDays days visibility).", $adId);
    }

    // ── Social ────────────────────────────────────────────────────────
    public function userFollowed(User $user, string $followerName, int $followerId): void
    {
        $this->send($user, 'social', 'user_followed',
            "$followerName started following you.", $followerId);
    }

    public function adReview(User $user, int $adId, string $adTitle, string $score, string $raterName): void
    {
        $emoji = $score === 'positive' ? '👍' : '👎';
        $this->send($user, 'social', 'ad_review',
            "$emoji $raterName left a $score review on your ad \"$adTitle\".", $adId);
    }

    // ── Honor ─────────────────────────────────────────────────────────
    public function honorRankUp(User $user, string $rankName): void
    {
        $this->send($user, 'honor', 'honor_rank_up',
            "🎉 Your Honor rank increased to $rankName!", $user->getId());
    }

    public function honorRankDown(User $user, string $rankName): void
    {
        $this->send($user, 'honor', 'honor_rank_down',
            "Your Honor rank dropped to $rankName.", $user->getId());
    }

    // ── System — Punishments ──────────────────────────────────────────
    public function userWarned(User $user, string $reason = ''): void
    {
        $msg = "⚠️ You received a warning from staff.";
        if ($reason) $msg .= " Reason: $reason";
        $this->send($user, 'system', 'user_warned', $msg);
    }

    public function userUnwarned(User $user): void
    {
        $this->send($user, 'system', 'user_unwarned', "A warning on your account has been lifted.");
    }

    public function userBanned(User $user, string $reason = '', int $minutes = 0): void
    {
        $dur = $minutes > 0 ? " Duration: {$minutes} min." : '';
        $msg = "🔴 Your account has been banned.{$dur}";
        if ($reason) $msg .= " Reason: $reason";
        $msg .= " Contact support to appeal.";
        $this->send($user, 'system', 'user_banned', $msg);
    }

    public function userUnbanned(User $user): void
    {
        $this->send($user, 'system', 'user_unbanned', "✅ Your account ban has been lifted. Welcome back.");
    }

    public function userMuteWarned(User $user, string $reason = ''): void
    {
        $msg = "⚠️ You received a mute warning from staff.";
        if ($reason) $msg .= " Reason: $reason";
        $this->send($user, 'system', 'user_mute_warned', $msg);
    }

    public function userMuteUnwarned(User $user): void
    {
        $this->send($user, 'system', 'user_mute_unwarned', "A mute warning on your account has been lifted.");
    }

    public function userMuted(User $user, string $reason = '', int $minutes = 0): void
    {
        $dur = $minutes > 0 ? " Duration: {$minutes} min." : '';
        $msg = "🔇 You have been muted. You cannot send messages or leave reviews.{$dur}";
        if ($reason) $msg .= " Reason: $reason";
        $this->send($user, 'system', 'user_muted', $msg);
    }

    public function userUnmuted(User $user): void
    {
        $this->send($user, 'system', 'user_unmuted', "✅ Your mute has been lifted.");
    }

    public function userAdWarned(User $user, string $reason = ''): void
    {
        $msg = "⚠️ You received an ad warning from staff.";
        if ($reason) $msg .= " Reason: $reason";
        $this->send($user, 'system', 'user_ad_warned', $msg);
    }

    public function userAdUnwarned(User $user): void
    {
        $this->send($user, 'system', 'user_ad_unwarned', "An ad warning on your account has been lifted.");
    }

    public function userAdBanned(User $user, string $reason = '', int $minutes = 0): void
    {
        $dur = $minutes > 0 ? " Duration: {$minutes} min." : '';
        $msg = "🔴 You have been banned from posting advertisements.{$dur}";
        if ($reason) $msg .= " Reason: $reason";
        $msg .= " Contact support to appeal.";
        $this->send($user, 'system', 'user_ad_banned', $msg);
    }

    public function userAdUnbanned(User $user): void
    {
        $this->send($user, 'system', 'user_ad_unbanned', "✅ Your advertisement ban has been lifted.");
    }

    public function adBanned(User $user, int $adId, string $adTitle): void
    {
        $this->send($user, 'system', 'ad_banned',
            "🔴 Your ad \"$adTitle\" was banned by staff. If you believe this is a mistake, contact support.", $adId);
    }

    public function adUnbanned(User $user, int $adId, string $adTitle): void
    {
        $this->send($user, 'system', 'ad_unbanned', "✅ Your ad \"$adTitle\" ban has been lifted.", $adId);
    }

    public function adLocked(User $user, int $adId, string $adTitle): void
    {
        $this->send($user, 'system', 'ad_locked',
            "🔒 Your ad \"$adTitle\" has been locked by staff. Contact support if you believe this is a mistake.", $adId);
    }

    public function reportResolved(User $user, int $reportId, string $details = ''): void
    {
        $this->send($user, 'system', 'report_resolved',
            "✅ A report against you was reviewed and resolved." . ($details ? " Details: $details" : "") .
            " If you believe staff made a mistake, contact support.", $reportId);
    }

    public function reportRejected(User $user, int $reportId, string $details = ''): void
    {
        $this->send($user, 'system', 'report_rejected',
            "A report against you was reviewed and no action was taken." . ($details ? " Details: $details" : "") .
            " If you believe staff made a mistake, contact support.", $reportId);
    }

    public function supportOpened(User $user, int $convId, string $subject): void
    {
        $this->send($user, 'system', 'user_warned',
            "🎧 Your support request \"$subject\" has been submitted. We'll get back to you shortly.", $convId);
    }

    public function supportUpdated(User $user, int $convId, string $subject): void
    {
        $existing = $this->notificationRepository->findLastForUserAndRef($user->getId(), 'system', $convId);
        if ($existing) {
            $existing->setContent("🎧 Support replied to your request \"$subject\". Click to view.");
            $existing->setReadStatus(0);
            $existing->setDate(new \DateTime());
            $this->em->flush();
        } else {
            $this->send($user, 'system', 'user_warned', "🎧 Support replied to your request \"$subject\".", $convId);
        }
    }

    public function supportClosed(User $user, int $convId, string $subject): void
    {
        $existing = $this->notificationRepository->findLastForUserAndRef($user->getId(), 'system', $convId);
        if ($existing) {
            $existing->setContent("🎧 Your support request \"$subject\" has been closed. Click to view.");
            $existing->setReadStatus(0);
            $existing->setDate(new \DateTime());
            $this->em->flush();
        } else {
            $this->send($user, 'system', 'user_warned', "🎧 Your support request \"$subject\" has been closed.", $convId);
        }
    }

    public function staffAssigned(User $user, string $divName, string $rankName): void
    {
        $this->send($user, 'system', 'staff_assigned',
            "🛡️ You have been assigned to the $divName division as $rankName.");
    }

    public function staffRemoved(User $user): void
    {
        $this->send($user, 'system', 'staff_removed', "🛡️ You have been removed from the staff team.");
    }

    public function userStaffWarned(User $user, string $reason): void
    {
        $this->send($user, 'system', 'staff_warned',
            "⚠️ You received a staff warning: $reason. Contact support if you believe this is a mistake.");
    }

    public function userStaffUnwarned(User $user): void
    {
        $this->send($user, 'system', 'staff_unwarned', "A staff warning on your account has been lifted.");
    }

    public function userStaffBanned(User $user, string $reason, int $minutes = 0): void
    {
        $dur = $minutes > 0 ? " Duration: {$minutes} min." : '';
        $this->send($user, 'system', 'staff_banned',
            "🔴 Your staff access has been suspended.{$dur} Reason: $reason. Contact support to appeal.");
    }

    public function userStaffUnbanned(User $user): void
    {
        $this->send($user, 'system', 'staff_unbanned', "✅ Your staff suspension has been lifted.");
    }

    public function countUnread(int $userId): int
    {
        return $this->notificationRepository->countUnread($userId);
    }

    public function getGrouped(User $user): array
    {
        $this->notificationRepository->markAllRead($user->getId());
        $rows = $this->notificationRepository->findForUser($user->getId(), 100);

        $groups = [
            'advertisements' => [],
            'conversations'  => [],
            'tokens'         => [],
            'social'         => [],
            'honor'          => [],
            'system'         => [],
        ];

        foreach ($rows as $n) {
            $cat = $n->getCategory();
            if (isset($groups[$cat])) {
                $groups[$cat][] = $this->serializeNotification($n);
            }
        }

        return array_filter($groups, fn($g) => !empty($g));
    }

    private function serializeNotification(Notification $n): array
    {
        return [
            'notification_id' => $n->getId(),
            'category'        => $n->getCategory(),
            'reference_type'  => $n->getReferenceType(),
            'reference_id'    => $n->getReferenceId(),
            'content'         => $n->getContent(),
            'read_status'     => $n->getReadStatus(),
            'date'            => $n->getDate()?->format('Y-m-d H:i:s'),
        ];
    }
}
