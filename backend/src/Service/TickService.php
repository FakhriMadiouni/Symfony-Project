<?php

namespace App\Service;

use App\Repository\AdvertisementRepository;
use App\Repository\UserRepository;
use App\Repository\UserSessionRepository;
use App\Repository\EmailVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * TickService replaces the old cron_tick.php.
 *
 * In the original PHP project a cron ran every minute to:
 *   1. Decrement time-limited user punishments (ban, ad_ban, mute, staff_ban)
 *   2. Expire ads whose time_left reaches 0
 *   3. Clean up stale sessions and expired verification codes
 *
 * Symfony 6.4 has no built-in scheduler, so this service is called from a
 * Symfony Console Command that must be run every minute via the OS task
 * scheduler (Windows Task Scheduler on XAMPP, or cron on Linux).
 *
 * Command: php bin/console app:tick
 *
 * Alternatively, keep the command running with a sleep loop using:
 *   php bin/console app:tick --daemon
 */
class TickService
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserRepository              $userRepo,
        private readonly AdvertisementRepository     $adRepo,
        private readonly UserSessionRepository       $sessionRepo,
        private readonly EmailVerificationRepository $verificationRepo,
        private readonly NotifierService             $notifier,
        private readonly LoggerInterface             $logger
    ) {}

    public function tick(): array
    {
        $stats = [
            'bans_lifted'       => 0,
            'ad_bans_lifted'    => 0,
            'mutes_lifted'      => 0,
            'staff_bans_lifted' => 0,
            'ads_expired'       => 0,
            'sessions_cleaned'  => 0,
            'codes_cleaned'     => 0,
        ];

        try {
            $stats = array_merge($stats, $this->processPunishments());
            $stats['ads_expired']       = $this->processAdExpiry();
            $stats['sessions_cleaned']  = $this->cleanExpiredSessions();
            $stats['codes_cleaned']     = $this->cleanExpiredVerifications();
        } catch (\Throwable $e) {
            $this->logger->error('TickService error: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $stats;
    }

    // ── Punishments ───────────────────────────────────────────────────

    private function processPunishments(): array
    {
        $stats = [
            'bans_lifted'       => 0,
            'ad_bans_lifted'    => 0,
            'mutes_lifted'      => 0,
            'staff_bans_lifted' => 0,
        ];

        // Load all users with at least one active timed punishment
        $users = $this->userRepo->findWithActivePunishments();

        foreach ($users as $user) {
            $changed = false;

            // Account ban countdown (-1 = permanent, 0 = inactive, N = minutes left)
            if ($user->getBanStatus() === 1 && $user->getBanTimeLeft() > 0) {
                $new = $user->getBanTimeLeft() - 1;
                $user->setBanTimeLeft($new);
                if ($new <= 0) {
                    $user->setBanStatus(0);
                    $user->setBanTimeLeft(0);
                    $this->notifier->userUnbanned($user);
                    $stats['bans_lifted']++;
                }
                $changed = true;
            }

            // Ad ban countdown
            if ($user->getAdBanStatus() === 1 && $user->getAdBanTimeLeft() > 0) {
                $new = $user->getAdBanTimeLeft() - 1;
                $user->setAdBanTimeLeft($new);
                if ($new <= 0) {
                    $user->setAdBanStatus(0);
                    $user->setAdBanTimeLeft(0);
                    $this->notifier->userAdUnbanned($user);
                    $stats['ad_bans_lifted']++;
                }
                $changed = true;
            }

            // Mute countdown
            if ($user->getMuteStatus() === 1 && $user->getMuteTimeLeft() > 0) {
                $new = $user->getMuteTimeLeft() - 1;
                $user->setMuteTimeLeft($new);
                if ($new <= 0) {
                    $user->setMuteStatus(0);
                    $user->setMuteTimeLeft(0);
                    $this->notifier->userUnmuted($user);
                    $stats['mutes_lifted']++;
                }
                $changed = true;
            }

            // Staff ban countdown
            if ($user->getStaffBan() === 1 && $user->getStaffBanTimeLeft() > 0) {
                $new = $user->getStaffBanTimeLeft() - 1;
                $user->setStaffBanTimeLeft($new);
                if ($new <= 0) {
                    $user->setStaffBan(0);
                    $user->setStaffBanTimeLeft(0);
                    $this->notifier->userStaffUnbanned($user);
                    $stats['staff_bans_lifted']++;
                }
                $changed = true;
            }

            if ($changed) {
                $this->em->persist($user);
            }
        }

        if (!empty($users)) {
            $this->em->flush();
        }

        return $stats;
    }

    // ── Ad expiry ─────────────────────────────────────────────────────

    private function processAdExpiry(): int
    {
        // Ads with time_left > 0 that are active
        $activeAds = $this->adRepo->findWithPositiveTimeLeft();
        $expired   = 0;

        foreach ($activeAds as $ad) {
            $new = $ad->getTimeLeft() - 1;
            $ad->setTimeLeft($new);

            if ($new <= 0) {
                $ad->setActive(0);
                $ad->setTimeLeft(0);
                $this->notifier->adExpired($ad->getUser(), $ad->getId(), $ad->getTitle());
                $expired++;
            }

            $this->em->persist($ad);
        }

        if (!empty($activeAds)) {
            $this->em->flush();
        }

        return $expired;
    }

    // ── Cleanup ───────────────────────────────────────────────────────

    private function cleanExpiredSessions(): int
    {
        return $this->sessionRepo->deleteExpired();
    }

    private function cleanExpiredVerifications(): int
    {
        return $this->verificationRepo->deleteExpiredUnverified();
    }
}
