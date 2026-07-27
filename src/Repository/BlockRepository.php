<?php

namespace App\Repository;

use App\Entity\Block;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Block::class);
    }

    public function findByTopic(int $topicId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.topic = :topicId')
            ->setParameter('topicId', $topicId)
            ->orderBy('b.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}