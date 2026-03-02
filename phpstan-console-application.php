<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require __DIR__.'/tests/bootstrap.php';

$kernel = new Kernel('test', false);

return new Application($kernel);
