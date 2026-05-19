<?php namespace App\Repository;
use App\Entity\AdReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class AdReviewRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, AdReview::class); }
    public function findByRatedUser(int $userId, int $limit=20, int $offset=0): array {
        return $this->createQueryBuilder('r')->where('r.ratedUser = :u AND r.lockStatus = 0')
            ->setParameter('u',$userId)->orderBy('r.date','DESC')
            ->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
    }
    public function existsForConversationAndRater(int $convId, int $raterId): bool {
        return (bool)$this->findOneBy(['conversation'=>$convId,'rater'=>$raterId]);
    }
    public function getStatsByUser(int $userId): array {
        $rows = $this->createQueryBuilder('r')
            ->select('r.rate, COUNT(r.id) as cnt, SUM(r.score) as total')
            ->where('r.ratedUser = :u AND r.lockStatus = 0')
            ->setParameter('u',$userId)->groupBy('r.rate')
            ->getQuery()->getResult();
        $stats=['positive'=>0,'negative'=>0,'score'=>0];
        foreach($rows as $row){
            $stats[$row['rate']]=(int)$row['cnt'];
            $stats['score']+=(int)$row['total'];
        }
        return $stats;
    }
}
