<?php namespace App\Repository;
use App\Entity\Subcategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class SubcategoryRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Subcategory::class); }
    public function findByCategory(int $catId): array {
        return $this->createQueryBuilder('s')->where('s.category = :c AND s.lockStatus = 0')
            ->setParameter('c',$catId)->orderBy('s.name','ASC')->getQuery()->getResult();
    }
}
