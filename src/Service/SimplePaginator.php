<?php

namespace App\Service;

use Knp\Component\Pager\PaginatorInterface;

class SimplePaginator
{
    public function __construct(private readonly ?PaginatorInterface $knpPaginator = null)
    {
    }

    /**
     * @template T
     * @param array<T> $items
     * @return array{items: array<T>, current_page:int, per_page:int, total_items:int, total_pages:int}
     */
    public function paginateArray(array $items, int $page, int $perPage = 10): array
    {
        $perPage = max(1, $perPage);

        if ($this->knpPaginator !== null) {
            $pagination = $this->knpPaginator->paginate($items, max(1, $page), $perPage);
            $totalItems = (int) $pagination->getTotalItemCount();
            $itemNumberPerPage = max(1, (int) $pagination->getItemNumberPerPage());
            $totalPages = max(1, (int) ceil($totalItems / $itemNumberPerPage));

            return [
                'items' => $pagination->getItems(),
                'current_page' => (int) $pagination->getCurrentPageNumber(),
                'per_page' => $itemNumberPerPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
            ];
        }

        $totalItems = count($items);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
        ];
    }
}
