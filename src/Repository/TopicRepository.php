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

    public function findAllOrderedByCreatedAt(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }
}