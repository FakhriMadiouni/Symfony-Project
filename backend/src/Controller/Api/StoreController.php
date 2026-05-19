<?php

namespace App\Controller\Api;

use App\Entity\UserAdToken;
use App\Repository\StoreAdTokenRepository;
use App\Repository\UserAdTokenRepository;
use App\Service\NotifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/store')]
class StoreController extends AbstractApiController
{
    public function __construct(
        private readonly StoreAdTokenRepository  $storeTokenRepo,
        private readonly UserAdTokenRepository   $userTokenRepo,
        private readonly EntityManagerInterface  $em,
        private readonly NotifierService         $notifier
    ) {}

    // ── List available offers ─────────────────────────────────────────

    #[Route('/tokens', methods: ['GET'])]
    public function listOffers(): JsonResponse
    {
        $tokens = $this->storeTokenRepo->findAllCurrentlyActive();
        return $this->ok(['tokens' => array_map(fn($t) => $this->serializeOffer($t), $tokens)]);
    }

    // ── Purchase ──────────────────────────────────────────────────────

    #[Route('/buy', methods: ['POST'])]
    public function buy(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $body = $this->body($request);

        $storeTokenId = (int)($body['store_token_id'] ?? 0);
        $quantity     = max(1, (int)($body['quantity'] ?? 1));

        if ($quantity > 99) return $this->error('Maximum 99 tokens per purchase.');

        $offer = $this->storeTokenRepo->find($storeTokenId);
        if (!$offer || !$offer->isCurrentlyActive()) {
            return $this->error('Token offer not found or no longer available.');
        }

        $lines = [];
        for ($i = 0; $i < $quantity; $i++) {
            $userToken = new UserAdToken();
            $userToken->setUser($me);
            $userToken->setCreationDate(new \DateTime());
            $userToken->setActive(1);
            $userToken->setName($offer->getName());
            $userToken->setDescription($offer->getDescription());
            $userToken->setPricePerUnit($offer->getPricePerUnit());
            $userToken->setDiscount($offer->getDiscount());
            $userToken->setMaxMedia($offer->getMaxMedia());
            $userToken->setAdDuration($offer->getAdDuration());
            $this->em->persist($userToken);
            $lines[] = "× {$offer->getName()} ({$offer->getAdDuration()} days)";
        }

        $this->em->flush();

        $this->notifier->tokensPurchasedBulk($me, $lines);

        return $this->ok([
            'message'  => "Purchased $quantity × {$offer->getName()} token(s) successfully.",
            'quantity' => $quantity,
        ], 201);
    }

    // ── My tokens ─────────────────────────────────────────────────────

    #[Route('/my-tokens', methods: ['GET'])]
    public function myTokens(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $tokens = $this->userTokenRepo->findAvailableForUser($me->getId());

        return $this->ok(['tokens' => array_map(fn($t) => [
            'user_token_id'  => $t->getId(),
            'name'           => $t->getName(),
            'description'    => $t->getDescription(),
            'max_media'      => $t->getMaxMedia(),
            'ad_duration'    => $t->getAdDuration(),
            'creation_date'  => $t->getCreationDate()?->format('Y-m-d H:i:s'),
        ], $tokens)]);
    }

    // ── Serializer ────────────────────────────────────────────────────

    private function serializeOffer(\App\Entity\StoreAdToken $t): array
    {
        return [
            'id_store_ad_token'     => $t->getId(),
            'name'                  => $t->getName(),
            'description'           => $t->getDescription(),
            'price_per_unit'        => $t->getPricePerUnit(),
            'discount'              => $t->getDiscount(),
            'max_media'             => $t->getMaxMedia(),
            'ad_duration'           => $t->getAdDuration(),
            'offer_expiration_date' => $t->getOfferExpirationDate()?->format('Y-m-d H:i:s'),
        ];
    }
}
