<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\String\Slugger\SluggerInterface;

trait SluggerTrait
{
    protected SluggerInterface $slugger;

    /**
     * @required
     */
    public function setSlugger(SluggerInterface $slugger): void
    {
        $this->slugger = $slugger;
    }
}
