<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Public;

use Lemonade\Framework\Component\Pagination\PaginationState;

/**
 * Represents one page of published auctions and its unpaginated count
 */
final readonly class PublishedAuctionCollection
{
    /**
     * Creates a page of published auctions
     *
     * @param list<PublishedAuction> $items
     */
    public function __construct(
        private array $items,
        private int $total,
        private int $page,
        private int $perPage,
    ) {}

    /**
     * Returns the auctions on this page
     *
     * @return list<PublishedAuction>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Returns the total number of matching auctions
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Returns the requested page number
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Returns the requested page size
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Creates framework pagination for the host's public URL context
     *
     * @param array<string, scalar|null> $query
     */
    public function getPagination(string $basePath, string $pageName = 'page', array $query = []): PaginationState
    {
        return new PaginationState(
            currentPage: $this->page,
            perPage: $this->perPage,
            total: $this->total,
            pageName: $pageName,
            basePath: $basePath,
            query: $query,
        );
    }
}
