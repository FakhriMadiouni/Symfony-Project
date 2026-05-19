<?php namespace App\Repository;
use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ConversationRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Conversation::class); }
    public function findForUser(int $userId, int $limit=20, int $offset=0): array {
        return $this->createQueryBuilder('c')
            ->where('c.sender = :u OR c.advertiser = :u')
            ->setParameter('u',$userId)
            ->orderBy('c.lastMessageDate','DESC')
            ->setMaxResults($limit)->setFirstResult($offset)
            ->getQuery()->getResult();
    }
    public function findBySenderAndAd(int $senderId, int $adId): ?Conversation {
        return $this->findOneBy(['sender'=>$senderId,'advertisement'=>$adId]);
    }
}
