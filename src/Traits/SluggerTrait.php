<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\String\Slugger\SluggerInterface;

trait SluggerTrait
{
    protected SluggerInterface $slugger;

    #[Required]
    public function setSlugger(SluggerInterface $slugger): void
    {
        $this->slugger = $slugger;
    }
}
