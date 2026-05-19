<?php namespace App\Repository;
use App\Entity\DivisionRank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class DivisionRankRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, DivisionRank::class); }
    public function findByDivision(int $divId): array {
        return $this->createQueryBuilder('r')->where('r.division = :d AND r.lockStatus = 0')
            ->setParameter('d',$divId)->orderBy('r.rank','ASC')->getQuery()->getResult();
    }
}
