<?php

namespace App\Controller\Api;

use App\Repository\AdReviewRepository;
use App\Repository\AdvertisementRepository;
use App\Repository\FollowRepository;
use App\Repository\UserAdTokenRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\HonorService;
use App\Service\NotifierService;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly UserRepository          $userRepo,
        private readonly FollowRepository        $followRepo,
        private readonly AdvertisementRepository $adRepo,
        private readonly AdReviewRepository      $reviewRepo,
        private readonly UserAdTokenRepository   $userTokenRepo,
        private readonly UploadService           $uploadService,
        private readonly AuthService             $authService,
        private readonly HonorService            $honorService,
        private readonly NotifierService         $notifier
    ) {}

    // ── Profile ───────────────────────────────────────────────────────

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function profile(int $id): JsonResponse
    {
        $user = $this->userRepo->find($id);
        if (!$user) return $this->error('User not found.', 404);

        $reviewStats = $this->reviewRepo->getStatsByUser($id);
        $honorRank   = $this->honorService->getCurrentRank($user);

        // Determine if the authenticated viewer is already following this user
        $viewer      = $this->getUser();
        $isFollowing = $viewer && $this->followRepo->isFollowing($viewer->getId(), $id);

        return $this->ok([
            'user' => [
                'user_id'          => $user->getId(),
                'username'         => $user->getUsername(),
                'profile_picture'  => $user->getProfilePicture(),
                'biography'        => $user->getBiography(),
                'honor_points'     => $user->getHonorPoints(),
                'honor_rank'       => $honorRank ? ['name' => $honorRank->getName(), 'color' => $honorRank->getColor()] : null,
                'is_staff'         => $user->isStaff(),
                'staff_division'   => $user->getStaffDivisionRank()?->getDivision()->getName(),
                'staff_rank'       => $user->getStaffDivisionRank()?->getName(),
                'reg_date'         => $user->getRegDate()?->format('Y-m-d H:i:s'),
                'followers'        => $this->followRepo->countFollowers($id),
                'following'        => $this->followRepo->countFollowing($id),
                'reviews'          => $reviewStats,
                'is_following'     => $isFollowing,
            ],
        ]);
    }

    #[Route('/me/update', methods: ['POST', 'PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $body = $this->body($request);

        if (isset($body['username'])) {
            $un = trim($body['username']);
            if (strlen($un) < 3 || strlen($un) > 50) return $this->error('Username must be 3–50 characters.');
            $me->setUsername($un);
        }
        if (array_key_exists('biography', $body)) {
            $bio = trim($body['biography'] ?? '');
            $me->setBiography($bio ?: null);
        }

        $this->em->persist($me);
        $this->em->flush();

        return $this->ok(['user' => $this->authService->serializeUser($me)]);
    }

    #[Route('/me/avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $file = $request->files->get('avatar');

        if (!$file) return $this->error('No file uploaded.');

        $result = $this->uploadService->uploadAvatar($file, $me->getProfilePicture());
        if (isset($result['error'])) return $this->error($result['error']);

        $me->setProfilePicture($result['file_name']);
        $this->em->persist($me);
        $this->em->flush();

        return $this->ok(['file_name' => $result['file_name']]);
    }

    #[Route('/me/avatar', methods: ['DELETE'])]
    public function deleteAvatar(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        if ($me->getProfilePicture()) {
            $this->uploadService->deleteAvatar($me->getProfilePicture());
            $me->setProfilePicture(null);
            $this->em->persist($me);
            $this->em->flush();
        }
        return $this->ok();
    }

    // ── Follow ────────────────────────────────────────────────────────

    #[Route('/{id}/follow', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function follow(int $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $target = $this->userRepo->find($id);

        if (!$target) return $this->error('User not found.', 404);
        if ($me->getId() === $id) return $this->error('Cannot follow yourself.');
        if ($this->followRepo->isFollowing($me->getId(), $id)) return $this->error('Already following.');

        $follow = new \App\Entity\Follow();
        $follow->setFollower($me);
        $follow->setFollowedUser($target);
        $follow->setDate(new \DateTime());
        $this->em->persist($follow);
        $this->em->flush();

        $this->notifier->userFollowed($target, $me->getUsername(), $me->getId());

        return $this->ok(['following' => true]);
    }

    #[Route('/{id}/follow', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function unfollow(int $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me  = $this->getUser();
        $row = $this->followRepo->findOneBy(['follower' => $me->getId(), 'followedUser' => $id]);

        if (!$row) return $this->error('Not following this user.');

        $this->em->remove($row);
        $this->em->flush();

        return $this->ok(['following' => false]);
    }

    // ── Store / Reviews ───────────────────────────────────────────────

    #[Route('/{id}/ads', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function userAds(int $id, Request $request): JsonResponse
    {
        $limit  = min((int)($request->query->get('limit', 50)), 100);
        $offset = (int)$request->query->get('offset', 0);

        $ads = $this->adRepo->findByUser($id, false, $limit, $offset);

        return $this->ok([
            'ads' => array_map([$this, 'serializeAd'], $ads),
        ]);
    }

    // ── Re-advertise (reactivate an expired ad with a token) ──────────

    #[Route('/{id}/readvertise', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function readvertise(int $id, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $body = $this->body($request);

        if ($me->getAdBanStatus() === 1) return $this->error('You are banned from posting advertisements.');
        if ($me->getBanStatus()   === 1) return $this->error('Your account is currently banned.');

        $ad = $this->adRepo->find($id);
        if (!$ad) return $this->error('Ad not found.', 404);
        if ($ad->getUser()->getId() !== $me->getId()) return $this->error('Forbidden.', 403);
        if ($ad->getBanStatus() === 1) return $this->error('Banned ads cannot be re-advertised.');
        if ($ad->getActive() === 1)    return $this->error('This ad is still active.');

        $userTokenId = (int)($body['user_token_id'] ?? 0);
        $token = $this->userTokenRepo->find($userTokenId);
        if (!$token || $token->getUser()->getId() !== $me->getId()) {
            return $this->error('Token not found.', 404);
        }
        if (!$token->getActive()) {
            return $this->error('This token has already been used.');
        }

        // Consume the token and reactivate the ad
        $token->setActive(0);
        $ad->setAdToken($token);
        $ad->setActive(1);
        $ad->setBanStatus(0);
        $ad->setHiddenByAdvertiser(0);
        $ad->setCreationDate(new \DateTime());
        $ad->setTimeLeft($token->getAdDuration() * 24 * 60); // minutes

        $this->em->persist($token);
        $this->em->persist($ad);
        $this->em->flush();

        $this->notifier->tokenUsed($me, $ad->getId(), $ad->getTitle(), $token->getName(), $token->getAdDuration());

        return $this->ok(['message' => 'Ad re-activated successfully.', 'ad_id' => $ad->getId()]);
    }

    #[Route('/{id}/reviews', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function userReviews(int $id, Request $request): JsonResponse
    {
        $limit  = min((int)($request->query->get('limit', 20)), 50);
        $offset = (int)$request->query->get('offset', 0);
        $rows   = $this->reviewRepo->findByRatedUser($id, $limit, $offset);

        return $this->ok([
            'reviews' => array_map([$this, 'serializeReview'], $rows),
            'stats'   => $this->reviewRepo->getStatsByUser($id),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function serializeAd(\App\Entity\Advertisement $ad): array
    {
        // Grab first media for thumbnail
        $mediaRepo = $this->em->getRepository(\App\Entity\Media::class);
        $media     = $mediaRepo->findBy(['advertisement' => $ad->getId()], ['position' => 'ASC'], 1);
        return [
            'ad_id'                => $ad->getId(),
            'title'                => $ad->getTitle(),
            'price'                => $ad->getPrice(),
            'active'               => $ad->getActive(),
            'ban_status'           => $ad->getBanStatus(),
            'hidden_by_advertiser' => $ad->getHiddenByAdvertiser(),
            'time_left'            => $ad->getTimeLeft(),
            'thumbnail'            => !empty($media) ? $media[0]->getFileName() : null,
            'subcategory'          => ['subcategory_id' => $ad->getSubcategory()->getId(), 'name' => $ad->getSubcategory()->getName()],
            'creation_date'        => $ad->getCreationDate()?->format('Y-m-d H:i:s'),
        ];
    }

    private function serializeReview(\App\Entity\AdReview $r): array
    {
        return [
            'ad_review_id' => $r->getId(),
            'rate'         => $r->getRate(),
            'score'        => $r->getScore(),
            'comment'      => $r->getComment(),
            'date'         => $r->getDate()?->format('Y-m-d H:i:s'),
            'anonymous'    => $r->getAnonymousStatus() === 1,
            'rater'        => $r->getAnonymousStatus() === 1 ? null : [
                'user_id'  => $r->getRater()->getId(),
                'username' => $r->getRater()->getUsername(),
            ],
        ];
    }
}
