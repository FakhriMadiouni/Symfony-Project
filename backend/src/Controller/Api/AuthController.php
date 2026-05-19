<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractApiController
{
    public function __construct(
        private readonly AuthService    $authService,
        private readonly UserRepository $userRepo
    ) {}

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $body = $this->body($request);
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (strlen($username) < 3 || strlen($username) > 50) {
            return $this->error('Username must be 3–50 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Invalid email address.');
        }
        if (strlen($password) < 8) {
            return $this->error('Password must be at least 8 characters.');
        }

        $result = $this->authService->register($username, $email, $password);

        return isset($result['error'])
            ? $this->error($result['error'])
            : $this->ok(['message' => 'Registration successful. Please check your email for a verification code.', 'user_id' => $result['user_id']]);
    }

    #[Route('/send-verification', methods: ['POST'])]
    public function sendVerification(Request $request): JsonResponse
    {
        $body = $this->body($request);
        $userId = (int)($body['user_id'] ?? 0);
        $type   = $body['type'] ?? 'registration';

        $user = $this->userRepo->find($userId);
        if (!$user) {
            return $this->error('User not found.', 404);
        }

        $this->authService->sendVerificationCode($user, $type);
        return $this->ok(['message' => 'Verification code sent.']);
    }

    #[Route('/verify-code', methods: ['POST'])]
    public function verifyCode(Request $request): JsonResponse
    {
        $body   = $this->body($request);
        $userId = (int)($body['user_id'] ?? 0);
        $code   = trim($body['code'] ?? '');
        $type   = $body['type'] ?? 'registration';

        $user = $this->userRepo->find($userId);
        if (!$user) {
            return $this->error('User not found.', 404);
        }

        $result = $this->authService->verifyCode($user, $code, $type);
        return isset($result['error'])
            ? $this->error($result['error'])
            : $this->ok(['message' => 'Verification successful.']);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $body   = $this->body($request);
        $email  = trim($body['email'] ?? '');
        $pass   = $body['password'] ?? '';

        $result = $this->authService->login($email, $pass);
        return isset($result['error'])
            ? $this->error($result['error'], $result['needs_verification'] ?? false ? 403 : 401)
            : $this->ok($result);
    }

    #[Route('/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $token = $this->bearerToken($request);
        if ($token) {
            $this->authService->logout($token);
        }
        return $this->ok(['message' => 'Logged out.']);
    }

    #[Route('/forgot-password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $body  = $this->body($request);
        $email = trim($body['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Invalid email.');
        }

        $this->authService->requestPasswordReset($email);
        // Always return success to prevent email enumeration
        return $this->ok(['message' => 'If that email exists, a reset code has been sent.']);
    }

    #[Route('/reset-password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $body     = $this->body($request);
        $email    = trim($body['email'] ?? '');
        $code     = trim($body['code'] ?? '');
        $password = $body['password'] ?? '';

        if (strlen($password) < 8) {
            return $this->error('Password must be at least 8 characters.');
        }

        $result = $this->authService->resetPassword($email, $code, $password);
        return isset($result['error'])
            ? $this->error($result['error'])
            : $this->ok(['message' => 'Password reset successful. Please log in.']);
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        return $this->ok(['user' => $this->authService->serializeUser($user)]);
    }

    #[Route('/send-change-password-code', methods: ['POST'])]
    public function sendChangePasswordCode(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $this->authService->sendVerificationCode($user, 'password_reset');
        return $this->ok(['message' => 'A password change code has been sent to your email.']);
    }

    #[Route('/change-password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $body = $this->body($request);
        $code        = trim($body['code'] ?? '');
        $newPassword = $body['new_password'] ?? '';

        if (strlen($newPassword) < 8) {
            return $this->error('New password must be at least 8 characters.');
        }

        $result = $this->authService->resetPassword($user->getEmail(), $code, $newPassword);
        return isset($result['error'])
            ? $this->error($result['error'])
            : $this->ok(['message' => 'Password changed successfully.']);
    }
}
