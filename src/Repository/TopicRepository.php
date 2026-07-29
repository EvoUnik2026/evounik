<?php

namespace App\Repository;

use App\Entity\Topic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Topic::class);
    }

    public function findBySlug(string $slug): ?Topic
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findHomepageTopics(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.showOnHomepage = :visible')
            ->setParameter('visible', true)
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByCreatedAt(): array
    {
        return $this->findBy([], ['position' => 'ASC', 'createdAt' => 'DESC']);
    }
}