<?php namespace App\Repository;
use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class CountryRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Country::class); }
    public function findAllActive(): array {
        return $this->createQueryBuilder('c')->where('c.lockStatus = 0')->orderBy('c.name','ASC')->getQuery()->getResult();
    }
}
