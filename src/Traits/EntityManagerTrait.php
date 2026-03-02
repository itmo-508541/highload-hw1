<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Contracts\Service\Attribute\Required;
use Doctrine\ORM\EntityManagerInterface;

trait EntityManagerTrait
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    #[Required]
    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
    }
}
