<?php namespace App\Repository;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class MessageRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Message::class); }
    public function findByConversation(int $convId, int $limit=50, int $offset=0): array {
        return $this->createQueryBuilder('m')->where('m.conversation = :c')
            ->setParameter('c',$convId)->orderBy('m.timestamp','ASC')
            ->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
    }
    public function countUnread(int $convId, int $forUserId): int {
        return (int)$this->createQueryBuilder('m')->select('COUNT(m.id)')
            ->where('m.conversation = :c AND m.readStatus = 0 AND m.sender != :u')
            ->setParameter('c',$convId)->setParameter('u',$forUserId)->getQuery()->getSingleScalarResult();
    }
    public function markConversationRead(int $convId, int $forUserId): void {
        $this->createQueryBuilder('m')->update()
            ->set('m.readStatus',1)
            ->where('m.conversation = :c AND m.sender != :u AND m.readStatus = 0')
            ->setParameter('c',$convId)->setParameter('u',$forUserId)->getQuery()->execute();
    }
}
