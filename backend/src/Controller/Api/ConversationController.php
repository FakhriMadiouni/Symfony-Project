<?php

namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\AdvertisementRepository;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\NotifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/conversations')]
class ConversationController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly ConversationRepository  $convRepo,
        private readonly MessageRepository       $msgRepo,
        private readonly AdvertisementRepository $adRepo,
        private readonly NotifierService         $notifier
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $convs = $this->convRepo->findForUser($me->getId());

        return $this->ok(['conversations' => array_map(fn($c) => $this->serializeConv($c, $me->getId()), $convs)]);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $conv = $this->convRepo->find($id);

        if (!$conv) return $this->error('Conversation not found.', 404);
        if (!$this->isParticipant($conv, $me->getId())) return $this->error('Forbidden.', 403);

        $ad        = $conv->getAdvertisement();
        $sender    = $conv->getSender();
        $advertiser= $conv->getAdvertiser();
        $isAdvertiser = $advertiser->getId() === $me->getId();
        $otherUser = $isAdvertiser ? $sender : $advertiser;

        return $this->ok([
            'conversation' => [
                'conversation_id'   => $conv->getId(),
                'ad_id'             => $ad->getId(),
                'ad_title'          => $ad->getTitle(),
                'ad_active'         => $ad->getActive(),
                'ad_ban_status'     => $ad->getBanStatus(),
                'ad_hidden'         => $ad->getHiddenByAdvertiser(),
                'lock_status'       => $conv->getLockStatus(),
                'start_date'        => $conv->getStartDate()?->format('Y-m-d H:i:s'),
                'is_advertiser'     => $isAdvertiser,
                'other_user' => [
                    'user_id'       => $otherUser->getId(),
                    'username'      => $otherUser->getUsername(),
                    'profile_picture'=> $otherUser->getProfilePicture(),
                    'ban_status'    => $otherUser->getBanStatus(),
                    'mute_status'   => $otherUser->getMuteStatus(),
                ],
                'my_mute_status'    => $me->getMuteStatus(),
                'unread'            => $this->msgRepo->countUnread($id, $me->getId()),
            ],
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $body = $this->body($request);
        $adId = (int)($body['ad_id'] ?? 0);

        $ad = $this->adRepo->find($adId);
        if (!$ad) return $this->error('Ad not found.', 404);
        if ($ad->getActive() !== 1) return $this->error('This ad is no longer active.');
        if ($ad->getUser()->getId() === $me->getId()) return $this->error('Cannot message your own ad.');

        if ($this->convRepo->findBySenderAndAd($me->getId(), $adId)) {
            return $this->error('You already have a conversation about this ad.');
        }

        $conv = new Conversation();
        $conv->setAdvertisement($ad);
        $conv->setAdvertiser($ad->getUser());
        $conv->setSender($me);
        $conv->setStartDate(new \DateTime());
        $conv->setLastMessageDate(new \DateTime());

        $this->em->persist($conv);
        $this->em->flush();

        $this->notifier->convStarted($ad->getUser(), $conv->getId(), $me->getUsername(), $ad->getTitle());

        // Send first message if provided
        $content = trim($body['message'] ?? '');
        if ($content) {
            $this->sendMessage($conv, $me, $content);
        }

        return $this->ok(['conversation_id' => $conv->getId()], 201);
    }

    #[Route('/{id}/messages', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function messages(int $id, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $conv = $this->convRepo->find($id);
        if (!$conv) return $this->error('Conversation not found.', 404);
        if (!$this->isParticipant($conv, $me->getId())) return $this->error('Forbidden.', 403);

        $limit  = min((int)($request->query->get('limit', 50)), 100);
        $offset = (int)$request->query->get('offset', 0);

        $this->msgRepo->markConversationRead($id, $me->getId());
        $msgs = $this->msgRepo->findByConversation($id, $limit, $offset);

        return $this->ok(['messages' => array_map(fn($m) => $this->serializeMsg($m), $msgs)]);
    }

    #[Route('/{id}/messages', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(int $id, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $conv = $this->convRepo->find($id);

        if (!$conv) return $this->error('Conversation not found.', 404);
        if (!$this->isParticipant($conv, $me->getId())) return $this->error('Forbidden.', 403);
        if ($conv->getLockStatus() === 1) return $this->error('This conversation is locked.');
        if ($me->getMuteStatus() === 1) return $this->error('You are currently muted and cannot send messages.');

        $body    = $this->body($request);
        $content = trim($body['content'] ?? '');
        if (!$content) return $this->error('Message cannot be empty.');
        if (strlen($content) > 2000) return $this->error('Message too long (max 2000 characters).');

        $msg = $this->sendMessage($conv, $me, $content);

        return $this->ok(['message' => $this->serializeMsg($msg)], 201);
    }

    private function sendMessage(Conversation $conv, \App\Entity\User $sender, string $content): Message
    {
        $msg = new Message();
        $msg->setConversation($conv);
        $msg->setSender($sender);
        $msg->setTimestamp(new \DateTime());
        $msg->setType('text');
        $msg->setContent($content);
        $msg->setReadStatus(0);

        $conv->setLastMessageDate(new \DateTime());

        $this->em->persist($msg);
        $this->em->persist($conv);
        $this->em->flush();

        return $msg;
    }

    private function isParticipant(Conversation $conv, int $userId): bool
    {
        return $conv->getSender()->getId() === $userId
            || $conv->getAdvertiser()->getId() === $userId;
    }

    private function serializeConv(Conversation $c, int $myId): array
    {
        $unread = $this->msgRepo->countUnread($c->getId(), $myId);
        return [
            'conversation_id'    => $c->getId(),
            'ad_id'              => $c->getAdvertisement()->getId(),
            'ad_title'           => $c->getAdvertisement()->getTitle(),
            'other_user'         => $c->getSender()->getId() === $myId
                ? ['user_id' => $c->getAdvertiser()->getId(), 'username' => $c->getAdvertiser()->getUsername()]
                : ['user_id' => $c->getSender()->getId(),     'username' => $c->getSender()->getUsername()],
            'unread'             => $unread,
            'last_message_date'  => $c->getLastMessageDate()?->format('Y-m-d H:i:s'),
            'lock_status'        => $c->getLockStatus(),
        ];
    }

    private function serializeMsg(Message $m): array
    {
        return [
            'message_id'  => $m->getId(),
            'sender_id'   => $m->getSender()->getId(),
            'sender_name' => $m->getSender()->getUsername(),
            'content'     => $m->getContent(),
            'type'        => $m->getType(),
            'timestamp'   => $m->getTimestamp()?->format('Y-m-d H:i:s'),
            'read_status' => $m->getReadStatus(),
        ];
    }
}
