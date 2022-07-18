<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entity\Config::class);
    }

    public function save(Entity\Config $config): void
    {
        $this->_em->persist($config);
        $this->_em->flush();
    }
}
