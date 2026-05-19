<?php

namespace App\Controller\Api;

use App\Entity\Report;
use App\Repository\AdvertisementRepository;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\AdReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports')]
class ReportController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly UserRepository          $userRepo,
        private readonly AdvertisementRepository $adRepo,
        private readonly ConversationRepository  $convRepo,
        private readonly MessageRepository       $msgRepo,
        private readonly AdReviewRepository      $reviewRepo
    ) {}

    #[Route('', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $body = $this->body($request);

        $type            = $body['type'] ?? '';
        $reportedUserId  = (int)($body['reported_user_id'] ?? 0);
        $reason          = trim($body['reason'] ?? '');

        $validTypes = ['user', 'ad', 'conversation', 'message', 'review'];
        if (!in_array($type, $validTypes, true)) return $this->error('Invalid report type.');

        $reportedUser = $this->userRepo->find($reportedUserId);
        if (!$reportedUser) return $this->error('Reported user not found.', 404);
        if ($reportedUser->getId() === $me->getId()) return $this->error('Cannot report yourself.');

        $report = new Report();
        $report->setReporter($me);
        $report->setReportedUser($reportedUser);
        $report->setDate(new \DateTime());
        $report->setType($type);
        $report->setReason($reason ?: null);

        if ($type === 'ad') {
            $ad = $this->adRepo->find((int)($body['reference_id'] ?? 0));
            if (!$ad) return $this->error('Ad not found.', 404);
            $report->setReportedAd($ad);
        } elseif ($type === 'message') {
            $msg = $this->msgRepo->find((int)($body['reference_id'] ?? 0));
            if (!$msg) return $this->error('Message not found.', 404);
            $report->setReportedMsg($msg);
        } elseif ($type === 'review') {
            $rev = $this->reviewRepo->find((int)($body['reference_id'] ?? 0));
            if (!$rev) return $this->error('Review not found.', 404);
            $report->setReportedAdReview($rev);
        }

        $this->em->persist($report);
        $this->em->flush();

        return $this->ok(['report_id' => $report->getId(), 'message' => 'Report submitted. Our team will review it shortly.'], 201);
    }
}
