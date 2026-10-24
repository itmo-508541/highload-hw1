<?php

declare(strict_types=1);

namespace App\Controller\Item;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ItemNotFoundHttpException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(\sprintf('Item %d not found.', $id));
    }
}
