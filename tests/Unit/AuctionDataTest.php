<?php

declare(strict_types=1);

namespace Lemonade\Cms\Auction\Tests\Unit;

use DomainException;
use Lemonade\Cms\Auction\AuctionData;
use Lemonade\Cms\Content\CmsDomainMapping;
use PHPUnit\Framework\TestCase;

/**
 * Overuje typovane mapovani a validaci hodnot drazby
 */
final class AuctionDataTest extends TestCase
{
    public function testMapsDecimalAndLocalizedLocation(): void
    {
        $data = AuctionData::fromEditor([
            'starting_price' => '1234567890123.5',
            'auction_at' => '2028-02-29T14:30',
            'auction_state' => 'active',
            'external_url' => 'https://example.com/auction',
            'location' => ' Praha ',
        ]);
        self::assertSame([
            'root' => [
                'author_name' => null,
                'starting_price' => '1234567890123.50',
                'currency' => 'CZK',
                'auction_at' => '2028-02-29 14:30:00',
                'auction_state' => 'active',
                'external_url' => 'https://example.com/auction',
            ],
            'localized' => ['location' => 'Praha'],
        ], (new CmsDomainMapping(AuctionData::class))->values($data));
    }

    /**
     * @dataProvider invalidValues
     * @param array<string,mixed> $data
     */
    public function testRejectsInvalidValuesWithExistingKey(array $data, string $key): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage($key);
        AuctionData::fromEditor($data);
    }

    /**
     * @return iterable<string,array{array<string,mixed>,string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'price' => [['starting_price' => '1.001'], 'auction.validation.price_invalid'];
        yield 'date' => [['auction_at' => '2026-02-30T12:00'], 'auction.validation.date_invalid'];
        yield 'state' => [['auction_state' => 'published'], 'auction.validation.state_invalid'];
        yield 'url' => [['external_url' => 'javascript:alert(1)'], 'auction.validation.url_invalid'];
        yield 'location' => [['location' => str_repeat('x', 256)], 'auction.validation.location_invalid'];
    }
}
