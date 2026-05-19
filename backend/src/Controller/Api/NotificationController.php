<?php

namespace App\Controller\Api;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\NotifierService;

#[Route('/api/notifications')]
class NotificationController extends AbstractApiController
{
    public function __construct(
        private readonly NotifierService         $notifier,
        private readonly NotificationRepository  $notifRepo,
        private readonly EntityManagerInterface  $em
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        return $this->ok(['notifications' => $this->notifier->getGrouped($me)]);
    }

    #[Route('/unread-count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        return $this->ok(['count' => $this->notifier->countUnread($me->getId())]);
    }

    #[Route('/{id}/read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markRead(int $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $notif = $this->notifRepo->find($id);

        if (!$notif || $notif->getUser()->getId() !== $me->getId()) {
            return $this->error('Not found.', 404);
        }

        $notif->setReadStatus(1);
        $this->em->flush();

        return $this->ok(['message' => 'Marked as read.']);
    }

    #[Route('/read-all', methods: ['POST'])]
    public function markAllRead(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        $this->notifRepo->markAllRead($me->getId());
        return $this->ok(['message' => 'All notifications marked as read.']);
    }
}
