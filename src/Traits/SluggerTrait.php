<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait SluggerTrait
{
    protected SluggerInterface $slugger;

    #[Required]
    public function setSlugger(SluggerInterface $slugger): void
    {
        $this->slugger = $slugger;
    }
}
