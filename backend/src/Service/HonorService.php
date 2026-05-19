<?php

namespace App\Service;

use App\Entity\HonorRank;
use App\Entity\User;
use App\Repository\HonorRankRepository;
use Doctrine\ORM\EntityManagerInterface;

class HonorService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HonorRankRepository    $honorRankRepo,
        private readonly NotifierService        $notifier
    ) {}

    /**
     * Add (or subtract) points from a user and check for rank changes.
     */
    public function adjustPoints(User $user, int $delta): void
    {
        $oldPoints = $user->getHonorPoints();
        $newPoints = max(0, $oldPoints + $delta);
        $user->setHonorPoints($newPoints);
        $this->em->persist($user);

        $this->checkRankChange($user, $oldPoints, $newPoints);
        $this->em->flush();
    }

    /**
     * Apply penalty for a moderation event (warn/ban/mute/ad_warn/ad_ban) using the
     * score table stored in HonorRank. Each rank defines different penalty amounts.
     */
    public function applyPenalty(User $user, string $event): void
    {
        $rank = $this->getRankForUser($user);
        if (!$rank) return;

        $delta = match ($event) {
            'ad_warn'    => $rank->getAdWarnScore(),
            'ad_ban'     => $rank->getAdBanScore(),
            'user_warn'  => $rank->getUserWarnScore(),
            'user_ban'   => $rank->getUserBanScore(),
            'mute_warn'  => $rank->getMuteWarnScore(),
            'mute'       => $rank->getMuteScore(),
            default      => 0,
        };

        if ($delta !== 0) {
            $this->adjustPoints($user, $delta);
        }
    }

    /**
     * Apply score from a received review (positive/negative).
     */
    public function applyReviewScore(User $user, string $rateType): void
    {
        $rank = $this->getRankForUser($user);
        if (!$rank) return;

        $delta = $rateType === 'positive'
            ? $rank->getPosRatingScore()
            : $rank->getNegRatingScore();

        $this->adjustPoints($user, $delta);
    }

    private function checkRankChange(User $user, int $oldPoints, int $newPoints): void
    {
        $ranks = $this->honorRankRepo->findAllOrdered();
        $oldRank = $this->findRankForPoints($ranks, $oldPoints);
        $newRank = $this->findRankForPoints($ranks, $newPoints);

        if (!$oldRank || !$newRank) return;
        if ($oldRank->getId() === $newRank->getId()) return;

        if ($newRank->getRank() > $oldRank->getRank()) {
            $this->notifier->honorRankUp($user, $newRank->getName());
        } else {
            $this->notifier->honorRankDown($user, $newRank->getName());
        }
    }

    /**
     * @param HonorRank[] $ranks  ordered by min_score ASC
     */
    private function findRankForPoints(array $ranks, int $points): ?HonorRank
    {
        $best = null;
        foreach ($ranks as $r) {
            if ($r->getLockStatus() === 1) continue;
            if ($points >= $r->getMinScore()) {
                $best = $r;
            }
        }
        return $best;
    }

    private function getRankForUser(User $user): ?HonorRank
    {
        return $this->findRankForPoints(
            $this->honorRankRepo->findAllOrdered(),
            $user->getHonorPoints()
        );
    }

    public function getCurrentRank(User $user): ?HonorRank
    {
        return $this->getRankForUser($user);
    }
}
