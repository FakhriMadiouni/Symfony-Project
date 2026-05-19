<?php namespace App\Repository;
use App\Entity\SupportMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class SupportMessageRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, SupportMessage::class); }
    public function findByConversation(int $convId): array {
        return $this->createQueryBuilder('m')->where('m.supportConversation = :c')
            ->setParameter('c',$convId)->orderBy('m.sentDate','ASC')->getQuery()->getResult();
    }
}
