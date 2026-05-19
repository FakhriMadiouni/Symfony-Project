<?php

namespace App\Service;

use App\Entity\EmailVerification;
use App\Entity\User;
use App\Entity\UserSession;
use App\Repository\EmailVerificationRepository;
use App\Repository\UserRepository;
use App\Repository\UserSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserRepository              $userRepo,
        private readonly UserSessionRepository       $sessionRepo,
        private readonly EmailVerificationRepository $verificationRepo,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EmailService                $emailService,
        private readonly RequestStack                $requestStack,
        private readonly int                         $sessionDuration,
        private readonly int                         $emailVerifyExpiry,
        private readonly int                         $passwordResetExpiry
    ) {}

    // ── Registration ──────────────────────────────────────────────────

    public function register(string $username, string $email, string $password): array
    {
        if ($this->userRepo->findOneBy(['email' => $email])) {
            return ['error' => 'Email already registered.'];
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setRegDate(new \DateTime());
        $user->setRegIp($this->getClientIp());
        $user->setLockStatus(1); // locked until email verified

        $this->em->persist($user);
        $this->em->flush();

        $this->sendVerificationCode($user, 'registration');

        return ['success' => true, 'user_id' => $user->getId()];
    }

    // ── Email verification ────────────────────────────────────────────

    public function sendVerificationCode(User $user, string $type = 'registration'): bool
    {
        // Invalidate any prior pending codes of same type
        $this->verificationRepo->invalidatePending($user->getId(), $type);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = new \DateTime();
        $expiry->modify('+' . $this->emailVerifyExpiry . ' seconds');

        $ev = new EmailVerification();
        $ev->setUser($user);
        $ev->setType($type);
        $ev->setCode($code);
        $ev->setExpiryDate($expiry);
        $ev->setVerified(0);

        $this->em->persist($ev);
        $this->em->flush();

        return $this->emailService->sendVerificationCode($user->getEmail(), $user->getUsername(), $code, $type);
    }

    public function verifyCode(User $user, string $code, string $type = 'registration'): array
    {
        $ev = $this->verificationRepo->findPendingCode($user->getId(), $code, $type);

        if (!$ev) {
            return ['error' => 'Invalid or expired code.'];
        }

        if ($ev->isExpired()) {
            return ['error' => 'Verification code has expired. Please request a new one.'];
        }

        $ev->setVerified(1);

        if ($type === 'registration') {
            $user->setLockStatus(0);
            $this->em->persist($user);
            $this->emailService->sendWelcome($user->getEmail(), $user->getUsername());
        }

        $this->em->persist($ev);
        $this->em->flush();

        return ['success' => true];
    }

    // ── Password reset ────────────────────────────────────────────────

    public function requestPasswordReset(string $email): array
    {
        $user = $this->userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            // Return success anyway to avoid email enumeration
            return ['success' => true];
        }

        $this->verificationRepo->invalidatePending($user->getId(), 'password_reset');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = new \DateTime();
        $expiry->modify('+' . $this->passwordResetExpiry . ' seconds');

        $ev = new EmailVerification();
        $ev->setUser($user);
        $ev->setType('password_reset');
        $ev->setCode($code);
        $ev->setExpiryDate($expiry);
        $ev->setVerified(0);

        $this->em->persist($ev);
        $this->em->flush();

        $this->emailService->sendVerificationCode($user->getEmail(), $user->getUsername(), $code, 'password_reset');

        return ['success' => true];
    }

    public function resetPassword(string $email, string $code, string $newPassword): array
    {
        $user = $this->userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            return ['error' => 'Invalid request.'];
        }

        $ev = $this->verificationRepo->findPendingCode($user->getId(), $code, 'password_reset');
        if (!$ev || $ev->isExpired()) {
            return ['error' => 'Invalid or expired code.'];
        }

        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        $ev->setVerified(1);

        // Invalidate all sessions
        $this->sessionRepo->deleteAllForUser($user->getId());

        $this->em->persist($user);
        $this->em->persist($ev);
        $this->em->flush();

        return ['success' => true];
    }

    // ── Login / Session ───────────────────────────────────────────────

    public function login(string $email, string $password): array
    {
        $user = $this->userRepo->findOneBy(['email' => $email]);

        if (!$user || !$this->hasher->isPasswordValid($user, $password)) {
            return ['error' => 'Invalid email or password.'];
        }

        if ($user->getLockStatus() === 1) {
            return ['error' => 'Please verify your email before logging in.', 'needs_verification' => true];
        }

        if ($user->getBanStatus() === 1 && $user->getBanTimeLeft() === -1) {
            return ['error' => 'Your account has been permanently banned. Contact support to appeal.'];
        }

        // Update login metadata
        $user->setLastLoginDate(new \DateTime());
        $user->setLastLoginIp($this->getClientIp());
        $this->em->persist($user);

        $token   = $this->generateSecureToken();
        $expiry  = new \DateTime();
        $expiry->modify('+' . $this->sessionDuration . ' seconds');

        $session = new UserSession();
        $session->setUser($user);
        $session->setToken($token);
        $session->setExpiryDate($expiry);
        $session->setIp($this->getClientIp());
        $session->setUserAgent($this->requestStack->getCurrentRequest()?->headers->get('User-Agent'));

        $this->em->persist($session);
        $this->em->flush();

        return [
            'success'    => true,
            'token'      => $token,
            'expires_at' => $expiry->format('Y-m-d H:i:s'),
            'user'       => $this->serializeUser($user),
        ];
    }

    public function logout(string $token): void
    {
        $session = $this->sessionRepo->findValidByToken($token);
        if ($session) {
            $this->em->remove($session);
            $this->em->flush();
        }
    }

    public function validateToken(string $token): ?User
    {
        $session = $this->sessionRepo->findValidByToken($token);
        return $session?->getUser();
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function serializeUser(User $user): array
    {
        return [
            'user_id'            => $user->getId(),
            'username'           => $user->getUsername(),
            'email'              => $user->getEmail(),
            'profile_picture'    => $user->getProfilePicture(),
            'biography'          => $user->getBiography(),
            'honor_points'       => $user->getHonorPoints(),
            'ban_status'         => $user->getBanStatus(),
            'ban_time_left'      => $user->getBanTimeLeft(),
            'ad_ban_status'      => $user->getAdBanStatus(),
            'ad_ban_time_left'   => $user->getAdBanTimeLeft(),
            'mute_status'        => $user->getMuteStatus(),
            'mute_time_left'     => $user->getMuteTimeLeft(),
            'is_staff'           => $user->isStaff(),
            'staff_division'     => $user->getStaffDivisionRank()?->getDivision()->getName(),
            'staff_rank'         => $user->getStaffDivisionRank()?->getName(),
            'reg_date'           => $user->getRegDate()?->format('Y-m-d H:i:s'),
            'last_login_date'    => $user->getLastLoginDate()?->format('Y-m-d H:i:s'),
        ];
    }

    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function getClientIp(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getClientIp() ?? '0.0.0.0';
    }
}
