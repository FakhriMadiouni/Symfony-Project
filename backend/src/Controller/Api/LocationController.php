<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use App\Repository\CountryRepository;
use App\Repository\SubcategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class LocationController extends AbstractApiController
{
    public function __construct(
        private readonly CountryRepository     $countryRepo,
        private readonly CategoryRepository    $categoryRepo,
        private readonly SubcategoryRepository $subcategoryRepo
    ) {}

    #[Route('/locations', methods: ['GET'])]
    public function countries(): JsonResponse
    {
        $countries = $this->countryRepo->findAllActive();
        return $this->ok(['countries' => array_map(fn($c) => [
            'country_id' => $c->getId(),
            'name'       => $c->getName(),
            'iso_code'   => $c->getIsoCode(),
        ], $countries)]);
    }

    #[Route('/categories', methods: ['GET'])]
    public function categories(Request $request): JsonResponse
    {
        $catId = $request->query->get('category_id');

        if ($catId) {
            $subs = $this->subcategoryRepo->findByCategory((int)$catId);
            return $this->ok(['subcategories' => array_map(fn($s) => [
                'subcategory_id' => $s->getId(),
                'name'           => $s->getName(),
                'category_id'    => $s->getCategory()->getId(),
            ], $subs)]);
        }

        $cats = $this->categoryRepo->findAllActive();
        return $this->ok(['categories' => array_map(fn($c) => [
            'category_id' => $c->getId(),
            'name'        => $c->getName(),
            'logo'        => $c->getLockStatus() === 0 ? $c->getLogoFileName() : null,
        ], $cats)]);
    }
}
