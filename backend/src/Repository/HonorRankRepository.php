<?php namespace App\Repository;
use App\Entity\HonorRank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class HonorRankRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, HonorRank::class); }
    /** @return HonorRank[] */
    public function findAllOrdered(): array {
        return $this->createQueryBuilder('h')->orderBy('h.minScore','ASC')->getQuery()->getResult();
    }
}
