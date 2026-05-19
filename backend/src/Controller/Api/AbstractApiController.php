<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiController extends AbstractController
{
    protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        return parent::json($data, $status, $headers, $context);
    }

    protected function ok(mixed $data = [], int $status = 200): JsonResponse
    {
        return $this->json(is_array($data) ? array_merge(['success' => true], $data) : $data, $status);
    }

    protected function error(string $message, int $status = 400): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }

    protected function body(Request $request): array
    {
        $content = $request->getContent();
        if (!$content) {
            return $request->request->all();
        }
        return json_decode($content, true) ?? $request->request->all();
    }

    protected function bearerToken(Request $request): ?string
    {
        $auth = $request->headers->get('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}
