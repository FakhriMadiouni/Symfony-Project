<?php

namespace App\Controller\Api;

use App\Entity\AdReview;
use App\Repository\AdReviewRepository;
use App\Repository\ConversationRepository;
use App\Repository\HonorRankRepository;
use App\Service\HonorService;
use App\Service\NotifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reviews')]
class ReviewController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdReviewRepository     $reviewRepo,
        private readonly ConversationRepository $convRepo,
        private readonly HonorRankRepository    $honorRankRepo,
        private readonly HonorService           $honorService,
        private readonly NotifierService        $notifier
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $body = $this->body($request);

        if ($me->getMuteStatus() === 1) return $this->error('You are currently muted and cannot leave reviews.');

        $convId    = (int)($body['conversation_id'] ?? 0);
        $rateType  = $body['rate'] ?? '';
        $comment   = trim($body['comment'] ?? '');
        $anonymous = !empty($body['anonymous']);

        if (!in_array($rateType, ['positive', 'negative'], true)) {
            return $this->error('Rate must be "positive" or "negative".');
        }

        $conv = $this->convRepo->find($convId);
        if (!$conv) return $this->error('Conversation not found.', 404);

        // Only participants can leave a review
        $isParticipant = $conv->getSender()->getId() === $me->getId()
            || $conv->getAdvertiser()->getId() === $me->getId();

        if (!$isParticipant) return $this->error('Forbidden.', 403);
        if ($this->reviewRepo->existsForConversationAndRater($convId, $me->getId())) {
            return $this->error('You have already reviewed this conversation.');
        }

        // Determine who is being rated
        $ratedUser = $conv->getSender()->getId() === $me->getId()
            ? $conv->getAdvertiser()
            : $conv->getSender();

        // Get score from honor rank table
        $rank  = $this->honorService->getCurrentRank($ratedUser);
        $score = $rank ? ($rateType === 'positive' ? $rank->getPosRatingScore() : $rank->getNegRatingScore()) : 0;

        $review = new AdReview();
        $review->setAdvertisement($conv->getAdvertisement());
        $review->setConversation($conv);
        $review->setRater($me);
        $review->setRatedUser($ratedUser);
        $review->setDate(new \DateTime());
        $review->setRate($rateType);
        $review->setScore($score);
        $review->setComment($comment ?: null);
        $review->setAnonymousStatus($anonymous ? 1 : 0);

        $this->em->persist($review);

        // Adjust honor points
        $this->honorService->applyReviewScore($ratedUser, $rateType);

        $this->notifier->adReview($ratedUser, $conv->getAdvertisement()->getId(), $conv->getAdvertisement()->getTitle(), $rateType, $anonymous ? 'Anonymous' : $me->getUsername());

        $this->em->flush();

        return $this->ok(['ad_review_id' => $review->getId()], 201);
    }

    #[Route('/check', methods: ['GET'])]
    public function check(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $convId = (int)$request->query->get('conv_id', 0);
        if (!$convId) return $this->error('conv_id required.');
        $exists = $this->reviewRepo->existsForConversationAndRater($convId, $me->getId());
        return $this->ok(['reviewed' => $exists]);
    }
}
