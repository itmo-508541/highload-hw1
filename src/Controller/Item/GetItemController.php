<?php

declare(strict_types=1);

namespace App\Controller\Item;

use App\Model\Rest\ItemValue;
use App\Repository\ItemRepository;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetItemController
{
    public function __construct(
        private ItemRepository $items,
    ) {
    }

    #[Route(path: '/items/{id}', name: 'items_get', requirements: ['id' => ItemRoute::ID_REQUIREMENT], methods: ['GET'])]
    public function __invoke(int $id): ItemValue
    {
        return new ItemValue($this->items->find($id) ?? throw new ItemNotFoundHttpException($id));
    }
}
