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
    public function testMapsTextPriceAndLocalizedLocation(): void
    {
        $data = AuctionData::fromEditor([
            'starting_price' => '400 000 Kč',
            'auction_at' => '2028-02-29T14:30',
            'auction_state' => 'active',
            'external_url' => 'https://example.com/auction',
            'location' => ' Praha ',
        ]);
        self::assertSame([
            'root' => [
                'author_name' => null,
                'starting_price' => '400 000 Kč',
                'currency' => 'CZK',
                'auction_at' => '2028-02-29 14:30:00',
                'auction_state' => 'active',
                'external_url' => 'https://example.com/auction',
            ],
            'localized' => ['location' => 'Praha'],
        ], (new CmsDomainMapping(AuctionData::class))->values($data));
    }

    /**
     * Nepovinna cena zustava textova a rozpracovana data mohou byt prazdna
     */
    public function testOptionalTextPriceAndEmptyDomainValuesRemainValid(): void
    {
        foreach (['', '400 000 Kč', 'Cena na vyžádání'] as $price) {
            $data = AuctionData::fromEditor(['starting_price' => $price, 'auction_at' => '', 'auction_state' => '', 'location' => '', 'external_url' => '']);
            $values = (new CmsDomainMapping(AuctionData::class))->values($data);
            self::assertSame($price === '' ? null : $price, $values['root']['starting_price']);
        }
    }

    /**
     * Vice domenovych chyb zachova vlastni nazvy poli
     */
    public function testDomainErrorsAreCollectedByField(): void
    {
        try {
            AuctionData::fromEditor(['starting_price' => '-1', 'auction_at' => 'invalid', 'external_url' => 'javascript:alert(1)', 'auction_state' => 'unknown']);
            self::fail('Invalid domain accepted');
        } catch (\Lemonade\Cms\Content\CmsContentValidationException $exception) {
            foreach (['starting_price', 'auction_at', 'external_url', 'auction_state'] as $field) {
                self::assertArrayHasKey($field, $exception->errors());
            }
        }
        try {
            AuctionData::fromEditor(['location' => ['nested'], 'external_url' => new \stdClass()]);
            self::fail('Structured domain accepted');
        } catch (\Lemonade\Cms\Content\CmsContentValidationException $exception) {
            self::assertSame(['external_url', 'location'], array_keys($exception->errors()));
        }
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
        yield 'price' => [['starting_price' => '-1 Kč'], 'auction.validation.price_invalid'];
        yield 'date' => [['auction_at' => '2026-02-30T12:00'], 'auction.validation.date_invalid'];
        yield 'state' => [['auction_state' => 'published'], 'auction.validation.state_invalid'];
        yield 'url' => [['external_url' => 'javascript:alert(1)'], 'auction.validation.url_invalid'];
        yield 'location' => [['location' => str_repeat('x', 256)], 'auction.validation.location_invalid'];
    }
}
