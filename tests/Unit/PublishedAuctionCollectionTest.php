<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Tests\Unit;

use Lemonade\Cms\Auction\Public\PublishedAuctionCollection;
use Lemonade\Framework\Component\Pagination\PaginationState;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that a published auction page supplies framework pagination state
 */
final class PublishedAuctionCollectionTest extends TestCase
{
    /**
     * Preserves page totals and the host's filters in navigation URLs
     */
    public function testCreatesFrameworkPaginationForPublicRoute(): void
    {
        $collection = new PublishedAuctionCollection([], total: 18, page: 2, perPage: 5);

        $pagination = $collection->getPagination(
            basePath: '/auctions',
            pageName: 'p',
            query: ['tag' => 'municipal', 'p' => 2],
        );

        self::assertInstanceOf(PaginationState::class, $pagination);
        self::assertSame(2, $pagination->currentPage());
        self::assertSame(5, $pagination->perPage());
        self::assertSame(18, $pagination->total());
        self::assertSame(5, $pagination->offset());
        self::assertSame(4, $pagination->lastPage());
        self::assertSame('/auctions?tag=municipal&p=3', $pagination->url(3));
    }
}
