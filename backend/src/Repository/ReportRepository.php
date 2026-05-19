<?php namespace App\Repository;
use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ReportRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Report::class); }
    public function findOpenReports(int $limit=20, int $offset=0): array {
        return $this->createQueryBuilder('r')->where('r.lockStatus = 0')
            ->orderBy('r.date','ASC')->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
    }
}
