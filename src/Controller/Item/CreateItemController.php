<?php

declare(strict_types=1);

namespace App\Controller\Item;

use App\Attribute\CollectMetrics;
use App\Grafana\RandomError;
use App\Model\Rest\ItemId;
use App\Model\Rest\ItemValue;
use App\Repository\ItemRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateItemController
{
    public function __construct(
        private ItemRepository $items,
        private RandomError $randomError,
    ) {
    }

    #[CollectMetrics(name: 'api_items_create')]
    #[Route(path: '/items', name: 'items_create', methods: ['POST'])]
    #[Rest\View(statusCode: Response::HTTP_CREATED)]
    public function __invoke(ItemValue $item): ItemId
    {
        $this->randomError->run();

        return new ItemId($this->items->create($item->getValue()));
    }
}
