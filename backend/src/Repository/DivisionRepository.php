<?php namespace App\Repository;
use App\Entity\Division;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class DivisionRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Division::class); }
    public function findAllActive(): array {
        return $this->createQueryBuilder('d')->where('d.lockStatus = 0')->orderBy('d.name','ASC')->getQuery()->getResult();
    }
}
