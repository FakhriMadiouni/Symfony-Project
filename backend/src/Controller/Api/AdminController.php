<?php

namespace App\Controller\Api;

use App\Repository\AdvertisementRepository;
use App\Repository\ReportRepository;
use App\Repository\SupportConversationRepository;
use App\Repository\SupportMessageRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\HonorService;
use App\Service\NotifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface       $em,
        private readonly UserRepository               $userRepo,
        private readonly AdvertisementRepository      $adRepo,
        private readonly ReportRepository             $reportRepo,
        private readonly SupportConversationRepository$supportConvRepo,
        private readonly SupportMessageRepository     $supportMsgRepo,
        private readonly HonorService                 $honorService,
        private readonly NotifierService              $notifier,
        private readonly EmailService                 $emailService
    ) {}

    // ── Helpers ───────────────────────────────────────────────────────

    private function staffCan(string $permission): bool
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $rank = $me->getStaffDivisionRank();
        if (!$rank) return false;
        $perm = $rank->toPermissionsArray();
        return ($perm[$permission] ?? 0) === 1;
    }

    private function findUserOrError(int $id): array|\App\Entity\User
    {
        $u = $this->userRepo->find($id);
        if (!$u) return ['__error' => true, '__response' => $this->error('User not found.', 404)];
        return $u;
    }

    // ── User moderation ───────────────────────────────────────────────

    #[Route('/users/{id}/warn', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function warnUser(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('ban_warn')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $body   = $this->body($request);
        $reason = trim($body['reason'] ?? '');

        $user->setBanWarnings($user->getBanWarnings() + 1);
        $this->em->persist($user);
        $this->honorService->applyPenalty($user, 'user_warn');
        $this->notifier->userWarned($user, $reason);
        $this->em->flush();

        return $this->ok(['warnings' => $user->getBanWarnings()]);
    }

    #[Route('/users/{id}/unwarn', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unwarnUser(int $id): JsonResponse
    {
        if (!$this->staffCan('ban_unwarn')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $user->setBanWarnings(max(0, $user->getBanWarnings() - 1));
        $this->em->persist($user);
        $this->notifier->userUnwarned($user);
        $this->em->flush();

        return $this->ok(['warnings' => $user->getBanWarnings()]);
    }

    #[Route('/users/{id}/ban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function banUser(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('ban')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $body    = $this->body($request);
        $minutes = (int)($body['minutes'] ?? -1); // -1 = permanent
        $reason  = trim($body['reason'] ?? '');

        $user->setBanStatus(1);
        $user->setBanTimeLeft($minutes === 0 ? -1 : $minutes);
        $this->em->persist($user);
        $this->honorService->applyPenalty($user, 'user_ban');
        $this->notifier->userBanned($user, $reason, $minutes > 0 ? $minutes : 0);
        $this->emailService->sendBanNotice($user->getEmail(), $user->getUsername(), 'ban', $minutes, $reason);
        $this->em->flush();

        return $this->ok();
    }

    #[Route('/users/{id}/unban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unbanUser(int $id): JsonResponse
    {
        if (!$this->staffCan('unban')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $user->setBanStatus(0);
        $user->setBanTimeLeft(0);
        $this->em->persist($user);
        $this->notifier->userUnbanned($user);
        $this->em->flush();

        return $this->ok();
    }

    #[Route('/users/{id}/mute-warn', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function muteWarnUser(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('mute_warn')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $reason = trim($this->body($request)['reason'] ?? '');
        $user->setMuteWarnings($user->getMuteWarnings() + 1);
        $this->em->persist($user);
        $this->honorService->applyPenalty($user, 'mute_warn');
        $this->notifier->userMuteWarned($user, $reason);
        $this->em->flush();

        return $this->ok(['mute_warnings' => $user->getMuteWarnings()]);
    }

    #[Route('/users/{id}/mute-unwarn', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function muteUnwarnUser(int $id): JsonResponse
    {
        if (!$this->staffCan('mute_unwarn')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $user->setMuteWarnings(max(0, $user->getMuteWarnings() - 1));
        $this->em->persist($user);
        $this->notifier->userUnwarned($user);
        $this->em->flush();

        return $this->ok(['mute_warnings' => $user->getMuteWarnings()]);
    }

    #[Route('/users/{id}/mute', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function muteUser(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('mute')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $body    = $this->body($request);
        $minutes = (int)($body['minutes'] ?? -1);
        $reason  = trim($body['reason'] ?? '');

        $user->setMuteStatus(1);
        $user->setMuteTimeLeft($minutes === 0 ? -1 : $minutes);
        $this->em->persist($user);
        $this->honorService->applyPenalty($user, 'mute');
        $this->notifier->userMuted($user, $reason, $minutes > 0 ? $minutes : 0);
        $this->emailService->sendBanNotice($user->getEmail(), $user->getUsername(), 'mute', $minutes, $reason);
        $this->em->flush();

        return $this->ok();
    }

    #[Route('/users/{id}/unmute', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unmuteUser(int $id): JsonResponse
    {
        if (!$this->staffCan('unmute')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $user->setMuteStatus(0);
        $user->setMuteTimeLeft(0);
        $this->em->persist($user);
        $this->notifier->userUnmuted($user);
        $this->em->flush();

        return $this->ok();
    }

    #[Route('/users/{id}/ad-warn', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adWarnUser(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('ad_warn')) return $this->error('Insufficient permissions.', 403);

        $user   = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $reason = trim($this->body($request)['reason'] ?? '');
        $user->setAdBanWarnings($user->getAdBanWarnings() + 1);
        $this->em->persist($user);
        $this->honorService->applyPenalty($user, 'ad_warn');
        $this->notifier->userAdWarned($user, $reason);
        $this->em->flush();

        return $this->ok(['ad_ban_warnings' => $user->getAdBanWarnings()]);
    }

    #[Route('/users/{id}/ad-ban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adBanUser(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('ad_ban')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $body    = $this->body($request);
        $minutes = (int)($body['minutes'] ?? -1);
        $reason  = trim($body['reason'] ?? '');

        $user->setAdBanStatus(1);
        $user->setAdBanTimeLeft($minutes === 0 ? -1 : $minutes);
        $this->em->persist($user);
        $this->honorService->applyPenalty($user, 'ad_ban');
        $this->notifier->userAdBanned($user, $reason, $minutes > 0 ? $minutes : 0);
        $this->emailService->sendBanNotice($user->getEmail(), $user->getUsername(), 'ad_ban', $minutes, $reason);
        $this->em->flush();

        return $this->ok();
    }

    #[Route('/users/{id}/ad-unban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adUnbanUser(int $id): JsonResponse
    {
        if (!$this->staffCan('ad_unban')) return $this->error('Insufficient permissions.', 403);

        $user = $this->findUserOrError($id);
        if (is_array($user)) return $user['__response'];

        $user->setAdBanStatus(0);
        $user->setAdBanTimeLeft(0);
        $this->em->persist($user);
        $this->notifier->userAdUnbanned($user);
        $this->em->flush();

        return $this->ok();
    }

    // ── Ad moderation ─────────────────────────────────────────────────

    #[Route('/ads/{id}/ban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function banAd(int $id): JsonResponse
    {
        if (!$this->staffCan('ban_ads')) return $this->error('Insufficient permissions.', 403);

        $ad = $this->adRepo->find($id);
        if (!$ad) return $this->error('Ad not found.', 404);

        $ad->setBanStatus(1);
        $this->em->persist($ad);
        $this->notifier->adBanned($ad->getUser(), $ad->getId(), $ad->getTitle());
        $this->em->flush();

        return $this->ok();
    }

    #[Route('/ads/{id}/unban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unbanAd(int $id): JsonResponse
    {
        if (!$this->staffCan('unban_ads')) return $this->error('Insufficient permissions.', 403);

        $ad = $this->adRepo->find($id);
        if (!$ad) return $this->error('Ad not found.', 404);

        $ad->setBanStatus(0);
        $this->em->persist($ad);
        $this->notifier->adUnbanned($ad->getUser(), $ad->getId(), $ad->getTitle());
        $this->em->flush();

        return $this->ok();
    }

    // ── Reports ───────────────────────────────────────────────────────

    #[Route('/reports', methods: ['GET'])]
    public function reports(Request $request): JsonResponse
    {
        if (!$this->staffCan('check_reports')) return $this->error('Insufficient permissions.', 403);

        $limit  = min((int)($request->query->get('limit', 20)), 50);
        $offset = (int)$request->query->get('offset', 0);

        $reports = $this->reportRepo->findOpenReports($limit, $offset);

        return $this->ok(['reports' => array_map(fn($r) => [
            'report_id'        => $r->getId(),
            'type'             => $r->getType(),
            'reason'           => $r->getReason(),
            'reporter'         => ['user_id' => $r->getReporter()->getId(), 'username' => $r->getReporter()->getUsername()],
            'reported_user'    => ['user_id' => $r->getReportedUser()->getId(), 'username' => $r->getReportedUser()->getUsername()],
            'date'             => $r->getDate()?->format('Y-m-d H:i:s'),
        ], $reports)]);
    }

    #[Route('/reports/{id}/close', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function closeReport(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('close_reports')) return $this->error('Insufficient permissions.', 403);

        $report = $this->reportRepo->find($id);
        if (!$report) return $this->error('Report not found.', 404);

        $body    = $this->body($request);
        $action  = $body['action'] ?? 'reject'; // 'resolve' | 'reject'
        $details = trim($body['details'] ?? '');

        $report->setLockStatus(1);
        $this->em->persist($report);

        if ($action === 'resolve') {
            $this->notifier->reportResolved($report->getReportedUser(), $report->getId(), $details);
        } else {
            $this->notifier->reportRejected($report->getReportedUser(), $report->getId(), $details);
        }

        $this->em->flush();

        return $this->ok();
    }

    // ── Support ───────────────────────────────────────────────────────

    #[Route('/support', methods: ['GET'])]
    public function supportList(Request $request): JsonResponse
    {
        if (!$this->staffCan('manage_support')) return $this->error('Insufficient permissions.', 403);

        $limit  = min((int)($request->query->get('limit', 20)), 50);
        $offset = (int)$request->query->get('offset', 0);
        $convs  = $this->supportConvRepo->findOpen($limit, $offset);

        return $this->ok(['conversations' => array_map(fn($c) => [
            'support_conv_id' => $c->getId(),
            'subject'         => $c->getSubject(),
            'status'          => $c->getStatus(),
            'user'            => ['user_id' => $c->getUser()->getId(), 'username' => $c->getUser()->getUsername()],
            'opened_date'     => $c->getOpenedDate()?->format('Y-m-d H:i:s'),
            'last_reply_date' => $c->getLastReplyDate()?->format('Y-m-d H:i:s'),
        ], $convs)]);
    }

    #[Route('/support/{id}/reply', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supportReply(int $id, Request $request): JsonResponse
    {
        if (!$this->staffCan('manage_support')) return $this->error('Insufficient permissions.', 403);

        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $conv = $this->supportConvRepo->find($id);
        if (!$conv) return $this->error('Not found.', 404);

        $body    = $this->body($request);
        $content = trim($body['content'] ?? '');
        if (!$content) return $this->error('Message cannot be empty.');

        $msg = new \App\Entity\SupportMessage();
        $msg->setSupportConversation($conv);
        $msg->setSender($me);
        $msg->setIsStaff(1);
        $msg->setContent($content);
        $msg->setSentDate(new \DateTime());

        $conv->setLastReplyDate(new \DateTime());

        $this->em->persist($msg);
        $this->em->persist($conv);
        $this->em->flush();

        $this->notifier->supportUpdated($conv->getUser(), $conv->getId(), $conv->getSubject());
        $this->emailService->sendSupportReply($conv->getUser()->getEmail(), $conv->getUser()->getUsername(), $conv->getSubject(), $content);

        return $this->ok(['support_msg_id' => $msg->getId()], 201);
    }

    #[Route('/support/{id}/close', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supportClose(int $id): JsonResponse
    {
        if (!$this->staffCan('manage_support')) return $this->error('Insufficient permissions.', 403);

        $conv = $this->supportConvRepo->find($id);
        if (!$conv) return $this->error('Not found.', 404);

        $conv->setStatus('closed');
        $conv->setClosedDate(new \DateTime());
        $this->em->persist($conv);
        $this->em->flush();

        $this->notifier->supportClosed($conv->getUser(), $conv->getId(), $conv->getSubject());
        $this->emailService->sendSupportClosed($conv->getUser()->getEmail(), $conv->getUser()->getUsername(), $conv->getSubject());

        return $this->ok();
    }
}
