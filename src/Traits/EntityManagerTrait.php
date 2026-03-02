<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

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
