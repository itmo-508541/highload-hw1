<?php

declare(strict_types=1);

namespace App\Controller\Item;

use App\Attribute\CollectMetrics;
use App\Model\Rest\ItemId;
use App\Model\Rest\ItemValue;
use App\Repository\ItemRepository;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateItemController
{
    public function __construct(
        private ItemRepository $items,
    ) {
    }

    #[CollectMetrics(name: 'items_update')]
    #[Route(path: '/items/{id}', name: 'items_update', requirements: ['id' => ItemRoute::ID_REQUIREMENT], methods: ['PUT'])]
    public function __invoke(int $id, ItemValue $item): ItemId
    {
        if (!$this->items->replace($id, $item->getValue())) {
            throw new ItemNotFoundHttpException($id);
        }

        return new ItemId($id);
    }
}
