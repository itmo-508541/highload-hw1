<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Config;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Config>
 */
class ConfigRepository extends ServiceEntityRepository implements ConfigRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    public function getValue(array $params, string $suffix, mixed $default = null): ?string
    {
        $key = $this->key($params, $suffix);
        $record = $this->find($key);

        return null === $record ? $default : $record->getValue();
    }

    public function setValue(array $params, string $suffix, mixed $value): void
    {
        $key = $this->key($params, $suffix);
        $record = $this->find($key);
        if (null === $record) {
            $record = new Config();
            $record->setId($key);
        }

        $record->setValue((string) $value);

        $em = $this->getEntityManager();
        $em->persist($record);
        $em->flush();
    }

    private function key(array $params, string $suffix): string
    {
        return implode('.', $params).'.'.$suffix;
    }
}
