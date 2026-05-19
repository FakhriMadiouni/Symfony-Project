<?php

namespace App\Controller\Api;

use App\Entity\Advertisement;
use App\Entity\Media;
use App\Repository\AdvertisementRepository;
use App\Repository\CountryRepository;
use App\Repository\MediaRepository;
use App\Repository\StoreAdTokenRepository;
use App\Repository\SubcategoryRepository;
use App\Service\NotifierService;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ads')]
class AdvertisementController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly AdvertisementRepository $adRepo,
        private readonly MediaRepository         $mediaRepo,
        private readonly SubcategoryRepository   $subcatRepo,
        private readonly CountryRepository       $countryRepo,
        private readonly StoreAdTokenRepository  $storeTokenRepo,
        private readonly UploadService           $uploadService,
        private readonly NotifierService         $notifier
    ) {}

    // ── Public listing ────────────────────────────────────────────────

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $q     = $request->query;
        $limit = min((int)($q->get('limit', 20)), 50);
        $followedOnly = (bool)$q->get('followed_only', false);

        // Followed-only feed: requires authentication
        if ($followedOnly) {
            $me = $this->getUser();
            if (!$me) return $this->error('Authentication required.', 401);

            $ads   = $this->adRepo->findPublicFollowed(
                $me->getId(),
                $q->get('search'),
                $q->get('sort', 'newest'),
                $limit,
                (int)$q->get('offset', 0)
            );
            $total = $this->adRepo->countPublicFollowed($me->getId(), $q->get('search'));
        } else {
            $ads   = $this->adRepo->findPublic(
                $q->get('subcategory') ? (int)$q->get('subcategory') : null,
                $q->get('country')     ? (int)$q->get('country')     : null,
                $q->get('search'),
                $q->get('sort', 'newest'),
                $limit,
                (int)$q->get('offset', 0)
            );
            $total = $this->adRepo->countPublic(
                $q->get('subcategory') ? (int)$q->get('subcategory') : null,
                $q->get('country')     ? (int)$q->get('country')     : null,
                $q->get('search')
            );
        }

        return $this->ok([
            'ads'   => array_map(fn($a) => $this->serializeSummary($a), $ads),
            'total' => $total,
        ]);
    }


    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $ad = $this->adRepo->find($id);
        if (!$ad) return $this->error('Ad not found.', 404);

        // Public-visible check
        if ($ad->getActive() !== 1 || $ad->getBanStatus() !== 0 || $ad->getHiddenByAdvertiser() !== 0) {
            // Allow the owner to see their own ad
            $me = $this->getUser();
            if (!$me || $me->getId() !== $ad->getUser()->getId()) {
                return $this->error('Ad not available.', 404);
            }
        }

        return $this->ok(['ad' => $this->serializeDetail($ad)]);
    }

    // ── Authenticated CRUD ────────────────────────────────────────────

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();

        if ($me->getAdBanStatus() === 1) return $this->error('You are banned from posting advertisements.');
        if ($me->getBanStatus()   === 1) return $this->error('Your account is currently banned.');

        $body = $this->body($request);

        $subcatId = (int)($body['subcategory_id'] ?? 0);
        $countryId= (int)($body['country_id']     ?? 0);
        $tokenId  = (int)($body['store_token_id'] ?? 0);

        $subcat  = $this->subcatRepo->find($subcatId);
        $country = $this->countryRepo->find($countryId);
        $token   = $this->storeTokenRepo->find($tokenId);

        if (!$subcat)  return $this->error('Invalid subcategory.');
        if (!$country) return $this->error('Invalid country.');
        if (!$token || !$token->isCurrentlyActive()) return $this->error('Invalid or inactive token offer.');

        $title = trim($body['title'] ?? '');
        if (strlen($title) < 3 || strlen($title) > 200) return $this->error('Title must be 3–200 characters.');

        // Create a UserAdToken record to track token ownership
        $userToken = new \App\Entity\UserAdToken();
        $userToken->setUser($me);
        $userToken->setCreationDate(new \DateTime());
        $userToken->setActive(1);
        $userToken->setName($token->getName());
        $userToken->setDescription($token->getDescription());
        $userToken->setPricePerUnit($token->getPricePerUnit());
        $userToken->setDiscount($token->getDiscount());
        $userToken->setMaxMedia($token->getMaxMedia());
        $userToken->setAdDuration($token->getAdDuration());
        $this->em->persist($userToken);

        $ad = new Advertisement();
        $ad->setUser($me);
        $ad->setSubcategory($subcat);
        $ad->setAdToken($userToken);
        $ad->setCountry($country);
        $ad->setRegionName(trim($body['region_name'] ?? '') ?: null);
        $ad->setCreationDate(new \DateTime());
        $ad->setTitle($title);
        $ad->setDescription(trim($body['description'] ?? '') ?: null);
        $ad->setPrice((string)max(0, (float)($body['price'] ?? 0)));
        $ad->setTimeLeft($userToken->getDurationMinutes());
        $ad->setActive(1);

        $this->em->persist($ad);
        $this->em->flush();

        $this->notifier->tokenUsed($me, $ad->getId(), $ad->getTitle(), $token->getName(), $token->getAdDuration());

        return $this->ok(['ad_id' => $ad->getId()], 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        $ad = $this->adRepo->find($id);

        if (!$ad) return $this->error('Ad not found.', 404);
        if ($ad->getUser()->getId() !== $me->getId()) return $this->error('Forbidden.', 403);

        $body = $this->body($request);

        if (isset($body['title'])) {
            $title = trim($body['title']);
            if (strlen($title) < 3 || strlen($title) > 200) return $this->error('Title must be 3–200 characters.');
            $ad->setTitle($title);
        }
        if (array_key_exists('description', $body)) {
            $ad->setDescription(trim($body['description']) ?: null);
        }
        if (isset($body['price'])) {
            $ad->setPrice((string)max(0, (float)$body['price']));
        }
        if (isset($body['hidden'])) {
            $ad->setHiddenByAdvertiser($body['hidden'] ? 1 : 0);
        }
        if (isset($body['region_name'])) {
            $ad->setRegionName(trim($body['region_name']) ?: null);
        }

        $this->em->persist($ad);
        $this->em->flush();

        return $this->ok(['ad' => $this->serializeDetail($ad)]);
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        $ad = $this->adRepo->find($id);

        if (!$ad) return $this->error('Ad not found.', 404);
        if ($ad->getUser()->getId() !== $me->getId()) return $this->error('Forbidden.', 403);

        // Delete associated media files
        foreach ($this->mediaRepo->findByAd($id) as $m) {
            $this->uploadService->deleteAdMedia($m->getFileName(), $m->getFileType());
        }

        $this->em->remove($ad);
        $this->em->flush();

        return $this->ok(['message' => 'Ad deleted.']);
    }

    // ── Media ─────────────────────────────────────────────────────────

    #[Route('/{id}/media', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadMedia(int $id, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        $ad = $this->adRepo->find($id);

        if (!$ad) return $this->error('Ad not found.', 404);
        if ($ad->getUser()->getId() !== $me->getId()) return $this->error('Forbidden.', 403);

        $file     = $request->files->get('file');
        if (!$file) return $this->error('No file uploaded.');

        $maxMedia = $ad->getAdToken()->getMaxMedia();
        $current  = $this->mediaRepo->countByAd($id);
        if ($current >= $maxMedia) return $this->error("Maximum $maxMedia media files allowed for this ad.");

        $result = $this->uploadService->uploadAdMedia($file);
        if (isset($result['error'])) return $this->error($result['error']);

        $media = new Media();
        $media->setAdvertisement($ad);
        $media->setCreationDate(new \DateTime());
        $media->setFileName($result['file_name']);
        $media->setFileType($result['file_type']);
        $media->setPosition($current + 1);

        $this->em->persist($media);
        $this->em->flush();

        return $this->ok(['media_id' => $media->getId(), 'file_name' => $media->getFileName(), 'file_type' => $media->getFileType()], 201);
    }

    #[Route('/{id}/media/{mediaId}', methods: ['DELETE'], requirements: ['id' => '\d+', 'mediaId' => '\d+'])]
    public function deleteMedia(int $id, int $mediaId): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me    = $this->getUser();
        $ad    = $this->adRepo->find($id);
        $media = $this->mediaRepo->find($mediaId);

        if (!$ad || !$media) return $this->error('Not found.', 404);
        if ($ad->getUser()->getId() !== $me->getId()) return $this->error('Forbidden.', 403);
        if ($media->getAdvertisement()->getId() !== $id) return $this->error('Media does not belong to this ad.', 400);

        $this->uploadService->deleteAdMedia($media->getFileName(), $media->getFileType());
        $this->em->remove($media);
        $this->em->flush();

        return $this->ok();
    }

    // ── Serializers ───────────────────────────────────────────────────

    private function serializeSummary(Advertisement $ad): array
    {
        $media = $this->mediaRepo->findByAd($ad->getId());
        return [
            'ad_id'       => $ad->getId(),
            'title'       => $ad->getTitle(),
            'price'       => $ad->getPrice(),
            'country'     => ['country_id' => $ad->getCountry()->getId(), 'name' => $ad->getCountry()->getName()],
            'region_name' => $ad->getRegionName(),
            'subcategory' => ['subcategory_id' => $ad->getSubcategory()->getId(), 'name' => $ad->getSubcategory()->getName()],
            'category'    => ['category_id' => $ad->getSubcategory()->getCategory()->getId(), 'name' => $ad->getSubcategory()->getCategory()->getName()],
            'user'        => ['user_id' => $ad->getUser()->getId(), 'username' => $ad->getUser()->getUsername()],
            'thumbnail'   => !empty($media) ? $media[0]->getFileName() : null,
            'creation_date' => $ad->getCreationDate()?->format('Y-m-d H:i:s'),
        ];
    }

    private function serializeDetail(Advertisement $ad): array
    {
        $media = $this->mediaRepo->findByAd($ad->getId());
        return [
            'ad_id'              => $ad->getId(),
            'title'              => $ad->getTitle(),
            'description'        => $ad->getDescription(),
            'price'              => $ad->getPrice(),
            'country'            => ['country_id' => $ad->getCountry()->getId(), 'name' => $ad->getCountry()->getName()],
            'region_name'        => $ad->getRegionName(),
            'subcategory'        => ['subcategory_id' => $ad->getSubcategory()->getId(), 'name' => $ad->getSubcategory()->getName()],
            'category'           => ['category_id' => $ad->getSubcategory()->getCategory()->getId(), 'name' => $ad->getSubcategory()->getCategory()->getName()],
            'user'               => ['user_id' => $ad->getUser()->getId(), 'username' => $ad->getUser()->getUsername(), 'profile_picture' => $ad->getUser()->getProfilePicture()],
            'active'             => $ad->getActive(),
            'ban_status'         => $ad->getBanStatus(),
            'hidden_by_advertiser' => $ad->getHiddenByAdvertiser(),
            'time_left'          => $ad->getTimeLeft(),
            'media'              => array_map(fn($m) => [
                'media_id'  => $m->getId(),
                'file_name' => $m->getFileName(),
                'file_type' => $m->getFileType(),
                'position'  => $m->getPosition(),
            ], $media),
            'creation_date'      => $ad->getCreationDate()?->format('Y-m-d H:i:s'),
        ];
    }
}
