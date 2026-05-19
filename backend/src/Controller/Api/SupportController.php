<?php

namespace App\Controller\Api;

use App\Entity\SupportConversation;
use App\Entity\SupportMessage;
use App\Repository\SupportConversationRepository;
use App\Repository\SupportMessageRepository;
use App\Service\NotifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/support')]
class SupportController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface       $em,
        private readonly SupportConversationRepository$convRepo,
        private readonly SupportMessageRepository     $msgRepo,
        private readonly NotifierService              $notifier
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me    = $this->getUser();
        $convs = $this->convRepo->findForUser($me->getId());

        return $this->ok(['conversations' => array_map(fn($c) => [
            'support_conv_id' => $c->getId(),
            'subject'         => $c->getSubject(),
            'status'          => $c->getStatus(),
            'opened_date'     => $c->getOpenedDate()?->format('Y-m-d H:i:s'),
            'last_reply_date' => $c->getLastReplyDate()?->format('Y-m-d H:i:s'),
        ], $convs)]);
    }

    #[Route('', methods: ['POST'])]
    public function open(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me      = $this->getUser();
        $body    = $this->body($request);
        $subject = trim($body['subject'] ?? '');
        $message = trim($body['message'] ?? '');

        if (strlen($subject) < 3) return $this->error('Subject too short.');
        if (!$message)            return $this->error('Message cannot be empty.');

        $conv = new SupportConversation();
        $conv->setUser($me);
        $conv->setSubject($subject);
        $conv->setStatus('open');
        $conv->setOpenedDate(new \DateTime());
        $conv->setLastReplyDate(new \DateTime());

        $this->em->persist($conv);

        $msg = new SupportMessage();
        $msg->setSupportConversation($conv);
        $msg->setSender($me);
        $msg->setIsStaff(0);
        $msg->setContent($message);
        $msg->setSentDate(new \DateTime());

        $this->em->persist($msg);
        $this->em->flush();

        $this->notifier->supportOpened($me, $conv->getId(), $subject);

        return $this->ok(['support_conv_id' => $conv->getId()], 201);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $conv = $this->convRepo->find($id);

        if (!$conv) return $this->error('Not found.', 404);
        if ($conv->getUser()->getId() !== $me->getId()) return $this->error('Forbidden.', 403);

        $msgs = $this->msgRepo->findByConversation($id);

        return $this->ok([
            'conversation' => [
                'support_conv_id' => $conv->getId(),
                'subject'         => $conv->getSubject(),
                'status'          => $conv->getStatus(),
                'opened_date'     => $conv->getOpenedDate()?->format('Y-m-d H:i:s'),
            ],
            'messages' => array_map(fn($m) => [
                'support_msg_id' => $m->getId(),
                'is_staff'       => $m->getIsStaff(),
                'content'        => $m->getContent(),
                'sent_date'      => $m->getSentDate()?->format('Y-m-d H:i:s'),
            ], $msgs),
        ]);
    }

    #[Route('/{id}/reply', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reply(int $id, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $conv = $this->convRepo->find($id);

        if (!$conv) return $this->error('Not found.', 404);
        if ($conv->getUser()->getId() !== $me->getId()) return $this->error('Forbidden.', 403);
        if ($conv->getStatus() === 'closed') return $this->error('This support ticket is closed.');

        $body    = $this->body($request);
        $content = trim($body['content'] ?? '');
        if (!$content) return $this->error('Message cannot be empty.');

        $msg = new SupportMessage();
        $msg->setSupportConversation($conv);
        $msg->setSender($me);
        $msg->setIsStaff(0);
        $msg->setContent($content);
        $msg->setSentDate(new \DateTime());

        $conv->setLastReplyDate(new \DateTime());

        $this->em->persist($msg);
        $this->em->persist($conv);
        $this->em->flush();

        return $this->ok(['support_msg_id' => $msg->getId()], 201);
    }
}
