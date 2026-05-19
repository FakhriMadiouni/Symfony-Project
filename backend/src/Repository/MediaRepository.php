<?php namespace App\Repository;
use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class MediaRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Media::class); }
    public function findByAd(int $adId): array {
        return $this->createQueryBuilder('m')->where('m.advertisement = :a')
            ->setParameter('a',$adId)->orderBy('m.position','ASC')->getQuery()->getResult();
    }
    public function countByAd(int $adId): int {
        return (int)$this->createQueryBuilder('m')->select('COUNT(m.id)')
            ->where('m.advertisement = :a')->setParameter('a',$adId)->getQuery()->getSingleScalarResult();
    }
}
