<?php namespace App\Repository;
use App\Entity\SupportConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class SupportConversationRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, SupportConversation::class); }
    public function findForUser(int $userId): array {
        return $this->createQueryBuilder('c')->where('c.user = :u')
            ->setParameter('u',$userId)->orderBy('c.openedDate','DESC')->getQuery()->getResult();
    }
    public function findOpen(int $limit=20, int $offset=0): array {
        return $this->createQueryBuilder('c')->where("c.status = 'open'")
            ->orderBy('c.lastReplyDate','ASC')->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
    }
}
