<?php

declare(strict_types=1);

namespace App\Controller\Item;

use App\Model\Rest\ItemId;
use App\Model\Rest\ItemValue;
use App\Repository\ItemRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateItemController
{
    public function __construct(
        private ItemRepository $items,
    ) {
    }

    #[Route(path: '/items/{id}', name: 'items_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function __invoke(int $id, ItemValue $item): ItemId
    {
        if (!$this->items->replace($id, $item)) {
            throw new NotFoundHttpException(\sprintf('Item %d not found.', $id));
        }

        return new ItemId($id);
    }
}
